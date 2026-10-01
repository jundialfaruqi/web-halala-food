<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
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
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Standard Permission Groups & Permissions
        $groups = [
            'Dashboard' => [
                'description' => 'Akses ringkasan metrik performa toko, penjualan, dan keuangan.',
                'permissions' => [
                    'dashboard-view' => 'Melihat dashboard utama dan ringkasan metrik performa.',
                ],
            ],
            'Pengguna & Hak Akses' => [
                'description' => 'Kelola akun staf, hierarki role pengguna, dan konfigurasi hak akses.',
                'permissions' => [
                    'user-manage' => 'Kelola akun staf dan pengguna sistem.',
                    'role-manage' => 'Kelola data role jabatan dan wewenang.',
                    'permission-manage' => 'Kelola izin dan hak akses teknis aplikasi.',
                ],
            ],
            'Master Produk & Resep' => [
                'description' => 'Kelola kategori camilan (Marie Wijen & Ting-Ting Susu), varian kemasan, dan formula resep (BOM).',
                'permissions' => [
                    'category-manage' => 'Kelola kategori produk makanan.',
                    'product-manage' => 'Kelola master produk, varian kemasan pouch/toples/ecer, harga jual, dan barcode.',
                    'recipe-manage' => 'Kelola formula resep standar (Bill of Materials) per varian produk.',
                ],
            ],
            'Bahan Baku & Inventori Gudang' => [
                'description' => 'Kelola master bahan baku, kartu stok gudang, pengadaan PO supplier, dan pencatatan bahan rusak.',
                'permissions' => [
                    'raw-material-manage' => 'Kelola data master bahan baku dan stok minimum.',
                    'stock-manage' => 'Melihat kartu stok bahan & barang jadi, penyesuaian stok opname.',
                    'waste-manage' => 'Pencatatan dan audit bahan baku rusak/kedaluwarsa (waste tracking).',
                    'purchase-manage' => 'Pengadaan dan pembelian bahan baku ke supplier.',
                ],
            ],
            'Dapur & Produksi Manufaktur' => [
                'description' => 'Akses rencana produksi harian, eksekusi batch masak dapur, dan konversi bahan baku ke produk jadi.',
                'permissions' => [
                    'production-manage' => 'Kelola rencana dan eksekusi batch produksi masak dapur.',
                ],
            ],
            'Mitra Toko & Distribusi Kurir' => [
                'description' => 'Kelola mitra supermarket / kelontong, surat jalan pengantaran, dan pelacakan kurir.',
                'permissions' => [
                    'partner-manage' => 'Kelola direktori mitra toko, batas piutang, dan harga khusus.',
                    'delivery-manage' => 'Surat jalan digital, penugasan kurir, dan konfirmasi serah terima toko.',
                ],
            ],
            'Point of Sale (POS) & Kasir' => [
                'description' => 'Layar kasir penjualan ecer/grosir, scan barcode kemasan, cetak struk, dan buka/tutup shift kasir.',
                'permissions' => [
                    'pos-manage' => 'Akses kasir POS dan transaksi penjualan toko.',
                    'cash-register-manage' => 'Buka dan tutup shift laci kasir serta rekonsiliasi kas.',
                ],
            ],
            'Keuangan & Akuntansi Otomatis' => [
                'description' => 'Bagan akun (COA), jurnal umum otomatis, buku besar, neraca saldo, laba rugi, arus kas, dan kas operasional.',
                'permissions' => [
                    'financial-report-view' => 'Akses laporan laba rugi, neraca, arus kas, dan aging piutang toko.',
                    'journal-manage' => 'Akses jurnal umum otomatis dan input jurnal penyesuaian.',
                    'ledger-manage' => 'Akses buku besar (General Ledger) dan neraca saldo.',
                    'coa-manage' => 'Kelola bagan akun (Chart of Accounts) dan aturan mapping auto-jurnal.',
                    'cash-transaction-manage' => 'Pencatatan kas masuk dan kas keluar operasional non-penjualan.',
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

        // 2. Create the 6 Standard System Roles
        $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
        $ceoRole = Role::firstOrCreate(['name' => 'ceo', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $masakRole = Role::firstOrCreate(['name' => 'tukang masak', 'guard_name' => 'web']);
        $kasirRole = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
        $kurirRole = Role::firstOrCreate(['name' => 'kurir', 'guard_name' => 'web']);

        // 3. Assign Granular Permissions to Roles
        // Developer / Super Admin gets everything
        $devRole->syncPermissions(Permission::all());

        // CEO / Owner
        $ceoRole->syncPermissions([
            'dashboard-view',
            'category-manage',
            'product-manage',
            'recipe-manage',
            'partner-manage',
            'delivery-manage',
            'financial-report-view',
            'journal-manage',
            'ledger-manage',
            'coa-manage',
            'cash-transaction-manage',
            'user-manage',
        ]);

        // Manager
        $managerRole->syncPermissions([
            'dashboard-view',
            'category-manage',
            'product-manage',
            'recipe-manage',
            'raw-material-manage',
            'stock-manage',
            'waste-manage',
            'purchase-manage',
            'production-manage',
            'partner-manage',
            'delivery-manage',
            'pos-manage',
            'cash-register-manage',
            'financial-report-view',
            'journal-manage',
            'ledger-manage',
            'cash-transaction-manage',
            'user-manage',
        ]);

        // Tukang Masak (Kitchen & Production)
        $masakRole->syncPermissions([
            'dashboard-view',
            'production-manage',
            'stock-manage',
            'waste-manage',
            'recipe-manage',
        ]);

        // Kasir (POS & Cash Register)
        $kasirRole->syncPermissions([
            'dashboard-view',
            'pos-manage',
            'cash-register-manage',
        ]);

        // Kurir (Delivery)
        $kurirRole->syncPermissions([
            'dashboard-view',
            'delivery-manage',
        ]);

        // 4. Seed Standard Users for Each Role
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
                'email' => 'ceo@halala-food.id',
                'name' => 'Bpk. Rahmat (CEO)',
                'phone' => '081311223344',
                'role' => $ceoRole,
            ],
            [
                'email' => 'manager@halala-food.id',
                'name' => 'Ibu Dewi Lestari (Manager)',
                'phone' => '081299887766',
                'role' => $managerRole,
            ],
            [
                'email' => 'masak@halala-food.id',
                'name' => 'Pak Joko (Tukang Masak)',
                'phone' => '085711224466',
                'role' => $masakRole,
            ],
            [
                'email' => 'kasir@halala-food.id',
                'name' => 'Siti Rahayu (Kasir)',
                'phone' => '087812345678',
                'role' => $kasirRole,
            ],
            [
                'email' => 'kurir@halala-food.id',
                'name' => 'Budi Pratama (Kurir)',
                'phone' => '089612348765',
                'role' => $kurirRole,
            ],
        ];

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

        // 5. Seed Fase 1: Products, Variants, Raw Materials, and Recipes
        $this->call(ProductAndRecipeSeeder::class);

        // 6. Seed Fase 2: Production Batches, Stock Mutations, and Waste Logs
        $this->call(ProductionAndStockSeeder::class);
    }
}


