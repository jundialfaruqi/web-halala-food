<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Mendapatkan daftar linimasa aktifitas sistem dengan paginasi berbasis hak akses / permission.
     * 
     * Hak Akses:
     * - 'aktifitas-semua' : melihat semua aktifitas dari seluruh user.
     * - 'aktifitas-kurir' : hanya melihat aktifitas yang ditujukan ke kurir tersebut (courier_id)
     *                      atau dikerjakan oleh kurir itu sendiri (user_id).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $canViewAll = $user->hasRole('dev') || $user->hasPermissionTo('aktifitas-semua');
        $canViewCourier = $user->hasRole('dev') || $user->hasPermissionTo('aktifitas-kurir');

        if (! $canViewAll && ! $canViewCourier) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk melihat linimasa aktifitas.',
            ], 403);
        }

        $query = Activity::with(['user.roles', 'courier']);

        // Jika hanya memiliki hak akses kurir (bukan aktifitas-semua)
        if (! $canViewAll && $canViewCourier) {
            $query->where(function ($q) use ($user) {
                $q->where('courier_id', $user->id)
                  ->orWhere('user_id', $user->id);
            });
        }

        // Paginasi: default 50 per page sesuai kebutuhan
        $perPage = min((int) $request->input('per_page', 50), 100);
        if ($perPage <= 0) {
            $perPage = 50;
        }

        // Cursor pagination jika parameter before_id diberikan (untuk scroll ke atas melihat data lebih lama)
        if ($request->filled('before_id')) {
            $query->where('id', '<', (int) $request->input('before_id'));
        }

        // Urutan: dari yang terbaru ke yang terlama (descending)
        $query->orderByDesc('id');

        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function (Activity $act) {
            $actor = $act->user;
            $actorRole = $actor?->roles?->first()?->name ?? 'Staf';

            return [
                'id' => $act->id,
                'type' => $act->type,
                'title' => $act->title,
                'description' => $act->description,
                'user_id' => $act->user_id,
                'user_name' => $actor?->name ?? 'User',
                'user_role' => ucfirst($actorRole),
                'user_initials' => $act->user_initials,
                'user_avatar' => $actor?->avatar_url,
                'courier_id' => $act->courier_id,
                'courier_name' => $act->courier?->name,
                'subject_type' => $act->subject_type,
                'subject_id' => $act->subject_id,
                'data' => $act->data,
                'created_at' => $act->created_at?->toIso8601String(),
                'time_formatted' => $act->time_formatted,
                'date_formatted' => $act->date_formatted,
                'date_group_key' => $act->date_group_key,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'can_view_all' => $canViewAll,
        ]);
    }
}
