<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Check whether authenticated user has permission to manage users.
     */
    private function authorizeUserManage(): ?JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if (! $user || (! $user->hasRole('dev') && ! $user->hasPermissionTo('user-manage', 'web') && ! $user->can('user-manage'))) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengelola data pengguna.',
            ], 403);
        }

        return null;
    }

    /**
     * Get paginated list of users with search and role filters.
     * Permission: user-manage (sama seperti web).
     */
    public function index(Request $request): JsonResponse
    {
        if ($authResponse = $this->authorizeUserManage()) {
            return $authResponse;
        }

        $query = User::with('roles');

        // Filter pencarian berdasarkan nama, email, atau telepon
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        // Filter berdasarkan peran (role)
        if ($request->filled('role') && $request->input('role') !== 'all') {
            $roleName = trim($request->input('role'));
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        // Urutkan (default: nama asc)
        $query->orderBy('name', 'asc');

        // Pagination
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));
        $users = $query->paginate($perPage);

        $mappedUsers = collect($users->items())->map(function (User $u) {
            return $this->formatUser($u);
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil dimuat.',
            'data' => $mappedUsers,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'has_more' => $users->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get list of roles for filter dropdown.
     */
    public function roles(): JsonResponse
    {
        if ($authResponse = $this->authorizeUserManage()) {
            return $authResponse;
        }

        $roles = Role::orderBy('name', 'asc')->get()->map(function (Role $r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'display_name' => $this->formatRoleDisplayName($r->name),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar peran berhasil dimuat.',
            'data' => $roles,
        ]);
    }

    /**
     * Get single user detail.
     */
    public function show(User $user): JsonResponse
    {
        if ($authResponse = $this->authorizeUserManage()) {
            return $authResponse;
        }

        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil dimuat.',
            'data' => $this->formatUser($user),
        ]);
    }

    /**
     * Create new user.
     */
    public function store(Request $request): JsonResponse
    {
        if ($authResponse = $this->authorizeUserManage()) {
            return $authResponse;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'role.required' => 'Pilih salah satu peran (role).',
            'role.exists' => 'Peran yang dipilih tidak ditemukan.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        $normalizedPhone = User::normalizePhone($validated['phone'] ?? null);

        $newUser = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $normalizedPhone,
            'password' => Hash::make($validated['password']),
        ]);

        $role = Role::findByName($validated['role'], 'web');
        $newUser->syncRoles([$role]);
        $newUser->load('roles');

        return response()->json([
            'success' => true,
            'message' => "Pengguna '{$newUser->name}' berhasil ditambahkan.",
            'data' => $this->formatUser($newUser),
        ], 201);
    }

    /**
     * Update existing user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if ($authResponse = $this->authorizeUserManage()) {
            return $authResponse;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'role.required' => 'Pilih salah satu peran (role).',
            'role.exists' => 'Peran yang dipilih tidak ditemukan.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        $updateData = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => User::normalizePhone($validated['phone'] ?? null),
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $role = Role::findByName($validated['role'], 'web');
        $user->syncRoles([$role]);
        $user->load('roles');

        return response()->json([
            'success' => true,
            'message' => "Data pengguna '{$user->name}' berhasil diperbarui.",
            'data' => $this->formatUser($user),
        ]);
    }

    /**
     * Delete user.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($authResponse = $this->authorizeUserManage()) {
            return $authResponse;
        }

        if (auth('api')->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 422);
        }

        $userName = $user->name;
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Pengguna '{$userName}' berhasil dihapus.",
        ]);
    }

    /**
     * Helper formatting user data.
     */
    private function formatUser(User $u): array
    {
        $primaryRole = $u->roles->first()?->name ?? 'Staff';

        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'formatted_phone' => $u->formattedPhone(),
            'whatsapp_url' => $u->whatsappUrl(),
            'initials' => $this->cleanInitials($u),
            'roles' => $u->roles->pluck('name')->values()->all(),
            'primary_role' => $primaryRole,
            'primary_role_label' => $this->formatRoleDisplayName($primaryRole),
            'created_at' => $u->created_at?->toIso8601String(),
            'created_at_human' => $u->created_at ? $u->created_at->translatedFormat('d M Y') : '-',
            'is_current_user' => auth('api')->id() === $u->id,
        ];
    }

    /**
     * Helper cleanly calculating 2-letter uppercase initials.
     */
    private function cleanInitials(User $u): string
    {
        $cleanName = trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $u->name));
        $words = array_values(array_filter(preg_split('/\s+/', $cleanName)));
        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1));
        } elseif (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, min(2, mb_strlen($words[0]))));
        }

        return 'US';
    }

    /**
     * Helper formatting human-friendly role name.
     */
    private function formatRoleDisplayName(string $role): string
    {
        return match (strtolower($role)) {
            'dev' => 'Developer / Super Admin',
            'manager' => 'Manager Operasional',
            'kurir' => 'Kurir Pengantaran',
            default => ucfirst($role),
        };
    }
}
