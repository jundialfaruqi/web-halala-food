<?php

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

test('unauthenticated request to dashboard stat api returns 401', function () {
    getJson('/api/dashboard/stat')->assertUnauthorized();
});

test('manager with dashboard-stat-bisnis receives business statistics and donut data', function () {
    $manager = User::where('email', 'manager@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($manager);

    $res = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stat');

    $res->assertOk();
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data.type'))->toBe('bisnis')
        ->and($res->json('data.title'))->toBe('Ringkasan Bisnis')
        ->and($res->json('data.donut'))->toHaveKeys(['percentage', 'center_label', 'center_sublabel', 'slices'])
        ->and($res->json('data.metrics'))->toBeArray()
        ->and(count($res->json('data.metrics')))->toBe(3)
        ->and($res->json('data.metrics.0.label'))->toBe('Pemasukan Periode ini')
        ->and($res->json('data.metrics.1.label'))->toBe('Biaya Usaha Periode ini')
        ->and($res->json('data.metrics.2.label'))->toBe('Total Prive')
        ->and($res->json('data.date_formatted'))->toMatch('/^(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu), \d{2} (Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des) \d{4}$/');
});

test('kurir with dashboard-stat-pengantaran receives courier delivery statistics and donut data', function () {
    $kurir = User::where('email', 'kurir@halala-food.id')->firstOrFail();
    $token = JWTAuth::fromUser($kurir);

    $res = withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/dashboard/stat');

    $res->assertOk();
    expect($res->json('success'))->toBeTrue()
        ->and($res->json('data.type'))->toBe('pengantaran')
        ->and($res->json('data.title'))->toBe('Tugas Pengantaran')
        ->and($res->json('data.donut'))->toHaveKeys(['percentage', 'center_label', 'center_sublabel', 'slices'])
        ->and($res->json('data.metrics'))->toBeArray()
        ->and(count($res->json('data.metrics')))->toBe(3)
        ->and($res->json('data.date_formatted'))->toMatch('/^(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu), \d{2} (Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des) \d{4}$/');
});
