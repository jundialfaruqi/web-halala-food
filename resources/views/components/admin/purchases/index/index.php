<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\StockMutation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] #[Title('Pengadaan & Pembelian Bahan Baku - Halala Food')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $paymentMethod = '';

    public ?int $viewingPurchaseId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function viewDetails(int $id): void
    {
        $this->viewingPurchaseId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingPurchaseId = null;
    }

    public function deletePurchase(int $id): void
    {
        if (Gate::denies('pembelian-delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membatalkan pembelian bahan baku.');
        }

        $purchase = RawMaterialPurchase::with('items.rawMaterial')->findOrFail($id);

        // Check if rollback will cause negative stock
        foreach ($purchase->items as $item) {
            $currentStock = (float) $item->rawMaterial->stock;
            if ($currentStock < (float) $item->quantity) {
                session()->flash('error', "Pembelian {$purchase->purchase_number} tidak dapat dihapus karena sisa stok '{$item->rawMaterial->name}' ({$currentStock}) lebih kecil dari jumlah yang dibeli ({$item->quantity}). Sebagian bahan telah terpakai dalam proses masak dapur.");
                return;
            }
        }

        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $rawMat = $item->rawMaterial;
                $stockBefore = (float) $rawMat->stock;
                $stockAfter = $stockBefore - (float) $item->quantity;

                // Create reverse mutation
                StockMutation::create([
                    'raw_material_id' => $rawMat->id,
                    'reference_type' => 'purchase_reversal',
                    'reference_id' => $purchase->id,
                    'reference_number' => $purchase->purchase_number,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'cost_per_unit' => $item->cost_per_unit,
                    'notes' => "Pembatalan pembelian {$purchase->purchase_number}",
                    'user_id' => auth()->id(),
                ]);

                $rawMat->decrement('stock', $item->quantity);
            }

            // Remove associated original stock mutations
            StockMutation::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->delete();

            // Remove associated journal entries
            \App\Models\JournalEntry::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->delete();

            $purchase->items()->delete();
            $purchase->delete();
        });

        if ($this->viewingPurchaseId === $id) {
            $this->viewingPurchaseId = null;
        }

        session()->flash('success', "Transaksi pembelian {$purchase->purchase_number} berhasil dibatalkan dan stok bahan dikembalikan.");
    }

    public function with(): array
    {
        $query = RawMaterialPurchase::query()
            ->with(['items.rawMaterial', 'creator'])
            ->latest('purchase_date')
            ->latest('id');

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('purchase_number', 'like', $term)
                    ->orWhere('supplier_name', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('items.rawMaterial', function ($mq) use ($term) {
                        $mq->where('name', 'like', $term);
                    });
            });
        }

        if ($this->paymentMethod !== '') {
            $query->where('payment_method', $this->paymentMethod);
        }

        $purchases = $query->paginate(15);

        // Stats summary for current month
        $thisMonth = now()->startOfMonth();
        $monthlyTotalAmount = (float) RawMaterialPurchase::where('purchase_date', '>=', $thisMonth)->sum('total_amount');
        $monthlyTotalCount = RawMaterialPurchase::where('purchase_date', '>=', $thisMonth)->count();

        $activePurchase = null;
        if ($this->viewingPurchaseId) {
            $activePurchase = RawMaterialPurchase::with(['items.rawMaterial.unitModel', 'creator'])->find($this->viewingPurchaseId);
        }

        return [
            'purchases' => $purchases,
            'monthlyTotalAmount' => $monthlyTotalAmount,
            'monthlyTotalCount' => $monthlyTotalCount,
            'activePurchase' => $activePurchase,
        ];
    }
};
