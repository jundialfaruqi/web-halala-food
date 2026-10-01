<div class="space-y-6 sm:space-y-8">

    <!-- 1. Metric Statistics Cards (Clean, Large Numbers, No Color Box) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">

        <!-- Stat 1: Total Pengguna -->
        <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm sm:text-base font-semibold text-brand-warm-gray">Total Pengguna</span>
                <i class="ti ti-users text-2xl text-brand-primary"></i>
            </div>
            <div class="text-3xl sm:text-4xl font-bold text-brand-espresso">
                {{ $totalUsers }}
            </div>
            <p class="text-xs sm:text-sm text-brand-warm-gray">Akun terdaftar dalam sistem</p>
        </div>

        <!-- Stat 2: Role Sistem -->
        <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm sm:text-base font-semibold text-brand-warm-gray">Total Peran (Role)</span>
                <i class="ti ti-shield-check text-2xl text-brand-primary"></i>
            </div>
            <div class="text-3xl sm:text-4xl font-bold text-brand-espresso">
                {{ $totalRoles }}
            </div>
            <p class="text-xs sm:text-sm text-brand-warm-gray">Peran otorisasi terkonfigurasi</p>
        </div>

        <!-- Stat 3: Hak Akses -->
        <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm sm:text-base font-semibold text-brand-warm-gray">Hak Akses (Permission)</span>
                <i class="ti ti-key text-2xl text-brand-primary"></i>
            </div>
            <div class="text-3xl sm:text-4xl font-bold text-brand-espresso">
                {{ $totalPermissions }}
            </div>
            <p class="text-xs sm:text-sm text-brand-warm-gray">Izin fitur dan hak akses aktif</p>
        </div>

        <!-- Stat 4: Varian Produk -->
        <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-sm sm:text-base font-semibold text-brand-warm-gray">Varian Produk</span>
                <i class="ti ti-cookie text-2xl text-brand-primary"></i>
            </div>
            <div class="text-3xl sm:text-4xl font-bold text-brand-espresso">
                2
            </div>
            <p class="text-xs sm:text-sm text-brand-warm-gray">Marie Wijen & Ting-Ting Susu</p>
        </div>

    </div>

    <!-- 3. Main Data Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">

        <!-- Left: Users Table (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-brand-border shadow-xs p-6 sm:p-8 space-y-6">

            <!-- Table Header & Controls -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-brand-border/60">
                <div>
                    <h3 class="text-xl font-bold text-brand-espresso">
                        Daftar Pengguna
                    </h3>
                    <p class="text-sm text-brand-warm-gray">
                        Kelola data nama, email, dan peran pengguna sistem.
                    </p>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative min-w-55">
                        <i
                            class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama atau email..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-brand-border bg-white text-sm text-brand-espresso placeholder:text-brand-warm-gray/70 focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20 transition">
                    </div>

                    <!-- Role Filter -->
                    <select wire:model.live="selectedRole"
                        class="select select-lg select-bordered rounded-xl border-brand-border bg-white text-brand-espresso font-medium focus:outline-none focus:border-brand-primary transition cursor-pointer">
                        <option value="">Semua Peran</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>

                    @if ($search || $selectedRole)
                        <button type="button" wire:click="resetFilters"
                            class="px-3 py-2.5 text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/60 rounded-xl transition cursor-pointer text-sm font-medium flex items-center gap-1.5"
                            title="Reset Filter">
                            <i class="ti ti-refresh text-base"></i>
                            <span>Reset</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-brand-border text-sm font-bold text-brand-espresso">
                            <th class="pb-3 px-3">Nama & Email</th>
                            <th class="pb-3 px-3">Peran (Role)</th>
                            <th class="pb-3 px-3">Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border/60 text-sm text-brand-text-primary">
                        @forelse($users as $user)
                            <tr class="hover:bg-brand-soft-cream-light/40 transition">
                                <!-- User Info -->
                                <td class="py-4 px-3">
                                    <div class="flex items-center gap-3.5">
                                        <div
                                            class="size-10 rounded-full bg-brand-primary text-white font-bold flex items-center justify-center text-sm shrink-0">
                                            {{ $user->initials() }}
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span
                                                class="font-bold text-base text-brand-espresso truncate">{{ $user->name }}</span>
                                            <span
                                                class="text-sm text-brand-warm-gray truncate">{{ $user->email }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Roles (Clean, single consistent styling) -->
                                <td class="py-4 px-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($user->roles as $role)
                                            <span
                                                class="px-3 py-1 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso bg-brand-soft-cream/60 border border-brand-border">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-brand-warm-gray italic">Tanpa Peran</span>
                                        @endforelse
                                    </div>
                                </td>

                                <!-- Joined Date -->
                                <td class="py-4 px-3 text-sm text-brand-warm-gray whitespace-nowrap">
                                    {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-12 text-center text-base text-brand-warm-gray">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="ti ti-user-x text-4xl text-brand-warm-gray/40"></i>
                                        <span>Tidak ada data pengguna yang sesuai.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if ($users->hasPages())
                <div class="pt-4 border-t border-brand-border">
                    {{ $users->links() }}
                </div>
            @endif

        </div>

        <!-- Right: Role Distribution & System Information (4 cols) -->
        <div class="lg:col-span-4 space-y-6">

            <!-- Card: Distribusi Peran -->
            <div class="bg-white rounded-2xl border border-brand-border shadow-xs p-6 space-y-5">
                <div class="border-b border-brand-border/60 pb-3">
                    <h3 class="font-bold text-lg text-brand-espresso">
                        Jumlah Pengguna per Peran
                    </h3>
                    <p class="text-sm text-brand-warm-gray">Total {{ $roles->count() }} peran terdaftar</p>
                </div>

                <div class="space-y-4">
                    @foreach ($roles as $role)
                        @php
                            $pct = $totalUsers > 0 ? round(($role->users_count / $totalUsers) * 100) : 0;
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-sm sm:text-base">
                                <span class="font-semibold text-brand-espresso">{{ $role->name }}</span>
                                <span class="font-bold text-brand-espresso">{{ $role->users_count }} orang</span>
                            </div>
                            <div class="w-full h-2.5 bg-neutral-100 rounded-full overflow-hidden">
                                <div class="h-full bg-brand-primary rounded-full transition-all duration-300"
                                    style="width: {{ $pct }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Card: Informasi Sistem -->
            <div class="bg-white rounded-2xl border border-brand-border shadow-xs p-6 space-y-4">
                <div class="border-b border-brand-border/60 pb-3">
                    <h3 class="font-bold text-lg text-brand-espresso">
                        Informasi Sistem
                    </h3>
                </div>

                <div class="space-y-3.5 text-sm sm:text-base">
                    <div class="flex items-center justify-between py-1 border-b border-brand-border/40">
                        <span class="text-brand-warm-gray">Otorisasi</span>
                        <span class="font-bold text-brand-espresso">Spatie Permission</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-brand-border/40">
                        <span class="text-brand-warm-gray">Database</span>
                        <span class="font-bold text-brand-espresso">MySQL</span>
                    </div>

                    <div class="flex items-center justify-between py-1">
                        <span class="text-brand-warm-gray">Keamanan Sandi</span>
                        <span class="font-bold text-brand-espresso">Terenkripsi (Bcrypt)</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
