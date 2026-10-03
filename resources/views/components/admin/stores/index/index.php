<?php

use App\Models\Store;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Mitra Toko & Distribusi - Halala Food')] class extends Component
{
    /**
     * Delete a partner store.
     *
     * @param int $id
     * @return array{success: bool, message: string}
     */
    public function deleteStore(int $id): array
    {
        if (Gate::denies('toko-delete')) {
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
        if (Gate::denies('toko-edit')) {
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

    /**
     * Update photo for a store from base64 DataURL (WebP or JPEG <= 50KB).
     *
     * @param int $id
     * @param string $photoData
     * @return array{success: bool, message: string, photo_url?: ?string}
     */
    public function updateStorePhoto(int $id, string $photoData): array
    {
        if (Gate::denies('toko-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk mengubah foto toko mitra.'];
        }

        $store = Store::find($id);
        if (! $store) {
            return ['success' => false, 'message' => 'Data toko mitra tidak ditemukan.'];
        }

        $validationError = Store::validatePhotoBase64($photoData);
        if ($validationError !== null) {
            return ['success' => false, 'message' => $validationError];
        }

        $saved = $store->updatePhotoFromBase64($photoData);
        if ($saved) {
            return [
                'success' => true,
                'message' => "Foto toko '{$store->name}' berhasil diperbarui.",
                'photo_url' => $store->photo_url,
            ];
        }

        return ['success' => false, 'message' => 'Gagal memproses file foto. Pastikan format gambar valid.'];
    }

    /**
     * Delete photo for a store.
     *
     * @param int $id
     * @return array{success: bool, message: string}
     */
    public function deleteStorePhoto(int $id): array
    {
        if (Gate::denies('toko-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk menghapus foto toko mitra.'];
        }

        $store = Store::find($id);
        if (! $store) {
            return ['success' => false, 'message' => 'Data toko mitra tidak ditemukan.'];
        }

        if ($store->photo && Storage::disk('public')->exists($store->photo)) {
            Storage::disk('public')->delete($store->photo);
        }

        $store->photo = null;
        $store->save();

        return ['success' => true, 'message' => "Foto toko '{$store->name}' berhasil dihapus."];
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

        $storesData = $stores->map(function ($store) {
            $data = $store->toArray();
            $data['edit_url'] = route('admin.stores.edit', $store->id);
            $data['photo_url'] = $store->photo_url;

            return $data;
        });

        return [
            'stores' => $storesData,
            'routes' => $routes,
        ];
    }
};
