<?php

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Store;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Faktur & Piutang Toko - Halala Food')] class extends Component
{
    /**
     * Cancel an invoice if unpaid or partially paid.
     */
    public function cancelInvoice(int $id): array
    {
        if (Gate::denies('faktur-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk membatalkan faktur ini.'];
        }

        $invoice = Invoice::find($id);
        if (! $invoice) {
            return ['success' => false, 'message' => 'Data faktur tidak ditemukan.'];
        }

        if ($invoice->status === 'lunas') {
            return ['success' => false, 'message' => 'Faktur yang sudah lunas tidak dapat dibatalkan.'];
        }

        if ($invoice->status === 'dibatalkan') {
            return ['success' => false, 'message' => 'Faktur ini sudah dalam status dibatalkan.'];
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'dibatalkan']);
            AccountingService::deleteInvoiceRecords($invoice);
        });

        return ['success' => true, 'message' => "Faktur {$invoice->invoice_number} berhasil dibatalkan."];
    }

    /**
     * Delete an invoice.
     */
    public function deleteInvoice(int $id): array
    {
        if (Gate::denies('faktur-delete')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk menghapus faktur.'];
        }

        $invoice = Invoice::find($id);
        if (! $invoice) {
            return ['success' => false, 'message' => 'Data faktur tidak ditemukan.'];
        }

        if ($invoice->status === 'lunas') {
            return ['success' => false, 'message' => 'Faktur yang telah lunas tidak boleh dihapus demi integritas data keuangan.'];
        }

        $invoiceNumber = $invoice->invoice_number;

        DB::transaction(function () use ($invoice) {
            AccountingService::deleteInvoiceRecords($invoice);
            $invoice->payments()->delete();
            $invoice->items()->delete();
            $invoice->delete();
        });

        return ['success' => true, 'message' => "Faktur {$invoiceNumber} berhasil dihapus."];
    }

    public function with(): array
    {
        $invoices = Invoice::with(['store', 'creator', 'items.product', 'payments'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'invoice_date' => $inv->invoice_date?->format('Y-m-d'),
                    'invoice_date_formatted' => $inv->invoice_date?->translatedFormat('d M Y') ?? '-',
                    'due_date' => $inv->due_date?->format('Y-m-d'),
                    'due_date_formatted' => $inv->due_date?->translatedFormat('d M Y') ?? '-',
                    'is_overdue' => $inv->is_overdue,
                    'subtotal' => (float) $inv->subtotal,
                    'discount' => (float) $inv->discount,
                    'total_amount' => (float) $inv->total_amount,
                    'paid_amount' => (float) $inv->paid_amount,
                    'remaining_balance' => (float) $inv->remaining_balance,
                    'status' => $inv->status,
                    'status_label' => $inv->status_label,
                    'notes' => $inv->notes,
                    'store' => $inv->store ? [
                        'id' => $inv->store->id,
                        'name' => $inv->store->name,
                        'owner_name' => $inv->store->owner_name,
                        'phone' => $inv->store->phone,
                        'route' => $inv->store->route,
                    ] : null,
                    'items_count' => $inv->items->count(),
                    'payments_count' => $inv->payments->count(),
                    'can_edit' => $inv->status !== 'lunas' && $inv->status !== 'dibatalkan',
                ];
            });

        $stores = Store::where('is_active', true)->orderBy('name')->get();

        return [
            'invoices' => $invoices,
            'stores' => $stores,
        ];
    }
};
