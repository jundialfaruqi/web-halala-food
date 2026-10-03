<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\ProductionBatch;
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
        $count = JournalEntry::where('entry_number', 'like', "JU-{$ym}-%")->count() + 1;

        return 'JU-'.$ym.'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
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
            $matchedMaterial = \App\Models\RawMaterial::all()->first(function ($mat) use ($cat) {
                return str_contains($cat, strtolower($mat->name))
                    || str_contains(strtolower($mat->name), $cat);
            });

            if ($matchedMaterial) {
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
     * Auto journal for Invoices / Titip Jual Penagihan Selesai
     */
    public static function recordInvoiceSettlement(Invoice $invoice, float $amountPaid, float $cogsCost = 0): ?JournalEntry
    {
        $totalAmount = (float) $invoice->total_amount;
        if ($totalAmount <= 0) {
            return null;
        }

        $date = Carbon::parse($invoice->invoice_date)->format('Y-m-d');
        $storeName = $invoice->store?->name ?? 'Toko Mitra';
        $items = [];

        // 1. Kas / Piutang vs Pendapatan Penjualan
        if ($amountPaid > 0) {
            $items[] = [
                'account_code' => '1-1001', // Kas Tunai Usaha
                'debit' => min($amountPaid, $totalAmount),
                'credit' => 0,
                'memo' => "Penerimaan Kas Pembayaran dari {$storeName}",
            ];
        }

        if ($amountPaid < $totalAmount) {
            $unpaid = $totalAmount - $amountPaid;
            $items[] = [
                'account_code' => '1-1200', // Piutang Toko Konsinyasi
                'debit' => $unpaid,
                'credit' => 0,
                'memo' => "Sisa Piutang Toko {$storeName}",
            ];
        }

        $items[] = [
            'account_code' => '4-1000', // Pendapatan Penjualan Konsinyasi
            'debit' => 0,
            'credit' => $totalAmount,
            'memo' => "Faktur Penjualan {$invoice->invoice_number} ({$storeName})",
        ];

        // 2. HPP & Pengurangan Produk Jadi jika ada
        if ($cogsCost > 0) {
            $items[] = [
                'account_code' => '5-1000', // HPP
                'debit' => $cogsCost,
                'credit' => 0,
                'memo' => "HPP Produk Terjual Faktur {$invoice->invoice_number}",
            ];
            $items[] = [
                'account_code' => '1-1400', // Persediaan Produk Jadi
                'debit' => 0,
                'credit' => $cogsCost,
                'memo' => "Pengurangan Stok Produk Jadi Faktur {$invoice->invoice_number}",
            ];
        }

        return self::postEntry(
            $date,
            "Penagihan Faktur {$invoice->invoice_number} ({$storeName})",
            $items,
            'invoice',
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
    public static function syncMaterialAccount(\App\Models\RawMaterial $material): ChartOfAccount
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
     * Record journal entry for a new fixed asset purchase
     * Debit: 1-2000 Aset Tetap Usaha
     * Credit: Cash/Bank account used to pay
     */
    public static function recordFixedAssetPurchase(\App\Models\FixedAsset $asset, ?int $cashAccountId = null): ?JournalEntry
    {
        self::ensureChartOfAccountsExist();

        // Ensure fixed asset COA exists
        ChartOfAccount::firstOrCreate(
            ['code' => '1-2000'],
            [
                'name'           => 'Aset Tetap Usaha',
                'type'           => 'asset',
                'normal_balance' => 'debit',
                'is_system'      => true,
                'description'    => 'Nilai buku aset tetap: mesin, peralatan, kendaraan, inventaris usaha',
            ]
        );

        $items = [
            [
                'account_code' => '1-2000',
                'debit'        => $asset->purchase_price,
                'credit'       => 0,
                'memo'         => "Pembelian Aset: {$asset->name}",
            ],
        ];

        // Credit side: cash account if provided, otherwise use equity (modal)
        if ($cashAccountId) {
            $cashAccount = \App\Models\Account::find($cashAccountId);
            if ($cashAccount) {
                // Decrement cash balance
                $cashAccount->decrement('balance', $asset->purchase_price);

                // Map cash account to COA — use the closest code
                $cashCoa = ChartOfAccount::where('name', 'like', '%Kas%')
                    ->where('normal_balance', 'debit')
                    ->first();

                $items[] = [
                    'account_code' => $cashCoa?->code ?? '1-1001',
                    'debit'        => 0,
                    'credit'       => $asset->purchase_price,
                    'memo'         => "Pembayaran aset: {$asset->name} via {$cashAccount->name}",
                ];
            } else {
                // Fallback: credit modal
                $items[] = [
                    'account_code' => '3-1000',
                    'debit'        => 0,
                    'credit'       => $asset->purchase_price,
                    'memo'         => "Pembelian Aset: {$asset->name} (kontribusi modal)",
                ];
            }
        } else {
            $items[] = [
                'account_code' => '3-1000',
                'debit'        => 0,
                'credit'       => $asset->purchase_price,
                'memo'         => "Pembelian Aset: {$asset->name} (kontribusi modal)",
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
    public static function removeMaterialAccount(\App\Models\RawMaterial $material): void
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
        $materials = \App\Models\RawMaterial::all();
        foreach ($materials as $material) {
            self::syncMaterialAccount($material);
        }
    }

    /**
     * Ensure chart of accounts and all raw material sub-accounts exist.
     */
    public static function ensureChartOfAccountsExist(): void
    {
        if (ChartOfAccount::count() === 0) {
            $seeder = new AccountingSeeder;
            $seeder->run();
        }

        self::syncAllRawMaterialAccounts();
    }
}
