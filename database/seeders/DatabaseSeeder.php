<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
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

        // 1. Create Permissions
        $permissions = [
            'view-dashboard',
            'manage-users',
            'manage-roles',
            'manage-permissions',
            'view-financial-reports',
            'manage-products',
            'manage-orders',
            'process-payment',
            'manage-kitchen',
            'manage-delivery',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
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
            'view-dashboard',
            'view-financial-reports',
            'manage-users',
            'manage-products',
            'manage-orders',
        ]);

        $managerRole->syncPermissions([
            'view-dashboard',
            'view-financial-reports',
            'manage-users',
            'manage-products',
            'manage-orders',
            'manage-kitchen',
            'manage-delivery',
        ]);

        $masakRole->syncPermissions([
            'view-dashboard',
            'manage-kitchen',
            'manage-products',
        ]);

        $kasirRole->syncPermissions([
            'view-dashboard',
            'manage-orders',
            'process-payment',
        ]);

        $kurirRole->syncPermissions([
            'view-dashboard',
            'manage-delivery',
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


