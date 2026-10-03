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
