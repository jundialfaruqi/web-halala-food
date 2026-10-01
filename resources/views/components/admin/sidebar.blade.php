<!-- Admin Sidebar Component -->
<div x-cloak x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-brand-espresso/60 backdrop-blur-xs lg:hidden">
</div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed lg:sticky top-0 left-0 z-50 h-screen w-72 bg-white border-r border-brand-border flex flex-col justify-between transition-transform duration-300 ease-in-out shrink-0 select-none shadow-lg lg:shadow-none">

    <!-- Top Section: Logo & Brand -->
    <div class="flex flex-col flex-1 min-h-0">
        <div class="h-20 flex items-center justify-between px-6 border-b border-brand-border">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center">
                <img src="{{ asset('assets/logo/logo.webp') }}" alt="Halala Food"
                    class="h-10 sm:h-11 w-auto object-contain">
            </a>

            <!-- Mobile Close Button -->
            <button type="button" @click="sidebarOpen = false"
                class="lg:hidden size-10 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer"
                aria-label="Tutup sidebar">
                <i class="ti ti-x text-xl"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="flex-1 overflow-y-auto py-6 space-y-6">
            <div>
                <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                    Menu Utama
                </p>
                <nav class="space-y-1">
                    <!-- 1. Dashboard -->
                    <a href="{{ route('admin.dashboard') }}"
                        class="w-full flex items-center justify-between px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                        <div class="flex items-center gap-3.5">
                            <i class="ti ti-layout-dashboard text-xl {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-brand-primary' }}"></i>
                            <span>Dashboard</span>
                        </div>
                    </a>

                    <!-- 2. Pengguna -->
                    <a href="{{ route('admin.users') }}"
                        class="w-full flex items-center justify-between px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.users*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                        <div class="flex items-center gap-3.5">
                            <i class="ti ti-users text-xl {{ request()->routeIs('admin.users*') ? 'text-white' : 'text-brand-primary' }}"></i>
                            <span>Pengguna</span>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ request()->routeIs('admin.users*') ? 'bg-white/20 text-white' : 'bg-neutral-100 text-brand-espresso' }}">
                            {{ \App\Models\User::count() }}
                        </span>
                    </a>

                    <!-- 3. Role & Permission -->
                    <a href="{{ route('admin.roles') }}"
                        class="w-full flex items-center justify-between px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.roles*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                        <div class="flex items-center gap-3.5">
                            <i class="ti ti-shield-lock text-xl {{ request()->routeIs('admin.roles*') ? 'text-white' : 'text-brand-primary' }}"></i>
                            <span>Role & Permission</span>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ request()->routeIs('admin.roles*') ? 'bg-white/20 text-white' : 'bg-neutral-100 text-brand-espresso' }}">
                            {{ class_exists('\Spatie\Permission\Models\Role') ? \Spatie\Permission\Models\Role::count() : '0' }}
                        </span>
                    </a>
                </nav>
            </div>

            <!-- Section: Pintasan Cepat -->
            <div class="pt-4 border-t border-brand-border/60">
                <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                    Akses Cepat
                </p>
                <nav class="space-y-1">
                    <a href="{{ route('home') }}" target="_blank"
                        class="w-full flex items-center justify-between px-6 py-3.5 text-base font-medium text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition">
                        <div class="flex items-center gap-3.5">
                            <i class="ti ti-world text-xl text-brand-warm-gray"></i>
                            <span>Lihat Toko</span>
                        </div>
                        <i class="ti ti-external-link text-sm text-brand-warm-gray"></i>
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Bottom Section: User Info & Logout -->
    <div class="p-4">
        <div class="flex items-center justify-between gap-3 p-3 rounded-2xl bg-brand-soft-cream/30">
            <div class="flex items-center gap-3 min-w-0">
                <div class="size-10 rounded-full bg-brand-primary text-white flex items-center justify-center font-bold text-sm shrink-0">
                    {{ auth()->user() ? auth()->user()->initials() : 'AD' }}
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-sm font-bold text-brand-espresso truncate">
                        {{ auth()->user()?->name ?? 'Administrator' }}
                    </span>
                    <span class="text-xs text-brand-warm-gray truncate">
                        {{ auth()->user()?->email ?? 'admin@halala-food.id' }}
                    </span>
                </div>
            </div>

            <button type="button"
                @click.prevent="$dispatch('open-logout-modal')"
                title="Keluar"
                class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-red-600 hover:bg-red-50 transition cursor-pointer">
                <i class="ti ti-logout text-lg"></i>
            </button>
        </div>
    </div>

</aside>
