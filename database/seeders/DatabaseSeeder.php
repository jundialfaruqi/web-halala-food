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

        // Manager gets operational management + Bahan Baku & Resep permissions
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
        ]);

        // Kurir gets dashboard access
        $kurirRole->syncPermissions([
            'dashboard-view',
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
            \App\Models\Unit::firstOrCreate(
                ['short_name' => $unitData['short_name']],
                $unitData
            );
        }

        // 6. Seed Sample Products & Dynamic Unit Linking
        $bungkusUnit = \App\Models\Unit::where('short_name', 'bungkus')->first();

        $marieWijen = \App\Models\Product::firstOrCreate(
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

        $tingTingSusu = \App\Models\Product::firstOrCreate(
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
        $gramUnit = \App\Models\Unit::where('short_name', 'g')->first();
        $mlUnit = \App\Models\Unit::where('short_name', 'ml')->first();
        $lembarUnit = \App\Models\Unit::where('short_name', 'lembar')->first();

        $materials = [
            ['name' => 'Wijen Putih Sangrai', 'unit_id' => $gramUnit?->id, 'unit' => 'g', 'stock' => 15000.00, 'min_stock' => 3000.00, 'cost_per_unit' => 65.00],
            ['name' => 'Gula Pasir Kristal', 'unit_id' => $gramUnit?->id, 'unit' => 'g', 'stock' => 25000.00, 'min_stock' => 5000.00, 'cost_per_unit' => 17.50],
            ['name' => 'Kacang Tanah Kupas', 'unit_id' => $gramUnit?->id, 'unit' => 'g', 'stock' => 12000.00, 'min_stock' => 2500.00, 'cost_per_unit' => 35.00],
            ['name' => 'Susu Kental Manis', 'unit_id' => $mlUnit?->id, 'unit' => 'ml', 'stock' => 8000.00, 'min_stock' => 1500.00, 'cost_per_unit' => 28.00],
            ['name' => 'Plastik Pouch Zipper & Stiker', 'unit_id' => $lembarUnit?->id, 'unit' => 'lembar', 'stock' => 500.00, 'min_stock' => 100.00, 'cost_per_unit' => 850.00],
        ];

        $materialModels = [];
        foreach ($materials as $mat) {
            $materialModels[$mat['name']] = \App\Models\RawMaterial::firstOrCreate(['name' => $mat['name']], $mat);
        }

        // 8. Seed Recipes (BOM)
        if ($marieWijen->recipes()->count() === 0) {
            \App\Models\ProductRecipe::create(['product_id' => $marieWijen->id, 'raw_material_id' => $materialModels['Wijen Putih Sangrai']->id, 'quantity_needed' => 35]);
            \App\Models\ProductRecipe::create(['product_id' => $marieWijen->id, 'raw_material_id' => $materialModels['Gula Pasir Kristal']->id, 'quantity_needed' => 40]);
            \App\Models\ProductRecipe::create(['product_id' => $marieWijen->id, 'raw_material_id' => $materialModels['Plastik Pouch Zipper & Stiker']->id, 'quantity_needed' => 1]);
        }

        if ($tingTingSusu->recipes()->count() === 0) {
            \App\Models\ProductRecipe::create(['product_id' => $tingTingSusu->id, 'raw_material_id' => $materialModels['Kacang Tanah Kupas']->id, 'quantity_needed' => 45]);
            \App\Models\ProductRecipe::create(['product_id' => $tingTingSusu->id, 'raw_material_id' => $materialModels['Gula Pasir Kristal']->id, 'quantity_needed' => 25]);
            \App\Models\ProductRecipe::create(['product_id' => $tingTingSusu->id, 'raw_material_id' => $materialModels['Susu Kental Manis']->id, 'quantity_needed' => 15]);
            \App\Models\ProductRecipe::create(['product_id' => $tingTingSusu->id, 'raw_material_id' => $materialModels['Plastik Pouch Zipper & Stiker']->id, 'quantity_needed' => 1]);
        }
    }
}
