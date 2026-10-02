<?php

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

test('master satuan permissions are created and grouped correctly', function () {
    $group = PermissionGroup::where('name', 'Master Satuan')->first();
    expect($group)->not->toBeNull();

    $expectedPermissions = [
        'satuan-view',
        'satuan-create',
        'satuan-edit',
        'satuan-delete',
    ];

    foreach ($expectedPermissions as $permName) {
        $permission = Permission::where('name', $permName)->first();
        expect($permission)->not->toBeNull()
            ->and($permission->permission_group_id)->toBe($group->id);
    }
});

test('role dev has all satuan permissions', function () {
    $devRole = Role::findByName('dev', 'web');

    expect($devRole->hasPermissionTo('satuan-view'))->toBeTrue()
        ->and($devRole->hasPermissionTo('satuan-create'))->toBeTrue()
        ->and($devRole->hasPermissionTo('satuan-edit'))->toBeTrue()
        ->and($devRole->hasPermissionTo('satuan-delete'))->toBeTrue();
});

test('role manager and kurir do not have satuan permissions', function () {
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $satuanPermissions = ['satuan-view', 'satuan-create', 'satuan-edit', 'satuan-delete'];

    foreach ($satuanPermissions as $perm) {
        expect($managerRole->hasPermissionTo($perm))->toBeFalse()
            ->and($kurirRole->hasPermissionTo($perm))->toBeFalse();
    }
});

test('dev user can perform all satuan actions', function () {
    $devUser = User::where('email', 'developer@halala-food.id')->first();
    expect($devUser)->not->toBeNull();

    expect($devUser->can('satuan-view'))->toBeTrue()
        ->and($devUser->can('satuan-create'))->toBeTrue()
        ->and($devUser->can('satuan-edit'))->toBeTrue()
        ->and($devUser->can('satuan-delete'))->toBeTrue();
});
