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
