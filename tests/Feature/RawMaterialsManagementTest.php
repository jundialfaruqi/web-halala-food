<?php

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

test('unauthenticated users are redirected from raw materials page to login', function () {
    get(route('admin.raw-materials'))
        ->assertRedirect(route('login'));
});

test('both role dev and manager have bahan baku and resep permissions while kurir does not', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $permissions = [
        'bahan-baku-view',
        'bahan-baku-create',
        'bahan-baku-edit',
        'bahan-baku-delete',
        'resep-manage',
    ];

    foreach ($permissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue()
            ->and($kurirRole->hasPermissionTo($perm))->toBeFalse();
    }
});

test('dev and manager can access raw materials page but kurir is forbidden', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $kurir = User::where('email', 'kurir@halala-food.id')->first();

    actingAs($dev);
    get(route('admin.raw-materials'))
        ->assertOk()
        ->assertSee('Master Bahan Baku')
        ->assertSee('Wijen Putih Sangrai');

    actingAs($manager);
    get(route('admin.raw-materials'))
        ->assertOk()
        ->assertSee('Master Bahan Baku')
        ->assertSee('Gula Pasir Kristal');

    actingAs($kurir);
    get(route('admin.raw-materials'))
        ->assertForbidden();
});

test('can create a new raw material with dynamic unit_id', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $gramUnit = Unit::where('short_name', 'g')->first();
    expect($gramUnit)->not->toBeNull();

    $component = Livewire::test('admin.raw-materials.index');
    $res = $component->instance()->saveMaterial(
        null,
        'Mentega Bos',
        $gramUnit->id,
        5000.0,
        1000.0,
        45.0
    );

    expect($res['success'])->toBeTrue();

    $created = RawMaterial::where('name', 'Mentega Bos')->first();
    expect($created)->not->toBeNull()
        ->and($created->unit_id)->toBe($gramUnit->id)
        ->and($created->stock)->toBe(5000.0)
        ->and($created->min_stock)->toBe(1000.0)
        ->and((float) $created->cost_per_unit)->toBe(45.0);
});

test('validates raw material form inputs', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    $component = Livewire::test('admin.raw-materials.index');
    $res = $component->instance()->saveMaterial(
        null,
        '', // missing name
        99999, // invalid unit_id
        -10, // negative stock
        -5, // negative min_stock
        -1 // negative cost
    );

    expect($res['success'])->toBeFalse()
        ->and($res['errors'])->toHaveKeys(['name', 'unit_id', 'stock', 'min_stock', 'cost_per_unit']);
});

test('can update an existing raw material', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $material = RawMaterial::where('name', 'Wijen Putih Sangrai')->first();
    expect($material)->not->toBeNull();

    $component = Livewire::test('admin.raw-materials.index');
    $res = $component->instance()->saveMaterial(
        $material->id,
        'Wijen Putih Premium',
        $material->unit_id,
        20000.0,
        4000.0,
        70.0
    );

    expect($res['success'])->toBeTrue();

    $material->refresh();
    expect($material->name)->toBe('Wijen Putih Premium')
        ->and($material->stock)->toBe(20000.0)
        ->and((float) $material->cost_per_unit)->toBe(70.0);
});

test('prevents deleting raw material that is used in recipes but allows deleting unused material', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    $usedMaterial = RawMaterial::where('name', 'Wijen Putih Sangrai')->first();
    expect($usedMaterial->recipes()->count())->toBeGreaterThan(0);

    $component = Livewire::test('admin.raw-materials.index');
    $resUsed = $component->instance()->deleteMaterial($usedMaterial->id);

    expect($resUsed['success'])->toBeFalse()
        ->and($resUsed['message'])->toContain('sedang digunakan');

    // Create an unused material
    $gramUnit = Unit::where('short_name', 'g')->first();
    $unused = RawMaterial::create([
        'name' => 'Bahan Uji Coba',
        'unit_id' => $gramUnit->id,
        'unit' => 'g',
        'stock' => 100,
        'min_stock' => 10,
        'cost_per_unit' => 20,
    ]);

    $resUnused = $component->instance()->deleteMaterial($unused->id);
    expect($resUnused['success'])->toBeTrue();
    expect(RawMaterial::find($unused->id))->toBeNull();
});

test('can save and update product recipe BOM and calculates material cost and margin', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $product = Product::where('name', 'Marie Wijen')->first();
    $mat1 = RawMaterial::where('name', 'Wijen Putih Sangrai')->first();
    $mat2 = RawMaterial::where('name', 'Gula Pasir Kristal')->first();

    $component = Livewire::test('admin.raw-materials.index');
    $res = $component->instance()->saveRecipe($product->id, [
        ['raw_material_id' => $mat1->id, 'quantity_needed' => 40], // 40g * 65 = 2.600
        ['raw_material_id' => $mat2->id, 'quantity_needed' => 50], // 50g * 17.5 = 875
    ]);

    expect($res['success'])->toBeTrue();

    $product->refresh();
    expect($product->recipes()->count())->toBe(2);

    // Total cost: 2600 + 875 = 3475
    expect($product->material_cost)->toBe(3475.0);

    // Consignment price is 12.000, margin = (12000 - 3475)/12000 = 71.0%
    expect($product->consignment_margin)->toBe(71.0);
});

test('sidebar displays both Satuan and Bahan Baku menus for dev', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);
    get(route('admin.dashboard'))
        ->assertSee('Master')
        ->assertSee('Satuan')
        ->assertSee('Bahan Baku &amp; Resep', false);
});

test('sidebar displays Bahan Baku but hides Satuan for manager', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);
    get(route('admin.dashboard'))
        ->assertSee('Master')
        ->assertDontSee('admin/units')
        ->assertSee('Bahan Baku &amp; Resep', false);
});

test('sidebar hides Master section completely for kurir', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    actingAs($kurir);
    get(route('admin.dashboard'))
        ->assertDontSee('Master')
        ->assertDontSee('Bahan Baku &amp; Resep', false);
});
