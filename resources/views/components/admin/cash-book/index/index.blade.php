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
            Pencatatan arus kas operasional, saldo rekening usaha, dan pemisahan dana belanja dapur pribadi keluarga.
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
                <span>⇄ Tarik Prive Keluarga</span>
            </button>
        </div>
    @endcan

    <!-- Alert Flash Notification -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl border border-brand-border bg-white text-brand-espresso text-sm font-medium flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button type="button" @click="$el.parentElement.remove()" class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                <i class="ti ti-x"></i>
            </button>
        </div>
    @endif

    <!-- Cards Saldo Kas Usaha vs Saldo Pribadi -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Saldo Kas Usaha -->
        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-brand-border/60">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Total Saldo Kas Usaha</h2>
                    <p class="text-xs text-brand-warm-gray">Modal dagang, penerimaan tagihan &amp; biaya operasional</p>
                </div>
                <span class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Kas Usaha</span>
            </div>

            <p class="text-3xl font-mono font-extrabold text-brand-espresso">
                Rp {{ number_format($totalBusinessBalance, 0, ',', '.') }}
            </p>

            <div class="space-y-1.5 pt-2 border-t border-brand-border/40 text-sm">
                @foreach($accounts->where('type', 'business') as $acc)
                    <div class="flex justify-between items-center py-1">
                        <span class="text-brand-warm-gray font-medium">{{ $acc->name }}</span>
                        <span class="font-mono font-bold text-brand-espresso">Rp {{ number_format($acc->balance, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Saldo Kas Pribadi / Keluarga -->
        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-brand-border/60">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Total Kas Belanja Pribadi</h2>
                    <p class="text-xs text-brand-warm-gray">Kebutuhan dapur, rumah tangga &amp; keperluan non-usaha</p>
                </div>
                <span class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Kas Pribadi</span>
            </div>

            <p class="text-3xl font-mono font-extrabold text-brand-espresso">
                Rp {{ number_format($totalPersonalBalance, 0, ',', '.') }}
            </p>

            <div class="space-y-1.5 pt-2 border-t border-brand-border/40 text-sm">
                @foreach($accounts->where('type', 'personal') as $acc)
                    <div class="flex justify-between items-center py-1">
                        <span class="text-brand-warm-gray font-medium">{{ $acc->name }}</span>
                        <span class="font-mono font-bold text-brand-espresso">Rp {{ number_format($acc->balance, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Search -->
            <div class="relative">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg pointer-events-none"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari kategori, keterangan..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Type Filter -->
            <div>
                <select wire:model.live="typeFilter"
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="all">Semua Jenis Transaksi</option>
                    <option value="income">Pemasukan Usaha (+)</option>
                    <option value="expense">Pengeluaran Usaha (-)</option>
                    <option value="prive">Tarik Prive Keluarga (⇄)</option>
                    <option value="personal_expense">Pengeluaran Pribadi (-)</option>
                </select>
            </div>

            <!-- Account Filter -->
            <div>
                <select wire:model.live="accountFilter"
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="">Semua Akun &amp; Rekening</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type === 'business' ? 'Usaha' : 'Pribadi' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Quick Period Buttons -->
            <div class="flex items-center gap-1.5">
                <button type="button" wire:click="setQuickDate('today')"
                    class="flex-1 py-2.5 px-3 rounded-xl text-xs font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer text-center">
                    Hari Ini
                </button>
                <button type="button" wire:click="setQuickDate('this_month')"
                    class="flex-1 py-2.5 px-3 rounded-xl text-xs font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer text-center">
                    Bulan Ini
                </button>
                <button type="button" wire:click="setQuickDate('all')"
                    class="flex-1 py-2.5 px-3 rounded-xl text-xs font-semibold border border-brand-border bg-white text-brand-espresso hover:bg-neutral-50 transition cursor-pointer text-center">
                    Semua
                </button>
            </div>
        </div>

        @if($hasActiveFilters)
            <div class="p-3.5 bg-neutral-50 rounded-xl border border-brand-border flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm">
                <div class="flex items-center gap-2 text-brand-warm-gray">
                    <span class="font-bold text-brand-espresso">{{ $transactions->total() }}</span> transaksi ditemukan
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <span class="text-brand-warm-gray">Pemasukan:</span>
                        <span class="font-mono font-bold text-brand-espresso ml-1">+ Rp {{ number_format($filteredIncome, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-brand-warm-gray">Pengeluaran:</span>
                        <span class="font-mono font-bold text-brand-espresso ml-1">- Rp {{ number_format($filteredExpense, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-brand-warm-gray">Prive:</span>
                        <span class="font-mono font-bold text-brand-espresso ml-1">⇄ Rp {{ number_format($filteredPrive, 0, ',', '.') }}</span>
                    </div>
                    <button type="button" wire:click="resetFilters" class="text-brand-primary hover:underline font-bold cursor-pointer">
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
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
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
                    <tr class="hover:bg-neutral-50/50 transition-colors">
                        <td class="py-4 px-6 font-mono font-bold text-brand-espresso whitespace-nowrap">
                            {{ $trx->transaction_date->format('d/m/Y') }}
                        </td>
                        <td class="py-4 px-6">
                            <p class="font-bold text-brand-espresso">{{ $trx->category }}</p>
                            <p class="text-xs text-brand-warm-gray uppercase tracking-wider mt-0.5">
                                @if($trx->type === 'income')
                                    Pemasukan Usaha
                                @elseif($trx->type === 'expense')
                                    Pengeluaran Usaha
                                @elseif($trx->type === 'prive')
                                    Tarik Prive Keluarga
                                @else
                                    Pengeluaran Pribadi
                                @endif
                            </p>
                        </td>
                        <td class="py-4 px-6 text-brand-espresso font-medium whitespace-nowrap">
                            {{ $trx->account->name }}
                        </td>
                        <td class="py-4 px-6 text-brand-warm-gray">
                            {{ $trx->description ?: '-' }}
                        </td>
                        <td class="py-4 px-6 text-right font-mono font-extrabold text-base whitespace-nowrap text-brand-espresso">
                            {{ $trx->type === 'income' ? '+' : '-' }} Rp {{ number_format($trx->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            @can('buku-kas-delete')
                                <button type="button" wire:click="confirmDeleteTransaction({{ $trx->id }})"
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

    @if($transactions->hasPages())
        <div class="pt-2">
            {{ $transactions->links() }}
        </div>
    @endif

    <!-- Modal Form Catat Transaksi Kas -->
    @if($showTransactionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-brand-espresso/60 backdrop-blur-xs p-4">
            <div class="bg-white w-full max-w-xl max-h-[90vh] flex flex-col rounded-2xl border border-brand-border shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border bg-neutral-50/50 shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-brand-espresso">Catat Transaksi Kas</h3>
                        <p class="text-xs text-brand-warm-gray">Catat pemasukan, pengeluaran, atau penarikan kas usaha</p>
                    </div>
                    <button type="button" wire:click="$set('showTransactionModal', false)"
                        class="text-brand-warm-gray hover:text-brand-espresso text-xl font-bold cursor-pointer">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <form wire:submit="prepareTransactionConfirmation" class="flex flex-col flex-1 overflow-hidden min-h-0">
                    <div class="p-6 overflow-y-auto space-y-4 flex-1">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                    Tanggal Transaksi <span class="text-red-600">*</span>
                                </label>
                                <input type="date" wire:model="transaction_date"
                                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                                @error('transaction_date') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                    Jenis Transaksi <span class="text-red-600">*</span>
                                </label>
                                <select wire:model.live="type"
                                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                    <option value="expense">Pengeluaran Usaha (-)</option>
                                    <option value="income">Pemasukan Usaha (+)</option>
                                    <option value="prive">Tarik Uang untuk Keluarga (Prive)</option>
                                    <option value="personal_expense">Pengeluaran Pribadi (-)</option>
                                </select>
                                @error('type') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                Akun / Rekening Kas <span class="text-red-600">*</span>
                            </label>
                            <select wire:model="account_id"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">
                                        {{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('account_id') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Pilihan Kategori Cepat -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                                Kategori / Pos Transaksi <span class="text-red-600">*</span>
                            </label>
                            <div class="flex flex-wrap gap-2">
                                @if($type === 'income')
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
                                    <button type="button" wire:click="$set('category', 'Operasional Lainnya')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer {{ $category === 'Operasional Lainnya' ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white text-brand-espresso border-brand-border hover:bg-neutral-50' }}">
                                        Operasional Lain
                                    </button>
                                @elseif($type === 'prive')
                                    <button type="button" wire:click="$set('category', 'Pengambilan Uang Usaha untuk Keluarga (Prive)')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer bg-brand-primary text-white border-brand-primary">
                                        Prive Keluarga
                                    </button>
                                @elseif($type === 'personal_expense')
                                    <button type="button" wire:click="$set('category', 'Kebutuhan Dapur & Belanja Rumah')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer bg-brand-primary text-white border-brand-primary">
                                        Belanja Rumah Tangga
                                    </button>
                                @endif
                            </div>

                            <input type="text" wire:model="category"
                                placeholder="Ketik atau pilih nama kategori..."
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                            @error('category') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                Nominal Transaksi (Rp) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" step="any" min="1" wire:model="amount"
                                placeholder="0"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl font-mono text-base font-bold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                            @error('amount') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                Keterangan Tambahan
                            </label>
                            <textarea wire:model="description" rows="2"
                                placeholder="Catatan transaksi (opsional)..."
                                class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary"></textarea>
                            @error('description') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-brand-border bg-neutral-50/50 shrink-0">
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
    @if($showConfirmTransactionModal)
        @php
            $selectedAccount = $accounts->firstWhere('id', $account_id);
            $currentBalance = $selectedAccount?->balance ?? 0;
            $newBalance = $type === 'income' ? ($currentBalance + (float) $amount) : ($currentBalance - (float) $amount);
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
                        {{ $type === 'income' ? 'Pemasukan' : ($type === 'prive' ? 'Tarik Prive' : 'Pengeluaran') }}
                    </span>
                    <p class="text-2xl font-mono font-extrabold text-brand-espresso">
                        {{ $type === 'income' ? '+' : '-' }} Rp {{ number_format((float) $amount, 0, ',', '.') }}
                    </p>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                        <span class="text-brand-warm-gray">Tanggal:</span>
                        <span class="font-bold text-brand-espresso">{{ $transaction_date ? \Carbon\Carbon::parse($transaction_date)->format('d/m/Y') : '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                        <span class="text-brand-warm-gray">Akun Kas:</span>
                        <span class="font-bold text-brand-espresso">{{ $selectedAccount?->name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-brand-border/40">
                        <span class="text-brand-warm-gray">Kategori:</span>
                        <span class="font-bold text-brand-espresso">{{ $category }}</span>
                    </div>
                    @if($description)
                        <div class="flex justify-between py-1 border-b border-brand-border/40">
                            <span class="text-brand-warm-gray">Keterangan:</span>
                            <span class="font-medium text-brand-espresso text-right">{{ $description }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between py-1 text-xs text-brand-warm-gray">
                        <span>Estimasi Saldo Baru:</span>
                        <span class="font-mono font-bold text-brand-espresso text-sm">Rp {{ number_format($newBalance, 0, ',', '.') }}</span>
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
    @if($showAccountModal)
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
                        @error('account_name') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
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
                        @error('account_type') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                            Saldo Awal (Rp) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" step="any" min="0" wire:model="initial_balance"
                            placeholder="0"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl font-mono text-base font-bold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                        @error('initial_balance') <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span> @enderror
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
    @if($deletingTransactionId)
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
                    Apakah Anda yakin ingin membatalkan transaksi ini? Saldo rekening kas dan ayat jurnal terkait akan dikembalikan ke posisi semula.
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

</div>
