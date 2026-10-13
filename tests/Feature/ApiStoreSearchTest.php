<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

test('authenticated user with toko-view can search stores by name, owner, address, and route', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    // Buat store khusus untuk testing pencarian
    Store::create([
        'name' => 'Toko Berkah Sejahtera',
        'owner_name' => 'Pak Budi Hartono',
        'address' => 'Jl. Mawar No. 12, Surabaya',
        'route' => 'Rute-Utara',
        'is_active' => true,
    ]);

    // Cari by owner name
    $resOwner = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/stores?search=Hartono&per_page=5');
    $resOwner->assertOk()
        ->assertJsonPath('success', true);
    expect(collect($resOwner->json('data'))->pluck('name'))->toContain('Toko Berkah Sejahtera');

    // Cari by address
    $resAddress = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/stores?search=Surabaya&per_page=5');
    $resAddress->assertOk();
    expect(collect($resAddress->json('data'))->pluck('name'))->toContain('Toko Berkah Sejahtera');

    // Cari by route
    $resRoute = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/stores?search=Rute-Utara&per_page=5');
    $resRoute->assertOk();
    expect(collect($resRoute->json('data'))->pluck('name'))->toContain('Toko Berkah Sejahtera');

    // Test per_page 5
    $resLimit5 = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/stores?per_page=5');
    $resLimit5->assertOk();
    expect(count($resLimit5->json('data')))->toBeLessThanOrEqual(5);

    // Test per_page 10
    $resLimit10 = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/stores?per_page=10');
    $resLimit10->assertOk();
    expect(count($resLimit10->json('data')))->toBeLessThanOrEqual(10);
});

test('authenticated user can get stores sorted by highest physical product sales from paid invoices', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $unit = \App\Models\Unit::firstOrCreate(['name' => 'Bungkus', 'short_name' => 'Bks'], ['is_active' => true]);
    $product = \App\Models\Product::create([
        'name' => 'Kue Bawang Super Renyah',
        'unit_id' => $unit->id,
        'unit' => 'Bks',
        'consignment_price' => 6000,
        'retail_price' => 7000,
        'stock_ready' => 500,
        'is_active' => true,
    ]);

    $storeA = Store::create([
        'name' => 'Toko Kuantiti Fisik Terbanyak',
        'is_active' => true,
    ]);
    $storeB = Store::create([
        'name' => 'Toko Nominal Tinggi Kuantiti Sedang',
        'is_active' => true,
    ]);
    $storeC = Store::create([
        'name' => 'Toko Faktur Belum Dibayar',
        'is_active' => true,
    ]);

    // Store A: 250 pcs terjual dari faktur LUNAS
    $invA = \App\Models\Invoice::create([
        'invoice_number' => 'INV-TEST-TOP-A',
        'store_id' => $storeA->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 1500000,
        'status' => 'lunas',
    ]);
    \App\Models\InvoiceItem::create([
        'invoice_id' => $invA->id,
        'product_id' => $product->id,
        'delivered_quantity' => 250,
        'remaining_quantity' => 0,
        'damaged_quantity' => 0,
        'returned_quantity' => 0,
        'quantity' => 250,
        'unit_price' => 6000,
        'subtotal' => 1500000,
    ]);

    // Store B: 50 pcs terjual dari faktur LUNAS (nominal lebih besar tapi kuantiti fisik lebih kecil)
    $invB = \App\Models\Invoice::create([
        'invoice_number' => 'INV-TEST-TOP-B',
        'store_id' => $storeB->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 5000000,
        'status' => 'lunas',
    ]);
    \App\Models\InvoiceItem::create([
        'invoice_id' => $invB->id,
        'product_id' => $product->id,
        'delivered_quantity' => 50,
        'remaining_quantity' => 0,
        'damaged_quantity' => 0,
        'returned_quantity' => 0,
        'quantity' => 50,
        'unit_price' => 100000,
        'subtotal' => 5000000,
    ]);

    // Store C: 1000 pcs tetapi faktur BELUM DIBAYAR (tidak boleh dihitung)
    $invC = \App\Models\Invoice::create([
        'invoice_number' => 'INV-TEST-TOP-C',
        'store_id' => $storeC->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'total_amount' => 10000000,
        'status' => 'belum_dibayar',
    ]);
    \App\Models\InvoiceItem::create([
        'invoice_id' => $invC->id,
        'product_id' => $product->id,
        'delivered_quantity' => 1000,
        'remaining_quantity' => 0,
        'damaged_quantity' => 0,
        'returned_quantity' => 0,
        'quantity' => 1000,
        'unit_price' => 10000,
        'subtotal' => 10000000,
    ]);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/stores?sort_by=sales&per_page=10');

    $response->assertOk();
    $items = collect($response->json('data'));

    // Toko A harus berada di urutan teratas karena total produk fisik terjual dari faktur lunas adalah 250 pcs
    expect($items->first()['name'])->toBe('Toko Kuantiti Fisik Terbanyak');
    expect($items->first()['sold_items_count'])->toBe(250);

    // Toko B berada di urutan kedua dengan 50 pcs
    $itemB = $items->firstWhere('name', 'Toko Nominal Tinggi Kuantiti Sedang');
    expect($itemB['sold_items_count'])->toBe(50);

    // Toko C tidak boleh menghitung 1000 pcs karena fakturnya belum lunas (sold_items_count = 0 / null)
    $itemC = $items->firstWhere('name', 'Toko Faktur Belum Dibayar');
    expect((int) ($itemC['sold_items_count'] ?? 0))->toBe(0);
});
