<div class="space-y-6 sm:space-y-8">

    <!-- Header & Breadcrumb -->
    <div class="space-y-1">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <span class="text-brand-warm-gray">Admin</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Dashboard</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
            Dashboard Utama
        </h1>
        <p class="text-sm sm:text-base text-brand-warm-gray">
            Ringkasan performa usaha, pengantaran mitra, piutang, dan stok Halala Food.
        </p>
    </div>

    <!-- 1. Ringkasan Metrik Usaha (Tanpa Icon BG, Tanpa Badge, Warna Minimalis) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">

        {{-- Metrik 1: Total Omset (Finance/Manager) / Pengantaran Selesai (Kurir) --}}
        @can('laporan-keuangan-view')
            <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-warm-gray">Omset Bulan Ini</span>
                    <i class="ti ti-receipt text-2xl text-brand-warm-gray"></i>
                </div>
                <div class="text-2xl sm:text-3xl font-mono font-extrabold text-brand-espresso">
                    Rp {{ number_format($monthlyInvoiced, 0, ',', '.') }}
                </div>
                <p class="text-xs text-brand-warm-gray">Total tagihan faktur penjualan konsinyasi</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-warm-gray">Pengantaran Selesai</span>
                    <i class="ti ti-circle-check text-2xl text-brand-warm-gray"></i>
                </div>
                <div class="text-2xl sm:text-3xl font-mono font-extrabold text-brand-espresso">
                    {{ $completedDeliveriesCount }} Surat Jalan
                </div>
                <p class="text-xs text-brand-warm-gray">Pengantaran sukses diserahterimakan</p>
            </div>
        @endcan

        <!-- Metrik 2: Piutang Toko Berjalan -->
        <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-warm-gray">Piutang Berjalan</span>
                <i class="ti ti-building-store text-2xl text-brand-warm-gray"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-mono font-extrabold text-brand-espresso">
                Rp {{ number_format($totalReceivables, 0, ',', '.') }}
            </div>
            <p class="text-xs text-brand-warm-gray">Tagihan toko mitra belum lunas</p>
        </div>

        <!-- Metrik 3: Saldo Kas Usaha / Stok Siap Antar -->
        @can('buku-kas-view')
            <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-warm-gray">Saldo Kas Usaha</span>
                    <i class="ti ti-wallet text-2xl text-brand-warm-gray"></i>
                </div>
                <div class="text-2xl sm:text-3xl font-mono font-extrabold text-brand-espresso">
                    Rp {{ number_format($totalCashBalance, 0, ',', '.') }}
                </div>
                <p class="text-xs text-brand-warm-gray">Total saldo aktif rekening & buku kas</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-warm-gray">Stok Siap Antar</span>
                    <i class="ti ti-package text-2xl text-brand-warm-gray"></i>
                </div>
                <div class="text-2xl sm:text-3xl font-mono font-extrabold text-brand-espresso">
                    {{ number_format($readyProductsStock, 0, ',', '.') }}
                </div>
                <p class="text-xs text-brand-warm-gray">Kemasan produk jadi siap didistribusikan</p>
            </div>
        @endcan

        <!-- Metrik 4: Pengantaran Berjalan & Mitra Toko -->
        <div class="bg-white rounded-2xl border border-brand-border p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-brand-warm-gray">Pengantaran Berjalan</span>
                <i class="ti ti-truck-delivery text-2xl text-brand-warm-gray"></i>
            </div>
            <div class="text-2xl sm:text-3xl font-mono font-extrabold text-brand-espresso">
                {{ $pendingDeliveriesCount }} Surat Jalan
            </div>
            <p class="text-xs text-brand-warm-gray">{{ $activeStoresCount }} mitra toko aktif terdaftar</p>
        </div>

    </div>

    <!-- 2. Aktivitas Pengantaran & Distribusi Terkini -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">

        <!-- Kolom Kiri: Surat Jalan Terkini (7 cols) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-brand-border shadow-xs p-6 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                <div>
                    <h3 class="text-lg font-bold text-brand-espresso">Pengantaran Surat Jalan Terkini</h3>
                    <p class="text-xs text-brand-warm-gray mt-0.5">Status distribusi produk ke mitra toko.</p>
                </div>
                @can('pengantaran-view')
                    <a href="{{ route('admin.deliveries') }}" wire:navigate class="text-xs font-bold text-brand-primary hover:underline">
                        Lihat Semua →
                    </a>
                @endcan
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-brand-border text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                            <th class="pb-2.5 px-2">No. Surat Jalan</th>
                            <th class="pb-2.5 px-2">Mitra Toko</th>
                            <th class="pb-2.5 px-2">Kurir</th>
                            <th class="pb-2.5 px-2 text-right">Total Item</th>
                            <th class="pb-2.5 px-2 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border/60">
                        @forelse($recentDeliveries as $del)
                            <tr class="hover:bg-neutral-50/50 transition">
                                <td class="py-3 px-2 font-mono font-bold text-brand-espresso">
                                    {{ $del->delivery_number }}
                                </td>
                                <td class="py-3 px-2 font-medium text-brand-espresso">
                                    {{ $del->store?->name ?? '-' }}
                                </td>
                                <td class="py-3 px-2 text-brand-warm-gray">
                                    {{ $del->courier?->name ?? '-' }}
                                </td>
                                <td class="py-3 px-2 text-right font-mono font-semibold text-brand-espresso">
                                    {{ $del->total_items }}
                                </td>
                                <td class="py-3 px-2 text-right">
                                    <span class="text-xs font-semibold text-brand-espresso capitalize">
                                        {{ str_replace('_', ' ', $del->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-sm text-brand-warm-gray">
                                    Belum ada data surat jalan pengantaran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kolom Kanan: Tagihan Piutang & Inventaris (5 cols) -->
        <div class="lg:col-span-5 space-y-6">

            <!-- Card: Tagihan Piutang Berjalan -->
            <div class="bg-white rounded-2xl border border-brand-border shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                    <div>
                        <h3 class="text-lg font-bold text-brand-espresso">Tagihan Toko Berjalan</h3>
                        <p class="text-xs text-brand-warm-gray mt-0.5">Faktur konsinyasi belum lunas.</p>
                    </div>
                    @can('faktur-view')
                        <a href="{{ route('admin.invoices') }}" wire:navigate class="text-xs font-bold text-brand-primary hover:underline">
                            Semua Faktur →
                        </a>
                    @endcan
                </div>

                <div class="space-y-3">
                    @forelse($pendingInvoices as $inv)
                        <div class="flex items-center justify-between py-2 border-b border-brand-border/40 last:border-b-0 text-sm">
                            <div class="min-w-0 pr-3">
                                <p class="font-bold text-brand-espresso truncate">{{ $inv->store?->name ?? 'Toko' }}</p>
                                <p class="font-mono text-xs text-brand-warm-gray">{{ $inv->invoice_number }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-mono font-bold text-brand-espresso">
                                    Rp {{ number_format($inv->remaining_balance, 0, ',', '.') }}
                                </p>
                                <p class="text-xs text-brand-warm-gray capitalize">
                                    {{ str_replace('_', ' ', $inv->status) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-brand-warm-gray">
                            Semua tagihan toko dalam status lunas.
                        </p>
                    @endforelse
                </div>
            </div>

            <!-- Card: Status Inventaris Bahan Baku -->
            @can('bahan-baku-view')
                <div class="bg-white rounded-2xl border border-brand-border shadow-xs p-6 space-y-3">
                    <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                        <h3 class="text-lg font-bold text-brand-espresso">Peringatan Bahan Baku</h3>
                        <a href="{{ route('admin.raw-materials') }}" wire:navigate class="text-xs font-bold text-brand-primary hover:underline">
                            Bahan Baku Dapur →
                        </a>
                    </div>

                    @if($lowStockMaterials->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($lowStockMaterials as $mat)
                                <div class="flex items-center justify-between py-1.5 border-b border-brand-border/40 last:border-b-0 text-sm">
                                    <span class="font-medium text-brand-espresso">{{ $mat->name }}</span>
                                    <span class="font-mono text-xs font-bold text-brand-espresso">
                                        {{ number_format($mat->stock, 0, ',', '.') }} / min {{ number_format($mat->min_stock, 0, ',', '.') }} {{ $mat->display_unit }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="py-2 text-sm text-brand-warm-gray">
                            Semua persediaan bahan baku dapur dalam kondisi aman di atas batas minimum.
                        </p>
                    @endif
                </div>
            @endcan

        </div>

    </div>

    <!-- 3. Manajemen Tim & Pengguna Sistem -->
    @can('user-manage')
        <div class="bg-white rounded-2xl border border-brand-border shadow-xs p-6 sm:p-8 space-y-6">

        <!-- Table Header & Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-brand-border">
            <div>
                <h3 class="text-xl font-bold text-brand-espresso">
                    Daftar Pengguna
                </h3>
                <p class="text-sm text-brand-warm-gray mt-0.5">
                    Total Pengguna: {{ $totalUsers }} orang terdaftar ({{ $totalRoles }} peran konfigurasi).
                </p>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative min-w-55">
                    <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-base"></i>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama atau email..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl border border-brand-border bg-white text-sm text-brand-espresso placeholder:text-brand-warm-gray/70 focus:outline-none focus:border-brand-primary transition">
                </div>

                <!-- Role Filter -->
                <select wire:model.live="selectedRole"
                    class="px-3 py-2 rounded-xl border border-brand-border bg-white text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary transition cursor-pointer">
                    <option value="">Semua Peran</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->name }}">{{ $r->name }}</option>
                    @endforeach
                </select>

                @if ($search || $selectedRole)
                    <button type="button" wire:click="resetFilters"
                        class="px-3 py-2 text-brand-warm-gray hover:text-brand-primary transition cursor-pointer text-sm font-medium flex items-center gap-1.5"
                        title="Reset Filter">
                        <i class="ti ti-refresh text-base"></i>
                        <span>Reset</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Table Content (Tanpa Icon BG, Tanpa Badge, Tipografi Bersih) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-brand-border text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                        <th class="pb-3 px-3">Nama Pengguna</th>
                        <th class="pb-3 px-3">Email Akun</th>
                        <th class="pb-3 px-3">Peran (Role)</th>
                        <th class="pb-3 px-3 text-right">Tanggal Dibuat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border/60">
                    @forelse($users as $user)
                        <tr class="hover:bg-neutral-50/50 transition">
                            <td class="py-3 px-3 font-bold text-brand-espresso">
                                {{ $user->name }}
                            </td>
                            <td class="py-3 px-3 font-mono text-brand-warm-gray">
                                {{ $user->email }}
                            </td>
                            <td class="py-3 px-3 text-brand-espresso capitalize">
                                @forelse($user->roles as $role)
                                    <span>{{ $role->name }}</span>{{ ! $loop->last ? ', ' : '' }}
                                @empty
                                    <span class="text-brand-warm-gray italic">Tanpa Peran</span>
                                @endforelse
                            </td>
                            <td class="py-3 px-3 text-right text-brand-warm-gray whitespace-nowrap">
                                {{ $user->created_at ? $user->created_at->translatedFormat('d/m/Y') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-sm text-brand-warm-gray">
                                Tidak ada data pengguna yang sesuai.
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
    @endcan

</div>
