<?php

use App\Models\Delivery;
use App\Models\FinishedStockMutation;
use App\Models\Partner;
use App\Models\ProductVariant;
use App\Models\User;
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

it('allows courier and manager to access deliveries page', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    actingAs($kurir);

    get(route('admin.deliveries'))
        ->assertOk()
        ->assertSee('Distribusi Surat Jalan')
        ->assertSee('Semua Surat Jalan')
        ->assertSee('Tugas Pengantaran Kurir');

    $manager = User::where('email', 'manager@halala-food.id')->first();
    actingAs($manager);

    get(route('admin.deliveries'))
        ->assertOk();
});

it('restricts unauthorized users from accessing deliveries page', function () {
    $guestRole = Role::firstOrCreate(['name' => 'regular_guest', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($guestRole);
    actingAs($user);

    get(route('admin.deliveries'))
        ->assertForbidden();
});

it('redirects unauthenticated users to login', function () {
    get(route('admin.deliveries'))
        ->assertRedirect(route('login'));
});

it('seeds initial deliveries properly', function () {
    expect(Delivery::count())->toBeGreaterThanOrEqual(3);

    $delivered = Delivery::where('status', 'delivered')->first();
    expect($delivered)->not->toBeNull()
        ->and($delivered->delivery_number)->toBe('SJ-20261001-0001')
        ->and($delivered->items->count())->toBeGreaterThan(0);
});

it('can create a new delivery order via Livewire saveDelivery method', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    $partner = Partner::first();
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    $variant = ProductVariant::first();

    actingAs($admin);

    $component = Livewire::test('admin.deliveries');

    $result = $component->instance()->saveDelivery(
        null,
        $partner->id,
        $kurir->id,
        'Catatan pengiriman test',
        [
            [
                'product_variant_id' => $variant->id,
                'qty_sent' => 20,
                'unit_price' => $variant->wholesale_price,
                'notes' => 'Pack kardus'
            ]
        ]
    );

    expect($result['success'])->toBeTrue();

    $newDelivery = Delivery::where('notes', 'Catatan pengiriman test')->first();
    expect($newDelivery)->not->toBeNull()
        ->and($newDelivery->status)->toBe('draft')
        ->and((float) $newDelivery->total_amount)->toBe(20 * (float) $variant->wholesale_price)
        ->and($newDelivery->items->count())->toBe(1);
});

it('validates minimum items when saving delivery order', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    $partner = Partner::first();

    actingAs($admin);

    $component = Livewire::test('admin.deliveries');

    $result = $component->instance()->saveDelivery(null, $partner->id, null, 'No items', []);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('minimal 1 item');
});

it('dispatches delivery and deducts finished goods inventory', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    $draft = Delivery::where('status', 'draft')->first();
    expect($draft)->not->toBeNull();

    $firstItem = $draft->items->first();
    $variant = $firstItem->productVariant;
    $initialStock = $variant->stock_qty;

    $component = Livewire::test('admin.deliveries');

    $result = $component->instance()->dispatchDelivery($draft->id);

    expect($result['success'])->toBeTrue();

    $draft->refresh();
    expect($draft->status)->toBe('on_the_way')
        ->and($draft->dispatched_at)->not->toBeNull();

    $variant->refresh();
    expect($variant->stock_qty)->toBe($initialStock - $firstItem->qty_sent);

    // Check finished stock mutation created
    $mutation = FinishedStockMutation::where('reference_type', 'delivery_out')
        ->where('reference_id', $draft->id)
        ->first();
    expect($mutation)->not->toBeNull();
});

it('confirms delivery receipt, updates receivables, and restores returned goods', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    actingAs($kurir);

    $onTheWay = Delivery::where('status', 'on_the_way')->first();
    expect($onTheWay)->not->toBeNull();

    $firstItem = $onTheWay->items->first();
    $variant = $firstItem->productVariant;
    $initialStock = $variant->stock_qty;
    $partner = $onTheWay->partner;
    $initialReceivable = (float) $partner->current_receivable;

    $sentQty = $firstItem->qty_sent;
    $acceptedQty = $sentQty - 2;
    $returnedQty = 2; // 2 returned items

    $component = Livewire::test('admin.deliveries');

    $itemResults = [
        [
            'product_variant_id' => $firstItem->product_variant_id,
            'qty_accepted' => $acceptedQty,
            'qty_returned' => $returnedQty,
        ]
    ];

    $result = $component->instance()->confirmDeliveryReceipt(
        $onTheWay->id,
        'Bpk. Agus (Kepala Gudang)',
        '08123456789',
        $itemResults
    );

    expect($result['success'])->toBeTrue();

    $onTheWay->refresh();
    expect($onTheWay->status)->toBe('delivered')
        ->and($onTheWay->receiver_name)->toBe('Bpk. Agus (Kepala Gudang)')
        ->and($onTheWay->delivered_at)->not->toBeNull();

    // Returned items should be restored to stock
    $variant->refresh();
    expect($variant->stock_qty)->toBe($initialStock + $returnedQty);

    // Partner receivable should increment
    $partner->refresh();
    expect((float) $partner->current_receivable)->toBeGreaterThan($initialReceivable);
});

it('restores deducted stock when cancelling an in-transit delivery', function () {
    $admin = User::where('email', 'admin@halala-food.id')->first();
    actingAs($admin);

    // Create a new draft and dispatch it
    $partner = Partner::first();
    $variant = ProductVariant::first();
    $initialStock = $variant->stock_qty;

    $delivery = Delivery::create([
        'delivery_number' => 'SJ-TEST-CANCEL',
        'partner_id' => $partner->id,
        'status' => 'draft',
        'total_amount' => 10 * (float) $variant->wholesale_price,
    ]);

    $delivery->items()->create([
        'product_variant_id' => $variant->id,
        'qty_sent' => 10,
        'unit_price' => $variant->wholesale_price,
        'subtotal' => 10 * (float) $variant->wholesale_price,
    ]);

    $component = Livewire::test('admin.deliveries');

    // Dispatch
    $component->instance()->dispatchDelivery($delivery->id);
    $variant->refresh();
    expect($variant->stock_qty)->toBe($initialStock - 10);

    // Now Cancel
    $cancelResult = $component->instance()->cancelDelivery($delivery->id);
    expect($cancelResult['success'])->toBeTrue();

    $delivery->refresh();
    expect($delivery->status)->toBe('cancelled');

    // Stock should be restored
    $variant->refresh();
    expect($variant->stock_qty)->toBe($initialStock);
});
