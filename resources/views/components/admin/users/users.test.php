<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

it('redirects unauthenticated user from users page to login', function () {
    get(route('admin.users'))
        ->assertRedirect(route('login'));
});

it('renders user management component successfully for authorized user', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
    $user->assignRole($role);
    $perm = Permission::firstOrCreate(['name' => 'user-manage', 'guard_name' => 'web']);
    $role->givePermissionTo($perm);

    actingAs($user);

    get(route('admin.users'))
        ->assertOk()
        ->assertSee('Manajemen Pengguna')
        ->assertSee('Tambah Pengguna');

    Livewire::test('admin.users')
        ->assertStatus(200)
        ->assertSee('Manajemen Pengguna')
        ->assertSee('Tambah Pengguna');
});

it('forbids unauthorized user without user-manage permission from accessing users page', function () {
    $user = User::factory()->create();
    actingAs($user);

    get(route('admin.users'))
        ->assertForbidden();
});

it('can create a new user with name, email, phone, role, and password', function () {
    $admin = User::factory()->create();
    $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
    $kasirRole = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);
    $perm = Permission::firstOrCreate(['name' => 'user-manage', 'guard_name' => 'web']);
    $devRole->givePermissionTo($perm);
    $admin->assignRole($devRole);

    actingAs($admin);

    $component = Livewire::test('admin.users');
    $result = $component->instance()->saveUser(
        null,
        'Andi Pratama',
        'andi@halala-food.id',
        '081234567890',
        'kasir',
        'secret123'
    );

    expect($result['success'])->toBeTrue();

    $newUser = User::where('email', 'andi@halala-food.id')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->name)->toBe('Andi Pratama')
        ->and($newUser->phone)->toBe('6281234567890')
        ->and($newUser->formattedPhone())->toBe('+62 812-3456-7890')
        ->and($newUser->whatsappUrl())->toBe('https://wa.me/6281234567890')
        ->and($newUser->hasRole('kasir'))->toBeTrue()
        ->and(Hash::check('secret123', $newUser->password))->toBeTrue();
});

it('can update an existing user without changing password', function () {
    $admin = User::factory()->create();
    $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
    $kurirRole = Role::firstOrCreate(['name' => 'kurir', 'guard_name' => 'web']);
    $perm = Permission::firstOrCreate(['name' => 'user-manage', 'guard_name' => 'web']);
    $devRole->givePermissionTo($perm);
    $admin->assignRole($devRole);

    actingAs($admin);

    $targetUser = User::create([
        'name' => 'Budi Lama',
        'email' => 'budi.lama@halala-food.id',
        'phone' => '628111222333',
        'password' => Hash::make('oldpassword'),
    ]);
    $targetUser->assignRole($devRole);

    $component = Livewire::test('admin.users');
    $result = $component->instance()->saveUser(
        $targetUser->id,
        'Budi Baru',
        'budi.baru@halala-food.id',
        '089988776655',
        'kurir',
        null // Keep old password
    );

    expect($result['success'])->toBeTrue();

    $targetUser->refresh();
    expect($targetUser->name)->toBe('Budi Baru')
        ->and($targetUser->email)->toBe('budi.baru@halala-food.id')
        ->and($targetUser->phone)->toBe('6289988776655')
        ->and($targetUser->hasRole('kurir'))->toBeTrue()
        ->and(Hash::check('oldpassword', $targetUser->password))->toBeTrue();
});

it('prevents a user from deleting their own active account', function () {
    $admin = User::factory()->create();
    $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
    $perm = Permission::firstOrCreate(['name' => 'user-manage', 'guard_name' => 'web']);
    $devRole->givePermissionTo($perm);
    $admin->assignRole($devRole);

    actingAs($admin);

    $component = Livewire::test('admin.users');
    $result = $component->instance()->deleteUser($admin->id);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('tidak dapat menghapus akun Anda sendiri');
});

it('can delete another user successfully', function () {
    $admin = User::factory()->create();
    $devRole = Role::firstOrCreate(['name' => 'dev', 'guard_name' => 'web']);
    $perm = Permission::firstOrCreate(['name' => 'user-manage', 'guard_name' => 'web']);
    $devRole->givePermissionTo($perm);
    $admin->assignRole($devRole);

    actingAs($admin);

    $otherUser = User::factory()->create(['name' => 'Staff To Delete']);
    $component = Livewire::test('admin.users');
    $result = $component->instance()->deleteUser($otherUser->id);

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('berhasil dihapus');

    expect(User::find($otherUser->id))->toBeNull();
});
