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
        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Belanja Bulan Ini</span>
            <p class="text-2xl font-extrabold text-brand-espresso">
                Rp {{ number_format($monthlyTotalAmount, 0, ',', '.') }}
            </p>
            <p class="text-xs text-brand-warm-gray">Total pengeluaran kas pembelian bahan baku</p>
        </div>

        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Transaksi Bulan Ini</span>
            <p class="text-2xl font-extrabold text-brand-espresso">
                {{ $monthlyTotalCount }} <span class="text-sm font-normal text-brand-warm-gray">nota</span>
            </p>
            <p class="text-xs text-brand-warm-gray">Jumlah pengadaan bahan yang tercatat</p>
        </div>

        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Transaksi Terdata</span>
            <p class="text-2xl font-extrabold text-brand-espresso">
                {{ $purchases->total() }} <span class="text-sm font-normal text-brand-warm-gray">transaksi</span>
            </p>
            <p class="text-xs text-brand-warm-gray">Riwayat keseluruhan dari supplier</p>
        </div>
    </div>

    <!-- Filter & Search Bar (Identik dengan format seragam aplikasi) -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg pointer-events-none"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari no. transaksi, supplier, bahan..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Metode Pembayaran Filter -->
            <div class="w-full sm:w-auto">
                <select wire:model.live="paymentMethod"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="">Semua Metode Pembayaran</option>
                    <option value="tunai">Tunai / Kas Kecil</option>
                    <option value="transfer_bank">Transfer Bank</option>
                    <option value="tempo">Tempo / Kredit Supplier</option>
                </select>
            </div>
        </div>

        <!-- Total Indicator -->
        <div class="text-xs sm:text-sm text-brand-warm-gray font-medium self-center shrink-0">
            Menampilkan <span class="font-bold text-brand-espresso">{{ $purchases->total() }}</span> transaksi pembelian
        </div>
    </div>

    <!-- Data Table Container (Identik dengan Faktur, Pengantaran & Toko Mitra) -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">No. Transaksi &amp; Tanggal</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Supplier</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Item Bahan Masuk</th>
                    <th class="py-3.5 px-6 whitespace-nowrap text-right">Total Belanja</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Metode Bayar</th>
                    <th class="py-3.5 px-6 whitespace-nowrap text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border/60 text-sm">
                @forelse ($purchases as $item)
                    <tr class="hover:bg-neutral-50/70 transition">
                        <!-- 1. No Transaksi & Tanggal -->
                        <td class="py-4 px-6 whitespace-nowrap">
                            <button type="button" wire:click="viewDetails({{ $item->id }})"
                                class="font-mono font-bold text-brand-espresso hover:text-brand-primary transition block text-left cursor-pointer">
                                {{ $item->purchase_number }}
                            </button>
                            <span class="text-xs text-brand-warm-gray block mt-0.5">
                                {{ \Carbon\Carbon::parse($item->purchase_date)->isoFormat('D MMMM Y') }}
                            </span>
                        </td>

                        <!-- 2. Supplier & Catatan -->
                        <td class="py-4 px-6 whitespace-nowrap">
                            <div class="font-bold text-brand-espresso">{{ $item->supplier_name }}</div>
                            @if ($item->notes)
                                <span class="text-xs text-brand-warm-gray block mt-0.5 truncate max-w-xs">{{ $item->notes }}</span>
                            @else
                                <span class="text-xs text-brand-warm-gray/60 block mt-0.5 font-light">Tanpa catatan</span>
                            @endif
                        </td>

                        <!-- 3. Item Bahan Masuk -->
                        <td class="py-4 px-6">
                            <div class="font-bold text-brand-espresso">{{ $item->items->count() }} jenis bahan</div>
                            <p class="text-xs text-brand-warm-gray mt-0.5 truncate max-w-xs sm:max-w-sm">
                                {{ $item->items->map(function ($it) {
                                    $name = $it->rawMaterial?->name ?? 'Bahan';
                                    $qty = rtrim(rtrim(number_format($it->quantity, 2, ',', '.'), '0'), ',');
                                    $unit = $it->rawMaterial?->display_unit ?? '';
                                    return "{$name} ({$qty} {$unit})";
                                })->join(', ') }}
                            </p>
                        </td>

                        <!-- 4. Total Belanja -->
                        <td class="py-4 px-6 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                            Rp {{ number_format($item->total_amount, 0, ',', '.') }}
                        </td>

                        <!-- 5. Metode Bayar (Dot + Text, NO BADGE) -->
                        <td class="py-4 px-6 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="inline-block size-2 rounded-full {{ $item->payment_method === 'tempo' ? 'bg-amber-600' : ($item->payment_method === 'transfer_bank' ? 'bg-brand-primary' : 'bg-stone-500') }}"></span>
                                <span class="text-xs font-semibold uppercase tracking-wider {{ $item->payment_method === 'tempo' ? 'text-amber-800' : ($item->payment_method === 'transfer_bank' ? 'text-brand-primary' : 'text-brand-espresso') }}">
                                    {{ $item->payment_method === 'transfer_bank' ? 'Transfer Bank' : ($item->payment_method === 'tempo' ? 'Tempo / Kredit' : 'Tunai') }}
                                </span>
                            </div>
                        </td>

                        <!-- 6. Aksi -->
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" wire:click="viewDetails({{ $item->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-brand-border rounded-lg text-xs font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                                    <span>Rincian</span>
                                    <i class="ti ti-arrow-right text-xs"></i>
                                </button>

                                @can('pembelian-delete')
                                    <button type="button"
                                        wire:click="deletePurchase({{ $item->id }})"
                                        wire:confirm="Yakin ingin membatalkan transaksi pembelian {{ $item->purchase_number }}? Stok bahan baku akan dikembalikan/dikurangi."
                                        class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-red-700 hover:bg-neutral-100 transition cursor-pointer"
                                        title="Hapus / Batalkan Transaksi">
                                        <i class="ti ti-trash text-base"></i>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-brand-warm-gray">
                            <div class="flex flex-col items-center justify-center">
                                <i class="ti ti-shopping-cart-off text-3xl text-brand-warm-gray/50 mb-2"></i>
                                <p class="font-bold text-brand-espresso">Tidak ada transaksi pembelian bahan</p>
                                <p class="text-xs text-brand-warm-gray mt-0.5">Belum ada data pengadaan bahan baku yang cocok dengan pencarian atau filter Anda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($purchases->hasPages())
            <div class="px-6 py-4 border-t border-brand-border/60 bg-white">
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
                    <button type="button" wire:click="closeDetails" class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm border-b border-brand-border pb-4">
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
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs font-bold uppercase tracking-wider">
                                        <th class="py-3 px-4">Bahan Baku</th>
                                        <th class="py-3 px-4 text-right">Jumlah</th>
                                        <th class="py-3 px-4 text-right">Harga Satuan</th>
                                        <th class="py-3 px-4 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-brand-border/60 text-sm">
                                    @foreach ($activePurchase->items as $detail)
                                        <tr class="hover:bg-neutral-50/50 transition">
                                            <td class="py-3 px-4 font-medium text-brand-espresso">
                                                {{ $detail->rawMaterial?->name ?? 'Bahan Dihapus' }}
                                                @if ($detail->notes)
                                                    <span class="block text-xs text-brand-warm-gray">{{ $detail->notes }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-right font-mono whitespace-nowrap">
                                                {{ number_format($detail->quantity, 2, ',', '.') }} {{ $detail->rawMaterial?->display_unit }}
                                            </td>
                                            <td class="py-3 px-4 text-right font-mono whitespace-nowrap">
                                                Rp {{ number_format($detail->cost_per_unit, 2, ',', '.') }}
                                            </td>
                                            <td class="py-3 px-4 text-right font-mono font-bold whitespace-nowrap">
                                                Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-brand-border bg-neutral-50/60">
                                        <th colspan="3" class="py-3 px-4 text-right font-bold text-brand-espresso">Total Biaya:</th>
                                        <th class="py-3 px-4 text-right font-mono font-extrabold text-brand-espresso text-base">
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
