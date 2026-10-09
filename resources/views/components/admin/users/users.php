<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Layout('components.layouts.admin'), Title('Manajemen Pengguna - Halala Food')] class extends Component
{
    public function mount()
    {
        if (Gate::denies('user-manage')) {
            abort(403, 'Anda tidak memiliki izin untuk mengelola data pengguna.');
        }
    }

    public function render()
    {
        $users = User::with('roles')
            ->latest('id')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'formatted_phone' => $user->formattedPhone(),
                    'whatsapp_url' => $user->whatsappUrl(),
                    'initials' => $user->initials(),
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'primary_role' => $user->roles->first()?->name ?? 'Tanpa Role',
                    'created_at_human' => $user->created_at ? $user->created_at->translatedFormat('d M Y') : '-',
                    'is_current_user' => $user->id === Auth::id(),
                ];
            });

        $roles = Role::orderBy('name')->get();
        $totalUsers = $users->count();

        return view('components.admin.users.users', [
            'users' => $users,
            'roles' => $roles,
            'totalUsers' => $totalUsers,
            'currentUserId' => Auth::id(),
        ]);
    }

    /**
     * Create or update a user.
     */
    public function saveUser(?int $id, string $name, string $email, ?string $phone, string $role, ?string $password = null): array
    {
        if (Gate::denies('user-manage')) {
            return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.'];
        }

        $name = trim($name);
        $email = strtolower(trim($email));
        $normalizedPhone = User::normalizePhone($phone);

        // Validation rules
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];

        if (! $id || ! empty($password)) {
            $rules['password'] = [! $id ? 'required' : 'nullable', 'string', 'min:6'];
        }

        $validator = validator([
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'phone' => $phone,
            'password' => $password,
        ], $rules, [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan oleh pengguna lain.',
            'role.required' => 'Pilih salah satu peran (role) untuk pengguna.',
            'role.exists' => 'Peran (role) yang dipilih tidak ditemukan dalam sistem.',
            'password.required' => 'Kata sandi wajib diisi untuk pengguna baru.',
            'password.min' => 'Kata sandi minimal harus terdiri dari 6 karakter.',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ];
        }

        try {
            DB::transaction(function () use ($id, $name, $email, $normalizedPhone, $role, $password) {
                $data = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $normalizedPhone,
                ];

                if (! empty($password)) {
                    $data['password'] = Hash::make($password);
                }

                if ($id) {
                    $user = User::findOrFail($id);
                    $user->update($data);
                } else {
                    $user = User::create($data);
                }

                $user->syncRoles([$role]);
            });

            $msg = $id ? 'Data pengguna berhasil diperbarui.' : 'Pengguna baru berhasil ditambahkan.';
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (Throwable $e) {
            $errMsg = 'Gagal menyimpan data pengguna: '.$e->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete a user.
     */
    public function deleteUser(int $id): array
    {
        if (Gate::denies('user-manage')) {
            $msg = 'Anda tidak memiliki izin untuk menghapus pengguna.';
            $this->dispatch('show-toast', message: $msg, type: 'error');

            return ['success' => false, 'message' => $msg];
        }

        if ($id === Auth::id()) {
            $msg = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.';
            $this->dispatch('show-toast', message: $msg, type: 'error');

            return [
                'success' => false,
                'message' => $msg,
            ];
        }

        $user = User::find($id);
        if (! $user) {
            $msg = 'Pengguna tidak ditemukan.';
            $this->dispatch('show-toast', message: $msg, type: 'error');

            return ['success' => false, 'message' => $msg];
        }

        // Prevent deleting developer role if only 1 exists
        if ($user->hasRole('dev') && User::role('dev')->count() <= 1) {
            $msg = 'Tidak dapat menghapus akun developer utama terakhir dalam sistem.';
            $this->dispatch('show-toast', message: $msg, type: 'error');

            return [
                'success' => false,
                'message' => $msg,
            ];
        }

        try {
            $userName = $user->name;
            $user->delete();

            $msg = "Pengguna '{$userName}' berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (Throwable $e) {
            $errMsg = 'Gagal menghapus pengguna: '.$e->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }
};
