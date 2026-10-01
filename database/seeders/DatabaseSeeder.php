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

        // Manager gets full operational, user & role management permissions
        $managerRole->syncPermissions([
            'dashboard-view',
            'user-manage',
            'role-manage',
            'permission-manage',
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
    }
}
