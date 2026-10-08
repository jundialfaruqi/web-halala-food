<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Helper to verify if the authenticated user has the specified permission.
     */
    private function checkPermission(string $permission): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('dev') || $user->hasPermissionTo($permission, 'web') || $user->can($permission);
    }

    /**
     * Get paginated list of invoices with filters for payment status, search, and store.
     * Permission: faktur-view
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->checkPermission('faktur-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data faktur tagihan.',
            ], 403);
        }

        $query = Invoice::with(['store', 'creator', 'items.product', 'payments']);

        // Search Filter (Nomor faktur, catatan, nama toko mitra, nama pemilik, alamat)
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('invoice_number', 'like', "%{$searchTerm}%")
                    ->orWhere('notes', 'like', "%{$searchTerm}%")
                    ->orWhereHas('store', function ($sq) use ($searchTerm) {
                        $sq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('owner_name', 'like', "%{$searchTerm}%")
                            ->orWhere('address', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Status Pelunasan Filter (belum_dibayar, sebagian, lunas, overdue, dibatalkan)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'overdue') {
                $query->whereNotIn('status', ['lunas', 'dibatalkan'])
                    ->whereDate('due_date', '<', now()->toDateString());
            } else {
                $query->where('status', $status);
            }
        }

        // Store Filter
        if ($request->filled('store_id') && $request->input('store_id') !== 'all') {
            $query->where('store_id', $request->input('store_id'));
        }

        // Date Filter
        if ($request->filled('invoice_date')) {
            $query->whereDate('invoice_date', $request->input('invoice_date'));
        }

        // Status counts for badge tabs
        $statusCounts = [
            'all' => Invoice::count(),
            'belum_dibayar' => Invoice::where('status', 'belum_dibayar')->count(),
            'sebagian' => Invoice::where('status', 'sebagian')->count(),
            'lunas' => Invoice::where('status', 'lunas')->count(),
            'overdue' => Invoice::whereNotIn('status', ['lunas', 'dibatalkan'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'dibatalkan' => Invoice::where('status', 'dibatalkan')->count(),
        ];

        // Store options list for filter dropdown
        $stores = Store::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'owner_name', 'address', 'phone', 'route']);

        // Order by latest invoice date and id
        $query->orderByDesc('invoice_date')->orderByDesc('id');

        // Pagination
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $invoices = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar faktur tagihan berhasil dimuat.',
            'data' => $invoices->items(),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
            'status_counts' => $statusCounts,
            'stores' => $stores,
        ]);
    }

    /**
     * Show detail of an invoice.
     * Permission: faktur-view
     */
    public function show(Invoice $invoice): JsonResponse
    {
        if (! $this->checkPermission('faktur-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat rincian faktur tagihan.',
            ], 403);
        }

        $invoice->load([
            'store',
            'delivery',
            'creator',
            'items.product.unitModel',
            'payments.user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rincian faktur tagihan berhasil dimuat.',
            'data' => $invoice,
        ]);
    }
}
