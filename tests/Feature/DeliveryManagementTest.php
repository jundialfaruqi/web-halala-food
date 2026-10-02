<?php

use App\Models\Delivery;
use App\Models\Product;
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

test('unauthenticated users are redirected from deliveries page to login', function () {
    get(route('admin.deliveries'))
        ->assertRedirect(route('login'));
});

test('role dev and manager have all pengantaran permissions while kurir has view and edit', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $allPermissions = [
        'pengantaran-view',
        'pengantaran-create',
        'pengantaran-edit',
        'pengantaran-delete',
    ];

    foreach ($allPermissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue();
    }

    expect($kurirRole->hasPermissionTo('pengantaran-view'))->toBeTrue()
        ->and($kurirRole->hasPermissionTo('pengantaran-edit'))->toBeTrue()
        ->and($kurirRole->hasPermissionTo('pengantaran-create'))->toBeFalse()
        ->and($kurirRole->hasPermissionTo('pengantaran-delete'))->toBeFalse();
});

test('dev, manager, and kurir can access deliveries index page', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $kurir = User::where('email', 'kurir@halala-food.id')->first();

    actingAs($dev);
    get(route('admin.deliveries'))
        ->assertOk()
        ->assertSee('Surat Jalan &amp; Pengantaran', false)
        ->assertSee('SJ-');

    actingAs($manager);
    get(route('admin.deliveries'))
        ->assertOk();

    actingAs($kurir);
    get(route('admin.deliveries'))
        ->assertOk();
});

test('manager can create a new delivery order and ready stock is deducted', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $store = Store::first();
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    $product = Product::where('stock_ready', '>=', 5)->first();

    $initialStock = $product->stock_ready;
    $qtyToSend = 5;

    actingAs($manager);

    Livewire::test('admin.deliveries.create')
        ->set('delivery_number', 'SJ-TEST-0001')
        ->set('store_id', $store->id)
        ->set('courier_id', $kurir->id)
        ->set('delivery_date', now()->toDateString())
        ->set('notes', 'Pengiriman uji coba')
        ->set('items', [
            [
                'product_id' => $product->id,
                'quantity' => $qtyToSend,
                'unit_price' => (float) $product->consignment_price,
                'stock_ready' => $initialStock,
                'subtotal' => $qtyToSend * (float) $product->consignment_price,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $delivery = Delivery::where('delivery_number', 'SJ-TEST-0001')->first();
    expect($delivery)->not->toBeNull()
        ->and($delivery->store_id)->toBe($store->id)
        ->and($delivery->courier_id)->toBe($kurir->id)
        ->and($delivery->status)->toBe('diproses')
        ->and($delivery->total_items)->toBe($qtyToSend);

    // Verify product stock is decremented
    $product->refresh();
    expect($product->stock_ready)->toBe($initialStock - $qtyToSend);
});

test('validation prevents creating delivery with quantity exceeding ready stock', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $store = Store::first();
    $product = Product::first();

    actingAs($manager);

    Livewire::test('admin.deliveries.create')
        ->set('delivery_number', 'SJ-EXCEED-0001')
        ->set('store_id', $store->id)
        ->set('delivery_date', now()->toDateString())
        ->set('items', [
            [
                'product_id' => $product->id,
                'quantity' => $product->stock_ready + 999, // Exceeds available stock
                'unit_price' => (float) $product->consignment_price,
                'stock_ready' => $product->stock_ready,
                'subtotal' => ($product->stock_ready + 999) * (float) $product->consignment_price,
            ],
        ])
        ->call('save')
        ->assertHasErrors(['items.0.quantity']);
});

test('courier or manager can dispatch delivery and complete handover', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    $delivery = Delivery::where('status', 'diproses')->first();

    actingAs($kurir);

    // 1. Dispatch delivery
    Livewire::test('admin.deliveries.show', ['delivery' => $delivery])
        ->call('startDelivery')
        ->assertHasNoErrors();

    $delivery->refresh();
    expect($delivery->status)->toBe('dikirim')
        ->and($delivery->dispatched_at)->not->toBeNull();

    // 2. Complete handover
    Livewire::test('admin.deliveries.show', ['delivery' => $delivery])
        ->set('recipient_name', 'Ibu Hj. Aminah (Pemilik Toko)')
        ->set('recipient_phone', '081234567890')
        ->set('handover_notes', 'Diterima utuh tanpa ada cacat')
        ->call('completeDelivery')
        ->assertHasNoErrors();

    $delivery->refresh();
    expect($delivery->status)->toBe('selesai')
        ->and($delivery->delivered_at)->not->toBeNull()
        ->and($delivery->recipient_name)->toBe('Ibu Hj. Aminah (Pemilik Toko)');
});

test('cancelling a delivery restores product stock', function () {
    $manager = User::where('email', 'manager@halala-food.id')->first();
    $delivery = Delivery::with('items')->where('status', 'diproses')->first();
    $item = $delivery->items->first();
    $product = Product::find($item->product_id);

    $stockBeforeCancel = $product->stock_ready;

    actingAs($manager);

    Livewire::test('admin.deliveries.show', ['delivery' => $delivery])
        ->call('cancelDelivery')
        ->assertHasNoErrors();

    $delivery->refresh();
    $product->refresh();

    expect($delivery->status)->toBe('dibatalkan')
        ->and($product->stock_ready)->toBe($stockBeforeCancel + $item->quantity);
});

test('deleting a delivery restores stock and completed delivery cannot be deleted', function () {
    $dev = User::where('email', 'developer@halala-food.id')->first();
    $delivery = Delivery::with('items')->where('status', 'diproses')->first();
    $item = $delivery->items->first();
    $product = Product::find($item->product_id);
    $stockBefore = $product->stock_ready;

    actingAs($dev);

    // Delete diproses delivery
    delete(route('admin.deliveries.destroy', $delivery))
        ->assertRedirect(route('admin.deliveries'));

    expect(Delivery::find($delivery->id))->toBeNull();
    $product->refresh();
    expect($product->stock_ready)->toBe($stockBefore + $item->quantity);

    // Try deleting completed delivery
    $completedDelivery = Delivery::create([
        'delivery_number' => 'SJ-COMPLETED-001',
        'store_id' => Store::first()->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'selesai',
        'recipient_name' => 'Pak Budi',
    ]);

    delete(route('admin.deliveries.destroy', $completedDelivery))
        ->assertRedirect(route('admin.deliveries'));

    // Should still exist
    expect(Delivery::find($completedDelivery->id))->not->toBeNull();
});
