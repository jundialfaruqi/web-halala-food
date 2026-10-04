<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login user and issue access_token & refresh_token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = auth('api')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau password yang Anda masukkan salah.',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat sesi autentikasi.',
            ], 500);
        }

        /** @var User $user */
        $user = auth('api')->user();

        return $this->respondWithToken($token, $user, 'Login berhasil.');
    }

    /**
     * Get authenticated user profile with roles and permissions.
     */
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil.',
            'data' => $this->formatUserData($user),
        ]);
    }

    /**
     * Refresh access token using refresh_token or current bearer token.
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            // Jika refresh token dikirimkan melalui body JSON
            $refreshToken = $request->input('refresh_token');

            if ($refreshToken) {
                try {
                    $tokenObj = JWTAuth::setToken($refreshToken);
                    $payload = $tokenObj->getPayload();

                    // Pastikan token tipe refresh atau valid
                    $userId = $payload->get('sub');
                    $user = User::find($userId);

                    if (! $user) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Pengguna untuk token ini tidak ditemukan.',
                        ], 404);
                    }

                    // Buat access token baru
                    $newToken = auth('api')->login($user);

                    return $this->respondWithToken($newToken, $user, 'Token berhasil diperbarui.');
                } catch (TokenExpiredException $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Refresh token telah kedaluwarsa. Silakan login kembali.',
                    ], 401);
                } catch (TokenInvalidException $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Refresh token tidak valid.',
                    ], 401);
                }
            }

            // Fallback: refresh bearer token via auth('api')
            $newToken = auth('api')->refresh();
            /** @var User $user */
            $user = auth('api')->user();

            return $this->respondWithToken($newToken, $user, 'Token berhasil diperbarui.');
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi autentikasi telah berakhir. Silakan login kembali.',
            ], 401);
        }
    }

    /**
     * Invalidate token and log the user out.
     */
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil keluar (logout).',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses logout: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to format token response.
     */
    protected function respondWithToken(string $token, User $user, string $message = 'Sukses'): JsonResponse
    {
        // Buat refresh token terpisah dengan masa berlaku 2 minggu
        $refreshTtlMinutes = (int) config('jwt.refresh_ttl', 20160);
        $refreshToken = auth('api')
            ->claims(['type' => 'refresh'])
            ->setTTL($refreshTtlMinutes)
            ->tokenById($user->id);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60, // detik
                'user' => $this->formatUserData($user),
            ],
        ]);
    }

    /**
     * Helper to extract user data with all roles and permissions.
     */
    protected function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'initials' => $user->initials(),
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
