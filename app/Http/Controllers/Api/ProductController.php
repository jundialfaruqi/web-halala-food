<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get paginated list of products (Master Produk Jadi) with search & filters.
     * Dilindungi dengan autentikasi auth:api dan permission produk-view.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        // Pastikan permission sama dengan web (produk-view)
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('produk-view', 'web') && ! $user->can('produk-view'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data produk.',
            ], 403);
        }

        $query = Product::with(['unitModel']);

        // Filter pencarian berdasarkan nama produk atau deskripsi atau satuan
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhereHas('unitModel', function ($uq) use ($searchTerm) {
                        $uq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('short_name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Filter status aktif (all, active, inactive)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'active' || $status === '1' || $status === 'true') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0' || $status === 'false') {
                $query->where('is_active', false);
            }
        } elseif ($request->has('is_active') && $request->input('is_active') !== null && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter satuan kemasan
        if ($request->filled('unit_id') && $request->input('unit_id') !== 'all') {
            $query->where('unit_id', $request->input('unit_id'));
        }

        // Urutkan berdasarkan nama produk
        $query->orderBy('name', 'asc');

        // Pagination
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));
        $products = $query->paginate($perPage);

        $mappedProducts = collect($products->items())->map(function ($prod) {
            return [
                'id' => $prod->id,
                'name' => $prod->name,
                'unit_id' => $prod->unit_id,
                'unit_name' => $prod->unitModel?->name ?? $prod->unit,
                'unit_short' => $prod->unitModel?->short_name ?? $prod->unit,
                'consignment_price' => (float) $prod->consignment_price,
                'consignment_price_formatted' => 'Rp ' . number_format($prod->consignment_price, 0, ',', '.'),
                'retail_price' => (float) $prod->retail_price,
                'retail_price_formatted' => 'Rp ' . number_format($prod->retail_price, 0, ',', '.'),
                'stock_ready' => (int) $prod->stock_ready,
                'description' => $prod->description ?? '-',
                'is_active' => (bool) $prod->is_active,
                'photo_url' => $prod->photo_url,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk berhasil dimuat.',
            'data' => $mappedProducts,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_more' => $products->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get list of active units for filtering products.
     */
    public function units(): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('produk-view', 'web') && ! $user->can('produk-view'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat satuan produk.',
            ], 403);
        }

        $units = Unit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        return response()->json([
            'success' => true,
            'message' => 'Daftar satuan produk berhasil dimuat.',
            'data' => $units,
        ]);
    }

    /**
     * Get detail of a specific product.
     */
    public function show(Product $product): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('produk-view', 'web') && ! $user->can('produk-view'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat detail produk.',
            ], 403);
        }

        $product->load(['unitModel']);

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil dimuat.',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'unit_id' => $product->unit_id,
                'unit_name' => $product->unitModel?->name ?? $product->unit,
                'unit_short' => $product->unitModel?->short_name ?? $product->unit,
                'consignment_price' => (float) $product->consignment_price,
                'consignment_price_formatted' => 'Rp ' . number_format($product->consignment_price, 0, ',', '.'),
                'retail_price' => (float) $product->retail_price,
                'retail_price_formatted' => 'Rp ' . number_format($product->retail_price, 0, ',', '.'),
                'stock_ready' => (int) $product->stock_ready,
                'description' => $product->description ?? '-',
                'is_active' => (bool) $product->is_active,
                'photo_url' => $product->photo_url,
            ],
        ]);
    }
}
