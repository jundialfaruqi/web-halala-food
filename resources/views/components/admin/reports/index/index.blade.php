<div class="space-y-6">

    <!-- Header Section (Screen Only) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 print:hidden">
        <div>
            <nav aria-label="Breadcrumb"
                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Keuangan</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Laporan Bisnis</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Laporan &amp; Rekapitulasi Bisnis
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Analisa laba kotor operasional, rekap tagihan konsinyasi mitra toko, dan valuasi aset stok.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-brand-espresso border border-brand-border bg-white hover:bg-neutral-100 transition shadow-xs cursor-pointer">
                <i class="ti ti-printer text-base"></i>
                <span>Cetak Laporan</span>
            </button>
        </div>
    </div>

    <!-- Print Only Header -->
    <div class="hidden print:block border-b-2 border-brand-espresso pb-4 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-xl font-bold text-brand-espresso uppercase tracking-wider">{{ $settings->company_name }}</h1>
                <p class="text-xs text-brand-warm-gray">{{ $settings->company_address }}</p>
                <p class="text-xs text-brand-warm-gray">Kontak: {{ $settings->company_phone }} | Email: {{ $settings->company_email }}</p>
            </div>
            <div class="text-right">
                <h2 class="text-lg font-bold text-brand-espresso">LAPORAN KEUANGAN &amp; OPERASIONAL</h2>
                <p class="text-xs text-brand-warm-gray">Periode: {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMM Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMM Y') }}</p>
                <p class="text-xs text-brand-warm-gray">Dicetak pada: {{ now()->isoFormat('D MMMM Y, HH:mm') }}</p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (Underline Navigation, Screen Only - Identik dengan Produksi & Role) -->
    <div class="border-b border-brand-border flex gap-6 overflow-x-auto print:hidden">
        <button type="button" wire:click="$set('tab', 'overview')"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 {{ $tab === 'overview' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
            Ringkasan Laba Rugi
        </button>
        <button type="button" wire:click="$set('tab', 'stores')"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 {{ $tab === 'stores' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
            Rekap Piutang Toko Mitra
        </button>
        <button type="button" wire:click="$set('tab', 'stock_waste')"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 {{ $tab === 'stock_waste' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
            Valuasi Stok &amp; Bahan
        </button>
    </div>

    <!-- Filter Bar (Identik dengan format seragam aplikasi) -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 print:hidden">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
            <!-- Period Dropdown Selector -->
            <div class="shrink-0">
                <select wire:model.live="period"
                    class="w-full sm:w-auto px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="this_month">Periode: Bulan Ini</option>
                    <option value="last_month">Periode: Bulan Lalu</option>
                    <option value="this_year">Periode: Tahun Ini</option>
                    <option value="custom">Periode: Kustom Tanggal</option>
                </select>
            </div>

            <!-- Custom Date Inputs when period === 'custom' -->
            @if ($period === 'custom')
                <div class="flex items-center gap-2">
                    <input type="date" wire:model.live="startDate"
                        class="px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                    <span class="text-xs text-brand-warm-gray font-medium">s/d</span>
                    <input type="date" wire:model.live="endDate"
                        class="px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                </div>
            @endif
        </div>

        <!-- Active Date Range Label -->
        <div class="text-xs sm:text-sm text-brand-warm-gray font-medium self-center shrink-0">
            Rentang: <span class="font-bold text-brand-espresso">{{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }}</span> s/d <span class="font-bold text-brand-espresso">{{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}</span>
        </div>
    </div>

    <!-- TAB 1: RINGKASAN LABA RUGI & ARUS KAS -->
    @if ($tab === 'overview' || request()->has('print_all'))
        <div class="space-y-6">
            <!-- Financial Metric Cards (Clean, No Icon BG, No Badge) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl border border-brand-border bg-white space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Omset Tagihan</span>
                    <p class="text-2xl font-extrabold text-brand-espresso">
                        Rp {{ number_format($totalInvoiced, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-brand-warm-gray">Nilai faktur penjualan konsinyasi</p>
                </div>

                <div class="p-5 rounded-2xl border border-brand-border bg-white space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">HPP Bahan Baku Terpakai</span>
                    <p class="text-2xl font-extrabold text-brand-espresso">
                        Rp {{ number_format($productionHpp, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-brand-warm-gray">Bahan yang terkonversi dalam masak</p>
                </div>

                <div class="p-5 rounded-2xl border border-brand-border bg-white space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Estimasi Laba Kotor</span>
                    <p class="text-2xl font-extrabold text-brand-espresso">
                        Rp {{ number_format($grossProfit, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-brand-warm-gray">Omset dikurangi HPP bahan baku</p>
                </div>

                <div class="p-5 rounded-2xl border border-brand-border bg-white space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Piutang Berjalan</span>
                    <p class="text-2xl font-extrabold text-brand-espresso">
                        Rp {{ number_format($totalReceivables, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-brand-warm-gray">Tagihan toko yang belum lunas</p>
                </div>
            </div>

            <!-- Cash Flow Comparison Table -->
            <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-4">
                <h2 class="text-base font-bold text-brand-espresso pb-2 border-b border-brand-border">
                    Rincian Arus Kas &amp; Margin Operasional
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-brand-espresso">
                        <tbody class="divide-y divide-brand-border">
                            <tr>
                                <td class="py-3 font-semibold text-brand-espresso">1. Total Nilai Penjualan Konsinyasi (Omset Faktur)</td>
                                <td class="py-3 text-right font-extrabold text-brand-espresso">Rp {{ number_format($totalInvoiced, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 pl-4 text-brand-warm-gray">&bull; Pembayaran Tagihan Diterima (Sudah Masuk)</td>
                                <td class="py-3 text-right text-brand-espresso">Rp {{ number_format($totalPaidOnInvoices, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 pl-4 text-brand-warm-gray">&bull; Piutang Belum Terbayar (Tertahan di Toko)</td>
                                <td class="py-3 text-right text-brand-espresso">Rp {{ number_format($totalReceivables, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-neutral-50/50">
                                <td class="py-3 font-semibold text-brand-espresso">2. Estimasi HPP Bahan Baku Masak (Dapur)</td>
                                <td class="py-3 text-right font-extrabold text-brand-espresso">Rp {{ number_format($productionHpp, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-brand-soft-cream/30 font-bold">
                                <td class="py-3.5 text-brand-espresso">Estimasi Laba Kotor Usaha (Omset - HPP)</td>
                                <td class="py-3.5 text-right text-brand-espresso text-base">Rp {{ number_format($grossProfit, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 font-semibold text-brand-espresso">3. Total Pengeluaran Belanja Pengadaan Bahan (Kas Keluar)</td>
                                <td class="py-3 text-right font-extrabold text-brand-espresso">Rp {{ number_format($purchasesCost, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="py-3 font-semibold text-brand-espresso">4. Total Kas Masuk Pelunasan Faktur (Kas Masuk Riil)</td>
                                <td class="py-3 text-right font-extrabold text-brand-espresso">Rp {{ number_format($cashIn, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 2: REKAPITULASI MITRA TOKO -->
    @if ($tab === 'stores' || request()->has('print_all'))
        <div class="bg-white border border-brand-border rounded-2xl overflow-hidden shadow-xs">
            <div class="p-6 border-b border-brand-border">
                <h2 class="text-base font-bold text-brand-espresso">Buku Rekapitulasi Mitra Toko (Statement of Account)</h2>
                <p class="text-xs text-brand-warm-gray mt-1">Daftar performa pengantaran, akumulasi tagihan, dan sisa piutang berjalan per mitra toko.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-brand-espresso">
                    <thead class="bg-brand-soft-cream/40 text-xs font-bold uppercase tracking-wider text-brand-warm-gray border-b border-brand-border">
                        <tr>
                            <th class="px-6 py-4">Nama Toko &amp; Kontak</th>
                            <th class="px-6 py-4 text-center">Pengantaran Selesai</th>
                            <th class="px-6 py-4 text-right">Total Ditagih</th>
                            <th class="px-6 py-4 text-right">Total Dibayar</th>
                            <th class="px-6 py-4 text-right">Sisa Piutang</th>
                            <th class="px-6 py-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border">
                        @forelse ($stores as $st)
                            <tr class="hover:bg-brand-soft-cream/20 transition">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-brand-espresso block">{{ $st['name'] }}</span>
                                    <span class="text-xs text-brand-warm-gray">{{ $st['owner'] }} ({{ $st['phone'] }})</span>
                                </td>
                                <td class="px-6 py-4 text-center font-semibold text-brand-espresso">
                                    {{ $st['deliveries_count'] }} kali
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-brand-espresso whitespace-nowrap">
                                    Rp {{ number_format($st['total_bill'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-brand-espresso whitespace-nowrap">
                                    Rp {{ number_format($st['total_paid'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-extrabold whitespace-nowrap {{ $st['balance'] > 0 ? 'text-brand-espresso' : 'text-brand-warm-gray' }}">
                                    Rp {{ number_format($st['balance'], 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap text-xs font-bold">
                                    @if ($st['balance'] <= 0 && $st['total_bill'] > 0)
                                        <span class="text-brand-espresso">Lunas</span>
                                    @elseif ($st['balance'] > 0)
                                        <span class="text-brand-espresso">Ada Piutang</span>
                                    @else
                                        <span class="text-brand-warm-gray">Belum Ada Transaksi</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-brand-warm-gray">
                                    Tidak ada mitra toko terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-brand-soft-cream/40 border-t border-brand-border text-xs font-bold uppercase text-brand-espresso">
                        <tr>
                            <th class="px-6 py-4">Total Keseluruhan</th>
                            <th class="px-6 py-4 text-center">{{ $stores->sum('deliveries_count') }} kali</th>
                            <th class="px-6 py-4 text-right">Rp {{ number_format($stores->sum('total_bill'), 0, ',', '.') }}</th>
                            <th class="px-6 py-4 text-right">Rp {{ number_format($stores->sum('total_paid'), 0, ',', '.') }}</th>
                            <th class="px-6 py-4 text-right">Rp {{ number_format($stores->sum('balance'), 0, ',', '.') }}</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 3: VALUASI STOK & ASET -->
    @if ($tab === 'stock_waste' || request()->has('print_all'))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Stok Bahan Baku -->
            <div class="bg-white border border-brand-border rounded-2xl overflow-hidden shadow-xs">
                <div class="p-6 border-b border-brand-border flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-brand-espresso">Stok Bahan Baku Dapur</h2>
                        <p class="text-xs text-brand-warm-gray mt-0.5">Valuasi stok bahan fisik saat ini.</p>
                    </div>
                    <span class="text-sm font-extrabold text-brand-espresso">
                        Rp {{ number_format($totalRawMaterialValue, 0, ',', '.') }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-brand-espresso">
                        <thead class="bg-brand-soft-cream/40 text-xs font-bold uppercase tracking-wider text-brand-warm-gray border-b border-brand-border">
                            <tr>
                                <th class="px-6 py-3">Nama Bahan</th>
                                <th class="px-6 py-3 text-right">Sisa Stok</th>
                                <th class="px-6 py-3 text-right">Valuasi (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border">
                            @foreach ($rawMaterials as $mat)
                                <tr>
                                    <td class="px-6 py-3 font-medium">{{ $mat->name }}</td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">{{ number_format($mat->stock, 2, ',', '.') }} {{ $mat->display_unit }}</td>
                                    <td class="px-6 py-3 text-right font-bold whitespace-nowrap">
                                        Rp {{ number_format((float) $mat->stock * (float) $mat->cost_per_unit, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stok Produk Jadi -->
            <div class="bg-white border border-brand-border rounded-2xl overflow-hidden shadow-xs">
                <div class="p-6 border-b border-brand-border flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-brand-espresso">Stok Produk Jadi di Gudang</h2>
                        <p class="text-xs text-brand-warm-gray mt-0.5">Valuasi produk siap kirim (harga setor).</p>
                    </div>
                    <span class="text-sm font-extrabold text-brand-espresso">
                        Rp {{ number_format($totalProductStockValue, 0, ',', '.') }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-brand-espresso">
                        <thead class="bg-brand-soft-cream/40 text-xs font-bold uppercase tracking-wider text-brand-warm-gray border-b border-brand-border">
                            <tr>
                                <th class="px-6 py-3">Nama Produk</th>
                                <th class="px-6 py-3 text-right">Stok Siap</th>
                                <th class="px-6 py-3 text-right">Valuasi (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border">
                            @foreach ($products as $prod)
                                <tr>
                                    <td class="px-6 py-3 font-medium">{{ $prod->name }}</td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">{{ $prod->stock_ready }} {{ $prod->unit }}</td>
                                    <td class="px-6 py-3 text-right font-bold whitespace-nowrap">
                                        Rp {{ number_format((int) $prod->stock_ready * (float) $prod->consignment_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>
