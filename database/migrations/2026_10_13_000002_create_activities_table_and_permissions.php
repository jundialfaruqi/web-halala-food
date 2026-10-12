<?php

use App\Models\PermissionGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        // 1. Buat tabel activities untuk menyimpan log linimasa aktifitas sistem
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index(); // delivery_created, delivery_dispatched, delivery_completed, invoice_created, invoice_paid
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('subject'); // subject_type, subject_id (Delivery / Invoice)
            $table->json('data')->nullable(); // metadata terstruktur (nomor SJ/faktur, toko, total, dll)
            $table->timestamps();

            // Index komposit untuk query performa tinggi
            $table->index(['created_at', 'id']);
            $table->index(['courier_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // 2. Registrasi grup izin dan permission baru
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $group = PermissionGroup::firstOrCreate(
            ['name' => 'Aktifitas & Linimasa'],
            ['description' => 'Akses pemantauan linimasa aktifitas operasional pengantaran dan faktur sistem.']
        );

        $newPermissions = [
            'aktifitas-semua' => 'Melihat seluruh linimasa aktifitas operasional dari semua staf dan pengguna.',
            'aktifitas-kurir' => 'Melihat linimasa aktifitas surat jalan dan faktur khusus yang ditugaskan ke kurir bersangkutan.',
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

        // 3. Pasangkan permission ke role
        // Dev: memiliki akses penuh ke aktifitas-semua dan aktifitas-kurir
        $devRole = Role::where('name', 'dev')->where('guard_name', 'web')->first();
        if ($devRole) {
            $devRole->givePermissionTo(array_keys($newPermissions));
        }

        // Manager: memiliki akses aktifitas-semua
        $managerRole = Role::where('name', 'manager')->where('guard_name', 'web')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo(['aktifitas-semua']);
        }

        // Kurir: hanya memiliki hak akses aktifitas-kurir
        $kurirRole = Role::where('name', 'kurir')->where('guard_name', 'web')->first();
        if ($kurirRole) {
            $kurirRole->givePermissionTo(['aktifitas-kurir']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', ['aktifitas-semua', 'aktifitas-kurir'])->delete();
        $group = PermissionGroup::where('name', 'Aktifitas & Linimasa')->first();
        if ($group && $group->permissions()->count() === 0) {
            $group->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
