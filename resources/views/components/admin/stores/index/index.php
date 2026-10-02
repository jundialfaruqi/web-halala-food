<?php

use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin'), Title('Mitra Toko & Distribusi - Halala Food')] class extends Component
{
    /**
     * Create a new partner store.
     *
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string}
     */
    public function createStore(array $data): array
    {
        if (! Auth::user()?->can('toko-create')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk menambahkan toko mitra.'];
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'route' => ['nullable', 'string', 'max:100'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable'],
        ], [
            'name.required' => 'Nama toko mitra wajib diisi.',
            'name.max' => 'Nama toko maksimal 255 karakter.',
            'commission_rate.numeric' => 'Persentase komisi harus berupa angka.',
            'commission_rate.min' => 'Persentase komisi minimal 0%.',
            'commission_rate.max' => 'Persentase komisi maksimal 100%.',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        Store::create([
            'name' => trim((string) $data['name']),
            'owner_name' => ! empty($data['owner_name']) ? trim((string) $data['owner_name']) : null,
            'phone' => ! empty($data['phone']) ? trim((string) $data['phone']) : null,
            'address' => ! empty($data['address']) ? trim((string) $data['address']) : null,
            'route' => ! empty($data['route']) ? trim((string) $data['route']) : null,
            'commission_rate' => isset($data['commission_rate']) && $data['commission_rate'] !== '' ? (float) $data['commission_rate'] : 0.00,
            'notes' => ! empty($data['notes']) ? trim((string) $data['notes']) : null,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);

        return ['success' => true, 'message' => "Toko mitra '{$data['name']}' berhasil ditambahkan."];
    }

    /**
     * Update an existing partner store.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return array{success: bool, message: string}
     */
    public function updateStore(int $id, array $data): array
    {
        if (! Auth::user()?->can('toko-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk mengubah data toko mitra.'];
        }

        $store = Store::find($id);
        if (! $store) {
            return ['success' => false, 'message' => 'Data toko tidak ditemukan.'];
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'route' => ['nullable', 'string', 'max:100'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable'],
        ], [
            'name.required' => 'Nama toko mitra wajib diisi.',
            'name.max' => 'Nama toko maksimal 255 karakter.',
            'commission_rate.numeric' => 'Persentase komisi harus berupa angka.',
            'commission_rate.min' => 'Persentase komisi minimal 0%.',
            'commission_rate.max' => 'Persentase komisi maksimal 100%.',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        $store->update([
            'name' => trim((string) $data['name']),
            'owner_name' => ! empty($data['owner_name']) ? trim((string) $data['owner_name']) : null,
            'phone' => ! empty($data['phone']) ? trim((string) $data['phone']) : null,
            'address' => ! empty($data['address']) ? trim((string) $data['address']) : null,
            'route' => ! empty($data['route']) ? trim((string) $data['route']) : null,
            'commission_rate' => isset($data['commission_rate']) && $data['commission_rate'] !== '' ? (float) $data['commission_rate'] : 0.00,
            'notes' => ! empty($data['notes']) ? trim((string) $data['notes']) : null,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);

        return ['success' => true, 'message' => "Perubahan data toko '{$store->name}' berhasil disimpan."];
    }

    /**
     * Delete a partner store.
     *
     * @param int $id
     * @return array{success: bool, message: string}
     */
    public function deleteStore(int $id): array
    {
        if (! Auth::user()?->can('toko-delete')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk menghapus data toko mitra.'];
        }

        $store = Store::find($id);
        if (! $store) {
            return ['success' => false, 'message' => 'Data toko tidak ditemukan.'];
        }

        $storeName = $store->name;
        $store->delete();

        return ['success' => true, 'message' => "Toko mitra '{$storeName}' berhasil dihapus."];
    }

    /**
     * Toggle store active status.
     *
     * @param int $id
     * @return array{success: bool, message: string}
     */
    public function toggleStatus(int $id): array
    {
        if (! Auth::user()?->can('toko-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk mengubah status toko.'];
        }

        $store = Store::find($id);
        if (! $store) {
            return ['success' => false, 'message' => 'Data toko tidak ditemukan.'];
        }

        $store->is_active = ! $store->is_active;
        $store->save();

        $statusStr = $store->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return ['success' => true, 'message' => "Status toko '{$store->name}' berhasil {$statusStr}."];
    }

    public function with(): array
    {
        $stores = Store::orderBy('name')->get();

        $routes = Store::whereNotNull('route')
            ->where('route', '!=', '')
            ->distinct()
            ->orderBy('route')
            ->pluck('route')
            ->toArray();

        $totalStores = $stores->count();
        $activeStores = $stores->where('is_active', true)->count();
        $totalRoutes = count($routes);
        $avgCommission = $totalStores > 0 ? (float) $stores->avg('commission_rate') : 0.00;

        return [
            'stores' => $stores,
            'routes' => $routes,
            'stats' => [
                'total_stores' => $totalStores,
                'active_stores' => $activeStores,
                'total_routes' => $totalRoutes,
                'avg_commission' => $avgCommission,
            ],
        ];
    }
};
