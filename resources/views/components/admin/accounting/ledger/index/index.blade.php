<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <nav aria-label="Breadcrumb"
                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Keuangan</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Buku Besar</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Buku Besar
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Rincian mutasi debet, kredit, dan akumulasi saldo berjalan per pos akun perkiraan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.accounting.journals') }}" wire:navigate
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                <span>Kembali ke Jurnal</span>
            </a>
            <a href="{{ route('admin.accounting.financial-statements') }}" wire:navigate
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                <span>Laporan Keuangan</span>
            </a>
        </div>
    </div>

    <!-- Alert Flash -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl border border-brand-border bg-white text-brand-espresso text-sm font-medium flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button type="button" @click="$el.parentElement.remove()" class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                <i class="ti ti-x"></i>
            </button>
        </div>
    @endif

    @if($accounts->isEmpty())
        <div class="p-12 text-center bg-white rounded-xl border border-brand-border space-y-4">
            <h3 class="text-lg font-bold text-brand-espresso">Bagan Akun Belum Tersedia</h3>
            <p class="text-sm text-brand-warm-gray max-w-md mx-auto">
                Bagan akun perkiraan (COA) belum dimuat dalam sistem. Silakan klik tombol di bawah untuk menginisialisasi akun standar.
            </p>
            <button type="button" wire:click="initDefaultAccounts"
                class="px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                Inisialisasi Akun Standar
            </button>
        </div>
    @else
        <!-- Filter Bar: Pilih Akun & Periode -->
        <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            <!-- Account Selector -->
            <div class="flex-1 max-w-xl">
                <select wire:model.live="selectedAccountId"
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-bold text-brand-espresso focus:outline-none focus:border-brand-primary">
                    @php
                        $grouped = $accounts->groupBy('type');
                        $typeLabels = [
                            'asset' => '1. Harta & Kas Usaha (Aset)',
                            'liability' => '2. Kewajiban & Hutang (Liabilitas)',
                            'equity' => '3. Modal Usaha (Ekuitas)',
                            'revenue' => '4. Pendapatan Penjualan (Omset)',
                            'cogs' => '5. Harga Pokok Penjualan (HPP)',
                            'expense' => '6. Beban Operasional Usaha',
                        ];
                    @endphp
                    @foreach ($typeLabels as $typeKey => $groupTitle)
                        @if (isset($grouped[$typeKey]) && $grouped[$typeKey]->isNotEmpty())
                            <optgroup label="{{ $groupTitle }}">
                                @foreach ($grouped[$typeKey] as $acc)
                                    <option value="{{ $acc->id }}">
                                        {{ $acc->code }} - {{ $acc->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>

            <!-- Quick Periods -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <button type="button" wire:click="setQuickDate('this_month')"
                    class="py-2.5 px-3 rounded-xl text-xs font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                    Bulan Ini
                </button>
                <button type="button" wire:click="setQuickDate('last_month')"
                    class="py-2.5 px-3 rounded-xl text-xs font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                    Bulan Lalu
                </button>
                <button type="button" wire:click="setQuickDate('this_year')"
                    class="py-2.5 px-3 rounded-xl text-xs font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                    Tahun Ini
                </button>
                <button type="button" wire:click="setQuickDate('all')"
                    class="py-2.5 px-3 rounded-xl text-xs font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                    Semua
                </button>
            </div>
        </div>

        @if($currentAccount)
            <!-- Active Account Card -->
            <div class="p-5 rounded-xl border border-brand-border bg-white flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-xs font-bold text-brand-espresso bg-neutral-100 px-2 py-0.5 rounded-md">
                            {{ $currentAccount->code }}
                        </span>
                        <h2 class="text-lg font-bold text-brand-espresso">
                            {{ $currentAccount->name }}
                        </h2>
                        <span class="text-xs text-brand-warm-gray">
                            • Saldo Normal: {{ strtoupper($currentAccount->normal_balance) }}
                        </span>
                    </div>
                    @if($currentAccount->description)
                        <p class="text-xs text-brand-warm-gray">
                            {{ $currentAccount->description }}
                        </p>
                    @endif
                </div>

                <div class="text-xs text-brand-warm-gray space-y-0.5 bg-neutral-50 p-3 rounded-lg border border-brand-border/60">
                    <p><strong class="text-brand-espresso">Debet:</strong> {{ $currentAccount->normal_balance === 'debit' ? 'Penambahan (+)' : 'Pengurang (-)' }}</p>
                    <p><strong class="text-brand-espresso">Kredit:</strong> {{ $currentAccount->normal_balance === 'credit' ? 'Penambahan (+)' : 'Pengurang (-)' }}</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-xl border border-brand-border bg-white space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">
                        Total Debet {{ $currentAccount->normal_balance === 'debit' ? '(+)' : '(-)' }}
                    </span>
                    <p class="text-2xl font-mono font-extrabold text-brand-espresso">
                        Rp {{ number_format($totalDebit, 0, ',', '.') }}
                    </p>
                </div>

                <div class="p-5 rounded-xl border border-brand-border bg-white space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">
                        Total Kredit {{ $currentAccount->normal_balance === 'credit' ? '(+)' : '(-)' }}
                    </span>
                    <p class="text-2xl font-mono font-extrabold text-brand-espresso">
                        Rp {{ number_format($totalCredit, 0, ',', '.') }}
                    </p>
                </div>

                <div class="p-5 rounded-xl border border-brand-border bg-white space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">
                        Saldo Akhir Terkini
                    </span>
                    <p class="text-2xl font-mono font-extrabold text-brand-espresso">
                        Rp {{ number_format($runningBalance, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-6 w-32 whitespace-nowrap">Tanggal</th>
                            <th class="py-3.5 px-6 w-36 whitespace-nowrap">No. Jurnal</th>
                            <th class="py-3.5 px-6">Keterangan Transaksi</th>
                            <th class="py-3.5 px-6 text-right w-40 whitespace-nowrap">Debet (Rp)</th>
                            <th class="py-3.5 px-6 text-right w-40 whitespace-nowrap">Kredit (Rp)</th>
                            <th class="py-3.5 px-6 text-right w-44 whitespace-nowrap">Saldo Berjalan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border/60 text-sm">
                        @php
                            $calcBalance = (float) $openingBalance;
                        @endphp

                        @if($openingBalance != 0)
                            <tr class="bg-neutral-50/50 italic text-brand-warm-gray font-medium">
                                <td class="py-3 px-6 font-mono text-xs">-</td>
                                <td class="py-3 px-6 font-mono text-xs">SALDO-AWAL</td>
                                <td class="py-3 px-6 text-brand-espresso font-semibold">Saldo Awal Sebelum Periode Ini</td>
                                <td class="py-3 px-6 text-right font-mono text-brand-warm-gray">-</td>
                                <td class="py-3 px-6 text-right font-mono text-brand-warm-gray">-</td>
                                <td class="py-3 px-6 text-right font-mono font-bold text-brand-espresso">
                                    Rp {{ number_format($openingBalance, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        @forelse($items as $item)
                            @php
                                if ($currentAccount->normal_balance === 'debit') {
                                    $calcBalance += (float) $item->debit - (float) $item->credit;
                                } else {
                                    $calcBalance += (float) $item->credit - (float) $item->debit;
                                }
                            @endphp
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <td class="py-3.5 px-6 font-mono text-brand-espresso whitespace-nowrap">
                                    {{ $item->journalEntry->entry_date->format('d/m/Y') }}
                                </td>
                                <td class="py-3.5 px-6 font-mono text-brand-espresso font-bold whitespace-nowrap">
                                    {{ $item->journalEntry->entry_number }}
                                </td>
                                <td class="py-3.5 px-6 text-brand-espresso">
                                    <span class="font-medium">{{ $item->journalEntry->notes }}</span>
                                    @if($item->memo && $item->memo !== $item->journalEntry->notes)
                                        <span class="block text-xs text-brand-warm-gray font-normal mt-0.5">({{ $item->memo }})</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-6 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                                    {{ $item->debit > 0 ? number_format($item->debit, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-3.5 px-6 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                                    {{ $item->credit > 0 ? number_format($item->credit, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-3.5 px-6 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                                    Rp {{ number_format($calcBalance, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-brand-warm-gray">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <i class="ti ti-report-off text-3xl text-brand-warm-gray"></i>
                                        <p class="font-bold text-brand-espresso text-base">Belum ada mutasi transaksi</p>
                                        <p class="text-xs text-brand-warm-gray">Belum ada mutasi transaksi untuk akun perkiraan ini pada periode yang dipilih.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-neutral-50/80 font-bold border-t border-brand-border text-sm">
                            <td colspan="3" class="py-3 px-6 text-right text-brand-warm-gray uppercase tracking-wider">Total Mutasi:</td>
                            <td class="py-3 px-6 text-right font-mono text-brand-espresso">
                                Rp {{ number_format($totalDebit, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-6 text-right font-mono text-brand-espresso">
                                Rp {{ number_format($totalCredit, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-6 text-right font-mono text-brand-espresso">
                                Rp {{ number_format($runningBalance, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endif

</div>
