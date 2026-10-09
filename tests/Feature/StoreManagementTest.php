<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Store::firstOrCreate(
        ['name' => 'Pusat Oleh-Oleh Barokah'],
        [
            'owner_name' => 'H. Ahmad Barokah',
            'phone' => '081234567890',
            'address' => 'Jl. Pasar Besar No. 12, Malang',
            'latitude' => -7.983908,
            'longitude' => 112.630852,
            'route' => 'Rute Pasar Besar',
            'notes' => 'Toko utama grosir oleh-oleh',
            'is_active' => true,
        ]
    );
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
        ->and($store->phone)->toBe('6281234567899')
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

test('manager can upload, view, and delete store photo from index component', function () {
    Storage::fake('public');

    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $store = Store::create([
        'name' => 'Toko Foto Test',
        'is_active' => true,
    ]);

    // 1x1 webp base64
    $webpBase64 = 'data:image/webp;base64,UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';

    $component = Livewire::test('admin.stores.index');
    $res = $component->instance()->updateStorePhoto($store->id, $webpBase64);

    expect($res['success'])->toBeTrue()
        ->and($res['photo_url'])->not->toBeNull();

    $store->refresh();
    expect($store->photo)->not->toBeNull()
        ->and(str_starts_with($store->photo, 'foto-toko/'))->toBeTrue()
        ->and(str_ends_with($store->photo, '.webp'))->toBeTrue()
        ->and($store->photo)->toContain(now()->format('Y-m-d'))
        ->and($store->photo)->toContain('toko-foto-test');

    expect(Storage::disk('public')->exists($store->photo))->toBeTrue();

    // Delete photo
    $resDelete = $component->instance()->deleteStorePhoto($store->id);
    expect($resDelete['success'])->toBeTrue();

    $photoPath = $store->photo;
    $store->refresh();
    expect($store->photo)->toBeNull();
    expect(Storage::disk('public')->exists($photoPath))->toBeFalse();
});

test('manager can upload jpeg photo formatted for safari/ios', function () {
    Storage::fake('public');

    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $store = Store::create([
        'name' => 'Toko Safari Test',
        'is_active' => true,
    ]);

    // 1x1 jpeg base64
    $jpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

    $component = Livewire::test('admin.stores.index');
    $res = $component->instance()->updateStorePhoto($store->id, $jpegBase64);

    expect($res['success'])->toBeTrue();
    $store->refresh();
    expect($store->photo)->not->toBeNull()
        ->and(str_starts_with($store->photo, 'foto-toko/'))->toBeTrue()
        ->and(str_ends_with($store->photo, '.jpg'))->toBeTrue()
        ->and($store->photo)->toContain(now()->format('Y-m-d'))
        ->and($store->photo)->toContain('toko-safari-test');

    expect(Storage::disk('public')->exists($store->photo))->toBeTrue();
});

test('backend validates max upload size and allowed formats', function () {
    // 1. Format tidak didukung (misal text/plain atau gif)
    $invalidFormat = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    $formatError = Store::validatePhotoBase64($invalidFormat);
    expect($formatError)->toContain('Format file foto tidak didukung');

    // 2. Maksimal upload 10MB (buat string base64 palsu > 10MB)
    // 11MB dummy data
    $largeRawData = str_repeat('A', (10 * 1024 * 1024) + 1024);
    $oversizedBase64 = 'data:image/jpeg;base64,'.base64_encode($largeRawData);
    $sizeError = Store::validatePhotoBase64($oversizedBase64);
    expect($sizeError)->toContain('Ukuran file foto melebihi batas maksimal 10MB');

    // 3. Foto kosong diperbolehkan (opsional)
    expect(Store::validatePhotoBase64(null))->toBeNull()
        ->and(Store::validatePhotoBase64(''))->toBeNull();
});

test('store create and edit components support photo_data and remain optional', function () {
    Storage::fake('public');

    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $webpBase64 = 'data:image/webp;base64,UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';

    // 1. Create store WITHOUT photo (opsional)
    Livewire::test('admin.stores.create')
        ->set('name', 'Toko Tanpa Foto')
        ->set('photo_data', null)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.stores'));

    $noPhotoStore = Store::where('name', 'Toko Tanpa Foto')->first();
    expect($noPhotoStore)->not->toBeNull()
        ->and($noPhotoStore->photo)->toBeNull();

    // 2. Create store WITH photo_data
    Livewire::test('admin.stores.create')
        ->set('name', 'Toko Baru Berfoto')
        ->set('photo_data', $webpBase64)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.stores'));

    $newStore = Store::where('name', 'Toko Baru Berfoto')->first();
    expect($newStore)->not->toBeNull()
        ->and($newStore->photo)->not->toBeNull()
        ->and(str_starts_with($newStore->photo, 'foto-toko/'))->toBeTrue()
        ->and($newStore->photo)->toContain(now()->format('Y-m-d'))
        ->and($newStore->photo)->toContain('toko-baru-berfoto');

    expect(Storage::disk('public')->exists($newStore->photo))->toBeTrue();

    // 3. Edit store and remove photo
    Livewire::test('admin.stores.edit', ['store' => $newStore])
        ->set('photo_data', 'DELETE')
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.stores'));

    $newStore->refresh();
    expect($newStore->photo)->toBeNull();
});

test('deleting a store model cleans up associated photo file from storage disk', function () {
    Storage::fake('public');

    $store = Store::create([
        'name' => 'Toko Auto Delete Photo',
        'is_active' => true,
    ]);

    $webpBase64 = 'data:image/webp;base64,UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';
    $store->updatePhotoFromBase64($webpBase64);

    $photoPath = $store->photo;
    expect(str_starts_with($photoPath, 'foto-toko/'))->toBeTrue();
    expect(Storage::disk('public')->exists($photoPath))->toBeTrue();

    $store->delete();
    expect(Storage::disk('public')->exists($photoPath))->toBeFalse();
});
