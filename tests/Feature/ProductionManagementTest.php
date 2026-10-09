<?php

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Models\StockMutation;
use App\Models\Unit;
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

    $wijen = RawMaterial::firstOrCreate(
        ['name' => 'Wijen Putih Sangrai'],
        [
            'unit_id' => $rawUnit->id,
            'unit' => 'g',
            'stock' => 15000.00,
            'min_stock' => 5000.00,
            'cost_per_unit' => 0.05,
        ]
    );

    ProductRecipe::firstOrCreate([
        'product_id' => $product->id,
        'raw_material_id' => $wijen->id,
    ], [
        'quantity_needed' => 30.00,
    ]);
});

test('unauthenticated users are redirected from production page to login', function () {
    get(route('admin.production'))
        ->assertRedirect(route('login'));
});

test('both role dev and manager have produksi permissions while kurir does not', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $permissions = [
        'produksi-view',
        'produksi-create',
        'produksi-edit',
        'produksi-delete',
    ];

    foreach ($permissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue()
            ->and($kurirRole->hasPermissionTo($perm))->toBeFalse();
    }
});

test('dev and manager can access production page but kurir is forbidden', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $kurir = User::where('email', 'kurir@halala-food.id')->first();

    actingAs($dev);
    get(route('admin.production'))
        ->assertOk()
        ->assertSee('Produksi &amp; Manufaktur', false)
        ->assertSee('Riwayat Batch Masak');

    actingAs($manager);
    get(route('admin.production'))
        ->assertOk()
        ->assertSee('Produksi &amp; Manufaktur', false)
        ->assertSee('Kartu Stok Bahan Baku');

    actingAs($kurir);
    get(route('admin.production'))
        ->assertForbidden();
});

test('sidebar displays Produksi menu for dev and manager and hides for kurir', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $kurir = User::where('email', 'kurir@halala-food.id')->first();

    actingAs($dev);
    get(route('admin.dashboard'))
        ->assertSee('Operasional')
        ->assertSee('Produksi (Batch Masak)');

    actingAs($manager);
    get(route('admin.dashboard'))
        ->assertSee('Operasional')
        ->assertSee('Produksi (Batch Masak)');

    actingAs($kurir);
    get(route('admin.dashboard'))
        ->assertDontSee('Operasional')
        ->assertDontSee('Produksi (Batch Masak)');
});

test('cannot execute production for product without recipes', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $emptyProduct = Product::create([
        'name' => 'Kue Kering Baru',
        'unit' => 'toples',
        'consignment_price' => 20000,
        'retail_price' => 25000,
        'stock_ready' => 0,
        'is_active' => true,
    ]);

    $component = Livewire::test('admin.production.index');
    $res = $component->instance()->executeBatch([
        'product_id' => $emptyProduct->id,
        'planned_qty' => 10,
        'actual_qty_good' => 10,
        'actual_qty_bad' => 0,
    ]);

    expect($res['success'])->toBeFalse()
        ->and($res['message'])->toContain('belum memiliki formula resep');
});

test('cannot execute production if material stock is insufficient', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $product = Product::where('name', 'Marie Wijen')->first();

    // Set stock to 0 to simulate shortage
    RawMaterial::where('name', 'Wijen Putih Sangrai')->update(['stock' => 0]);

    $component = Livewire::test('admin.production.index');
    $res = $component->instance()->executeBatch([
        'product_id' => $product->id,
        'planned_qty' => 100,
        'actual_qty_good' => 100,
        'actual_qty_bad' => 0,
    ]);

    expect($res['success'])->toBeFalse()
        ->and($res['message'])->toContain('Stok bahan baku tidak mencukupi');
});

