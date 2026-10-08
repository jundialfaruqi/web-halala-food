<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Store;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
     * Get options for creating a new invoice (stores, products, deliveries, next invoice number).
     * Permission: faktur-create
     */
    public function createOptions(Request $request): JsonResponse
    {
        if (! $this->checkPermission('faktur-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membuat faktur tagihan.',
            ], 403);
        }

        $stores = Store::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'owner_name', 'phone', 'address', 'latitude', 'longitude', 'route']);

        $products = Product::where('is_active', true)
            ->with('unitModel')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'unit_id', 'stock_ready', 'consignment_price', 'retail_price', 'photo']);

        $deliveriesQuery = Delivery::with(['store', 'items.product.unitModel'])
            ->where('status', '!=', 'dibatalkan')
            ->whereDoesntHave('invoice', function ($q) {
                $q->where('status', '!=', 'dibatalkan');
            });

        if ($request->filled('store_id')) {
            $deliveriesQuery->where('store_id', $request->input('store_id'));
        }

        $deliveries = $deliveriesQuery->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        $nextInvoiceNumber = Invoice::generateInvoiceNumber();

        return response()->json([
            'success' => true,
            'message' => 'Opsi formulir pembuatan faktur tagihan berhasil dimuat.',
            'data' => [
                'stores' => $stores,
                'products' => $products,
                'deliveries' => $deliveries,
                'next_invoice_number' => $nextInvoiceNumber,
                'default_invoice_date' => now()->toDateString(),
                'default_due_date' => now()->addDays(14)->toDateString(),
            ],
        ]);
    }

    /**
     * Store a newly created invoice with items, accounting sync, and stock update for returned goods.
     * Permission: faktur-create
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->checkPermission('faktur-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membuat faktur tagihan.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'invoice_number' => ['nullable', 'string', 'max:50', 'unique:invoices,invoice_number'],
            'store_id' => ['required', 'exists:stores,id'],
            'delivery_id' => [
                'nullable',
                'exists:deliveries,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $hasInvoice = Invoice::where('delivery_id', $value)
                            ->where('status', '!=', 'dibatalkan')
                            ->exists();
                        if ($hasInvoice) {
                            $fail('Surat jalan ini sudah memiliki faktur tagihan.');
                        }
                    }
                },
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.delivered_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.remaining_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.returned_quantity' => ['nullable', 'integer', 'min:0'],
        ], [
            'store_id.required' => 'Pilih toko mitra tujuan penagihan.',
            'store_id.exists' => 'Toko mitra yang dipilih tidak valid.',
            'delivery_id.exists' => 'Surat jalan yang dipilih tidak valid.',
            'invoice_date.required' => 'Tanggal faktur wajib diisi.',
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal faktur.',
            'discount.numeric' => 'Potongan harga / diskon harus berupa angka.',
            'discount.min' => 'Potongan harga / diskon tidak boleh bernilai negatif.',
            'items.required' => 'Daftar rincian faktur wajib diisi minimal 1 jenis produk.',
            'items.min' => 'Daftar rincian faktur wajib diisi minimal 1 jenis produk.',
            'items.*.product_id.required' => 'Pilih produk untuk setiap baris faktur.',
            'items.*.product_id.exists' => 'Produk yang dipilih tidak valid.',
            'items.*.product_id.distinct' => 'Produk tidak boleh ganda pada faktur yang sama.',
            'items.*.quantity.required' => 'Jumlah barang tertagih wajib diisi.',
            'items.*.quantity.min' => 'Jumlah barang tertagih minimal 0.',
            'items.*.unit_price.required' => 'Harga satuan wajib diisi.',
            'items.*.unit_price.min' => 'Harga satuan tidak boleh negatif.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Silakan periksa kembali data yang dimasukkan.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $itemsData = $request->input('items', []);
        $subtotal = 0.0;
        foreach ($itemsData as $item) {
            $qty = (int) $item['quantity'];
            $price = (float) $item['unit_price'];
            $subtotal += ($qty * $price);
        }

        $discount = (float) ($request->input('discount') ?? 0);
        $totalAmount = max(0.0, $subtotal - $discount);

        $invoiceNumber = $request->filled('invoice_number')
            ? trim((string) $request->input('invoice_number'))
            : Invoice::generateInvoiceNumber();

        $invoice = DB::transaction(function () use ($request, $invoiceNumber, $subtotal, $discount, $totalAmount, $itemsData) {
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'delivery_id' => $request->input('delivery_id'),
                'store_id' => $request->input('store_id'),
                'created_by' => auth('api')->id(),
                'invoice_date' => $request->input('invoice_date'),
                'due_date' => $request->input('due_date'),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'remaining_balance' => $totalAmount,
                'status' => 'belum_dibayar',
                'notes' => $request->input('notes'),
            ]);

            foreach ($itemsData as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $delivered = isset($item['delivered_quantity']) && $item['delivered_quantity'] !== '' ? (int) $item['delivered_quantity'] : $qty;
                $remaining = (int) ($item['remaining_quantity'] ?? 0);
                $damaged = (int) ($item['damaged_quantity'] ?? 0);
                $returned = (int) ($item['returned_quantity'] ?? 0);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'delivered_quantity' => $delivered,
                    'remaining_quantity' => $remaining,
                    'damaged_quantity' => $damaged,
                    'returned_quantity' => $returned,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $qty * $price,
                ]);

                // Increment warehouse stock for returned goods that were brought back in good condition
                if ($returned > 0) {
                    Product::where('id', $item['product_id'])->increment('stock_ready', $returned);
                }
            }

            return $invoice;
        });

        // Sinkronisasi pembukuan & akuntansi
        AccountingService::syncInvoiceAccounting($invoice);

        $invoice->load([
            'store',
            'delivery',
            'creator',
            'items.product.unitModel',
            'payments.user',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Faktur tagihan {$invoice->invoice_number} berhasil dibuat.",
            'data' => $invoice,
        ], 201);
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
