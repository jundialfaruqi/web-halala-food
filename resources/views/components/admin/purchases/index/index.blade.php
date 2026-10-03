<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <nav aria-label="Breadcrumb"
                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Operasional</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Pembelian Bahan</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Pengadaan Bahan Baku
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Catat belanja bahan baku dapur, kelola riwayat nota supplier, dan pantau pengeluaran kas operasional.
            </p>
        </div>

        @can('pembelian-create')
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.purchases.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs">
                    <i class="ti ti-plus text-base"></i>
                    <span>Catat Pembelian Baru</span>
                </a>
            </div>
        @endcan
    </div>

    <!-- Alert Flash Notifications -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl border border-brand-border bg-white text-brand-espresso text-sm font-medium flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button type="button" @click="$el.parentElement.remove()" class="text-brand-warm-gray hover:text-brand-espresso">
                <i class="ti ti-x"></i>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 rounded-xl border border-brand-border bg-white text-brand-espresso text-sm font-medium flex items-center justify-between">
            <span class="text-red-700 font-semibold">{{ session('error') }}</span>
            <button type="button" @click="$el.parentElement.remove()" class="text-brand-warm-gray hover:text-brand-espresso">
                <i class="ti ti-x"></i>
            </button>
        </div>
    @endif

    <!-- Clean Metric Summary (No Icon BG, No Badge) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl border border-brand-border bg-white space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Belanja Bulan Ini</span>
            <p class="text-2xl font-extrabold text-brand-espresso">
                Rp {{ number_format($monthlyTotalAmount, 0, ',', '.') }}
            </p>
            <p class="text-xs text-brand-warm-gray">Total pengeluaran kas pembelian bahan baku</p>
        </div>

        <div class="p-5 rounded-2xl border border-brand-border bg-white space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Transaksi Bulan Ini</span>
            <p class="text-2xl font-extrabold text-brand-espresso">
                {{ $monthlyTotalCount }} <span class="text-sm font-normal text-brand-warm-gray">nota</span>
            </p>
            <p class="text-xs text-brand-warm-gray">Jumlah pengadaan bahan yang tercatat</p>
        </div>

        <div class="p-5 rounded-2xl border border-brand-border bg-white space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Transaksi Terdata</span>
            <p class="text-2xl font-extrabold text-brand-espresso">
                {{ $purchases->total() }} <span class="text-sm font-normal text-brand-warm-gray">transaksi</span>
            </p>
            <p class="text-xs text-brand-warm-gray">Riwayat keseluruhan dari supplier</p>
        </div>
    </div>

    <!-- Filter & Search Section -->
    <div class="bg-white border border-brand-border rounded-2xl p-4 sm:p-5 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
            <div class="sm:col-span-8 relative">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-base pointer-events-none"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari no. pembelian, supplier, atau nama bahan..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-brand-border text-sm text-brand-espresso placeholder-brand-warm-gray/60 focus:outline-hidden focus:border-brand-primary bg-white">
            </div>

            <div class="sm:col-span-4">
                <select wire:model.live="paymentMethod"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-brand-border text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary bg-white">
                    <option value="">Semua Metode Pembayaran</option>
                    <option value="tunai">Tunai / Kas Kecil</option>
                    <option value="transfer_bank">Transfer Bank</option>
                    <option value="tempo">Tempo / Kredit Supplier</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white border border-brand-border rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-brand-espresso">
                <thead class="bg-brand-soft-cream/40 text-xs font-bold uppercase tracking-wider text-brand-warm-gray border-b border-brand-border">
                    <tr>
                        <th class="px-6 py-4">No. Transaksi</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Supplier</th>
                        <th class="px-6 py-4">Item Bahan</th>
                        <th class="px-6 py-4 text-right">Total Biaya</th>
                        <th class="px-6 py-4">Metode Bayar</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border">
                    @forelse ($purchases as $item)
                        <tr class="hover:bg-brand-soft-cream/20 transition">
                            <td class="px-6 py-4 font-mono font-bold text-brand-espresso">
                                {{ $item->purchase_number }}
                            </td>
                            <td class="px-6 py-4 text-brand-warm-gray whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item->purchase_date)->isoFormat('D MMM Y') }}
                            </td>
                            <td class="px-6 py-4 font-medium text-brand-espresso">
                                {{ $item->supplier_name }}
                            </td>
                            <td class="px-6 py-4 text-brand-warm-gray">
                                <span class="font-semibold text-brand-espresso">{{ $item->items->count() }} jenis</span>
                                <span class="text-xs">
                                    ({{ Str::limit($item->items->pluck('rawMaterial.name')->filter()->join(', '), 35) }})
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-extrabold text-brand-espresso whitespace-nowrap">
                                Rp {{ number_format($item->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 capitalize text-brand-warm-gray whitespace-nowrap">
                                @if ($item->payment_method === 'transfer_bank')
                                    Transfer Bank
                                @elseif ($item->payment_method === 'tempo')
                                    Tempo
                                @else
                                    Tunai
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center justify-center gap-3">
                                    <button type="button" wire:click="viewDetails({{ $item->id }})"
                                        class="text-xs font-semibold text-brand-espresso hover:text-brand-primary transition cursor-pointer">
                                        Rincian
                                    </button>

                                    @can('pembelian-delete')
                                        <button type="button"
                                            wire:click="deletePurchase({{ $item->id }})"
                                            wire:confirm="Yakin ingin membatalkan transaksi pembelian {{ $item->purchase_number }}? Stok bahan baku akan dikembalikan/dikurangi."
                                            class="text-xs font-semibold text-red-600 hover:text-red-700 transition cursor-pointer">
                                            Hapus
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-brand-warm-gray">
                                Tidak ada transaksi pembelian bahan baku ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($purchases->hasPages())
            <div class="px-6 py-4 border-t border-brand-border bg-white">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

    <!-- Details Modal -->
    @if ($activePurchase)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-brand-espresso/60 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl border border-brand-border w-full max-w-2xl overflow-hidden shadow-xl" @click.outside="$wire.closeDetails()">
                <div class="px-6 py-4 border-b border-brand-border flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-brand-espresso">Rincian Pembelian Bahan</h2>
                        <span class="text-xs font-mono text-brand-warm-gray">{{ $activePurchase->purchase_number }}</span>
                    </div>
                    <button type="button" wire:click="closeDetails" class="text-brand-warm-gray hover:text-brand-espresso">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4 text-sm border-b border-brand-border pb-4">
                        <div>
                            <span class="text-xs uppercase text-brand-warm-gray font-semibold block">Supplier</span>
                            <span class="font-bold text-brand-espresso">{{ $activePurchase->supplier_name }}</span>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-brand-warm-gray font-semibold block">Tanggal Pembelian</span>
                            <span class="font-medium text-brand-espresso">{{ \Carbon\Carbon::parse($activePurchase->purchase_date)->isoFormat('D MMMM Y') }}</span>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-brand-warm-gray font-semibold block">Metode Pembayaran</span>
                            <span class="font-medium text-brand-espresso capitalize">
                                {{ str_replace('_', ' ', $activePurchase->payment_method) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs uppercase text-brand-warm-gray font-semibold block">Dicatat Oleh</span>
                            <span class="font-medium text-brand-espresso">{{ $activePurchase->creator?->name ?? 'Sistem' }}</span>
                        </div>
                    </div>

                    @if ($activePurchase->notes)
                        <div class="text-sm border-b border-brand-border pb-4">
                            <span class="text-xs uppercase text-brand-warm-gray font-semibold block mb-1">Catatan Nota</span>
                            <p class="text-brand-espresso">{{ $activePurchase->notes }}</p>
                        </div>
                    @endif

                    <div class="space-y-2">
                        <span class="text-xs uppercase text-brand-warm-gray font-semibold block">Daftar Bahan Masuk</span>
                        <div class="border border-brand-border rounded-xl overflow-hidden">
                            <table class="w-full text-left text-sm text-brand-espresso">
                                <thead class="bg-brand-soft-cream/40 text-xs font-bold uppercase text-brand-warm-gray border-b border-brand-border">
                                    <tr>
                                        <th class="px-4 py-2.5">Bahan Baku</th>
                                        <th class="px-4 py-2.5 text-right">Jumlah</th>
                                        <th class="px-4 py-2.5 text-right">Harga Satuan</th>
                                        <th class="px-4 py-2.5 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-brand-border">
                                    @foreach ($activePurchase->items as $detail)
                                        <tr>
                                            <td class="px-4 py-2.5 font-medium">
                                                {{ $detail->rawMaterial?->name ?? 'Bahan Dihapus' }}
                                                @if ($detail->notes)
                                                    <span class="block text-xs text-brand-warm-gray">{{ $detail->notes }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                                {{ number_format($detail->quantity, 2, ',', '.') }} {{ $detail->rawMaterial?->display_unit }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                                Rp {{ number_format($detail->cost_per_unit, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-bold whitespace-nowrap">
                                                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-brand-border bg-brand-soft-cream/20">
                                    <tr>
                                        <th colspan="3" class="px-4 py-3 text-right font-bold text-brand-espresso">Total Biaya:</th>
                                        <th class="px-4 py-3 text-right font-extrabold text-brand-espresso text-base">
                                            Rp {{ number_format($activePurchase->total_amount, 0, ',', '.') }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-brand-border bg-white flex justify-end">
                    <button type="button" wire:click="closeDetails"
                        class="px-5 py-2 rounded-xl text-sm font-bold text-brand-espresso border border-brand-border hover:bg-neutral-100 transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
