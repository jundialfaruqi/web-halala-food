<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withHeader;

beforeEach(function () {
    seed(DatabaseSeeder::class);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    Store::firstOrCreate(
        ['name' => 'Toko Sumber Rejeki Testing'],
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

test('unauthenticated request to edit store via api returns 401', function () {
    $store = Store::where('name', 'Toko Sumber Rejeki Testing')->firstOrFail();

    $response = putJson("/api/stores/{$store->id}", [
        'name' => 'Toko Ganti Nama',
    ]);

    $response->assertUnauthorized();
});

test('kurir user cannot update store because lacking toko-edit permission', function () {
    $store = Store::where('name', 'Toko Sumber Rejeki Testing')->firstOrFail();
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/stores/{$store->id}", [
            'name' => 'Toko Diedit Kurir',
        ]);

    $response->assertForbidden();
    expect($response->json('message'))->toContain('Anda tidak memiliki hak akses');
});

test('manager user can update store details via api', function () {
    $store = Store::where('name', 'Toko Sumber Rejeki Testing')->firstOrFail();
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/stores/{$store->id}", [
            'name' => 'Toko Sumber Rejeki Berkah',
            'owner_name' => 'Pak Joko S.',
            'phone' => '085712345678', // harus dinormalisasi ke 6285712345678
            'address' => 'Jl. Suhat Indah No. 99',
            'latitude' => -7.954321,
            'longitude' => 112.619876,
            'route' => 'Rute Suhat Timur',
            'notes' => 'Catatan diperbarui',
            'is_active' => false,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $store->refresh();
    expect($store->name)->toBe('Toko Sumber Rejeki Berkah')
        ->and($store->owner_name)->toBe('Pak Joko S.')
        ->and($store->phone)->toBe('6285712345678')
        ->and($store->address)->toBe('Jl. Suhat Indah No. 99')
        ->and($store->latitude)->toBe(-7.954321)
        ->and($store->longitude)->toBe(112.619876)
        ->and($store->route)->toBe('Rute Suhat Timur')
        ->and($store->is_active)->toBeFalse();
});

test('validation fails when name is empty', function () {
    $store = Store::where('name', 'Toko Sumber Rejeki Testing')->firstOrFail();
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $response = withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/stores/{$store->id}", [
            'name' => '',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('manager user can upload store photo via api matching web naming and storage path', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $store = Store::where('name', 'Toko Sumber Rejeki Testing')->firstOrFail();
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    // 1x1 transparent png data url
    $sampleBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    $response = withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/stores/{$store->id}", [
            'name' => 'Toko Sumber Rejeki Berfoto',
            'photo_data' => $sampleBase64,
        ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $store->refresh();
    expect($store->photo)->not->toBeNull()
        ->and($store->photo)->toStartWith('foto-toko/')
        ->and($store->photo)->toEndWith('.png');

    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($store->photo);
});
