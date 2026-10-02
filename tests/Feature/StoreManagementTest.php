<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

test('unauthenticated users are redirected from stores page to login', function () {
    get(route('admin.stores'))
        ->assertRedirect(route('login'));
});

test('role dev and manager have all toko permissions while kurir has only toko-view', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $fullPermissions = [
        'toko-view',
        'toko-create',
        'toko-edit',
        'toko-delete',
    ];

    foreach ($fullPermissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue();
    }

    expect($kurirRole->hasPermissionTo('toko-view'))->toBeTrue()
        ->and($kurirRole->hasPermissionTo('toko-create'))->toBeFalse()
        ->and($kurirRole->hasPermissionTo('toko-edit'))->toBeFalse()
        ->and($kurirRole->hasPermissionTo('toko-delete'))->toBeFalse();
});

test('dev, manager, and kurir can access stores page', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $kurir = User::where('email', 'kurir@halala-food.id')->first();

    actingAs($dev);
    get(route('admin.stores'))
        ->assertOk()
        ->assertSee('Mitra Toko &amp; Distribusi', false)
        ->assertSee('Pusat Oleh-Oleh Barokah');

    actingAs($manager);
    get(route('admin.stores'))
        ->assertOk()
        ->assertSee('Mitra Toko &amp; Distribusi', false)
        ->assertSee('Tambah Toko Baru');

    actingAs($kurir);
    get(route('admin.stores'))
        ->assertOk()
        ->assertSee('Mitra Toko &amp; Distribusi', false)
        ->assertDontSee('Tambah Toko Baru'); // Kurir does not have toko-create
});

test('manager can create a new partner store via component', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $component = Livewire::test('admin.stores.index');

    // Validation failure when name is missing
    $resFail = $component->instance()->createStore([
        'name' => '',
    ]);
    expect($resFail['success'])->toBeFalse()
        ->and($resFail['message'])->toBe('Nama toko mitra wajib diisi.');

    // Success creation
    $resSuccess = $component->instance()->createStore([
        'name' => 'Toko Barokah Jaya 2',
        'owner_name' => 'Hj. Fatimah',
        'phone' => '081234567899',
        'address' => 'Jl. Kawi No. 10',
        'route' => 'Rute Kawi',
        'commission_rate' => 10.5,
        'is_active' => true,
        'notes' => 'Rak kaca paling atas',
    ]);

    expect($resSuccess['success'])->toBeTrue()
        ->and(Store::where('name', 'Toko Barokah Jaya 2')->exists())->toBeTrue();

    $store = Store::where('name', 'Toko Barokah Jaya 2')->first();
    expect($store->owner_name)->toBe('Hj. Fatimah')
        ->and($store->route)->toBe('Rute Kawi')
        ->and((float) $store->commission_rate)->toBe(10.5)
        ->and($store->is_active)->toBeTrue();
});

test('manager can update an existing partner store', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $store = Store::where('name', 'Pusat Oleh-Oleh Barokah')->first();

    $component = Livewire::test('admin.stores.index');
    $res = $component->instance()->updateStore($store->id, [
        'name' => 'Pusat Oleh-Oleh Barokah Updated',
        'owner_name' => 'Ibu Hj. Aminah',
        'phone' => '081299990000',
        'address' => 'Jl. Pasar Baru No. 99',
        'route' => 'Rute Pasar Baru',
        'commission_rate' => 12.0,
        'is_active' => true,
        'notes' => 'Catatan diperbarui',
    ]);

    expect($res['success'])->toBeTrue();

    $store->refresh();
    expect($store->name)->toBe('Pusat Oleh-Oleh Barokah Updated')
        ->and($store->phone)->toBe('081299990000')
        ->and($store->route)->toBe('Rute Pasar Baru')
        ->and((float) $store->commission_rate)->toBe(12.0);
});

test('manager can toggle store active status', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $store = Store::where('name', 'Pusat Oleh-Oleh Barokah')->first();
    expect($store->is_active)->toBeTrue();

    $component = Livewire::test('admin.stores.index');
    $res = $component->instance()->toggleStatus($store->id);

    expect($res['success'])->toBeTrue();
    $store->refresh();
    expect($store->is_active)->toBeFalse();

    // Toggle back
    $component->instance()->toggleStatus($store->id);
    $store->refresh();
    expect($store->is_active)->toBeTrue();
});

test('manager can delete a partner store', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $store = Store::create([
        'name' => 'Toko Sementara Untuk Dihapus',
        'is_active' => true,
    ]);

    $component = Livewire::test('admin.stores.index');
    $res = $component->instance()->deleteStore($store->id);

    expect($res['success'])->toBeTrue()
        ->and(Store::find($store->id))->toBeNull();
});

test('kurir cannot create, update, or delete stores', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    actingAs($kurir);

    $store = Store::first();
    $component = Livewire::test('admin.stores.index');

    $resCreate = $component->instance()->createStore(['name' => 'Hacker Store']);
    expect($resCreate['success'])->toBeFalse();

    $resUpdate = $component->instance()->updateStore($store->id, ['name' => 'Hacked Store']);
    expect($resUpdate['success'])->toBeFalse();

    $resDelete = $component->instance()->deleteStore($store->id);
    expect($resDelete['success'])->toBeFalse();
});
