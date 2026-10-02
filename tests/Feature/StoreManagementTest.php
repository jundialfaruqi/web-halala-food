<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
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

test('dev, manager, and kurir can access stores index page', function () {
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

test('manager can access create page and view form with map container and breadcrumb', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    get(route('admin.stores.create'))
        ->assertOk()
        ->assertSee('Tambah Toko Mitra Baru')
        ->assertSee('Titik Lokasi &amp; Koordinat GPS', false)
        ->assertSee('Lokasi GPS Saya')
        ->assertSee('Pilihan cepat:')
        ->assertSee('Rute Pasar Besar')
        ->assertSee('Tambah Toko Baru'); // Breadcrumb
});

test('kurir is forbidden from accessing create store page', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    actingAs($kurir);

    get(route('admin.stores.create'))
        ->assertForbidden();
});

test('manager can create a new store with latitude, longitude, and route on dedicated create page', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    Livewire::test('admin.stores.create')
        ->set('name', 'Toko Berkah Baru')
        ->set('owner_name', 'Hj. Siti Nurhaliza')
        ->set('phone', '081234567899')
        ->set('address', 'Jl. Ijen No. 25, Kota Malang')
        ->set('latitude', -7.9723000)
        ->set('longitude', 112.6256000)
        ->set('route', 'Rute Ijen')
        ->set('notes', 'Titik toko di seberang katedral.')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.stores'));

    $store = Store::where('name', 'Toko Berkah Baru')->first();
    expect($store)->not->toBeNull()
        ->and($store->owner_name)->toBe('Hj. Siti Nurhaliza')
        ->and($store->phone)->toBe('081234567899')
        ->and($store->latitude)->toBe(-7.9723)
        ->and($store->longitude)->toBe(112.6256)
        ->and($store->route)->toBe('Rute Ijen')
        ->and($store->is_active)->toBeTrue();
});

test('store creation validates required name', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    Livewire::test('admin.stores.create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('manager can access edit store page and updates coordinates and details', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $store = Store::where('name', 'Pusat Oleh-Oleh Barokah')->first();

    get(route('admin.stores.edit', $store->id))
        ->assertOk()
        ->assertSee('Ubah Data Toko Mitra')
        ->assertSee('Pusat Oleh-Oleh Barokah');

    Livewire::test('admin.stores.edit', ['store' => $store])
        ->set('name', 'Pusat Oleh-Oleh Barokah Renamed')
        ->set('latitude', -7.9890123)
        ->set('longitude', 112.6324567)
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.stores'));

    $store->refresh();
    expect($store->name)->toBe('Pusat Oleh-Oleh Barokah Renamed')
        ->and($store->latitude)->toBe(-7.9890123)
        ->and($store->longitude)->toBe(112.6324567);
});

test('kurir is forbidden from accessing edit store page', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    actingAs($kurir);

    $store = Store::first();

    get(route('admin.stores.edit', $store->id))
        ->assertForbidden();
});

test('manager can toggle store active status from index', function () {
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

test('manager can delete store from index component and destroy route', function () {
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

    // Test destroy route
    $store2 = Store::create([
        'name' => 'Toko Kedua Untuk Dihapus',
        'is_active' => true,
    ]);

    delete(route('admin.stores.destroy', $store2->id))
        ->assertRedirect(route('admin.stores'));

    expect(Store::find($store2->id))->toBeNull();
});

test('kurir cannot delete or toggle store status', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    actingAs($kurir);

    $store = Store::first();
    $component = Livewire::test('admin.stores.index');

    $resDelete = $component->instance()->deleteStore($store->id);
    expect($resDelete['success'])->toBeFalse();

    $resToggle = $component->instance()->toggleStatus($store->id);
    expect($resToggle['success'])->toBeFalse();
});
