<!-- Admin Sidebar Component -->
<div x-cloak x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-brand-espresso/60 backdrop-blur-xs lg:hidden print:hidden">
</div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed lg:sticky top-0 left-0 z-50 h-screen w-72 bg-white border-r border-brand-border flex flex-col justify-between transition-transform duration-300 ease-in-out shrink-0 select-none shadow-lg lg:shadow-none print:hidden">

    <!-- Top Section: Logo & Brand -->
    <div class="flex flex-col flex-1 min-h-0">
        <div class="h-20 flex items-center justify-between px-6 border-b border-brand-border">
            <a href="{{ route('admin.dashboard') }}" wire:navigate @click="window.__adminSidebarScroll = 0; sessionStorage.setItem('admin_sidebar_scroll', '0')" class="flex items-center">
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
        <div id="admin-sidebar-scroll"
            style="scroll-behavior: auto !important;"
            x-data="{
                init() {
                    this.restore();
                    this._navHandler = () => this.restore();
                    window.addEventListener('livewire:navigated', this._navHandler);
                },
                destroy() {
                    if (this._navHandler) {
                        window.removeEventListener('livewire:navigated', this._navHandler);
                    }
                },
                save() {
                    window.__adminSidebarScroll = this.$el.scrollTop;
                    sessionStorage.setItem('admin_sidebar_scroll', String(this.$el.scrollTop));
                },
                restore() {
                    const el = this.$el;
                    const saved = window.__adminSidebarScroll ?? sessionStorage.getItem('admin_sidebar_scroll');
                    if (saved !== null && saved !== undefined && saved !== '') {
                        el.scrollTop = parseInt(saved, 10);
                    } else {
                        const active = el.querySelector('[data-sidebar-active=\"true\"]');
                        if (active) {
                            el.scrollTop = Math.max(0, active.offsetTop - 120);
                        }
                    }
                }
            }"
            @scroll="save()"
            @click="if ($event.target.closest('a')) { save(); if (window.innerWidth < 1024) sidebarOpen = false; }"
            class="flex-1 overflow-y-auto py-6 space-y-6">
            <div>
                <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                    Menu Utama
                </p>
                <nav class="space-y-1">
                    <!-- 1. Dashboard -->
                    @can('dashboard-view')
                        <a href="{{ route('admin.dashboard') }}" wire:navigate
                            @if(request()->routeIs('admin.dashboard')) data-sidebar-active="true" @endif
                            class="w-full flex items-center justify-between px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                            <div class="flex items-center gap-3.5">
                                <i class="ti ti-layout-dashboard text-xl {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Dashboard</span>
                            </div>
                        </a>
                    @endcan

                    <!-- 2. Pengguna -->
                    @can('user-manage')
                        <a href="{{ route('admin.users') }}" wire:navigate
                            @if(request()->routeIs('admin.users*')) data-sidebar-active="true" @endif
                            class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.users*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                            <i class="ti ti-users text-xl {{ request()->routeIs('admin.users*') ? 'text-white' : 'text-brand-primary' }}"></i>
                            <span>Pengguna</span>
                        </a>
                    @endcan

                    <!-- 3. Role & Permission -->
                    @canany(['role-manage', 'permission-manage'])
                        <a href="{{ route('admin.roles') }}" wire:navigate
                            @if(request()->routeIs('admin.roles*')) data-sidebar-active="true" @endif
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
                            <a href="{{ route('admin.units') }}" wire:navigate
                                @if(request()->routeIs('admin.units*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.units*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-scale text-xl {{ request()->routeIs('admin.units*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Satuan</span>
                            </a>
                        @endcan

                        @can('bahan-baku-view')
                            <a href="{{ route('admin.raw-materials') }}" wire:navigate
                                @if(request()->routeIs('admin.raw-materials*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.raw-materials*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-leaf text-xl {{ request()->routeIs('admin.raw-materials*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Bahan Baku &amp; Resep</span>
                            </a>
                        @endcan
                    </nav>
                </div>
            @endcanany

            {{-- Section: Operasional Produksi & Produk --}}
            @canany(['produk-view', 'produksi-view', 'pembelian-view'])
                <div class="pt-4 border-t border-brand-border/60">
                    <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                        Operasional
                    </p>
                    <nav class="space-y-1">
                        @can('produk-view')
                            <a href="{{ route('admin.products') }}" wire:navigate
                                @if(request()->routeIs('admin.products*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.products*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-cookie text-xl {{ request()->routeIs('admin.products*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Produk Jadi</span>
                            </a>
                        @endcan

                        @can('produksi-view')
                            <a href="{{ route('admin.production') }}" wire:navigate
                                @if(request()->routeIs('admin.production*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.production*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-flame text-xl {{ request()->routeIs('admin.production*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Produksi (Batch Masak)</span>
                            </a>
                        @endcan

                        @can('pembelian-view')
                            <a href="{{ route('admin.purchases') }}" wire:navigate
                                @if(request()->routeIs('admin.purchases*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.purchases*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-shopping-cart text-xl {{ request()->routeIs('admin.purchases*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Pembelian Bahan</span>
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
                            <a href="{{ route('admin.deliveries') }}" wire:navigate
                                @if(request()->routeIs('admin.deliveries*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.deliveries*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-truck-delivery text-xl {{ request()->routeIs('admin.deliveries*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Pengantaran (Surat Jalan)</span>
                            </a>
                        @endcan

                        @can('toko-view')
                            <a href="{{ route('admin.stores') }}" wire:navigate
                                @if(request()->routeIs('admin.stores*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.stores*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-building-store text-xl {{ request()->routeIs('admin.stores*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Toko Mitra</span>
                            </a>
                        @endcan
                    </nav>
                </div>
            @endcanany

            {{-- Section: Keuangan & Akuntansi --}}
            @canany(['buku-kas-view', 'aset-view', 'faktur-view', 'jurnal-view', 'laporan-keuangan-view', 'laporan-view'])
                <div class="pt-4 border-t border-brand-border/60">
                    <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                        Keuangan
                    </p>
                    <nav class="space-y-1">
                        @can('buku-kas-view')
                            <a href="{{ route('admin.cash-book') }}" wire:navigate
                                @if(request()->routeIs('admin.cash-book*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.cash-book*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-wallet text-xl {{ request()->routeIs('admin.cash-book*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Buku Kas</span>
                            </a>
                        @endcan

                        @can('aset-view')
                            <a href="{{ route('admin.fixed-assets') }}" wire:navigate
                                @if(request()->routeIs('admin.fixed-assets*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.fixed-assets*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-building-factory-2 text-xl {{ request()->routeIs('admin.fixed-assets*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Aset Tetap Usaha</span>
                            </a>
                        @endcan

                        @can('faktur-view')
                            <a href="{{ route('admin.invoices') }}" wire:navigate
                                @if(request()->routeIs('admin.invoices*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.invoices*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-receipt-2 text-xl {{ request()->routeIs('admin.invoices*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Faktur &amp; Piutang Toko</span>
                            </a>
                        @endcan

                        @can('jurnal-view')
                            <a href="{{ route('admin.accounting.journals') }}" wire:navigate
                                @if(request()->routeIs('admin.accounting.journals*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.accounting.journals*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-book-2 text-xl {{ request()->routeIs('admin.accounting.journals*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Jurnal Akuntansi</span>
                            </a>
                        @endcan

                        @can('jurnal-view')
                            <a href="{{ route('admin.accounting.ledger') }}" wire:navigate
                                @if(request()->routeIs('admin.accounting.ledger*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.accounting.ledger*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-notebook text-xl {{ request()->routeIs('admin.accounting.ledger*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Buku Besar</span>
                            </a>
                        @endcan

                        @can('laporan-keuangan-view')
                            <a href="{{ route('admin.accounting.financial-statements') }}" wire:navigate
                                @if(request()->routeIs('admin.accounting.financial-statements*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.accounting.financial-statements*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-scale text-xl {{ request()->routeIs('admin.accounting.financial-statements*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Laba Rugi &amp; Neraca</span>
                            </a>
                        @endcan

                        @can('laporan-view')
                            <a href="{{ route('admin.reports') }}" wire:navigate
                                @if(request()->routeIs('admin.reports*')) data-sidebar-active="true" @endif
                                class="w-full flex items-center gap-3.5 px-6 py-3.5 text-base font-semibold transition {{ request()->routeIs('admin.reports*') ? 'bg-brand-primary text-white' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60' }}">
                                <i class="ti ti-report-analytics text-xl {{ request()->routeIs('admin.reports*') ? 'text-white' : 'text-brand-primary' }}"></i>
                                <span>Laporan Bisnis</span>
                            </a>
                        @endcan
                    </nav>
                </div>
            @endcanany

            {{-- Section: Pengaturan Sistem & Usaha --}}
            @can('pengaturan-view')
                <div class="pt-4 border-t border-brand-border/60">
                    <p class="px-6 text-xs font-bold text-brand-warm-gray uppercase tracking-wider mb-2">
                        Pengaturan
                    </p>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.settings') }}" wire:navigate
                            @if(request()->routeIs('admin.settings*')) data-sidebar-active="true" @endif
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

        <!-- Inline Restoration to eliminate any scroll jump on first render -->
        <script>
            (function() {
                var el = document.getElementById('admin-sidebar-scroll');
                if (el) {
                    var saved = window.__adminSidebarScroll ?? sessionStorage.getItem('admin_sidebar_scroll');
                    if (saved !== null && saved !== undefined && saved !== '') {
                        el.scrollTop = parseInt(saved, 10);
                    } else {
                        var active = el.querySelector('[data-sidebar-active="true"]');
                        if (active) {
                            el.scrollTop = Math.max(0, active.offsetTop - 120);
                        }
                    }
                }

                if (!window.__sidebarNavListenerAttached) {
                    window.__sidebarNavListenerAttached = true;
                    document.addEventListener('livewire:navigating', function() {
                        var scrollContainer = document.getElementById('admin-sidebar-scroll');
                        if (scrollContainer) {
                            window.__adminSidebarScroll = scrollContainer.scrollTop;
                            sessionStorage.setItem('admin_sidebar_scroll', String(scrollContainer.scrollTop));
                        }
                    });
                }
            })();
        </script>
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
