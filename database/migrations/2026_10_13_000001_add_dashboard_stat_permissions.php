<?php

use App\Models\PermissionGroup;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan cache permission di-reset
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Dapatkan atau buat grup Dashboard
        $group = PermissionGroup::firstOrCreate(
            ['name' => 'Dashboard'],
            ['description' => 'Akses ringkasan metrik performa toko dan operasional.']
        );

        // 2. Daftar permission statistik dashboard
        $newPermissions = [
            'dashboard-stat-bisnis' => 'Melihat kartu statistik ringkasan bisnis, omzet penjualan, dan performa keuangan di dashboard.',
            'dashboard-stat-pengantaran' => 'Melihat kartu statistik tugas pengantaran surat jalan dan progres pengiriman kurir di dashboard.',
        ];

        foreach ($newPermissions as $permName => $desc) {
            $permission = Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web'],
                ['permission_group_id' => $group->id]
            );

            if ($permission->permission_group_id !== $group->id) {
                $permission->update(['permission_group_id' => $group->id]);
            }
        }

        // 3. Pasangkan permission ke role awal
        // Dev: memiliki kedua hak akses
        $devRole = Role::where('name', 'dev')->where('guard_name', 'web')->first();
        if ($devRole) {
            $devRole->givePermissionTo(array_keys($newPermissions));
        }

        // Manager: memiliki akses statistik bisnis dan pemantauan pengantaran
        $managerRole = Role::where('name', 'manager')->where('guard_name', 'web')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo(['dashboard-stat-bisnis', 'dashboard-stat-pengantaran']);
        }

        // Kurir: hanya memiliki akses statistik tugas pengantaran pribadinya
        $kurirRole = Role::where('name', 'kurir')->where('guard_name', 'web')->first();
        if ($kurirRole) {
            $kurirRole->givePermissionTo(['dashboard-stat-pengantaran']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = ['dashboard-stat-bisnis', 'dashboard-stat-pengantaran'];

        foreach ($perms as $permName) {
            $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
            if ($perm) {
                $perm->roles()->detach();
                $perm->users()->detach();
                $perm->delete();
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
