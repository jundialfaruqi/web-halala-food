<?php

use App\Models\PermissionGroup;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan permission group Faktur & Piutang Toko ada
        $group = PermissionGroup::firstOrCreate(
            ['name' => 'Faktur & Piutang Toko'],
            ['description' => 'Kelola faktur tagihan konsinyasi mitra toko, jatuh tempo piutang, dan pencatatan pembayaran pelunasan.']
        );

        $newPermissions = [
            'faktur-pembayaran' => 'Mencatat penerimaan pembayaran atau cicilan faktur.',
            'faktur-rekonsiliasi' => 'Melakukan rekonsiliasi faktur (retur dan barang terjual).',
            'faktur-pembayaran-delete' => 'Menghapus riwayat transaksi pembayaran faktur.',
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

        // Assign ke roles
        $devRole = Role::where('name', 'dev')->where('guard_name', 'web')->first();
        if ($devRole) {
            $devRole->givePermissionTo(array_keys($newPermissions));
        }

        $managerRole = Role::where('name', 'manager')->where('guard_name', 'web')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo(array_keys($newPermissions));
        }

        $kurirRole = Role::where('name', 'kurir')->where('guard_name', 'web')->first();
        if ($kurirRole) {
            // Kurir mendapatkan hak catat pembayaran dan rekonsiliasi, TIDAK mendapatkan hak hapus riwayat pembayaran
            $kurirRole->givePermissionTo(['faktur-pembayaran', 'faktur-rekonsiliasi']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perms = ['faktur-pembayaran', 'faktur-rekonsiliasi', 'faktur-pembayaran-delete'];

        foreach ($perms as $permName) {
            $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
            if ($perm) {
                $perm->roles()->detach();
                $perm->users()->detach();
                $perm->delete();
            }
        }
    }
};
