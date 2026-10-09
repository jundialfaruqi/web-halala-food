<?php

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

test('unauthenticated request to create store via api returns 401', function () {
    $response = postJson('/api/stores', [
        'name' => 'Toko Mitra Baru',
    ]);

    $response->assertUnauthorized();
});

test('kurir user cannot create store because lacking toko-create permission', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/stores', [
            'name' => 'Toko Baru Kurir',
        ]);

    $response->assertForbidden();
    expect($response->json('message'))->toContain('Anda tidak memiliki hak akses');
});

test('manager user can create new store via api', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/stores', [
            'name' => 'Toko Berkah Abadi API',
            'owner_name' => 'Haji Mansur',
            'phone' => '081299887766',
            'address' => 'Jl. Veteran No. 10, Malang',
            'latitude' => -7.9567,
            'longitude' => 112.6123,
            'route' => 'Rute Dinoyo',
            'notes' => 'Toko grosir snack',
            'is_active' => true,
        ]);

    $response->assertCreated();
    expect($response->json('success'))->toBeTrue()
        ->and($response->json('data.name'))->toBe('Toko Berkah Abadi API')
        ->and($response->json('data.phone'))->toBe('6281299887766')
        ->and($response->json('data.route'))->toBe('Rute Dinoyo');

    $this->assertDatabaseHas('stores', [
        'name' => 'Toko Berkah Abadi API',
        'owner_name' => 'Haji Mansur',
        'phone' => '6281299887766',
    ]);
});
