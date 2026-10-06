<?php

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

test('unauthenticated request to create product via api returns 401', function () {
    $response = postJson('/api/products', [
        'name' => 'Kripik Tempe Renyah',
    ]);

    $response->assertUnauthorized();
});

test('kurir user cannot create product because lacking produk-create permission', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    $unit = Unit::firstOrCreate(['name' => 'Karton', 'short_name' => 'Krt'], ['is_active' => true]);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/products', [
            'name' => 'Kripik Tempe Kurir',
            'unit_id' => $unit->id,
            'consignment_price' => 12000,
            'retail_price' => 15000,
            'stock_ready' => 10,
        ]);

    $response->assertForbidden();
    expect($response->json('message'))->toContain('Anda tidak memiliki hak akses');
});

test('validation errors return 422 when required fields are missing', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/products', []);

    $response->assertUnprocessable();
    expect($response->json('errors'))->toHaveKeys(['name', 'unit_id', 'consignment_price', 'retail_price', 'stock_ready']);
});

test('manager user can create new product via api', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $unit = Unit::firstOrCreate(['name' => 'Pouch', 'short_name' => 'Pch'], ['is_active' => true]);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/products', [
            'name' => 'Stik Balado Spesial Halala 200g',
            'unit_id' => $unit->id,
            'consignment_price' => 14000,
            'retail_price' => 17500,
            'stock_ready' => 25,
            'description' => 'Camilan stik renyah rasa balado pedas manis kemasan pouch',
            'is_active' => true,
        ]);

    $response->assertCreated();
    expect($response->json('success'))->toBeTrue()
        ->and($response->json('data.name'))->toBe('Stik Balado Spesial Halala 200g')
        ->and($response->json('data.unit_id'))->toBe($unit->id)
        ->and($response->json('data.consignment_price'))->toEqual(14000)
        ->and($response->json('data.retail_price'))->toEqual(17500)
        ->and($response->json('data.stock_ready'))->toBe(25)
        ->and($response->json('data.is_active'))->toBeTrue();

    $this->assertDatabaseHas('products', [
        'name' => 'Stik Balado Spesial Halala 200g',
        'unit_id' => $unit->id,
        'stock_ready' => 25,
    ]);
});
