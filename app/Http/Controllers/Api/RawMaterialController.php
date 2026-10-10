<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Models\StockMutation;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RawMaterialController extends Controller
{
    /**
     * Check user permission with support for roles (dev, manager) and Spatie permissions.
     */
    private function checkPermission(string $permission): bool
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('dev') || $user->hasRole('manager')) {
            return true;
        }

        return $user->hasPermissionTo($permission, 'web') || $user->can($permission);
    }

    /**
     * Get paginated / filtered list of raw materials with search and stock status.
     * Permission: bahan-baku-view
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->checkPermission('bahan-baku-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data bahan baku.',
            ], 403);
        }

        $query = RawMaterial::with(['unitModel'])->withCount('recipes');

        // Filter pencarian berdasarkan nama bahan atau satuan
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('unitModel', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('short_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter status stok (all, safe, warning, danger)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'safe') {
                $query->where('stock', '>', DB::raw('min_stock'))
                    ->where('stock', '>', 0);
            } elseif ($status === 'warning') {
                $query->where('stock', '<=', DB::raw('min_stock'))
                    ->where('stock', '>', 0);
            } elseif ($status === 'danger') {
                $query->where('stock', '<=', 0);
            }
        }

        $query->orderBy('name', 'asc');

        // Statistik ringkasan seluruh bahan baku
        $allMaterials = RawMaterial::all();
        $safeCount = $allMaterials->filter(fn ($m) => $m->stock > $m->min_stock && $m->stock > 0)->count();
        $warningCount = $allMaterials->filter(fn ($m) => $m->stock <= $m->min_stock && $m->stock > 0)->count();
        $dangerCount = $allMaterials->filter(fn ($m) => $m->stock <= 0)->count();
        $totalInventoryValue = $allMaterials->sum(fn ($m) => (float) $m->stock * (float) $m->cost_per_unit);

        $perPage = (int) $request->input('per_page', 50);
        if ($perPage > 0) {
            $paginated = $query->paginate($perPage);
            $items = collect($paginated->items())->map(fn ($mat) => $this->formatMaterial($mat));
            $paginationData = [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ];
        } else {
            $items = $query->get()->map(fn ($mat) => $this->formatMaterial($mat));
            $paginationData = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $items->count(),
                'total' => $items->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'summary' => [
                'total_materials' => $allMaterials->count(),
                'safe_materials' => $safeCount,
                'warning_materials' => $warningCount,
                'danger_materials' => $dangerCount,
                'total_inventory_value' => round($totalInventoryValue, 2),
                'total_inventory_value_formatted' => 'Rp '.number_format($totalInventoryValue, 0, ',', '.'),
            ],
            'meta' => $paginationData,
        ]);
    }

    /**
     * Get units and raw materials options for selection forms.
     */
    public function options(): JsonResponse
    {
        if (! $this->checkPermission('bahan-baku-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat opsi bahan baku.',
            ], 403);
        }

        $units = Unit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        $materials = RawMaterial::with('unitModel')
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'unit_id' => $m->unit_id,
                'unit_short' => $m->display_unit,
                'cost_per_unit' => (float) $m->cost_per_unit,
                'stock' => (float) $m->stock,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'units' => $units,
                'raw_materials' => $materials,
            ],
        ]);
    }

    /**
     * Create a new raw material.
     * Permission: bahan-baku-create
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->checkPermission('bahan-baku-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menambahkan bahan baku.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
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
            return response()->json([
                'success' => false,
                'message' => 'Validasi data bahan baku gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $unit = Unit::find($request->input('unit_id'));

        $material = RawMaterial::create([
            'name' => trim($request->input('name')),
            'unit_id' => $request->input('unit_id'),
            'unit' => $unit?->short_name ?? 'gram',
            'stock' => (float) $request->input('stock'),
            'min_stock' => (float) $request->input('min_stock'),
            'cost_per_unit' => (float) $request->input('cost_per_unit'),
        ]);

        $material->load(['unitModel'])->loadCount('recipes');

        return response()->json([
            'success' => true,
            'message' => "Bahan baku '{$material->name}' berhasil ditambahkan.",
            'data' => $this->formatMaterial($material),
        ], 201);
    }

    /**
     * Show single raw material detail.
     * Permission: bahan-baku-view
     */
    public function show(RawMaterial $rawMaterial): JsonResponse
    {
        if (! $this->checkPermission('bahan-baku-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat detail bahan baku.',
            ], 403);
        }

        $rawMaterial->load(['unitModel', 'recipes.product'])->loadCount('recipes');

        return response()->json([
            'success' => true,
            'data' => $this->formatMaterial($rawMaterial),
        ]);
    }

    /**
     * Update an existing raw material.
     * Permission: bahan-baku-edit
     */
    public function update(Request $request, RawMaterial $rawMaterial): JsonResponse
    {
        if (! $this->checkPermission('bahan-baku-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengubah bahan baku.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
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
            return response()->json([
                'success' => false,
                'message' => 'Validasi data bahan baku gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $unit = Unit::find($request->input('unit_id'));

        $rawMaterial->update([
            'name' => trim($request->input('name')),
            'unit_id' => $request->input('unit_id'),
            'unit' => $unit?->short_name ?? $rawMaterial->unit,
            'stock' => (float) $request->input('stock'),
            'min_stock' => (float) $request->input('min_stock'),
            'cost_per_unit' => (float) $request->input('cost_per_unit'),
        ]);

        $rawMaterial->load(['unitModel'])->loadCount('recipes');

        return response()->json([
            'success' => true,
            'message' => "Bahan baku '{$rawMaterial->name}' berhasil diperbarui.",
            'data' => $this->formatMaterial($rawMaterial),
        ]);
    }

    /**
     * Delete a raw material.
     * Permission: bahan-baku-delete
     */
    public function destroy(RawMaterial $rawMaterial): JsonResponse
    {
        if (! $this->checkPermission('bahan-baku-delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus bahan baku.',
            ], 403);
        }

        $recipesCount = $rawMaterial->recipes()->count();
        if ($recipesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Bahan baku '{$rawMaterial->name}' sedang digunakan pada {$recipesCount} resep produk. Hapus keterkaitan resep terlebih dahulu sebelum menghapus bahan ini.",
            ], 422);
        }

        $name = $rawMaterial->name;
        $rawMaterial->delete();

        return response()->json([
            'success' => true,
            'message' => "Bahan baku '{$name}' berhasil dihapus.",
        ]);
    }

    /**
     * Stock Opname (Penyesuaian Fisik Stok) for a raw material.
     * Permission: bahan-baku-edit
     */
    public function adjustStock(Request $request, RawMaterial $rawMaterial): JsonResponse
    {
        if (! $this->checkPermission('bahan-baku-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menyesuaikan stok bahan baku.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'physical_stock' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
        ], [
            'physical_stock.required' => 'Jumlah stok fisik hasil opname wajib diisi.',
            'physical_stock.min' => 'Stok fisik tidak boleh bernilai negatif.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi stok opname gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $physicalStock = (float) $request->input('physical_stock');
        $currentStock = (float) $rawMaterial->stock;
        $diff = round($physicalStock - $currentStock, 4);

        if ($diff == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Stok fisik yang dimasukkan sama dengan stok sistem (tidak ada selisih).',
            ], 422);
        }

        $reason = $request->input('reason') ?: 'Penyesuaian fisik opname';
        $date = $request->input('date') ?: now()->toDateString();

        DB::transaction(function () use ($rawMaterial, $physicalStock, $currentStock, $diff, $reason, $date) {
            $cost = (float) $rawMaterial->cost_per_unit;

            // 1. Catat ke StockMutation
            StockMutation::create([
                'raw_material_id' => $rawMaterial->id,
                'reference_type' => 'stock_opname',
                'reference_id' => $rawMaterial->id,
                'reference_number' => 'OPN-BAHAN-'.date('Ymd-His'),
                'type' => $diff > 0 ? 'in' : 'out',
                'quantity' => abs($diff),
                'stock_before' => $currentStock,
                'stock_after' => $physicalStock,
                'cost_per_unit' => $cost,
                'notes' => 'Stock Opname: '.$reason,
                'user_id' => Auth::id() ?: auth('api')->id(),
            ]);

            // 2. Update stok bahan
            $rawMaterial->stock = $physicalStock;
            $rawMaterial->save();

            // 3. Catat dan posting Jurnal Akuntansi otomatis
            AccountingService::recordStockAdjustment(
                $rawMaterial,
                $diff,
                $cost,
                $reason,
                $date
            );
        });

        $diffStr = ($diff > 0 ? '+' : '').number_format($diff, 2, ',', '.').' '.$rawMaterial->display_unit;

        $rawMaterial->refresh()->load(['unitModel'])->loadCount('recipes');

        return response()->json([
            'success' => true,
            'message' => "Stok bahan '{$rawMaterial->name}' berhasil disesuaikan ({$diffStr}) dan dicatat di Jurnal Akuntansi.",
            'data' => $this->formatMaterial($rawMaterial),
        ]);
    }

    /**
     * Get list of products with their Recipe / Bill of Materials (BOM) formulas.
     * Permission: bahan-baku-view
     */
    public function recipes(Request $request): JsonResponse
    {
        if (! $this->checkPermission('bahan-baku-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data resep produk.',
            ], 403);
        }

        $query = Product::with(['unitModel', 'recipes.rawMaterial.unitModel'])
            ->where('is_active', true);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name', 'asc');

        $products = $query->get()->map(function ($prod) {
            $materialCost = (float) $prod->material_cost;
            $consignmentPrice = (float) $prod->consignment_price;
            $grossMargin = (float) $prod->consignment_margin;

            $recipeItems = $prod->recipes->map(function ($r) {
                return [
                    'id' => $r->id,
                    'raw_material_id' => $r->raw_material_id,
                    'material_name' => $r->rawMaterial?->name ?? 'Bahan Dihapus',
                    'unit_short' => $r->rawMaterial?->display_unit ?? '-',
                    'cost_per_unit' => (float) ($r->rawMaterial?->cost_per_unit ?? 0),
                    'cost_formatted' => 'Rp '.number_format((float) ($r->rawMaterial?->cost_per_unit ?? 0), 2, ',', '.'),
                    'quantity_needed' => (float) $r->quantity_needed,
                    'subtotal_cost' => (float) $r->subtotal_cost,
                    'subtotal_formatted' => 'Rp '.number_format((float) $r->subtotal_cost, 2, ',', '.'),
                ];
            });

            return [
                'id' => $prod->id,
                'name' => $prod->name,
                'unit_id' => $prod->unit_id,
                'unit_name' => $prod->unitModel?->name ?? $prod->unit,
                'consignment_price' => $consignmentPrice,
                'consignment_formatted' => 'Rp '.number_format($consignmentPrice, 0, ',', '.'),
                'retail_price' => (float) $prod->retail_price,
                'retail_formatted' => 'Rp '.number_format((float) $prod->retail_price, 0, ',', '.'),
                'material_cost' => $materialCost,
                'material_cost_formatted' => 'Rp '.number_format($materialCost, 2, ',', '.'),
                'gross_margin' => $grossMargin,
                'recipes' => $recipeItems,
                'recipes_count' => $recipeItems->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
            'summary' => [
                'total_products' => $products->count(),
                'products_with_recipe' => $products->filter(fn ($p) => $p['recipes_count'] > 0)->count(),
                'products_without_recipe' => $products->filter(fn ($p) => $p['recipes_count'] === 0)->count(),
            ],
        ]);
    }

    /**
     * Save / overwrite formula recipes (BOM) for a product.
     * Permission: resep-manage
     */
    public function saveRecipe(Request $request, Product $product): JsonResponse
    {
        if (! $this->checkPermission('resep-manage')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengatur formula resep.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'ingredients' => ['required', 'array'],
            'ingredients.*.raw_material_id' => ['required', 'exists:raw_materials,id'],
            'ingredients.*.quantity_needed' => ['required', 'numeric', 'min:0.0001'],
        ], [
            'ingredients.required' => 'Daftar bahan baku formula wajib diisi.',
            'ingredients.*.raw_material_id.required' => 'Bahan baku harus dipilih.',
            'ingredients.*.raw_material_id.exists' => 'Bahan baku yang dipilih tidak valid.',
            'ingredients.*.quantity_needed.required' => 'Takaran bahan baku wajib diisi.',
            'ingredients.*.quantity_needed.min' => 'Takaran bahan baku harus lebih dari 0.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi formula resep gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ingredients = $request->input('ingredients');

        DB::transaction(function () use ($product, $ingredients) {
            ProductRecipe::where('product_id', $product->id)->delete();

            foreach ($ingredients as $item) {
                ProductRecipe::create([
                    'product_id' => $product->id,
                    'raw_material_id' => (int) $item['raw_material_id'],
                    'quantity_needed' => (float) $item['quantity_needed'],
                ]);
            }
        });

        $product->refresh()->load(['unitModel', 'recipes.rawMaterial.unitModel']);

        $recipeItems = $product->recipes->map(function ($r) {
            return [
                'id' => $r->id,
                'raw_material_id' => $r->raw_material_id,
                'material_name' => $r->rawMaterial?->name ?? 'Bahan Dihapus',
                'unit_short' => $r->rawMaterial?->display_unit ?? '-',
                'cost_per_unit' => (float) ($r->rawMaterial?->cost_per_unit ?? 0),
                'cost_formatted' => 'Rp '.number_format((float) ($r->rawMaterial?->cost_per_unit ?? 0), 2, ',', '.'),
                'quantity_needed' => (float) $r->quantity_needed,
                'subtotal_cost' => (float) $r->subtotal_cost,
                'subtotal_formatted' => 'Rp '.number_format((float) $r->subtotal_cost, 2, ',', '.'),
            ];
        });

        $materialCost = (float) $product->material_cost;

        return response()->json([
            'success' => true,
            'message' => "Formula resep untuk produk '{$product->name}' berhasil disimpan.",
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'material_cost' => $materialCost,
                'material_cost_formatted' => 'Rp '.number_format($materialCost, 2, ',', '.'),
                'gross_margin' => (float) $product->consignment_margin,
                'recipes' => $recipeItems,
                'recipes_count' => $recipeItems->count(),
            ],
        ]);
    }

    /**
     * Format raw material model for JSON response.
     */
    private function formatMaterial(RawMaterial $mat): array
    {
        return [
            'id' => $mat->id,
            'name' => $mat->name,
            'unit_id' => $mat->unit_id,
            'unit_name' => $mat->unitModel?->name ?? $mat->unit,
            'unit_short' => $mat->display_unit,
            'stock' => (float) $mat->stock,
            'min_stock' => (float) $mat->min_stock,
            'cost_per_unit' => (float) $mat->cost_per_unit,
            'cost_formatted' => 'Rp '.number_format((float) $mat->cost_per_unit, 2, ',', '.'),
            'stock_status' => $mat->stock_status,
            'stock_status_label' => match ($mat->stock_status) {
                'safe' => 'Aman',
                'warning' => 'Menipis',
                'danger' => 'Habis',
                default => 'Tidak Diketahui',
            },
            'recipes_count' => (int) ($mat->recipes_count ?? $mat->recipes()->count()),
            'created_at' => $mat->created_at?->toISOString(),
            'updated_at' => $mat->updated_at?->toISOString(),
        ];
    }
}
