<?php

use App\Models\Delivery;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public Delivery $delivery;

    // Handover Confirmation Form Fields
    public string $recipient_name = '';
    public ?string $recipient_phone = '';
    public ?string $handover_notes = '';

    public function mount(Delivery $delivery)
    {
        if (Gate::denies('pengantaran-view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data pengantaran.');
        }

        $this->delivery = $delivery->load(['store', 'courier', 'creator', 'items.product.unitModel']);

        $this->recipient_name = $delivery->recipient_name ?? $delivery->store?->owner_name ?? '';
        $this->recipient_phone = $delivery->recipient_phone ?? $delivery->store?->phone ?? '';
    }

    public function title(): string
    {
        return "Surat Jalan {$this->delivery->delivery_number} - Halala Food";
    }

    public function startDelivery(): void
    {
        if (Gate::denies('pengantaran-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah status pengantaran.');
        }

        if ($this->delivery->status !== 'diproses') {
            return;
        }

        $this->delivery->update([
            'status' => 'dikirim',
            'dispatched_at' => now(),
        ]);

        $this->delivery->refresh();

        session()->flash('toast', [
            'message' => "Surat jalan {$this->delivery->delivery_number} kini dalam perjalanan.",
            'type' => 'success',
        ]);
    }

    public function completeDelivery(): void
    {
        if (Gate::denies('pengantaran-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyelesaikan pengantaran.');
        }

        if ($this->delivery->status !== 'dikirim') {
            return;
        }

        $this->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'handover_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'recipient_name.required' => 'Nama staf/pemilik toko penerima wajib diisi sebagai bukti serah terima.',
        ]);

        $existingNotes = $this->delivery->notes ? rtrim($this->delivery->notes) : '';
        $updatedNotes = $existingNotes;
        if ($this->handover_notes) {
            $updatedNotes = $existingNotes ? "{$existingNotes}\n[Serah Terima]: {$this->handover_notes}" : "[Serah Terima]: {$this->handover_notes}";
        }

        $this->delivery->update([
            'status' => 'selesai',
            'delivered_at' => now(),
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'notes' => $updatedNotes,
        ]);

        $this->delivery->refresh();

        session()->flash('toast', [
            'message' => "Pengantaran selesai! Barang telah diterima oleh {$this->recipient_name}.",
            'type' => 'success',
        ]);
    }

    public function cancelDelivery(): void
    {
        if (Gate::denies('pengantaran-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membatalkan surat jalan.');
        }

        if ($this->delivery->status === 'selesai' || $this->delivery->status === 'dibatalkan') {
            return;
        }

        DB::transaction(function () {
            // Restore ready stock
            foreach ($this->delivery->items as $item) {
                Product::where('id', $item->product_id)->increment('stock_ready', $item->quantity);
            }

            $this->delivery->update(['status' => 'dibatalkan']);
        });

        $this->delivery->refresh();

        session()->flash('toast', [
            'message' => "Surat jalan dibatalkan dan stok produk dikembalikan ke gudang.",
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
