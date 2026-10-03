<?php

use App\Models\Delivery;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public Delivery $delivery;

    // Handover Confirmation Form Fields
    public string $recipient_name = '';
    public ?string $recipient_role = '';
    public ?string $recipient_phone = '';
    public ?string $handover_notes = '';
    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public mixed $proof_photo = null;
    public ?string $signature_data = '';

    public function mount(Delivery $delivery)
    {
        if (Gate::denies('pengantaran-view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat data pengantaran.');
        }

        $this->delivery = $delivery->load(['store', 'courier', 'creator', 'items.product.unitModel']);

        $this->recipient_name = $delivery->recipient_name ?? $delivery->store?->owner_name ?? '';
        $this->recipient_role = $delivery->recipient_role ?? '';
        $this->recipient_phone = $delivery->recipient_phone ?? $delivery->store?->phone ?? '';
        $this->signature_data = $delivery->signature_data ?? '';
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
            'recipient_role' => ['nullable', 'string', 'max:100'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'handover_notes' => ['nullable', 'string', 'max:500'],
            'proof_photo' => ['nullable', 'image', 'max:5120'],
            'signature_data' => ['nullable', 'string'],
        ], [
            'recipient_name.required' => 'Nama staf/pemilik toko penerima wajib diisi sebagai bukti serah terima.',
            'proof_photo.image' => 'File bukti serah terima harus berupa format foto/gambar.',
            'proof_photo.max' => 'Ukuran foto bukti tidak boleh melebihi 5MB.',
        ]);

        $proofPath = $this->delivery->proof_image;
        if ($this->proof_photo) {
            $proofPath = $this->proof_photo->store('delivery-proofs', 'public');
        }

        $existingNotes = $this->delivery->notes ? rtrim($this->delivery->notes) : '';
        $updatedNotes = $existingNotes;
        if ($this->handover_notes) {
            $updatedNotes = $existingNotes ? "{$existingNotes}\n[Serah Terima]: {$this->handover_notes}" : "[Serah Terima]: {$this->handover_notes}";
        }

        $this->delivery->update([
            'status' => 'selesai',
            'delivered_at' => now(),
            'recipient_name' => $this->recipient_name,
            'recipient_role' => $this->recipient_role ?: null,
            'recipient_phone' => $this->recipient_phone ?: null,
            'proof_image' => $proofPath,
            'signature_data' => $this->signature_data ?: $this->delivery->signature_data,
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
