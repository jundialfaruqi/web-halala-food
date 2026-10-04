<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Store::firstOrCreate(
        ['name' => 'Toko Dihapus Testing'],
        [
            'owner_name' => 'Pak Joko',
            'phone' => '6281234567890',
            'address' => 'Jl. Soekarno Hatta No. 45, Malang',
            'latitude' => -7.9523,
            'longitude' => 112.6145,
            'route' => 'Rute Suhat',
            'notes' => 'Langganan kue',
            'is_active' => true,
        ]
    );
});

test('unauthenticated request to delete store via api returns 401', function () {
    $store = Store::where('name', 'Toko Dihapus Testing')->firstOrFail();

    $response = deleteJson("/api/stores/{$store->id}");

    $response->assertUnauthorized();
});

test('kurir user cannot delete store because lacking toko-delete permission', function () {
    $store = Store::where('name', 'Toko Dihapus Testing')->firstOrFail();
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/stores/{$store->id}");

    $response->assertForbidden();
    expect($response->json('message'))->toContain('Anda tidak memiliki hak akses');
});

test('manager user can delete store via api', function () {
    $store = Store::where('name', 'Toko Dihapus Testing')->firstOrFail();
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/stores/{$store->id}");

    $response->assertOk();
    expect($response->json('success'))->toBeTrue();

    $this->assertDatabaseMissing('stores', [
        'id' => $store->id,
    ]);
});
