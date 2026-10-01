<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SupplierAndPurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('email', 'manager@halala-food.id')->first();
        $admin = User::where('email', 'admin@halala-food.id')->first();

        $suppliers = [
            [
                'code' => 'SPL-001',
                'name' => 'PT Berkah Pangan Nusantara',
                'contact_person' => 'Bpk. Hendri Kusuma (Sales Manager)',
                'phone' => '081223344556',
                'whatsapp_number' => '081223344556',
                'email' => 'sales@berkahpangan.co.id',
                'address' => 'Kawasan Industri Pulogadung Blok B No. 12',
                'city' => 'Jakarta Timur',
                'payment_terms_days' => 30,
                'notes' => 'Pemasok utama susu bubuk full cream dan gula pasir kristal murni.',
                'is_active' => true,
            ],
            [
                'code' => 'SPL-002',
                'name' => 'CV Sari Wijen Indonesia',
                'contact_person' => 'Ibu Maya Hartono',
                'phone' => '081334455667',
                'whatsapp_number' => '081334455667',
                'email' => 'order@sariwijen.com',
                'address' => 'Jl. Rungkut Industri Raya No. 45',
                'city' => 'Surabaya',
                'payment_terms_days' => 14,
                'notes' => 'Pemasok biji wijen sangrai kualitas ekspor gurih dan bersih.',
                'is_active' => true,
            ],
            [
                'code' => 'SPL-003',
                'name' => 'PT Bogasari Flour Distributor',
                'contact_person' => 'Bpk. Rudianto',
                'phone' => '081112233445',
                'whatsapp_number' => '081112233445',
                'email' => 'distribusi@bogasari-agen.id',
                'address' => 'Jl. Cilincing Raya No. 101, Tanjung Priok',
                'city' => 'Jakarta Utara',
                'payment_terms_days' => 14,
                'notes' => 'Pemasok tepung terigu Segitiga Biru & Cakra Kembar per sak 25kg.',
                'is_active' => true,
            ],
            [
                'code' => 'SPL-004',
                'name' => 'Toko Kemasan Sejahtera Jaya',
                'contact_person' => 'Koh Hendra (Acin)',
                'phone' => '087811223344',
                'whatsapp_number' => '087811223344',
                'email' => 'kemasansejahtera@gmail.com',
                'address' => 'Pusat Grosir Pasar Pagi Mangga Dua Blok A No. 18',
                'city' => 'Jakarta Barat',
                'payment_terms_days' => 0,
                'notes' => 'Pemasok standing pouch ziplock, toples plastik silinder, dan cetak stiker gold foil.',
                'is_active' => true,
            ],
        ];

        $supplierMap = [];
        foreach ($suppliers as $sData) {
            $supplier = Supplier::updateOrCreate(
                ['code' => $sData['code']],
                [
                    'name' => $sData['name'],
                    'contact_person' => $sData['contact_person'],
                    'phone' => Supplier::normalizePhone($sData['phone']),
                    'whatsapp_number' => Supplier::normalizePhone($sData['whatsapp_number']),
                    'email' => $sData['email'],
                    'address' => $sData['address'],
                    'city' => $sData['city'],
                    'payment_terms_days' => $sData['payment_terms_days'],
                    'notes' => $sData['notes'],
                    'is_active' => $sData['is_active'],
                ]
            );
            $supplierMap[$sData['code']] = $supplier;
        }

        // Materials lookup
        $susu = RawMaterial::where('code', 'BB-SSU-01')->first();
        $wijen = RawMaterial::where('code', 'BB-WJN-01')->first();
        $tepung = RawMaterial::where('code', 'BB-TPG-01')->first();
        $gula = RawMaterial::where('code', 'BB-GLA-01')->first();
        $pouch = RawMaterial::whereIn('code', ['KM-PCH-250', 'KM-PCH-01'])->first();
        $stiker = RawMaterial::where('code', 'KM-LBL-01')->first();

        // 1. PO-1 (Received)
        if (isset($supplierMap['SPL-001']) && $susu && $gula) {
            $po1 = PurchaseOrder::updateOrCreate(
                ['po_number' => 'PO-20261001-0001'],
                [
                    'supplier_id' => $supplierMap['SPL-001']->id,
                    'order_date' => Carbon::now()->subDays(5),
                    'due_date' => Carbon::now()->addDays(25),
                    'status' => 'received',
                    'payment_status' => 'unpaid',
                    'total_amount' => (25000 * 95.00) + (20000 * 17.50), // 25kg susu + 20kg gula
                    'received_at' => Carbon::now()->subDays(2),
                    'receiver_user_id' => $admin?->id,
                    'notes' => 'Pengadaan rutin bahan baku susu bubuk dan gula karamelisasi dapur.',
                    'created_by' => $manager?->id,
                ]
            );

            $po1->items()->delete();
            $po1->items()->create([
                'raw_material_id' => $susu->id,
                'qty_ordered' => 25000,
                'qty_received' => 25000,
                'unit_price' => 95.00,
                'subtotal' => 25000 * 95.00,
            ]);
            $po1->items()->create([
                'raw_material_id' => $gula->id,
                'qty_ordered' => 20000,
                'qty_received' => 20000,
                'unit_price' => 17.50,
                'subtotal' => 20000 * 17.50,
            ]);
        }

        // 2. PO-2 (Ordered - On the way)
        if (isset($supplierMap['SPL-002']) && $wijen) {
            $po2 = PurchaseOrder::updateOrCreate(
                ['po_number' => 'PO-20261001-0002'],
                [
                    'supplier_id' => $supplierMap['SPL-002']->id,
                    'order_date' => Carbon::now()->subDays(1),
                    'due_date' => Carbon::now()->addDays(13),
                    'status' => 'ordered',
                    'payment_status' => 'unpaid',
                    'total_amount' => 30000 * 65.00, // 30kg wijen
                    'received_at' => null,
                    'receiver_user_id' => null,
                    'notes' => 'Pemesanan biji wijen sangrai stok cadangan produksi Marie Wijen.',
                    'created_by' => $manager?->id,
                ]
            );

            $po2->items()->delete();
            $po2->items()->create([
                'raw_material_id' => $wijen->id,
                'qty_ordered' => 30000,
                'qty_received' => 0,
                'unit_price' => 65.00,
                'subtotal' => 30000 * 65.00,
            ]);
        }

        // 3. PO-3 (Draft)
        if (isset($supplierMap['SPL-004']) && $pouch && $stiker) {
            $po3 = PurchaseOrder::updateOrCreate(
                ['po_number' => 'PO-20261001-0003'],
                [
                    'supplier_id' => $supplierMap['SPL-004']->id,
                    'order_date' => Carbon::now(),
                    'due_date' => Carbon::now()->addDays(7),
                    'status' => 'draft',
                    'payment_status' => 'unpaid',
                    'total_amount' => (500 * 750.00) + (1000 * 350.00),
                    'received_at' => null,
                    'receiver_user_id' => null,
                    'notes' => 'Rencana pengadaan kemasan standing pouch & stiker label gold.',
                    'created_by' => $manager?->id,
                ]
            );

            $po3->items()->delete();
            $po3->items()->create([
                'raw_material_id' => $pouch->id,
                'qty_ordered' => 500,
                'qty_received' => 0,
                'unit_price' => 750.00,
                'subtotal' => 500 * 750.00,
            ]);
            $po3->items()->create([
                'raw_material_id' => $stiker->id,
                'qty_ordered' => 1000,
                'qty_received' => 0,
                'unit_price' => 350.00,
                'subtotal' => 1000 * 350.00,
            ]);
        }
    }
}
