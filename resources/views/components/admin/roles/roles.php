<?php

namespace App\Livewire\Admin;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

new #[Layout('layouts.admin'), Title('Role & Permission - Halala Food')] class extends Component
{
    /**
     * Save (Create or Update) a Role
     *
     * @param int|null $id
     * @param string $name
     * @param array<int> $permissionIds
     */
    public function saveRole(?int $id, string $name, array $permissionIds = []): array
    {
        $name = trim($name);

        $rules = [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('roles', 'name')->ignore($id),
            ],
        ];

        $validator = validator(
            ['name' => $name],
            $rules,
            [
                'name.required' => 'Nama role wajib diisi.',
                'name.unique' => 'Nama role ini sudah digunakan.',
                'name.min' => 'Nama role minimal 2 karakter.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first('name'),
            ];
        }

        try {
            DB::transaction(function () use ($id, $name, $permissionIds) {
                if ($id) {
                    $role = Role::findOrFail($id);
                    $role->name = $name;
                    $role->save();
                } else {
                    $role = Role::create([
                        'name' => $name,
                        'guard_name' => 'web',
                    ]);
                }

                // Sync permissions
                $permissions = Permission::whereIn('id', $permissionIds)->get();
                $role->syncPermissions($permissions);

                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            });

            $msg = $id ? "Role '{$name}' berhasil diperbarui." : "Role baru '{$name}' berhasil ditambahkan.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Terjadi kesalahan: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete a Role
     */
    public function deleteRole(int $id): array
    {
        try {
            $role = Role::withCount('users')->findOrFail($id);

            // Prevent deleting developer / superadmin role
            if (in_array(strtolower($role->name), ['dev', 'developer'])) {
                $msg = "Role '{$role->name}' adalah role sistem utama dan tidak dapat dihapus.";
                $this->dispatch('show-toast', message: $msg, type: 'error');

                return [
                    'success' => false,
                    'message' => $msg,
                ];
            }

            if ($role->users_count > 0) {
                $msg = "Role '{$role->name}' sedang digunakan oleh {$role->users_count} pengguna. Pindahkan pengguna terlebih dahulu.";
                $this->dispatch('show-toast', message: $msg, type: 'error');

                return [
                    'success' => false,
                    'message' => $msg,
                ];
            }

            $roleName = $role->name;
            $role->delete();

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $msg = "Role '{$roleName}' berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menghapus role: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Save (Create or Update) a Permission Group
     *
     * @param int|null $id
     * @param string $name
     * @param string|null $description
     * @param array<int> $permissionIds
     */
    public function saveGroup(?int $id, string $name, ?string $description = null, array $permissionIds = []): array
    {
        $name = trim($name);
        $description = $description ? trim($description) : null;

        $rules = [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('permission_groups', 'name')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];

        $validator = validator(
            ['name' => $name, 'description' => $description],
            $rules,
            [
                'name.required' => 'Nama grup permission wajib diisi.',
                'name.unique' => 'Nama grup permission sudah ada.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first('name') ?: $validator->errors()->first('description'),
            ];
        }

        try {
            DB::transaction(function () use ($id, $name, $description, $permissionIds) {
                if ($id) {
                    $group = PermissionGroup::findOrFail($id);
                    $group->update([
                        'name' => $name,
                        'description' => $description,
                    ]);
                } else {
                    $group = PermissionGroup::create([
                        'name' => $name,
                        'description' => $description,
                    ]);
                }

                // Unassign permissions that are no longer in this group
                Permission::where('permission_group_id', $group->id)
                    ->whereNotIn('id', $permissionIds)
                    ->update(['permission_group_id' => null]);

                // Assign selected permissions to this group
                if (!empty($permissionIds)) {
                    Permission::whereIn('id', $permissionIds)
                        ->update(['permission_group_id' => $group->id]);
                }

                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            });

            $msg = $id ? "Grup permission '{$name}' berhasil diperbarui." : "Grup permission '{$name}' berhasil dibuat.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Terjadi kesalahan: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete a Permission Group
     */
    public function deleteGroup(int $id): array
    {
        try {
            $group = PermissionGroup::findOrFail($id);
            $groupName = $group->name;

            // Unlink permissions from this group before deleting
            Permission::where('permission_group_id', $id)->update(['permission_group_id' => null]);

            $group->delete();

            $msg = "Grup permission '{$groupName}' berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menghapus grup permission: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Save (Create or Update) an Individual Permission
     */
    public function savePermission(?int $id, string $name, ?int $groupId = null): array
    {
        $name = trim(strtolower($name));

        $rules = [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-z0-9\-_]+$/i',
                Rule::unique('permissions', 'name')->ignore($id),
            ],
            'groupId' => ['nullable', 'exists:permission_groups,id'],
        ];

        $validator = validator(
            ['name' => $name, 'groupId' => $groupId],
            $rules,
            [
                'name.required' => 'Nama permission wajib diisi.',
                'name.unique' => 'Nama permission sudah ada.',
                'name.regex' => 'Format permission harus menggunakan huruf, angka, tanda strip (-) atau underscore (_). Contoh: order-create',
                'groupId.exists' => 'Grup permission yang dipilih tidak valid.',
            ]
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => $validator->errors()->first('name') ?: $validator->errors()->first('groupId'),
            ];
        }

        try {
            if ($id) {
                $permission = Permission::findOrFail($id);
                $permission->name = $name;
                $permission->permission_group_id = $groupId ?: null;
                $permission->save();
            } else {
                Permission::create([
                    'name' => $name,
                    'guard_name' => 'web',
                    'permission_group_id' => $groupId ?: null,
                ]);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $msg = $id ? "Permission '{$name}' berhasil diperbarui." : "Permission '{$name}' berhasil dibuat.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Terjadi kesalahan: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Delete an Individual Permission
     */
    public function deletePermission(int $id): array
    {
        try {
            $permission = Permission::findOrFail($id);
            $permissionName = $permission->name;

            // Detach from all roles first
            $permission->roles()->detach();
            $permission->delete();

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $msg = "Permission '{$permissionName}' berhasil dihapus.";
            $this->dispatch('show-toast', message: $msg, type: 'success');

            return [
                'success' => true,
                'message' => $msg,
            ];
        } catch (\Throwable $th) {
            $errMsg = 'Gagal menghapus permission: ' . $th->getMessage();
            $this->dispatch('show-toast', message: $errMsg, type: 'error');

            return [
                'success' => false,
                'message' => $errMsg,
            ];
        }
    }

    /**
     * Provide dataset for the view
     */
    public function with(): array
    {
        $roles = Role::withCount('users')
            ->with('permissions')
            ->orderBy('id', 'asc')
            ->get();

        $groups = PermissionGroup::withCount('permissions')
            ->with('permissions')
            ->orderBy('name', 'asc')
            ->get();

        $permissions = Permission::with(['group', 'roles'])
            ->orderBy('name', 'asc')
            ->get();

        $ungroupedPermissions = Permission::whereNull('permission_group_id')
            ->orderBy('name', 'asc')
            ->get();

        return [
            'roles' => $roles,
            'groups' => $groups,
            'permissions' => $permissions,
            'ungroupedPermissions' => $ungroupedPermissions,
            'totalRoles' => $roles->count(),
            'totalGroups' => $groups->count(),
            'totalPermissions' => $permissions->count(),
        ];
    }
};
