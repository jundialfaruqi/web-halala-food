<?php

namespace App\Http\Controllers\Api;

use App\Events\InvoiceCreated;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    private function isDevOrManager(User $user): bool
    {
        return $user->hasRole('dev', 'web')
            || $user->hasRole('manager', 'web')
            || $user->roles->contains('name', 'dev')
            || $user->roles->contains('name', 'manager');
    }

    /**
     * Helper to verify if the authenticated user has the specified permission.
     */
    private function checkPermission(string $permission): bool
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return false;
        }

        return $this->isDevOrManager($user)
            || $user->hasPermissionTo($permission, 'web')
            || $user->can($permission);
    }

    /**
     * Check if user is allowed to record payment for the invoice.
     * Allowed if:
     * 1. User has 'faktur-pembayaran' permission or 'faktur-edit' permission
     * 2. Or user has dev/manager role
     * 3. Or user is the courier assigned to this invoice ($invoice->courier_id === $user->id)
     */
    private function canRecordPaymentForInvoice(Invoice $invoice): bool
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return false;
        }

        if ($this->isDevOrManager($user)) {
            return true;
        }

        if ($this->checkPermission('faktur-pembayaran') || $this->checkPermission('faktur-edit')) {
            return true;
        }

        return $invoice->courier_id !== null && (int) $invoice->courier_id === (int) $user->id;
    }

    /**
     * Check if user is allowed to reconcile items for the invoice.
     * Allowed if:
     * 1. User has 'faktur-rekonsiliasi' permission or 'faktur-edit' permission
     * 2. Or user has dev/manager role
     * 3. Or user is the courier assigned to this invoice ($invoice->courier_id === $user->id)
     */
    private function canReconcileInvoice(Invoice $invoice): bool
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return false;
        }

        if ($this->isDevOrManager($user)) {
            return true;
        }

        if ($this->checkPermission('faktur-rekonsiliasi') || $this->checkPermission('faktur-edit')) {
            return true;
        }

        return $invoice->courier_id !== null && (int) $invoice->courier_id === (int) $user->id;
    }

    /**
     * Check if user is allowed to delete payment record.
     * Strictly requires 'faktur-pembayaran-delete' permission (or dev/manager role).
     * Assigned courier status does NOT automatically allow deleting payment records.
     */
    private function canDeletePaymentRecord(): bool
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        if (! $user) {
            return false;
        }

        if ($this->isDevOrManager($user)) {
            return true;
        }

        return $this->checkPermission('faktur-pembayaran-delete');
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

        $deliveriesQuery = Delivery::with(['store', 'courier', 'items.product.unitModel'])
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

        /** @var User|null $user */
        $user = auth('api')->user();
        $isCourier = $user && $user->hasRole('kurir') && ! $user->hasAnyRole(['dev', 'manager']);

        $couriers = $isCourier
            ? User::where('id', $user->id)->get(['id', 'name', 'phone', 'email'])
            : User::role('kurir')->orderBy('name')->get(['id', 'name', 'phone', 'email']);
        if ($couriers->isEmpty()) {
            $couriers = User::orderBy('name')->get(['id', 'name', 'phone', 'email']);
        }

        $nextInvoiceNumber = Invoice::generateInvoiceNumber();

        return response()->json([
            'success' => true,
            'message' => 'Opsi formulir pembuatan faktur tagihan berhasil dimuat.',
            'data' => [
                'stores' => $stores,
                'couriers' => $couriers,
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
            'courier_id' => ['nullable', 'exists:users,id'],
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
            'courier_id.exists' => 'Kurir yang dipilih tidak valid.',
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

        /** @var User|null $currentUser */
        $currentUser = auth('api')->user();
        $isCourier = $currentUser && $currentUser->hasRole('kurir') && ! $currentUser->hasAnyRole(['dev', 'manager']);

        $courierId = $isCourier ? $currentUser->id : $request->input('courier_id');
        if (! $courierId && $request->filled('delivery_id')) {
            $delivery = Delivery::find($request->input('delivery_id'));
            $courierId = $delivery?->courier_id;
        }

        $invoice = DB::transaction(function () use ($request, $invoiceNumber, $courierId, $subtotal, $discount, $totalAmount, $itemsData) {
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'delivery_id' => $request->input('delivery_id'),
                'store_id' => $request->input('store_id'),
                'courier_id' => $courierId,
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
            'courier',
            'creator',
            'items.product.unitModel',
            'payments.user',
        ]);

        // Broadcast event ke kurir yang ditugaskan & manajemen secara real-time via WebSocket Reverb
        try {
            event(new InvoiceCreated($invoice));
        } catch (\Throwable $e) {
            Log::warning('Broadcast InvoiceCreated error: '.$e->getMessage());
        }

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

        $query = Invoice::with(['store', 'courier', 'creator', 'items.product', 'payments']);

        // Search Filter (Nomor faktur, catatan, nama toko mitra, nama pemilik, alamat, nama kurir)
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('invoice_number', 'like', "%{$searchTerm}%")
                    ->orWhere('notes', 'like', "%{$searchTerm}%")
                    ->orWhereHas('store', function ($sq) use ($searchTerm) {
                        $sq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('owner_name', 'like', "%{$searchTerm}%")
                            ->orWhere('address', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('courier', function ($cq) use ($searchTerm) {
                        $cq->where('name', 'like', "%{$searchTerm}%");
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

        // Courier Filter
        if ($request->filled('courier_id') && $request->input('courier_id') !== 'all') {
            $query->where('courier_id', $request->input('courier_id'));
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
            'courier',
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

    /**
     * Update an invoice and its items.
     * Permission: faktur-edit
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->checkPermission('faktur-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengubah faktur tagihan.',
            ], 403);
        }

        if ($invoice->status === 'lunas' || $invoice->status === 'dibatalkan') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur yang sudah lunas atau dibatalkan tidak dapat diedit.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'store_id' => ['required', 'exists:stores,id'],
            'courier_id' => ['nullable', 'exists:users,id'],
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $itemsData = $request->input('items', []);
        $discount = (float) $request->input('discount', 0);
        $subtotal = 0.0;
        foreach ($itemsData as $item) {
            $subtotal += ((int) $item['quantity']) * ((float) $item['unit_price']);
        }
        $totalAmount = max(0.0, $subtotal - $discount);

        if ((float) $invoice->paid_amount > $totalAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Total tagihan baru tidak boleh lebih kecil dari pembayaran yang sudah diterima (Rp '.number_format((float) $invoice->paid_amount, 0, ',', '.').').',
            ], 422);
        }

        DB::transaction(function () use ($invoice, $request, $itemsData, $subtotal, $discount, $totalAmount) {
            $updatePayload = [
                'store_id' => $request->input('store_id'),
                'invoice_date' => $request->input('invoice_date'),
                'due_date' => $request->input('due_date'),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'notes' => $request->input('notes'),
            ];

            if ($request->has('courier_id')) {
                $updatePayload['courier_id'] = $request->input('courier_id');
            }

            $invoice->update($updatePayload);

            // Revert previously returned items from warehouse inventory before deleting
            foreach ($invoice->items as $oldItem) {
                if ($oldItem->returned_quantity > 0) {
                    Product::where('id', $oldItem->product_id)->decrement('stock_ready', $oldItem->returned_quantity);
                }
            }

            // Replace line items
            $invoice->items()->delete();
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

                // Increment warehouse stock for newly returned items
                if ($returned > 0) {
                    Product::where('id', $item['product_id'])->increment('stock_ready', $returned);
                }
            }

            $invoice->recalculateStatusAndBalance();
            AccountingService::syncInvoiceAccounting($invoice);
        });

        $invoice->load(['store', 'delivery', 'courier', 'creator', 'items.product.unitModel', 'payments.user']);

        return response()->json([
            'success' => true,
            'message' => "Faktur tagihan {$invoice->invoice_number} berhasil diperbarui.",
            'data' => $invoice,
        ]);
    }

    /**
     * Cancel an unpaid or partially paid invoice.
     * Permission: faktur-edit
     */
    public function cancel(Invoice $invoice): JsonResponse
    {
        if (! $this->checkPermission('faktur-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membatalkan faktur ini.',
            ], 403);
        }

        if ($invoice->status === 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur yang sudah lunas tidak dapat dibatalkan.',
            ], 422);
        }

        if ($invoice->status === 'dibatalkan') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur ini sudah dalam status dibatalkan.',
            ], 422);
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'dibatalkan']);
            AccountingService::deleteInvoiceRecords($invoice);
        });

        return response()->json([
            'success' => true,
            'message' => "Faktur {$invoice->invoice_number} berhasil dibatalkan.",
        ]);
    }

    /**
     * Delete an invoice and restore any affected data.
     * Permission: faktur-delete
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        if (! $this->checkPermission('faktur-delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus faktur.',
            ], 403);
        }

        if ($invoice->status === 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur yang telah lunas tidak boleh dihapus demi integritas data keuangan.',
            ], 422);
        }

        $invoiceNumber = $invoice->invoice_number;

        DB::transaction(function () use ($invoice) {
            AccountingService::deleteInvoiceRecords($invoice);
            $invoice->payments()->delete();
            $invoice->items()->delete();
            $invoice->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Faktur {$invoiceNumber} berhasil dihapus.",
        ]);
    }

    /**
     * Record a new payment for an invoice.
     * Permission: faktur-pembayaran or assigned courier (or faktur-edit)
     */
    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->canRecordPaymentForInvoice($invoice)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mencatat pembayaran faktur.',
            ], 403);
        }

        if ($invoice->status === 'dibatalkan') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur yang telah dibatalkan tidak dapat dicatat pembayarannya.',
            ], 422);
        }

        $remaining = (float) $invoice->remaining_balance;
        if ($remaining <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Faktur ini sudah lunas.',
            ], 422);
        }

        // Normalisasi payload jika client mengirim 'amount' atau variasi payment_method
        if ($request->has('amount') && ! $request->filled('payment_amount')) {
            $request->merge(['payment_amount' => $request->input('amount')]);
        }
        if ($request->input('payment_method') === 'transfer') {
            $request->merge(['payment_method' => 'transfer_bank']);
        }

        $validator = Validator::make($request->all(), [
            'payment_amount' => ['required', 'numeric', 'min:1', 'max:'.$remaining],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:tunai,transfer,transfer_bank,qris,giro'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'payment_amount.required' => 'Nominal pembayaran wajib diisi.',
            'payment_amount.min' => 'Nominal pembayaran minimal Rp 1.',
            'payment_amount.max' => 'Nominal pembayaran tidak boleh melebihi sisa piutang (Rp '.number_format($remaining, 0, ',', '.').').',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'payment_method.required' => 'Pilih metode pembayaran.',
            'payment_method.in' => 'Metode pembayaran harus berupa tunai, transfer, transfer_bank, qris, atau giro.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi pembayaran gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $method = $request->input('payment_method');
        if ($method === 'transfer') {
            $method = 'transfer_bank';
        }

        $accountId = $request->input('account_id');
        if (! $accountId) {
            if (in_array($method, ['transfer_bank', 'transfer', 'qris'])) {
                $bankAcc = Account::where('name', 'like', '%bank%')->orWhere('name', 'like', '%bca%')->orWhere('name', 'like', '%mandiri%')->orWhere('name', 'like', '%bri%')->first();
                $accountId = $bankAcc?->id ?? Account::first()?->id;
            } else {
                $cashAcc = Account::where('name', 'like', '%kas%')->orWhere('name', 'like', '%tunai%')->first();
                $accountId = $cashAcc?->id ?? Account::first()?->id;
            }
        }

        $amount = (float) $request->input('payment_amount');

        $payment = DB::transaction(function () use ($invoice, $request, $amount, $accountId, $method) {
            $payment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth('api')->id(),
                'payment_number' => InvoicePayment::generatePaymentNumber(),
                'payment_date' => $request->input('payment_date'),
                'payment_method' => $method,
                'amount' => $amount,
                'reference_number' => $request->input('reference_number'),
                'notes' => $request->input('notes'),
            ]);

            AccountingService::recordInvoicePayment($payment, $accountId);

            $invoice->recalculateStatusAndBalance();

            return $payment;
        });

        $invoice->load(['payments.user', 'items.product.unitModel', 'store']);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran sebesar Rp '.number_format($amount, 0, ',', '.').' berhasil dicatat.',
            'data' => [
                'payment' => $payment,
                'invoice' => $invoice,
            ],
        ], 201);
    }

    /**
     * Delete a payment record.
     * Permission: faktur-pembayaran-delete
     */
    public function deletePayment(Invoice $invoice, InvoicePayment $payment): JsonResponse
    {
        if (! $this->canDeletePaymentRecord()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus pembayaran.',
            ], 403);
        }

        if ($payment->invoice_id !== $invoice->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran tidak terdaftar pada faktur ini.',
            ], 422);
        }

        DB::transaction(function () use ($payment, $invoice) {
            AccountingService::deleteInvoicePaymentRecords($payment);
            $payment->delete();
            $invoice->recalculateStatusAndBalance();
        });

        $invoice->load(['payments.user', 'items.product.unitModel', 'store']);

        return response()->json([
            'success' => true,
            'message' => 'Data pembayaran berhasil dihapus.',
            'data' => $invoice,
        ]);
    }

    /**
     * Reconcile consignment invoice items (sold, remaining, damaged, returned).
     * Permission: faktur-rekonsiliasi or assigned courier (or faktur-edit)
     */
    public function reconcile(Request $request, Invoice $invoice): JsonResponse
    {
        if (! $this->canReconcileInvoice($invoice)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk merekonsiliasi faktur.',
            ], 403);
        }

        if ($invoice->status === 'dibatalkan') {
            return response()->json([
                'success' => false,
                'message' => 'Faktur yang telah dibatalkan tidak dapat direkonsiliasi.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.remaining_quantity' => ['required', 'integer', 'min:0'],
            'items.*.damaged_quantity' => ['required', 'integer', 'min:0'],
            'items.*.returned_quantity' => ['required', 'integer', 'min:0'],
        ], [
            'items.required' => 'Daftar barang rekonsiliasi wajib diisi.',
            'items.min' => 'Daftar barang rekonsiliasi minimal 1 produk.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi rekonsiliasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $itemsInput = $request->input('items', []);

        // Validate each item against delivered_quantity
        $newSubtotalCalc = 0;
        foreach ($itemsInput as $rItem) {
            $itemModel = InvoiceItem::where('invoice_id', $invoice->id)->find($rItem['id']);
            if (! $itemModel) {
                return response()->json([
                    'success' => false,
                    'message' => "Item dengan ID {$rItem['id']} tidak ditemukan pada faktur ini.",
                ], 422);
            }

            $delivered = $itemModel->delivered_quantity !== null && $itemModel->delivered_quantity > 0
                ? (int) $itemModel->delivered_quantity
                : (int) $itemModel->quantity;

            $remaining = (int) $rItem['remaining_quantity'];
            $damaged = (int) $rItem['damaged_quantity'];
            $returned = (int) $rItem['returned_quantity'];
            $totalOut = $remaining + $damaged + $returned;

            if ($totalOut > $delivered) {
                return response()->json([
                    'success' => false,
                    'message' => "Total sisa, rusak, dan retur ({$totalOut}) melebihi jumlah terkirim ({$delivered}) untuk produk {$itemModel->product?->name}.",
                ], 422);
            }

            $sold = max(0, $delivered - $remaining - $damaged - $returned);
            $newSubtotalCalc += $sold * (float) $itemModel->unit_price;
        }

        $newTotalCalc = max(0.0, $newSubtotalCalc - (float) $invoice->discount);
        if ((float) $invoice->paid_amount > $newTotalCalc) {
            return response()->json([
                'success' => false,
                'message' => 'Total tagihan hasil rekonsiliasi (Rp '.number_format($newTotalCalc, 0, ',', '.').') tidak boleh lebih kecil dari pembayaran yang sudah diterima (Rp '.number_format((float) $invoice->paid_amount, 0, ',', '.').').',
            ], 422);
        }

        DB::transaction(function () use ($invoice, $itemsInput, $newSubtotalCalc, $newTotalCalc) {
            foreach ($itemsInput as $rItem) {
                $itemModel = InvoiceItem::where('invoice_id', $invoice->id)->find($rItem['id']);
                if ($itemModel) {
                    $oldReturned = (int) ($itemModel->returned_quantity ?? 0);
                    $newReturned = (int) $rItem['returned_quantity'];
                    $deltaReturned = $newReturned - $oldReturned;

                    if ($deltaReturned != 0) {
                        Product::where('id', $itemModel->product_id)->increment('stock_ready', $deltaReturned);
                    }

                    $delivered = $itemModel->delivered_quantity !== null && $itemModel->delivered_quantity > 0
                        ? (int) $itemModel->delivered_quantity
                        : (int) $itemModel->quantity;

                    $remaining = (int) $rItem['remaining_quantity'];
                    $damaged = (int) $rItem['damaged_quantity'];
                    $sold = max(0, $delivered - $remaining - $damaged - $newReturned);

                    $itemModel->update([
                        'delivered_quantity' => $delivered,
                        'remaining_quantity' => $remaining,
                        'damaged_quantity' => $damaged,
                        'returned_quantity' => $newReturned,
                        'quantity' => $sold,
                        'subtotal' => $sold * (float) $itemModel->unit_price,
                    ]);
                }
            }

            $paid = (float) $invoice->paid_amount;
            $newRemaining = max(0.0, $newTotalCalc - $paid);
            $newStatus = $invoice->status;
            if ($newStatus !== 'dibatalkan') {
                if ($newRemaining <= 0 && $newTotalCalc > 0) {
                    $newStatus = 'lunas';
                } elseif ($paid > 0) {
                    $newStatus = 'sebagian';
                } else {
                    $newStatus = 'belum_dibayar';
                }
            }

            $invoice->update([
                'subtotal' => $newSubtotalCalc,
                'total_amount' => $newTotalCalc,
                'remaining_balance' => $newRemaining,
                'status' => $newStatus,
            ]);

            AccountingService::syncInvoiceAccounting($invoice);
        });

        $invoice->load(['payments.user', 'items.product.unitModel', 'store', 'courier']);

        return response()->json([
            'success' => true,
            'message' => 'Rekonsiliasi produk dan penyesuaian tagihan berhasil disimpan.',
            'data' => $invoice,
        ]);
    }
}
