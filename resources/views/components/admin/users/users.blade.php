<div x-data="{
    search: '',
    roleFilter: 'all',
    isProcessing: false,

    // Data from backend
    users: {{ \Illuminate\Support\Js::from($users) }},
    roles: {{ \Illuminate\Support\Js::from($roles) }},
    currentUserId: {{ $currentUserId }},

    // User Modal State
    showUserModal: false,
    userModalTitle: 'Tambah Pengguna Baru',
    userForm: { id: null, name: '', email: '', phone: '', role: '', password: '' },
    userErrors: {},
    userFormError: '',

    // Delete Modal State
    showDeleteModal: false,
    deleteTarget: { id: null, name: '', description: '' },

    // Sanitize phone input client-side (strip leading 0, 62, and non-digits)
    sanitizePhone(val) {
        if (!val) return '';
        let digits = String(val).replace(/\D/g, '');
        if (digits.startsWith('62')) digits = digits.substring(2);
        while (digits.startsWith('0')) digits = digits.substring(1);
        return digits;
    },

    // Dispatch to global Toast component
    notify(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    // Open Create Modal
    openCreateModal() {
        this.userForm = { id: null, name: '', email: '', phone: '', role: this.roles[0]?.name || '', password: '' };
        this.userModalTitle = 'Tambah Pengguna Baru';
        this.userErrors = {};
        this.userFormError = '';
        this.showUserModal = true;
    },

    // Open Edit Modal by User ID
    openEditModalById(id) {
        const user = this.users.find(u => u.id === id);
        if (!user) return;
        this.userForm = {
            id: user.id,
            name: user.name,
            email: user.email,
            phone: user.phone ? (user.phone.startsWith('62') ? user.phone.substring(2) : user.phone) : '',
            role: user.roles[0] || (this.roles[0]?.name || ''),
            password: ''
        };
        this.userModalTitle = 'Edit Pengguna: ' + user.name;
        this.userErrors = {};
        this.userFormError = '';
        this.showUserModal = true;
    },

    // Submit User Form
    async submitUser() {
        this.userErrors = {};
        this.userFormError = '';

        let hasClientError = false;
        if (!this.userForm.name.trim()) {
            this.userErrors.name = ['Nama lengkap pengguna wajib diisi.'];
            hasClientError = true;
        }
        if (!this.userForm.email.trim()) {
            this.userErrors.email = ['Alamat email wajib diisi.'];
            hasClientError = true;
        }
        if (!this.userForm.role) {
            this.userErrors.role = ['Pilih salah satu peran (role) untuk pengguna.'];
            hasClientError = true;
        }
        if (!this.userForm.id && !this.userForm.password) {
            this.userErrors.password = ['Kata sandi wajib diisi untuk pengguna baru.'];
            hasClientError = true;
        }
        if (this.userForm.password && this.userForm.password.length < 6) {
            this.userErrors.password = ['Kata sandi minimal harus terdiri dari 6 karakter.'];
            hasClientError = true;
        }

        if (hasClientError) return;

        this.isProcessing = true;
        const res = await $wire.saveUser(
            this.userForm.id,
            this.userForm.name,
            this.userForm.email,
            this.userForm.phone,
            this.userForm.role,
            this.userForm.password || null
        );
        this.isProcessing = false;

        if (res.success) {
            this.showUserModal = false;
            this.userErrors = {};
        } else {
            this.userErrors = res.errors || {};
            if (Object.keys(this.userErrors).length === 0) {
                this.userFormError = res.message;
            }
        }
    },

    // Confirm Delete Modal
    confirmDeleteUserById(id) {
        const user = this.users.find(u => u.id === id);
        if (!user) return;
        if (user.id === this.currentUserId) {
            this.notify('Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.', 'error');
            return;
        }
        this.deleteTarget = {
            id: user.id,
            name: user.name,
            description: `Apakah Anda yakin ingin menghapus akun pengguna '${user.name}' (${user.email})? Tindakan ini tidak dapat dibatalkan.`
        };
        this.showDeleteModal = true;
    },

    // Execute Delete
    async executeDelete() {
        if (!this.deleteTarget.id) return;
        this.isProcessing = true;
        await $wire.deleteUser(this.deleteTarget.id);
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
                <span class="text-brand-primary">Manajemen Pengguna</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Manajemen Pengguna
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola daftar staf dan pengguna sistem, nomor kontak WhatsApp, serta peran hak akses.
            </p>
        </div>
    </div>

    <!-- Main Content Box -->
    <div class="space-y-4">
        
        <!-- Action & Filter Bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                    <input type="text" x-model="search" placeholder="Cari nama, email, atau no. WhatsApp..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Role Filter -->
                <div class="shrink-0">
                    <select x-model="roleFilter"
                        class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                        <option value="all">Semua Peran (Role)</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Create User Button -->
            <button type="button" @click="openCreateModal()"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
                <i class="ti ti-plus text-lg"></i>
                <span>Tambah Pengguna</span>
            </button>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">Pengguna</th>
                        <th class="py-3.5 px-6">No. WhatsApp / HP</th>
                        <th class="py-3.5 px-6">Peran (Role)</th>
                        <th class="py-3.5 px-6">Terdaftar Pada</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                    @forelse ($users as $user)
                        <tr x-show="(!search || $el.textContent.toLowerCase().includes(search.toLowerCase())) && (roleFilter === 'all' || '{{ strtolower($user['primary_role']) }}' === roleFilter.toLowerCase())"
                            class="hover:bg-neutral-50/50 transition">
                            
                            <!-- User Identity (Avatar + Name + Email) -->
                            <td class="py-4 px-6 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-full bg-brand-soft-cream text-brand-primary font-bold text-sm flex items-center justify-center shrink-0">
                                        {{ $user['initials'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-brand-espresso text-base block truncate">
                                                {{ $user['name'] }}
                                            </span>
                                            @if ($user['is_current_user'])
                                                <span class="text-xs font-semibold text-brand-warm-gray">
                                                    (Anda)
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-xs sm:text-sm text-brand-warm-gray block truncate">
                                            {{ $user['email'] }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- WhatsApp / Phone -->
                            <td class="py-4 px-6 align-middle">
                                @if ($user['formatted_phone'])
                                    <div class="inline-flex items-center gap-2 text-sm font-medium text-brand-espresso font-mono">
                                        <i class="ti ti-brand-whatsapp text-brand-warm-gray text-base"></i>
                                        <span>{{ $user['formatted_phone'] }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-brand-warm-gray italic">
                                        Belum diisi
                                    </span>
                                @endif
                            </td>

                            <!-- Role -->
                            <td class="py-4 px-6 align-middle">
                                @if (!empty($user['roles']))
                                    <span class="text-sm font-semibold text-brand-espresso capitalize">
                                        {{ implode(', ', $user['roles']) }}
                                    </span>
                                @else
                                    <span class="text-xs text-brand-warm-gray italic">Tanpa Role</span>
                                @endif
                            </td>

                            <!-- Created At -->
                            <td class="py-4 px-6 align-middle text-sm text-brand-warm-gray">
                                {{ $user['created_at_human'] }}
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 align-middle text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($user['whatsapp_url'])
                                        <a href="{{ $user['whatsapp_url'] }}" target="_blank"
                                            class="inline-flex items-center justify-center size-8 rounded-lg text-green-700 hover:bg-green-50 transition cursor-pointer border border-green-200"
                                            title="Chat WhatsApp">
                                            <i class="ti ti-brand-whatsapp text-lg"></i>
                                        </a>
                                    @endif

                                    <button type="button"
                                        @click="openEditModalById({{ $user['id'] }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                        <i class="ti ti-edit text-base"></i>
                                        <span>Edit</span>
                                    </button>

                                    @if (!$user['is_current_user'])
                                        <button type="button"
                                            @click="confirmDeleteUserById({{ $user['id'] }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200">
                                            <i class="ti ti-trash text-base"></i>
                                            <span>Hapus</span>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-brand-warm-gray bg-neutral-100 border border-neutral-200" title="Akun Anda yang sedang aktif">
                                            <i class="ti ti-user-check"></i> Aktif
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-brand-warm-gray">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <i class="ti ti-users text-3xl text-brand-warm-gray"></i>
                                    <p class="font-bold text-brand-espresso text-base">Belum ada pengguna terdaftar</p>
                                    <p class="text-xs text-brand-warm-gray">Klik tombol "Tambah Pengguna" untuk membuat akun baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    <!-- MODALS -->
    @include('components.admin.users.partials.modal-user')
    @include('components.admin.users.partials.modal-delete')

</div>
