<div class="space-y-6">

    <!-- Header Section (Hidden on Print) -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <nav aria-label="Breadcrumb"
                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Keuangan</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Laporan Keuangan Formal</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Laporan Keuangan Formal
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Laporan laba rugi dan neraca posisi keuangan usaha berbasis standar akuntansi.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.accounting.journals') }}" wire:navigate
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                <span>Lihat Jurnal Umum</span>
            </a>
            <button type="button" onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                <i class="ti ti-printer text-base"></i>
                <span>Cetak Lembar</span>
            </button>
        </div>
    </div>

    <!-- Segmented Tab Switcher & Period Filter (Hidden on Print) -->
    <div class="no-print flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <!-- Tab Navigasi -->
        <div class="inline-flex p-1 bg-neutral-100 rounded-xl border border-brand-border/60 self-start">
            <button type="button" wire:click="setTab('income_statement')"
                class="px-4 py-2 rounded-lg text-sm font-bold transition cursor-pointer {{ $activeTab === 'income_statement' ? 'bg-white text-brand-espresso shadow-xs' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
                Laporan Laba Rugi
            </button>
            <button type="button" wire:click="setTab('balance_sheet')"
                class="px-4 py-2 rounded-lg text-sm font-bold transition cursor-pointer {{ $activeTab === 'balance_sheet' ? 'bg-white text-brand-espresso shadow-xs' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
                Neraca Keuangan
            </button>
        </div>

        <!-- Filter Periode -->
        <div class="inline-flex p-1 bg-neutral-100 rounded-xl border border-brand-border/60 self-start sm:self-auto flex-wrap">
            <button type="button" wire:click="setPeriod('this_month')"
                class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer {{ $periodPreset === 'this_month' ? 'bg-white text-brand-espresso shadow-xs font-bold' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
                Bulan Ini
            </button>
            <button type="button" wire:click="setPeriod('last_month')"
                class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer {{ $periodPreset === 'last_month' ? 'bg-white text-brand-espresso shadow-xs font-bold' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
                Bulan Lalu
            </button>
            <button type="button" wire:click="setPeriod('this_year')"
                class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer {{ $periodPreset === 'this_year' ? 'bg-white text-brand-espresso shadow-xs font-bold' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
                Tahun Ini
            </button>
            <button type="button" wire:click="setPeriod('all')"
                class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer {{ $periodPreset === 'all' ? 'bg-white text-brand-espresso shadow-xs font-bold' : 'text-brand-warm-gray hover:text-brand-espresso' }}">
                Semua
            </button>
        </div>
    </div>

    <!-- Printable Report Container -->
    <div id="printable-financial-report">
        @if($activeTab === 'income_statement')
            <!-- 1. LAPORAN LABA RUGI -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-brand-border print:border-none print:p-0 print:shadow-none space-y-6">
                <!-- Header Dokumen Laporan -->
                <div class="text-center pb-5 border-b-2 border-brand-espresso">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">HALALA FOOD</h2>
                    <p class="text-xs uppercase tracking-widest text-brand-warm-gray font-bold mt-0.5">Usaha Makanan &amp; Oleh-Oleh Keluarga</p>
                    <p class="text-lg sm:text-xl font-bold text-brand-espresso mt-2">LAPORAN LABA RUGI</p>
                    <p class="text-sm font-medium text-brand-warm-gray mt-0.5">
                        Periode: {{ $periodPreset === 'this_month' ? 'Bulan Ini (' . Carbon\Carbon::now()->translatedFormat('F Y') . ')' : ($periodPreset === 'last_month' ? 'Bulan Lalu (' . Carbon\Carbon::now()->subMonth()->translatedFormat('F Y') . ')' : ($periodPreset === 'this_year' ? 'Tahun ' . Carbon\Carbon::now()->format('Y') : 'Seluruh Periode Berjalan')) }}
                    </p>
                </div>

                <div class="space-y-6 text-sm">
                    <!-- A. PENDAPATAN USAHA -->
                    <div>
                        <h3 class="font-bold text-brand-espresso uppercase tracking-wider text-xs bg-neutral-100 p-2.5 rounded-lg mb-2">
                            1. PENDAPATAN USAHA
                        </h3>
                        <div class="space-y-1.5 px-3">
                            @foreach($revenueAccounts as $rev)
                                <div class="flex justify-between py-1.5 border-b border-brand-border/40">
                                    <span class="text-brand-espresso font-medium">{{ $rev->name }}</span>
                                    <span class="font-mono font-bold text-brand-espresso">Rp {{ number_format($rev->balance, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between pt-2.5 font-bold text-brand-espresso border-t border-brand-border">
                                <span>TOTAL PENDAPATAN USAHA:</span>
                                <span class="font-mono text-base">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- B. BEBAN POKOK PENJUALAN (HPP) -->
                    <div>
                        <h3 class="font-bold text-brand-espresso uppercase tracking-wider text-xs bg-neutral-100 p-2.5 rounded-lg mb-2">
                            2. BEBAN POKOK PENJUALAN (HPP)
                        </h3>
                        <div class="space-y-1.5 px-3">
                            @foreach($cogsAccounts as $cogs)
                                <div class="flex justify-between py-1.5 border-b border-brand-border/40">
                                    <span class="text-brand-espresso font-medium">{{ $cogs->name }}</span>
                                    <span class="font-mono font-bold text-brand-espresso">Rp {{ number_format($cogs->balance, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between pt-2.5 font-bold text-brand-espresso border-t border-brand-border">
                                <span>TOTAL BEBAN POKOK PENJUALAN (HPP):</span>
                                <span class="font-mono text-base">(Rp {{ number_format($totalCogs, 0, ',', '.') }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- C. LABA KOTOR -->
                    <div class="p-4 bg-neutral-50 rounded-xl border border-brand-border flex justify-between items-center font-bold text-base">
                        <span class="text-brand-espresso">LABA KOTOR (PENDAPATAN - HPP):</span>
                        <span class="font-mono text-lg text-brand-espresso">Rp {{ number_format($grossProfit, 0, ',', '.') }}</span>
                    </div>

                    <!-- D. BEBAN OPERASIONAL -->
                    <div>
                        <h3 class="font-bold text-brand-espresso uppercase tracking-wider text-xs bg-neutral-100 p-2.5 rounded-lg mb-2">
                            3. BEBAN OPERASIONAL USAHA
                        </h3>
                        <div class="space-y-1.5 px-3">
                            @foreach($expenseAccounts as $exp)
                                <div class="flex justify-between py-1.5 border-b border-brand-border/40">
                                    <span class="text-brand-espresso font-medium">{{ $exp->name }}</span>
                                    <span class="font-mono font-bold text-brand-espresso">Rp {{ number_format($exp->balance, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between pt-2.5 font-bold text-brand-espresso border-t border-brand-border">
                                <span>TOTAL BEBAN OPERASIONAL:</span>
                                <span class="font-mono text-base">(Rp {{ number_format($totalExpense, 0, ',', '.') }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- E. LABA BERSIH -->
                    <div class="p-5 bg-brand-espresso text-white rounded-xl flex justify-between items-center font-bold text-lg print:border print:border-black print:text-black print:bg-white">
                        <span>LABA BERSIH USAHA (NET PROFIT):</span>
                        <span class="font-mono text-xl">
                            Rp {{ number_format($netIncome, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- Bagian Tanda Tangan Cetak -->
                <div class="print-only pt-8 mt-8 border-t border-brand-border">
                    <div class="grid grid-cols-2 text-center text-xs font-semibold">
                        <div>
                            <p class="text-brand-warm-gray">Dibuat Oleh,</p>
                            <div class="h-16"></div>
                            <p class="font-bold text-brand-espresso border-t border-brand-border inline-block px-8 pt-1">Bagian Pembukuan</p>
                        </div>
                        <div>
                            <p class="text-brand-warm-gray">Disetujui Oleh,</p>
                            <div class="h-16"></div>
                            <p class="font-bold text-brand-espresso border-t border-brand-border inline-block px-8 pt-1">Pemilik Usaha Halala Food</p>
                        </div>
                    </div>
                    <div class="text-right text-[10px] text-brand-warm-gray mt-6 font-mono">
                        Dicetak pada: {{ Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}
                    </div>
                </div>
            </div>

        @else
            <!-- 2. LAPORAN NERACA KEUANGAN -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-brand-border print:border-none print:p-0 print:shadow-none space-y-6">
                <!-- Header Dokumen Laporan -->
                <div class="text-center pb-5 border-b-2 border-brand-espresso">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">HALALA FOOD</h2>
                    <p class="text-xs uppercase tracking-widest text-brand-warm-gray font-bold mt-0.5">Usaha Makanan &amp; Oleh-Oleh Keluarga</p>
                    <p class="text-lg sm:text-xl font-bold text-brand-espresso mt-2">NERACA KEUANGAN (BALANCE SHEET)</p>
                    <p class="text-sm font-medium text-brand-warm-gray mt-0.5">
                        Posisi per: {{ Carbon\Carbon::now()->translatedFormat('d F Y') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
                    <!-- KOLOM KIRI: ASET / AKTIVA -->
                    <div class="space-y-4">
                        <h3 class="font-bold text-brand-espresso uppercase tracking-wider text-xs bg-neutral-100 p-2.5 rounded-lg">
                            ASET (HARTA USAHA)
                        </h3>
                        <div class="space-y-1.5 px-2">
                            @foreach($assetAccounts as $asset)
                                <div class="flex justify-between py-1.5 border-b border-brand-border/40">
                                    <span class="text-brand-espresso font-medium">{{ $asset->name }}</span>
                                    <span class="font-mono font-bold text-brand-espresso">Rp {{ number_format($asset->balance, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="p-4 bg-neutral-50 rounded-xl border border-brand-border flex justify-between items-center font-bold text-base">
                            <span class="text-brand-espresso">TOTAL ASET:</span>
                            <span class="font-mono text-lg text-brand-espresso">Rp {{ number_format($totalAssets, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: KEWAJIBAN & EKUITAS / PASIVA -->
                    <div class="space-y-6">
                        <!-- Kewajiban -->
                        <div class="space-y-3">
                            <h3 class="font-bold text-brand-espresso uppercase tracking-wider text-xs bg-neutral-100 p-2.5 rounded-lg">
                                KEWAJIBAN (HUTANG)
                            </h3>
                            <div class="space-y-1.5 px-2">
                                @forelse($liabilityAccounts as $liab)
                                    <div class="flex justify-between py-1.5 border-b border-brand-border/40">
                                        <span class="text-brand-espresso font-medium">{{ $liab->name }}</span>
                                        <span class="font-mono font-bold text-brand-espresso">Rp {{ number_format($liab->balance, 0, ',', '.') }}</span>
                                    </div>
                                @empty
                                    <p class="text-xs text-brand-warm-gray py-1 italic">Tidak ada kewajiban / hutang usaha.</p>
                                @endforelse
                            </div>
                            <div class="flex justify-between px-2 font-bold text-brand-espresso border-t border-brand-border/60 pt-2 text-xs">
                                <span>Total Kewajiban:</span>
                                <span class="font-mono text-sm">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Ekuitas / Modal -->
                        <div class="space-y-3">
                            <h3 class="font-bold text-brand-espresso uppercase tracking-wider text-xs bg-neutral-100 p-2.5 rounded-lg">
                                EKUITAS (MODAL USAHA)
                            </h3>
                            <div class="space-y-1.5 px-2">
                                @foreach($equityAccounts as $eq)
                                    <div class="flex justify-between py-1.5 border-b border-brand-border/40">
                                        <span class="text-brand-espresso font-medium">{{ $eq->name }}</span>
                                        <span class="font-mono font-bold text-brand-espresso">
                                            {{ $eq->normal_balance === 'debit' ? '(Rp ' . number_format($eq->balance, 0, ',', '.') . ')' : 'Rp ' . number_format($eq->balance, 0, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                                <div class="flex justify-between py-1.5 border-b border-brand-border/40">
                                    <span class="text-brand-espresso font-medium">Laba Bersih Periode Berjalan</span>
                                    <span class="font-mono font-bold text-brand-espresso">Rp {{ number_format($cumulativeNetIncome, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between px-2 font-bold text-brand-espresso border-t border-brand-border/60 pt-2 text-xs">
                                <span>Total Ekuitas / Modal:</span>
                                <span class="font-mono text-sm">Rp {{ number_format($totalEquity, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="p-4 bg-neutral-50 rounded-xl border border-brand-border flex justify-between items-center font-bold text-base">
                            <span class="text-brand-espresso">TOTAL KEWAJIBAN &amp; EKUITAS:</span>
                            <span class="font-mono text-lg text-brand-espresso">Rp {{ number_format($totalLiabilitiesAndEquity, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bagian Tanda Tangan Cetak -->
                <div class="print-only pt-8 mt-8 border-t border-brand-border">
                    <div class="grid grid-cols-2 text-center text-xs font-semibold">
                        <div>
                            <p class="text-brand-warm-gray">Dibuat Oleh,</p>
                            <div class="h-16"></div>
                            <p class="font-bold text-brand-espresso border-t border-brand-border inline-block px-8 pt-1">Bagian Pembukuan</p>
                        </div>
                        <div>
                            <p class="text-brand-warm-gray">Disetujui Oleh,</p>
                            <div class="h-16"></div>
                            <p class="font-bold text-brand-espresso border-t border-brand-border inline-block px-8 pt-1">Pemilik Usaha Halala Food</p>
                        </div>
                    </div>
                    <div class="text-right text-[10px] text-brand-warm-gray mt-6 font-mono">
                        Dicetak pada: {{ Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Print Specific CSS -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
        }
        @media screen {
            .print-only {
                display: none;
            }
        }
    </style>

</div>
