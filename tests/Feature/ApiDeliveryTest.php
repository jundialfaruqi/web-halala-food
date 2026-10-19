<?php

use App\Jobs\SendCourierPushNotificationJob;
use App\Models\Delivery;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Queue;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

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

    // 5. Kurir CANNOT edit/update delivery data (denied: pengantaran-edit)
    $updateRes = withHeader('Authorization', "Bearer {$kurirToken}")
        ->putJson("/api/deliveries/{$existingDelivery->id}", [
            'delivery_number' => $existingDelivery->delivery_number,
            'store_id' => $store->id,
            'delivery_date' => now()->toDateString(),
            'items' => [['product_id' => 1, 'quantity' => 2]],
        ]);
    $updateRes->assertStatus(403);

    // 6. Kurir CANNOT cancel delivery (denied: pengantaran-delete / pengantaran-edit)
    $cancelRes = withHeader('Authorization', "Bearer {$kurirToken}")
        ->postJson("/api/deliveries/{$existingDelivery->id}/cancel");
    $cancelRes->assertStatus(403);

    // 7. Kurir CAN dispatch their own delivery (allowed: pengantaran-status)
    $dispatchRes = withHeader('Authorization', "Bearer {$kurirToken}")
        ->postJson("/api/deliveries/{$existingDelivery->id}/dispatch");
    $dispatchRes->assertOk();

    // 8. Kurir CAN complete their own delivery (allowed: pengantaran-status)
    $completeRes = withHeader('Authorization', "Bearer {$kurirToken}")
        ->postJson("/api/deliveries/{$existingDelivery->id}/complete", [
            'recipient_name' => 'Bapak Budi Toko',
        ]);
    $completeRes->assertOk();
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

test('courier can only see and access their own deliveries in api, cannot see or access deliveries of other couriers', function () {
    $kurir1 = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $kurir2 = User::updateOrCreate(
        ['email' => 'kurir2@halala-food.id'],
        [
            'name' => 'Kurir Kedua',
            'phone' => '089912345678',
            'password' => bcrypt('admin123'),
        ]
    );
    $kurir2->syncRoles(['kurir']);

    $dev = User::where('email', 'developer@halala-food.id')->firstOrFail();
    $store = Store::firstOrCreate(
        ['name' => 'Toko Kurir Isolasi'],
        ['owner_name' => 'Pak Budi', 'phone' => '0812345678', 'address' => 'Jl. Test', 'is_active' => true]
    );

    $delivery1 = Delivery::create([
        'delivery_number' => 'SJ-ISO-0001',
        'store_id' => $store->id,
        'courier_id' => $kurir1->id,
        'created_by' => $dev->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 1,
        'total_amount' => 10000,
    ]);

    $delivery2 = Delivery::create([
        'delivery_number' => 'SJ-ISO-0002',
        'store_id' => $store->id,
        'courier_id' => $kurir2->id,
        'created_by' => $dev->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 1,
        'total_amount' => 10000,
    ]);

    $tokenKurir1 = JWTAuth::fromUser($kurir1);

    // 1. In list API: kurir1 only sees delivery1, never delivery2
    $listRes = withHeader('Authorization', "Bearer {$tokenKurir1}")
        ->getJson('/api/deliveries?per_page=100');
    $listRes->assertOk();
    $ids = collect($listRes->json('data'))->pluck('id')->all();
    expect($ids)->toContain($delivery1->id)
        ->and($ids)->not->toContain($delivery2->id);

    // 2. In show API: kurir1 can view delivery1, but receives 403 when trying to view delivery2
    withHeader('Authorization', "Bearer {$tokenKurir1}")
        ->getJson("/api/deliveries/{$delivery1->id}")
        ->assertOk();

    withHeader('Authorization', "Bearer {$tokenKurir1}")
        ->getJson("/api/deliveries/{$delivery2->id}")
        ->assertStatus(403);

    // 3. In dispatch API: kurir1 cannot dispatch delivery2
    withHeader('Authorization', "Bearer {$tokenKurir1}")
        ->postJson("/api/deliveries/{$delivery2->id}/dispatch")
        ->assertStatus(403);

    // 4. In complete API: kurir1 cannot complete delivery2
    withHeader('Authorization', "Bearer {$tokenKurir1}")
        ->postJson("/api/deliveries/{$delivery2->id}/complete", [
            'recipient_name' => 'Penerima Toko',
        ])
        ->assertStatus(403);

    // 5. In cancel API: kurir1 cannot cancel delivery2
    withHeader('Authorization', "Bearer {$tokenKurir1}")
        ->postJson("/api/deliveries/{$delivery2->id}/cancel")
        ->assertStatus(403);

    // 6. In update API: kurir1 cannot update delivery2
    withHeader('Authorization', "Bearer {$tokenKurir1}")
        ->putJson("/api/deliveries/{$delivery2->id}", [
            'delivery_number' => 'SJ-ISO-0002-MOD',
            'store_id' => $store->id,
            'delivery_date' => now()->toDateString(),
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ])
        ->assertStatus(403);

    // 7. Dev CAN see both deliveries in list API and view delivery2
    auth('api')->setUser($dev);
    $devToken = JWTAuth::fromUser($dev);
    $devListRes = withHeader('Authorization', "Bearer {$devToken}")
        ->getJson('/api/deliveries?per_page=100');
    $devIds = collect($devListRes->json('data'))->pluck('id')->all();
    expect($devIds)->toContain($delivery1->id)
        ->and($devIds)->toContain($delivery2->id);

    withHeader('Authorization', "Bearer {$devToken}")
        ->getJson("/api/deliveries/{$delivery2->id}")
        ->assertOk();
});

