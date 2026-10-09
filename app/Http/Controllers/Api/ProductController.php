<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryItem;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Get paginated list of products (Master Produk Jadi) with search & filters.
     * Dilindungi dengan autentikasi auth:api dan permission produk-view.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
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
                'consignment_price_formatted' => 'Rp '.number_format($prod->consignment_price, 0, ',', '.'),
                'retail_price' => (float) $prod->retail_price,
                'retail_price_formatted' => 'Rp '.number_format($prod->retail_price, 0, ',', '.'),
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
        /** @var User|null $user */
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
        /** @var User|null $user */
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
                'consignment_price_formatted' => 'Rp '.number_format($product->consignment_price, 0, ',', '.'),
                'retail_price' => (float) $product->retail_price,
                'retail_price_formatted' => 'Rp '.number_format($product->retail_price, 0, ',', '.'),
                'stock_ready' => (int) $product->stock_ready,
                'description' => $product->description ?? '-',
                'is_active' => (bool) $product->is_active,
                'photo_url' => $product->photo_url,
            ],
        ]);
    }

    /**
     * Create new product (Master Produk Jadi).
     * Dilindungi dengan autentikasi auth:api dan permission produk-create (sama persis dengan Web).
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        // Pastikan permission sama persis dengan web (produk-create)
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('produk-create', 'web') && ! $user->can('produk-create'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menambahkan produk baru.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150', 'unique:products,name'],
            'unit_id' => ['required', 'exists:units,id'],
            'consignment_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'stock_ready' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable'],
            'photo_data' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (! empty($value)) {
                        $error = Product::validatePhotoBase64($value);
                        if ($error) {
                            $fail($error);
                        }
                    }
                },
            ],
        ], [
            'name.required' => 'Nama produk kemasan wajib diisi.',
            'name.max' => 'Nama produk kemasan maksimal 150 karakter.',
            'name.unique' => 'Nama produk ini sudah terdaftar sebelumnya.',
            'unit_id.required' => 'Pilih satuan kemasan produk.',
            'unit_id.exists' => 'Satuan kemasan produk tidak valid.',
            'consignment_price.required' => 'Harga setor konsinyasi wajib diisi.',
            'consignment_price.numeric' => 'Harga setor konsinyasi harus berupa angka.',
            'consignment_price.min' => 'Harga setor konsinyasi tidak boleh bernilai negatif.',
            'retail_price.required' => 'Harga eceran toko rekomendasi wajib diisi.',
            'retail_price.numeric' => 'Harga eceran toko rekomendasi harus berupa angka.',
            'retail_price.min' => 'Harga eceran toko rekomendasi tidak boleh bernilai negatif.',
            'stock_ready.required' => 'Stok awal barang jadi wajib diisi.',
            'stock_ready.integer' => 'Stok awal barang jadi harus berupa bilangan bulat.',
            'stock_ready.min' => 'Stok awal barang jadi tidak boleh bernilai negatif.',
            'description.max' => 'Deskripsi produk maksimal 500 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data produk gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $unit = Unit::find($validated['unit_id']);

        $isActive = true;
        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $product = Product::create([
            'name' => trim($validated['name']),
            'unit_id' => $validated['unit_id'],
            'unit' => $unit?->short_name ?? 'pcs',
            'consignment_price' => $validated['consignment_price'],
            'retail_price' => $validated['retail_price'],
            'stock_ready' => $validated['stock_ready'],
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_active' => $isActive,
        ]);

        // Penanganan Foto Produk (Mendukung Base64 DataURL atau Multipart File Upload)
        $photoData = $request->input('photo_data');
        if (empty($photoData) && is_string($request->input('photo'))) {
            $photoData = $request->input('photo');
        }

        if (! empty($photoData) && str_starts_with($photoData, 'data:image/')) {
            $product->updatePhotoFromBase64($photoData);
        } elseif ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if ($file->isValid()) {
                $date = now()->format('Y-m-d');
                $productSlug = Str::slug($product->name ?: 'produk');
                $randomCode = Str::lower(Str::random(8));
                $ext = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = "{$date}_{$productSlug}_{$randomCode}.{$ext}";
                $path = 'foto-produk/'.$filename;

                Storage::disk('public')->putFileAs('foto-produk', $file, $filename);

                $product->photo = $path;
                $product->save();
            }
        }

        $product->refresh();
        $product->load(['unitModel']);

        return response()->json([
            'success' => true,
            'message' => "Produk kemasan '{$product->name}' berhasil ditambahkan ke katalog.",
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'unit_id' => $product->unit_id,
                'unit_name' => $product->unitModel?->name ?? $product->unit,
                'unit_short' => $product->unitModel?->short_name ?? $product->unit,
                'consignment_price' => (float) $product->consignment_price,
                'consignment_price_formatted' => 'Rp '.number_format($product->consignment_price, 0, ',', '.'),
                'retail_price' => (float) $product->retail_price,
                'retail_price_formatted' => 'Rp '.number_format($product->retail_price, 0, ',', '.'),
                'stock_ready' => (int) $product->stock_ready,
                'description' => $product->description ?? '-',
                'is_active' => (bool) $product->is_active,
                'photo_url' => $product->photo_url,
            ],
        ], 201);
    }

    /**
     * Update existing product (Master Produk Jadi).
     * Dilindungi dengan autentikasi auth:api dan permission produk-edit (sama persis dengan Web).
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        // Pastikan permission sama persis dengan web (produk-edit)
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('produk-edit', 'web') && ! $user->can('produk-edit'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengubah data produk.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150', 'unique:products,name,'.$product->id],
            'unit_id' => ['required', 'exists:units,id'],
            'consignment_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'stock_ready' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable'],
            'photo_data' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (! empty($value) && $value !== 'DELETE') {
                        $error = Product::validatePhotoBase64($value);
                        if ($error) {
                            $fail($error);
                        }
                    }
                },
            ],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama produk kemasan wajib diisi.',
            'name.max' => 'Nama produk kemasan maksimal 150 karakter.',
            'name.unique' => 'Nama produk ini sudah terdaftar sebelumnya.',
            'unit_id.required' => 'Pilih satuan kemasan produk.',
            'unit_id.exists' => 'Satuan kemasan produk tidak valid.',
            'consignment_price.required' => 'Harga setor konsinyasi wajib diisi.',
            'consignment_price.numeric' => 'Harga setor konsinyasi harus berupa angka.',
            'consignment_price.min' => 'Harga setor konsinyasi tidak boleh bernilai negatif.',
            'retail_price.required' => 'Harga eceran toko rekomendasi wajib diisi.',
            'retail_price.numeric' => 'Harga eceran toko rekomendasi harus berupa angka.',
            'retail_price.min' => 'Harga eceran toko rekomendasi tidak boleh bernilai negatif.',
            'stock_ready.required' => 'Stok barang jadi wajib diisi.',
            'stock_ready.integer' => 'Stok barang jadi harus berupa bilangan bulat.',
            'stock_ready.min' => 'Stok barang jadi tidak boleh bernilai negatif.',
            'description.max' => 'Deskripsi produk maksimal 500 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data produk gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $unit = Unit::find($validated['unit_id']);

        $isActive = $product->is_active;
        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $product->update([
            'name' => trim($validated['name']),
            'unit_id' => $validated['unit_id'],
            'unit' => $unit?->short_name ?? $product->unit,
            'consignment_price' => $validated['consignment_price'],
            'retail_price' => $validated['retail_price'],
            'stock_ready' => $validated['stock_ready'],
            'description' => ! empty($validated['description']) ? trim($validated['description']) : null,
            'is_active' => $isActive,
        ]);

        // Penanganan Foto Produk: Hapus, Ganti Base64, atau Multipart
        $photoData = $request->input('photo_data');
        if (empty($photoData) && is_string($request->input('photo'))) {
            $photoData = $request->input('photo');
        }

        $shouldRemovePhoto = filter_var($request->input('remove_photo'), FILTER_VALIDATE_BOOLEAN) || $photoData === 'DELETE';

        if ($shouldRemovePhoto) {
            if ($product->photo && Storage::disk('public')->exists($product->photo)) {
                Storage::disk('public')->delete($product->photo);
            }
            $product->photo = null;
            $product->save();
        } elseif (! empty($photoData) && str_starts_with($photoData, 'data:image/')) {
            $product->updatePhotoFromBase64($photoData);
        } elseif ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if ($file->isValid()) {
                if ($product->photo && Storage::disk('public')->exists($product->photo)) {
                    Storage::disk('public')->delete($product->photo);
                }

                $date = now()->format('Y-m-d');
                $productSlug = Str::slug($product->name ?: 'produk');
                $randomCode = Str::lower(Str::random(8));
                $ext = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = "{$date}_{$productSlug}_{$randomCode}.{$ext}";
                $path = 'foto-produk/'.$filename;

                Storage::disk('public')->putFileAs('foto-produk', $file, $filename);

                $product->photo = $path;
                $product->save();
            }
        }

        $product->refresh();
        $product->load(['unitModel']);

        return response()->json([
            'success' => true,
            'message' => "Data produk '{$product->name}' berhasil diperbarui.",
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'unit_id' => $product->unit_id,
                'unit_name' => $product->unitModel?->name ?? $product->unit,
                'unit_short' => $product->unitModel?->short_name ?? $product->unit,
                'consignment_price' => (float) $product->consignment_price,
                'consignment_price_formatted' => 'Rp '.number_format($product->consignment_price, 0, ',', '.'),
                'retail_price' => (float) $product->retail_price,
                'retail_price_formatted' => 'Rp '.number_format($product->retail_price, 0, ',', '.'),
                'stock_ready' => (int) $product->stock_ready,
                'description' => $product->description ?? '-',
                'is_active' => (bool) $product->is_active,
                'photo_url' => $product->photo_url,
            ],
        ]);
    }

    /**
     * Delete product by ID.
     * Dilindungi dengan autentikasi auth:api dan permission produk-delete (sama persis dengan Web).
     */
    public function destroy(Product $product): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        // Pastikan permission sama dengan web (produk-delete)
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('produk-delete', 'web') && ! $user->can('produk-delete'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus produk.',
            ], 403);
        }

        // Safety check: verifikasi jika produk digunakan pada formula resep, riwayat produksi, surat jalan, atau faktur
        $hasRecipes = $product->recipes()->exists();
        $hasBatches = $product->productionBatches()->exists();
        $hasDeliveries = DeliveryItem::where('product_id', $product->id)->exists();
        $hasInvoices = InvoiceItem::where('product_id', $product->id)->exists();

        if ($hasRecipes || $hasBatches || $hasDeliveries || $hasInvoices) {
            return response()->json([
                'success' => false,
                'message' => "Produk '{$product->name}' tidak dapat dihapus karena sudah memiliki data formula resep, riwayat produksi, surat jalan, atau faktur tagihan. Silakan nonaktifkan status produk sebagai gantinya.",
            ], 422);
        }

        if ($product->photo && Storage::disk('public')->exists($product->photo)) {
            Storage::disk('public')->delete($product->photo);
        }

        $productName = $product->name;
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => "Produk '{$productName}' berhasil dihapus.",
        ]);
    }
}
