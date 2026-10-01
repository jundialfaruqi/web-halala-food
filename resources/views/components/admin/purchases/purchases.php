<?php

namespace App\Livewire\Admin;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RawMaterial;
use App\Models\StockMutation;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin'), Title('Pengadaan & Supplier - Halala Food')] class extends Component
{
    /**
     * Create or Update a Purchase Order (PO)
     */
    public function savePurchaseOrder(
        ?int $id,
        int $supplierId,
        string $orderDate,
        ?string $dueDate,
        string $status,
        ?string $notes,
        array $items
    ): array {
        $notes = $notes ? trim($notes) : null;
        $status = in_array($status, ['draft', 'ordered']) ? $status : 'draft';

        if (empty($items)) {
            return [
                'success' => false,
                'message' => 'Tambahkan minimal 1 item bahan baku pada pesanan pembelian.',
                'errors' => ['items' => ['Minimal 1 bahan baku wajib ditambahkan.']],
            ];
        }

        $rules = [
            'supplierId' => ['required', 'exists:suppliers,id'],
            'orderDate' => ['required', 'date'],
            'dueDate' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.raw_material_id' => ['required', 'exists:raw_materials,id'],
            'items.*.qty_ordered' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];

        $validator = validator(
            [
                'supplierId' => $supplierId,
                'orderDate' => $orderDate,
                'dueDate' => $dueDate,
                'items' => $items,
            ],
            $rules,
            [
                'supplierId.required' => 'Pilih supplier tujuan pemesanan.',
                'orderDate.required' => 'Tanggal pemesanan wajib diisi.',
                'items.required' => 'Daftar item pesanan wajib diisi.',
                'items.*.raw_material_id.required' => 'Pilih bahan baku.',
                'items.*.qty_ordered.required' => 'Jumlah kuantitas pesan wajib diisi.',
                'items.*.qty_ordered.min' => 'Jumlah kuantitas minimal 0.01.',
                'items.*.unit_price.required' => 'Harga satuan wajib diisi.',
                'items.*.unit_price.min' => 'Harga satuan tidak boleh negatif.',
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
            DB::transaction(function () use ($id, $supplierId, $orderDate, $dueDate, $status, $notes, $items) {
                $totalAmount = 0;
                $processedItems = [];

                foreach ($items as $item) {
                    $material = RawMaterial::findOrFail($item['raw_material_id']);
                    $qty = (float) $item['qty_ordered'];
                    $unitPrice = (float) $item['unit_price'];
                    $subtotal = $qty * $unitPrice;
                    $totalAmount += $subtotal;

                    $processedItems[] = [
                        'raw_material_id' => $material->id,
                        'qty_ordered' => $qty,
                        'qty_received' => 0,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'notes' => !empty($item['notes']) ? trim($item['notes']) : null,
                    ];
                }

                if ($id) {
                    $po = PurchaseOrder::findOrFail($id);
                    if (!in_array($po->status, ['draft', 'ordered'])) {
                        throw new \Exception('Hanya PO berstatus Draft atau Dipesan yang dapat diedit.');
                    }

                    $po->update([
                        'supplier_id' => $supplierId,
                        'order_date' => $orderDate,
                        'due_date' => $dueDate ?: null,
                        'status' => $status,
                        'total_amount' => $totalAmount,
                        'notes' => $notes,
                    ]);

                    $po->items()->delete();
                    foreach ($processedItems as $pItem) {
                        $po->items()->create($pItem);
                    }
                } else {
                    $poNumber = PurchaseOrder::generatePoNumber();

                    $po = PurchaseOrder::create([
                        'po_number' => $poNumber,
                        'supplier_id' => $supplierId,
                        'order_date' => $orderDate,
                        'due_date' => $dueDate ?: null,
                        'status' => $status,
                        'payment_status' => 'unpaid',
                        'total_amount' => $totalAmount,
                        'notes' => $notes,
                        'created_by' => Auth::id(),
                    ]);

                    foreach ($processedItems as $pItem) {
                        $po->items()->create($pItem);
                    }
                }
            });

            return [
                'success' => true,
                'message' => $id ? 'Pesanan Pembelian (PO) berhasil diperbarui.' : 'Pesanan Pembelian (PO) berhasil dibuat.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menyimpan PO: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm Receiving of Goods at Warehouse (Auto-updates Stock & Moving Average Cost)
     */
    public function receivePurchaseOrder(int $id, array $receivedItems): array
    {
        try {
            DB::transaction(function () use ($id, $receivedItems) {
                $po = PurchaseOrder::with('items.rawMaterial')->findOrFail($id);

                if ($po->status === 'received') {
                    throw new \Exception('Pesanan ini sudah diterima sebelumnya.');
                }
                if ($po->status === 'cancelled') {
                    throw new \Exception('Pesanan yang telah dibatalkan tidak dapat diterima.');
                }

                $now = Carbon::now();
                /** @var User|null $currentUser */
                $currentUser = Auth::user();
                $userId = $currentUser?->id;

                foreach ($receivedItems as $itemData) {
                    $itemId = (int) ($itemData['item_id'] ?? 0);
                    $qtyReceived = (float) ($itemData['qty_received'] ?? 0);

                    $poItem = $po->items->firstWhere('id', $itemId);
                    if (!$poItem) {
                        continue;
                    }

                    if ($qtyReceived < 0) {
                        throw new \Exception('Jumlah terima tidak boleh negatif.');
                    }

                    $poItem->update([
                        'qty_received' => $qtyReceived,
                    ]);

                    if ($qtyReceived > 0) {
                        $material = RawMaterial::lockForUpdate()->findOrFail($poItem->raw_material_id);
                        $oldStock = (float) $material->stock_qty;
                        $oldAvgCost = (float) $material->average_cost;
                        $newStock = $oldStock + $qtyReceived;
                        $unitPrice = (float) $poItem->unit_price;

                        // Moving Average Cost Formula:
                        // ((oldStock * oldAvgCost) + (receivedQty * unitPrice)) / newStock
                        if ($oldStock <= 0) {
                            $newAvgCost = $unitPrice;
                        } else {
                            $newAvgCost = (($oldStock * $oldAvgCost) + ($qtyReceived * $unitPrice)) / $newStock;
                        }

                        $material->update([
                            'stock_qty' => $newStock,
                            'average_cost' => round($newAvgCost, 2),
                        ]);

                        // Record Stock Mutation
                        StockMutation::create([
                            'raw_material_id' => $material->id,
                            'reference_type' => 'purchase',
                            'reference_id' => $po->id,
                            'type' => 'in',
                            'quantity' => $qtyReceived,
                            'current_stock' => $newStock,
                            'cost_per_unit' => $unitPrice,
                            'notes' => "Penerimaan PO #{$po->po_number}",
                            'user_id' => $userId,
                        ]);
                    }
                }

                $po->update([
                    'status' => 'received',
                    'received_at' => $now,
                    'receiver_user_id' => $userId,
                ]);
            });

            return [
                'success' => true,
                'message' => 'Penerimaan barang berhasil dikonfirmasi! Stok bahan baku dan HPP rata-rata berjalan telah diperbarui otomatis.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengonfirmasi penerimaan: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a Purchase Order
     */
    public function cancelPurchaseOrder(int $id): array
    {
        try {
            $po = PurchaseOrder::findOrFail($id);

            if ($po->status === 'received') {
                return [
                    'success' => false,
                    'message' => 'PO yang sudah diterima di gudang tidak dapat dibatalkan.',
                ];
            }

            $po->update(['status' => 'cancelled']);

            return [
                'success' => true,
                'message' => "Pesanan {$po->po_number} berhasil dibatalkan.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal membatalkan PO: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a Purchase Order (Draft / Cancelled only)
     */
    public function deletePurchaseOrder(int $id): array
    {
        try {
            $po = PurchaseOrder::findOrFail($id);

            if ($po->status === 'received') {
                return [
                    'success' => false,
                    'message' => 'PO yang telah diterima dan memutasi stok tidak dapat dihapus.',
                ];
            }

            DB::transaction(function () use ($po) {
                $po->items()->delete();
                $po->delete();
            });

            return [
                'success' => true,
                'message' => 'Pesanan Pembelian berhasil dihapus.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghapus PO: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create or Update a Supplier
     */
    public function saveSupplier(
        ?int $id,
        string $code,
        string $name,
        ?string $contactPerson,
        ?string $phone,
        ?string $whatsappNumber,
        ?string $email,
        ?string $address,
        ?string $city,
        int $paymentTermsDays,
        ?string $notes,
        bool $isActive = true
    ): array {
        $code = trim(strtoupper($code));
        $name = trim($name);
        $contactPerson = $contactPerson ? trim($contactPerson) : null;
        $email = $email ? trim(strtolower($email)) : null;
        $address = $address ? trim($address) : null;
        $city = $city ? trim($city) : null;
        $notes = $notes ? trim($notes) : null;

        $rules = [
            'code' => ['required', 'string', 'max:50', 'unique:suppliers,code,' . ($id ?: 'NULL') . ',id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'paymentTermsDays' => ['required', 'integer', 'min:0'],
        ];

        $validator = validator(
            [
                'code' => $code,
                'name' => $name,
                'email' => $email,
                'paymentTermsDays' => $paymentTermsDays,
            ],
            $rules,
            [
                'code.required' => 'Kode supplier wajib diisi.',
                'code.unique' => 'Kode supplier sudah digunakan.',
                'name.required' => 'Nama supplier wajib diisi.',
                'email.email' => 'Format email supplier tidak valid.',
                'paymentTermsDays.required' => 'Syarat pembayaran (tempo hari) wajib diisi.',
                'paymentTermsDays.min' => 'Syarat pembayaran minimal 0 hari (Tunai/COD).',
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
            $data = [
                'code' => $code,
                'name' => $name,
                'contact_person' => $contactPerson,
                'phone' => Supplier::normalizePhone($phone),
                'whatsapp_number' => Supplier::normalizePhone($whatsappNumber ?: $phone),
                'email' => $email,
                'address' => $address,
                'city' => $city,
                'payment_terms_days' => $paymentTermsDays,
                'notes' => $notes,
                'is_active' => $isActive,
            ];

            if ($id) {
                $supplier = Supplier::findOrFail($id);
                $supplier->update($data);
            } else {
                Supplier::create($data);
            }

            return [
                'success' => true,
                'message' => $id ? 'Data supplier berhasil diperbarui.' : 'Supplier baru berhasil ditambahkan.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menyimpan supplier: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a Supplier
     */
    public function deleteSupplier(int $id): array
    {
        try {
            $supplier = Supplier::withCount('purchaseOrders')->findOrFail($id);

            if ($supplier->purchase_orders_count > 0) {
                return [
                    'success' => false,
                    'message' => "Supplier '{$supplier->name}' tidak dapat dihapus karena memiliki riwayat {$supplier->purchase_orders_count} pesanan pembelian (PO). Anda dapat menonaktifkan statusnya.",
                ];
            }

            $supplier->delete();

            return [
                'success' => true,
                'message' => "Supplier '{$supplier->name}' berhasil dihapus.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghapus supplier: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Provide View Data
     */
    public function with(): array
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'creator', 'receiver', 'items.rawMaterial'])
            ->latest('id')
            ->get()
            ->map(function ($po) {
                return [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'supplier_id' => $po->supplier_id,
                    'supplier_name' => $po->supplier?->name ?? 'Supplier',
                    'supplier_code' => $po->supplier?->code ?? '-',
                    'supplier_phone' => $po->supplier?->phone ?? '-',
                    'supplier_wa_url' => $po->supplier?->whatsappUrl(),
                    'supplier_payment_terms' => $po->supplier?->paymentTermLabel() ?? '-',
                    'order_date' => $po->order_date?->format('Y-m-d'),
                    'formatted_order_date' => $po->order_date?->format('d M Y'),
                    'due_date' => $po->due_date?->format('Y-m-d'),
                    'formatted_due_date' => $po->due_date?->format('d M Y') ?? '-',
                    'status' => $po->status,
                    'status_label' => $po->statusLabel(),
                    'payment_status' => $po->payment_status,
                    'payment_status_label' => $po->paymentStatusLabel(),
                    'total_amount' => (float) $po->total_amount,
                    'formatted_total_amount' => $po->formattedTotalAmount(),
                    'received_at' => $po->received_at?->format('d M Y, H:i'),
                    'receiver_name' => $po->receiver?->name,
                    'creator_name' => $po->creator?->name ?? 'Admin',
                    'notes' => $po->notes,
                    'items_count' => $po->items->count(),
                    'items' => $po->items->map(function ($it) {
                        return [
                            'id' => $it->id,
                            'raw_material_id' => $it->raw_material_id,
                            'material_name' => $it->rawMaterial?->name ?? 'Bahan Baku',
                            'material_code' => $it->rawMaterial?->code ?? '-',
                            'material_unit' => $it->rawMaterial?->unit ?? 'pcs',
                            'qty_ordered' => (float) $it->qty_ordered,
                            'qty_received' => (float) $it->qty_received,
                            'unit_price' => (float) $it->unit_price,
                            'formatted_unit_price' => $it->formattedUnitPrice(),
                            'subtotal' => (float) $it->subtotal,
                            'formatted_subtotal' => $it->formattedSubtotal(),
                            'notes' => $it->notes,
                        ];
                    }),
                ];
            });

        $suppliers = Supplier::withCount('purchaseOrders')
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'contact_person' => $s->contact_person,
                    'phone' => $s->phone,
                    'formatted_phone' => $s->formattedPhone(),
                    'whatsapp_number' => $s->whatsapp_number,
                    'whatsapp_url' => $s->whatsappUrl(),
                    'email' => $s->email,
                    'address' => $s->address,
                    'city' => $s->city,
                    'payment_terms_days' => (int) $s->payment_terms_days,
                    'payment_term_label' => $s->paymentTermLabel(),
                    'notes' => $s->notes,
                    'is_active' => (bool) $s->is_active,
                    'purchase_orders_count' => (int) $s->purchase_orders_count,
                    'total_purchases_amount' => (float) $s->totalPurchasesAmount(),
                    'formatted_total_purchases' => $s->formattedTotalPurchases(),
                ];
            });

        $rawMaterials = RawMaterial::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'code' => $m->code,
                    'name' => $m->name,
                    'category' => $m->category,
                    'unit' => $m->unit,
                    'stock_qty' => (float) $m->stock_qty,
                    'average_cost' => (float) $m->average_cost,
                    'formatted_average_cost' => $m->formattedAverageCost(),
                ];
            });

        $totalOrders = $purchaseOrders->count();
        $orderedCount = $purchaseOrders->where('status', 'ordered')->count();
        $receivedCount = $purchaseOrders->where('status', 'received')->count();
        $draftCount = $purchaseOrders->where('status', 'draft')->count();
        $totalSuppliers = $suppliers->count();

        $nextSupplierCode = Supplier::generateSupplierCode();

        return [
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => $suppliers,
            'rawMaterials' => $rawMaterials,
            'totalOrders' => $totalOrders,
            'orderedCount' => $orderedCount,
            'receivedCount' => $receivedCount,
            'draftCount' => $draftCount,
            'totalSuppliers' => $totalSuppliers,
            'nextSupplierCode' => $nextSupplierCode,
            'todayDate' => Carbon::now()->format('Y-m-d'),
        ];
    }
};
