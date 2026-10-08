<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DeliveryController extends Controller
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
     * Get paginated list of deliveries with filters and status counts.
     * Permission: pengantaran-view
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data surat jalan pengantaran.',
            ], 403);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        $isCourier = $user->hasRole('kurir') && ! $user->hasAnyRole(['dev', 'manager']);

        $query = Delivery::with(['store', 'courier', 'creator', 'items.product'])->forUser($user);

        // Search query across delivery_number, store name, owner name, address, courier name, recipient name, notes
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('delivery_number', 'like', "%{$searchTerm}%")
                    ->orWhere('recipient_name', 'like', "%{$searchTerm}%")
                    ->orWhere('recipient_phone', 'like', "%{$searchTerm}%")
                    ->orWhere('notes', 'like', "%{$searchTerm}%")
                    ->orWhereHas('store', function ($sq) use ($searchTerm) {
                        $sq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('owner_name', 'like', "%{$searchTerm}%")
                            ->orWhere('address', 'like', "%{$searchTerm}%")
                            ->orWhere('route', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('courier', function ($cq) use ($searchTerm) {
                        $cq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('phone', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Status Filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Route Filter
        if ($request->filled('route') && $request->input('route') !== 'all') {
            $query->whereHas('store', function ($sq) use ($request) {
                $sq->where('route', $request->input('route'));
            });
        }

        // Date Filter
        if ($request->filled('delivery_date')) {
            $query->whereDate('delivery_date', $request->input('delivery_date'));
        }

        // Courier Filter (only for dev/manager, kurir is strictly scoped to their own tasks)
        if (! $isCourier && $request->filled('courier_id')) {
            $query->where('courier_id', $request->input('courier_id'));
        }

        // Status counts for badge tabs (scoped to user's accessible deliveries)
        $statusCounts = [
            'all' => (clone $query)->withoutGlobalScopes()->count(),
            'diproses' => Delivery::forUser($user)->where('status', 'diproses')->count(),
            'dikirim' => Delivery::forUser($user)->where('status', 'dikirim')->count(),
            'selesai' => Delivery::forUser($user)->where('status', 'selesai')->count(),
            'dibatalkan' => Delivery::forUser($user)->where('status', 'dibatalkan')->count(),
        ];

        // Ordering: latest delivery_date and id first
        $query->orderByDesc('delivery_date')->orderByDesc('id');

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $deliveries = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar surat jalan berhasil dimuat.',
            'data' => $deliveries->items(),
            'pagination' => [
                'current_page' => $deliveries->currentPage(),
                'last_page' => $deliveries->lastPage(),
                'per_page' => $deliveries->perPage(),
                'total' => $deliveries->total(),
            ],
            'status_counts' => $statusCounts,
            'routes' => Store::whereNotNull('route')
                ->where('route', '!=', '')
                ->distinct()
                ->orderBy('route')
                ->pluck('route')
                ->values(),
            'current_user_id' => $user->id,
            'is_courier' => $isCourier,
        ]);
    }

    /**
     * Get options required for creating / editing deliveries:
     * Stores, products with stock_ready, couriers, routes, and auto-generated delivery number.
     * Permission: pengantaran-view
     */
    public function options(): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses.',
            ], 403);
        }

        $stores = Store::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'owner_name', 'phone', 'address', 'latitude', 'longitude', 'route']);

        $products = Product::where('is_active', true)
            ->with('unitModel')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'unit_id', 'stock_ready', 'consignment_price', 'retail_price', 'photo']);

        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();
        $isCourier = $user && $user->hasRole('kurir') && ! $user->hasAnyRole(['dev', 'manager']);

        $couriers = $isCourier
            ? User::where('id', $user->id)->get(['id', 'name', 'phone', 'email'])
            : User::role('kurir')->orderBy('name')->get(['id', 'name', 'phone', 'email']);
        if ($couriers->isEmpty()) {
            $couriers = User::orderBy('name')->get(['id', 'name', 'phone', 'email']);
        }

        $routes = Store::whereNotNull('route')
            ->where('route', '!=', '')
            ->distinct()
            ->orderBy('route')
            ->pluck('route')
            ->values();

        $nextDeliveryNumber = Delivery::generateDeliveryNumber();

        return response()->json([
            'success' => true,
            'message' => 'Opsi formulir surat jalan berhasil dimuat.',
            'data' => [
                'stores' => $stores,
                'products' => $products,
                'couriers' => $couriers,
                'routes' => $routes,
                'next_delivery_number' => $nextDeliveryNumber,
            ],
        ]);
    }

    /**
     * Create a new delivery (Surat Jalan).
     * Permission: pengantaran-create
     */
    public function store(Request $request): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-create')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membuat surat jalan pengantaran.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'delivery_number' => ['nullable', 'string', 'max:50', 'unique:deliveries,delivery_number'],
            'store_id' => ['required', 'exists:stores,id'],
            'courier_id' => ['nullable', 'exists:users,id'],
            'delivery_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'store_id.required' => 'Pilih toko mitra tujuan pengantaran.',
            'store_id.exists' => 'Toko mitra yang dipilih tidak valid.',
            'delivery_date.required' => 'Tanggal pengantaran wajib diisi.',
            'items.required' => 'Daftar muatan barang wajib diisi minimal 1 jenis produk.',
            'items.min' => 'Daftar muatan barang wajib diisi minimal 1 jenis produk.',
            'items.*.product_id.required' => 'Pilih produk untuk setiap baris.',
            'items.*.product_id.distinct' => 'Produk tidak boleh ganda pada surat jalan yang sama.',
            'items.*.quantity.min' => 'Jumlah barang minimal 1.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Silakan periksa kembali data yang dimasukkan.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $items = $request->input('items', []);

        // Validate stock sufficiency for each product
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $qty = (int) $item['quantity'];

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.',
                ], 422);
            }

            if ($qty > $product->stock_ready) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok produk '{$product->name}' tidak mencukupi (Tersedia ready di gudang: {$product->stock_ready} {$product->unit}).",
                    'errors' => [
                        'items' => ["Stok {$product->name} tidak mencukupi (sisa: {$product->stock_ready})."],
                    ],
                ], 422);
            }
        }

        $deliveryNumber = $request->filled('delivery_number')
            ? trim($request->input('delivery_number'))
            : Delivery::generateDeliveryNumber();

        // Calculate totals
        $totalItems = 0;
        $totalAmount = 0.0;

        foreach ($items as &$item) {
            $product = Product::find($item['product_id']);
            $qty = (int) $item['quantity'];
            $price = isset($item['unit_price']) && is_numeric($item['unit_price'])
                ? (float) $item['unit_price']
                : (float) $product->consignment_price;

            $item['quantity'] = $qty;
            $item['unit_price'] = $price;
            $item['subtotal'] = $qty * $price;

            $totalItems += $qty;
            $totalAmount += $item['subtotal'];
        }
        unset($item);

        /** @var \App\Models\User $currentUser */
        $currentUser = auth('api')->user();
        $isCourier = $currentUser->hasRole('kurir') && ! $currentUser->hasAnyRole(['dev', 'manager']);

        // Courier assignment: If user is courier, force courier_id to themselves
        $courierId = $isCourier ? $currentUser->id : $request->input('courier_id');

        $delivery = DB::transaction(function () use ($deliveryNumber, $request, $currentUser, $courierId, $totalItems, $totalAmount, $items) {
            $delivery = Delivery::create([
                'delivery_number' => $deliveryNumber,
                'store_id' => $request->input('store_id'),
                'courier_id' => $courierId,
                'created_by' => $currentUser->id,
                'delivery_date' => $request->input('delivery_date'),
                'status' => 'diproses',
                'notes' => $request->input('notes'),
                'total_items' => $totalItems,
                'total_amount' => $totalAmount,
            ]);

            foreach ($items as $item) {
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Decrement warehouse ready stock
                Product::where('id', $item['product_id'])->decrement('stock_ready', $item['quantity']);
            }

            return $delivery;
        });

        $delivery->load(['store', 'courier', 'creator', 'items.product.unitModel']);

        return response()->json([
            'success' => true,
            'message' => "Surat jalan {$delivery->delivery_number} berhasil dibuat.",
            'data' => $delivery,
        ], 201);
    }

    /**
     * Show delivery detail.
     * Permission: pengantaran-view
     */
    public function show(Delivery $delivery): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-view')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat rincian surat jalan pengantaran.',
            ], 403);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        if (! $delivery->isAccessibleBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat surat jalan milik kurir lain.',
            ], 403);
        }

        $delivery->load(['store', 'courier', 'creator', 'items.product.unitModel', 'invoice.items.product']);

        return response()->json([
            'success' => true,
            'message' => 'Rincian surat jalan berhasil dimuat.',
            'data' => $delivery,
        ]);
    }

    /**
     * Update an existing delivery (only allowed in 'diproses' status).
     * Permission: pengantaran-edit
     */
    public function update(Request $request, Delivery $delivery): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengubah surat jalan.',
            ], 403);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        if (! $delivery->isAccessibleBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengubah surat jalan milik kurir lain.',
            ], 403);
        }

        if (! $delivery->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'Surat jalan yang sedang dikirim, telah selesai, atau telah dibatalkan tidak dapat diubah.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'delivery_number' => ['required', 'string', 'max:50', 'unique:deliveries,delivery_number,' . $delivery->id],
            'store_id' => ['required', 'exists:stores,id'],
            'courier_id' => ['nullable', 'exists:users,id'],
            'delivery_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'store_id.required' => 'Pilih toko mitra tujuan pengantaran.',
            'delivery_date.required' => 'Tanggal pengantaran wajib diisi.',
            'items.required' => 'Daftar muatan barang wajib diisi minimal 1 jenis produk.',
            'items.*.product_id.required' => 'Pilih produk untuk setiap baris.',
            'items.*.product_id.distinct' => 'Produk tidak boleh ganda pada surat jalan yang sama.',
            'items.*.quantity.min' => 'Jumlah barang minimal 1.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Silakan periksa kembali data yang dimasukkan.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $delivery->load('items');
        $items = $request->input('items', []);

        // Validate stock sufficiency considering currently reserved stock
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $qty = (int) $item['quantity'];

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.',
                ], 422);
            }

            $prevItem = $delivery->items->firstWhere('product_id', $item['product_id']);
            $currentAvailable = (int) $product->stock_ready + ($prevItem ? (int) $prevItem->quantity : 0);

            if ($qty > $currentAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok produk '{$product->name}' tidak mencukupi (Tersedia: {$currentAvailable} {$product->unit}).",
                    'errors' => [
                        'items' => ["Stok {$product->name} tidak mencukupi (tersedia: {$currentAvailable})."],
                    ],
                ], 422);
            }
        }

        // Calculate totals
        $totalItems = 0;
        $totalAmount = 0.0;

        foreach ($items as &$item) {
            $product = Product::find($item['product_id']);
            $qty = (int) $item['quantity'];
            $price = isset($item['unit_price']) && is_numeric($item['unit_price'])
                ? (float) $item['unit_price']
                : (float) $product->consignment_price;

            $item['quantity'] = $qty;
            $item['unit_price'] = $price;
            $item['subtotal'] = $qty * $price;

            $totalItems += $qty;
            $totalAmount += $item['subtotal'];
        }
        unset($item);

        $isCourier = $user->hasRole('kurir') && ! $user->hasAnyRole(['dev', 'manager']);
        $courierId = $isCourier ? $delivery->courier_id : $request->input('courier_id');

        DB::transaction(function () use ($delivery, $request, $items, $totalItems, $totalAmount, $courierId) {
            // 1. Restore previous reserved stock
            foreach ($delivery->items as $oldItem) {
                Product::where('id', $oldItem->product_id)->increment('stock_ready', $oldItem->quantity);
            }

            // 2. Delete old items
            $delivery->items()->delete();

            // 3. Create new items and decrement stock
            foreach ($items as $item) {
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                Product::where('id', $item['product_id'])->decrement('stock_ready', $item['quantity']);
            }

            // 4. Update delivery metadata
            $delivery->update([
                'delivery_number' => $request->input('delivery_number'),
                'store_id' => $request->input('store_id'),
                'courier_id' => $courierId,
                'delivery_date' => $request->input('delivery_date'),
                'notes' => $request->input('notes'),
                'total_items' => $totalItems,
                'total_amount' => $totalAmount,
            ]);
        });

        $delivery->refresh();
        $delivery->load(['store', 'courier', 'creator', 'items.product.unitModel']);

        return response()->json([
            'success' => true,
            'message' => "Surat jalan {$delivery->delivery_number} berhasil diperbarui.",
            'data' => $delivery,
        ]);
    }

    /**
     * Dispatch delivery (Change status from 'diproses' to 'dikirim').
     * Quick action for couriers / managers when leaving the warehouse.
     * Permission: pengantaran-status / pengantaran-edit
     */
    public function dispatchDelivery(Delivery $delivery): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-status') && ! $this->checkPermission('pengantaran-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengubah status pengantaran.',
            ], 403);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        if (! $delivery->isAccessibleBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk memberangkatkan surat jalan milik kurir lain.',
            ], 403);
        }

        if ($delivery->status !== 'diproses') {
            return response()->json([
                'success' => false,
                'message' => 'Surat jalan tidak dapat diberangkatkan karena status bukan menunggu pengambilan.',
            ], 422);
        }

        $delivery->update([
            'status' => 'dikirim',
            'dispatched_at' => now(),
        ]);

        $delivery->refresh();
        $delivery->load(['store', 'courier', 'creator', 'items.product.unitModel']);

        return response()->json([
            'success' => true,
            'message' => "Surat jalan {$delivery->delivery_number} kini dalam perjalanan (Sedang Dikirim).",
            'data' => $delivery,
        ]);
    }

    /**
     * Complete delivery handover at store (Change status from 'dikirim' to 'selesai').
     * Generates invoice automatically.
     * Permission: pengantaran-status / pengantaran-edit
     */
    public function completeDelivery(Request $request, Delivery $delivery): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-status') && ! $this->checkPermission('pengantaran-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menyelesaikan pengantaran.',
            ], 403);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        if (! $delivery->isAccessibleBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menyelesaikan surat jalan milik kurir lain.',
            ], 403);
        }

        if ($delivery->status !== 'dikirim') {
            return response()->json([
                'success' => false,
                'message' => 'Pengantaran hanya dapat diselesaikan jika surat jalan dalam status sedang dikirim.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'recipient_name' => ['required', 'string', 'max:100'],
            'recipient_role' => ['nullable', 'string', 'max:100'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'handover_notes' => ['nullable', 'string', 'max:500'],
            'proof_photo' => ['nullable', 'image', 'max:5120'],
            'photo_data' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    $error = Delivery::validatePhotoBase64($value);
                    if ($error) {
                        $fail($error);
                    }
                },
            ],
            'signature_data' => ['nullable', 'string'],
        ], [
            'recipient_name.required' => 'Nama penerima / staf toko wajib diisi sebagai bukti serah terima.',
            'proof_photo.image' => 'File bukti serah terima harus berupa format foto/gambar.',
            'proof_photo.max' => 'Ukuran foto bukti tidak boleh melebihi 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Silakan periksa kembali data serah terima.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Process proof photo
        $proofPath = $delivery->proof_image;

        if ($request->filled('photo_data')) {
            $delivery->updateProofPhotoFromBase64($request->input('photo_data'));
            $proofPath = $delivery->proof_image;
        } elseif ($request->hasFile('proof_photo')) {
            if ($delivery->proof_image && Storage::disk('public')->exists($delivery->proof_image)) {
                Storage::disk('public')->delete($delivery->proof_image);
            }
            $proofPath = $request->file('proof_photo')->store('delivery-proofs', 'public');
        }

        // Handle notes appending
        $existingNotes = $delivery->notes ? rtrim($delivery->notes) : '';
        $handoverNotes = $request->input('handover_notes');
        $updatedNotes = $existingNotes;
        if (! empty($handoverNotes)) {
            $updatedNotes = $existingNotes
                ? "{$existingNotes}\n[Serah Terima]: {$handoverNotes}"
                : "[Serah Terima]: {$handoverNotes}";
        }

        $recipientPhone = $request->input('recipient_phone');
        $cleanPhone = null;
        if (! empty($recipientPhone)) {
            $digits = preg_replace('/\D/', '', $recipientPhone);
            $cleanPhone = str_starts_with($digits, '62') ? $digits : '62' . $digits;
        }

        $invoice = DB::transaction(function () use ($delivery, $request, $proofPath, $updatedNotes, $cleanPhone) {
            $delivery->update([
                'status' => 'selesai',
                'delivered_at' => now(),
                'recipient_name' => $request->input('recipient_name'),
                'recipient_role' => $request->input('recipient_role') ?: null,
                'recipient_phone' => $cleanPhone,
                'proof_image' => $proofPath,
                'signature_data' => $request->input('signature_data') ?: $delivery->signature_data,
                'notes' => $updatedNotes,
            ]);

            return $delivery->generateInvoice();
        });

        $delivery->refresh();
        $delivery->load(['store', 'courier', 'creator', 'items.product.unitModel', 'invoice']);

        $invoiceMsg = $invoice ? " Faktur piutang ({$invoice->invoice_number}) otomatis diterbitkan." : "";

        return response()->json([
            'success' => true,
            'message' => "Pengantaran selesai! Barang telah diterima oleh {$delivery->recipient_name}.{$invoiceMsg}",
            'data' => $delivery,
        ]);
    }

    /**
     * Cancel delivery and restore stock to warehouse.
     * Permission: pengantaran-delete / pengantaran-edit
     */
    public function cancelDelivery(Delivery $delivery): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-delete') && ! $this->checkPermission('pengantaran-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membatalkan surat jalan.',
            ], 403);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        if (! $delivery->isAccessibleBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membatalkan surat jalan milik kurir lain.',
            ], 403);
        }

        if ($delivery->status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Surat jalan yang sudah selesai tidak dapat dibatalkan.',
            ], 422);
        }

        if ($delivery->status === 'dibatalkan') {
            return response()->json([
                'success' => false,
                'message' => 'Surat jalan ini sudah dalam status dibatalkan.',
            ], 422);
        }

        $delivery->load('items');

        DB::transaction(function () use ($delivery) {
            // Restore ready stock for each product
            foreach ($delivery->items as $item) {
                Product::where('id', $item->product_id)->increment('stock_ready', $item->quantity);
            }

            $delivery->update(['status' => 'dibatalkan']);
        });

        $delivery->refresh();
        $delivery->load(['store', 'courier', 'creator', 'items.product.unitModel']);

        return response()->json([
            'success' => true,
            'message' => "Surat jalan {$delivery->delivery_number} berhasil dibatalkan dan stok dikembalikan ke gudang.",
            'data' => $delivery,
        ]);
    }

    /**
     * Delete delivery (and restore stock if not already cancelled).
     * Selesai status CANNOT be deleted.
     * Permission: pengantaran-delete
     */
    public function destroy(Delivery $delivery): JsonResponse
    {
        if (! $this->checkPermission('pengantaran-delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus surat jalan.',
            ], 403);
        }

        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        if (! $delivery->isAccessibleBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus surat jalan milik kurir lain.',
            ], 403);
        }

        if ($delivery->status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Surat jalan yang telah selesai serah terima tidak boleh dihapus demi integritas data riwayat.',
            ], 422);
        }

        $deliveryNumber = $delivery->delivery_number;
        $delivery->load('items');

        DB::transaction(function () use ($delivery) {
            // Restore ready stock if delivery was not already cancelled
            if ($delivery->status !== 'dibatalkan') {
                foreach ($delivery->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock_ready', $item->quantity);
                }
            }

            $delivery->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Surat jalan {$deliveryNumber} berhasil dihapus.",
        ]);
    }
}
