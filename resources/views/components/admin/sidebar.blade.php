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
                    @can('dashboard-view')
                        <a href="{{ route('admin.dashboard') }}"
                            class="w-full flex items-center justify-between px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                            <div class="flex items-center gap-3.5">
                                <i class="ti ti-layout-dashboard text-xl {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Dashboard</span>
                            </div>
                        </a>
                    @endcan

                    <!-- 2. Pengguna -->
                    @can('user-manage')
                        <a href="{{ route('admin.users') }}"
                            class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.users*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                            <i class="ti ti-users text-xl {{ request()->routeIs('admin.users*') ? 'text-white' : 'text-brand-primary' }}"></i>
                            <span>Pengguna</span>
                        </a>
                    @endcan

                    <!-- 3. Role & Permission -->
                    @canany(['role-manage', 'permission-manage'])
                        <a href="{{ route('admin.roles') }}"
                            class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.roles*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                            <i class="ti ti-shield-lock text-xl {{ request()->routeIs('admin.roles*') ? 'text-white' : 'text-brand-primary' }}"></i>
                            <span>Role & Permission</span>
                        </a>
                    @endcanany
                </nav>
            </div>

            {{-- Section: Master Data --}}
            @canany(['satuan-view', 'bahan-baku-view'])
                <div class="pt-4 border-t border-brand-border/60">
                    <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                        Master
                    </p>
                    <nav class="space-y-1">
                        @can('satuan-view')
                            <a href="{{ route('admin.units') }}"
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.units*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-scale text-xl {{ request()->routeIs('admin.units*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Satuan</span>
                            </a>
                        @endcan

                        @can('bahan-baku-view')
                            <a href="{{ route('admin.raw-materials') }}"
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.raw-materials*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-leaf text-xl {{ request()->routeIs('admin.raw-materials*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Bahan Baku &amp; Resep</span>
                            </a>
                        @endcan
                    </nav>
                </div>
            @endcanany

            {{-- Section: Operasional Produksi & Produk --}}
            @canany(['produk-view', 'produksi-view'])
                <div class="pt-4 border-t border-brand-border/60">
                    <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                        Operasional
                    </p>
                    <nav class="space-y-1">
                        @can('produk-view')
                            <a href="{{ route('admin.products') }}"
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.products*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-cookie text-xl {{ request()->routeIs('admin.products*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Produk Jadi</span>
                            </a>
                        @endcan

                        @can('produksi-view')
                            <a href="{{ route('admin.production') }}"
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.production*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-flame text-xl {{ request()->routeIs('admin.production*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Produksi (Batch Masak)</span>
                            </a>
                        @endcan
                    </nav>
                </div>
            @endcanany

            {{-- Section: Distribusi --}}
            @canany(['toko-view', 'pengantaran-view'])
                <div class="pt-4 border-t border-brand-border/60">
                    <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                        Distribusi
                    </p>
                    <nav class="space-y-1">
                        @can('pengantaran-view')
                            <a href="{{ route('admin.deliveries') }}"
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.deliveries*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-truck-delivery text-xl {{ request()->routeIs('admin.deliveries*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Pengantaran (Surat Jalan)</span>
                            </a>
                        @endcan

                        @can('toko-view')
                            <a href="{{ route('admin.stores') }}"
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.stores*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-building-store text-xl {{ request()->routeIs('admin.stores*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Toko Mitra</span>
                            </a>
                        @endcan
                    </nav>
                </div>
            @endcanany

            {{-- Section: Keuangan & Piutang --}}
            @can('faktur-view')
                <div class="pt-4 border-t border-brand-border/60">
                    <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                        Keuangan
                    </p>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.invoices') }}"
                            class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.invoices*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                            <i class="ti ti-receipt-2 text-xl {{ request()->routeIs('admin.invoices*') ? 'text-white' : 'text-brand-primary' }}"></i>
                            <span>Faktur &amp; Piutang Toko</span>
                        </a>
                    </nav>
                </div>
            @endcan

            {{-- Section: Pengaturan Sistem & Usaha --}}
            @can('pengaturan-view')
                <div class="pt-4 border-t border-brand-border/60">
                    <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                        Pengaturan
                    </p>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.settings') }}"
                            class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.settings*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                            <i class="ti ti-settings text-xl {{ request()->routeIs('admin.settings*') ? 'text-white' : 'text-brand-primary' }}"></i>
                            <span>Pengaturan Usaha</span>
                        </a>
                    </nav>
                </div>
            @endcan

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

    <!-- Bottom Section: App Info & Version -->
    <div class="p-5 border-t border-brand-border/60">
        <div class="flex flex-col">
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-brand-espresso">Halala Food</span>
                <span class="text-xs font-medium text-brand-warm-gray font-mono">v1.0.0</span>
            </div>
            <span class="text-xs text-brand-warm-gray mt-0.5">Sistem Usaha & Distribusi</span>
        </div>
    </div>

</aside>
