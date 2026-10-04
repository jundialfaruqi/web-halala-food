<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Get paginated list of stores (Toko Mitra) with search by store name.
     * Dilindungi dengan autentikasi auth:api dan permission toko-view.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        // Pastikan permission sama dengan web (toko-view)
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('toko-view', 'web') && ! $user->can('toko-view'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data toko mitra.',
            ], 403);
        }

        $query = Store::query();

        // Filter pencarian berdasarkan nama toko
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        // Filter rute pengantaran opsional jika diperlukan
        if ($request->filled('route')) {
            $query->where('route', $request->input('route'));
        }

        // Filter status aktif opsional jika diperlukan
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Urutkan berdasarkan nama toko
        $query->orderBy('name', 'asc');

        // Pagination
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $stores = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar toko mitra berhasil dimuat.',
            'data' => $stores->items(),
            'pagination' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
                'has_more' => $stores->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get unique delivery routes of stores.
     */
    public function routes(): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('toko-view', 'web') && ! $user->can('toko-view'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat rute toko mitra.',
            ], 403);
        }

        $routes = Store::query()
            ->whereNotNull('route')
            ->where('route', '!=', '')
            ->distinct()
            ->orderBy('route', 'asc')
            ->pluck('route')
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar rute pengantaran berhasil dimuat.',
            'data' => $routes,
        ]);
    }

    /**
     * Get detail of a specific store.
     */
    public function show(Store $store): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail toko mitra berhasil dimuat.',
            'data' => $store,
        ]);
    }
}
