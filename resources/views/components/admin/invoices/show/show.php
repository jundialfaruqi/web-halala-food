<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public Invoice $invoice;

    // Reconciliation Form State (Saat Barang Dijemput & Ditagih)
    public bool $showReconciliation = false;

    /**
     * @var array<int, array{
     *     id: int,
     *     product_id: int,
     *     product_name: string,
     *     unit: string,
     *     delivered_quantity: int,
     *     remaining_quantity: int,
     *     damaged_quantity: int,
     *     returned_quantity: int,
     *     quantity: int,
     *     unit_price: float,
     *     subtotal: float
     * }>
     */
    public array $reconciliationItems = [];

    // Payment Form Fields
    public ?float $payment_amount = null;
    public string $payment_date = '';
    public string $payment_method = 'tunai';
    public ?string $reference_number = '';
    public ?string $payment_notes = '';

    public function mount(Invoice $invoice)
    {
        if (Gate::denies('faktur-view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data faktur tagihan.');
        }

        $this->invoice = $invoice->load([
            'store',
            'delivery',
            'creator',
            'items.product.unitModel',
            'payments.user',
        ]);

        $this->resetPaymentForm();
    }

    public function resetPaymentForm(): void
    {
        $this->payment_amount = (float) $this->invoice->remaining_balance > 0 ? (float) $this->invoice->remaining_balance : null;
        $this->payment_date = now()->toDateString();
        $this->payment_method = 'tunai';
        $this->reference_number = '';
        $this->payment_notes = '';
    }

    public function title(): string
    {
        return "Faktur {$this->invoice->invoice_number} - Halala Food";
    }

    public function fillFullPayment(): void
    {
        $this->payment_amount = (float) $this->invoice->remaining_balance;
    }

    public function openReconciliation(): void
    {
        $this->reconciliationItems = [];
        foreach ($this->invoice->items as $item) {
            $delivered = $item->delivered_quantity !== null && $item->delivered_quantity > 0
                ? (int) $item->delivered_quantity
                : (int) $item->quantity;
            $remaining = (int) ($item->remaining_quantity ?? 0);
            $damaged = (int) ($item->damaged_quantity ?? 0);
            $returned = (int) ($item->returned_quantity ?? 0);

            $hasPriorReconciliation = $item->remaining_quantity > 0 || $item->damaged_quantity > 0 || $item->returned_quantity > 0 || ($item->delivered_quantity !== null && $item->delivered_quantity != $item->quantity);
            $sold = $hasPriorReconciliation ? max(0, $delivered - $remaining - $damaged - $returned) : (int) $item->quantity;
            $price = (float) $item->unit_price;

            $this->reconciliationItems[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Produk',
                'unit' => $item->product?->unitModel?->name ?? $item->product?->unit ?? 'pcs',
                'delivered_quantity' => $delivered,
                'remaining_quantity' => $remaining,
                'damaged_quantity' => $damaged,
                'returned_quantity' => $returned,
                'quantity' => $sold,
                'unit_price' => $price,
                'subtotal' => $sold * $price,
            ];
        }
        $this->showReconciliation = true;
    }

    public function closeReconciliation(): void
    {
        $this->showReconciliation = false;
        $this->reconciliationItems = [];
    }

    public function updatedReconciliationItems(mixed $value, ?string $key = null): void
    {
        if (! $key) {
            return;
        }

        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            if (in_array($field, ['remaining_quantity', 'damaged_quantity', 'returned_quantity'])) {
                if (isset($this->reconciliationItems[$index])) {
                    $delivered = (int) ($this->reconciliationItems[$index]['delivered_quantity'] ?? 0);
                    $remaining = max(0, (int) ($this->reconciliationItems[$index]['remaining_quantity'] ?? 0));
                    $damaged = max(0, (int) ($this->reconciliationItems[$index]['damaged_quantity'] ?? 0));
                    $returned = max(0, (int) ($this->reconciliationItems[$index]['returned_quantity'] ?? 0));

                    $sold = max(0, $delivered - $remaining - $damaged - $returned);
                    $price = (float) ($this->reconciliationItems[$index]['unit_price'] ?? 0);

                    $this->reconciliationItems[$index]['remaining_quantity'] = $remaining;
                    $this->reconciliationItems[$index]['damaged_quantity'] = $damaged;
                    $this->reconciliationItems[$index]['returned_quantity'] = $returned;
                    $this->reconciliationItems[$index]['quantity'] = $sold;
                    $this->reconciliationItems[$index]['subtotal'] = $sold * $price;
                }
            }
        }
    }

    public function saveReconciliation(): void
    {
        if (Gate::denies('faktur-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk merekonsiliasi faktur.');
        }

        if ($this->invoice->status === 'dibatalkan') {
            session()->flash('toast', [
                'message' => 'Faktur yang telah dibatalkan tidak dapat direkonsiliasi.',
                'type' => 'error',
            ]);
            return;
        }

        $this->validate([
            'reconciliationItems' => ['required', 'array', 'min:1'],
            'reconciliationItems.*.remaining_quantity' => ['required', 'integer', 'min:0'],
            'reconciliationItems.*.damaged_quantity' => ['required', 'integer', 'min:0'],
            'reconciliationItems.*.returned_quantity' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($this->reconciliationItems as $idx => $rItem) {
            $delivered = (int) $rItem['delivered_quantity'];
            $totalOut = (int) $rItem['remaining_quantity'] + (int) $rItem['damaged_quantity'] + (int) $rItem['returned_quantity'];
            if ($totalOut > $delivered) {
                $this->addError("reconciliationItems.{$idx}.remaining_quantity", "Total sisa, rusak, dan retur ({$totalOut}) melebihi jumlah terkirim ({$delivered}) untuk {$rItem['product_name']}.");
                return;
            }
        }

        DB::transaction(function () {
            foreach ($this->reconciliationItems as $rItem) {
                $itemModel = InvoiceItem::where('invoice_id', $this->invoice->id)->find($rItem['id']);
                if ($itemModel) {
                    $oldReturned = (int) ($itemModel->returned_quantity ?? 0);
                    $newReturned = (int) $rItem['returned_quantity'];
                    $diffReturned = $newReturned - $oldReturned;

                    if ($diffReturned !== 0) {
                        Product::where('id', $itemModel->product_id)->increment('stock_ready', $diffReturned);
                    }

                    $delivered = (int) $rItem['delivered_quantity'];
                    $remaining = (int) $rItem['remaining_quantity'];
                    $damaged = (int) $rItem['damaged_quantity'];
                    $sold = max(0, $delivered - $remaining - $damaged - $newReturned);
                    $price = (float) $rItem['unit_price'];

                    $itemModel->update([
                        'delivered_quantity' => $delivered,
                        'remaining_quantity' => $remaining,
                        'damaged_quantity' => $damaged,
                        'returned_quantity' => $newReturned,
                        'quantity' => $sold,
                        'subtotal' => $sold * $price,
                    ]);
                }
            }

            $newSubtotal = (float) InvoiceItem::where('invoice_id', $this->invoice->id)->sum('subtotal');
            $discount = (float) $this->invoice->discount;
            $newTotalAmount = max(0.0, $newSubtotal - $discount);
            $paidAmount = (float) $this->invoice->paid_amount;
            $newRemainingBalance = max(0.0, $newTotalAmount - $paidAmount);

            $newStatus = 'belum_dibayar';
            if ($paidAmount >= $newTotalAmount && $newTotalAmount > 0) {
                $newStatus = 'lunas';
            } elseif ($paidAmount > 0) {
                $newStatus = 'sebagian';
            }

            $this->invoice->update([
                'subtotal' => $newSubtotal,
                'total_amount' => $newTotalAmount,
                'remaining_balance' => $newRemainingBalance,
                'status' => $newStatus,
            ]);
        });

        $this->invoice->refresh();
        $this->invoice->load(['items.product.unitModel', 'payments.user']);
        $this->resetPaymentForm();
        $this->showReconciliation = false;

        session()->flash('toast', [
            'message' => 'Rekonsiliasi titip jual berhasil disimpan. Tagihan baru sebesar Rp ' . number_format($this->invoice->total_amount, 0, ',', '.') . '.',
            'type' => 'success',
        ]);
    }

    public function recordPayment(): void
    {
        if (Gate::denies('faktur-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencatat pembayaran faktur.');
        }

        if ($this->invoice->status === 'dibatalkan') {
            session()->flash('toast', [
                'message' => 'Faktur yang telah dibatalkan tidak dapat menerima pembayaran.',
                'type' => 'error',
            ]);
            return;
        }

        $remaining = (float) $this->invoice->remaining_balance;
        if ($remaining <= 0) {
            session()->flash('toast', [
                'message' => 'Faktur tagihan ini sudah lunas.',
                'type' => 'warning',
            ]);
            return;
        }

        $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:1', 'max:' . $remaining],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:tunai,transfer_bank,qris'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_amount.required' => 'Nominal pembayaran wajib diisi.',
            'payment_amount.min' => 'Nominal pembayaran minimal Rp 1.',
            'payment_amount.max' => 'Nominal pembayaran tidak boleh melebihi sisa piutang (Rp ' . number_format($remaining, 0, ',', '.') . ').',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'payment_method.required' => 'Pilih metode pembayaran.',
        ]);

        $amount = (float) $this->payment_amount;

        DB::transaction(function () use ($amount) {
            InvoicePayment::create([
                'invoice_id' => $this->invoice->id,
                'payment_number' => InvoicePayment::generatePaymentNumber(),
                'user_id' => Auth::id(),
                'payment_date' => $this->payment_date,
                'amount' => $amount,
                'payment_method' => $this->payment_method,
                'reference_number' => $this->reference_number,
                'notes' => $this->payment_notes,
            ]);

            $this->invoice->recalculateStatusAndBalance();
        });

        $this->invoice->refresh();
        $this->invoice->load(['payments.user']);
        $this->resetPaymentForm();

        session()->flash('toast', [
            'message' => 'Pembayaran sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil dicatat.',
            'type' => 'success',
        ]);
    }

    public function deletePayment(int $paymentId): void
    {
        if (Gate::denies('faktur-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus pembayaran.');
        }

        $payment = InvoicePayment::where('invoice_id', $this->invoice->id)->find($paymentId);
        if (! $payment) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $payment->delete();
            $this->invoice->recalculateStatusAndBalance();
        });

        $this->invoice->refresh();
        $this->invoice->load(['payments.user']);
        $this->resetPaymentForm();

        session()->flash('toast', [
            'message' => 'Catatan pembayaran berhasil dihapus dan saldo piutang diperbarui.',
            'type' => 'success',
        ]);
    }

    public function cancelInvoice(): void
    {
        if (Gate::denies('faktur-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membatalkan faktur.');
        }

        if ((float) $this->invoice->paid_amount > 0) {
            session()->flash('toast', [
                'message' => 'Faktur yang sudah memiliki riwayat pembayaran tidak dapat dibatalkan. Hapus pembayaran terlebih dahulu jika ingin membatalkan.',
                'type' => 'error',
            ]);
            return;
        }

        $this->invoice->update(['status' => 'dibatalkan']);
        $this->invoice->refresh();

        session()->flash('toast', [
            'message' => 'Faktur tagihan berhasil dibatalkan.',
            'type' => 'success',
        ]);
    }

    public function getWhatsappUrlProperty(): string
    {
        $setting = \App\Models\BusinessSetting::getSettings();
        $store = $this->invoice->store;
        $companyName = $setting->company_name ?: 'Halala Food';

        $phone = preg_replace('/[^0-9]/', '', $store->phone ?? '');
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $lines = [];
        $recipientGreeting = $store->owner_name ? "{$store->name} (Bpk/Ibu {$store->owner_name})" : $store->name;
        $lines[] = "Halo *{$recipientGreeting}*,";
        $lines[] = "";
        $lines[] = "Berikut kami sampaikan rincian tagihan faktur konsinyasi dari *{$companyName}*:";
        $lines[] = "• No. Faktur: *{$this->invoice->invoice_number}*";
        $lines[] = "• Tanggal: " . ($this->invoice->invoice_date?->translatedFormat('d F Y') ?? '-');
        $lines[] = "• Jatuh Tempo: " . ($this->invoice->due_date?->translatedFormat('d F Y') ?? '-');
        $lines[] = "";
        $lines[] = "*Rincian Produk:*";
        foreach ($this->invoice->items as $item) {
            $prodName = $item->product?->name ?? 'Produk';
            $unitName = $item->product?->unitModel?->name ?? $item->product?->unit ?? 'pcs';
            $lines[] = "- {$prodName} ({$item->quantity} {$unitName}) = Rp " . number_format($item->subtotal, 0, ',', '.');
        }
        $lines[] = "";
        $lines[] = "Total Tagihan: *Rp " . number_format($this->invoice->total_amount, 0, ',', '.') . "*";
        if ((float) $this->invoice->paid_amount > 0) {
            $lines[] = "Sudah Dibayar: Rp " . number_format($this->invoice->paid_amount, 0, ',', '.');
        }
        $lines[] = "*Sisa Tagihan: Rp " . number_format($this->invoice->remaining_balance, 0, ',', '.') . "*";

        if (! empty($setting->bank_accounts) && is_array($setting->bank_accounts)) {
            $lines[] = "";
            $lines[] = "*Rekening Pembayaran Resmi:*";
            foreach ($setting->bank_accounts as $bank) {
                if (! empty($bank['bank_name']) && ! empty($bank['account_number'])) {
                    $accName = $bank['account_name'] ?? $bank['account_holder'] ?? 'Halala Food CV';
                    $lines[] = "- {$bank['bank_name']}: {$bank['account_number']} a.n {$accName}";
                }
            }
        }

        $lines[] = "";
        $lines[] = "Terima kasih atas kerja samanya.";

        $message = implode("\n", $lines);

        $baseUrl = 'https://api.whatsapp.com/send?';
        $params = ['text' => $message];
        if (! empty($phone)) {
            $params['phone'] = $phone;
        }

        return $baseUrl . http_build_query($params);
    }

    public function with(): array
    {
        return [
            'businessSetting' => \App\Models\BusinessSetting::getSettings(),
        ];
    }
};
