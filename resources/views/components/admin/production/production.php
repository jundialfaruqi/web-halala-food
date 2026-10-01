<?php

namespace App\Livewire\Admin;

use App\Models\FinishedStockMutation;
use App\Models\ProductVariant;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\StockMutation;
use App\Models\User;
use App\Models\WasteLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin'), Title('Dapur & Produksi Manufaktur - Halala Food')] class extends Component
{
    /**
     * Create or Update a Production Batch Plan
     */
    public function saveBatch(?int $id, int $recipeId, ?int $userId, int $plannedQty, ?string $notes = null): array
    {
        $notes = $notes ? trim($notes) : null;

        $rules = [
            'recipeId' => ['required', 'exists:recipes,id'],
            'plannedQty' => ['required', 'integer', 'min:1'],
            'userId' => ['nullable', 'exists:users,id'],
        ];

        $validator = validator(
            [
                'recipeId' => $recipeId,
                'plannedQty' => $plannedQty,
                'userId' => $userId,
            ],
            $rules,
            [
                'recipeId.required' => 'Pilih formula resep produksi.',
                'plannedQty.required' => 'Target rencana jumlah output (pack) wajib diisi.',
                'plannedQty.min' => 'Target rencana minimal 1 pack.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ];
        }

        try {
            DB::transaction(function () use ($id, $recipeId, $userId, $plannedQty, $notes) {
                $recipe = Recipe::with('items.rawMaterial')->findOrFail($recipeId);

                // Calculate multiplier based on standard recipe batch output
                $baseBatchOutput = max(1, $recipe->batch_output_qty);
                $multiplier = $plannedQty / $baseBatchOutput;

                $totalEstimatedMaterialCost = 0;

                if ($id) {
                    $batch = ProductionBatch::findOrFail($id);
                    if ($batch->status !== 'draft') {
                        throw new \Exception('Hanya rencana produksi berstatus Draft yang dapat diedit.');
                    }
                    $batch->update([
                        'recipe_id' => $recipeId,
                        'user_id' => $userId ?: Auth::id(),
                        'planned_qty' => $plannedQty,
                        'notes' => $notes,
                    ]);
                } else {
                    $batch = ProductionBatch::create([
                        'batch_number' => ProductionBatch::generateBatchNumber(),
                        'recipe_id' => $recipeId,
                        'user_id' => $userId ?: Auth::id(),
                        'planned_qty' => $plannedQty,
                        'actual_qty_good' => 0,
                        'actual_qty_bad' => 0,
                        'total_material_cost' => 0,
                        'unit_cost_produced' => $recipe->calculateEstimatedHpp(),
                        'status' => 'draft',
                        'notes' => $notes,
                    ]);
                }

                // Populate planned materials
                $batch->materials()->delete();
                foreach ($recipe->items as $item) {
                    $plannedItemQty = (float) $item->quantity_required * $multiplier;
                    $costPerUnit = (float) ($item->rawMaterial?->average_cost ?? 0);
                    $subtotal = $costPerUnit * $plannedItemQty;
                    $totalEstimatedMaterialCost += $subtotal;

                    ProductionBatchMaterial::create([
                        'production_batch_id' => $batch->id,
                        'raw_material_id' => $item->raw_material_id,
                        'planned_qty' => $plannedItemQty,
                        'actual_used_qty' => $plannedItemQty,
                        'cost_per_unit' => $costPerUnit,
                        'subtotal_cost' => $subtotal,
                    ]);
                }

                $batch->update([
                    'total_material_cost' => $totalEstimatedMaterialCost,
                ]);
            });

            $msg = $id ? "Rencana produksi berhasil diperbarui." : "Rencana batch produksi baru berhasil dibuat.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menyimpan rencana produksi: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Start Batch Production (Deduct Raw Materials from Stock)
     */
    public function startBatch(int $id): array
    {
        try {
            DB::transaction(function () use ($id) {
                $batch = ProductionBatch::with(['materials.rawMaterial', 'recipe'])->findOrFail($id);

                if ($batch->status !== 'draft') {
                    throw new \Exception('Batch ini sudah dimulai atau telah selesai.');
                }

                // Check raw material stock availability
                foreach ($batch->materials as $matItem) {
                    $material = $matItem->rawMaterial;
                    if (!$material) continue;

                    if ((float) $material->stock_qty < (float) $matItem->planned_qty) {
                        throw new \Exception("Stok {$material->name} di gudang tidak mencukupi. Tersedia: {$material->stock_qty} {$material->unit}, Dibutuhkan: {$matItem->planned_qty} {$material->unit}.");
                    }
                }

                // Deduct stock and log mutations
                foreach ($batch->materials as $matItem) {
                    $material = $matItem->rawMaterial;
                    if (!$material) continue;

                    $newStock = (float) $material->stock_qty - (float) $matItem->planned_qty;
                    $material->update(['stock_qty' => $newStock]);

                    StockMutation::create([
                        'raw_material_id' => $material->id,
                        'reference_type' => 'production_usage',
                        'reference_id' => $batch->id,
                        'type' => 'out',
                        'quantity' => $matItem->planned_qty,
                        'current_stock' => $newStock,
                        'cost_per_unit' => $matItem->cost_per_unit,
                        'notes' => "Penggunaan bahan untuk batch {$batch->batch_number}",
                        'user_id' => Auth::id(),
                    ]);
                }

                $batch->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);
            });

            $msg = "Batch produksi berhasil dimulai. Bahan baku otomatis dipotong dari gudang.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Finish Batch Production (Add Finished Goods to Variant Stock & Calculate HPP)
     */
    public function finishBatch(int $id, int $actualQtyGood, int $actualQtyBad = 0, ?string $notes = null): array
    {
        if ($actualQtyGood <= 0) {
            $msg = 'Jumlah produk jadi yang bagus harus lebih dari 0.';
            $this->dispatch('show-toast', message: $msg, type: 'error');

            return [
                'success' => false,
                'message' => $msg,
            ];
        }

        try {
            DB::transaction(function () use ($id, $actualQtyGood, $actualQtyBad, $notes) {
                $batch = ProductionBatch::with(['recipe.productVariant', 'materials'])->findOrFail($id);

                if ($batch->status !== 'in_progress') {
                    throw new \Exception('Hanya batch yang sedang diproses (In Progress) yang dapat diselesaikan.');
                }

                $recipe = $batch->recipe;
                $variant = $recipe?->productVariant;

                if (!$variant) {
                    throw new \Exception('Varian produk target tidak ditemukan.');
                }

                // Calculate actual HPP per unit
                $totalMaterialCost = (float) $batch->total_material_cost;
                $laborCost = (float) ($recipe->labor_cost ?? 0);
                $overheadCost = (float) ($recipe->overhead_cost ?? 0);
                $grandTotalCost = $totalMaterialCost + $laborCost + $overheadCost;
                $unitCostProduced = $grandTotalCost / $actualQtyGood;

                // Add to Finished Goods Stock
                $newStock = $variant->stock_qty + $actualQtyGood;
                $variant->update([
                    'stock_qty' => $newStock,
                    'base_cost' => $unitCostProduced, // Update estimated base cost with actual produced cost
                ]);

                // Record Finished Goods Mutation
                FinishedStockMutation::create([
                    'product_variant_id' => $variant->id,
                    'reference_type' => 'production_in',
                    'reference_id' => $batch->id,
                    'type' => 'in',
                    'quantity' => $actualQtyGood,
                    'current_stock' => $newStock,
                    'notes' => "Hasil produksi batch {$batch->batch_number} (Bagus: {$actualQtyGood}, Cacat: {$actualQtyBad})",
                    'user_id' => Auth::id(),
                ]);

                $batch->update([
                    'actual_qty_good' => $actualQtyGood,
                    'actual_qty_bad' => $actualQtyBad,
                    'unit_cost_produced' => $unitCostProduced,
                    'status' => 'completed',
                    'completed_at' => now(),
                    'notes' => $notes ?: $batch->notes,
                ]);
            });

            $msg = "Batch produksi selesai! {$actualQtyGood} pack barang jadi telah ditambahkan ke stok siap jual.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menyelesaikan batch: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Cancel Batch Production (Rolls back materials if in progress)
     */
    public function cancelBatch(int $id): array
    {
        try {
            DB::transaction(function () use ($id) {
                $batch = ProductionBatch::with(['materials.rawMaterial'])->findOrFail($id);

                if ($batch->status === 'completed') {
                    throw new \Exception('Batch yang sudah selesai tidak dapat dibatalkan.');
                }

                // If in progress, rollback raw materials stock
                if ($batch->status === 'in_progress') {
                    foreach ($batch->materials as $matItem) {
                        $material = $matItem->rawMaterial;
                        if (!$material) continue;

                        $newStock = (float) $material->stock_qty + (float) $matItem->planned_qty;
                        $material->update(['stock_qty' => $newStock]);

                        StockMutation::create([
                            'raw_material_id' => $material->id,
                            'reference_type' => 'adjustment',
                            'reference_id' => $batch->id,
                            'type' => 'in',
                            'quantity' => $matItem->planned_qty,
                            'current_stock' => $newStock,
                            'cost_per_unit' => $matItem->cost_per_unit,
                            'notes' => "Pengembalian bahan akibat pembatalan batch {$batch->batch_number}",
                            'user_id' => Auth::id(),
                        ]);
                    }
                }

                $batch->update(['status' => 'cancelled']);
            });

            $msg = "Batch produksi berhasil dibatalkan.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal membatalkan batch: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Record Raw Material Waste & Spoilage
     */
    public function saveWasteLog(int $rawMaterialId, float $quantity, string $reason, ?string $notes = null): array
    {
        if ($quantity <= 0) {
            $msg = 'Jumlah bahan rusak harus lebih dari 0.';
            $this->dispatch('show-toast', message: $msg, type: 'error');

            return [
                'success' => false,
                'message' => $msg,
            ];
        }

        try {
            DB::transaction(function () use ($rawMaterialId, $quantity, $reason, $notes) {
                $material = RawMaterial::findOrFail($rawMaterialId);

                $costLoss = (float) $material->average_cost * $quantity;
                $newStock = max(0, (float) $material->stock_qty - $quantity);
                $material->update(['stock_qty' => $newStock]);

                $wasteLog = WasteLog::create([
                    'code' => WasteLog::generateWasteCode(),
                    'raw_material_id' => $material->id,
                    'quantity' => $quantity,
                    'cost_loss' => $costLoss,
                    'reason' => $reason,
                    'notes' => $notes ? trim($notes) : null,
                    'reported_by' => Auth::id(),
                    'approved_by' => Auth::id(),
                ]);

                StockMutation::create([
                    'raw_material_id' => $material->id,
                    'reference_type' => 'waste_spoilage',
                    'reference_id' => $wasteLog->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'current_stock' => $newStock,
                    'cost_per_unit' => $material->average_cost,
                    'notes' => "Laporan bahan rusak/waste {$wasteLog->code} ({$reason})",
                    'user_id' => Auth::id(),
                ]);
            });

            $msg = "Laporan bahan rusak/waste berhasil dicatat dan stok gudang telah diperbarui.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal mencatat bahan rusak: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Stock Opname Adjustment for Raw Materials
     */
    public function adjustRawStock(int $rawMaterialId, float $actualStock, string $reason): array
    {
        try {
            DB::transaction(function () use ($rawMaterialId, $actualStock, $reason) {
                $material = RawMaterial::findOrFail($rawMaterialId);
                $diff = $actualStock - (float) $material->stock_qty;

                if ($diff === 0.0) return;

                $type = $diff > 0 ? 'in' : 'out';
                $material->update(['stock_qty' => $actualStock]);

                StockMutation::create([
                    'raw_material_id' => $material->id,
                    'reference_type' => 'adjustment',
                    'reference_id' => null,
                    'type' => $type,
                    'quantity' => abs($diff),
                    'current_stock' => $actualStock,
                    'cost_per_unit' => $material->average_cost,
                    'notes' => "Penyesuaian stok opname: " . trim($reason),
                    'user_id' => Auth::id(),
                ]);
            });

            $msg = "Stok bahan baku berhasil disesuaikan.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menyesuaikan stok: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Stock Opname Adjustment for Finished Goods
     */
    public function adjustFinishedStock(int $productVariantId, int $actualStock, string $reason): array
    {
        try {
            DB::transaction(function () use ($productVariantId, $actualStock, $reason) {
                $variant = ProductVariant::findOrFail($productVariantId);
                $diff = $actualStock - (int) $variant->stock_qty;

                if ($diff === 0) return;

                $type = $diff > 0 ? 'in' : 'out';
                $variant->update(['stock_qty' => $actualStock]);

                FinishedStockMutation::create([
                    'product_variant_id' => $variant->id,
                    'reference_type' => 'adjustment',
                    'reference_id' => null,
                    'type' => $type,
                    'quantity' => abs($diff),
                    'current_stock' => $actualStock,
                    'notes' => "Penyesuaian stok opname barang jadi: " . trim($reason),
                    'user_id' => Auth::id(),
                ]);
            });

            $msg = "Stok barang jadi berhasil disesuaikan.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menyesuaikan stok: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete a Batch in Draft status
     */
    public function deleteBatch(int $id): array
    {
        try {
            $batch = ProductionBatch::findOrFail($id);
            if ($batch->status !== 'draft') {
                throw new \Exception('Hanya batch berstatus Draft yang dapat dihapus.');
            }

            $batchNumber = $batch->batch_number;
            $batch->materials()->delete();
            $batch->delete();

            $msg = "Rencana batch {$batchNumber} berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menghapus batch: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Provide dataset for the view
     */
    public function with(): array
    {
        $batches = ProductionBatch::with([
            'recipe.productVariant.product',
            'user',
            'materials.rawMaterial',
        ])
            ->orderBy('id', 'desc')
            ->get();

        $rawStockMutations = StockMutation::with(['rawMaterial', 'user'])
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        $finishedStockMutations = FinishedStockMutation::with(['productVariant.product', 'user'])
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        $wasteLogs = WasteLog::with(['rawMaterial', 'reporter', 'approver'])
            ->orderBy('id', 'desc')
            ->get();

        $recipes = Recipe::with('productVariant.product')
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $rawMaterials = RawMaterial::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $productVariants = ProductVariant::with('product')
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $cooks = User::whereHas('roles', fn($q) => $q->whereIn('name', ['tukang masak', 'manager', 'dev']))
            ->orderBy('name', 'asc')
            ->get();

        // Statistics
        $inProgressBatchesCount = $batches->where('status', 'in_progress')->count();
        $completedTodayCount = $batches->where('status', 'completed')
            ->filter(fn($b) => $b->completed_at && $b->completed_at->isToday())
            ->count();
        $totalWasteCost = (float) $wasteLogs->sum('cost_loss');

        return [
            'batches' => $batches,
            'rawStockMutations' => $rawStockMutations,
            'finishedStockMutations' => $finishedStockMutations,
            'wasteLogs' => $wasteLogs,
            'recipes' => $recipes,
            'rawMaterials' => $rawMaterials,
            'productVariants' => $productVariants,
            'cooks' => $cooks,
            'totalBatches' => $batches->count(),
            'inProgressBatchesCount' => $inProgressBatchesCount,
            'completedTodayCount' => $completedTodayCount,
            'totalWasteCost' => $totalWasteCost,
        ];
    }
};
