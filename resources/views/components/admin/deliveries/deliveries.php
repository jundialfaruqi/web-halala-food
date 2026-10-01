<?php

namespace App\Livewire\Admin;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\FinishedStockMutation;
use App\Models\Partner;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin'), Title('Distribusi Surat Jalan & Kurir - Halala Food')] class extends Component
{
    /**
     * Create or Update a Delivery Order (Surat Jalan)
     */
    public function saveDelivery(?int $id, int $partnerId, ?int $courierId, ?string $notes, array $items): array
    {
        $notes = $notes ? trim($notes) : null;

        if (empty($items)) {
            return [
                'success' => false,
                'message' => 'Tambahkan minimal 1 item produk kemasan pada surat jalan.',
                'errors' => ['items' => ['Minimal 1 produk wajib ditambahkan.']],
            ];
        }

        $rules = [
            'partnerId' => ['required', 'exists:partners,id'],
            'courierId' => ['nullable', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.qty_sent' => ['required', 'integer', 'min:1'],
        ];

        $validator = validator(
            [
                'partnerId' => $partnerId,
                'courierId' => $courierId,
                'items' => $items,
            ],
            $rules,
            [
                'partnerId.required' => 'Pilih mitra toko tujuan pengiriman.',
                'items.required' => 'Daftar muatan produk wajib diisi.',
                'items.*.product_variant_id.required' => 'Pilih varian produk.',
                'items.*.qty_sent.required' => 'Jumlah kuantitas kirim wajib diisi.',
                'items.*.qty_sent.min' => 'Jumlah kuantitas minimal 1 pcs/pack.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ];
        }

        try {
            DB::transaction(function () use ($id, $partnerId, $courierId, $notes, $items) {
                $totalAmount = 0;
                $processedItems = [];

                foreach ($items as $item) {
                    $variant = ProductVariant::findOrFail($item['product_variant_id']);
                    $qty = (int) $item['qty_sent'];
                    $unitPrice = (float) ($item['unit_price'] ?? $variant->wholesale_price);
                    $subtotal = $qty * $unitPrice;
                    $totalAmount += $subtotal;

                    $processedItems[] = [
                        'product_variant_id' => $variant->id,
                        'qty_sent' => $qty,
                        'qty_accepted' => 0,
                        'qty_returned' => 0,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'notes' => !empty($item['notes']) ? trim($item['notes']) : null,
                    ];
                }

                if ($id) {
                    $delivery = Delivery::findOrFail($id);
                    if (!in_array($delivery->status, ['draft', 'on_the_way'])) {
                        throw new \Exception('Hanya Surat Jalan berstatus Draft yang dapat diperbarui.');
                    }

                    $delivery->update([
                        'partner_id' => $partnerId,
                        'courier_id' => $courierId,
                        'total_amount' => $totalAmount,
                        'notes' => $notes,
                    ]);

                    $delivery->items()->delete();
                } else {
                    $delivery = Delivery::create([
                        'delivery_number' => Delivery::generateDeliveryNumber(),
                        'partner_id' => $partnerId,
                        'courier_id' => $courierId,
                        'status' => 'draft',
                        'total_amount' => $totalAmount,
                        'notes' => $notes,
                        'created_by' => Auth::id(),
                    ]);
                }

                foreach ($processedItems as $pItem) {
                    $delivery->items()->create($pItem);
                }
            });

            return [
                'success' => true,
                'message' => $id ? 'Surat Jalan berhasil diperbarui.' : 'Surat Jalan baru berhasil dibuat.',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Gagal menyimpan Surat Jalan: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Dispatch Delivery (Kurir Berangkat / On The Way)
     * Automatically deducts finished goods inventory from warehouse.
     */
    public function dispatchDelivery(int $id): array
    {
        try {
            DB::transaction(function () use ($id) {
                $delivery = Delivery::with('items.productVariant')->findOrFail($id);

                if ($delivery->status !== 'draft') {
                    throw new \Exception('Hanya surat jalan berstatus Draft yang dapat diberangkatkan.');
                }

                // Check and deduct stock for each item
                foreach ($delivery->items as $item) {
                    $variant = $item->productVariant;
                    if ($variant->stock_qty < $item->qty_sent) {
                        throw new \Exception("Stok barang jadi untuk '{$variant->name}' tidak mencukupi (Tersedia: {$variant->stock_qty}, Dibutuhkan: {$item->qty_sent}).");
                    }

                    // Deduct stock
                    $variant->decrement('stock_qty', $item->qty_sent);

                    // Create stock mutation log
                    FinishedStockMutation::create([
                        'product_variant_id' => $variant->id,
                        'reference_type' => 'delivery_out',
                        'reference_id' => $delivery->id,
                        'type' => 'out',
                        'quantity' => $item->qty_sent,
                        'current_stock' => $variant->stock_qty,
                        'notes' => "Pengantaran Surat Jalan {$delivery->delivery_number} ke " . ($delivery->partner?->name ?? 'Toko'),
                        'user_id' => Auth::id(),
                    ]);
                }

                $delivery->update([
                    'status' => 'on_the_way',
                    'dispatched_at' => Carbon::now(),
                ]);
            });

            return [
                'success' => true,
                'message' => 'Surat Jalan berhasil diberangkatkan. Stok barang jadi telah dialokasikan.',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Gagal memberangkatkan pengiriman: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Confirm Delivery Receipt at Partner Store (Serah Terima Toko Selesai)
     * Records actual accepted qty, returned qty (restores stock if any), and updates partner receivables.
     */
    public function confirmDeliveryReceipt(int $id, string $receiverName, ?string $receiverPhone, array $itemResults): array
    {
        $receiverName = trim($receiverName);
        $receiverPhone = $receiverPhone ? trim($receiverPhone) : null;

        if (empty($receiverName)) {
            return [
                'success' => false,
                'message' => 'Nama staf/penanggung jawab penerima di toko wajib diisi.',
                'errors' => ['receiverName' => ['Nama penerima toko wajib diisi.']],
            ];
        }

        try {
            DB::transaction(function () use ($id, $receiverName, $receiverPhone, $itemResults) {
                $delivery = Delivery::with('items.productVariant', 'partner')->findOrFail($id);

                if (!in_array($delivery->status, ['draft', 'on_the_way'])) {
                    throw new \Exception('Surat jalan ini sudah selesai atau dibatalkan sebelumnya.');
                }

                // If confirming directly from draft (e.g. instant handover), deduct initial stock first
                if ($delivery->status === 'draft') {
                    foreach ($delivery->items as $item) {
                        $variant = $item->productVariant;
                        $variant->decrement('stock_qty', $item->qty_sent);

                        FinishedStockMutation::create([
                            'product_variant_id' => $variant->id,
                            'reference_type' => 'delivery_out',
                            'reference_id' => $delivery->id,
                            'type' => 'out',
                            'quantity' => $item->qty_sent,
                            'current_stock' => $variant->stock_qty,
                            'notes' => "Pengantaran Surat Jalan {$delivery->delivery_number}",
                            'user_id' => Auth::id(),
                        ]);
                    }
                }

                $totalAcceptedAmount = 0;

                // Process each item result
                foreach ($delivery->items as $item) {
                    $res = collect($itemResults)->firstWhere('product_variant_id', $item->product_variant_id);
                    $qtyAccepted = isset($res['qty_accepted']) ? (int) $res['qty_accepted'] : $item->qty_sent;
                    $qtyReturned = isset($res['qty_returned']) ? (int) $res['qty_returned'] : 0;

                    // Ensure accepted + returned <= sent
                    if (($qtyAccepted + $qtyReturned) > $item->qty_sent) {
                        $qtyAccepted = $item->qty_sent - $qtyReturned;
                    }

                    $itemSubtotal = $qtyAccepted * (float) $item->unit_price;
                    $totalAcceptedAmount += $itemSubtotal;

                    $item->update([
                        'qty_accepted' => $qtyAccepted,
                        'qty_returned' => $qtyReturned,
                        'subtotal' => $itemSubtotal,
                    ]);

                    // If any goods returned, restore to inventory
                    if ($qtyReturned > 0) {
                        $variant = $item->productVariant;
                        $variant->increment('stock_qty', $qtyReturned);

                        FinishedStockMutation::create([
                            'product_variant_id' => $variant->id,
                            'reference_type' => 'consignment_return',
                            'reference_id' => $delivery->id,
                            'type' => 'in',
                            'quantity' => $qtyReturned,
                            'current_stock' => $variant->stock_qty,
                            'notes' => "Retur barang pengantaran {$delivery->delivery_number} dari " . ($delivery->partner?->name ?? 'Toko'),
                            'user_id' => Auth::id(),
                        ]);
                    }
                }

                // Update delivery record
                $delivery->update([
                    'status' => 'delivered',
                    'total_amount' => $totalAcceptedAmount,
                    'receiver_name' => $receiverName,
                    'receiver_phone' => $receiverPhone,
                    'delivered_at' => Carbon::now(),
                ]);

                // Automatically increment partner receivable
                if ($delivery->partner && $totalAcceptedAmount > 0) {
                    $delivery->partner->increment('current_receivable', $totalAcceptedAmount);
                }
            });

            return [
                'success' => true,
                'message' => 'Serah terima pengantaran berhasil dikonfirmasi. Piutang toko telah dicatat.',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Gagal mengonfirmasi serah terima: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Cancel Delivery Order
     */
    public function cancelDelivery(int $id): array
    {
        try {
            DB::transaction(function () use ($id) {
                $delivery = Delivery::with('items.productVariant')->findOrFail($id);

                if ($delivery->status === 'delivered') {
                    throw new \Exception('Surat Jalan yang telah selesai diserahterimakan tidak dapat dibatalkan.');
                }

                // If already on_the_way, return deducted stock
                if ($delivery->status === 'on_the_way') {
                    foreach ($delivery->items as $item) {
                        $variant = $item->productVariant;
                        $variant->increment('stock_qty', $item->qty_sent);

                        FinishedStockMutation::create([
                            'product_variant_id' => $variant->id,
                            'reference_type' => 'consignment_return',
                            'reference_id' => $delivery->id,
                            'type' => 'in',
                            'quantity' => $item->qty_sent,
                            'current_stock' => $variant->stock_qty,
                            'notes' => "Pembatalan Surat Jalan {$delivery->delivery_number}",
                            'user_id' => Auth::id(),
                        ]);
                    }
                }

                $delivery->update(['status' => 'cancelled']);
            });

            return [
                'success' => true,
                'message' => 'Surat Jalan berhasil dibatalkan.',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Gagal membatalkan pengiriman: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Delete Delivery Order
     */
    public function deleteDelivery(int $id): array
    {
        try {
            $delivery = Delivery::findOrFail($id);

            if (!in_array($delivery->status, ['draft', 'cancelled'])) {
                return [
                    'success' => false,
                    'message' => 'Hanya Surat Jalan berstatus Draft atau Dibatalkan yang dapat dihapus.',
                ];
            }

            $delivery->delete();

            return [
                'success' => true,
                'message' => 'Surat Jalan berhasil dihapus.',
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Gagal menghapus Surat Jalan: ' . $th->getMessage(),
            ];
        }
    }

    public function with(): array
    {
        $deliveries = Delivery::with(['partner', 'courier', 'items.productVariant.product'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'delivery_number' => $d->delivery_number,
                    'partner_id' => $d->partner_id,
                    'partner_name' => $d->partner?->name ?? 'Mitra Toko',
                    'partner_code' => $d->partner?->code ?? '-',
                    'partner_type' => $d->partner?->typeLabel() ?? '-',
                    'partner_city' => $d->partner?->city ?? '-',
                    'partner_address' => $d->partner?->address ?? '-',
                    'partner_phone' => $d->partner?->phone ?? '-',
                    'partner_wa_url' => $d->partner?->whatsappUrl(),
                    'courier_id' => $d->courier_id,
                    'courier_name' => $d->courier?->name ?? 'Belum Ditugaskan',
                    'status' => $d->status,
                    'status_label' => $d->statusLabel(),
                    'total_amount' => (float) $d->total_amount,
                    'formatted_total_amount' => $d->formattedTotalAmount(),
                    'total_sent_qty' => $d->totalQuantitySent(),
                    'total_accepted_qty' => $d->totalQuantityAccepted(),
                    'total_returned_qty' => $d->totalQuantityReturned(),
                    'receiver_name' => $d->receiver_name,
                    'receiver_phone' => $d->receiver_phone,
                    'notes' => $d->notes,
                    'created_at' => $d->created_at?->format('d M Y, H:i'),
                    'dispatched_at' => $d->dispatched_at?->format('d M Y, H:i'),
                    'delivered_at' => $d->delivered_at?->format('d M Y, H:i'),
                    'items' => $d->items->map(function ($it) {
                        return [
                            'id' => $it->id,
                            'product_variant_id' => $it->product_variant_id,
                            'product_name' => $it->productVariant?->product?->name ?? 'Produk',
                            'variant_name' => $it->productVariant?->name ?? 'Varian',
                            'full_name' => ($it->productVariant?->product?->name ?? 'Produk') . ' - ' . ($it->productVariant?->name ?? 'Varian'),
                            'sku_code' => $it->productVariant?->sku_code ?? '-',
                            'qty_sent' => $it->qty_sent,
                            'qty_accepted' => $it->qty_accepted,
                            'qty_returned' => $it->qty_returned,
                            'unit_price' => (float) $it->unit_price,
                            'formatted_unit_price' => $it->formattedUnitPrice(),
                            'subtotal' => (float) $it->subtotal,
                            'formatted_subtotal' => $it->formattedSubtotal(),
                            'notes' => $it->notes,
                        ];
                    }),
                ];
            });

        $partners = Partner::where('is_active', true)->orderBy('name')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'type_label' => $p->typeLabel(),
                'city' => $p->city,
                'address' => $p->address,
                'payment_term_label' => $p->paymentTermLabel(),
                'current_receivable' => (float) $p->current_receivable,
                'credit_limit' => (float) $p->credit_limit,
            ];
        });

        $couriers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['kurir', 'manager', 'dev']);
        })->orderBy('name')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
            ];
        });

        $productVariants = ProductVariant::with('product')
            ->where('is_active', true)
            ->orderBy('product_id')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'product_name' => $v->product?->name ?? 'Produk',
                    'variant_name' => $v->name,
                    'full_name' => ($v->product?->name ?? 'Produk') . ' - ' . $v->name,
                    'packaging_type' => $v->packaging_type,
                    'wholesale_price' => (float) $v->wholesale_price,
                    'formatted_wholesale_price' => $v->formattedWholesalePrice(),
                    'stock_qty' => $v->stock_qty,
                ];
            });

        $totalDeliveries = $deliveries->count();
        $onTheWayCount = $deliveries->where('status', 'on_the_way')->count();
        $deliveredCount = $deliveries->where('status', 'delivered')->count();
        $draftCount = $deliveries->where('status', 'draft')->count();

        return [
            'deliveries' => $deliveries,
            'partners' => $partners,
            'couriers' => $couriers,
            'productVariants' => $productVariants,
            'totalDeliveries' => $totalDeliveries,
            'onTheWayCount' => $onTheWayCount,
            'deliveredCount' => $deliveredCount,
            'draftCount' => $draftCount,
            'currentUserId' => Auth::id(),
        ];
    }
};
