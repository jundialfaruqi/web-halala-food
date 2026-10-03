<?php

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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

    $unit = Unit::firstOrCreate(['short_name' => 'bungkus'], ['name' => 'Bungkus', 'is_active' => true]);
    $rawUnit = Unit::firstOrCreate(['short_name' => 'g'], ['name' => 'Gram', 'is_active' => true]);

    $product = Product::firstOrCreate(
        ['name' => 'Marie Wijen'],
        [
            'unit_id' => $unit->id,
            'unit' => 'bungkus',
            'consignment_price' => 12000.00,
            'retail_price' => 15000.00,
            'stock_ready' => 50,
            'is_active' => true,
        ]
    );

    $mat = \App\Models\RawMaterial::firstOrCreate(
        ['name' => 'Wijen Putih Sangrai'],
        ['unit_id' => $rawUnit->id, 'unit' => 'g', 'stock' => 5000, 'cost_per_unit' => 0.05]
    );

    \App\Models\ProductRecipe::firstOrCreate([
        'product_id' => $product->id,
        'raw_material_id' => $mat->id,
    ], [
        'quantity_needed' => 30.00,
    ]);
});

test('unauthenticated users are redirected from products page to login', function () {
    get(route('admin.products'))
        ->assertRedirect(route('login'));
});

test('dev and manager have all produk permissions while kurir has only produk-view', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $allPermissions = [
        'produk-view',
        'produk-create',
        'produk-edit',
        'produk-delete',
    ];

    foreach ($allPermissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue();
    }

    expect($kurirRole->hasPermissionTo('produk-view'))->toBeFalse()
        ->and($kurirRole->hasPermissionTo('produk-create'))->toBeFalse()
        ->and($kurirRole->hasPermissionTo('produk-edit'))->toBeFalse()
        ->and($kurirRole->hasPermissionTo('produk-delete'))->toBeFalse();
});

test('manager can access products index page and see list of products', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);
    get(route('admin.products'))
        ->assertOk()
        ->assertSee('Produk Jadi &amp; Harga', false)
        ->assertSee('Operasional')
        ->assertSee('Tambah Produk Baru');
});

test('manager can create a new product', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $unit = Unit::first();

    actingAs($manager);

    Livewire::test('admin.products.create')
        ->set('name', 'Keripik Tempe Renyah 200g')
        ->set('unit_id', $unit->id)
        ->set('consignment_price', 14000)
        ->set('retail_price', 18000)
        ->set('stock_ready', 25)
        ->set('description', 'Keripik tempe kedelai lokal renyah dengan daun jeruk')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.products'));

    $product = Product::where('name', 'Keripik Tempe Renyah 200g')->first();
    expect($product)->not->toBeNull()
        ->and((float) $product->consignment_price)->toBe(14000.0)
        ->and((float) $product->retail_price)->toBe(18000.0)
        ->and($product->stock_ready)->toBe(25)
        ->and($product->unit)->toBe($unit->short_name)
        ->and($product->is_active)->toBeTrue();
});

test('manager can edit an existing product', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $product = Product::first();

    actingAs($manager);

    Livewire::test('admin.products.edit', ['product' => $product])
        ->set('consignment_price', 16500)
        ->set('retail_price', 21000)
        ->set('stock_ready', 50)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.products'));

    $product->refresh();
    expect((float) $product->consignment_price)->toBe(16500.0)
        ->and((float) $product->retail_price)->toBe(21000.0)
        ->and($product->stock_ready)->toBe(50);
});

test('manager can toggle product active status', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $product = Product::first();

    actingAs($manager);

    $initialStatus = $product->is_active;

    Livewire::test('admin.products.index')
        ->call('toggleStatus', $product->id);

    $product->refresh();
    expect($product->is_active)->toBe(! $initialStatus);
});

test('deleting a product that is in active transactions is prevented', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $productWithTransactions = Product::whereHas('recipes')->first();

    actingAs($manager);

    delete(route('admin.products.destroy', $productWithTransactions))
        ->assertRedirect(route('admin.products'))
        ->assertSessionHas('toast', fn ($toast) => $toast['type'] === 'error');

    expect(Product::find($productWithTransactions->id))->not->toBeNull();
});

test('deleting a standalone product without relations succeeds', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $unit = Unit::first();

    $standalone = Product::create([
        'name' => 'Produk Uji Coba Tanpa Relasi',
        'unit_id' => $unit->id,
        'unit' => $unit->short_name,
        'consignment_price' => 10000,
        'retail_price' => 12000,
        'stock_ready' => 0,
    ]);

    actingAs($manager);

    delete(route('admin.products.destroy', $standalone))
        ->assertRedirect(route('admin.products'))
        ->assertSessionHas('toast', fn ($toast) => $toast['type'] === 'success');

    expect(Product::find($standalone->id))->toBeNull();
});