test('successfully executes production batch, deducts materials, creates mutations, and increases finished stock', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $product = Product::with('recipes.rawMaterial')->where('name', 'Marie Wijen')->first();
    $initialStockReady = $product->stock_ready;

    // Record initial stocks of raw materials
    $materialsInitial = [];
    foreach ($product->recipes as $r) {
        $materialsInitial[$r->raw_material_id] = (float) $r->rawMaterial->stock;
    }

    $plannedQty = 20;
    $goodQty = 18;
    $badQty = 2;

    $component = Livewire::test('admin.production.index');
    $res = $component->instance()->executeBatch([
        'product_id' => $product->id,
        'planned_qty' => $plannedQty,
        'actual_qty_good' => $goodQty,
        'actual_qty_bad' => $badQty,
        'notes' => 'Testing batch eksekusi',
    ]);

    expect($res['success'])->toBeTrue()
        ->and($res['message'])->toContain('berhasil diselesaikan');

    // 1. Verify Finished Product stock increased
    $product->refresh();
    expect($product->stock_ready)->toBe($initialStockReady + $goodQty);

    // 2. Verify ProductionBatch record
    $batch = ProductionBatch::where('product_id', $product->id)
        ->orderByDesc('id')
        ->first();

    expect($batch)->not->toBeNull()
        ->and($batch->planned_qty)->toBe($plannedQty)
        ->and($batch->actual_qty_good)->toBe($goodQty)
        ->and($batch->actual_qty_bad)->toBe($badQty)
        ->and($batch->status)->toBe('completed')
        ->and($batch->unit_cost_produced)->toBeGreaterThan(0);

    // 3. Verify Raw Materials deducted and StockMutations created
    foreach ($product->recipes as $r) {
        $rawMat = $r->rawMaterial->fresh();
        $expectedUsed = (float) $r->quantity_needed * $plannedQty;
        $expectedStock = $materialsInitial[$r->raw_material_id] - $expectedUsed;

        expect((float) $rawMat->stock)->toEqualWithDelta($expectedStock, 0.01);

        // Check StockMutation record
        $mutation = StockMutation::where('raw_material_id', $r->raw_material_id)
            ->where('reference_number', $batch->batch_code)
            ->where('type', 'out')
            ->first();

        expect($mutation)->not->toBeNull()
            ->and((float) $mutation->quantity)->toEqualWithDelta($expectedUsed, 0.01)
            ->and((float) $mutation->stock_after)->toEqualWithDelta($expectedStock, 0.01);
    }
});

test('cancelling a batch restores raw materials and decrements product ready stock', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    actingAs($dev);

    $product = Product::where('name', 'Marie Wijen')->first();
    $wijen = RawMaterial::where('name', 'Wijen Putih Sangrai')->first();

    $batch = ProductionBatch::create([
        'batch_code' => 'BATCH-TEST-CANCEL',
        'product_id' => $product->id,
        'user_id' => $dev->id,
        'planned_qty' => 10,
        'actual_qty_good' => 10,
        'actual_qty_bad' => 0,
        'total_material_cost' => 15000.0,
        'unit_cost_produced' => 1500.0,
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    ProductionBatchMaterial::create([
        'production_batch_id' => $batch->id,
        'raw_material_id' => $wijen->id,
        'unit_name' => 'g',
        'planned_qty' => 300.0,
        'actual_used_qty' => 300.0,
        'cost_per_unit' => 50.0,
        'subtotal_cost' => 15000.0,
    ]);

    $batch->load(['product', 'batchMaterials.rawMaterial']);

    $product = $batch->product;
    $initialProductStock = $product->stock_ready;

    // Track materials before cancel
    $materialsBefore = [];
    foreach ($batch->batchMaterials as $bm) {
        $materialsBefore[$bm->raw_material_id] = (float) $bm->rawMaterial->stock;
    }

    $component = Livewire::test('admin.production.index');
    $res = $component->instance()->cancelBatch($batch->id);

    expect($res['success'])->toBeTrue()
        ->and($res['message'])->toContain('berhasil dibatalkan');

    // 1. Verify batch status is cancelled
    $batch->refresh();
    expect($batch->status)->toBe('cancelled');

    // 2. Verify product stock was decremented back
    $product->refresh();
    expect($product->stock_ready)->toBe($initialProductStock - $batch->actual_qty_good);

    // 3. Verify raw materials were restored
    foreach ($batch->batchMaterials as $bm) {
        $rawMat = $bm->rawMaterial->fresh();
        $expectedRestored = $materialsBefore[$bm->raw_material_id] + (float) $bm->actual_used_qty;
        expect((float) $rawMat->stock)->toEqualWithDelta($expectedRestored, 0.01);

        // Verify restoration mutation
        $restoreMutation = StockMutation::where('raw_material_id', $bm->raw_material_id)
            ->where('reference_number', $batch->batch_code)
            ->where('type', 'in')
            ->first();

        expect($restoreMutation)->not->toBeNull();
    }
});
