<div class="space-y-6">

    {{-- Header --}}
    <div>
        <nav aria-label="Breadcrumb"
            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Aset</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Aset Tetap Usaha</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
            Aset Tetap Usaha
        </h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
            Inventaris aset usaha: mesin produksi, peralatan dapur, kendaraan, dan inventaris lainnya.
        </p>
    </div>

    {{-- Action Button --}}
    @can('aset-create')
        <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                <i class="ti ti-plus text-base"></i>
                <span>Catat Aset Baru</span>
            </button>
        </div>
    @endcan

    {{-- Flash --}}
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

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Total Nilai Aset --}}
        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-brand-border/60">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Total Nilai Aset</h2>
                    <p class="text-xs text-brand-warm-gray">Nilai buku aset aktif & rusak ringan</p>
                </div>
                <span class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Aset Tetap</span>
            </div>
            <p class="text-3xl font-mono font-extrabold text-brand-espresso">
                Rp {{ number_format($totalValue, 0, ',', '.') }}
            </p>
        </div>

        {{-- Jumlah Aset --}}
        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-brand-border/60">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Total Item Aset</h2>
                    <p class="text-xs text-brand-warm-gray">Semua aset yang tercatat</p>
                </div>
                <span class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Inventaris</span>
            </div>
            <p class="text-3xl font-mono font-extrabold text-brand-espresso">{{ $totalAssets }} item</p>
        </div>

        {{-- Aset Aktif --}}
        <div class="p-5 rounded-xl border border-brand-border bg-white space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-brand-border/60">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Aset Aktif</h2>
                    <p class="text-xs text-brand-warm-gray">Kondisi baik & rusak ringan</p>
                </div>
                <span class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Operasional</span>
            </div>
            <p class="text-3xl font-mono font-extrabold text-brand-espresso">{{ $activeCount }} item</p>
        </div>
    </div>

    {{-- Filter & Table Card --}}
    <div class="bg-white rounded-2xl border border-brand-border overflow-hidden">
        {{-- Filter Bar --}}
        <div class="p-4 border-b border-brand-border flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-50">
                <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-brand-warm-gray text-sm"></i>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama, kode, atau lokasi aset..."
                    class="w-full pl-9 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
            </div>
            <select wire:model.live="categoryFilter"
                class="px-3 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="conditionFilter"
                class="px-3 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                <option value="">Semua Kondisi</option>
                @foreach ($conditions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @if ($hasActiveFilters)
                <button type="button" wire:click="resetFilters"
                    class="px-3 py-2.5 rounded-xl text-xs font-bold text-brand-warm-gray border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                    Reset Filter
                </button>
            @endif
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 border-b border-brand-border">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                            Nama Aset</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                            Kategori</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-brand-warm-gray hidden sm:table-cell">
                            Tgl Perolehan</th>
                        <th
                            class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                            Harga Perolehan</th>
                        <th
                            class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-brand-warm-gray hidden md:table-cell">
                            Nilai Buku</th>
                        <th
                            class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                            Kondisi</th>
                        <th
                            class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-brand-warm-gray">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border/50">
                    @forelse($assets as $asset)
                        <tr class="hover:bg-neutral-50/60 transition-colors">
                            <td class="px-4 py-3.5">
                                <div>
                                    <p class="font-bold text-brand-espresso">{{ $asset->name }}</p>
                                    @if ($asset->asset_code)
                                        <p class="text-xs text-brand-warm-gray font-mono">{{ $asset->asset_code }}</p>
                                    @endif
                                    @if ($asset->location)
                                        <p class="text-xs text-brand-warm-gray mt-0.5">
                                            <i class="ti ti-map-pin text-xs"></i> {{ $asset->location }}
                                        </p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-xs font-bold text-brand-espresso">
                                    {{ $categories[$asset->category] ?? $asset->category }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 hidden sm:table-cell">
                                <span class="text-sm font-semibold text-brand-espresso">
                                    {{ $asset->purchase_date->format('d/m/Y') }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <span class="font-mono font-bold text-brand-espresso text-sm">
                                    Rp {{ number_format($asset->purchase_price, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right hidden md:table-cell">
                                <span
                                    class="font-mono font-semibold text-sm {{ $asset->book_value < $asset->purchase_price ? 'text-brand-warm-gray' : 'text-brand-espresso' }}">
                                    Rp {{ number_format($asset->book_value, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @php
                                    $conditionColors = [
                                        'baik' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                                        'rusak_ringan' => 'text-amber-700 bg-amber-50 border-amber-200',
                                        'rusak_berat' => 'text-red-700 bg-red-50 border-red-200',
                                        'tidak_aktif' => 'text-neutral-600 bg-neutral-100 border-neutral-200',
                                    ];
                                    $condClass =
                                        $conditionColors[$asset->condition] ??
                                        'text-brand-espresso border-brand-border';
                                @endphp
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $condClass }}">
                                    {{ $conditions[$asset->condition] ?? $asset->condition }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($deletingId === $asset->id)
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="deleteAsset" wire:loading.attr="disabled"
                                            class="px-2.5 py-1.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg transition cursor-pointer">
                                            Hapus
                                        </button>
                                        <button wire:click="cancelDelete"
                                            class="px-2.5 py-1.5 text-xs font-bold text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 rounded-lg transition cursor-pointer">
                                            Batal
                                        </button>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center gap-1">
                                        @can('aset-edit')
                                            <button wire:click="openEditModal({{ $asset->id }})"
                                                class="p-2 text-brand-warm-gray hover:text-brand-primary rounded-lg hover:bg-neutral-100 transition cursor-pointer"
                                                title="Edit">
                                                <i class="ti ti-pencil text-sm"></i>
                                            </button>
                                        @endcan
                                        @can('aset-delete')
                                            <button wire:click="confirmDelete({{ $asset->id }})"
                                                class="p-2 text-brand-warm-gray hover:text-red-600 rounded-lg hover:bg-red-50 transition cursor-pointer"
                                                title="Hapus">
                                                <i class="ti ti-trash text-sm"></i>
                                            </button>
                                        @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-brand-warm-gray">
                                    <i class="ti ti-tools text-5xl opacity-40"></i>
                                    <p class="font-semibold text-sm">
                                        @if ($hasActiveFilters)
                                            Tidak ada aset yang cocok dengan filter.
                                        @else
                                            Belum ada aset tetap yang dicatat.
                                        @endif
                                    </p>
                                    @if (!$hasActiveFilters)
                                        @can('aset-create')
                                            <button wire:click="openCreateModal"
                                                class="mt-1 px-4 py-2 text-sm font-bold text-white bg-brand-primary rounded-xl hover:bg-brand-primary/90 transition cursor-pointer">
                                                + Catat Aset Pertama
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($assets->hasPages())
            <div class="p-4 border-t border-brand-border">
                {{ $assets->links() }}
            </div>
        @endif
    </div>

    {{-- ===== MODAL: Create/Edit Asset ===== --}}
    @if ($showAssetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data
            x-on:keydown.escape.window="$wire.set('showAssetModal', false)">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showAssetModal', false)">
            </div>

            <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh] z-10">
                {{-- Header --}}
                <div class="flex items-center justify-between p-6 border-b border-brand-border shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-brand-espresso">
                            {{ $editingId ? 'Edit Data Aset' : 'Catat Aset Baru' }}
                        </h2>
                        <p class="text-xs text-brand-warm-gray mt-0.5">
                            {{ $editingId ? 'Perbarui info & kondisi aset.' : 'Aset akan dijurnal otomatis ke COA Aset Tetap (1-2000).' }}
                        </p>
                    </div>
                    <button type="button" wire:click="$set('showAssetModal', false)"
                        class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer p-1">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 overflow-y-auto flex-1 space-y-4">

                    {{-- Nama Aset --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                            Nama Aset <span class="text-red-600">*</span>
                        </label>
                        <input type="text" wire:model="name"
                            placeholder="Contoh: Mesin Cetak Label Roll, Kompor Industri 3 Tungku..."
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                        @error('name')
                            <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kategori --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                            Kategori <span class="text-red-600">*</span>
                        </label>
                        <select wire:model="category"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>


                    @if (!$editingId)
                        {{-- Tanggal & Harga Perolehan (create only) --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                    Tanggal Perolehan <span class="text-red-600">*</span>
                                </label>
                                <input type="date" wire:model="purchase_date"
                                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                                @error('purchase_date')
                                    <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                    Harga Perolehan (Rp) <span class="text-red-600">*</span>
                                </label>
                                <input type="number" wire:model="purchase_price" min="0" step="1000"
                                    placeholder="0"
                                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                                @error('purchase_price')
                                    <span class="text-xs text-red-600 font-semibold mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Akun Pembayaran --}}
                        @if ($accounts->count() > 0)
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                    Dibayar dari Kas / Rekening
                                </label>
                                <select wire:model="account_id"
                                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                    <option value="">— Tidak dipotong dari kas (kredit modal) —</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->name }} (Saldo: Rp
                                            {{ number_format($acc->balance, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-brand-warm-gray mt-1">
                                    Jika dipilih, saldo kas akan dikurangi dan jurnal akan mencatat kredit ke kas
                                    tersebut.
                                </p>
                            </div>
                        @endif
                    @endif

                    {{-- Kondisi & Lokasi --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                Kondisi Aset <span class="text-red-600">*</span>
                            </label>
                            <select wire:model="condition"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                @foreach ($conditions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                                Lokasi
                            </label>
                            <input type="text" wire:model="location"
                                placeholder="Contoh: Dapur Produksi, Gudang..."
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-warm-gray mb-1.5">
                            Catatan / Keterangan
                        </label>
                        <textarea wire:model="notes" rows="2" placeholder="Nomor seri, merek, garansi, atau catatan lain..."
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary resize-none"></textarea>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 p-6 border-t border-brand-border shrink-0">
                    <button type="button" wire:click="$set('showAssetModal', false)"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold text-brand-espresso bg-white border border-brand-border hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" wire:click="saveAsset" wire:loading.attr="disabled"
                        class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-primary hover:bg-brand-primary/90 transition shadow-xs cursor-pointer disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveAsset">
                            {{ $editingId ? 'Simpan Perubahan' : 'Catat & Jurnal Aset' }}
                        </span>
                        <span wire:loading wire:target="saveAsset">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
