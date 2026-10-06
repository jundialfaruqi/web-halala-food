<?php

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $unit = Unit::firstOrCreate(['name' => 'Pouch', 'short_name' => 'Pch'], ['is_active' => true]);

    Product::firstOrCreate(
        ['name' => 'Kripik Tempe Hapus Testing'],
        [
            'unit_id' => $unit->id,
            'unit' => 'Pch',
            'consignment_price' => 10000,
            'retail_price' => 13000,
            'stock_ready' => 20,
            'description' => 'Produk untuk testing hapus',
            'is_active' => true,
        ]
    );
});

test('unauthenticated request to delete product via api returns 401', function () {
    $product = Product::where('name', 'Kripik Tempe Hapus Testing')->firstOrFail();

    $response = deleteJson("/api/products/{$product->id}");

    $response->assertUnauthorized();
});

test('kurir user cannot delete product because lacking produk-delete permission', function () {
    $product = Product::where('name', 'Kripik Tempe Hapus Testing')->firstOrFail();
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/products/{$product->id}");

    $response->assertForbidden();
    expect($response->json('message'))->toContain('Anda tidak memiliki hak akses');
});

test('product cannot be deleted if associated with delivery items or recipes returning 422', function () {
    $product = Product::where('name', 'Kripik Tempe Hapus Testing')->firstOrFail();
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $store = Store::firstOrCreate(
        ['name' => 'Toko Mitra Relasi Test'],
        ['is_active' => true]
    );

    $delivery = Delivery::create([
        'delivery_number' => 'SJ-TEST-DEL-01',
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'delivery_date' => now()->toDateString(),
        'status' => 'draft',
    ]);

    DeliveryItem::create([
        'delivery_id' => $delivery->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => 10000,
        'subtotal' => 100000,
    ]);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('tidak dapat dihapus karena sudah memiliki data');
});

test('manager user can delete standalone product via api', function () {
    $product = Product::where('name', 'Kripik Tempe Hapus Testing')->firstOrFail();
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/products/{$product->id}");

    $response->assertOk();
    expect($response->json('success'))->toBeTrue()
        ->and($response->json('message'))->toContain('berhasil dihapus');

    assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});
