<?php

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    // Ensure permission registrar is cleared
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

it('redirects unauthenticated user from roles page to login', function () {
    get(route('admin.roles'))
        ->assertRedirect(route('login'));
});

it('renders roles and permission management component successfully', function () {
    $user = User::factory()->create();
    $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
    $perm = Permission::firstOrCreate(['name' => 'role-manage', 'guard_name' => 'web']);
    $devRole->givePermissionTo($perm);
    $user->assignRole($devRole);

    actingAs($user);

    get(route('admin.roles'))
        ->assertOk()
        ->assertSee('Role Pengguna')
        ->assertSee('Grup Permission')
        ->assertSee('Daftar Permission');

    Livewire::test('admin.roles')
        ->assertStatus(200)
        ->assertSee('Role Pengguna')
        ->assertSee('Grup Permission')
        ->assertSee('Daftar Permission');
});

it('forbids unauthorized user without role-manage permission from accessing roles page', function () {
    $user = User::factory()->create();
    actingAs($user);

    get(route('admin.roles'))
        ->assertForbidden();
});

it('can create a new role and sync permissions', function () {
    $user = User::factory()->create();
    actingAs($user);

    $perm1 = Permission::firstOrCreate(['name' => 'test-permission-1', 'guard_name' => 'web']);
    $perm2 = Permission::firstOrCreate(['name' => 'test-permission-2', 'guard_name' => 'web']);

    $component = Livewire::test('admin.roles');
    $result = $component->instance()->saveRole(null, 'Supervisor Gudang', [$perm1->id, $perm2->id]);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue();

    $newRole = Role::where('name', 'Supervisor Gudang')->first();
    expect($newRole)->not->toBeNull()
        ->and($newRole->hasPermissionTo('test-permission-1'))->toBeTrue()
        ->and($newRole->hasPermissionTo('test-permission-2'))->toBeTrue();
});

it('can update an existing role and modify permissions', function () {
    $user = User::factory()->create();
    actingAs($user);

    $role = Role::firstOrCreate(['name' => 'Staff Tester', 'guard_name' => 'web']);
    $perm = Permission::firstOrCreate(['name' => 'test-permission-3', 'guard_name' => 'web']);

    $component = Livewire::test('admin.roles');
    $result = $component->instance()->saveRole($role->id, 'Staff QA Updated', [$perm->id]);

    expect($result['success'])->toBeTrue();

    $role->refresh();
    expect($role->name)->toBe('Staff QA Updated')
        ->and($role->hasPermissionTo('test-permission-3'))->toBeTrue();
});

it('prevents deleting protected dev role or role with assigned users', function () {
    $user = User::factory()->create();
    $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
    $user->assignRole($devRole);
    actingAs($user);

    $component = Livewire::test('admin.roles');
    $resDev = $component->instance()->deleteRole($devRole->id);

    expect($resDev['success'])->toBeFalse()
        ->and($resDev['message'])->toContain('role sistem utama');

    // Create a regular role with assigned user
    $roleWithUser = Role::firstOrCreate(['name' => 'role-with-user', 'guard_name' => 'web']);
    $user->assignRole($roleWithUser);

    $resWithUser = $component->instance()->deleteRole($roleWithUser->id);
    expect($resWithUser['success'])->toBeFalse()
        ->and($resWithUser['message'])->toContain('sedang digunakan oleh');
});

it('can create, update, and delete permission groups and sync permissions to group', function () {
    $user = User::factory()->create();
    actingAs($user);

    $perm1 = Permission::firstOrCreate(['name' => 'stock-in', 'guard_name' => 'web']);
    $perm2 = Permission::firstOrCreate(['name' => 'stock-out', 'guard_name' => 'web']);

    $component = Livewire::test('admin.roles');

    // 1. Create with permissions assigned
    $resCreate = $component->instance()->saveGroup(null, 'Inventaris & Gudang', 'Modul stok barang', [$perm1->id, $perm2->id]);
    expect($resCreate['success'])->toBeTrue();

    $group = PermissionGroup::where('name', 'Inventaris & Gudang')->first();
    expect($group)->not->toBeNull()
        ->and($group->description)->toBe('Modul stok barang');

    $perm1->refresh();
    $perm2->refresh();
    expect($perm1->permission_group_id)->toBe($group->id)
        ->and($perm2->permission_group_id)->toBe($group->id);

    // 2. Update with modified permissions (remove perm2)
    $resUpdate = $component->instance()->saveGroup($group->id, 'Inventaris & Logistik', 'Modul logistik gudang', [$perm1->id]);
    expect($resUpdate['success'])->toBeTrue();
    $group->refresh();
    expect($group->name)->toBe('Inventaris & Logistik');

    $perm1->refresh();
    $perm2->refresh();
    expect($perm1->permission_group_id)->toBe($group->id)
        ->and($perm2->permission_group_id)->toBeNull();

    // 3. Delete
    $resDelete = $component->instance()->deleteGroup($group->id);
    expect($resDelete['success'])->toBeTrue();
    expect(PermissionGroup::find($group->id))->toBeNull();
});

it('can create, update, and delete individual permissions with group mapping', function () {
    $user = User::factory()->create();
    actingAs($user);

    $group = PermissionGroup::firstOrCreate(['name' => 'Grup Test']);
    $component = Livewire::test('admin.roles');

    // 1. Create permission
    $resCreate = $component->instance()->savePermission(null, 'inventory-check', $group->id);
    expect($resCreate['success'])->toBeTrue();

    $perm = Permission::where('name', 'inventory-check')->first();
    expect($perm)->not->toBeNull()
        ->and($perm->permission_group_id)->toBe($group->id);

    // 2. Update permission
    $resUpdate = $component->instance()->savePermission($perm->id, 'inventory-audit', null);
    expect($resUpdate['success'])->toBeTrue();
    $perm->refresh();
    expect($perm->name)->toBe('inventory-audit')
        ->and($perm->permission_group_id)->toBeNull();

    // 3. Delete permission
    $resDelete = $component->instance()->deletePermission($perm->id);
    expect($resDelete['success'])->toBeTrue();
    expect(Permission::find($perm->id))->toBeNull();
});
