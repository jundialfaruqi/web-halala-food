<?php

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public Invoice $invoice;

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

    public function with(): array
    {
        return [
            'businessSetting' => \App\Models\BusinessSetting::getSettings(),
        ];
    }
};
