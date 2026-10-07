<?php

use App\Models\Delivery;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

test('unauthenticated request to deliveries api returns 401', function () {
    getJson('/api/deliveries')->assertUnauthorized();
    getJson('/api/deliveries/options')->assertUnauthorized();
    postJson('/api/deliveries', [])->assertUnauthorized();
});

test('kurir can view deliveries, get options, dispatch, and complete, but cannot create or delete', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $kurirToken = JWTAuth::fromUser($kurir);

    // 1. Kurir can get options
    $optionsRes = withHeader('Authorization', "Bearer {$kurirToken}")
        ->getJson('/api/deliveries/options');
    $optionsRes->assertOk();
    expect($optionsRes->json('success'))->toBeTrue();

    // 2. Kurir can get list
    $listRes = withHeader('Authorization', "Bearer {$kurirToken}")
        ->getJson('/api/deliveries');
    $listRes->assertOk();
    expect($listRes->json('success'))->toBeTrue();

    // 3. Kurir CANNOT create delivery (denied: pengantaran-create)
    $store = Store::firstOrCreate(
        ['name' => 'Toko Kurir Test'],
        ['owner_name' => 'Pak Test', 'phone' => '0812345678', 'address' => 'Jl. Test', 'route' => 'Rute Test', 'is_active' => true]
    );

    $createRes = withHeader('Authorization', "Bearer {$kurirToken}")
        ->postJson('/api/deliveries', [
            'store_id' => $store->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                ['product_id' => 1, 'quantity' => 5],
            ],
        ]);
    $createRes->assertStatus(403);

    // Create a delivery first to test deletion authorization
    $dev = User::where('email', 'developer@halala-food.id')->firstOrFail();
    $existingDelivery = Delivery::create([
        'delivery_number' => Delivery::generateDeliveryNumber(),
        'store_id' => $store->id,
        'courier_id' => $kurir->id,
        'created_by' => $dev->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 1,
        'total_amount' => 10000,
    ]);

    // 4. Kurir CANNOT delete delivery (denied: pengantaran-delete)
    $deleteRes = withHeader('Authorization', "Bearer {$kurirToken}")
        ->deleteJson("/api/deliveries/{$existingDelivery->id}");
    $deleteRes->assertStatus(403);
});

test('dev/manager can create delivery, stock decrements, then dispatch and complete generates invoice', function () {
    $dev = User::where('email', 'developer@halala-food.id')->firstOrFail();
    $devToken = JWTAuth::fromUser($dev);

    $unit = Unit::firstOrCreate(['name' => 'Karton', 'short_name' => 'Krt'], ['is_active' => true]);
    $product = Product::create([
        'name' => 'Kue Bawang Super Renyah',
        'unit_id' => $unit->id,
        'unit' => 'Krt',
        'consignment_price' => 20000,
        'retail_price' => 25000,
        'stock_ready' => 50,
        'description' => 'Test produk',
        'is_active' => true,
    ]);

    $store = Store::firstOrCreate(
        ['name' => 'Toko Berkah Abadi'],
        [
            'owner_name' => 'Haji Mansur',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10',
            'route' => 'Rute Barat',
            'is_active' => true,
        ]
    );

    // 1. Create delivery
    $createRes = withHeader('Authorization', "Bearer {$devToken}")
        ->postJson('/api/deliveries', [
            'store_id' => $store->id,
            'delivery_date' => now()->toDateString(),
            'notes' => 'Catatan tes delivery',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 20000],
            ],
        ]);

    $createRes->assertCreated();
    expect($createRes->json('success'))->toBeTrue();
    $deliveryId = $createRes->json('data.id');

    // Verify stock decremented: 50 - 10 = 40
    expect($product->fresh()->stock_ready)->toBe(40);

    // 2. Dispatch delivery
    $dispatchRes = withHeader('Authorization', "Bearer {$devToken}")
        ->postJson("/api/deliveries/{$deliveryId}/dispatch");
    $dispatchRes->assertOk();
    expect($dispatchRes->json('data.status'))->toBe('dikirim');

    // 3. Complete delivery
    $completeRes = withHeader('Authorization', "Bearer {$devToken}")
        ->postJson("/api/deliveries/{$deliveryId}/complete", [
            'recipient_name' => 'Bapak Mansur',
            'recipient_role' => 'Pemilik Toko',
            'recipient_phone' => '081234567890',
            'handover_notes' => 'Semua kardus aman',
        ]);
    $completeRes->assertOk();
    expect($completeRes->json('data.status'))->toBe('selesai');
    expect($completeRes->json('data.invoice'))->not->toBeNull();

    // 4. Cannot delete completed delivery
    $deleteRes = withHeader('Authorization', "Bearer {$devToken}")
        ->deleteJson("/api/deliveries/{$deliveryId}");
    $deleteRes->assertStatus(422);
});

test('cancelling delivery restores stock ready', function () {
    $dev = User::where('email', 'developer@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($dev);

    $unit = Unit::firstOrCreate(['name' => 'Pcs', 'short_name' => 'Pcs'], ['is_active' => true]);
    $product = Product::create([
        'name' => 'Kerupuk Ikan Tenggiri',
        'unit_id' => $unit->id,
        'unit' => 'Pcs',
        'consignment_price' => 15000,
        'retail_price' => 20000,
        'stock_ready' => 25,
        'is_active' => true,
    ]);

    $store = Store::firstOrCreate(
        ['name' => 'Toko Batalkan Test'],
        ['owner_name' => 'Bu Test', 'phone' => '0898765432', 'address' => 'Jl. Mawar', 'route' => 'Rute Timur', 'is_active' => true]
    );

    $createRes = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/deliveries', [
            'store_id' => $store->id,
            'delivery_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 15],
            ],
        ]);
    $createRes->assertCreated();
    $deliveryId = $createRes->json('data.id');

    expect($product->fresh()->stock_ready)->toBe(10);

    // Cancel delivery
    $cancelRes = withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/deliveries/{$deliveryId}/cancel");
    $cancelRes->assertOk();

    // Stock should be restored to 25
    expect($product->fresh()->stock_ready)->toBe(25);
    expect($cancelRes->json('data.status'))->toBe('dibatalkan');
});
