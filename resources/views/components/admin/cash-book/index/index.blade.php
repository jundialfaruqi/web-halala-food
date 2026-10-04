<div class="space-y-6">

    <!-- Header Section -->
    <div>
        <nav aria-label="Breadcrumb"
            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Keuangan</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Buku Kas &amp; Keuangan</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
            Buku Kas &amp; Keuangan
        </h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
            Pencatatan arus kas operasional, saldo rekening kas &amp; bank usaha, serta penarikan prive pemilik.
        </p>
    </div>

    <!-- Tombol Aksi Kas (Di bawah Judul Halaman) -->
    @can('buku-kas-create')
        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" wire:click="openAccountModal"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                <i class="ti ti-plus text-base"></i>
                <span>Tambah Akun Kas</span>
            </button>
            <button type="button" wire:click="openTransactionModal('expense')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                <i class="ti ti-plus text-base"></i>
                <span>Catat Transaksi Baru</span>
            </button>
            <button type="button" wire:click="openTransactionModal('income')"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                <span>+ Pemasukan</span>
            </button>
            <button type="button" wire:click="openTransactionModal('expense')"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                <span>- Biaya Usaha</span>
            </button>
            <button type="button" wire:click="openTransactionModal('prive')"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                <span>Prive</span>
            </button>
        </div>
    @endcan

    <!-- Alert Flash Notification -->
    @if (session()->has('success'))
        <div
            class="p-4 rounded-xl border border-brand-border bg-white text-brand-espresso text-sm font-medium flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button type="button" @click="$el.parentElement.remove()"
                class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                <i class="ti ti-x"></i>
            </button>
        </div>
    @endif

    <!-- Cards Ringkasan Kas Usaha & Prive -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Saldo Kas Usaha -->
        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-brand-border/60">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Total Saldo Kas Usaha</h2>
                    <p class="text-xs text-brand-warm-gray">Kas kecil &amp; rekening operasional</p>
                </div>
                <span class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Kas &amp; Bank</span>
            </div>

            <p class="text-3xl font-mono font-extrabold text-brand-espresso">
                Rp {{ number_format($totalCashBalance, 0, ',', '.') }}
            </p>

            <div class="space-y-1.5 pt-2 border-t border-brand-border/40 text-sm">
                @foreach ($accounts as $acc)
                    <div class="flex justify-between items-center py-0.5">
                        <span class="text-brand-warm-gray font-medium">{{ $acc->name }}</span>
                        <span class="font-mono font-bold text-brand-espresso">Rp
                            {{ number_format($acc->balance, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Pemasukan & Pengeluaran Operasional -->
        <div class="p-5 rounded-xl border border-brand-border bg-white flex flex-col justify-between space-y-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Pemasukan
                    (Periode Ini)</span>
                <p class="text-2xl font-mono font-extrabold text-brand-espresso mt-1">
                    Rp {{ number_format($filteredIncome, 0, ',', '.') }}
                </p>
                <p class="text-xs text-brand-warm-gray mt-1">Penerimaan penjualan, piutang &amp; setoran modal</p>
            </div>
            <div class="pt-3 border-t border-brand-border/40">
                <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Biaya Usaha
                    (Periode Ini)</span>
                <p class="text-2xl font-mono font-extrabold text-brand-espresso mt-1">
                    Rp {{ number_format($filteredExpense, 0, ',', '.') }}
                </p>
                <p class="text-xs text-brand-warm-gray mt-1">Belanja bahan baku, utilitas &amp; operasional dapur</p>
            </div>
        </div>

        <!-- Penarikan Prive -->
        <div class="p-5 rounded-xl border border-brand-border bg-white flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">Total Prive
                        (Periode Ini)</span>
                    <span class="text-xs font-mono font-bold text-brand-warm-gray">Akun 3-2000</span>
                </div>
                <p class="text-2xl font-mono font-extrabold text-brand-espresso mt-1">
                    Rp {{ number_format($filteredPrive, 0, ',', '.') }}
                </p>
                <p class="text-xs text-brand-warm-gray mt-1">Penarikan dana kas usaha oleh pemilik</p>
            </div>
            <div class="pt-3 border-t border-brand-border/40 text-xs text-brand-warm-gray space-y-1">
                <p><strong class="text-brand-espresso font-semibold">Prive:</strong> Pengambilan uang kas usaha yang
                    dicatat mengurangi ekuitas modal pemilik di Neraca.</p>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Search -->
            <div class="relative">
                <i
                    class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg pointer-events-none"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kategori, keterangan..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Type Filter -->
            <div>
                <select wire:model.live="typeFilter"
                    class="w-full px-4 py-3 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="all">Semua Jenis Transaksi</option>
                    <option value="income">Pemasukan Usaha (+)</option>
                    <option value="expense">Pengeluaran Usaha (-)</option>
                    <option value="prive">Prive</option>
                </select>
            </div>

            <!-- Account Filter -->
            <div>
                <select wire:model.live="accountFilter"
                    class="w-full px-4 py-3 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="">Semua Akun &amp; Rekening</option>
                    @foreach ($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Quick Period Buttons -->
            <div class="flex items-center gap-1.5">
                <button type="button" wire:click="setQuickDate('today')"
                    class="flex-1 py-2.5 px-3 rounded-xl text-base font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer text-center">
                    Hari Ini
                </button>
                <button type="button" wire:click="setQuickDate('this_month')"
                    class="flex-1 py-2.5 px-3 rounded-xl text-base font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer text-center">
                    Bulan Ini
                </button>
                <button type="button" wire:click="setQuickDate('all')"
                    class="flex-1 py-2.5 px-3 rounded-xl text-base font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer text-center">
                    Semua
                </button>
            </div>
        </div>

        @if ($hasActiveFilters)
            <div
                class="p-3.5 bg-neutral-50 rounded-xl border border-brand-border flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm">
                <div class="flex items-center gap-2 text-brand-warm-gray">
                    <span class="font-bold text-brand-espresso">{{ $transactions->total() }}</span> transaksi ditemukan
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <span class="text-brand-warm-gray">Pemasukan:</span>
                        <span class="font-mono font-bold text-brand-espresso ml-1">+ Rp
                            {{ number_format($filteredIncome, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-brand-warm-gray">Pengeluaran:</span>
                        <span class="font-mono font-bold text-brand-espresso ml-1">- Rp
                            {{ number_format($filteredExpense, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-brand-warm-gray">Prive:</span>
                        <span class="font-mono font-bold text-brand-espresso ml-1">Rp
                            {{ number_format($filteredPrive, 0, ',', '.') }}</span>
                    </div>
                    <button type="button" wire:click="resetFilters"
                        class="text-brand-primary hover:underline font-bold cursor-pointer">
                        ✕ Reset Filter
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Data Table Container -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr
                    class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">Tanggal</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Jenis &amp; Kategori</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Akun / Rekening</th>
                    <th class="py-3.5 px-6">Keterangan</th>
                    <th class="py-3.5 px-6 whitespace-nowrap text-right">Nominal</th>
                    <th class="py-3.5 px-6 whitespace-nowrap text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border/60 text-sm">
                @forelse($transactions as $trx)
                    <tr wire:key="trx-row-{{ $trx->id }}" wire:click="showDetail({{ $trx->id }})"
                        class="hover:bg-neutral-100/70 transition-colors cursor-pointer"
                        title="Klik untuk melihat rincian transaksi lengkap">
                        <td class="py-4 px-6 font-mono font-bold text-brand-espresso whitespace-nowrap">
                            {{ $trx->transaction_date->format('d/m/Y') }}
                        </td>
                        <td class="py-4 px-6">
                            <p class="font-bold text-brand-espresso">{{ $trx->category }}</p>
                            <p class="text-xs text-brand-warm-gray uppercase tracking-wider mt-0.5">
                                @if ($trx->type === 'income')
                                    Pemasukan Usaha
                                @elseif($trx->type === 'expense')
                                    Pengeluaran Usaha
                                @elseif($trx->type === 'prive')
                                    Prive
                                @endif
                            </p>
                        </td>
                        <td class="py-4 px-6 text-brand-espresso font-medium whitespace-nowrap">
                            {{ $trx->account->name }}
                        </td>
                        <td class="py-4 px-6 text-brand-espresso">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-brand-espresso leading-snug">{{ $trx->description ?: '-' }}</p>
                                @if ($trx->materials_summary && !str_contains($trx->description ?? '', $trx->materials_summary))
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 text-amber-900 border border-amber-200/80 rounded-lg text-xs font-medium">
                                        <svg class="w-3.5 h-3.5 text-amber-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span><strong class="font-semibold text-amber-950">Bahan Baku:</strong> {{ $trx->materials_summary }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td
                            class="py-4 px-6 text-right font-mono font-extrabold text-base whitespace-nowrap text-brand-espresso">
                            {{ $trx->type === 'income' ? '+' : '-' }} Rp
                            {{ number_format($trx->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            @can('buku-kas-delete')
                                <button type="button" wire:click.stop="confirmDeleteTransaction({{ $trx->id }})"
                                    class="text-brand-warm-gray hover:text-red-700 transition cursor-pointer p-1"
                                    title="Hapus Transaksi">
                                    <i class="ti ti-trash text-lg"></i>
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-brand-warm-gray">
                            Belum ada catatan mutasi kas yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transactions->hasPages())
        <div class="pt-2">
            {{ $transactions->links() }}
        </div>
    @endif

    <!-- Modal Form Catat Transaksi Kas -->
    @if ($showTransactionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-brand-espresso/60 backdrop-blur-xs p-4">
            <div
                class="bg-white w-full max-w-xl max-h-[90vh] flex flex-col rounded-2xl border border-brand-border shadow-2xl overflow-hidden">
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-brand-border bg-neutral-50/50 shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-brand-espresso">Catat Transaksi Kas</h3>
                        <p class="text-xs text-brand-warm-gray">Catat pemasukan, pengeluaran, atau penarikan kas usaha
                        </p>
                    </div>
                    <button type="button" wire:click="$set('showTransactionModal', false)"
                        class="text-brand-warm-gray hover:text-brand-espresso text-xl font-bold cursor-pointer">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <form wire:submit="prepareTransactionConfirmation"
                    class="flex flex-col flex-1 overflow-hidden min-h-0">
                    <div class="p-6 overflow-y-auto space-y-4 flex-1">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                    Tanggal Transaksi <span class="text-red-600">*</span>
                                </label>
                                <input type="date" wire:model="transaction_date"
                                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                                @error('transaction_date')
                                    <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                    Jenis Transaksi <span class="text-red-600">*</span>
                                </label>
                                <select wire:model.live="type"
                                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                    <option value="expense">Pengeluaran Usaha (-)</option>
                                    <option value="income">Pemasukan Usaha (+)</option>
                                    <option value="prive">Prive</option>
                                </select>
                                @error('type')
                                    <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                Akun / Rekening Kas <span class="text-red-600">*</span>
                            </label>
                            <select wire:model="account_id"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">
                                        {{ $acc->name }} (Saldo: Rp
                                        {{ number_format($acc->balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('account_id')
                                <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Pilihan Kategori Cepat -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                                Kategori / Pos Transaksi <span class="text-red-600">*</span>
                            </label>
                            <div class="flex flex-wrap gap-2">
                                @if ($type === 'income')
                                    <button type="button" wire:click="$set('category', 'Setoran Modal')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Setoran Modal' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Setoran Modal
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Pendapatan Penjualan')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Pendapatan Penjualan' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Pendapatan Penjualan
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Pelunasan Piutang Toko')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Pelunasan Piutang Toko' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Pelunasan Piutang
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Pendapatan Lain-lain')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Pendapatan Lain-lain' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Lain-lain
                                    </button>
                                @elseif($type === 'expense')
                                    <button type="button" wire:click="$set('category', 'Belanja Bahan Baku')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Belanja Bahan Baku' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Belanja Bahan
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Gaji & Upah Karyawan')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Gaji & Upah Karyawan' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Gaji &amp; Upah
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Beli Kemasan & Stiker')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Beli Kemasan & Stiker' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Kemasan &amp; Stiker
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Biaya Listrik, Air & Gas')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Biaya Listrik, Air & Gas' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Listrik &amp; Gas
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Bensin & Transportasi')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Bensin & Transportasi' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Bensin &amp; Kurir
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Pelunasan Hutang Supplier')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Pelunasan Hutang Supplier' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Hutang Supplier
                                    </button>
                                    <button type="button" wire:click="$set('category', 'Operasional Lainnya')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Operasional Lainnya' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Operasional Lain
                                    </button>
                                @elseif($type === 'prive')
                                    <button type="button" wire:click="$set('category', 'Prive')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer bg-brand-primary text-white border-brand-primary">
                                        Prive
                                    </button>
                                @endif
                            </div>

                            <input type="text" wire:model="category"
                                placeholder="Ketik atau pilih nama kategori..."
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                            @error('category')
                                <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div x-data="{
                            formatRupiah(val) {
                                if (!val && val !== 0) return '';
                                let clean = val.toString().replace(/[^0-9]/g, '').replace(/^0+/, '');
                                if (!clean) return '';
                                return 'Rp ' + clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            },
                            cleanNumber(val) {
                                let clean = (val || '').toString().replace(/[^0-9]/g, '').replace(/^0+/, '');
                                return clean ? parseInt(clean, 10) : 0;
                            },
                            onInput(e) {
                                let oldVal = e.target.value;
                                let oldPos = e.target.selectionEnd || oldVal.length;
                                let charsFromRight = oldVal.length - oldPos;

                                let raw = this.cleanNumber(oldVal);
                                let formatted = raw > 0 ? this.formatRupiah(raw) : '';
                                e.target.value = formatted;

                                if (formatted) {
                                    let newPos = Math.max(3, formatted.length - charsFromRight);
                                    e.target.setSelectionRange(newPos, newPos);
                                }

                                $wire.set('amount', raw, false);
                                if (this.$refs.hiddenAmount) {
                                    this.$refs.hiddenAmount.value = raw;
                                    this.$refs.hiddenAmount.dispatchEvent(new Event('input', { bubbles: true }));
                                }
                            },
                            onBlur(e) {
                                let raw = this.cleanNumber(e.target.value);
                                e.target.value = raw > 0 ? this.formatRupiah(raw) : '';
                                $wire.set('amount', raw, false);
                                if (this.$refs.hiddenAmount) {
                                    this.$refs.hiddenAmount.value = raw;
                                    this.$refs.hiddenAmount.dispatchEvent(new Event('input', { bubbles: true }));
                                }
                            },
                            init() {
                                let current = @js($amount);
                                let input = this.$refs.displayInput;
                                if (current && current > 0) {
                                    input.value = this.formatRupiah(current);
                                } else {
                                    input.value = '';
                                }
                                this.$watch('$wire.amount', (newVal) => {
                                    let raw = this.cleanNumber(newVal);
                                    let currentRaw = this.cleanNumber(input.value);
                                    if (raw !== currentRaw) {
                                        input.value = raw > 0 ? this.formatRupiah(raw) : '';
                                    }
                                });
                            }
                        }">
                            <label
                                class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                Nominal Transaksi (Rp) <span class="text-red-600">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                    x-ref="displayInput"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    @input="onInput($event)"
                                    @blur="onBlur($event)"
                                    placeholder="Rp 0"
                                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl font-mono text-base font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition" />
                                <input type="hidden" wire:model="amount" x-ref="hiddenAmount" />
                            </div>
                            @error('amount')
                                <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                Keterangan Tambahan
                            </label>
                            <textarea wire:model="description" rows="2" placeholder="Catatan transaksi (opsional)..."
                                class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary"></textarea>
                            @error('description')
                                <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 px-6 py-4 border-t border-brand-border bg-neutral-50/50 shrink-0">
                        <button type="button" wire:click="$set('showTransactionModal', false)"
                            class="px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                            Lanjut Konfirmasi →
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Konfirmasi Transaksi Kas -->
    @if ($showConfirmTransactionModal)
        @php
            $selectedAccount = $accounts->firstWhere('id', $account_id);
            $currentBalance = $selectedAccount?->balance ?? 0;
            $newBalance = $type === 'income' ? $currentBalance + (float) $amount : $currentBalance - (float) $amount;
        @endphp
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-brand-espresso/60 backdrop-blur-xs p-4">
            <div class="bg-white w-full max-w-md rounded-2xl p-6 border border-brand-border shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                    <h3 class="text-lg font-bold text-brand-espresso">Konfirmasi Transaksi</h3>
                    <button type="button" wire:click="$set('showConfirmTransactionModal', false)"
                        class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <div class="p-4 rounded-xl border border-brand-border bg-neutral-50 text-center space-y-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-brand-warm-gray">
                        {{ $type === 'income' ? 'Pemasukan' : ($type === 'prive' ? 'Prive' : 'Pengeluaran') }}
                    </span>
                    <p class="text-2xl font-mono font-extrabold text-brand-espresso">
                        {{ $type === 'income' ? '+' : '-' }} Rp {{ number_format((float) $amount, 0, ',', '.') }}
                    </p>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                        <span class="text-brand-warm-gray">Tanggal:</span>
                        <span
                            class="font-bold text-brand-espresso">{{ $transaction_date ? \Carbon\Carbon::parse($transaction_date)->format('d/m/Y') : '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                        <span class="text-brand-warm-gray">Akun Kas:</span>
                        <span class="font-bold text-brand-espresso">{{ $selectedAccount?->name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                        <span class="text-brand-warm-gray">Kategori:</span>
                        <span class="font-bold text-brand-espresso">{{ $category }}</span>
                    </div>
                    @if ($description)
                        <div class="flex justify-between py-1 border-b border-brand-border/40">
                            <span class="text-brand-warm-gray">Keterangan:</span>
                            <span class="font-medium text-brand-espresso text-right">{{ $description }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between py-1 text-xs text-brand-warm-gray">
                        <span>Estimasi Saldo Baru:</span>
                        <span class="font-mono font-bold text-brand-espresso text-sm">Rp
                            {{ number_format($newBalance, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-border">
                    <button type="button" wire:click="$set('showConfirmTransactionModal', false)"
                        class="px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                        Ubah
                    </button>
                    <button type="button" wire:click="saveTransaction" wire:loading.attr="disabled"
                        class="px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                        <span wire:loading.remove wire:target="saveTransaction">Simpan Transaksi</span>
                        <span wire:loading wire:target="saveTransaction">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Form Tambah Akun Kas -->
    @if ($showAccountModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-brand-espresso/60 backdrop-blur-xs p-4">
            <div class="bg-white w-full max-w-md rounded-2xl p-6 border border-brand-border shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                    <h3 class="text-lg font-bold text-brand-espresso">Tambah Akun Kas Baru</h3>
                    <button type="button" wire:click="$set('showAccountModal', false)"
                        class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <form wire:submit="saveAccount" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                            Nama Akun / Rekening <span class="text-red-600">*</span>
                        </label>
                        <input type="text" wire:model="account_name"
                            placeholder="Misal: Kas Toko, Rekening Mandiri..."
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                        @error('account_name')
                            <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                            Jenis Akun <span class="text-red-600">*</span>
                        </label>
                        <select wire:model="account_type"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                            <option value="business">Kas Usaha (Operasional / Hasil Tagihan)</option>
                            <option value="personal">Kas Pribadi (Kebutuhan Belanja Keluarga)</option>
                        </select>
                        @error('account_type')
                            <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div x-data="{
                        formatRupiah(val) {
                            if (!val && val !== 0) return '';
                            let clean = val.toString().replace(/[^0-9]/g, '').replace(/^0+/, '');
                            if (!clean) return '';
                            return 'Rp ' + clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        },
                        cleanNumber(val) {
                            let clean = (val || '').toString().replace(/[^0-9]/g, '').replace(/^0+/, '');
                            return clean ? parseInt(clean, 10) : 0;
                        },
                        onInput(e) {
                            let oldVal = e.target.value;
                            let oldPos = e.target.selectionEnd || oldVal.length;
                            let charsFromRight = oldVal.length - oldPos;

                            let raw = this.cleanNumber(oldVal);
                            let formatted = raw > 0 ? this.formatRupiah(raw) : '';
                            e.target.value = formatted;

                            if (formatted) {
                                let newPos = Math.max(3, formatted.length - charsFromRight);
                                e.target.setSelectionRange(newPos, newPos);
                            }

                            $wire.set('initial_balance', raw, false);
                            if (this.$refs.hiddenInitialBalance) {
                                this.$refs.hiddenInitialBalance.value = raw;
                                this.$refs.hiddenInitialBalance.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        },
                        onBlur(e) {
                            let raw = this.cleanNumber(e.target.value);
                            e.target.value = raw > 0 ? this.formatRupiah(raw) : '';
                            $wire.set('initial_balance', raw, false);
                            if (this.$refs.hiddenInitialBalance) {
                                this.$refs.hiddenInitialBalance.value = raw;
                                this.$refs.hiddenInitialBalance.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        },
                        init() {
                            let current = @js($initial_balance);
                            let input = this.$refs.displayInitialBalance;
                            if (current && current > 0) {
                                input.value = this.formatRupiah(current);
                            } else {
                                input.value = '';
                            }
                            this.$watch('$wire.initial_balance', (newVal) => {
                                let raw = this.cleanNumber(newVal);
                                let currentRaw = this.cleanNumber(input.value);
                                if (raw !== currentRaw) {
                                    input.value = raw > 0 ? this.formatRupiah(raw) : '';
                                }
                            });
                        }
                    }">
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                            Saldo Awal (Rp) <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                x-ref="displayInitialBalance"
                                inputmode="numeric"
                                autocomplete="off"
                                @input="onInput($event)"
                                @blur="onBlur($event)"
                                placeholder="Rp 0"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl font-mono text-base font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition" />
                            <input type="hidden" wire:model="initial_balance" x-ref="hiddenInitialBalance" />
                        </div>
                        @error('initial_balance')
                            <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                            Keterangan
                        </label>
                        <input type="text" wire:model="account_description"
                            placeholder="Catatan rekening (opsional)..."
                            class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-border">
                        <button type="button" wire:click="$set('showAccountModal', false)"
                            class="px-4 py-2.5 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                            Simpan Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Konfirmasi Hapus Transaksi -->
    @if ($deletingTransactionId)
        <div class="fixed inset-0 z-60 flex items-center justify-center bg-brand-espresso/60 backdrop-blur-xs p-4">
            <div class="bg-white w-full max-w-sm rounded-2xl p-6 border border-brand-border shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                    <h3 class="text-lg font-bold text-brand-espresso">Hapus Transaksi Kas</h3>
                    <button type="button" wire:click="cancelDelete"
                        class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <p class="text-sm text-brand-warm-gray leading-relaxed">
                    Apakah Anda yakin ingin membatalkan transaksi ini? Saldo rekening kas dan ayat jurnal terkait akan
                    dikembalikan ke posisi semula.
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" wire:click="cancelDelete"
                        class="px-4 py-2 rounded-xl font-bold text-sm text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" wire:click="deleteTransaction"
                        class="px-4 py-2 rounded-xl font-bold text-sm text-white bg-red-600 hover:bg-red-700 transition cursor-pointer">
                        Hapus Transaksi
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Detail Transaksi Kas (Minimalis, Bersih & Mudah Dibaca Orang Tua) -->
    @if ($showDetailModal && $selectedTransaction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 sm:p-6 overflow-y-auto"
            x-data
            @keydown.escape.window="$wire.closeDetailModal()">
            <div class="bg-white w-full max-w-xl rounded-2xl p-6 sm:p-8 border border-neutral-300 shadow-2xl space-y-6 text-neutral-900 my-8"
                @click.outside="$wire.closeDetailModal()">

                <!-- Header Modal -->
                <div class="flex items-center justify-between pb-4 border-b border-neutral-200">
                    <h3 class="text-xl sm:text-2xl font-bold tracking-tight text-neutral-900">
                        Detail Transaksi Kas
                    </h3>
                    <button type="button" wire:click="closeDetailModal"
                        class="text-neutral-400 hover:text-neutral-900 transition p-1 cursor-pointer"
                        title="Tutup Modal">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Nominal & Jenis Transaksi (Besar & Kontras Tinggi) -->
                <div class="p-5 bg-neutral-50 rounded-xl border border-neutral-200 space-y-1">
                    <p class="text-sm font-semibold text-neutral-500 uppercase tracking-wider">
                        @if ($selectedTransaction->type === 'income')
                            Pemasukan Kas
                        @elseif ($selectedTransaction->type === 'expense')
                            Pengeluaran Kas
                        @elseif ($selectedTransaction->type === 'prive')
                            Penarikan Prive
                        @endif
                    </p>
                    <p class="text-3xl sm:text-4xl font-extrabold font-mono text-neutral-900 tracking-tight">
                        {{ $selectedTransaction->type === 'income' ? '+' : '-' }} Rp {{ number_format($selectedTransaction->amount, 0, ',', '.') }}
                    </p>
                </div>

                <!-- Informasi Pokok Transaksi -->
                <div class="divide-y divide-neutral-200 border-y border-neutral-200 text-base">
                    <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                        <span class="text-neutral-500 font-medium">Tanggal Transaksi</span>
                        <span class="font-bold text-neutral-900">{{ $selectedTransaction->transaction_date->translatedFormat('l, d F Y') }}</span>
                    </div>
                    <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                        <span class="text-neutral-500 font-medium">Kategori Pos</span>
                        <span class="font-bold text-neutral-900">{{ $selectedTransaction->category }}</span>
                    </div>
                    <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                        <span class="text-neutral-500 font-medium">Rekening / Akun Kas</span>
                        <span class="font-bold text-neutral-900">{{ $selectedTransaction->account?->name }}</span>
                    </div>
                    <div class="py-3 flex flex-col gap-1.5">
                        <span class="text-neutral-500 font-medium">Keterangan Lengkap</span>
                        <span class="font-medium text-neutral-800 leading-relaxed">{{ $selectedTransaction->description ?: '-' }}</span>
                    </div>
                </div>

                <!-- Rincian Bahan Baku (Jika Transaksi Pembelian atau Pelunasan Hutang Supplier) -->
                @if ($selectedTransaction->purchase && $selectedTransaction->purchase->items->isNotEmpty())
                    <div class="space-y-3 pt-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-base font-bold text-neutral-900">
                                Rincian Bahan Baku yang Dibeli
                            </h4>
                            <span class="text-xs text-neutral-500 font-mono">
                                {{ $selectedTransaction->purchase->purchase_number }}
                            </span>
                        </div>
                        <p class="text-sm text-neutral-600">
                            Supplier: <strong class="text-neutral-900">{{ $selectedTransaction->purchase->supplier_name }}</strong>
                        </p>
                        <div class="border border-neutral-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-neutral-100 text-neutral-700 font-semibold border-b border-neutral-200">
                                    <tr>
                                        <th class="py-2.5 px-3">Bahan Baku</th>
                                        <th class="py-2.5 px-3 text-right">Jumlah</th>
                                        <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                                        <th class="py-2.5 px-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-200">
                                    @foreach ($selectedTransaction->purchase->items as $pItem)
                                        <tr>
                                            <td class="py-2.5 px-3 font-medium text-neutral-900">
                                                {{ $pItem->rawMaterial?->name }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono text-neutral-800">
                                                {{ number_format($pItem->quantity, (floor($pItem->quantity) == $pItem->quantity ? 0 : 2), ',', '.') }}
                                                {{ $pItem->rawMaterial?->display_unit }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono text-neutral-800">
                                                Rp {{ number_format($pItem->cost_per_unit, 0, ',', '.') }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-neutral-900">
                                                Rp {{ number_format($pItem->subtotal, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Rincian Pelunasan Faktur Toko -->
                @if ($selectedTransaction->invoicePayment && $selectedTransaction->invoicePayment->invoice)
                    <div class="space-y-3 pt-1 border-t border-neutral-200">
                        <h4 class="text-base font-bold text-neutral-900">
                            Rincian Faktur Toko Mitra
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-neutral-500 block text-xs">Toko Mitra:</span>
                                <span class="font-bold text-neutral-900">{{ $selectedTransaction->invoicePayment->invoice->store?->name }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-500 block text-xs">Nomor Faktur:</span>
                                <span class="font-bold text-neutral-900 font-mono">{{ $selectedTransaction->invoicePayment->invoice->invoice_number }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-500 block text-xs">No. Pembayaran:</span>
                                <span class="font-bold text-neutral-900 font-mono">{{ $selectedTransaction->invoicePayment->payment_number }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-500 block text-xs">Metode Pembayaran:</span>
                                <span class="font-bold text-neutral-900">{{ strtoupper(str_replace('_', ' ', $selectedTransaction->invoicePayment->payment_method)) }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Rincian Aset Tetap -->
                @if ($selectedTransaction->fixedAsset)
                    <div class="space-y-3 pt-1 border-t border-neutral-200">
                        <h4 class="text-base font-bold text-neutral-900">
                            Rincian Aset Tetap
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-neutral-500 block text-xs">Nama Aset:</span>
                                <span class="font-bold text-neutral-900">{{ $selectedTransaction->fixedAsset->name }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-500 block text-xs">Kode Aset:</span>
                                <span class="font-bold text-neutral-900 font-mono">{{ $selectedTransaction->fixedAsset->asset_code }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-500 block text-xs">Lokasi:</span>
                                <span class="font-bold text-neutral-900">{{ $selectedTransaction->fixedAsset->location ?: '-' }}</span>
                            </div>
                            <div>
                                <span class="text-neutral-500 block text-xs">Kondisi:</span>
                                <span class="font-bold text-neutral-900 capitalize">{{ str_replace('_', ' ', $selectedTransaction->fixedAsset->condition) }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Tombol Tutup Besar & Nyaman -->
                <div class="pt-2">
                    <button type="button" wire:click="closeDetailModal"
                        class="w-full py-3.5 px-6 rounded-xl font-bold text-base text-white bg-neutral-900 hover:bg-neutral-800 transition cursor-pointer text-center">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
