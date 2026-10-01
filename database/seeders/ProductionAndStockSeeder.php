<?php

namespace Database\Seeders;

use App\Models\FinishedStockMutation;
use App\Models\ProductVariant;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\StockMutation;
use App\Models\User;
use App\Models\WasteLog;
use Illuminate\Database\Seeder;

class ProductionAndStockSeeder extends Seeder
{
    public function run(): void
    {
        $cook = User::where('email', 'masak@halala-food.id')->first() ?: User::first();
        $manager = User::where('email', 'manager@halala-food.id')->first() ?: User::first();

        // 1. Seed Initial Stock Mutations for Raw Materials
        $rawMaterials = RawMaterial::all();
        foreach ($rawMaterials as $mat) {
            StockMutation::updateOrCreate(
                [
                    'raw_material_id' => $mat->id,
                    'reference_type' => 'initial_stock',
                ],
                [
                    'reference_id' => null,
                    'type' => 'in',
                    'quantity' => $mat->stock_qty,
                    'current_stock' => $mat->stock_qty,
                    'cost_per_unit' => $mat->average_cost,
                    'notes' => 'Saldo awal stok bahan baku gudang',
                    'user_id' => $manager?->id,
                ]
            );
        }

        // 2. Seed Initial Stock Mutations for Finished Goods
        $variants = ProductVariant::all();
        foreach ($variants as $var) {
            FinishedStockMutation::updateOrCreate(
                [
                    'product_variant_id' => $var->id,
                    'reference_type' => 'initial_stock',
                ],
                [
                    'reference_id' => null,
                    'type' => 'in',
                    'quantity' => $var->stock_qty,
                    'current_stock' => $var->stock_qty,
                    'notes' => 'Saldo awal stok barang jadi di rak display',
                    'user_id' => $manager?->id,
                ]
            );
        }

        // 3. Seed Production Batch 1: Completed Batch (Ting-Ting Susu Pouch 200g)
        $recipePouch = Recipe::whereHas('productVariant', fn($q) => $q->where('sku_code', 'TTS-PCH-200G'))->first();
        if ($recipePouch) {
            $batch1 = ProductionBatch::updateOrCreate(
                ['batch_number' => 'PRD-20261001-0001'],
                [
                    'recipe_id' => $recipePouch->id,
                    'user_id' => $cook?->id,
                    'planned_qty' => 50,
                    'actual_qty_good' => 50,
                    'actual_qty_bad' => 0,
                    'total_material_cost' => $recipePouch->calculateTotalMaterialCost(),
                    'unit_cost_produced' => $recipePouch->calculateEstimatedHpp(),
                    'status' => 'completed',
                    'notes' => 'Batch pagi Ting-Ting Susu Pouch, hasil kemasan rapat dan renyah sempurna.',
                    'started_at' => now()->subHours(4),
                    'completed_at' => now()->subHours(2),
                ]
            );

            // Record allocated materials
            $batch1->materials()->delete();
            foreach ($recipePouch->items as $item) {
                ProductionBatchMaterial::create([
                    'production_batch_id' => $batch1->id,
                    'raw_material_id' => $item->raw_material_id,
                    'planned_qty' => $item->quantity_required,
                    'actual_used_qty' => $item->quantity_required,
                    'cost_per_unit' => $item->rawMaterial?->average_cost ?? 0,
                    'subtotal_cost' => ($item->rawMaterial?->average_cost ?? 0) * (float) $item->quantity_required,
                ]);
            }
        }

        // 4. Seed Production Batch 2: In-Progress Batch (Ting-Ting Susu Toples)
        $recipeToples = Recipe::whereHas('productVariant', fn($q) => $q->where('sku_code', 'TTS-TPL-20PCS'))->first();
        if ($recipeToples) {
            $batch2 = ProductionBatch::updateOrCreate(
                ['batch_number' => 'PRD-20261001-0002'],
                [
                    'recipe_id' => $recipeToples->id,
                    'user_id' => $cook?->id,
                    'planned_qty' => 25,
                    'actual_qty_good' => 0,
                    'actual_qty_bad' => 0,
                    'total_material_cost' => $recipeToples->calculateTotalMaterialCost(),
                    'unit_cost_produced' => $recipeToples->calculateEstimatedHpp(),
                    'status' => 'in_progress',
                    'notes' => 'Batch siang Ting-Ting Susu Toples sedang proses pemasakan adonan.',
                    'started_at' => now()->subMinutes(45),
                    'completed_at' => null,
                ]
            );

            $batch2->materials()->delete();
            foreach ($recipeToples->items as $item) {
                ProductionBatchMaterial::create([
                    'production_batch_id' => $batch2->id,
                    'raw_material_id' => $item->raw_material_id,
                    'planned_qty' => $item->quantity_required,
                    'actual_used_qty' => $item->quantity_required,
                    'cost_per_unit' => $item->rawMaterial?->average_cost ?? 0,
                    'subtotal_cost' => ($item->rawMaterial?->average_cost ?? 0) * (float) $item->quantity_required,
                ]);
            }
        }

        // 5. Seed Sample Waste Log (Bahan Rusak/Tumpah)
        $tepung = RawMaterial::where('code', 'BB-TPG-01')->first();
        if ($tepung) {
            WasteLog::updateOrCreate(
                ['code' => 'WST-20261001-0001'],
                [
                    'raw_material_id' => $tepung->id,
                    'quantity' => 500, // 500 gram
                    'cost_loss' => 500 * (float) $tepung->average_cost, // 500 * 14 = Rp 7.000
                    'reason' => 'spilled',
                    'notes' => 'Kemasan tepung sobek dan tumpah di meja racik saat penimbangan.',
                    'reported_by' => $cook?->id,
                    'approved_by' => $manager?->id,
                ]
            );
        }
    }
}
