<?php

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\RawMaterial;
use App\Models\StockMutation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Produksi & Manufaktur - Halala Food')] class extends Component
{
    /**
     * Execute a new cooking batch with atomic material deduction & finished goods increment.
     *
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string}
     */
    public function executeBatch(array $data): array
    {
        if (Gate::denies('produksi-create')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk mengeksekusi produksi.'];
        }

        $validator = Validator::make($data, [
            'product_id' => ['required', 'exists:products,id'],
            'planned_qty' => ['required', 'integer', 'min:1'],
            'actual_qty_good' => ['required', 'integer', 'min:0'],
            'actual_qty_bad' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'product_id.required' => 'Pilih produk yang akan diproduksi.',
            'product_id.exists' => 'Produk tidak valid.',
            'planned_qty.required' => 'Target jumlah produksi wajib diisi.',
            'planned_qty.min' => 'Target produksi minimal 1 unit.',
            'actual_qty_good.required' => 'Jumlah hasil bagus (lolos QC) wajib diisi.',
            'actual_qty_good.min' => 'Jumlah hasil bagus minimal 0.',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        $productId = (int) $data['product_id'];
        $plannedQty = (int) $data['planned_qty'];
        $actualGood = (int) $data['actual_qty_good'];
        $actualBad = (int) ($data['actual_qty_bad'] ?? 0);
        $notes = ! empty($data['notes']) ? trim((string) $data['notes']) : null;

        $product = Product::with(['recipes.rawMaterial.unitModel', 'unitModel'])->find($productId);

        if (! $product) {
            return ['success' => false, 'message' => 'Produk tidak ditemukan.'];
        }

        if ($product->recipes->isEmpty()) {
            return [
                'success' => false,
                'message' => "Produk '{$product->name}' belum memiliki formula resep (BOM). Silakan buat resep terlebih dahulu di menu Bahan Baku & Resep.",
            ];
        }

        // Check material availability
        $shortages = [];
        $materialRequirements = [];

        foreach ($product->recipes as $recipe) {
            $rawMat = $recipe->rawMaterial;
            if (! $rawMat) {
                continue;
            }

            $neededQty = (float) $recipe->quantity_needed * $plannedQty;
            $unitName = $rawMat->display_unit;

            if ((float) $rawMat->stock < $neededQty) {
                $deficit = $neededQty - (float) $rawMat->stock;
                $shortages[] = "{$rawMat->name} (Kurang " . number_format($deficit, 2, ',', '.') . " {$unitName})";
            }

            $materialRequirements[] = [
                'material' => $rawMat,
                'needed_qty' => $neededQty,
                'unit_name' => $unitName,
                'cost_per_unit' => (float) $rawMat->cost_per_unit,
                'subtotal_cost' => $neededQty * (float) $rawMat->cost_per_unit,
            ];
        }

        if (! empty($shortages)) {
            return [
                'success' => false,
                'message' => 'Stok bahan baku tidak mencukupi untuk memproduksi ' . number_format($plannedQty, 0, ',', '.') . " unit: \n• " . implode("\n• ", $shortages),
            ];
        }

        // Execute batch inside database transaction
        return DB::transaction(function () use ($product, $plannedQty, $actualGood, $actualBad, $notes, $materialRequirements) {
            $batchCode = ProductionBatch::generateBatchCode();
            $totalCost = array_sum(array_column($materialRequirements, 'subtotal_cost'));
            $unitCostProduced = $actualGood > 0 ? ($totalCost / $actualGood) : 0;

            $batch = ProductionBatch::create([
                'batch_code' => $batchCode,
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'planned_qty' => $plannedQty,
                'actual_qty_good' => $actualGood,
                'actual_qty_bad' => $actualBad,
                'total_material_cost' => $totalCost,
                'unit_cost_produced' => $unitCostProduced,
                'status' => 'completed',
                'notes' => $notes,
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            // Deduct each raw material & record stock mutation
            foreach ($materialRequirements as $item) {
                /** @var RawMaterial $mat */
                $mat = $item['material'];
                $usedQty = $item['needed_qty'];
                $stockBefore = (float) $mat->stock;
                $stockAfter = $stockBefore - $usedQty;

                // Decrement material stock
                $mat->decrement('stock', $usedQty);

                // Record snapshot in production_batch_materials
                ProductionBatchMaterial::create([
                    'production_batch_id' => $batch->id,
                    'raw_material_id' => $mat->id,
                    'unit_name' => $item['unit_name'],
                    'planned_qty' => $usedQty,
                    'actual_used_qty' => $usedQty,
                    'cost_per_unit' => $item['cost_per_unit'],
                    'subtotal_cost' => $item['subtotal_cost'],
                ]);

                // Record stock card audit mutation
                StockMutation::create([
                    'raw_material_id' => $mat->id,
                    'reference_type' => 'production',
                    'reference_id' => $batch->id,
                    'reference_number' => $batchCode,
                    'type' => 'out',
                    'quantity' => $usedQty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'cost_per_unit' => $item['cost_per_unit'],
                    'notes' => "Pemakaian bahan batch {$batchCode} ({$product->name} x {$plannedQty})",
                    'user_id' => Auth::id(),
                ]);
            }

            // Increment finished goods ready stock
            $product->increment('stock_ready', $actualGood);

            // Catat jurnal pemakaian bahan baku & penambahan produk jadi
            \App\Services\AccountingService::recordProduction($batch);

            return [
                'success' => true,
                'message' => "Batch masak {$batchCode} berhasil diselesaikan! Stok bahan baku terpotong dan {$actualGood} {$product->unit} barang jadi siap jual bertambah ke gudang.",
            ];
        });
    }

    /**
     * Cancel an existing batch, safely restoring raw materials and adjusting finished goods stock.
     *
     * @param int $batchId
     * @return array{success: bool, message: string}
     */
    public function cancelBatch(int $batchId): array
    {
        if (Gate::denies('produksi-delete')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk membatalkan batch produksi.'];
        }

        $batch = ProductionBatch::with(['batchMaterials.rawMaterial', 'product'])->find($batchId);

        if (! $batch) {
            return ['success' => false, 'message' => 'Batch produksi tidak ditemukan.'];
        }

        if ($batch->status === 'cancelled') {
            return ['success' => false, 'message' => 'Batch produksi ini sudah dibatalkan sebelumnya.'];
        }

        // Check if finished stock is sufficient to deduct
        if ($batch->product && $batch->product->stock_ready < $batch->actual_qty_good) {
            return [
                'success' => false,
                'message' => "Tidak dapat membatalkan batch. Stok barang jadi di gudang saat ini ({$batch->product->stock_ready}) lebih kecil dari hasil batch ({$batch->actual_qty_good}), sebagian produk kemungkinan telah terjual atau dikirim.",
            ];
        }

        return DB::transaction(function () use ($batch) {
            // Restore raw materials
            foreach ($batch->batchMaterials as $bm) {
                $mat = $bm->rawMaterial;
                if ($mat) {
                    $stockBefore = (float) $mat->stock;
                    $restoreQty = (float) $bm->actual_used_qty;
                    $stockAfter = $stockBefore + $restoreQty;

                    $mat->increment('stock', $restoreQty);

                    StockMutation::create([
                        'raw_material_id' => $mat->id,
                        'reference_type' => 'adjustment',
                        'reference_id' => $batch->id,
                        'reference_number' => $batch->batch_code,
                        'type' => 'in',
                        'quantity' => $restoreQty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'cost_per_unit' => (float) $bm->cost_per_unit,
                        'notes' => "Pengembalian bahan akibat pembatalan batch {$batch->batch_code}",
                        'user_id' => Auth::id(),
                    ]);
                }
            }

            // Deduct finished goods stock
            if ($batch->product && $batch->actual_qty_good > 0) {
                $batch->product->decrement('stock_ready', $batch->actual_qty_good);
            }

            // Batalkan catatan jurnal akuntansi batch produksi
            \App\Models\JournalEntry::where('reference_type', 'production')
                ->where('reference_id', $batch->id)
                ->delete();

            $batch->update(['status' => 'cancelled']);

            return [
                'success' => true,
                'message' => "Batch {$batch->batch_code} berhasil dibatalkan. Seluruh bahan baku dan stok produk telah dikembalikan secara proporsional.",
            ];
        });
    }

    /**
     * Render data for the blade template.
     */
    public function with(): array
    {
        $products = Product::with(['recipes.rawMaterial.unitModel', 'unitModel'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $batches = ProductionBatch::with(['product.unitModel', 'user', 'batchMaterials.rawMaterial.unitModel'])
            ->orderByDesc('id')
            ->get();

        $mutations = StockMutation::with(['rawMaterial.unitModel', 'user'])
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $rawMaterials = RawMaterial::with('unitModel')->orderBy('name')->get();

        // Calculate summary KPI stats
        $totalBatchesCount = $batches->count();
        $totalGoodProduced = $batches->where('status', 'completed')->sum('actual_qty_good');
        $totalBadProduced = $batches->where('status', 'completed')->sum('actual_qty_bad');
        $totalProductionCost = $batches->where('status', 'completed')->sum('total_material_cost');

        return [
            'products' => $products,
            'batches' => $batches,
            'mutations' => $mutations,
            'rawMaterials' => $rawMaterials,
            'stats' => [
                'total_batches' => $totalBatchesCount,
                'total_good' => $totalGoodProduced,
                'total_bad' => $totalBadProduced,
                'total_cost' => $totalProductionCost,
                'success_rate' => ($totalGoodProduced + $totalBadProduced) > 0
                    ? round(($totalGoodProduced / ($totalGoodProduced + $totalBadProduced)) * 100, 1)
                    : 100,
            ],
        ];
    }
};
