<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Distribusi</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.deliveries') }}" wire:navigate class="hover:text-brand-primary transition">Pengantaran</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Buat Surat Jalan</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Buat Surat Jalan Pengantaran</h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Tugaskan kurir untuk mengantar produk jadi ke toko mitra bersangkutan.</p>
    </div>

    <!-- Main Form Container -->
    <form wire:submit="save" class="space-y-6">

        <!-- Card 1: Informasi Pengantaran -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-brand-espresso pb-3 border-b border-brand-border">Informasi Dokumen &amp; Tujuan</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Nomor Surat Jalan -->
                <div>
                    <label for="delivery_number" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nomor Surat Jalan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="delivery_number" wire:model="delivery_number"
                        class="w-full px-4 py-2.5 bg-neutral-50 border border-brand-border rounded-xl text-sm font-mono font-bold text-brand-espresso focus:outline-none focus:border-brand-primary focus:bg-white transition"
                        placeholder="SJ-20261003-0001">
                    @error('delivery_number')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 2. Tanggal Pengantaran -->
                <div>
                    <label for="delivery_date" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Tanggal Pengantaran <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="delivery_date" wire:model="delivery_date"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('delivery_date')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 3. Toko Mitra Tujuan -->
                <div>
                    <label for="store_id" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Toko Mitra Tujuan <span class="text-red-500">*</span>
                    </label>
                    <select id="store_id" wire:model.live="store_id"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                        <option value="">-- Pilih Toko Mitra --</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">
                                {{ $store->name }} {{ $store->route ? "({$store->route})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('store_id')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror

                    <!-- Selected Store Info Box -->
                    @if ($selectedStore)
                        <div class="mt-3 p-3.5 bg-neutral-50 rounded-xl border border-brand-border/80 text-xs space-y-1">
                            <div class="font-bold text-brand-espresso">{{ $selectedStore->name }}</div>
                            <div class="text-brand-warm-gray">{{ $selectedStore->address ?? 'Alamat belum diatur' }}</div>
                            <div class="flex items-center gap-3 pt-1 text-brand-warm-gray">
                                @if ($selectedStore->route)
                                    <span>Rute: <strong class="text-brand-espresso">{{ $selectedStore->route }}</strong></span>
                                @endif
                                @if ($selectedStore->owner_name)
                                    <span>Pemilik: {{ $selectedStore->owner_name }}</span>
                                @endif
                                @if ($selectedStore->phone)
                                    <span>Tel: {{ $selectedStore->phone }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 4. Kurir Bertugas -->
                <div>
                    <label for="courier_id" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Kurir yang Ditugaskan
                    </label>
                    <select id="courier_id" wire:model="courier_id"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                        <option value="">-- Belum Ditugaskan / Pilih Kurir --</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier->id }}">
                                {{ $courier->name }} {{ $courier->phone ? "({$courier->phone})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('courier_id')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Kurir dapat melihat surat jalan ini di dashboard dan aplikasinya.</p>
                </div>

                <!-- 5. Catatan Pengantaran -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Catatan Pengantaran (Opsional)
                    </label>
                    <textarea id="notes" wire:model="notes" rows="2"
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"
                        placeholder="Contoh: Titip ke kasir utama, tagihan konsinyasi ditagihkan bulan depan..."></textarea>
                    @error('notes')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Card 2: Daftar Produk yang Dikirim -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Daftar Produk yang Dikirim</h2>
                    <p class="text-xs text-brand-warm-gray mt-0.5">Pilih produk jadi yang dibawa kurir dari gudang.</p>
                </div>

                <button type="button" wire:click="addItem"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 border border-brand-border rounded-xl text-xs font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                    <i class="ti ti-plus text-sm"></i>
                    <span>Tambah Baris</span>
                </button>
            </div>

            <!-- Table Items -->
            <div class="border border-brand-border rounded-xl overflow-hidden">
                <table class="w-full text-left text-sm text-brand-espresso">
                    <thead class="bg-neutral-50 border-b border-brand-border text-xs uppercase tracking-wider font-semibold text-brand-warm-gray">
                        <tr>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">Produk Jadi</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-36">Stok Ready</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-36">Jumlah Kirim</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-44">Harga Konsinyasi</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-40 text-right">Subtotal</th>
                            <th scope="col" class="px-3 py-3 w-12 text-center whitespace-nowrap"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border/60">
                        @foreach ($items as $index => $item)
                            <tr class="hover:bg-neutral-50/50 transition">
                                <!-- 1. Produk -->
                                <td class="px-4 py-3">
                                    <select wire:model.live="items.{{ $index }}.product_id"
                                        class="w-full px-3 py-1.5 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->name }} (Stok: {{ $product->stock_ready }} {{ $product->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.{$index}.product_id")
                                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- 2. Stok Ready -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-mono font-medium text-brand-warm-gray">
                                        {{ $item['stock_ready'] }} kemasan
                                    </span>
                                </td>

                                <!-- 3. Jumlah Kirim -->
                                <td class="px-4 py-3">
                                    <input type="number" min="1" wire:model.live="items.{{ $index }}.quantity"
                                        class="w-full px-3 py-1.5 bg-white border border-brand-border rounded-lg text-sm font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                    @error("items.{$index}.quantity")
                                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- 4. Harga Satuan -->
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-brand-warm-gray">Rp</span>
                                        <input type="number" step="500" min="0" wire:model.live="items.{{ $index }}.unit_price"
                                            class="w-full pl-8 pr-3 py-1.5 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso focus:outline-none focus:border-brand-primary font-mono">
                                    </div>
                                    @error("items.{$index}.unit_price")
                                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- 5. Subtotal -->
                                <td class="px-4 py-3 text-right whitespace-nowrap font-mono font-semibold text-brand-espresso">
                                    Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}
                                </td>

                                <!-- 6. Hapus Baris -->
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    @if (count($items) > 1)
                                        <button type="button" wire:click="removeItem({{ $index }})"
                                            class="size-7 rounded flex items-center justify-center text-brand-warm-gray hover:text-red-700 hover:bg-red-50 transition cursor-pointer"
                                            title="Hapus baris">
                                            <i class="ti ti-x text-sm"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-neutral-50 border-t border-brand-border">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-brand-espresso">
                                Total Muatan &amp; Nilai Barang
                            </td>
                            <td class="px-4 py-3 font-bold text-brand-espresso font-mono whitespace-nowrap">
                                {{ number_format($this->totalQuantity, 0, ',', '.') }} kemasan
                            </td>
                            <td></td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-brand-espresso whitespace-nowrap text-base">
                                Rp {{ number_format($this->totalAmount, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @error('items')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- Form Actions Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.deliveries') }}" wire:navigate
                class="px-5 py-2.5 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                <span wire:loading.remove>Simpan &amp; Buat Surat Jalan</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>

    </form>
</div>
