<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use App\Models\StockMutation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    \App\Models\Account::firstOrCreate(
        ['name' => 'Kas Tunai Usaha'],
        ['type' => 'business', 'balance' => 1000000.00]
    );

    $unit = \App\Models\Unit::firstOrCreate(['short_name' => 'g'], ['name' => 'Gram', 'is_active' => true]);

    \App\Models\RawMaterial::firstOrCreate(
        ['name' => 'Wijen Putih Sangrai'],
        [
            'unit_id' => $unit->id,
            'unit' => 'g',
            'stock' => 10000,
            'min_stock' => 2000,
            'cost_per_unit' => 0.05,
        ]
    );
});

test('unauthenticated users are redirected from purchases page to login', function () {
    get(route('admin.purchases'))
        ->assertRedirect(route('login'));
});

test('manager and dev have pembelian permissions while kurir does not', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $permissions = ['pembelian-view', 'pembelian-create', 'pembelian-delete'];

    foreach ($permissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue()
            ->and($kurirRole->hasPermissionTo($perm))->toBeFalse();
    }
});

test('manager can access purchases list and see sample data', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();

    actingAs($manager);
    get(route('admin.purchases'))
        ->assertOk()
        ->assertSee('Pengadaan Bahan Baku')
        ->assertSee('Catat Pembelian Baru');
});

test('manager can record new raw material purchase and stock increments automatically', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $material = RawMaterial::where('name', 'Wijen Putih Sangrai')->first();
    $initialStock = (float) $material->stock;

    actingAs($manager);

    Livewire::test('admin.purchases.create')
        ->set('purchase_number', 'BELI-TEST-0001')
        ->set('supplier_name', 'Supplier Aneka Tepung')
        ->set('purchase_date', now()->toDateString())
        ->set('payment_method', 'tunai')
        ->set('notes', 'Pembelian darurat tambahan wijen.')
        ->set('items', [
            [
                'raw_material_id' => $material->id,
                'quantity' => 2500, // 2.5 kg
                'cost_per_unit' => 0.07,
                'subtotal' => 175.0,
                'notes' => '1 karung kecil',
            ],
        ])
        ->call('save')
        ->assertRedirect(route('admin.purchases'));

    $purchase = RawMaterialPurchase::where('purchase_number', 'BELI-TEST-0001')->first();
    expect($purchase)->not->toBeNull()
        ->and($purchase->supplier_name)->toBe('Supplier Aneka Tepung');

    $material->refresh();
    expect((float) $material->stock)->toBe($initialStock + 2500)
        ->and((float) $material->cost_per_unit)->toBe(0.07);

    // Verify stock mutation created
    $mutation = StockMutation::where('reference_type', 'purchase')
        ->where('reference_id', $purchase->id)
        ->first();

    expect($mutation)->not->toBeNull()
        ->and($mutation->type)->toBe('in')
        ->and((float) $mutation->quantity)->toBe(2500.0);

    // Verify CashTransaction created in Buku Kas
    $cashTx = \App\Models\CashTransaction::where('reference_type', 'purchase')
        ->where('reference_id', $purchase->id)
        ->first();

    expect($cashTx)->not->toBeNull()
        ->and($cashTx->type)->toBe('expense')
        ->and($cashTx->category)->toBe('Belanja Bahan Baku')
        ->and((float) $cashTx->amount)->toBe(175.0);
});

test('manager can cancel purchase and rollback stock', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $material = RawMaterial::where('name', 'Wijen Putih Sangrai')->first();
    $initialStock = (float) $material->stock;

    $purchase = RawMaterialPurchase::create([
        'purchase_number' => 'BELI-CANCEL-TEST',
        'supplier_name' => 'Supplier Batal',
        'purchase_date' => now()->toDateString(),
        'total_amount' => 100000.00,
        'payment_method' => 'tunai',
        'created_by' => $manager->id,
    ]);

    $purchase->items()->create([
        'raw_material_id' => $material->id,
        'quantity' => 1000,
        'cost_per_unit' => 100,
        'subtotal' => 100000,
    ]);

    $material->increment('stock', 1000);

    actingAs($manager);

    Livewire::test('admin.purchases.index')
        ->call('deletePurchase', $purchase->id);

    expect(RawMaterialPurchase::find($purchase->id))->toBeNull();

    $material->refresh();
    expect((float) $material->stock)->toBe($initialStock);
});

test('package mode auto-calculates price_per_package, stock quantity, and HPP when subtotal is entered', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $material = RawMaterial::where('name', 'Wijen Putih Sangrai')->first();
    $initialStock = (float) $material->stock;

    actingAs($manager);

    $component = Livewire::test('admin.purchases.create')
        ->set('purchase_number', 'BELI-PKG-0001')
        ->set('supplier_name', 'Toko Grosir Bahan')
        ->set('purchase_date', now()->toDateString())
        ->set('payment_method', 'tunai')
        ->set('items.0.raw_material_id', $material->id)
        ->set('items.0.package_count', 10)
        ->set('items.0.content_per_package', 500)
        ->set('items.0.subtotal', 400000);

    // Verify bi-directional auto-calculation
    expect($component->get('items.0.quantity'))->toBe(5000.0)
        ->and((float) $component->get('items.0.price_per_package'))->toBe(40000.0)
        ->and((float) $component->get('items.0.cost_per_unit'))->toBe(80.0);

    $component->call('save')
        ->assertRedirect(route('admin.purchases'));

    $purchase = RawMaterialPurchase::where('purchase_number', 'BELI-PKG-0001')->first();
    expect($purchase)->not->toBeNull()
        ->and((float) $purchase->total_amount)->toBe(400000.0);

    $item = $purchase->items()->first();
    expect((float) $item->quantity)->toBe(5000.0)
        ->and((float) $item->cost_per_unit)->toBe(80.0)
        ->and((float) $item->subtotal)->toBe(400000.0)
        ->and($item->notes)->toContain('10 kemasan');

    $material->refresh();
    expect((float) $material->stock)->toBe($initialStock + 5000.0)
        ->and((float) $material->cost_per_unit)->toBe(80.0);
});

test('package mode auto-calculates subtotal and HPP when price_per_package is entered', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $material = RawMaterial::where('name', 'Wijen Putih Sangrai')->first();
    $initialStock = (float) $material->stock;

    actingAs($manager);

    $component = Livewire::test('admin.purchases.create')
        ->set('purchase_number', 'BELI-PKG-0002')
        ->set('supplier_name', 'Depo Air & Tepung')
        ->set('purchase_date', now()->toDateString())
        ->set('payment_method', 'tunai')
        ->set('items.0.raw_material_id', $material->id)
        ->set('items.0.package_count', 4)
        ->set('items.0.content_per_package', 250)
        ->set('items.0.price_per_package', 15000);

    // Verify bi-directional auto-calculation: 4 x 15000 = 60000, 4 x 250 = 1000g, HPP = 60000 / 1000 = 60
    expect($component->get('items.0.quantity'))->toBe(1000.0)
        ->and((float) $component->get('items.0.subtotal'))->toBe(60000.0)
        ->and((float) $component->get('items.0.cost_per_unit'))->toBe(60.0);

    $component->call('save')
        ->assertRedirect(route('admin.purchases'));

    $purchase = RawMaterialPurchase::where('purchase_number', 'BELI-PKG-0002')->first();
    expect($purchase)->not->toBeNull()
        ->and((float) $purchase->total_amount)->toBe(60000.0);

    $material->refresh();
    expect((float) $material->stock)->toBe($initialStock + 1000.0)
        ->and((float) $material->cost_per_unit)->toBe(60.0);
});