test('status_counts all remains total count when filtering by specific status', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $dev = User::where('email', 'developer@halala-food.id')->firstOrFail();
    $store = Store::firstOrCreate(
        ['name' => 'Toko Kurir Test Status'],
        ['owner_name' => 'Pak Test', 'phone' => '0812345678', 'address' => 'Jl. Test', 'route' => 'Rute Test', 'is_active' => true]
    );

    // Create 3 deliveries: 2 diproses, 1 dikirim
    Delivery::create([
        'delivery_number' => 'SJ-TEST-COUNT-01',
        'store_id' => $store->id,
        'courier_id' => $kurir->id,
        'created_by' => $dev->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 1,
        'total_amount' => 10000,
    ]);
    Delivery::create([
        'delivery_number' => 'SJ-TEST-COUNT-02',
        'store_id' => $store->id,
        'courier_id' => $kurir->id,
        'created_by' => $dev->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'diproses',
        'total_items' => 1,
        'total_amount' => 10000,
    ]);
    Delivery::create([
        'delivery_number' => 'SJ-TEST-COUNT-03',
        'store_id' => $store->id,
        'courier_id' => $kurir->id,
        'created_by' => $dev->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'dikirim',
        'total_items' => 1,
        'total_amount' => 10000,
    ]);

    $kurirToken = JWTAuth::fromUser($kurir);

    // When status=all
    $resAll = withHeader('Authorization', "Bearer {$kurirToken}")
        ->getJson('/api/deliveries?status=all');
    $resAll->assertOk();
    $totalAll = $resAll->json('status_counts.all');
    $totalDiproses = $resAll->json('status_counts.diproses');
    expect($totalDiproses)->toBeGreaterThanOrEqual(2)
        ->and($totalAll)->toBeGreaterThan($totalDiproses);

    // When status=diproses (filtered)
    $resDiproses = withHeader('Authorization', "Bearer {$kurirToken}")
        ->getJson('/api/deliveries?status=diproses');
    $resDiproses->assertOk();

    // 'all' count MUST remain equal to totalAll, NOT shrink to diproses count!
    expect($resDiproses->json('status_counts.all'))->toBe($totalAll)
        ->and($resDiproses->json('status_counts.diproses'))->toBe($totalDiproses)
        ->and(count($resDiproses->json('data')))->toBe($totalDiproses);
});

