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

        // Pastikan permission sama dengan web (toko-view) atau memiliki izin transaksi terkait
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('toko-view', 'web') && ! $user->can('toko-view') && ! $user->can('faktur-create') && ! $user->can('pengantaran-create'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat data toko mitra.',
            ], 403);
        }

        $query = Store::query();

        // Filter pencarian berdasarkan nama toko, pemilik, alamat, atau rute
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('owner_name', 'like', "%{$searchTerm}%")
                    ->orWhere('address', 'like', "%{$searchTerm}%")
                    ->orWhere('route', 'like', "%{$searchTerm}%");
            });
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

    /**
     * Create new store (Toko Mitra).
     * Dilindungi dengan autentikasi auth:api dan permission toko-create.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        // Pastikan permission sama dengan web (toko-create)
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('toko-create', 'web') && ! $user->can('toko-create'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menambahkan data toko mitra baru.',
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'route' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable'],
            'photo_data' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (! empty($value) && $value !== 'DELETE') {
                        $error = Store::validatePhotoBase64($value);
                        if ($error) {
                            $fail($error);
                        }
                    }
                },
            ],
        ], [
            'name.required' => 'Nama toko mitra wajib diisi.',
            'name.max' => 'Nama toko mitra maksimal 255 karakter.',
            'latitude.numeric' => 'Latitude harus berupa format angka koordinat.',
            'latitude.between' => 'Latitude harus berada dalam rentang -90 hingga 90.',
            'longitude.numeric' => 'Longitude harus berupa format angka koordinat.',
            'longitude.between' => 'Longitude harus berada dalam rentang -180 hingga 180.',
            'phone.max' => 'Nomor kontak maksimal 50 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data toko mitra gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Normalisasi nomor telepon ke format 62... jika diisi
        $normalizedPhone = null;
        if (! empty($validated['phone'])) {
            $digits = preg_replace('/\D/', '', $validated['phone']);
            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            } elseif (str_starts_with($digits, '62')) {
                $digits = substr($digits, 2);
            }
            $normalizedPhone = ! empty($digits) ? '62' . $digits : null;
        }

        $isActive = true;
        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $newStore = Store::create([
            'name' => trim($validated['name']),
            'owner_name' => ! empty($validated['owner_name']) ? trim($validated['owner_name']) : null,
            'phone' => $normalizedPhone,
            'address' => ! empty($validated['address']) ? trim($validated['address']) : null,
            'latitude' => isset($validated['latitude']) && $validated['latitude'] !== '' && $validated['latitude'] !== null ? (float) $validated['latitude'] : null,
            'longitude' => isset($validated['longitude']) && $validated['longitude'] !== '' && $validated['longitude'] !== null ? (float) $validated['longitude'] : null,
            'route' => ! empty($validated['route']) ? trim($validated['route']) : null,
            'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            'is_active' => $isActive,
        ]);

        // Penanganan Foto Toko (Mendukung Multipart File Upload atau Base64 DataURL)
        $photoData = $request->input('photo_data');
        if (empty($photoData) && is_string($request->input('photo'))) {
            $photoData = $request->input('photo');
        }

        if (! empty($photoData) && str_starts_with($photoData, 'data:image/')) {
            $newStore->updatePhotoFromBase64($photoData);
        } elseif ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if ($file->isValid()) {
                $date = now()->format('Y-m-d');
                $storeSlug = \Illuminate\Support\Str::slug($newStore->name ?: 'toko');
                $randomCode = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
                $ext = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = "{$date}_{$storeSlug}_{$randomCode}.{$ext}";
                $path = 'foto-toko/' . $filename;

                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('foto-toko', $file, $filename);

                $newStore->photo = $path;
                $newStore->save();
            }
        }

        $newStore->refresh();

        return response()->json([
            'success' => true,
            'message' => "Toko mitra '{$newStore->name}' berhasil ditambahkan.",
            'data' => $newStore,
        ], 201);
    }

    /**
     * Update data toko mitra by ID.
     * Dilindungi dengan autentikasi auth:api dan permission toko-edit.
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        // Pastikan permission sama dengan web (toko-edit)
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('toko-edit', 'web') && ! $user->can('toko-edit'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengubah data toko mitra.',
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'route' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'photo' => ['nullable'],
            'photo_data' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (! empty($value) && $value !== 'DELETE') {
                        $error = Store::validatePhotoBase64($value);
                        if ($error) {
                            $fail($error);
                        }
                    }
                },
            ],
        ], [
            'name.required' => 'Nama toko mitra wajib diisi.',
            'name.max' => 'Nama toko mitra maksimal 255 karakter.',
            'latitude.numeric' => 'Latitude harus berupa format angka koordinat.',
            'latitude.between' => 'Latitude harus berada dalam rentang -90 hingga 90.',
            'longitude.numeric' => 'Longitude harus berupa format angka koordinat.',
            'longitude.between' => 'Longitude harus berada dalam rentang -180 hingga 180.',
            'phone.max' => 'Nomor kontak maksimal 50 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data toko mitra gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Normalisasi nomor telepon ke format 62... jika diisi
        $normalizedPhone = null;
        if (! empty($validated['phone'])) {
            $digits = preg_replace('/\D/', '', $validated['phone']);
            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            } elseif (str_starts_with($digits, '62')) {
                $digits = substr($digits, 2);
            }
            $normalizedPhone = ! empty($digits) ? '62' . $digits : null;
        }

        // Tentukan nilai status is_active (default tetap seperti semula jika tidak dikirim)
        $isActive = $store->is_active;
        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $store->update([
            'name' => trim($validated['name']),
            'owner_name' => ! empty($validated['owner_name']) ? trim($validated['owner_name']) : null,
            'phone' => $normalizedPhone,
            'address' => ! empty($validated['address']) ? trim($validated['address']) : null,
            'latitude' => isset($validated['latitude']) && $validated['latitude'] !== '' && $validated['latitude'] !== null ? (float) $validated['latitude'] : null,
            'longitude' => isset($validated['longitude']) && $validated['longitude'] !== '' && $validated['longitude'] !== null ? (float) $validated['longitude'] : null,
            'route' => ! empty($validated['route']) ? trim($validated['route']) : null,
            'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            'is_active' => $isActive,
        ]);

        // Penanganan Foto Toko (Mendukung Multipart File Upload, Base64 DataURL, dan DELETE)
        $photoData = $request->input('photo_data');
        if (empty($photoData) && is_string($request->input('photo'))) {
            $photoData = $request->input('photo');
        }

        if ($photoData === 'DELETE') {
            if ($store->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($store->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($store->photo);
            }
            $store->photo = null;
            $store->save();
        } elseif (! empty($photoData) && str_starts_with($photoData, 'data:image/')) {
            $store->updatePhotoFromBase64($photoData);
        } elseif ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if ($file->isValid()) {
                if ($store->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($store->photo)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($store->photo);
                }

                $date = now()->format('Y-m-d');
                $storeSlug = \Illuminate\Support\Str::slug($store->name ?: 'toko');
                $randomCode = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
                $ext = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = "{$date}_{$storeSlug}_{$randomCode}.{$ext}";
                $path = 'foto-toko/' . $filename;

                \Illuminate\Support\Facades\Storage::disk('public')->putFileAs('foto-toko', $file, $filename);

                $store->photo = $path;
                $store->save();
            }
        }

        $store->refresh();

        return response()->json([
            'success' => true,
            'message' => "Perubahan data toko '{$store->name}' berhasil disimpan.",
            'data' => $store,
        ]);
    }

    /**
     * Delete data toko mitra by ID.
     * Dilindungi dengan autentikasi auth:api dan permission toko-delete.
     */
    public function destroy(Store $store): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth('api')->user();

        // Pastikan permission sama dengan web (toko-delete)
        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('toko-delete', 'web') && ! $user->can('toko-delete'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus data toko mitra.',
            ], 403);
        }

        $storeName = $store->name;
        $store->delete();

        return response()->json([
            'success' => true,
            'message' => "Toko mitra '{$storeName}' berhasil dihapus.",
        ]);
    }
}
