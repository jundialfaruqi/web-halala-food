<div x-data="{
    activeTab: (function() {
        const hash = window.location.hash.replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        if (['roles', 'groups', 'permissions'].includes(hash)) return hash;
        if (['roles', 'groups', 'permissions'].includes(queryTab)) return queryTab;
        return 'roles';
    })(),
    roleSearch: '',
    groupSearch: '',
    permissionSearch: '',
    permissionGroupFilter: 'all',
    isProcessing: false,

    init() {
        this.$watch('activeTab', (tab) => {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.replaceState(null, '', url.toString());
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (['roles', 'groups', 'permissions'].includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });
    },

    // Data stored directly from backend
    roles: {{ \Illuminate\Support\Js::from($roles) }},
    groups: {{ \Illuminate\Support\Js::from($groups) }},
    permissions: {{ \Illuminate\Support\Js::from($permissions) }},
    ungroupedPermissions: {{ \Illuminate\Support\Js::from($ungroupedPermissions) }},

    // Role Modal
    showRoleModal: false,
    roleModalTitle: 'Tambah Role Baru',
    roleForm: { id: null, name: '', permission_ids: [] },
    rolePermSearch: '',
    roleErrors: {},
    roleFormError: '',

    // Group Modal
    showGroupModal: false,
    groupModalTitle: 'Tambah Grup Permission',
    groupForm: { id: null, name: '', description: '', permission_ids: [] },
    groupPermSearch: '',
    groupErrors: {},
    groupFormError: '',

    // Permission Modal
    showPermissionModal: false,
    permissionModalTitle: 'Tambah Permission',
    permissionForm: { id: null, name: '', group_id: '' },
    permissionErrors: {},
    permissionFormError: '',

    // Delete Modal
    showDeleteModal: false,
    deleteTarget: { type: '', id: null, name: '', description: '', isDev: false },

    // Dispatch to global Toast component
    notify(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    // Role Methods
    openCreateRoleModal() {
        this.roleForm = { id: null, name: '', permission_ids: [] };
        this.roleModalTitle = 'Tambah Role Baru';
        this.rolePermSearch = '';
        this.roleErrors = {};
        this.roleFormError = '';
        this.showRoleModal = true;
    },
    openEditRoleById(id) {
        const role = this.roles.find(r => r.id === id);
        if (!role) return;
        this.roleForm = {
            id: role.id,
            name: role.name,
            permission_ids: role.permissions ? role.permissions.map(p => p.id) : []
        };
        this.roleModalTitle = 'Edit Role: ' + role.name;
        this.rolePermSearch = '';
        this.roleErrors = {};
        this.roleFormError = '';
        this.showRoleModal = true;
    },
    toggleAllPermissions() {
        const allIds = this.permissions.map(p => p.id);
        if (this.areAllSelected(allIds)) {
            this.roleForm.permission_ids = [];
        } else {
            this.roleForm.permission_ids = [...allIds];
        }
    },
    areAllSelected(ids) {
        if (!ids || ids.length === 0) return false;
        return ids.every(id => this.roleForm.permission_ids.includes(id));
    },
    toggleGroupPermissionsById(groupId) {
        const group = this.groups.find(g => g.id === groupId);
        if (!group || !group.permissions) return;
        const groupPermIds = group.permissions.map(p => p.id);
        const allSelected = groupPermIds.length > 0 && groupPermIds.every(id => this.roleForm.permission_ids.includes(id));
        if (allSelected) {
            this.roleForm.permission_ids = this.roleForm.permission_ids.filter(id => !groupPermIds.includes(id));
        } else {
            const combined = new Set([...this.roleForm.permission_ids, ...groupPermIds]);
            this.roleForm.permission_ids = Array.from(combined);
        }
    },
    togglePermission(id) {
        const idx = this.roleForm.permission_ids.indexOf(id);
        if (idx > -1) {
            this.roleForm.permission_ids.splice(idx, 1);
        } else {
            this.roleForm.permission_ids.push(id);
        }
    },
    async submitRole() {
        this.roleErrors = {};
        this.roleFormError = '';
        if (!this.roleForm.name.trim()) {
            this.roleErrors.name = ['Nama role wajib diisi.'];
            return;
        }
        this.isProcessing = true;
        const res = await $wire.saveRole(this.roleForm.id, this.roleForm.name, this.roleForm.permission_ids);
        this.isProcessing = false;
        if (res.success) {
            this.showRoleModal = false;
            this.roleErrors = {};
        } else {
            this.roleErrors = res.errors || {};
            if (Object.keys(this.roleErrors).length === 0) {
                this.roleFormError = res.message;
            }
        }
    },

    // Group Methods
    openCreateGroupModal() {
        this.groupForm = { id: null, name: '', description: '', permission_ids: [] };
        this.groupModalTitle = 'Tambah Grup Permission';
        this.groupPermSearch = '';
        this.groupErrors = {};
        this.groupFormError = '';
        this.showGroupModal = true;
    },
    openEditGroupById(id) {
        const group = this.groups.find(g => g.id === id);
        if (!group) return;
        this.groupForm = {
            id: group.id,
            name: group.name,
            description: group.description || '',
            permission_ids: group.permissions ? group.permissions.map(p => p.id) : []
        };
        this.groupModalTitle = 'Edit Grup: ' + group.name;
        this.groupPermSearch = '';
        this.groupErrors = {};
        this.groupFormError = '';
        this.showGroupModal = true;
    },
    toggleGroupModalPermission(id) {
        const idx = this.groupForm.permission_ids.indexOf(id);
        if (idx > -1) {
            this.groupForm.permission_ids.splice(idx, 1);
        } else {
            this.groupForm.permission_ids.push(id);
        }
    },
    toggleAllGroupModalPermissions() {
        const allIds = this.permissions.map(p => p.id);
        if (this.areAllSelectedInGroup(allIds)) {
            this.groupForm.permission_ids = [];
        } else {
            this.groupForm.permission_ids = [...allIds];
        }
    },
    areAllSelectedInGroup(ids) {
        if (!ids || ids.length === 0) return false;
        return ids.every(id => this.groupForm.permission_ids.includes(id));
    },
    async submitGroup() {
        this.groupErrors = {};
        this.groupFormError = '';
        if (!this.groupForm.name.trim()) {
            this.groupErrors.name = ['Nama grup wajib diisi.'];
            return;
        }
        this.isProcessing = true;
        const res = await $wire.saveGroup(
            this.groupForm.id, 
            this.groupForm.name, 
            this.groupForm.description, 
            this.groupForm.permission_ids
        );
        this.isProcessing = false;
        if (res.success) {
            this.showGroupModal = false;
            this.groupErrors = {};
        } else {
            this.groupErrors = res.errors || {};
            if (Object.keys(this.groupErrors).length === 0) {
                this.groupFormError = res.message;
            }
        }
    },

    // Permission Methods
    openCreatePermissionModal() {
        this.permissionForm = { id: null, name: '', group_id: '' };
        this.permissionModalTitle = 'Tambah Permission Baru';
        this.permissionPermSearch = '';
        this.permissionErrors = {};
        this.permissionFormError = '';
        this.showPermissionModal = true;
    },
    openEditPermissionById(id) {
        const perm = this.permissions.find(p => p.id === id);
        if (!perm) return;
        this.permissionForm = {
            id: perm.id,
            name: perm.name,
            group_id: perm.permission_group_id ? String(perm.permission_group_id) : ''
        };
        this.permissionModalTitle = 'Edit Permission: ' + perm.name;
        this.permissionErrors = {};
        this.permissionFormError = '';
        this.showPermissionModal = true;
    },
    async submitPermission() {
        this.permissionErrors = {};
        this.permissionFormError = '';
        if (!this.permissionForm.name.trim()) {
            this.permissionErrors.name = ['Nama permission wajib diisi.'];
            return;
        }
        this.isProcessing = true;
        const res = await $wire.savePermission(
            this.permissionForm.id,
            this.permissionForm.name,
            this.permissionForm.group_id ? parseInt(this.permissionForm.group_id) : null
        );
        this.isProcessing = false;
        if (res.success) {
            this.showPermissionModal = false;
            this.permissionErrors = {};
        } else {
            this.permissionErrors = res.errors || {};
            if (Object.keys(this.permissionErrors).length === 0) {
                this.permissionFormError = res.message;
            }
        }
    },

    // Delete Confirmation Methods
    confirmDeleteRoleById(id) {
        const role = this.roles.find(r => r.id === id);
        if (!role) return;
        const isDev = ['dev', 'developer'].includes(role.name.toLowerCase());
        this.deleteTarget = {
            type: 'role',
            id: role.id,
            name: role.name,
            isDev: isDev,
            description: isDev 
                ? 'Role developer adalah role sistem utama dan tidak dapat dihapus.'
                : `Apakah Anda yakin ingin menghapus role '${role.name}'? Role ini saat ini digunakan oleh ${role.users_count || 0} pengguna.`
        };
        this.showDeleteModal = true;
    },
    confirmDeleteGroupById(id) {
        const group = this.groups.find(g => g.id === id);
        if (!group) return;
        const count = group.permissions_count !== undefined ? group.permissions_count : (group.permissions ? group.permissions.length : 0);
        this.deleteTarget = {
            type: 'group',
            id: group.id,
            name: group.name,
            isDev: false,
            description: `Apakah Anda yakin ingin menghapus grup permission '${group.name}'? Seluruh permission di dalamnya (${count}) tidak akan terhapus, hanya status grupnya menjadi belum dikelompokkan.`
        };
        this.showDeleteModal = true;
    },
    confirmDeletePermissionById(id) {
        const perm = this.permissions.find(p => p.id === id);
        if (!perm) return;
        this.deleteTarget = {
            type: 'permission',
            id: perm.id,
            name: perm.name,
            isDev: false,
            description: `Apakah Anda yakin ingin menghapus permission '${perm.name}'? Hak akses ini akan dicabut dari semua role yang menggunakannya.`
        };
        this.showDeleteModal = true;
    },
    async executeDelete() {
        if (!this.deleteTarget.id || this.deleteTarget.isDev) return;
        this.isProcessing = true;
        if (this.deleteTarget.type === 'role') {
            await $wire.deleteRole(this.deleteTarget.id);
        } else if (this.deleteTarget.type === 'group') {
            await $wire.deleteGroup(this.deleteTarget.id);
        } else if (this.deleteTarget.type === 'permission') {
            await $wire.deletePermission(this.deleteTarget.id);
        }
        this.isProcessing = false;
        this.showDeleteModal = false;
    }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Role &amp; Hak Akses</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Role &amp; Hak Akses
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola hak akses peran pengguna, grup otorisasi, dan permission sistem Halala Food.
            </p>
        </div>
    </div>

    <!-- Main Section (Clean Underline Tab Navigation) -->
    <div class="space-y-6">
        
        <!-- Tab Navigation Bar -->
        <div class="flex border-b border-brand-border gap-4 sm:gap-8 overflow-x-auto">
            <!-- 1. Tab Role -->
            <button type="button" @click="activeTab = 'roles'"
                :class="activeTab === 'roles' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-1.5 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <span>Role Pengguna</span>
                <span class="text-xs text-brand-warm-gray">({{ $totalRoles }})</span>
            </button>

            <!-- 2. Tab Group Permission -->
            <button type="button" @click="activeTab = 'groups'"
                :class="activeTab === 'groups' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-1.5 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <span>Grup Permission</span>
                <span class="text-xs text-brand-warm-gray">({{ $totalGroups }})</span>
            </button>

            <!-- 3. Tab Permission -->
            <button type="button" @click="activeTab = 'permissions'"
                :class="activeTab === 'permissions' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-1.5 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <span>Daftar Permission</span>
                <span class="text-xs text-brand-warm-gray">({{ $totalPermissions }})</span>
            </button>
        </div>

        <!-- TAB 1: ROLE PENGGUNA -->
        @include('components.admin.roles.partials.tab-roles')

        <!-- TAB 2: GRUP PERMISSION -->
        @include('components.admin.roles.partials.tab-groups')

        <!-- TAB 3: DAFTAR PERMISSION -->
        @include('components.admin.roles.partials.tab-permissions')

    </div>

    <!-- MODALS -->
    @include('components.admin.roles.partials.modal-role')
    @include('components.admin.roles.partials.modal-group')
    @include('components.admin.roles.partials.modal-permission')
    @include('components.admin.roles.partials.modal-delete')

</div>

