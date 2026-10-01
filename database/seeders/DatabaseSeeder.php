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

        // 1. Create Permission Groups
        $groups = [
            'Dashboard' => [
                'description' => 'Akses halaman utama dan ringkasan metrik performa toko.',
                'permissions' => ['dashboard-view'],
            ],
            'Pengguna & Akses' => [
                'description' => 'Kelola akun staf, role pengguna, dan konfigurasi hak akses.',
                'permissions' => ['user-manage', 'role-manage', 'permission-manage'],
            ],
            'Keuangan & Laporan' => [
                'description' => 'Akses laporan omzet, rekap penjualan, dan pembukuan.',
                'permissions' => ['financial-report-view'],
            ],
            'Produk & Menu' => [
                'description' => 'Kelola katalog camilan (Marie Wijen & Ting-Ting Susu) dan stok.',
                'permissions' => ['product-manage'],
            ],
            'Dapur & Produksi' => [
                'description' => 'Akses pesanan masuk, antrean penggorengan, dan pengemasan.',
                'permissions' => ['kitchen-manage'],
            ],
            'Pesanan & Transaksi' => [
                'description' => 'Kelola pesanan pelanggan dan konfirmasi pembelian.',
                'permissions' => ['order-manage'],
            ],
            'Kasir & Pembayaran' => [
                'description' => 'Proses transaksi kasir dan konfirmasi penerimaan uang.',
                'permissions' => ['payment-process'],
            ],
            'Pengiriman & Kurir' => [
                'description' => 'Kelola rute pengantaran pesanan dan kurir.',
                'permissions' => ['delivery-manage'],
            ],
        ];

        foreach ($groups as $groupName => $groupData) {
            $group = PermissionGroup::firstOrCreate(
                ['name' => $groupName],
                ['description' => $groupData['description']]
            );

            foreach ($groupData['permissions'] as $permissionName) {
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['permission_group_id' => $group->id]
                );
                // Ensure permission group id is set if already existed
                Permission::where('name', $permissionName)->update(['permission_group_id' => $group->id]);
            }
        }

        // 2. Create the 6 Roles
        $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
        $ceoRole = Role::firstOrCreate(['name' => 'ceo', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $masakRole = Role::firstOrCreate(['name' => 'tukang masak', 'guard_name' => 'web']);
        $kasirRole = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
        $kurirRole = Role::firstOrCreate(['name' => 'kurir', 'guard_name' => 'web']);

        // 3. Assign Permissions to Roles
        $devRole->syncPermissions(Permission::all());

        $ceoRole->syncPermissions([
            'dashboard-view',
            'financial-report-view',
            'user-manage',
            'product-manage',
            'order-manage',
        ]);

        $managerRole->syncPermissions([
            'dashboard-view',
            'financial-report-view',
            'user-manage',
            'product-manage',
            'order-manage',
            'kitchen-manage',
            'delivery-manage',
        ]);

        $masakRole->syncPermissions([
            'dashboard-view',
            'kitchen-manage',
            'product-manage',
        ]);

        $kasirRole->syncPermissions([
            'dashboard-view',
            'order-manage',
            'payment-process',
        ]);

        $kurirRole->syncPermissions([
            'dashboard-view',
            'delivery-manage',
        ]);

        // 4. Seed Users for Each Role
        $users = [
            [
                'email' => 'developer@halala-food.id',
                'name' => 'Developer Super Admin',
                'role' => $devRole,
            ],
            [
                'email' => 'admin@halala-food.id',
                'name' => 'Administrator Halala',
                'role' => $devRole,
            ],
            [
                'email' => 'ceo@halala-food.id',
                'name' => 'Bpk. Rahmat (CEO)',
                'role' => $ceoRole,
            ],
            [
                'email' => 'manager@halala-food.id',
                'name' => 'Ibu Dewi Lestari (Manager)',
                'role' => $managerRole,
            ],
            [
                'email' => 'masak@halala-food.id',
                'name' => 'Pak Joko (Tukang Masak)',
                'role' => $masakRole,
            ],
            [
                'email' => 'kasir@halala-food.id',
                'name' => 'Siti Rahayu (Kasir)',
                'role' => $kasirRole,
            ],
            [
                'email' => 'kurir@halala-food.id',
                'name' => 'Budi Pratama (Kurir)',
                'role' => $kurirRole,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$userData['role']]);
        }
    }
}


