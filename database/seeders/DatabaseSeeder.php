<?php

namespace Database\Seeders;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Models\StockMutation;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with 3 core roles: dev, manager, and kurir.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Core Permission Groups & Permissions
        $groups = [
            'Dashboard' => [
                'description' => 'Akses ringkasan metrik performa toko dan operasional.',
                'permissions' => [
                    'dashboard-view' => 'Melihat dashboard utama dan ringkasan metrik performa.',
                ],
            ],
            'Pengguna & Hak Akses' => [
                'description' => 'Kelola akun pengguna, hierarki 3 role (dev, manager, kurir), dan konfigurasi izin akses.',
                'permissions' => [
                    'user-manage' => 'Kelola akun staf dan pengguna sistem.',
                    'role-manage' => 'Kelola data role jabatan dan wewenang.',
                    'permission-manage' => 'Kelola izin dan hak akses teknis aplikasi.',
                ],
            ],
            'Master Satuan' => [
                'description' => 'Kelola data master satuan pengukuran produk dan bahan baku.',
                'permissions' => [
                    'satuan-view' => 'Melihat daftar master satuan.',
                    'satuan-create' => 'Menambahkan data satuan baru.',
                    'satuan-edit' => 'Mengubah data master satuan.',
                    'satuan-delete' => 'Menghapus data master satuan.',
                ],
            ],
            'Master Produk Jadi' => [
                'description' => 'Kelola katalog produk jadi, harga setor konsinyasi, harga eceran toko, dan stok gudang.',
                'permissions' => [
                    'produk-view' => 'Melihat katalog produk jadi dan daftar harga.',
                    'produk-create' => 'Menambahkan produk jadi baru.',
                    'produk-edit' => 'Mengubah data produk jadi, harga setor, dan status.',
                    'produk-delete' => 'Menghapus data produk jadi.',
                ],
            ],
            'Bahan Baku & Resep' => [
                'description' => 'Kelola inventaris bahan baku, batas minimum stok, dan formula resep produk (BOM).',
                'permissions' => [
                    'bahan-baku-view' => 'Melihat daftar stok bahan baku dan formula resep produk.',
                    'bahan-baku-create' => 'Menambahkan data bahan baku baru.',
                    'bahan-baku-edit' => 'Mengubah data bahan baku dan stok.',
                    'bahan-baku-delete' => 'Menghapus data bahan baku.',
                    'resep-manage' => 'Mengatur formula resep produk (Bill of Material / BOM).',
                ],
            ],
            'Produksi & Manufaktur' => [
                'description' => 'Kelola eksekusi batch masak dapur, konversi bahan baku ke barang jadi, dan mutasi kartu stok.',
                'permissions' => [
                    'produksi-view' => 'Melihat daftar riwayat batch masak dan kartu stok.',
                    'produksi-create' => 'Membuat dan mengeksekusi batch produksi baru.',
                    'produksi-edit' => 'Mengubah catatan atau status batch produksi.',
                    'produksi-delete' => 'Membatalkan atau menghapus catatan batch produksi.',
                ],
            ],
            'Mitra Toko' => [
                'description' => 'Kelola direktori mitra toko, rute pengantaran kurir, dan komisi titip jual.',
                'permissions' => [
                    'toko-view' => 'Melihat daftar toko mitra dan rute pengantaran.',
                    'toko-create' => 'Menambahkan data toko mitra baru.',
                    'toko-edit' => 'Mengubah data toko mitra.',
                    'toko-delete' => 'Menghapus data toko mitra.',
                ],
            ],
            'Pengantaran & Distribusi' => [
                'description' => 'Kelola surat jalan pengantaran barang jadi ke toko mitra dan tracking kurir.',
                'permissions' => [
                    'pengantaran-view' => 'Melihat daftar surat jalan dan tugas pengantaran.',
                    'pengantaran-create' => 'Membuat surat jalan pengantaran baru.',
                    'pengantaran-edit' => 'Mengubah surat jalan atau memperbarui status pengantaran.',
                    'pengantaran-delete' => 'Membatalkan atau menghapus surat jalan.',
                ],
            ],
            'Faktur & Piutang Toko' => [
                'description' => 'Kelola faktur tagihan konsinyasi mitra toko, jatuh tempo piutang, dan pencatatan pembayaran pelunasan.',
                'permissions' => [
                    'faktur-view' => 'Melihat daftar faktur tagihan dan kartu piutang toko mitra.',
                    'faktur-create' => 'Membuat faktur tagihan konsinyasi baru.',
                    'faktur-edit' => 'Mengubah data faktur tagihan atau mencatat pembayaran pelunasan.',
                    'faktur-delete' => 'Membatalkan atau menghapus faktur tagihan.',
                ],
            ],
            'Pengaturan Usaha' => [
                'description' => 'Kelola profil usaha, kontak resmi, rekening bank pembayaran, dan format cetak dokumen.',
                'permissions' => [
                    'pengaturan-view' => 'Melihat profil usaha dan rekening pembayaran.',
                    'pengaturan-edit' => 'Mengubah profil usaha, kontak, dan rekening pembayaran.',
                ],
            ],
        ];

        // Track valid active permission names
        $validPermissionNames = [];

        foreach ($groups as $groupName => $groupData) {
            $group = PermissionGroup::updateOrCreate(
                ['name' => $groupName],
                ['description' => $groupData['description']]
            );

            foreach ($groupData['permissions'] as $permissionName => $permissionDesc) {
                $validPermissionNames[] = $permissionName;

                Permission::updateOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['permission_group_id' => $group->id]
                );
            }
        }

        // Delete obsolete permissions that are no longer part of the system
        $obsoletePermissions = Permission::whereNotIn('name', $validPermissionNames)->get();
        foreach ($obsoletePermissions as $obsPerm) {
            $obsPerm->roles()->detach();
            $obsPerm->users()->detach();
            $obsPerm->delete();
        }

        // Delete empty obsolete permission groups
        $validGroupNames = array_keys($groups);
        PermissionGroup::whereNotIn('name', $validGroupNames)->each(function ($grp) {
            if ($grp->permissions()->count() === 0) {
                $grp->delete();
            }
        });

        // 2. Define exactly 3 Core Roles: dev, manager, and kurir
        $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $kurirRole = Role::firstOrCreate(['name' => 'kurir', 'guard_name' => 'web']);

        // Delete any obsolete roles not in ['dev', 'manager', 'kurir']
        Role::whereNotIn('name', ['dev', 'manager', 'kurir'])->get()->each(function ($role) {
            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();
        });

        // 3. Assign Permissions to Roles
        // Dev / Super Admin gets all permissions
        $devRole->syncPermissions(Permission::all());

        // Manager gets operational management + Bahan Baku, Resep, Produksi, Toko, Pengantaran & Faktur permissions
        $managerRole->syncPermissions([
            'dashboard-view',
            'user-manage',
            'role-manage',
            'permission-manage',
            'bahan-baku-view',
            'bahan-baku-create',
            'bahan-baku-edit',
            'bahan-baku-delete',
            'resep-manage',
            'produksi-view',
            'produksi-create',
            'produksi-edit',
            'produksi-delete',
            'toko-view',
            'toko-create',
            'toko-edit',
            'toko-delete',
            'pengantaran-view',
            'pengantaran-create',
            'pengantaran-edit',
            'pengantaran-delete',
            'faktur-view',
            'faktur-create',
            'faktur-edit',
            'faktur-delete',
            'pengaturan-view',
            'pengaturan-edit',
            'produk-view',
            'produk-create',
            'produk-edit',
            'produk-delete',
        ]);

        // Kurir gets dashboard access, toko view, pengantaran, and faktur view
        $kurirRole->syncPermissions([
            'dashboard-view',
            'toko-view',
            'pengantaran-view',
            'pengantaran-edit',
            'faktur-view',
        ]);

        // 4. Seed Standard Users for the 3 Roles
        $users = [
            [
                'email' => 'developer@halala-food.id',
                'name' => 'Developer Super Admin',
                'phone' => '081234567890',
                'role' => $devRole,
            ],
            [
                'email' => 'admin@halala-food.id',
                'name' => 'Administrator Halala',
                'phone' => '081288889999',
                'role' => $devRole,
            ],
            [
                'email' => 'manager@halala-food.id',
                'name' => 'Ibu Dewi Lestari (Manager)',
                'phone' => '081299887766',
                'role' => $managerRole,
            ],
            [
                'email' => 'kurir@halala-food.id',
                'name' => 'Budi Pratama (Kurir)',
                'phone' => '089612348765',
                'role' => $kurirRole,
            ],
        ];

        // Clean up users not in the new standard list
        $activeEmails = array_column($users, 'email');
        User::whereNotIn('email', $activeEmails)->delete();

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'phone' => User::normalizePhone($userData['phone']),
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$userData['role']]);
        }

        // 5. Seed Standard Master Units
        $defaultUnits = [
            ['name' => 'Kilogram', 'short_name' => 'kg', 'description' => 'Satuan berat standar (1.000 gram)', 'is_active' => true],
            ['name' => 'Gram', 'short_name' => 'g', 'description' => 'Satuan berat baku resep produksi', 'is_active' => true],
            ['name' => 'Liter', 'short_name' => 'l', 'description' => 'Satuan volume cairan', 'is_active' => true],
            ['name' => 'Mililiter', 'short_name' => 'ml', 'description' => 'Satuan volume cairan resep', 'is_active' => true],
            ['name' => 'Pieces', 'short_name' => 'pcs', 'description' => 'Satuan hitungan per kemasan atau butir', 'is_active' => true],
            ['name' => 'Bungkus', 'short_name' => 'bungkus', 'description' => 'Satuan kemasan kantong atau pouch', 'is_active' => true],
            ['name' => 'Toples', 'short_name' => 'toples', 'description' => 'Satuan wadah toples mika', 'is_active' => true],
            ['name' => 'Lembar', 'short_name' => 'lembar', 'description' => 'Satuan stiker, segel, dan label kemasan', 'is_active' => true],
        ];

        foreach ($defaultUnits as $unitData) {
            Unit::firstOrCreate(
                ['short_name' => $unitData['short_name']],
                $unitData
            );
        }

        // 6. Seed Sample Products & Dynamic Unit Linking
        $bungkusUnit = Unit::where('short_name', 'bungkus')->first();

        $marieWijen = Product::firstOrCreate(
            ['name' => 'Marie Wijen'],
            [
                'unit_id' => $bungkusUnit?->id,
                'unit' => 'bungkus',
                'consignment_price' => 12000.00,
                'retail_price' => 15000.00,
                'stock_ready' => 50,
                'description' => 'Biskuit Marie salut gula karamel wijen panggang renyah kemasan pouch.',
                'is_active' => true,
            ]
        );

        $tingTingSusu = Product::firstOrCreate(
            ['name' => 'Ting-Ting Susu'],
            [
                'unit_id' => $bungkusUnit?->id,
                'unit' => 'bungkus',
                'consignment_price' => 10000.00,
                'retail_price' => 13000.00,
                'stock_ready' => 40,
                'description' => 'Enting-enting kacang tanah olahan susu manis gurih kemasan pouch.',
                'is_active' => true,
            ]
        );

        // 7. Seed Sample Raw Materials linking dynamically to Units
        $gramUnit = Unit::where('short_name', 'g')->first();
        $mlUnit = Unit::where('short_name', 'ml')->first();
        $lembarUnit = Unit::where('short_name', 'lembar')->first();

        $materials = [
            ['name' => 'Wijen Putih Sangrai', 'unit_id' => $gramUnit?->id, 'unit' => 'g', 'stock' => 15000.00, 'min_stock' => 3000.00, 'cost_per_unit' => 65.00],
            ['name' => 'Gula Pasir Kristal', 'unit_id' => $gramUnit?->id, 'unit' => 'g', 'stock' => 25000.00, 'min_stock' => 5000.00, 'cost_per_unit' => 17.50],
            ['name' => 'Kacang Tanah Kupas', 'unit_id' => $gramUnit?->id, 'unit' => 'g', 'stock' => 12000.00, 'min_stock' => 2500.00, 'cost_per_unit' => 35.00],
            ['name' => 'Susu Kental Manis', 'unit_id' => $mlUnit?->id, 'unit' => 'ml', 'stock' => 8000.00, 'min_stock' => 1500.00, 'cost_per_unit' => 28.00],
            ['name' => 'Plastik Pouch Zipper & Stiker', 'unit_id' => $lembarUnit?->id, 'unit' => 'lembar', 'stock' => 500.00, 'min_stock' => 100.00, 'cost_per_unit' => 850.00],
        ];

        $materialModels = [];
        foreach ($materials as $mat) {
            $materialModels[$mat['name']] = RawMaterial::firstOrCreate(['name' => $mat['name']], $mat);
        }

        // 8. Seed Recipes (BOM)
        if ($marieWijen->recipes()->count() === 0) {
            ProductRecipe::create(['product_id' => $marieWijen->id, 'raw_material_id' => $materialModels['Wijen Putih Sangrai']->id, 'quantity_needed' => 35]);
            ProductRecipe::create(['product_id' => $marieWijen->id, 'raw_material_id' => $materialModels['Gula Pasir Kristal']->id, 'quantity_needed' => 40]);
            ProductRecipe::create(['product_id' => $marieWijen->id, 'raw_material_id' => $materialModels['Plastik Pouch Zipper & Stiker']->id, 'quantity_needed' => 1]);
        }

        if ($tingTingSusu->recipes()->count() === 0) {
            ProductRecipe::create(['product_id' => $tingTingSusu->id, 'raw_material_id' => $materialModels['Kacang Tanah Kupas']->id, 'quantity_needed' => 45]);
            ProductRecipe::create(['product_id' => $tingTingSusu->id, 'raw_material_id' => $materialModels['Gula Pasir Kristal']->id, 'quantity_needed' => 25]);
            ProductRecipe::create(['product_id' => $tingTingSusu->id, 'raw_material_id' => $materialModels['Susu Kental Manis']->id, 'quantity_needed' => 15]);
            ProductRecipe::create(['product_id' => $tingTingSusu->id, 'raw_material_id' => $materialModels['Plastik Pouch Zipper & Stiker']->id, 'quantity_needed' => 1]);
        }

        // 9. Seed Sample Initial Completed Production Batch
        $devUser = User::where('email', 'developer@halala-food.id')->first();
        if ($devUser && ProductionBatch::count() === 0) {
            $batchCode = 'PRD-'.now()->format('Ymd').'-0001';
            $sampleBatch = ProductionBatch::create([
                'batch_code' => $batchCode,
                'product_id' => $tingTingSusu->id,
                'user_id' => $devUser->id,
                'planned_qty' => 50,
                'actual_qty_good' => 48,
                'actual_qty_bad' => 2,
                'total_material_cost' => 164125.00,
                'unit_cost_produced' => 3419.27,
                'status' => 'completed',
                'notes' => 'Batch perdana percontohan Ting-Ting Susu Halala. 2 bungkus reject saat sealing kemasan.',
                'started_at' => now()->subHours(3),
                'completed_at' => now()->subHours(1),
            ]);

            $tingTingSusu->increment('stock_ready', 48);

            // Snapshot materials used
            foreach ($tingTingSusu->recipes as $recipe) {
                $rawMat = $recipe->rawMaterial;
                $usedQty = (float) $recipe->quantity_needed * 50;
                $costPerUnit = (float) ($rawMat?->cost_per_unit ?? 0);
                $subtotal = $usedQty * $costPerUnit;

                ProductionBatchMaterial::create([
                    'production_batch_id' => $sampleBatch->id,
                    'raw_material_id' => $recipe->raw_material_id,
                    'unit_name' => $rawMat?->display_unit ?? 'gram',
                    'planned_qty' => $usedQty,
                    'actual_used_qty' => $usedQty,
                    'cost_per_unit' => $costPerUnit,
                    'subtotal_cost' => $subtotal,
                ]);

                // Create stock mutation entry
                if ($rawMat) {
                    StockMutation::create([
                        'raw_material_id' => $rawMat->id,
                        'reference_type' => 'production',
                        'reference_id' => $sampleBatch->id,
                        'reference_number' => $batchCode,
                        'type' => 'out',
                        'quantity' => $usedQty,
                        'stock_before' => (float) $rawMat->stock + $usedQty,
                        'stock_after' => (float) $rawMat->stock,
                        'cost_per_unit' => $costPerUnit,
                        'notes' => "Alokasi bahan untuk {$batchCode} ({$tingTingSusu->name} x 50)",
                        'user_id' => $devUser->id,
                    ]);
                }
            }
        }

        // 8. Seed Sample Partner Stores
        $sampleStores = [
            [
                'name' => 'Pusat Oleh-Oleh Barokah',
                'owner_name' => 'Ibu Hj. Aminah',
                'phone' => '081234567890',
                'address' => 'Jl. Raya Pasar Besar No. 12, Kota Malang',
                'latitude' => -7.98680000,
                'longitude' => 112.63150000,
                'route' => 'Rute Pasar Besar',
                'is_active' => true,
                'notes' => 'Rak display kaca dekat kasir utama. Jadwal pengantaran tiap hari Sabtu pagi.',
            ],
            [
                'name' => 'Toko Snack Berkah Jaya',
                'owner_name' => 'Pak Bambang',
                'phone' => '085798765432',
                'address' => 'Jl. Ahmad Yani No. 45, Klojen',
                'latitude' => -7.95420000,
                'longitude' => 112.63980000,
                'route' => 'Rute Kota',
                'is_active' => true,
                'notes' => 'Titip di etalase depan. Penagihan konsinyasi tiap awal bulan.',
            ],
            [
                'name' => 'Supermarket Harmoni Jaya',
                'owner_name' => 'Bpk. Hendra Wijaya',
                'phone' => '081377889900',
                'address' => 'Jl. Jenderal Sudirman No. 88, Kav. 3',
                'latitude' => -7.97850000,
                'longitude' => 112.63500000,
                'route' => 'Rute Supermarket',
                'is_active' => true,
                'notes' => 'Penerimaan barang di loading dock belakang pkl 08.00-11.00 WIB.',
            ],
        ];

        foreach ($sampleStores as $storeData) {
            Store::firstOrCreate(
                ['name' => $storeData['name']],
                $storeData
            );
        }

        // 9. Seed Sample Delivery (Surat Jalan Pengantaran)
        $barokahStore = Store::where('name', 'Pusat Oleh-Oleh Barokah')->first();
        $kurirUser = User::where('email', 'kurir@halala-food.id')->first();
        $managerUser = User::where('email', 'manager@halala-food.id')->first();

        if ($barokahStore && $kurirUser && Delivery::count() === 0) {
            $delivery = Delivery::create([
                'delivery_number' => 'SJ-' . now()->format('Ymd') . '-0001',
                'store_id' => $barokahStore->id,
                'courier_id' => $kurirUser->id,
                'created_by' => $managerUser?->id ?? $devUser?->id,
                'delivery_date' => now()->toDateString(),
                'status' => 'diproses',
                'notes' => 'Harap konfirmasi dengan Ibu Hj. Aminah saat tiba di toko.',
                'total_items' => 25,
                'total_amount' => 280000.00,
            ]);

            DeliveryItem::create([
                'delivery_id' => $delivery->id,
                'product_id' => $marieWijen->id,
                'quantity' => 15,
                'unit_price' => $marieWijen->consignment_price,
                'subtotal' => 15 * (float) $marieWijen->consignment_price,
                'notes' => '15 bungkus kemasan pouch',
            ]);

            DeliveryItem::create([
                'delivery_id' => $delivery->id,
                'product_id' => $tingTingSusu->id,
                'quantity' => 10,
                'unit_price' => $tingTingSusu->consignment_price,
                'subtotal' => 10 * (float) $tingTingSusu->consignment_price,
                'notes' => '10 bungkus kemasan pouch',
            ]);

            // Decrement ready stock for initial delivery demo
            $marieWijen->decrement('stock_ready', 15);
            $tingTingSusu->decrement('stock_ready', 10);
        }

        // 10. Seed Sample Invoice & Payment (Faktur Konsinyasi & Piutang)
        if ($barokahStore && Invoice::count() === 0) {
            $sampleDelivery = Delivery::first();
            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . now()->format('Ymd') . '-0001',
                'delivery_id' => $sampleDelivery?->id,
                'store_id' => $barokahStore->id,
                'created_by' => $managerUser?->id ?? $devUser?->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'subtotal' => 280000.00,
                'discount' => 0.00,
                'total_amount' => 280000.00,
                'paid_amount' => 100000.00,
                'remaining_balance' => 180000.00,
                'status' => 'sebagian',
                'notes' => 'Faktur titip jual konsinyasi batch perdana. Pembayaran termin 1 diterima transfer Rp 100.000.',
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $marieWijen->id,
                'quantity' => 15,
                'unit_price' => $marieWijen->consignment_price,
                'subtotal' => 15 * (float) $marieWijen->consignment_price,
                'notes' => '15 bungkus kemasan pouch',
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $tingTingSusu->id,
                'quantity' => 10,
                'unit_price' => $tingTingSusu->consignment_price,
                'subtotal' => 10 * (float) $tingTingSusu->consignment_price,
                'notes' => '10 bungkus kemasan pouch',
            ]);

            InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'payment_number' => 'PAY-' . now()->format('Ymd') . '-0001',
                'user_id' => $managerUser?->id ?? $devUser?->id,
                'payment_date' => now()->toDateString(),
                'amount' => 100000.00,
                'payment_method' => 'transfer_bank',
                'reference_number' => 'BCA-TRX-882910',
                'notes' => 'Pembayaran termin 1 via transfer bank BCA oleh Ibu Hj. Aminah.',
            ]);
        }

        // Initialize default business settings if not already present
        \App\Models\BusinessSetting::getSettings();
    }
}
