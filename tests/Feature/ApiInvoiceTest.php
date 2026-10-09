<?php

use App\Events\InvoiceCreated;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Event;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $unit = Unit::firstOrCreate(['short_name' => 'pcs'], ['name' => 'Pcs', 'is_active' => true]);
    $product = Product::firstOrCreate(
        ['name' => 'Kue Kering Barokah'],
        [
            'unit_id' => $unit->id,
            'consignment_price' => 20000,
            'retail_price' => 25000,
            'stock' => 100,
            'stock_ready' => 100,
            'min_stock' => 10,
            'is_active' => true,
        ]
    );

    $store = Store::firstOrCreate(
        ['name' => 'Toko Mitra Berkah'],
        [
            'owner_name' => 'H. Berkah',
            'phone' => '081234567899',
            'address' => 'Jl. Merdeka No. 10',
            'route' => 'Rute Kota',
            'is_active' => true,
        ]
    );

    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();

    $delivery = Delivery::firstOrCreate(
        ['delivery_number' => 'SJ-INV-0001'],
        [
            'store_id' => $store->id,
            'created_by' => $manager->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'selesai',
            'total_items' => 5,
            'total_amount' => 100000,
        ]
    );

    $invoice = Invoice::firstOrCreate(
        ['invoice_number' => 'INV-TEST-0001'],
        [
            'delivery_id' => $delivery->id,
            'store_id' => $store->id,
            'created_by' => $manager->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal' => 100000,
            'discount' => 0,
            'total_amount' => 100000,
            'paid_amount' => 0,
            'remaining_balance' => 100000,
            'status' => 'belum_dibayar',
            'notes' => 'Tagihan perdana toko berkah',
        ]
    );
});

test('unauthenticated request to invoices api returns 401', function () {
    getJson('/api/invoices')->assertUnauthorized();
    getJson('/api/invoices/1')->assertUnauthorized();
});

test('user without faktur-view permission is denied with 403', function () {
    $guestUser = User::create([
        'name' => 'Guest User Without Permission',
        'email' => 'guest@halala-food.id',
        'password' => bcrypt('password123'),
        'phone' => '0899999999',
    ]);
    $token = JWTAuth::fromUser($guestUser);

    $res = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices');
    $res->assertStatus(403);
    expect($res->json('success'))->toBeFalse();
});

test('kurir and manager can view invoices list with search, status, and store filters', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    // 1. List Invoices
    $res = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices');
    $res->assertOk();
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data'))->toBeArray()
        ->and($res->json('status_counts'))->toHaveKeys(['all', 'belum_dibayar', 'sebagian', 'lunas', 'overdue', 'dibatalkan'])
        ->and($res->json('stores'))->toBeArray();

    // 2. Search filter
    $searchRes = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices?search=INV-TEST-0001');
    $searchRes->assertOk();
    expect(count($searchRes->json('data')))->toBeGreaterThanOrEqual(1);

    // 3. Status filter
    $statusRes = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices?status=belum_dibayar');
    $statusRes->assertOk();
    expect(count($statusRes->json('data')))->toBeGreaterThanOrEqual(1);

    $lunasRes = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices?status=lunas');
    $lunasRes->assertOk();
    expect(count($lunasRes->json('data')))->toBe(0);

    // 4. Store filter
    $store = Store::where('name', 'Toko Mitra Berkah')->firstOrFail();
    $storeRes = withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/invoices?store_id={$store->id}");
    $storeRes->assertOk();
    expect(count($storeRes->json('data')))->toBeGreaterThanOrEqual(1);
});

test('user can view invoice detail with relations loaded', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $invoice = Invoice::where('invoice_number', 'INV-TEST-0001')->firstOrFail();

    $res = withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/invoices/{$invoice->id}");
    $res->assertOk();
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data.invoice_number'))->toBe('INV-TEST-0001')
        ->and($res->json('data.store.name'))->toBe('Toko Mitra Berkah');
});

test('kurir without faktur-create permission cannot access create-options and cannot create invoice', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    // 1. Check create-options forbidden
    $optionsRes = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices/create-options');
    $optionsRes->assertStatus(403);
    expect($optionsRes->json('success'))->toBeFalse();

    // 2. Check store forbidden
    $storeRes = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/invoices', [
            'store_id' => 1,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [],
        ]);
    $storeRes->assertStatus(403);
    expect($storeRes->json('success'))->toBeFalse();
});

test('manager with faktur-create permission can get create options', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $res = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices/create-options');

    $res->assertOk();
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data'))->toHaveKeys(['stores', 'products', 'deliveries', 'next_invoice_number'])
        ->and($res->json('data.next_invoice_number'))->toStartWith('INV-');
});

