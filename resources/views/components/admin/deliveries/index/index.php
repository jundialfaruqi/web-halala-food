<?php

use App\Models\Delivery;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Surat Jalan & Pengantaran - Halala Food')] class extends Component
{
    /**
     * Cancel a delivery and restore stock.
     */
    public function cancelDelivery(int $id): array
    {
        if (Gate::denies('pengantaran-delete') && Gate::denies('pengantaran-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk membatalkan surat jalan ini.'];
        }

        $delivery = Delivery::with('items')->find($id);
        if (! $delivery) {
            return ['success' => false, 'message' => 'Data surat jalan tidak ditemukan.'];
        }

        if (! $delivery->isAccessibleBy(Auth::user())) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk membatalkan surat jalan milik kurir lain.'];
        }

        if ($delivery->status === 'selesai') {
            return ['success' => false, 'message' => 'Surat jalan yang sudah selesai tidak dapat dibatalkan.'];
        }

        if ($delivery->status === 'dibatalkan') {
            return ['success' => false, 'message' => 'Surat jalan ini sudah dalam status dibatalkan.'];
        }

        DB::transaction(function () use ($delivery) {
            // Restore ready stock for products
            foreach ($delivery->items as $item) {
                Product::where('id', $item->product_id)->increment('stock_ready', $item->quantity);
            }

            $delivery->update(['status' => 'dibatalkan']);
        });

        return ['success' => true, 'message' => "Surat jalan {$delivery->delivery_number} berhasil dibatalkan dan stok dikembalikan ke gudang."];
    }

    /**
     * Delete a delivery and restore stock if not yet delivered.
     */
    public function deleteDelivery(int $id): array
    {
        if (Gate::denies('pengantaran-delete')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk menghapus surat jalan.'];
        }

        $delivery = Delivery::with('items')->find($id);
        if (! $delivery) {
            return ['success' => false, 'message' => 'Data surat jalan tidak ditemukan.'];
        }

        if (! $delivery->isAccessibleBy(Auth::user())) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk menghapus surat jalan milik kurir lain.'];
        }

        if ($delivery->status === 'selesai') {
            return ['success' => false, 'message' => 'Surat jalan yang telah selesai serah terima tidak boleh dihapus demi integritas data riwayat.'];
        }

        $deliveryNumber = $delivery->delivery_number;

        DB::transaction(function () use ($delivery) {
            // Restore ready stock if delivery was not already cancelled
            if ($delivery->status !== 'dibatalkan') {
                foreach ($delivery->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock_ready', $item->quantity);
                }
            }

            $delivery->delete();
        });

        return ['success' => true, 'message' => "Surat jalan {$deliveryNumber} berhasil dihapus."];
    }

    /**
     * Quick dispatch by courier.
     */
    public function markAsDispatched(int $id): array
    {
        if (Gate::denies('pengantaran-status') && Gate::denies('pengantaran-edit')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk mengubah status pengantaran.'];
        }

        $delivery = Delivery::find($id);
        if (! $delivery) {
            return ['success' => false, 'message' => 'Data surat jalan tidak ditemukan.'];
        }

        if (! $delivery->isAccessibleBy(Auth::user())) {
            return ['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk mengubah status surat jalan milik kurir lain.'];
        }

        if ($delivery->status !== 'diproses') {
            return ['success' => false, 'message' => 'Surat jalan tidak dapat diberangkatkan karena status bukan menunggu pengambilan.'];
        }

        $delivery->update([
            'status' => 'dikirim',
            'dispatched_at' => now(),
        ]);

        return ['success' => true, 'message' => "Surat jalan {$delivery->delivery_number} kini dalam perjalanan (Sedang Dikirim)."];
    }

    public function with(): array
    {
        $user = Auth::user();
        $isCourier = ($user instanceof User && $user->hasRole('kurir') && ! $user->hasAnyRole(['dev', 'manager']));

        $deliveries = Delivery::with(['store', 'courier', 'items.product'])
            ->forUser($user)
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'delivery_number' => $d->delivery_number,
                    'delivery_date' => $d->delivery_date?->format('Y-m-d'),
                    'delivery_date_formatted' => $d->delivery_date?->translatedFormat('d M Y') ?? '-',
                    'status' => $d->status,
                    'status_label' => $d->status_label,
                    'notes' => $d->notes,
                    'store' => $d->store ? [
                        'id' => $d->store->id,
                        'name' => $d->store->name,
                        'owner_name' => $d->store->owner_name,
                        'phone' => $d->store->phone,
                        'address' => $d->store->address,
                        'latitude' => $d->store->latitude,
                        'longitude' => $d->store->longitude,
                        'route' => $d->store->route,
                    ] : null,
                    'courier' => $d->courier ? [
                        'id' => $d->courier->id,
                        'name' => $d->courier->name,
                        'phone' => $d->courier->phone,
                    ] : null,
                    'recipient_name' => $d->recipient_name,
                    'recipient_phone' => $d->recipient_phone,
                    'dispatched_at' => $d->dispatched_at?->translatedFormat('d M Y H:i'),
                    'delivered_at' => $d->delivered_at?->translatedFormat('d M Y H:i'),
                    'total_items' => $d->total_items,
                    'total_amount' => (float) $d->total_amount,
                    'items_summary' => $d->items->map(fn ($i) => ($i->product?->name ?? 'Produk') . ' (' . $i->quantity . ')')->join(', '),
                    'can_edit' => $d->canBeEdited(),
                ];
            });

        $routes = Store::whereNotNull('route')->where('route', '!=', '')->distinct()->pluck('route')->values()->all();

        return [
            'deliveries' => $deliveries,
            'routes' => $routes,
            'currentUserId' => $user?->id,
            'isCourier' => $isCourier,
        ];
    }
};
