<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\RawMaterial;
use App\Models\StockMutation;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductionController extends Controller
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
     * Get list of production batches with search, status filter, and summary statistics.
     * Permission: produksi-view
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->checkPermission('produksi-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data produksi.',
            ], 403);
        }

        $query = ProductionBatch::with([
            'product.unitModel',
            'user',
            'batchMaterials.rawMaterial.unitModel',
        ]);

        // Filter pencarian berdasarkan kode batch, nama produk, catatan, atau operator
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('batch_code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter status (all, completed, cancelled)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $batches = $query->orderByDesc('id')->get();

        // Hitung statistik ringkasan KPI
        $allBatches = ProductionBatch::all();
        $totalBatchesCount = $allBatches->count();
        $completedBatches = $allBatches->where('status', 'completed');
        $totalGoodProduced = (int) $completedBatches->sum('actual_qty_good');
        $totalBadProduced = (int) $completedBatches->sum('actual_qty_bad');
        $totalProductionCost = (float) $completedBatches->sum('total_material_cost');
        $totalProduced = $totalGoodProduced + $totalBadProduced;

        $stats = [
            'total_batches' => $totalBatchesCount,
            'total_good' => $totalGoodProduced,
            'total_bad' => $totalBadProduced,
            'total_cost' => $totalProductionCost,
            'success_rate' => $totalProduced > 0
                ? round(($totalGoodProduced / $totalProduced) * 100, 1)
                : 100.0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'batches' => $batches->map(function (ProductionBatch $b) {
                    return $this->formatBatch($b);
                }),
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Get stock mutations history (Kartu Stok Bahan Baku).
     * Permission: produksi-view
     */
    public function mutations(Request $request): JsonResponse
    {
        if (! $this->checkPermission('produksi-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat kartu stok bahan baku.',
            ], 403);
        }

        $query = StockMutation::with(['rawMaterial.unitModel', 'user']);

        // Filter pencarian berdasarkan nama bahan, nomor referensi, atau catatan
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('rawMaterial', function ($mq) use ($search) {
                        $mq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter tipe mutasi (all, out, in)
        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        // Filter berdasarkan bahan baku spesifik
        if ($request->filled('raw_material_id') && $request->input('raw_material_id') !== 'all') {
            $query->where('raw_material_id', $request->input('raw_material_id'));
        }

        $limit = $request->input('limit', 100);
        $mutations = $query->orderByDesc('id')->limit((int) $limit)->get();

        return response()->json([
            'success' => true,
            'data' => $mutations->map(function (StockMutation $m) {
                $rawMat = $m->rawMaterial;
                $unitDisplay = $rawMat ? ($rawMat->display_unit ?? $rawMat->unit ?? 'gr') : 'gr';

                return [
                    'id' => $m->id,
                    'raw_material_id' => $m->raw_material_id,
                    'raw_material_name' => $rawMat ? $rawMat->name : 'Bahan Baku',
                    'unit' => $unitDisplay,
                    'reference_type' => $m->reference_type,
                    'reference_id' => $m->reference_id,
                    'reference_number' => $m->reference_number ?? '-',
                    'type' => $m->type, // in | out
                    'type_label' => $m->type === 'out' ? 'Produksi (Keluar)' : 'Masuk (Koreksi)',
                    'quantity' => (float) $m->quantity,
                    'stock_before' => (float) $m->stock_before,
                    'stock_after' => (float) $m->stock_after,
                    'cost_per_unit' => (float) $m->cost_per_unit,
                    'notes' => $m->notes ?? '-',
                    'user_id' => $m->user_id,
                    'user_name' => $m->user ? $m->user->name : 'Sistem',
                    'created_at' => $m->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Get options required for starting a new batch (products with recipes & raw materials).
     * Permission: produksi-view or produksi-create
     */
    public function options(): JsonResponse
    {
        if (! $this->checkPermission('produksi-view') && ! $this->checkPermission('produksi-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk memuat opsi produksi.',
            ], 403);
        }

        $products = Product::with(['recipes.rawMaterial.unitModel', 'unitModel'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $rawMaterials = RawMaterial::with('unitModel')->orderBy('name')->get();

        $nextBatchCode = ProductionBatch::generateBatchCode();

        return response()->json([
            'success' => true,
            'data' => [
                'next_batch_code' => $nextBatchCode,
                'products' => $products->map(function (Product $p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'unit' => $p->display_unit ?? $p->unit ?? 'pcs',
                        'price' => (float) $p->price,
                        'stock_ready' => (int) $p->stock_ready,
                        'photo_url' => $p->photo_url,
                        'has_recipe' => $p->recipes->isNotEmpty(),
                        'recipes_count' => $p->recipes->count(),
                        'recipes' => $p->recipes->map(function ($r) {
                            $rawMat = $r->rawMaterial;
                            return [
                                'id' => $r->id,
                                'raw_material_id' => $r->raw_material_id,
                                'raw_material_name' => $rawMat ? $rawMat->name : 'Bahan Baku',
                                'quantity_needed' => (float) $r->quantity_needed,
                                'unit' => $rawMat ? ($rawMat->display_unit ?? $rawMat->unit ?? 'gr') : 'gr',
                                'current_stock' => $rawMat ? (float) $rawMat->stock : 0.0,
                                'cost_per_unit' => $rawMat ? (float) $rawMat->cost_per_unit : 0.0,
                            ];
                        }),
                    ];
                }),
                'raw_materials' => $rawMaterials->map(function (RawMaterial $m) {
                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                        'stock' => (float) $m->stock,
                        'unit' => $m->display_unit ?? $m->unit ?? 'gr',
                        'cost_per_unit' => (float) $m->cost_per_unit,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Execute a new cooking batch with atomic material deduction & finished goods increment.
     * Permission: produksi-create
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->checkPermission('produksi-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengeksekusi produksi.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'exists:products,id'],
            'planned_qty' => ['required', 'integer', 'min:1'],
            'actual_qty_good' => ['required', 'integer', 'min:0'],
            'actual_qty_bad' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'product_id.required' => 'Pilih produk yang akan diproduksi.',
            'product_id.exists' => 'Produk yang dipilih tidak valid.',
            'planned_qty.required' => 'Target jumlah produksi wajib diisi.',
            'planned_qty.min' => 'Target produksi minimal 1 unit.',
            'actual_qty_good.required' => 'Jumlah hasil bagus (lolos QC) wajib diisi.',
            'actual_qty_good.min' => 'Jumlah hasil bagus minimal 0 unit.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $productId = (int) $request->input('product_id');
        $plannedQty = (int) $request->input('planned_qty');
        $actualGood = (int) $request->input('actual_qty_good');
        $actualBad = (int) ($request->input('actual_qty_bad') ?? 0);
        $notes = $request->filled('notes') ? trim((string) $request->input('notes')) : null;

        $product = Product::with(['recipes.rawMaterial.unitModel', 'unitModel'])->find($productId);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        if ($product->recipes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Produk '{$product->name}' belum memiliki formula resep (BOM). Silakan buat resep terlebih dahulu di menu Bahan Baku & Resep.",
            ], 422);
        }

        // Cek ketersediaan bahan baku sesuai target rencana
        $shortages = [];
        $materialRequirements = [];

        foreach ($product->recipes as $recipe) {
            $rawMat = $recipe->rawMaterial;
            if (! $rawMat) {
                continue;
            }

            $neededQty = (float) $recipe->quantity_needed * $plannedQty;
            $unitName = $rawMat->display_unit ?? $rawMat->unit ?? 'gr';

            if ((float) $rawMat->stock < $neededQty) {
                $deficit = $neededQty - (float) $rawMat->stock;
                $shortages[] = "{$rawMat->name} (Kurang ".number_format($deficit, 2, ',', '.')." {$unitName})";
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
            return response()->json([
                'success' => false,
                'message' => 'Stok bahan baku tidak mencukupi untuk memproduksi '.number_format($plannedQty, 0, ',', '.')." unit:\n• ".implode("\n• ", $shortages),
                'shortages' => $shortages,
            ], 422);
        }

        // Eksekusi transaksi database
        try {
            $result = DB::transaction(function () use ($product, $plannedQty, $actualGood, $actualBad, $notes, $materialRequirements) {
                $batchCode = ProductionBatch::generateBatchCode();
                $totalCost = array_sum(array_column($materialRequirements, 'subtotal_cost'));
                $unitCostProduced = $actualGood > 0 ? ($totalCost / $actualGood) : 0;

                $batch = ProductionBatch::create([
                    'batch_code' => $batchCode,
                    'product_id' => $product->id,
                    'user_id' => Auth::id() ?? auth('api')->id(),
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

                // Potong stok masing-masing bahan baku & catat mutasi stok
                foreach ($materialRequirements as $item) {
                    /** @var RawMaterial $mat */
                    $mat = $item['material'];
                    $usedQty = $item['needed_qty'];
                    $stockBefore = (float) $mat->stock;
                    $stockAfter = $stockBefore - $usedQty;

                    // Kurangi stok bahan
                    $mat->decrement('stock', $usedQty);

                    // Rekam snapshot di production_batch_materials
                    ProductionBatchMaterial::create([
                        'production_batch_id' => $batch->id,
                        'raw_material_id' => $mat->id,
                        'unit_name' => $item['unit_name'],
                        'planned_qty' => $usedQty,
                        'actual_used_qty' => $usedQty,
                        'cost_per_unit' => $item['cost_per_unit'],
                        'subtotal_cost' => $item['subtotal_cost'],
                    ]);

                    // Rekam kartu stok mutasi audit
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
                        'user_id' => Auth::id() ?? auth('api')->id(),
                    ]);
                }

                // Tambahkan stok produk jadi siap jual
                $product->increment('stock_ready', $actualGood);

                // Catat jurnal akuntansi pemakaian bahan & penambahan produk jadi
                AccountingService::recordProduction($batch);

                return $batch;
            });

            $loadedBatch = ProductionBatch::with([
                'product.unitModel',
                'user',
                'batchMaterials.rawMaterial.unitModel',
            ])->find($result->id);

            return response()->json([
                'success' => true,
                'message' => "Batch masak {$result->batch_code} berhasil dieksekusi! Stok bahan baku terpotong dan {$actualGood} unit {$product->name} siap jual bertambah ke gudang.",
                'data' => $this->formatBatch($loadedBatch),
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengeksekusi batch: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show detailed batch information.
     * Permission: produksi-view
     */
    public function show(ProductionBatch $productionBatch): JsonResponse
    {
        if (! $this->checkPermission('produksi-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat rincian batch produksi.',
            ], 403);
        }

        $productionBatch->load([
            'product.unitModel',
            'user',
            'batchMaterials.rawMaterial.unitModel',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatBatch($productionBatch),
        ]);
    }

    /**
     * Cancel an existing batch, safely restoring raw materials and adjusting finished goods stock.
     * Permission: produksi-delete
     */
    public function cancel(ProductionBatch $productionBatch): JsonResponse
    {
        if (! $this->checkPermission('produksi-delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membatalkan batch produksi.',
            ], 403);
        }

        $productionBatch->load(['batchMaterials.rawMaterial', 'product']);

        if ($productionBatch->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Batch produksi ini sudah dibatalkan sebelumnya.',
            ], 422);
        }

        // Cek apakah stok barang jadi di gudang masih mencukupi untuk ditarik kembali
        if ($productionBatch->product && $productionBatch->product->stock_ready < $productionBatch->actual_qty_good) {
            return response()->json([
                'success' => false,
                'message' => "Tidak dapat membatalkan batch. Stok barang jadi di gudang saat ini ({$productionBatch->product->stock_ready}) lebih kecil dari hasil batch ({$productionBatch->actual_qty_good}), sebagian produk kemungkinan telah terjual atau dikirim.",
            ], 422);
        }

        try {
            DB::transaction(function () use ($productionBatch) {
                // Kembalikan stok bahan baku
                foreach ($productionBatch->batchMaterials as $bm) {
                    $mat = $bm->rawMaterial;
                    if ($mat) {
                        $stockBefore = (float) $mat->stock;
                        $restoreQty = (float) $bm->actual_used_qty;
                        $stockAfter = $stockBefore + $restoreQty;

                        $mat->increment('stock', $restoreQty);

                        StockMutation::create([
                            'raw_material_id' => $mat->id,
                            'reference_type' => 'adjustment',
                            'reference_id' => $productionBatch->id,
                            'reference_number' => $productionBatch->batch_code,
                            'type' => 'in',
                            'quantity' => $restoreQty,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'cost_per_unit' => (float) $bm->cost_per_unit,
                            'notes' => "Pengembalian bahan akibat pembatalan batch {$productionBatch->batch_code}",
                            'user_id' => Auth::id() ?? auth('api')->id(),
                        ]);
                    }
                }

                // Kurangi kembali stok barang jadi
                if ($productionBatch->product && $productionBatch->actual_qty_good > 0) {
                    $productionBatch->product->decrement('stock_ready', $productionBatch->actual_qty_good);
                }

                // Batalkan catatan jurnal akuntansi
                JournalEntry::where('reference_type', 'production')
                    ->where('reference_id', $productionBatch->id)
                    ->delete();

                $productionBatch->update(['status' => 'cancelled']);
            });

            $productionBatch->refresh()->load([
                'product.unitModel',
                'user',
                'batchMaterials.rawMaterial.unitModel',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Batch {$productionBatch->batch_code} berhasil dibatalkan. Seluruh bahan baku dan stok produk telah dikembalikan secara proporsional.",
                'data' => $this->formatBatch($productionBatch),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat membatalkan batch: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to format ProductionBatch model into standardized array.
     */
    private function formatBatch(ProductionBatch $batch): array
    {
        $prod = $batch->product;
        $unitDisplay = $prod ? ($prod->display_unit ?? $prod->unit ?? 'pcs') : 'pcs';

        return [
            'id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'product_id' => $batch->product_id,
            'product_name' => $prod ? $prod->name : 'Produk Tidak Ditemukan',
            'product_unit' => $unitDisplay,
            'product_photo_url' => $prod ? $prod->photo_url : null,
            'user_id' => $batch->user_id,
            'operator_name' => $batch->user ? $batch->user->name : 'Sistem',
            'planned_qty' => (int) $batch->planned_qty,
            'actual_qty_good' => (int) $batch->actual_qty_good,
            'actual_qty_bad' => (int) $batch->actual_qty_bad,
            'total_material_cost' => (float) $batch->total_material_cost,
            'unit_cost_produced' => (float) $batch->unit_cost_produced,
            'status' => $batch->status, // completed | cancelled
            'status_label' => $batch->status === 'completed' ? 'Selesai' : 'Dibatalkan',
            'notes' => $batch->notes,
            'started_at' => $batch->started_at?->toIso8601String(),
            'completed_at' => $batch->completed_at?->toIso8601String(),
            'created_at' => $batch->created_at?->toIso8601String(),
            'materials' => $batch->batchMaterials->map(function (ProductionBatchMaterial $bm) {
                $raw = $bm->rawMaterial;
                return [
                    'id' => $bm->id,
                    'raw_material_id' => $bm->raw_material_id,
                    'raw_material_name' => $raw ? $raw->name : 'Bahan Baku',
                    'unit_name' => $bm->unit_name ?? ($raw ? ($raw->display_unit ?? $raw->unit) : 'gr'),
                    'planned_qty' => (float) $bm->planned_qty,
                    'actual_used_qty' => (float) $bm->actual_used_qty,
                    'cost_per_unit' => (float) $bm->cost_per_unit,
                    'subtotal_cost' => (float) $bm->subtotal_cost,
                ];
            }),
        ];
    }
}
