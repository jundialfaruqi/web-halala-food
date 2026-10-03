<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Operasional</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.products') }}" wire:navigate class="hover:text-brand-primary transition">Produk Jadi</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Tambah Produk</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Tambah Produk Jadi Baru</h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Daftarkan varian produk camilan kemasan baru, atur harga setor konsinyasi, dan catat stok awal gudang.</p>
    </div>

    <!-- Main Form Container -->
    <form wire:submit="save" class="space-y-6">

        <!-- Card: Informasi Produk & Harga -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-brand-espresso pb-3 border-b border-brand-border">Informasi Produk &amp; Harga Konsinyasi</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Nama Produk -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nama Produk Kemasan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="Contoh: Marie Wijen Halala 150g"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Nama lengkap produk beserta varian rasa atau gramasi kemasan.</p>
                </div>

                <!-- 2. Satuan Kemasan -->
                <div>
                    <label for="unit_id" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Satuan Kemasan <span class="text-red-500">*</span>
                    </label>
                    <select id="unit_id" wire:model="unit_id"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                        <option value="">-- Pilih Satuan Kemasan --</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">
                                {{ $unit->name }} ({{ $unit->short_name }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Satuan fisik produk (misal: Pouch, Toples, Bungkus, Box).</p>
                </div>

                <!-- 3. Harga Setor Konsinyasi -->
                <div>
                    <label for="consignment_price" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Harga Setor Konsinyasi (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-brand-warm-gray">Rp</span>
                        <input type="number" id="consignment_price" wire:model="consignment_price" step="500" min="0" placeholder="12000"
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-mono font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    </div>
                    @error('consignment_price')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Harga yang ditagihkan kepada toko mitra pada surat jalan &amp; faktur penagihan.</p>
                </div>

                <!-- 4. Harga Eceran Toko (Rekomendasi) -->
                <div>
                    <label for="retail_price" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Harga Jual Eceran Toko (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-brand-warm-gray">Rp</span>
                        <input type="number" id="retail_price" wire:model="retail_price" step="500" min="0" placeholder="15000"
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-mono text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    </div>
                    @error('retail_price')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Harga jual rekomendasi kepada konsumen akhir (keuntungan toko mitra = Eceran - Setor).</p>
                </div>

                <!-- 5. Stok Awal Siap Kirim di Gudang -->
                <div>
                    <label for="stock_ready" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Stok Awal Siap Kirim (Kemasan) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="stock_ready" wire:model="stock_ready" min="0" placeholder="0"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-mono font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('stock_ready')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Jumlah fisik produk kemasan yang saat ini sudah tersedia di gudang pabrik.</p>
                </div>

                <!-- 6. Status Produk Aktif -->
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="checkbox checkbox-primary rounded-lg">
                        <div>
                            <span class="text-sm font-semibold text-brand-espresso">Produk Aktif</span>
                            <p class="text-xs text-brand-warm-gray">Produk aktif dapat dipilih dalam formulir produksi, surat jalan, dan faktur tagihan.</p>
                        </div>
                    </label>
                </div>

                <!-- 7. Deskripsi & Catatan Kemasan -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Deskripsi / Keterangan Produk (Opsional)
                    </label>
                    <textarea id="description" wire:model="description" rows="3"
                        placeholder="Contoh: Kemasan pouch klip aluminium foil 150 gram, masa simpan 6 bulan, izin P-IRT dan sertifikasi Halal..."
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"></textarea>
                    @error('description')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Form Actions Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.products') }}" wire:navigate
                class="px-5 py-2.5 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                <span wire:loading.remove>Simpan Produk Jadi</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>

    </form>
</div>
