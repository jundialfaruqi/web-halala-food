<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Encode string to Base64URL (RFC 7515).
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Dapatkan Google OAuth2 Access Token menggunakan Service Account JSON.
     * Token di-cache selama 50 menit untuk menghemat round-trip HTTP.
     */
    public static function getGoogleAccessToken(): ?string
    {
        return Cache::remember('firebase_fcm_access_token', 3000, function () {
            $credentialsPath = storage_path('app/firebase/firebase_credentials.json');

            if (! file_exists($credentialsPath)) {
                Log::warning('FCM Error: File firebase_credentials.json tidak ditemukan di: '.$credentialsPath);

                return null;
            }

            try {
                $credentials = json_decode((string) file_get_contents($credentialsPath), true);
                if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
                    Log::error('FCM Error: Format file firebase_credentials.json tidak valid.');

                    return null;
                }

                $clientEmail = $credentials['client_email'];
                $privateKey = $credentials['private_key'];

                $now = time();
                $header = self::base64UrlEncode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
                $payload = self::base64UrlEncode((string) json_encode([
                    'iss' => $clientEmail,
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'iat' => $now,
                    'exp' => $now + 3600,
                ]));

                $signature = '';
                if (! openssl_sign("{$header}.{$payload}", $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                    Log::error('FCM Error: Gagal menandatangani JWT Assertion dengan private key.');

                    return null;
                }

                $jwt = "{$header}.{$payload}.".self::base64UrlEncode($signature);

                $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if (! $response->successful()) {
                    Log::error('FCM OAuth2 Token Error: '.$response->body());

                    return null;
                }

                return $response->json('access_token');
            } catch (Exception $e) {
                Log::error('FCM Exception saat generate OAuth token: '.$e->getMessage());

                return null;
            }
        });
    }

    /**
     * Kirim Push Notifikasi ke perangkat spesifik via FCM HTTP v1 API.
     *
     * @param  array<string, mixed>  $data
     */
    public static function sendPushNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty(trim($fcmToken))) {
            return false;
        }

        try {
            $credentialsPath = storage_path('app/firebase/firebase_credentials.json');
            if (! file_exists($credentialsPath)) {
                return false;
            }

            $credentials = json_decode((string) file_get_contents($credentialsPath), true);
            $projectId = $credentials['project_id'] ?? null;

            if (! $projectId) {
                Log::error('FCM Error: project_id tidak ditemukan pada file kredensial.');

                return false;
            }

            $accessToken = self::getGoogleAccessToken();
            if (! $accessToken) {
                return false;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // Format data payload wajib berupa string key-value
            $stringData = [];
            foreach ($data as $key => $val) {
                $stringData[(string) $key] = (string) $val;
            }

            $messagePayload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $stringData,
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'sound' => 'default',
                            'channel_id' => 'halala_food_notifications',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->contentType('application/json')
                ->post($url, $messagePayload);

            if ($response->successful()) {
                Log::info('FCM Berhasil terkirim ke: '.substr($fcmToken, 0, 15).'...');

                return true;
            }

            // Jika token sudah tidak valid / kedaluwarsa di Google, bersihkan dari database
            if ($response->status() === 404 || str_contains($response->body(), 'UNREGISTERED')) {
                User::where('fcm_token', $fcmToken)->update(['fcm_token' => null]);
                Log::info('FCM Token kedaluwarsa telah dibersihkan dari database.');
            }

            Log::error('FCM Kirim Gagal: '.$response->body());

            return false;
        } catch (Exception $e) {
            Log::error('FCM Exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Helper untuk mengirim notifikasi ke user tertentu jika memiliki token.
     *
     * @param  array<string, mixed>  $data
     */
    public static function sendToUser(?User $user, string $title, string $body, array $data = []): bool
    {
        if (! $user || empty($user->fcm_token)) {
            return false;
        }

        return self::sendPushNotification($user->fcm_token, $title, $body, $data);
    }

    /**
     * Helper untuk mengirim notifikasi ke kurir berdasarkan ID.
     *
     * @param  array<string, mixed>  $data
     */
    public static function sendToCourier(?int $courierId, string $title, string $body, array $data = []): bool
    {
        if (! $courierId) {
            return false;
        }

        $courier = User::find($courierId);

        return self::sendToUser($courier, $title, $body, $data);
    }
}
