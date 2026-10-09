<?php

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
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

    $unit = Unit::firstOrCreate(['short_name' => 'bungkus'], ['name' => 'Bungkus', 'is_active' => true]);

    $product = Product::firstOrCreate(
        ['name' => 'Marie Wijen'],
        [
            'unit_id' => $unit->id,
            'consignment_price' => 12000,
            'retail_price' => 15000,
            'stock' => 50,
            'stock_ready' => 50,
            'min_stock' => 10,
            'is_active' => true,
        ]
    );

    $store = Store::firstOrCreate(
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

    $kurir = User::where('email', 'kurir@halala-food.id')->first();

    $delivery = Delivery::firstOrCreate(
        ['delivery_number' => 'SJ-2026-0001'],
        [
            'store_id' => $store->id,
            'courier_id' => $kurir?->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'diproses',
            'total_items' => 10,
            'total_value' => 120000,
            'notes' => 'Pengantaran stok pagi',
        ]
    );

    DeliveryItem::firstOrCreate(
        ['delivery_id' => $delivery->id, 'product_id' => $product->id],
        [
            'quantity' => 10,
            'unit_price' => 12000,
            'subtotal' => 120000,
        ]
    );
});

test('unauthenticated users are redirected from deliveries page to login', function () {
    get(route('admin.deliveries'))
        ->assertRedirect(route('login'));
});

