<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header & Breadcrumb -->
    <div class="space-y-1">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.units') }}" class="hover:text-brand-primary transition">Master Satuan</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Tambah Satuan Baru</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
            Tambah Satuan Baru
        </h1>
        <p class="text-sm sm:text-base text-brand-warm-gray">
            Tambahkan satuan pengukuran baru untuk digunakan pada bahan baku, resep, atau produk jadi.
        </p>
    </div>

    <!-- Form Card -->
    <div class="bg-white border border-brand-border rounded-2xl shadow-xs p-6 sm:p-8">
        <form wire:submit="save" class="space-y-6">
            
            <!-- Nama Satuan -->
            <div>
                <label for="name" class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Nama Satuan <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" wire:model="name" placeholder="Misal: Kilogram, Mililiter, Pieces, Lusin"
                    class="w-full px-4 py-2.5 bg-white border {{ $errors->has('name') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition">
                @error('name')
                    <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
                <p class="text-xs text-brand-warm-gray mt-1">Nama lengkap satuan yang mudah dikenali saat penginputan data.</p>
            </div>

            <!-- Simbol / Singkatan -->
            <div>
                <label for="short_name" class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Simbol / Singkatan <span class="text-red-500">*</span>
                </label>
                <div class="relative max-w-xs">
                    <input type="text" id="short_name" wire:model="short_name" placeholder="Misal: kg, ml, pcs, lsn"
                        class="w-full px-4 py-2.5 font-mono bg-white border {{ $errors->has('short_name') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition uppercase sm:lowercase">
                </div>
                @error('short_name')
                    <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
                <p class="text-xs text-brand-warm-gray mt-1">Singkatan unik yang akan dicetak pada label barcode, kemasan, atau resep.</p>
            </div>

            <!-- Keterangan / Deskripsi -->
            <div>
                <label for="description" class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Keterangan <span class="text-xs font-normal text-brand-warm-gray">(Opsional)</span>
                </label>
                <textarea id="description" wire:model="description" rows="3" placeholder="Misal: Satuan baku untuk bahan tepung dan gula padat"
                    class="w-full px-4 py-2.5 bg-white border {{ $errors->has('description') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition"></textarea>
                @error('description')
                    <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Status Aktif Switch -->
            <div class="pt-2 border-t border-brand-border/60">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="checkbox checkbox-primary rounded-lg">
                    <div>
                        <span class="text-sm font-bold text-brand-espresso">Satuan Aktif</span>
                        <p class="text-xs text-brand-warm-gray">Satuan aktif dapat dipilih dalam form bahan baku, resep, dan produk.</p>
                    </div>
                </label>
            </div>

            <!-- Buttons -->
            <div class="pt-4 border-t border-brand-border/60 flex items-center justify-end gap-3">
                <a href="{{ route('admin.units') }}"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-base font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                    Batal
                </a>
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                    <span wire:loading class="loading loading-spinner loading-xs"></span>
                    <span>Simpan Satuan</span>
                </button>
            </div>

        </form>
    </div>

</div>