test('creating deliveries for multiple stores dispatches a single consolidated queued push notification', function () {
    Queue::fake();

    $dev = User::where('email', 'developer@halala-food.id')->firstOrFail();
    $devToken = JWTAuth::fromUser($dev);

    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();

    $unit = Unit::firstOrCreate(['name' => 'Pcs', 'short_name' => 'Pcs'], ['is_active' => true]);
    $product = Product::create([
        'name' => 'Produk Batch Notification Test',
        'unit_id' => $unit->id,
        'unit' => 'Pcs',
        'consignment_price' => 10000,
        'retail_price' => 12000,
        'stock_ready' => 100,
        'is_active' => true,
    ]);

    $store1 = Store::create([
        'name' => 'Toko Batch A',
        'address' => 'Jl. A',
        'is_active' => true,
    ]);
    $store2 = Store::create([
        'name' => 'Toko Batch B',
        'address' => 'Jl. B',
        'is_active' => true,
    ]);
    $store3 = Store::create([
        'name' => 'Toko Batch C',
        'address' => 'Jl. C',
        'is_active' => true,
    ]);

    $response = withHeader('Authorization', "Bearer {$devToken}")
        ->postJson('/api/deliveries', [
            'store_ids' => [$store1->id, $store2->id, $store3->id],
            'courier_id' => $kurir->id,
            'delivery_date' => now()->toDateString(),
            'notes' => 'Batch delivery test',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10000],
            ],
        ]);

    $response->assertCreated();
    expect($response->json('success'))->toBeTrue();
    expect(count($response->json('deliveries')))->toBe(3);

    // Verify exactly 1 consolidated push notification job was dispatched to Queue
    Queue::assertPushed(SendCourierPushNotificationJob::class, 1);
    Queue::assertPushed(SendCourierPushNotificationJob::class, function ($job) use ($kurir) {
        return $job->courierId === $kurir->id &&
               $job->title === 'Tugas Pengantaran Baru!' &&
               str_contains($job->body, '3 surat jalan pengantaran baru') &&
               $job->data['type'] === 'delivery_batch' &&
               $job->data['count'] === '3';
    });
});

test('manager or dev can create deliveries with dynamic per-store product allocations and valid stock', function () {
    $dev = User::where('email', 'developer@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($dev);

    $unit = Unit::firstOrCreate(['name' => 'Pcs', 'short_name' => 'Pcs'], ['is_active' => true]);
    $product = Product::create([
        'name' => 'Produk Dinamis Delivery Test',
        'unit_id' => $unit->id,
        'unit' => 'Pcs',
        'consignment_price' => 10000,
        'retail_price' => 12000,
        'stock_ready' => 25,
        'is_active' => true,
    ]);

    $storeA = Store::create(['name' => 'Toko Delivery A', 'address' => 'Alamat A', 'is_active' => true]);
    $storeB = Store::create(['name' => 'Toko Delivery B', 'address' => 'Alamat B', 'is_active' => true]);

    // 1. Sukses jika total alokasi (10 + 12 = 22) <= stock_ready (25)
    $response = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/deliveries', [
            'delivery_date' => now()->toDateString(),
            'notes' => 'Dynamic per-store delivery test',
            'stores' => [
                [
                    'store_id' => $storeA->id,
                    'items' => [
                        ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 10000],
                    ],
                ],
                [
                    'store_id' => $storeB->id,
                    'items' => [
                        ['product_id' => $product->id, 'quantity' => 12, 'unit_price' => 10000],
                    ],
                ],
            ],
        ]);

    $response->assertCreated();
    expect($response->json('success'))->toBeTrue();
    $deliveries = $response->json('deliveries');
    expect(count($deliveries))->toBe(2);
    expect((int) $deliveries[0]['total_items'])->toBe(10);
    expect((int) $deliveries[1]['total_items'])->toBe(12);

    // Verifikasi stok gudang berkurang tepat 22 (25 - 22 = 3)
    $product->refresh();
    expect($product->stock_ready)->toBe(3);

    // 2. Gagal 422 jika total alokasi melebihi sisa stock_ready (3)
    $responseFail = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/deliveries', [
            'delivery_date' => now()->toDateString(),
            'stores' => [
                [
                    'store_id' => $storeA->id,
                    'items' => [
                        ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000],
                    ],
                ],
                [
                    'store_id' => $storeB->id,
                    'items' => [
                        ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10000],
                    ],
                ],
            ],
        ]);

    $responseFail->assertStatus(422);
    expect($responseFail->json('message'))->toContain('tidak mencukupi');
});