test('role dev and manager have all pengantaran permissions while kurir has view and status', function () {
    $devRole = Role::findByName('dev', 'web');
    $managerRole = Role::findByName('manager', 'web');
    $kurirRole = Role::findByName('kurir', 'web');

    $allPermissions = [
        'pengantaran-view',
        'pengantaran-create',
        'pengantaran-edit',
        'pengantaran-status',
        'pengantaran-delete',
    ];

    foreach ($allPermissions as $perm) {
        expect($devRole->hasPermissionTo($perm))->toBeTrue()
            ->and($managerRole->hasPermissionTo($perm))->toBeTrue();
    }

    expect($kurirRole->hasPermissionTo('pengantaran-view'))->toBeTrue()
        ->and($kurirRole->hasPermissionTo('pengantaran-status'))->toBeTrue()
        ->and($kurirRole->hasPermissionTo('pengantaran-edit'))->toBeFalse()
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

test('courier can complete delivery with proof photo and digital signature', function () {
    Storage::fake('public');

    $kurir = User::where('email', 'kurir@halala-food.id')->first();
    $delivery = Delivery::where('status', 'diproses')->first();

    actingAs($kurir);

    // 1. Dispatch delivery
    Livewire::test('admin.deliveries.show', ['delivery' => $delivery])
        ->call('startDelivery');

    $delivery->refresh();
    expect($delivery->status)->toBe('dikirim');

    // 2. Complete handover with photo and signature
    $file = UploadedFile::fake()->image('bukti_drop.jpg');
    $fakeSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    Livewire::test('admin.deliveries.show', ['delivery' => $delivery])
        ->set('recipient_name', 'Ibu Hj. Aminah')
        ->set('recipient_role', 'Pemilik Toko')
        ->set('recipient_phone', '081234567890')
        ->set('proof_photo', $file)
        ->set('signature_data', $fakeSignature)
        ->set('handover_notes', 'Semua barang diterima dalam kondisi baik')
        ->call('completeDelivery')
        ->assertHasNoErrors();

    $delivery->refresh();
    expect($delivery->status)->toBe('selesai')
        ->and($delivery->recipient_name)->toBe('Ibu Hj. Aminah')
        ->and($delivery->recipient_role)->toBe('Pemilik Toko')
        ->and($delivery->proof_image)->not->toBeNull()
        ->and($delivery->signature_data)->toBe($fakeSignature);

    expect(Storage::disk('public')->exists($delivery->proof_image))->toBeTrue();

    // Verify invoice is automatically generated
    $invoice = Invoice::with('items')->where('delivery_id', $delivery->id)->first();
    expect($invoice)->not->toBeNull()
        ->and($invoice->store_id)->toBe($delivery->store_id)
        ->and($invoice->status)->toBe('belum_dibayar')
        ->and((float) $invoice->total_amount)->toBe((float) $delivery->total_amount)
        ->and((float) $invoice->remaining_balance)->toBe((float) $delivery->total_amount)
        ->and($invoice->items->count())->toBe($delivery->items->count());

    // Verify rendered in view
    get(route('admin.deliveries.show', $delivery))
        ->assertOk()
        ->assertSee('Foto Bukti Serah Terima')
        ->assertSee('Tanda Tangan Digital Penerima')
        ->assertSee('Faktur Piutang Toko')
        ->assertSee($invoice->invoice_number)
        ->assertSee('Lihat Faktur');
});

test('courier can complete delivery handover with client-compressed photo_data base64', function () {
    Storage::fake('public');

    $courier = User::where('email', 'kurir@halala-food.id')->first();
    $delivery = Delivery::where('status', 'diproses')->first();

    actingAs($courier);

    // 1. Dispatch
    Livewire::test('admin.deliveries.show', ['delivery' => $delivery])
        ->call('startDelivery')
        ->assertHasNoErrors();

    $delivery->refresh();
    expect($delivery->status)->toBe('dikirim');

    // 2. Base64 WebP sample photo (compressed by client-side browser engine)
    $fakeBase64Photo = 'data:image/webp;base64,UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';

    Livewire::test('admin.deliveries.show', ['delivery' => $delivery])
        ->set('recipient_name', 'Bpk. Hendra')
        ->set('recipient_role', 'Kepala Toko')
        ->set('photo_data', $fakeBase64Photo)
        ->set('handover_notes', 'Diterima oleh kepala toko langsung')
        ->call('completeDelivery')
        ->assertHasNoErrors();

    $delivery->refresh();
    expect($delivery->status)->toBe('selesai')
        ->and($delivery->recipient_name)->toBe('Bpk. Hendra')
        ->and($delivery->proof_image)->not->toBeNull()
        ->and($delivery->proof_image)->toContain('delivery-proofs/');

    expect(Storage::disk('public')->exists($delivery->proof_image))->toBeTrue();
});

test('courier in web interface can only view their own deliveries, and cannot open detail, edit, or delete another couriers delivery', function () {
    $kurir1 = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $kurir2 = User::updateOrCreate(
        ['email' => 'kurir-b@halala-food.id'],
        [
            'name' => 'Kurir B Web',
            'phone' => '089988776655',
            'password' => bcrypt('admin123'),
        ]
    );
    $kurir2->syncRoles(['kurir']);

    $store = Store::first();

    $delivery1 = Delivery::create([
        'delivery_number' => 'SJ-WEB-K1-001',
        'store_id' => $store->id,
        'courier_id' => $kurir1->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 5,
        'total_amount' => 50000,
    ]);

    $delivery2 = Delivery::create([
        'delivery_number' => 'SJ-WEB-K2-002',
        'store_id' => $store->id,
        'courier_id' => $kurir2->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 5,
        'total_amount' => 50000,
    ]);

    actingAs($kurir1);

    // 1. Livewire index only includes kurir1's deliveries
    $indexTest = Livewire::test('admin.deliveries.index')
        ->assertSee($delivery1->delivery_number)
        ->assertDontSee($delivery2->delivery_number);

    // 2. Courier 1 can view own delivery show page, but is blocked (403) from courier 2's delivery
    get(route('admin.deliveries.show', $delivery1))
        ->assertOk();

    get(route('admin.deliveries.show', $delivery2))
        ->assertForbidden();

    // 3. Courier 1 cannot mount show Livewire component for courier 2
    Livewire::test('admin.deliveries.show', ['delivery' => $delivery2])
        ->assertForbidden();

    // 4. Courier 1 cannot mount edit Livewire component for courier 2
    Livewire::test('admin.deliveries.edit', ['delivery' => $delivery2])
        ->assertForbidden();

    // 5. Courier 1 cannot delete courier 2's delivery via route
    delete(route('admin.deliveries.destroy', $delivery2))
        ->assertForbidden();

    // 6. Courier 1 action on index component for courier 2 is rejected (delivery remains unchanged)
    $indexTest->call('cancelDelivery', $delivery2->id);
    expect($delivery2->fresh()->status)->toBe('diproses');

    $indexTest->call('deleteDelivery', $delivery2->id);
    expect(Delivery::find($delivery2->id))->not->toBeNull();

    $indexTest->call('markAsDispatched', $delivery2->id);
    expect($delivery2->fresh()->status)->toBe('diproses');
});
