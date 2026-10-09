<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use Carbon\Carbon;
use Database\Seeders\AccountingSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Generate unique journal entry number: JU-YYYYMM-XXXX
     */
    public static function generateEntryNumber(string $date): string
    {
        $ym = Carbon::parse($date)->format('Ym');
        $prefix = "JU-{$ym}-";

        $latest = JournalEntry::where('entry_number', 'like', "{$prefix}%")
            ->orderByDesc('entry_number')
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/JU-\d{6}-(\d+)/', $latest->entry_number, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        while (JournalEntry::where('entry_number', $prefix.str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT))->exists()) {
            $nextSeq++;
        }

        return $prefix.str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Post a balanced journal entry
     *
     * @param  array<int, array{account_code: string, debit: float|int, credit: float|int, memo?: ?string}>  $items
     */
    public static function postEntry(
        string $date,
        string $notes,
        array $items,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null
    ): ?JournalEntry {
        self::ensureChartOfAccountsExist();

        // Filter out zero amount lines
        $validItems = array_values(array_filter($items, function ($item) {
            $debit = (float) ($item['debit'] ?? 0);
            $credit = (float) ($item['credit'] ?? 0);

            return $debit > 0 || $credit > 0;
        }));

        if (empty($validItems)) {
            return null;
        }

        $totalDebit = round(collect($validItems)->sum('debit'), 2);
        $totalCredit = round(collect($validItems)->sum('credit'), 2);

        if ($totalDebit !== $totalCredit) {
            throw new \InvalidArgumentException("Jurnal tidak seimbang! Total Debet (Rp {$totalDebit}) != Total Kredit (Rp {$totalCredit})");
        }

        return DB::transaction(function () use ($date, $notes, $validItems, $referenceType, $referenceId, $userId) {
            // Jika update transaksi terkait, bersihkan entri sebelumnya
            if ($referenceType && $referenceId) {
                JournalEntry::where('reference_type', $referenceType)
                    ->where('reference_id', $referenceId)
                    ->delete();
            }

            $entry = JournalEntry::create([
                'entry_number' => self::generateEntryNumber($date),
                'entry_date' => $date,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => $userId ?? Auth::id(),
            ]);

            $accountsByCode = ChartOfAccount::all()->keyBy('code');

            foreach ($validItems as $row) {
                $code = $row['account_code'];
                $account = $accountsByCode->get($code) ?? ChartOfAccount::where('code', $code)->first();

                if ($account) {
                    $entry->items()->create([
                        'chart_of_account_id' => $account->id,
                        'debit' => $row['debit'] ?? 0,
                        'credit' => $row['credit'] ?? 0,
                        'memo' => $row['memo'] ?? null,
                    ]);
                }
            }

            return $entry;
        });
    }

    /**
     * Auto journal for Cash Transactions (Buku Kas)
     */
    public static function recordCashTransaction(CashTransaction $transaction): ?JournalEntry
    {
        if ($transaction->reference_type === 'purchase' || $transaction->reference_type === 'invoice_payment' || $transaction->reference_type === 'purchase_payment') {
            return null; // Handled directly by recordPurchase(), recordInvoicePayment(), and recordPurchaseDebtPayment() to avoid duplicate journals
        }

        $amount = (float) $transaction->amount;
        if ($amount <= 0) {
            return null;
        }

        $account = $transaction->account;
        $isBank = $account && (
            str_contains(strtolower($account->name), 'bank') ||
            str_contains(strtolower($account->name), 'bca') ||
            str_contains(strtolower($account->name), 'bri') ||
            str_contains(strtolower($account->name), 'mandiri') ||
            str_contains(strtolower($account->name), 'bni')
        );
        $cashCode = $isBank ? '1-1002' : '1-1001';

        $items = [];
        $notes = $transaction->description ?: "Transaksi Kas: {$transaction->category}";

        if ($transaction->type === 'expense') {
            $cat = strtolower($transaction->category);
            $expenseCode = '6-1099'; // default beban operasional

            // Check if matches specific raw material in database
            $matchedMaterial = RawMaterial::all()->first(function ($mat) use ($cat) {
                return str_contains($cat, strtolower($mat->name))
                    || str_contains(strtolower($mat->name), $cat);
            });

            if (str_contains($cat, 'gaji') || str_contains($cat, 'upah') || str_contains($cat, 'honor') || str_contains($cat, 'lembur') || str_contains($cat, 'thr') || str_contains($cat, 'bonus') || str_contains($cat, 'insentif')) {
                $expenseCode = '6-1007'; // Beban Gaji & Upah Karyawan / Kurir
            } elseif (str_contains($cat, 'hutang supplier') || str_contains($cat, 'bayar hutang') || str_contains($cat, 'pelunasan hutang') || str_contains($cat, 'utang supplier')) {
                $expenseCode = '2-1000'; // Pelunasan Hutang Usaha
            } elseif ($matchedMaterial) {
                $expenseCode = self::getAccountCodeForRawMaterial($matchedMaterial->id);
            } elseif (str_contains($cat, 'bahan') || str_contains($cat, 'baku') || str_contains($cat, 'kacang') || str_contains($cat, 'gula') || str_contains($cat, 'susu') || str_contains($cat, 'margarin') || str_contains($cat, 'wijen')) {
                $expenseCode = '1-1300'; // Pembelian bahan baku umum
            } elseif (str_contains($cat, 'bensin') || str_contains($cat, 'transport') || str_contains($cat, 'ongkir') || str_contains($cat, 'kurir') || str_contains($cat, 'antar')) {
                $expenseCode = '6-1001';
            } elseif (str_contains($cat, 'listrik') || str_contains($cat, 'air') || str_contains($cat, 'gas') || str_contains($cat, 'pln') || str_contains($cat, 'pdam') || str_contains($cat, 'elpiji')) {
                $expenseCode = '6-1002';
            } elseif (str_contains($cat, 'kemasan') || str_contains($cat, 'toples') || str_contains($cat, 'plastik') || str_contains($cat, 'label') || str_contains($cat, 'stiker') || str_contains($cat, 'pouch') || str_contains($cat, 'seal')) {
                $expenseCode = '6-1003';
            } elseif (str_contains($cat, 'rusak') || str_contains($cat, 'retur') || str_contains($cat, 'basi') || str_contains($cat, 'kadaluarsa') || str_contains($cat, 'reject')) {
                $expenseCode = '6-1004';
            } elseif (str_contains($cat, 'susut') || str_contains($cat, 'depresiasi')) {
                $expenseCode = '6-1005';
            }

            $items[] = ['account_code' => $expenseCode, 'debit' => $amount, 'credit' => 0, 'memo' => $transaction->category];
            $items[] = ['account_code' => $cashCode, 'debit' => 0, 'credit' => $amount, 'memo' => 'Kas Keluar: '.($account?->name ?? 'Kas')];
        } elseif ($transaction->type === 'income') {
            $cat = strtolower($transaction->category);
            if (str_contains($cat, 'modal') || str_contains($cat, 'investasi') || str_contains($cat, 'ekuitas') || str_contains($cat, 'setor') || str_contains($cat, 'tabungan') || str_contains($cat, 'mdl')) {
                $incomeCode = '3-1000'; // Modal Usaha Pemilik
            } elseif (str_contains($cat, 'piutang') || str_contains($cat, 'pelunasan') || str_contains($cat, 'tagihan')) {
                $incomeCode = '1-1200'; // Pelunasan Piutang Toko
            } elseif (str_contains($cat, 'pinjam') || str_contains($cat, 'utang') || str_contains($cat, 'hutang') || str_contains($cat, 'kredit')) {
                $incomeCode = '2-1000'; // Hutang Usaha
            } elseif (str_contains($cat, 'toko') || str_contains($cat, 'penjualan') || str_contains($cat, 'omset') || str_contains($cat, 'omzet') || str_contains($cat, 'konsinyasi') || str_contains($cat, 'jual') || str_contains($cat, 'laku')) {
                $incomeCode = '4-1000'; // Pendapatan Penjualan Konsinyasi
            } else {
                $incomeCode = '4-2000'; // Pendapatan Lain-lain
            }

            $items[] = ['account_code' => $cashCode, 'debit' => $amount, 'credit' => 0, 'memo' => 'Kas Masuk: '.($account?->name ?? 'Kas')];
            $items[] = ['account_code' => $incomeCode, 'debit' => 0, 'credit' => $amount, 'memo' => $transaction->category];
        } elseif ($transaction->type === 'prive') {
            $items[] = ['account_code' => '3-2000', 'debit' => $amount, 'credit' => 0, 'memo' => 'Prive'];
            $items[] = ['account_code' => $cashCode, 'debit' => 0, 'credit' => $amount, 'memo' => 'Penarikan dari '.($account?->name ?? 'Kas Usaha')];
        }

        return self::postEntry(
            $transaction->transaction_date->format('Y-m-d'),
            $notes,
            $items,
            'cash_transaction',
            $transaction->id
        );
    }

    /**
     * Auto journal for Raw Material Purchase (Pengadaan Bahan Baku)
     */
    public static function recordPurchase(RawMaterialPurchase $purchase): ?JournalEntry
    {
        $amount = (float) $purchase->total_amount;
        if ($amount <= 0) {
            return null;
        }

        self::ensureChartOfAccountsExist();

        $creditCode = match (strtolower($purchase->payment_method)) {
            'transfer', 'transfer_bank', 'bank' => '1-1002', // Kas Bank
            'tempo', 'utang', 'hutang' => '2-1000', // Hutang Usaha
            default => '1-1001', // Kas Tunai
        };

        $items = [];
        $purchase->loadMissing(['items.rawMaterial']);

        if ($purchase->items->isNotEmpty()) {
            foreach ($purchase->items as $item) {
                $subtotal = (float) $item->subtotal;
                if ($subtotal > 0) {
                    $code = self::getAccountCodeForRawMaterial($item->raw_material_id);
                    $unitStr = $item->rawMaterial?->display_unit ?? '';
                    $items[] = [
                        'account_code' => $code, // Persediaan Bahan Spesifik (+)
                        'debit' => $subtotal,
                        'credit' => 0,
                        'memo' => "Bahan: {$item->rawMaterial?->name} ({$item->quantity} {$unitStr})",
                    ];
                }
            }
        }

        if (empty($items)) {
            $items[] = [
                'account_code' => '1-1300', // Persediaan Bahan Baku (Umum)
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Pembelian Bahan Baku ({$purchase->purchase_number}) - Supplier: {$purchase->supplier_name}",
            ];
        }

        $items[] = [
            'account_code' => $creditCode,
            'debit' => 0,
            'credit' => $amount,
            'memo' => "Pembayaran Pembelian {$purchase->purchase_number}",
        ];

        return self::postEntry(
            Carbon::parse($purchase->purchase_date)->format('Y-m-d'),
            "Pembelian Bahan Baku {$purchase->purchase_number} dari {$purchase->supplier_name}",
            $items,
            'purchase',
            $purchase->id
        );
    }

    /**
     * Auto journal for Production Batch (Memasak Dapur -> Konversi Bahan Baku ke Produk Jadi)
     */
    public static function recordProduction(ProductionBatch $batch, float $materialCost = 0): ?JournalEntry
    {
        self::ensureChartOfAccountsExist();

        $batch->loadMissing(['batchMaterials.rawMaterial', 'product']);
        $productName = $batch->product?->name ?? 'Produk Jadi';

        $items = [];
        $totalCost = 0;

        if ($batch->batchMaterials->isNotEmpty()) {
            foreach ($batch->batchMaterials as $bm) {
                $cost = (float) $bm->subtotal_cost;
                if ($cost > 0) {
                    $totalCost += $cost;
                    $code = self::getAccountCodeForRawMaterial($bm->raw_material_id);
                    $unitStr = $bm->unit_name ?: ($bm->rawMaterial?->display_unit ?? '');
                    $items[] = [
                        'account_code' => $code, // Persediaan Bahan Spesifik (-)
                        'debit' => 0,
                        'credit' => $cost,
                        'memo' => "Pemakaian {$bm->rawMaterial?->name} ({$bm->actual_used_qty} {$unitStr}) untuk Batch {$batch->batch_code}",
                    ];
                }
            }
        }

        if ($totalCost <= 0) {
            $totalCost = $materialCost > 0 ? $materialCost : (float) $batch->total_material_cost;
            if ($totalCost > 0) {
                $items[] = [
                    'account_code' => '1-1300',
                    'debit' => 0,
                    'credit' => $totalCost,
                    'memo' => "Pemakaian Bahan Baku untuk Batch {$batch->batch_code}",
                ];
            }
        }

        if ($totalCost <= 0) {
            return null;
        }

        // Debit Persediaan Produk Jadi (+)
        array_unshift($items, [
            'account_code' => '1-1400',
            'debit' => $totalCost,
            'credit' => 0,
            'memo' => "Produksi {$batch->actual_qty_good} pcs {$productName} (Batch {$batch->batch_code})",
        ]);

        $date = $batch->completed_at ? $batch->completed_at->format('Y-m-d') : ($batch->started_at ? $batch->started_at->format('Y-m-d') : now()->format('Y-m-d'));

        return self::postEntry(
            $date,
            "Hasil Masak Produksi {$productName} ({$batch->batch_code})",
            $items,
            'production',
            $batch->id
        );
    }

    /**
     * Auto journal for Invoice Sale (Penjualan, Piutang, HPP, & Pengurangan Persediaan Produk Jadi)
     */
    public static function recordInvoiceSale(Invoice $invoice): ?JournalEntry
    {
        self::ensureChartOfAccountsExist();

        $totalAmount = (float) $invoice->total_amount;
        if ($totalAmount <= 0) {
            JournalEntry::where('reference_type', 'invoice_sale')
                ->where('reference_id', $invoice->id)
                ->delete();

            return null;
        }

        $date = Carbon::parse($invoice->invoice_date)->format('Y-m-d');
        $storeName = $invoice->store?->name ?? 'Toko Mitra';

        $invoice->loadMissing(['items.product', 'store']);

        $totalCOGS = 0.0;
        foreach ($invoice->items as $item) {
            $soldQty = (int) $item->quantity;
            if ($soldQty > 0 && $item->product) {
                $materialCost = (float) $item->product->material_cost;
                $costPerUnit = $materialCost > 0 ? $materialCost : (float) $item->product->consignment_price;
                $totalCOGS += $soldQty * $costPerUnit;
            }
        }
        $totalCOGS = round($totalCOGS, 2);

        $items = [
            [
                'account_code' => '1-1200', // Piutang Toko Konsinyasi (+)
                'debit' => $totalAmount,
                'credit' => 0,
                'memo' => "Piutang Penjualan Faktur {$invoice->invoice_number} ({$storeName})",
            ],
            [
                'account_code' => '4-1000', // Pendapatan Penjualan Konsinyasi (+)
                'debit' => 0,
                'credit' => $totalAmount,
                'memo' => "Pendapatan Faktur {$invoice->invoice_number} ({$storeName})",
            ],
        ];

        if ($totalCOGS > 0) {
            $items[] = [
                'account_code' => '5-1000', // Beban Pokok Penjualan (HPP) (+)
                'debit' => $totalCOGS,
                'credit' => 0,
                'memo' => "HPP Produk Terjual Faktur {$invoice->invoice_number}",
            ];
            $items[] = [
                'account_code' => '1-1400', // Persediaan Produk Jadi (-)
                'debit' => 0,
                'credit' => $totalCOGS,
                'memo' => "Pengurangan Stok Terjual Faktur {$invoice->invoice_number}",
            ];
        }

        return self::postEntry(
            $date,
            "Penjualan Faktur {$invoice->invoice_number} ({$storeName})",
            $items,
            'invoice_sale',
            $invoice->id
        );
    }

    /**
     * Auto journal & Cash Book record for Invoice Payment (Penerimaan Kas & Pelunasan Piutang)
     */
    public static function recordInvoicePayment(InvoicePayment $payment, ?int $accountId = null): ?JournalEntry
    {
        self::ensureChartOfAccountsExist();

        $amount = (float) $payment->amount;
        if ($amount <= 0) {
            return null;
        }

        $payment->loadMissing(['invoice.store']);
        $invoice = $payment->invoice;
        if (! $invoice) {
            return null;
        }

        $storeName = $invoice->store?->name ?? 'Toko Mitra';
        $isBank = in_array(strtolower($payment->payment_method), ['transfer_bank', 'transfer', 'qris', 'bank']);

        // 1. Resolve Cash Account for Cash Book (Buku Kas)
        $account = null;
        if ($accountId) {
            $account = Account::find($accountId);
        }

        if (! $account) {
            if ($isBank) {
                $account = Account::where('name', 'like', '%bank%')
                    ->orWhere('name', 'like', '%bca%')
                    ->orWhere('name', 'like', '%mandiri%')
                    ->orWhere('name', 'like', '%bri%')
                    ->first();
            }

            if (! $account) {
                $account = Account::where('name', 'like', '%kas%')
                    ->orWhere('name', 'like', '%tunai%')
                    ->first() ?? Account::first() ?? Account::firstOrCreate(
                        ['name' => 'Kas Tunai'],
                        [
                            'type' => 'business',
                            'balance' => 0.00,
                            'description' => 'Kas Tunai Usaha Utama',
                        ]
                    );
            }
        }

        if ($account) {
            $existingTx = CashTransaction::where('reference_type', 'invoice_payment')
                ->where('reference_id', $payment->id)
                ->first();

            $description = "Pelunasan Faktur {$invoice->invoice_number} ({$storeName}) - Ref: {$payment->payment_number}";

            if ($existingTx) {
                if ($existingTx->account_id === $account->id) {
                    $diff = $amount - (float) $existingTx->amount;
                    if ($diff !== 0.0) {
                        $account->increment('balance', $diff);
                    }
                } else {
                    $existingTx->account?->decrement('balance', $existingTx->amount);
                    $account->increment('balance', $amount);
                }

                $existingTx->update([
                    'transaction_date' => $payment->payment_date,
                    'account_id' => $account->id,
                    'type' => 'income',
                    'category' => 'Pelunasan Piutang Toko',
                    'amount' => $amount,
                    'description' => $description,
                ]);
            } else {
                CashTransaction::create([
                    'transaction_date' => $payment->payment_date,
                    'account_id' => $account->id,
                    'type' => 'income',
                    'category' => 'Pelunasan Piutang Toko',
                    'amount' => $amount,
                    'reference_type' => 'invoice_payment',
                    'reference_id' => $payment->id,
                    'description' => $description,
                ]);

                $account->increment('balance', $amount);
            }
        }

        // 2. Post Journal Entry (Debet Kas/Bank vs Kredit Piutang)
        $cashCode = $isBank ? '1-1002' : '1-1001';
        $paymentDate = Carbon::parse($payment->payment_date)->format('Y-m-d');

        $items = [
            [
                'account_code' => $cashCode, // Kas Tunai / Bank Usaha (+)
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Penerimaan Pembayaran Faktur {$invoice->invoice_number} ({$payment->payment_method})",
            ],
            [
                'account_code' => '1-1200', // Piutang Toko Konsinyasi (-)
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Pelunasan Piutang {$storeName} Faktur {$invoice->invoice_number}",
            ],
        ];

        return self::postEntry(
            $paymentDate,
            "Pembayaran Faktur {$invoice->invoice_number} ({$storeName})",
            $items,
            'invoice_payment',
            $payment->id
        );
    }

    /**
     * Delete payment records from Cash Book and General Ledger
     */
    public static function deleteInvoicePaymentRecords(InvoicePayment $payment): void
    {
        $cashTx = CashTransaction::where('reference_type', 'invoice_payment')
            ->where('reference_id', $payment->id)
            ->first();

        if ($cashTx) {
            $account = $cashTx->account;
            if ($account) {
                $account->decrement('balance', $cashTx->amount);
            }
            $cashTx->delete();
        }

        JournalEntry::where('reference_type', 'invoice_payment')
            ->where('reference_id', $payment->id)
            ->delete();
    }

    /**
     * Delete all accounting entries and cash transactions for an invoice
     */
    public static function deleteInvoiceRecords(Invoice $invoice): void
    {
        $invoice->loadMissing(['payments', 'items']);

        foreach ($invoice->payments as $payment) {
            self::deleteInvoicePaymentRecords($payment);
        }

        JournalEntry::where('reference_type', 'invoice_sale')
            ->where('reference_id', $invoice->id)
            ->delete();

        JournalEntry::where('reference_type', 'invoice_damaged_goods')
            ->where('reference_id', $invoice->id)
            ->delete();
    }

    /**
     * Synchronize all accounting & cash transactions for an invoice
     */
    public static function syncInvoiceAccounting(Invoice $invoice): void
    {
        $invoice->loadMissing(['items.product.recipes.rawMaterial', 'payments', 'store']);

        if ($invoice->status === 'dibatalkan') {
            self::deleteInvoiceRecords($invoice);

            return;
        }

        self::recordInvoiceSale($invoice);

        foreach ($invoice->payments as $payment) {
            self::recordInvoicePayment($payment);
        }

        if ($invoice->items->sum('damaged_quantity') > 0) {
            self::recordInvoiceDamagedGoods($invoice, $invoice->items->toArray());
        }
    }

    /**
     * Auto journal for Damaged / Expired Goods from Invoice Reconciliation
     */
    public static function recordInvoiceDamagedGoods(Invoice $invoice, array $itemsData): ?JournalEntry
    {
        self::ensureChartOfAccountsExist();

        $date = now()->format('Y-m-d');
        $storeName = $invoice->store?->name ?? 'Toko Mitra';

        $totalDamagedValue = 0.0;
        $memos = [];

        foreach ($itemsData as $item) {
            $qty = (int) ($item['damaged_quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $product = Product::with(['recipes.rawMaterial', 'unitModel'])->find($productId);

            $costPerUnit = 0.0;
            if ($product) {
                $materialCost = (float) $product->material_cost;
                $costPerUnit = $materialCost > 0 ? $materialCost : (float) ($item['unit_price'] ?? $product->consignment_price);
            } else {
                $costPerUnit = (float) ($item['unit_price'] ?? 0);
            }

            $subtotalCost = $qty * $costPerUnit;
            $totalDamagedValue += $subtotalCost;

            $productName = $product?->name ?? ($item['product_name'] ?? 'Produk');
            $unit = $product?->unit ?? ($item['unit'] ?? 'pcs');
            $memos[] = "{$productName} ({$qty} {$unit} @ Rp ".number_format($costPerUnit, 0, ',', '.').')';
        }

        $totalDamagedValue = round($totalDamagedValue, 2);

        // Jika tidak ada barang rusak (0), hapus jurnal sebelumnya jika ada
        if ($totalDamagedValue <= 0) {
            JournalEntry::where('reference_type', 'invoice_damaged_goods')
                ->where('reference_id', $invoice->id)
                ->delete();

            return null;
        }

        $memoStr = implode(', ', $memos);

        $items = [
            [
                'account_code' => '6-1004', // Beban Kerugian Barang Rusak / Kadaluarsa (+)
                'debit' => $totalDamagedValue,
                'credit' => 0,
                'memo' => "Beban Rusak/BS Toko {$storeName}: {$memoStr}",
            ],
            [
                'account_code' => '1-1400', // Persediaan Produk Jadi (-)
                'debit' => 0,
                'credit' => $totalDamagedValue,
                'memo' => "Pengurangan Stok Rusak/BS Faktur {$invoice->invoice_number}",
            ],
        ];

        return self::postEntry(
            $date,
            "Kerugian Barang Rusak/BS Toko {$storeName} (Faktur {$invoice->invoice_number})",
            $items,
            'invoice_damaged_goods',
            $invoice->id
        );
    }

    /**
     * Get Chart of Account code for specific raw material
     * e.g. ID 1 -> 1-1301, ID 2 -> 1-1302
     */
    public static function getAccountCodeForRawMaterial(int $rawMaterialId): string
    {
        return '1-13'.str_pad((string) $rawMaterialId, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Sync single raw material to Chart of Accounts
     */
    public static function syncMaterialAccount(RawMaterial $material): ChartOfAccount
    {
        $code = self::getAccountCodeForRawMaterial($material->id);

        return ChartOfAccount::updateOrCreate(
            ['code' => $code],
            [
                'name' => 'Persediaan Bahan - '.$material->name,
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_system' => false,
                'description' => "Persediaan fisik {$material->name} ({$material->display_unit}) untuk dapur produksi",
            ]
        );
    }

    /**
     * Record opening balance for a Cash/Bank account in Cash Book and General Ledger
     * Debit: 1-1001 (Kas Tunai) or 1-1002 (Kas Bank)
     * Credit: 3-1000 (Modal Usaha Pemilik)
     */
    public static function recordOpeningBalance(Account $account, float $amount, ?string $date = null): ?CashTransaction
    {
        if ($amount <= 0) {
            return null;
        }

        self::ensureChartOfAccountsExist();

        $txDate = $date ? Carbon::parse($date)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        $existingTx = CashTransaction::where('reference_type', 'opening_balance')
            ->where('reference_id', $account->id)
            ->first();

        if (! $existingTx) {
            $existingTx = CashTransaction::create([
                'transaction_date' => $txDate,
                'account_id' => $account->id,
                'type' => 'income',
                'category' => 'Setoran Modal',
                'amount' => $amount,
                'reference_type' => 'opening_balance',
                'reference_id' => $account->id,
                'description' => "Setoran Modal Saldo Awal: {$account->name}",
            ]);
        }

        self::recordCashTransaction($existingTx);

        return $existingTx;
    }

    /**
     * Record journal entry for a new fixed asset purchase
     * Debit: 1-2000 Aset Tetap Usaha
     * Credit: Cash/Bank account used to pay
     */
    public static function recordFixedAssetPurchase(FixedAsset $asset, ?int $cashAccountId = null): ?JournalEntry
    {
        self::ensureChartOfAccountsExist();

        // Ensure fixed asset COA exists
        ChartOfAccount::firstOrCreate(
            ['code' => '1-2000'],
            [
                'name' => 'Aset Tetap Usaha',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_system' => true,
                'description' => 'Nilai buku aset tetap: mesin, peralatan, kendaraan, inventaris usaha',
            ]
        );

        $items = [
            [
                'account_code' => '1-2000',
                'debit' => $asset->purchase_price,
                'credit' => 0,
                'memo' => "Pembelian Aset: {$asset->name}",
            ],
        ];

        // Credit side: cash account if provided, otherwise use equity (modal)
        if ($cashAccountId) {
            $cashAccount = Account::find($cashAccountId);
            if ($cashAccount) {
                // Decrement cash balance
                $cashAccount->decrement('balance', $asset->purchase_price);

                // Create CashTransaction in Cash Book if not already recorded
                $existingTx = CashTransaction::where('reference_type', 'fixed_asset_purchase')
                    ->where('reference_id', $asset->id)
                    ->first();

                if (! $existingTx) {
                    CashTransaction::create([
                        'transaction_date' => Carbon::parse($asset->purchase_date)->format('Y-m-d'),
                        'account_id' => $cashAccount->id,
                        'type' => 'expense',
                        'category' => 'Aset Tetap Usaha',
                        'amount' => $asset->purchase_price,
                        'reference_type' => 'fixed_asset_purchase',
                        'reference_id' => $asset->id,
                        'description' => "Pembelian Aset Tetap: {$asset->name} ({$asset->asset_code})",
                    ]);
                }

                // Map cash account to COA
                $isBank = (
                    str_contains(strtolower($cashAccount->name), 'bank') ||
                    str_contains(strtolower($cashAccount->name), 'bca') ||
                    str_contains(strtolower($cashAccount->name), 'bri') ||
                    str_contains(strtolower($cashAccount->name), 'mandiri') ||
                    str_contains(strtolower($cashAccount->name), 'bni')
                );
                $cashCode = $isBank ? '1-1002' : '1-1001';

                $items[] = [
                    'account_code' => $cashCode,
                    'debit' => 0,
                    'credit' => $asset->purchase_price,
                    'memo' => "Pembayaran aset: {$asset->name} via {$cashAccount->name}",
                ];
            } else {
                // Fallback: credit modal
                $items[] = [
                    'account_code' => '3-1000',
                    'debit' => 0,
                    'credit' => $asset->purchase_price,
                    'memo' => "Pembelian Aset: {$asset->name} (kontribusi modal)",
                ];
            }
        } else {
            $items[] = [
                'account_code' => '3-1000',
                'debit' => 0,
                'credit' => $asset->purchase_price,
                'memo' => "Pembelian Aset: {$asset->name} (kontribusi modal)",
            ];
        }

        return self::postEntry(
            date: $asset->purchase_date->format('Y-m-d'),
            notes: "Pencatatan aset tetap: {$asset->name} ({$asset->category})",
            items: $items,
            referenceType: 'fixed_asset',
            referenceId: $asset->id,
        );
    }

    /**
     * Remove raw material account if unused
     */
    public static function removeMaterialAccount(RawMaterial $material): void
    {
        $code = self::getAccountCodeForRawMaterial($material->id);
        $account = ChartOfAccount::where('code', $code)->first();

        if ($account && $account->journalItems()->count() === 0) {
            $account->delete();
        }
    }

    /**
     * Sync all existing raw materials to Chart of Accounts
     */
    public static function syncAllRawMaterialAccounts(): void
    {
        $materials = RawMaterial::all();
        foreach ($materials as $material) {
            self::syncMaterialAccount($material);
        }
    }

    /**
     * Record payment for Tempo/Credit Raw Material Purchase (Pelunasan Hutang Supplier)
     * Debit: 2-1000 Hutang Usaha
     * Credit: 1-1001 (Kas Tunai) or 1-1002 (Kas Bank)
     */
    public static function recordPurchaseDebtPayment(
        RawMaterialPurchase $purchase,
        Account $account,
        float $amount,
        ?string $date = null,
        ?string $notes = null
    ): ?JournalEntry {
        if ($amount <= 0) {
            return null;
        }

        self::ensureChartOfAccountsExist();

        $txDate = $date ? Carbon::parse($date)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $isBank = (
            str_contains(strtolower($account->name), 'bank') ||
            str_contains(strtolower($account->name), 'bca') ||
            str_contains(strtolower($account->name), 'bri') ||
            str_contains(strtolower($account->name), 'mandiri') ||
            str_contains(strtolower($account->name), 'bni')
        );
        $cashCode = $isBank ? '1-1002' : '1-1001';

        // 1. Decrement cash account
        $account->decrement('balance', $amount);

        // 2. Create cash transaction in Cash Book
        $purchase->loadMissing(['items.rawMaterial.unitModel']);
        $materialsStr = $purchase->items->map(function ($it) {
            $name = $it->rawMaterial?->name ?? 'Bahan';
            $unit = $it->rawMaterial?->display_unit ?? '';
            $qty = (float) $it->quantity;
            $formattedQty = number_format($qty, (floor($qty) == $qty ? 0 : 2), ',', '.');

            return "{$name} ({$formattedQty} {$unit})";
        })->filter()->implode(', ');

        $desc = $notes ?: ("Pelunasan Hutang Pembelian {$purchase->purchase_number}: ".($materialsStr ? "{$materialsStr} - " : '')."Supplier: {$purchase->supplier_name}");
        CashTransaction::create([
            'transaction_date' => $txDate,
            'account_id' => $account->id,
            'type' => 'expense',
            'category' => 'Pelunasan Hutang Supplier',
            'amount' => $amount,
            'reference_type' => 'purchase_payment',
            'reference_id' => $purchase->id,
            'description' => $desc,
        ]);

        // 3. Post Journal Entry
        $items = [
            [
                'account_code' => '2-1000', // Hutang Usaha (-)
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Pelunasan Hutang {$purchase->purchase_number} - Supplier: {$purchase->supplier_name}",
            ],
            [
                'account_code' => $cashCode, // Kas/Bank (-)
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Kas Keluar: {$account->name}",
            ],
        ];

        $journal = self::postEntry(
            $txDate,
            "Pelunasan Hutang Pembelian {$purchase->purchase_number} ({$purchase->supplier_name})",
            $items,
            'purchase_payment',
            $purchase->id
        );

        // 4. Update purchase
        $newPaid = (float) $purchase->paid_amount + $amount;
        $purchase->paid_amount = $newPaid;
        if ($newPaid >= (float) $purchase->total_amount) {
            $purchase->payment_status = 'lunas';
        }
        $purchase->paid_at = Carbon::parse($txDate);
        $purchase->paid_account_id = $account->id;
        $purchase->save();

        return $journal;
    }

    /**
     * Record monthly depreciation for Fixed Asset
     * Debit: 6-1005 (Beban Penyusutan Aset Tetap)
     * Credit: 1-2100 (Akumulasi Penyusutan Aset Tetap)
     */
    public static function recordFixedAssetDepreciation(
        FixedAsset $asset,
        float $amount,
        ?string $date = null,
        ?string $notes = null
    ): ?JournalEntry {
        if ($amount <= 0) {
            return null;
        }

        self::ensureChartOfAccountsExist();

        $depDate = $date ? Carbon::parse($date)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $desc = $notes ?: "Penyusutan Aset Tetap: {$asset->name} ({$asset->asset_code})";

        $items = [
            [
                'account_code' => '6-1005', // Beban Penyusutan Aset Tetap (+)
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Beban Depresiasi {$asset->name} ({$asset->asset_code})",
            ],
            [
                'account_code' => '1-2100', // Akumulasi Penyusutan Aset Tetap (+)
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Akum. Depresiasi {$asset->name} ({$asset->asset_code})",
            ],
        ];

        $journal = self::postEntry(
            $depDate,
            $desc,
            $items,
            'fixed_asset_depreciation',
            $asset->id
        );

        // Update asset
        $newAccum = (float) $asset->accumulated_depreciation + $amount;
        $asset->accumulated_depreciation = $newAccum;
        $asset->book_value = max(0, (float) $asset->purchase_price - $newAccum);
        $asset->last_depreciation_date = $depDate;
        $asset->save();

        return $journal;
    }

    /**
     * Auto journal for Stock Opname / Physical Inventory Adjustment
     * Model can be RawMaterial or Product
     *
     * If diffQty < 0 (Selisih Kurang / Kehilangan / Rusak):
     *   Debit: 6-1006 Beban Selisih Stok Opname / Kehilangan Persediaan
     *   Credit: Persediaan (Bahan Baku / Produk Jadi)
     *
     * If diffQty > 0 (Selisih Lebih / Penyesuaian Positif):
     *   Debit: Persediaan (Bahan Baku / Produk Jadi)
     *   Credit: 6-1006 Beban Selisih Stok Opname / Koreksi Persediaan
     */
    public static function recordStockAdjustment(
        RawMaterial|Product $item,
        float $diffQty,
        float $costPerUnit,
        string $reason = 'Stock Opname',
        ?string $date = null
    ): ?JournalEntry {
        if ($diffQty == 0) {
            return null;
        }

        self::ensureChartOfAccountsExist();

        $totalValue = round(abs($diffQty) * $costPerUnit, 2);
        if ($totalValue <= 0) {
            return null;
        }

        $adjDate = $date ? Carbon::parse($date)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        if ($item instanceof RawMaterial) {
            $inventoryCode = self::getAccountCodeForRawMaterial($item->id);
            $itemName = "Bahan Baku: {$item->name}";
            $unit = $item->display_unit;
        } else {
            $inventoryCode = '1-1400'; // Persediaan Produk Jadi
            $itemName = "Produk Jadi: {$item->name}";
            $unit = $item->unitModel?->short_name ?? $item->unit ?? 'pcs';
        }

        $formattedDiff = ($diffQty > 0 ? '+' : '').number_format($diffQty, 2, ',', '.').' '.$unit;
        $memo = "Stock Opname {$itemName} ({$formattedDiff}) - {$reason}";

        $items = [];
        if ($diffQty < 0) {
            // Selisih Kurang (Kehilangan / Rusak)
            $items[] = [
                'account_code' => '6-1006', // Beban Selisih Stok Opname
                'debit' => $totalValue,
                'credit' => 0,
                'memo' => $memo,
            ];
            $items[] = [
                'account_code' => $inventoryCode, // Persediaan (-)
                'debit' => 0,
                'credit' => $totalValue,
                'memo' => "Penurunan persediaan fisik: {$item->name}",
            ];
        } else {
            // Selisih Lebih (Temuan / Penyesuaian Positif)
            $items[] = [
                'account_code' => $inventoryCode, // Persediaan (+)
                'debit' => $totalValue,
                'credit' => 0,
                'memo' => "Peningkatan persediaan fisik: {$item->name}",
            ];
            $items[] = [
                'account_code' => '6-1006', // Koreksi Beban Persediaan
                'debit' => 0,
                'credit' => $totalValue,
                'memo' => $memo,
            ];
        }

        $refType = ($item instanceof RawMaterial) ? 'stock_opname_material' : 'stock_opname_product';

        return self::postEntry(
            $adjDate,
            "Penyesuaian Fisik (Stock Opname) {$itemName}: {$reason}",
            $items,
            $refType,
            $item->id
        );
    }

    /**
     * Ensure chart of accounts and all raw material sub-accounts exist.
     */
    public static function ensureChartOfAccountsExist(): void
    {
        if (ChartOfAccount::whereIn('code', ['1-2000', '1-2100', '6-1005', '6-1006', '6-1007'])->count() < 5) {
            $seeder = new AccountingSeeder;
            $seeder->run();
        }

        self::syncAllRawMaterialAccounts();
    }
}
