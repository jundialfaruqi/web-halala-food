<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Master</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.units') }}" wire:navigate class="hover:text-brand-primary transition">Satuan</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Ubah Satuan</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Ubah Data Satuan</h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Perbarui informasi nama, simbol singkatan, atau status keaktifan satuan.</p>
    </div>

    <!-- Main Form Container -->
    <form wire:submit="update" class="space-y-6">

        <!-- Card: Informasi Satuan -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-brand-espresso pb-3 border-b border-brand-border">Informasi Satuan</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nama Satuan -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nama Lengkap Satuan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="Misal: Kilogram, Mililiter, Pieces, Lusin"
                        class="w-full px-4 py-2.5 bg-white border {{ $errors->has('name') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Simbol / Singkatan -->
                <div>
                    <label for="short_name" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Simbol / Singkatan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="short_name" wire:model="short_name" placeholder="Misal: kg, ml, pcs, bungkus"
                        class="w-full px-4 py-2.5 font-mono bg-white border {{ $errors->has('short_name') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('short_name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Digunakan pada formula resep, cetak stiker, dan laporan.</p>
                </div>

                <!-- Keterangan / Deskripsi -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Keterangan (Opsional)
                    </label>
                    <textarea id="description" wire:model="description" rows="2" placeholder="Misal: Satuan baku untuk bahan tepung dan gula padat..."
                        class="w-full px-4 py-2 bg-white border {{ $errors->has('description') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"></textarea>
                    @error('description')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Aktif Switch -->
                <div class="md:col-span-2 pt-2 border-t border-brand-border/60">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="checkbox checkbox-primary rounded-lg">
                        <div>
                            <span class="text-sm font-semibold text-brand-espresso">Satuan Aktif</span>
                            <p class="text-xs text-brand-warm-gray">Jika dinonaktifkan, satuan ini tidak akan muncul pada pilihan formulir baru.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Form Actions Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.units') }}" wire:navigate
                class="px-5 py-2.5 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                <span wire:loading.remove>Simpan Perubahan</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>

    </form>
</div>
