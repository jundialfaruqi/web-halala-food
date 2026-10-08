<?php

use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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
