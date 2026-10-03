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
                <span class="text-brand-primary">Jurnal Umum Akuntansi</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Jurnal Umum Akuntansi
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Catatan berpasangan debet dan kredit dari seluruh aktivitas transaksi usaha secara sistematis.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.accounting.ledger') }}" wire:navigate
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                <span>Lihat Buku Besar</span>
            </a>
            <a href="{{ route('admin.accounting.financial-statements') }}" wire:navigate
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                <span>Laporan Keuangan</span>
            </a>
        </div>
    </div>

    <!-- Summary Balance Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Debet</span>
            <p class="text-2xl font-mono font-extrabold text-brand-espresso">
                Rp {{ number_format($totalDebit, 0, ',', '.') }}
            </p>
            <p class="text-xs text-brand-warm-gray">Akumulasi pencatatan debet periode ini</p>
        </div>

        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Kredit</span>
                @if(round($totalDebit, 2) === round($totalCredit, 2))
                    <span class="text-xs font-bold text-brand-primary uppercase">Seimbang (Balanced)</span>
                @else
                    <span class="text-xs font-bold text-red-600 uppercase">Selisih (Unbalanced)</span>
                @endif
            </div>
            <p class="text-2xl font-mono font-extrabold text-brand-espresso">
                Rp {{ number_format($totalCredit, 0, ',', '.') }}
            </p>
            <p class="text-xs text-brand-warm-gray">Akumulasi pencatatan kredit periode ini</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <!-- Search -->
        <div class="relative flex-1 max-w-md">
            <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg pointer-events-none"></i>
            <input type="text" wire:model.live.debounce.300ms="search"
                placeholder="Cari nomor jurnal, akun, catatan..."
                class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
        </div>

        <!-- Quick Periods -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" wire:click="setQuickDate('today')"
                class="py-2.5 px-3 rounded-xl text-xs font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                Hari Ini
            </button>
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

            @if($hasActiveFilters)
                <button type="button" wire:click="resetFilters" class="text-xs font-bold text-brand-primary hover:underline ml-2 cursor-pointer">
                    ✕ Reset
                </button>
            @endif
        </div>
    </div>

    <!-- Journal Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 w-32 whitespace-nowrap">Tanggal</th>
                    <th class="py-3.5 px-6 w-40 whitespace-nowrap">No. Jurnal</th>
                    <th class="py-3.5 px-6">Keterangan &amp; Akun Perkiraan</th>
                    <th class="py-3.5 px-6 text-right w-44 whitespace-nowrap">Debet (Rp)</th>
                    <th class="py-3.5 px-6 text-right w-44 whitespace-nowrap">Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border/60 text-sm">
                @forelse($entries as $entry)
                    <!-- Header Entry -->
                    <tr class="bg-neutral-50/40 font-bold border-t border-brand-border/80">
                        <td class="py-3 px-6 font-mono text-brand-espresso whitespace-nowrap align-top">
                            {{ $entry->entry_date->format('d/m/Y') }}
                        </td>
                        <td class="py-3 px-6 font-mono text-brand-espresso whitespace-nowrap align-top">
                            {{ $entry->entry_number }}
                        </td>
                        <td colspan="3" class="py-3 px-6 text-brand-espresso font-bold align-top">
                            {{ $entry->notes }}
                        </td>
                    </tr>

                    <!-- Items Debit / Credit -->
                    @foreach($entry->items as $item)
                        <tr class="hover:bg-neutral-50/50 transition-colors">
                            <td class="py-2.5 px-6"></td>
                            <td class="py-2.5 px-6 font-mono text-xs text-brand-warm-gray">
                                {{ $item->account->code }}
                            </td>
                            <td class="py-2.5 px-6 {{ $item->credit > 0 ? 'pl-12 text-brand-warm-gray' : 'text-brand-espresso font-medium' }}">
                                <span>{{ $item->account->name }}</span>
                                @if($item->memo && $item->memo !== $entry->notes)
                                    <span class="block text-xs text-brand-warm-gray font-normal mt-0.5">({{ $item->memo }})</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-6 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                                @if($item->debit > 0)
                                    {{ number_format($item->debit, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2.5 px-6 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                                @if($item->credit > 0)
                                    {{ number_format($item->credit, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="py-14 text-center text-brand-warm-gray">
                            Belum ada catatan jurnal umum yang terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($entries->hasPages())
        <div class="pt-2">
            {{ $entries->links() }}
        </div>
    @endif

</div>
