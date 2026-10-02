<?php

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Models\Unit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Master Bahan Baku & Resep (BOM) - Halala Food')] class extends Component {
    public function mount()
    {
        if (Gate::denies('bahan-baku-view')) {
            abort(403, 'Anda tidak memiliki izin untuk melihat data bahan baku dan resep.');
        }
    }

    public function render()
    {
        $materials = RawMaterial::with('unitModel')
            ->orderBy('name')
            ->get()
            ->map(function ($mat) {
                return [
                    'id' => $mat->id,
                    'name' => $mat->name,
                    'unit_id' => $mat->unit_id,
                    'unit_name' => $mat->unitModel?->name ?? $mat->unit,
                    'unit_short' => $mat->display_unit,
                    'stock' => (float) $mat->stock,
                    'min_stock' => (float) $mat->min_stock,
                    'cost_per_unit' => (float) $mat->cost_per_unit,
                    'cost_formatted' => 'Rp ' . number_format($mat->cost_per_unit, 2, ',', '.'),
                    'stock_status' => $mat->stock_status,
                    'recipes_count' => $mat->recipes()->count(),
                ];
            });

        $products = Product::with(['unitModel', 'recipes.rawMaterial.unitModel'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($prod) {
                $materialCost = $prod->material_cost;
                $consignmentPrice = (float) $prod->consignment_price;
                $grossMargin = $prod->consignment_margin;

                $recipeItems = $prod->recipes->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'raw_material_id' => $r->raw_material_id,
                        'material_name' => $r->rawMaterial?->name ?? 'Bahan Dihapus',
                        'unit_short' => $r->rawMaterial?->display_unit ?? '-',
                        'cost_per_unit' => (float) ($r->rawMaterial?->cost_per_unit ?? 0),
                        'quantity_needed' => (float) $r->quantity_needed,
                        'subtotal_cost' => $r->subtotal_cost,
                        'subtotal_formatted' => 'Rp ' . number_format($r->subtotal_cost, 2, ',', '.'),
                    ];
                });

                return [
                    'id' => $prod->id,
                    'name' => $prod->name,
                    'unit_name' => $prod->unitModel?->name ?? $prod->unit,
                    'consignment_price' => $consignmentPrice,
                    'consignment_formatted' => 'Rp ' . number_format($consignmentPrice, 0, ',', '.'),
                    'retail_price' => (float) $prod->retail_price,
                    'retail_formatted' => 'Rp ' . number_format($prod->retail_price, 0, ',', '.'),
                    'material_cost' => $materialCost,
                    'material_cost_formatted' => 'Rp ' . number_format($materialCost, 2, ',', '.'),
                    'gross_margin' => $grossMargin,
                    'recipes' => $recipeItems,
                    'recipes_count' => $recipeItems->count(),
                ];
            });

        $units = Unit::where('is_active', true)->orderBy('name')->get(['id', 'name', 'short_name']);

        return view('components.admin.raw-materials.index.index', [
            'materials' => $materials,
            'products' => $products,
            'units' => $units,
        ]);
    }

    /**
     * Save (create or update) a raw material.
     */
    public function saveMaterial(?int $id, string $name, int $unitId, float $stock, float $minStock, float $costPerUnit): array
    {
        $perm = $id ? 'bahan-baku-edit' : 'bahan-baku-create';
        if (Gate::denies($perm)) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menyimpan bahan baku.'];
        }

        $validator = \Illuminate\Support\Facades\Validator::make([
            'name' => $name,
            'unit_id' => $unitId,
            'stock' => $stock,
            'min_stock' => $minStock,
            'cost_per_unit' => $costPerUnit,
        ], [
            'name' => ['required', 'string', 'max:100'],
            'unit_id' => ['required', 'exists:units,id'],
            'stock' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'cost_per_unit' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Nama bahan baku wajib diisi.',
            'unit_id.required' => 'Satuan pengukuran wajib dipilih.',
            'unit_id.exists' => 'Satuan yang dipilih tidak valid.',
            'stock.required' => 'Jumlah stok saat ini wajib diisi.',
            'stock.min' => 'Stok tidak boleh bernilai negatif.',
            'min_stock.required' => 'Batas minimum stok wajib diisi.',
            'min_stock.min' => 'Batas minimum tidak boleh negatif.',
            'cost_per_unit.required' => 'Harga beli per satuan wajib diisi.',
            'cost_per_unit.min' => 'Harga beli tidak boleh negatif.',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->toArray()];
        }

        $unit = Unit::find($unitId);

        $material = RawMaterial::updateOrCreate(
            ['id' => $id],
            [
                'name' => trim($name),
                'unit_id' => $unitId,
                'unit' => $unit?->short_name ?? 'gram',
                'stock' => $stock,
                'min_stock' => $minStock,
                'cost_per_unit' => $costPerUnit,
            ]
        );

        return [
            'success' => true,
            'message' => "Bahan baku '{$material->name}' berhasil disimpan.",
        ];
    }

    /**
     * Delete a raw material.
     */
    public function deleteMaterial(int $id): array
    {
        if (Gate::denies('bahan-baku-delete')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus bahan baku.'];
        }

        $material = RawMaterial::find($id);
        if (! $material) {
            return ['success' => false, 'message' => 'Data bahan baku tidak ditemukan.'];
        }

        $recipesCount = $material->recipes()->count();
        if ($recipesCount > 0) {
            return [
                'success' => false,
                'message' => "Bahan baku '{$material->name}' sedang digunakan pada {$recipesCount} resep produk. Hapus keterkaitan resep terlebih dahulu sebelum menghapus bahan ini.",
            ];
        }

        $name = $material->name;
        $material->delete();

        return [
            'success' => true,
            'message' => "Bahan baku '{$name}' berhasil dihapus.",
        ];
    }

    /**
     * Save product recipe items (Bill of Material).
     */
    public function saveRecipe(int $productId, array $ingredients): array
    {
        if (Gate::denies('resep-manage')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk mengatur formula resep.'];
        }

        $product = Product::find($productId);
        if (! $product) {
            return ['success' => false, 'message' => 'Produk tidak ditemukan.'];
        }

        // Validate ingredients
        $validRows = [];
        foreach ($ingredients as $item) {
            $matId = isset($item['raw_material_id']) ? (int) $item['raw_material_id'] : null;
            $qty = isset($item['quantity_needed']) ? (float) $item['quantity_needed'] : 0.0;

            if ($matId && $qty > 0) {
                $validRows[] = [
                    'raw_material_id' => $matId,
                    'quantity_needed' => $qty,
                ];
            }
        }

        if (empty($validRows)) {
            return [
                'success' => false,
                'message' => 'Resep harus memiliki minimal 1 bahan baku dengan takaran lebih dari 0.',
            ];
        }

        ProductRecipe::where('product_id', $productId)->delete();

        foreach ($validRows as $row) {
            ProductRecipe::create([
                'product_id' => $productId,
                'raw_material_id' => $row['raw_material_id'],
                'quantity_needed' => $row['quantity_needed'],
            ]);
        }

        return [
            'success' => true,
            'message' => "Formula resep untuk produk '{$product->name}' berhasil disimpan.",
        ];
    }
};
