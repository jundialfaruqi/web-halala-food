<?php

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $unit = Unit::firstOrCreate(['name' => 'Pouch', 'short_name' => 'Pch'], ['is_active' => true]);

    Product::firstOrCreate(
        ['name' => 'Kripik Tempe Original 150g'],
        [
            'unit_id' => $unit->id,
            'unit' => 'Pch',
            'consignment_price' => 10000,
            'retail_price' => 13000,
            'stock_ready' => 20,
            'description' => 'Kripik tempe gurih renyah',
            'is_active' => true,
        ]
    );
});

test('unauthenticated request to update product via api returns 401', function () {
    $product = Product::where('name', 'Kripik Tempe Original 150g')->firstOrFail();

    $response = putJson("/api/products/{$product->id}", [
        'name' => 'Produk Baru',
    ]);

    $response->assertUnauthorized();
});

test('kurir user cannot update product because lacking produk-edit permission', function () {
    $product = Product::where('name', 'Kripik Tempe Original 150g')->firstOrFail();
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/products/{$product->id}", [
            'name' => 'Nama Baru Kurir',
            'unit_id' => $product->unit_id,
            'consignment_price' => 12000,
            'retail_price' => 15000,
            'stock_ready' => 10,
        ]);

    $response->assertForbidden();
    expect($response->json('message'))->toContain('Anda tidak memiliki hak akses');
});

test('validation errors return 422 when required fields are invalid on update', function () {
    $product = Product::where('name', 'Kripik Tempe Original 150g')->firstOrFail();
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/products/{$product->id}", [
            'name' => '',
            'consignment_price' => -500,
            'retail_price' => -1000,
        ]);

    $response->assertUnprocessable();
    expect($response->json('errors'))->toHaveKeys(['name', 'unit_id', 'consignment_price', 'retail_price', 'stock_ready']);
});

test('manager user can update product via api with produk-edit permission', function () {
    $product = Product::where('name', 'Kripik Tempe Original 150g')->firstOrFail();
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $unit = Unit::firstOrCreate(['name' => 'Toples 500g', 'short_name' => 'Tpl'], ['is_active' => true]);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/products/{$product->id}", [
            'name' => 'Nama Produk Terupdate Halala',
            'unit_id' => $unit->id,
            'consignment_price' => 18000,
            'retail_price' => 22000,
            'stock_ready' => 45,
            'description' => 'Deskripsi produk berhasil diperbarui via API',
            'is_active' => false,
        ]);

    $response->assertOk();
    expect($response->json('success'))->toBeTrue()
        ->and($response->json('data.name'))->toBe('Nama Produk Terupdate Halala')
        ->and($response->json('data.unit_id'))->toBe($unit->id)
        ->and($response->json('data.consignment_price'))->toEqual(18000)
        ->and($response->json('data.retail_price'))->toEqual(22000)
        ->and($response->json('data.stock_ready'))->toBe(45)
        ->and($response->json('data.is_active'))->toBeFalse()
        ->and($response->json('data.description'))->toBe('Deskripsi produk berhasil diperbarui via API');

    assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Nama Produk Terupdate Halala',
        'unit_id' => $unit->id,
        'stock_ready' => 45,
        'is_active' => false,
    ]);
});
