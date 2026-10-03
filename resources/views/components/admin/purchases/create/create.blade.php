<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Operasional</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.purchases') }}" wire:navigate class="hover:text-brand-primary transition">Pembelian Bahan</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Catat Pembelian</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Catat Pembelian Bahan Baku</h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Input nota belanja bahan baku dapur dari supplier untuk menambah stok dan mencatat pengeluaran kas.</p>
    </div>

    <!-- Main Form Container -->
    <form wire:submit="save" class="space-y-6">

        <!-- Card 1: Informasi Nota & Supplier -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-brand-espresso pb-3 border-b border-brand-border">Informasi Nota &amp; Supplier</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- No. Transaksi -->
                <div>
                    <label for="purchase_number" class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
                        Nomor Pembelian <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="purchase_number" wire:model="purchase_number"
                        class="w-full px-4 py-3 rounded-xl border border-brand-border text-sm font-mono text-brand-espresso focus:outline-hidden focus:border-brand-primary bg-neutral-50/50">
                    @error('purchase_number')
                        <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Pembelian -->
                <div>
                    <label for="purchase_date" class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
                        Tanggal Pembelian <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="purchase_date" wire:model="purchase_date"
                        class="w-full px-4 py-3 rounded-xl border border-brand-border text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary bg-white">
                    @error('purchase_date')
                        <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Supplier -->
                <div>
                    <label for="supplier_name" class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
                        Nama Toko / Supplier <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="supplier_name" wire:model="supplier_name"
                        placeholder="Contoh: Toko Bahan Kue Berkah Jaya, Pasar Induk..."
                        class="w-full px-4 py-3 rounded-xl border border-brand-border text-sm text-brand-espresso placeholder-brand-warm-gray/60 focus:outline-hidden focus:border-brand-primary bg-white">
                    @error('supplier_name')
                        <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Metode Pembayaran -->
                <div>
                    <label for="payment_method" class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
                        Metode Pembayaran <span class="text-red-500">*</span>
                    </label>
                    <select id="payment_method" wire:model="payment_method"
                        class="w-full px-4 py-3 rounded-xl border border-brand-border text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary bg-white">
                        <option value="tunai">Tunai / Kas Kecil</option>
                        <option value="transfer_bank">Transfer Bank</option>
                        <option value="tempo">Tempo / Kredit Supplier</option>
                    </select>
                    @error('payment_method')
                        <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Catatan -->
            <div>
                <label for="notes" class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
                    Catatan Pengadaan
                </label>
                <textarea id="notes" wire:model="notes" rows="2"
                    placeholder="Keterangan tambahan nota, no. struk fisik, atau kondisi bahan..."
                    class="w-full px-4 py-3 rounded-xl border border-brand-border text-sm text-brand-espresso placeholder-brand-warm-gray/60 focus:outline-hidden focus:border-brand-primary bg-white"></textarea>
                @error('notes')
                    <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Card 2: Rincian Bahan Masuk (Repeater) -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Daftar Bahan Baku Dibeli</h2>
                    <p class="text-xs text-brand-warm-gray mt-0.5">Pilih bahan baku, masukkan kuantitas dan harga satuan.</p>
                </div>
                <button type="button" wire:click="addItem"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold text-brand-primary border border-brand-primary/30 hover:bg-brand-soft-cream/40 transition cursor-pointer">
                    <i class="ti ti-plus"></i>
                    <span>Tambah Baris</span>
                </button>
            </div>

            @error('items')
                <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
            @enderror

            <div class="space-y-4">
                @foreach ($items as $index => $row)
                    <div class="p-4 rounded-xl border border-brand-border bg-brand-soft-cream/10 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                            <!-- Bahan Baku Selection -->
                            <div class="md:col-span-4">
                                <label class="block text-xs font-semibold text-brand-warm-gray mb-1">
                                    Bahan Baku <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="items.{{ $index }}.raw_material_id"
                                    class="w-full px-3 py-2 rounded-lg border border-brand-border text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary">
                                    <option value="">Pilih Bahan...</option>
                                    @foreach ($rawMaterials as $mat)
                                        <option value="{{ $mat->id }}">
                                            {{ $mat->name }} (Satuan: {{ $mat->display_unit }})
                                        </option>
                                    @endforeach
                                </select>
                                @error("items.{$index}.raw_material_id")
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Kuantitas -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-brand-warm-gray mb-1">
                                    Jumlah (Qty) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="any" wire:model.live.debounce.300ms="items.{{ $index }}.quantity"
                                    placeholder="0"
                                    class="w-full px-3 py-2 rounded-lg border border-brand-border text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary">
                                @error("items.{$index}.quantity")
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Harga Satuan -->
                            <div class="md:col-span-3">
                                <label class="block text-xs font-semibold text-brand-warm-gray mb-1">
                                    Harga Satuan (Rp) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="any" wire:model.live.debounce.300ms="items.{{ $index }}.cost_per_unit"
                                    placeholder="0"
                                    class="w-full px-3 py-2 rounded-lg border border-brand-border text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary">
                                @error("items.{$index}.cost_per_unit")
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Subtotal -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-brand-warm-gray mb-1">
                                    Subtotal (Rp)
                                </label>
                                <div class="px-3 py-2 rounded-lg bg-neutral-100 text-sm font-bold text-brand-espresso text-right">
                                    Rp {{ number_format($row['subtotal'] ?? 0, 0, ',', '.') }}
                                </div>
                            </div>

                            <!-- Tombol Hapus Baris -->
                            <div class="md:col-span-1 pt-6 text-center">
                                @if (count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="text-brand-warm-gray hover:text-red-600 transition cursor-pointer p-1"
                                        title="Hapus baris ini">
                                        <i class="ti ti-trash text-lg"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Keterangan Baris Item -->
                        <div>
                            <input type="text" wire:model="items.{{ $index }}.notes"
                                placeholder="Keterangan opsional untuk bahan ini (misal: karung 25kg, kemasan jerigen)..."
                                class="w-full px-3 py-1.5 rounded-lg border border-brand-border/60 text-xs text-brand-espresso placeholder-brand-warm-gray/60 bg-white focus:outline-hidden focus:border-brand-primary">
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Grand Total Ringkasan -->
            <div class="pt-4 border-t border-brand-border flex flex-col sm:flex-row items-end sm:items-center justify-between gap-4">
                <span class="text-sm font-bold text-brand-espresso">Total Biaya Pengadaan:</span>
                <span class="text-2xl font-extrabold text-brand-espresso">
                    Rp {{ number_format($this->totalAmount, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-4 pt-2">
            <a href="{{ route('admin.purchases') }}" wire:navigate
                class="px-6 py-3 rounded-xl border border-brand-border text-sm font-bold text-brand-espresso hover:bg-neutral-100 transition">
                Batal
            </a>
            <button type="submit"
                class="px-7 py-3 rounded-xl bg-brand-primary text-sm font-bold text-white hover:bg-brand-primary/90 transition shadow-xs cursor-pointer">
                Simpan Transaksi Pembelian
            </button>
        </div>

    </form>

</div>
