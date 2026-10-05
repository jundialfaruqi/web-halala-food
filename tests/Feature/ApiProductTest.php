<?php

use App\Models\Product;
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
});

test('unauthenticated request to get products via api returns 401', function () {
    $response = getJson('/api/products');
    $response->assertUnauthorized();
});

test('authenticated user with produk-view can get products list and filter', function () {
    $dev = User::where('email', 'developer@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($dev);

    $unit = Unit::firstOrCreate(['name' => 'Karton', 'short_name' => 'Krt'], ['is_active' => true]);
    Product::create([
        'name' => 'Kripik Tempe Rasa Barbeque',
        'unit_id' => $unit->id,
        'unit' => 'Krt',
        'consignment_price' => 15000,
        'retail_price' => 18000,
        'stock_ready' => 50,
        'description' => 'Kripik tempe renyah bumbu BBQ',
        'is_active' => true,
    ]);

    Product::create([
        'name' => 'Kripik Singkong Pedas',
        'unit_id' => $unit->id,
        'unit' => 'Krt',
        'consignment_price' => 12000,
        'retail_price' => 15000,
        'stock_ready' => 0,
        'description' => 'Kripik singkong gurih pedas',
        'is_active' => false,
    ]);

    // 1. Get all products
    $response = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/products');

    $response->assertOk();
    expect($response->json('success'))->toBeTrue();
    expect($response->json('data'))->toBeArray();

    // 2. Search filter
    $searchResponse = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/products?search=Barbeque');
    $searchResponse->assertOk();
    $names = collect($searchResponse->json('data'))->pluck('name');
    expect($names)->toContain('Kripik Tempe Rasa Barbeque');
    expect($names)->not->toContain('Kripik Singkong Pedas');

    // 3. Status filter active
    $activeResponse = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/products?status=active');
    $activeResponse->assertOk();
    $activeNames = collect($activeResponse->json('data'))->pluck('name');
    expect($activeNames)->toContain('Kripik Tempe Rasa Barbeque');
    expect($activeNames)->not->toContain('Kripik Singkong Pedas');

    // 4. Units endpoint
    $unitsResponse = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/products/units');
    $unitsResponse->assertOk();
    expect($unitsResponse->json('success'))->toBeTrue();
    expect($unitsResponse->json('data'))->toBeArray();
});
