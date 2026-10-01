<?php

use App\Models\FinishedStockMutation;
use App\Models\ProductVariant;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\StockMutation;
use App\Models\User;
use App\Models\WasteLog;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

it('redirects unauthenticated user from production page to login', function () {
    get(route('admin.production'))
        ->assertRedirect(route('login'));
});

it('renders production and kitchen component successfully for authorized user', function () {
    $cook = User::where('email', 'masak@halala-food.id')->first();
    actingAs($cook);

    get(route('admin.production'))
        ->assertOk()
        ->assertSee('Dapur')
        ->assertSee('Batch Produksi')
        ->assertSee('Mutasi Bahan Baku')
        ->assertSee('Mutasi Barang Jadi')
        ->assertSee('Bahan Rusak');

    Livewire::test('admin.production')
        ->assertStatus(200)
        ->assertSee('Dapur')
        ->assertSee('PRD-20261001-0001')
        ->assertSee('PRD-20261001-0002');
});

it('forbids unauthorized user without production permissions from accessing production page', function () {
    $guestRole = Role::firstOrCreate(['name' => 'regular_guest', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($guestRole);
    actingAs($user);

    get(route('admin.production'))
        ->assertForbidden();
});

it('can create a new production batch plan in draft status', function () {
    $cook = User::where('email', 'masak@halala-food.id')->first();
    actingAs($cook);

    $recipe = Recipe::whereHas('productVariant', fn($q) => $q->where('sku_code', 'TTS-PCH-200G'))->first();

    $component = Livewire::test('admin.production');
    $result = $component->instance()->saveBatch(
        null,
        $recipe->id,
        $cook->id,
        100, // 100 pack
        'Rencana produksi batch sore 100 pouch'
    );

    expect($result['success'])->toBeTrue();

    $newBatch = ProductionBatch::where('notes', 'Rencana produksi batch sore 100 pouch')->first();
    expect($newBatch)->not->toBeNull()
        ->and($newBatch->status)->toBe('draft')
        ->and($newBatch->planned_qty)->toBe(100)
        ->and($newBatch->materials->count())->toBeGreaterThan(0);
});

it('can start a batch, deduct raw materials, and create stock mutations', function () {
    $cook = User::where('email', 'masak@halala-food.id')->first();
    actingAs($cook);

    $recipe = Recipe::whereHas('productVariant', fn($q) => $q->where('sku_code', 'TTS-PCH-200G'))->first();

    // Create a draft batch of 50 packs
    $batch = ProductionBatch::create([
        'batch_number' => 'PRD-TEST-0001',
        'recipe_id' => $recipe->id,
        'user_id' => $cook->id,
        'planned_qty' => 50,
        'status' => 'draft',
    ]);

    foreach ($recipe->items as $item) {
        $batch->materials()->create([
            'raw_material_id' => $item->raw_material_id,
            'planned_qty' => $item->quantity_required,
            'actual_used_qty' => $item->quantity_required,
            'cost_per_unit' => $item->rawMaterial?->average_cost ?? 0,
            'subtotal_cost' => ($item->rawMaterial?->average_cost ?? 0) * (float) $item->quantity_required,
        ]);
    }

    $susu = RawMaterial::where('code', 'BB-SSU-01')->first();
    $initialStock = (float) $susu->stock_qty;

    $component = Livewire::test('admin.production');
    $result = $component->instance()->startBatch($batch->id);

    expect($result['success'])->toBeTrue();

    $batch->refresh();
    expect($batch->status)->toBe('in_progress')
        ->and($batch->started_at)->not->toBeNull();

    // Check raw material stock deduction
    $susu->refresh();
    expect((float) $susu->stock_qty)->toBeLessThan($initialStock);

    // Verify stock mutation created
    $mutation = StockMutation::where('reference_id', $batch->id)
        ->where('reference_type', 'production_usage')
        ->where('raw_material_id', $susu->id)
        ->first();
    expect($mutation)->not->toBeNull()
        ->and($mutation->type)->toBe('out');
});

it('can finish a batch, add finished goods stock, and calculate actual HPP', function () {
    $cook = User::where('email', 'masak@halala-food.id')->first();
    actingAs($cook);

    $recipe = Recipe::whereHas('productVariant', fn($q) => $q->where('sku_code', 'TTS-PCH-200G'))->first();
    $variant = $recipe->productVariant;
    $initialVariantStock = $variant->stock_qty;

    // Create an in_progress batch
    $batch = ProductionBatch::create([
        'batch_number' => 'PRD-TEST-FINISH',
        'recipe_id' => $recipe->id,
        'user_id' => $cook->id,
        'planned_qty' => 50,
        'total_material_cost' => 500000,
        'status' => 'in_progress',
        'started_at' => now()->subHour(),
    ]);

    $component = Livewire::test('admin.production');
    $result = $component->instance()->finishBatch($batch->id, 48, 2, 'Kemas 48 pack bagus, 2 pack remuk');

    expect($result['success'])->toBeTrue();

    $batch->refresh();
    expect($batch->status)->toBe('completed')
        ->and($batch->actual_qty_good)->toBe(48)
        ->and($batch->actual_qty_bad)->toBe(2)
        ->and($batch->completed_at)->not->toBeNull()
        ->and((float) $batch->unit_cost_produced)->toBeGreaterThan(0);

    // Verify finished stock added
    $variant->refresh();
    expect($variant->stock_qty)->toBe($initialVariantStock + 48);

    // Verify finished stock mutation
    $fgMutation = FinishedStockMutation::where('reference_id', $batch->id)
        ->where('reference_type', 'production_in')
        ->first();
    expect($fgMutation)->not->toBeNull()
        ->and($fgMutation->quantity)->toBe(48);
});

it('can record raw material waste and deduct warehouse stock', function () {
    $cook = User::where('email', 'masak@halala-food.id')->first();
    actingAs($cook);

    $wijen = RawMaterial::where('code', 'BB-WJN-01')->first();
    $initialStock = (float) $wijen->stock_qty;

    $component = Livewire::test('admin.production');
    $result = $component->instance()->saveWasteLog(
        $wijen->id,
        1000, // 1 kg
        'expired',
        'Wijen bau tengik karena kemasan terbuka'
    );

    expect($result['success'])->toBeTrue();

    $wijen->refresh();
    expect((float) $wijen->stock_qty)->toBe($initialStock - 1000);

    $waste = WasteLog::where('notes', 'Wijen bau tengik karena kemasan terbuka')->first();
    expect($waste)->not->toBeNull()
        ->and((float) $waste->cost_loss)->toBe(1000 * (float) $wijen->average_cost);
});

it('can adjust stock for raw materials and finished goods via opname', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    $tepung = RawMaterial::where('code', 'BB-TPG-01')->first();
    $variant = ProductVariant::where('sku_code', 'TTS-PCH-200G')->first();

    $component = Livewire::test('admin.production');

    // 1. Raw material stock opname adjustment
    $resRaw = $component->instance()->adjustRawStock($tepung->id, 60000, 'Audit stok fisik opname');
    expect($resRaw['success'])->toBeTrue();

    $tepung->refresh();
    expect((float) $tepung->stock_qty)->toBe(60000.00);

    // 2. Finished goods stock opname adjustment
    $resFG = $component->instance()->adjustFinishedStock($variant->id, 200, 'Audit display rak toko');
    expect($resFG['success'])->toBeTrue();

    $variant->refresh();
    expect($variant->stock_qty)->toBe(200);
});