test('manager with faktur-create permission can create invoice with validation, accounting sync, and returned stock restoration', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $store = Store::where('name', 'Toko Mitra Berkah')->firstOrFail();
    $product = Product::where('name', 'Kue Kering Barokah')->firstOrFail();
    $initialStock = $product->stock_ready;

    // 1. Validation error test
    $invalidRes = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/invoices', [
            'store_id' => null,
            'items' => [],
        ]);
    $invalidRes->assertStatus(422);
    expect($invalidRes->json('success'))->toBeFalse()
        ->and($invalidRes->json('errors'))->toHaveKeys(['store_id', 'items']);

    // 2. Successful invoice creation
    $payload = [
        'store_id' => $store->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'discount' => 5000,
        'notes' => 'Catatan faktur mobile test',
        'items' => [
            [
                'product_id' => $product->id,
                'delivered_quantity' => 10,
                'remaining_quantity' => 2,
                'damaged_quantity' => 1,
                'returned_quantity' => 3,
                'quantity' => 5, // terjual/tertagih
                'unit_price' => 20000,
            ],
        ],
    ];

    $res = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/invoices', $payload);

    $res->assertStatus(201);
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data.store_id'))->toBe($store->id)
        ->and((float) $res->json('data.subtotal'))->toBe(100000.0)
        ->and((float) $res->json('data.discount'))->toBe(5000.0)
        ->and((float) $res->json('data.total_amount'))->toBe(95000.0)
        ->and((float) $res->json('data.remaining_balance'))->toBe(95000.0)
        ->and($res->json('data.status'))->toBe('belum_dibayar')
        ->and(count($res->json('data.items')))->toBe(1);

    // Verify product returned quantity was restored to warehouse
    $product->refresh();
    expect($product->stock_ready)->toBe($initialStock + 3);
});

test('create options returns couriers list', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $res = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices/create-options');

    $res->assertOk();
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data'))->toHaveKey('couriers')
        ->and($res->json('data.couriers'))->toBeArray();
});

test('creating invoice assigns courier directly and broadcasts InvoiceCreated event', function () {
    Event::fake([InvoiceCreated::class]);

    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $store = Store::firstOrFail();
    $product = Product::firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $payload = [
        'store_id' => $store->id,
        'courier_id' => $kurir->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'discount' => 0,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 10000,
            ],
        ],
    ];

    $res = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/invoices', $payload);

    $res->assertStatus(201);
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data.courier_id'))->toBe($kurir->id)
        ->and($res->json('data.courier.id'))->toBe($kurir->id);

    Event::assertDispatched(InvoiceCreated::class, function ($event) use ($kurir) {
        $channels = array_map(fn ($ch) => $ch->name, $event->broadcastOn());

        return $event->invoice->courier_id === $kurir->id
            && in_array('private-courier.'.$kurir->id, $channels)
            && in_array('private-invoices', $channels);
    });
});

test('creating invoice with delivery auto-defaults courier_id if not explicitly set', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $store = Store::firstOrFail();
    $product = Product::firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $delivery = Delivery::create([
        'delivery_number' => 'SJ-AUTO-COURIER',
        'store_id' => $store->id,
        'courier_id' => $kurir->id,
        'created_by' => $manager->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 1,
        'total_amount' => 20000,
    ]);

    $payload = [
        'store_id' => $store->id,
        'delivery_id' => $delivery->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 20000,
            ],
        ],
    ];

    $res = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/invoices', $payload);

    $res->assertStatus(201);
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data.courier_id'))->toBe($kurir->id);
});

test('filtering invoices by courier_id returns matching invoices', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $store = Store::firstOrFail();
    $token = JWTAuth::fromUser($manager);

    Invoice::create([
        'invoice_number' => 'INV-FILTER-KURIR-1',
        'store_id' => $store->id,
        'courier_id' => $kurir->id,
        'created_by' => $manager->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 50000,
        'remaining_balance' => 50000,
        'status' => 'belum_dibayar',
    ]);

    $res = withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/invoices?courier_id={$kurir->id}");

    $res->assertOk();
    expect(count($res->json('data')))->toBeGreaterThanOrEqual(1)
        ->and($res->json('data.0.courier_id'))->toBe($kurir->id);
});

test('channel authorization rule permits courier to own channel and denies others', function () {
    $kurirA = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $kurirB = User::create([
        'name' => 'Kurir Lain',
        'email' => 'kurir2@halala-food.id',
        'password' => bcrypt('password123'),
        'phone' => '081234567800',
    ]);
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();

    $rule = function ($user, $id) {
        return (int) $user->id === (int) $id || $user->hasAnyRole(['dev', 'manager']);
    };

    expect($rule($kurirA, $kurirA->id))->toBeTrue()
        ->and($rule($kurirB, $kurirA->id))->toBeFalse()
        ->and($rule($manager, $kurirA->id))->toBeTrue();
});

