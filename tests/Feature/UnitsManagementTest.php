<?php

use App\Models\Permission;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

test('unauthenticated users are redirected to login', function () {
    $unit = Unit::first();

    get(route('admin.units'))->assertRedirect(route('login'));
    get(route('admin.units.create'))->assertRedirect(route('login'));
    get(route('admin.units.edit', $unit->id))->assertRedirect(route('login'));
    delete(route('admin.units.destroy', $unit->id))->assertRedirect(route('login'));
});

test('unauthorized users without permissions are forbidden', function () {
    $user = User::factory()->create();
    $unit = Unit::first();

    actingAs($user);

    get(route('admin.units'))->assertForbidden();
    get(route('admin.units.create'))->assertForbidden();
    get(route('admin.units.edit', $unit->id))->assertForbidden();
    delete(route('admin.units.destroy', $unit->id))->assertForbidden();
});

test('manager cannot access units routes because satuan permissions belong only to dev', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $unit = Unit::first();

    actingAs($manager);

    get(route('admin.units'))->assertForbidden();
    get(route('admin.units.create'))->assertForbidden();
    get(route('admin.units.edit', $unit->id))->assertForbidden();
    delete(route('admin.units.destroy', $unit->id))->assertForbidden();
});

test('dev user can access units index page', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    get(route('admin.units'))
        ->assertOk()
        ->assertSee('Master Satuan')
        ->assertSee('Kilogram')
        ->assertSee('kg')
        ->assertSee('Tambah Satuan');

    Livewire::test('admin.units.index')
        ->assertStatus(200)
        ->assertSee('Master Satuan')
        ->assertSee('Kilogram');
});

test('dev user can access create page and store a new unit', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    get(route('admin.units.create'))
        ->assertOk()
        ->assertSee('Tambah Satuan Baru')
        ->assertSee('Nama Satuan')
        ->assertSee('Simbol / Singkatan');

    Livewire::test('admin.units.create')
        ->set('name', 'Pack')
        ->set('short_name', 'pack')
        ->set('description', 'Satuan kemasan pak isi 10')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.units'));

    expect(Unit::where('short_name', 'pack')->exists())->toBeTrue();
});

test('create unit validates required and unique short_name fields', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    Livewire::test('admin.units.create')
        ->set('name', '')
        ->set('short_name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'short_name' => 'required']);

    // 'kg' already seeded
    Livewire::test('admin.units.create')
        ->set('name', 'Kilogram Duplikat')
        ->set('short_name', 'kg')
        ->call('save')
        ->assertHasErrors(['short_name' => 'unique']);
});

test('dev user can access edit page and update unit', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    $unit = Unit::where('short_name', 'kg')->first();

    get(route('admin.units.edit', $unit->id))
        ->assertOk()
        ->assertSee('Ubah Data Satuan')
        ->assertSee('Kilogram');

    Livewire::test('admin.units.edit', ['unit' => $unit])
        ->set('name', 'Kilogram Baku')
        ->set('description', 'Satuan berat standar baku industri')
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.units'));

    $unit->refresh();
    expect($unit->name)->toBe('Kilogram Baku')
        ->and($unit->description)->toBe('Satuan berat standar baku industri');
});

test('dev user can toggle unit active status via Livewire component', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    $unit = Unit::where('short_name', 'kg')->first();
    expect($unit->is_active)->toBeTrue();

    $component = Livewire::test('admin.units.index');
    $res = $component->instance()->toggleStatus($unit->id);

    expect($res['success'])->toBeTrue();
    $unit->refresh();
    expect($unit->is_active)->toBeFalse();
});

test('dev user can delete unit via Livewire action and HTTP delete route', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    $testUnit1 = Unit::create(['name' => 'Test Unit 1', 'short_name' => 'tu1']);
    $component = Livewire::test('admin.units.index');
    $res = $component->instance()->deleteUnit($testUnit1->id);

    expect($res['success'])->toBeTrue();
    expect(Unit::find($testUnit1->id))->toBeNull();

    // Test HTTP route DELETE
    $testUnit2 = Unit::create(['name' => 'Test Unit 2', 'short_name' => 'tu2']);
    delete(route('admin.units.destroy', $testUnit2->id))
        ->assertRedirect(route('admin.units'));

    expect(Unit::find($testUnit2->id))->toBeNull();
});

test('sidebar renders Master subtitle and Satuan menu for authorized dev user and hides it for kurir', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    $kurir = User::where('email', 'kurir@halala-food.id')->first();

    actingAs($dev);
    get(route('admin.dashboard'))
        ->assertSee('Master')
        ->assertSee('Satuan');

    actingAs($kurir);
    get(route('admin.dashboard'))
        ->assertDontSee('Master')
        ->assertDontSee('Satuan');
});

test('flash toast is rendered on index page after redirect from create or edit', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    test()->withSession(['toast' => ['message' => 'Satuan Bal berhasil ditambahkan.', 'type' => 'success']])
        ->get(route('admin.units'))
        ->assertOk()
        ->assertSee('Satuan Bal berhasil ditambahkan.');
});
