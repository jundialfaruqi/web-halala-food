<?php

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeader;

uses(RefreshDatabase::class);

test('authenticated user can update fcm token via api', function () {
    $role = Role::firstOrCreate(['name' => 'kurir', 'guard_name' => 'web']);
    $user = User::factory()->create([
        'email' => 'kurir@halala-food.id',
        'fcm_token' => null,
    ]);
    $user->assignRole($role);

    $token = JWTAuth::fromUser($user);

    $testFcmToken = 'fcm_sample_token_abc_123_456_789_xyz_unique_key';

    $response = withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/user/fcm-token', [
            'fcm_token' => $testFcmToken,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'FCM Token berhasil diperbarui.',
        ]);

    expect($user->fresh()->fcm_token)->toBe($testFcmToken);
});

test('unauthenticated request to update fcm token fails', function () {
    $response = postJson('/api/user/fcm-token', [
        'fcm_token' => 'fcm_sample_token',
    ]);

    $response->assertUnauthorized();
});

test('fcm service can generate google access token when credentials exist', function () {
    $credentialsPath = storage_path('app/firebase/firebase_credentials.json');

    if (! file_exists($credentialsPath)) {
        $this->markTestSkipped('firebase_credentials.json not present in storage/app/firebase');
    }

    $token = FcmService::getGoogleAccessToken();

    expect($token)->toBeString()
        ->and(strlen($token))->toBeGreaterThan(20);
});
