<?php

use App\Models\Store;
use Illuminate\Support\Facades\Auth;
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

        $storesData = $stores->map(function ($store) {
            $data = $store->toArray();
            $data['edit_url'] = route('admin.stores.edit', $store->id);

            return $data;
        });

        return [
            'stores' => $storesData,
            'routes' => $routes,
        ];
    }
};
