<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb"
            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Operasional</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.purchases') }}" wire:navigate class="hover:text-brand-primary transition">Pembelian
                Bahan</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Catat Pembelian</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Catat Pembelian Bahan Baku
        </h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Input nota belanja bahan baku dapur dari supplier
            untuk menambah stok dan mencatat pengeluaran kas.</p>
    </div>

    <!-- Main Form Container -->
    <form wire:submit="save" class="space-y-6">

        <!-- Card 1: Informasi Nota & Supplier -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-brand-espresso pb-3 border-b border-brand-border">Informasi Nota &amp;
                Supplier</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- No. Transaksi -->
                <div>
                    <label for="purchase_number"
                        class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
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
                    <label for="purchase_date"
                        class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
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
                    <label for="supplier_name"
                        class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
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
                    <label for="payment_method"
                        class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
                        Metode Pembayaran <span class="text-red-500">*</span>
                    </label>
                    <select id="payment_method" wire:model.live="payment_method"
                        class="w-full px-4 py-3 rounded-xl border border-brand-border text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary bg-white">
                        <option value="tunai">Tunai / Kas Kecil</option>
                        <option value="transfer_bank">Transfer Bank</option>
                        <option value="tempo">Tempo / Kredit Supplier</option>
                    </select>
                    @error('payment_method')
                        <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sumber Kas / Rekening Pembayaran -->
                @if ($payment_method !== 'tempo')
                    <div>
                        <label for="account_id"
                            class="block text-xs font-bold text-brand-espresso uppercase tracking-wider mb-2">
                            Sumber Kas / Rekening <span class="text-red-500">*</span>
                        </label>
                        <select id="account_id" wire:model="account_id"
                            class="w-full px-4 py-3 rounded-xl border border-brand-border text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary bg-white">
                            <option value="">-- Pilih Kas / Rekening --</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->name }} (Saldo: Rp {{ number_format($acc->balance, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        @error('account_id')
                            <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div class="flex items-center">
                        <div
                            class="p-3 bg-neutral-50 rounded-xl border border-neutral-200 text-xs text-brand-warm-gray w-full">
                            <i class="ti ti-info-circle mr-1"></i> Pembayaran tempo dicatat sebagai <strong>Hutang
                                Usaha</strong>. Tidak memotong saldo kas saat ini.
                        </div>
                    </div>
                @endif
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
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6"
            x-data="{
                grandTotal: {{ (float) $this->totalAmount }},
                updateGrandTotal() {
                    let total = 0;
                    document.querySelectorAll('.item-subtotal-input').forEach(el => {
                        total += parseFloat(el.value || 0) || 0;
                    });
                    this.grandTotal = Math.round(total * 100) / 100;
                }
            }"
            @subtotal-changed.window="updateGrandTotal()">
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
                    @php
                        $selectedMat = !empty($row['raw_material_id']) ? $rawMaterials->firstWhere('id', $row['raw_material_id']) : null;
                        $unitShort = $selectedMat?->display_unit ?? 'satuan';
                        $inputMode = $row['input_mode'] ?? (isset($row['quantity']) && !isset($row['package_count']) ? 'direct' : 'package');
                    @endphp
                    <div class="p-4 rounded-2xl border border-brand-border bg-white shadow-xs space-y-4"
                        wire:key="item-card-{{ $index }}"
                        x-data="{
                            inputMode: @entangle('items.' . $index . '.input_mode'),
                            pkgCount: @entangle('items.' . $index . '.package_count'),
                            content: @entangle('items.' . $index . '.content_per_package'),
                            pricePerPkg: @entangle('items.' . $index . '.price_per_package'),
                            subtotal: @entangle('items.' . $index . '.subtotal'),
                            qty: @entangle('items.' . $index . '.quantity'),
                            costPerUnit: @entangle('items.' . $index . '.cost_per_unit'),
                            unitShort: '{{ $unitShort }}',

                            formatRupiah(val) {
                                let num = parseFloat(val) || 0;
                                return 'Rp ' + Math.round(num).toLocaleString('id-ID');
                            },

                            calcTotalQty() {
                                let p = parseFloat(this.pkgCount) || 0;
                                let c = parseFloat(this.content) || 0;
                                if (p > 0 && c > 0) {
                                    this.qty = Math.round(p * c * 10000) / 10000;
                                } else if (p > 0 && (!this.content || parseFloat(this.content) <= 0)) {
                                    this.qty = p;
                                } else {
                                    this.qty = '';
                                }
                                return parseFloat(this.qty) || 0;
                            },

                            onPriceChanged() {
                                let p = parseFloat(this.pkgCount) || 0;
                                let totalQty = this.calcTotalQty();

                                if (this.pricePerPkg === '' || this.pricePerPkg === null || this.pricePerPkg === undefined) {
                                    this.pricePerPkg = '';
                                    this.subtotal = '';
                                    this.costPerUnit = '';
                                    this.$dispatch('subtotal-changed');
                                    return;
                                }

                                let pr = parseFloat(this.pricePerPkg) || 0;
                                if (p > 0) {
                                    this.subtotal = Math.round(p * pr * 100) / 100;
                                    if (totalQty > 0) {
                                        this.costPerUnit = Math.round((this.subtotal / totalQty) * 10000) / 10000;
                                    } else {
                                        this.costPerUnit = '';
                                    }
                                } else {
                                    this.subtotal = '';
                                    this.costPerUnit = '';
                                }
                                this.$dispatch('subtotal-changed');
                            },

                            onSubtotalChanged() {
                                let p = parseFloat(this.pkgCount) || 0;
                                let totalQty = this.calcTotalQty();

                                if (this.subtotal === '' || this.subtotal === null || this.subtotal === undefined) {
                                    this.subtotal = '';
                                    this.pricePerPkg = '';
                                    this.costPerUnit = '';
                                    this.$dispatch('subtotal-changed');
                                    return;
                                }

                                let sub = parseFloat(this.subtotal) || 0;
                                if (p > 0) {
                                    this.pricePerPkg = Math.round((sub / p) * 100) / 100;
                                    if (totalQty > 0) {
                                        this.costPerUnit = Math.round((sub / totalQty) * 10000) / 10000;
                                    } else {
                                        this.costPerUnit = '';
                                    }
                                } else {
                                    this.pricePerPkg = '';
                                    this.costPerUnit = '';
                                }
                                this.$dispatch('subtotal-changed');
                            },

                            onPkgOrContentChanged() {
                                let totalQty = this.calcTotalQty();
                                let p = parseFloat(this.pkgCount) || 0;

                                if (this.pricePerPkg !== '' && this.pricePerPkg !== null && this.pricePerPkg !== undefined && parseFloat(this.pricePerPkg) > 0 && p > 0) {
                                    this.subtotal = Math.round(p * parseFloat(this.pricePerPkg) * 100) / 100;
                                    if (totalQty > 0) {
                                        this.costPerUnit = Math.round((this.subtotal / totalQty) * 10000) / 10000;
                                    }
                                } else if (this.subtotal !== '' && this.subtotal !== null && this.subtotal !== undefined && parseFloat(this.subtotal) > 0 && p > 0) {
                                    this.pricePerPkg = Math.round((parseFloat(this.subtotal) / p) * 100) / 100;
                                    if (totalQty > 0) {
                                        this.costPerUnit = Math.round((parseFloat(this.subtotal) / totalQty) * 10000) / 10000;
                                    }
                                } else if (p <= 0) {
                                    this.costPerUnit = '';
                                }
                                this.$dispatch('subtotal-changed');
                            },

                            onDirectChanged() {
                                let q = parseFloat(this.qty) || 0;
                                let c = parseFloat(this.costPerUnit) || 0;
                                if (q > 0 && c > 0) {
                                    this.subtotal = Math.round(q * c * 100) / 100;
                                } else {
                                    this.subtotal = '';
                                }
                                this.$dispatch('subtotal-changed');
                            }
                        }">
                        <!-- Baris Header: Label Baris & Mode Switcher -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-brand-border/60">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-brand-espresso">Bahan #{{ $index + 1 }}</span>
                                @if ($selectedMat)
                                    <span class="text-xs text-brand-warm-gray">• Satuan Dapur: <strong class="text-brand-espresso">{{ $unitShort }}</strong></span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-auto">
                                <!-- Mode Switcher (Client-Side Alpine) -->
                                <div class="inline-flex items-center p-0.5 bg-neutral-100 rounded-lg text-xs font-semibold border border-neutral-200/60">
                                    <button type="button" @click="inputMode = 'package'; onPkgOrContentChanged()"
                                        class="px-2.5 py-1 rounded-md transition cursor-pointer flex items-center gap-1"
                                        :class="inputMode === 'package' ? 'bg-white shadow-xs text-brand-primary font-bold' : 'text-brand-warm-gray hover:text-brand-espresso'">
                                        <i class="ti ti-package text-xs"></i>
                                        <span>Mode Kemasan Beli</span>
                                    </button>
                                    <button type="button" @click="inputMode = 'direct'; onDirectChanged()"
                                        class="px-2.5 py-1 rounded-md transition cursor-pointer flex items-center gap-1"
                                        :class="inputMode === 'direct' ? 'bg-white shadow-xs text-brand-primary font-bold' : 'text-brand-warm-gray hover:text-brand-espresso'">
                                        <i class="ti ti-scale text-xs"></i>
                                        <span>Mode Satuan Langsung</span>
                                    </button>
                                </div>

                                <!-- Tombol Hapus Baris -->
                                @if (count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="p-1 text-brand-warm-gray hover:text-red-600 transition cursor-pointer rounded-lg hover:bg-red-50"
                                        title="Hapus baris bahan ini">
                                        <i class="ti ti-trash text-base"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Form Input Baris Bahan (Grid Responsif) -->
                        <div class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3 items-start">
                                <!-- 1. Bahan Baku (Unified di Kedua Mode) -->
                                <div class="md:col-span-4 sm:col-span-2">
                                    <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                        Pilih Bahan Baku <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model.live="items.{{ $index }}.raw_material_id"
                                        class="w-full px-3 py-2 rounded-xl border {{ $errors->has("items.{$index}.raw_material_id") ? 'border-red-500' : 'border-brand-border' }} text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary">
                                        <option value="">-- Pilih Bahan Baku Dapur --</option>
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

                                <!-- 2. Sisi Kanan: MODE KEMASAN BELI (8 Kolom) -->
                                <div x-show="inputMode === 'package'" class="md:col-span-8 sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-8 gap-3 items-start">
                                    <!-- Jumlah Kemasan Beli -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                            Jumlah Beli <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" step="any" min="0"
                                            x-model="pkgCount"
                                            @input="onPkgOrContentChanged()"
                                            placeholder="Misal: 10"
                                            class="w-full px-3 py-2 rounded-xl border {{ $errors->has("items.{$index}.quantity") ? 'border-red-500' : 'border-brand-border' }} text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary font-mono">
                                        <span class="text-[10px] text-brand-warm-gray mt-0.5 block">Buah / Galon / Dus</span>
                                    </div>

                                    <!-- Isi Bersih per Kemasan -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                            Isi per Kemasan <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" step="any" min="0"
                                            x-model="content"
                                            @input="onPkgOrContentChanged()"
                                            placeholder="Misal: 500"
                                            class="w-full px-3 py-2 rounded-xl border border-brand-border text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary font-mono">
                                        <span class="text-[10px] text-brand-warm-gray mt-0.5 block">Dalam {{ $unitShort }}</span>
                                    </div>

                                    <!-- Harga Beli per Kemasan (Auto-Sync) -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                            Harga / Kemasan (Rp)
                                        </label>
                                        <input type="number" step="any" min="0"
                                            x-model="pricePerPkg"
                                            @input="onPriceChanged()"
                                            placeholder="Misal: 40000"
                                            class="w-full px-3 py-2 rounded-xl border border-brand-border text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary font-mono">
                                        <span class="text-[10px] text-brand-warm-gray mt-0.5 block">Jika tertera di nota</span>
                                    </div>

                                    <!-- Total Belanja Baris Ini (Auto-Sync) -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                            Total Belanja (Rp) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" step="any" min="0"
                                            x-model="subtotal"
                                            @input="onSubtotalChanged()"
                                            placeholder="Misal: 400000"
                                            class="item-subtotal-input w-full px-3 py-2 rounded-xl border border-brand-border text-sm font-bold text-brand-espresso bg-neutral-50 focus:bg-white focus:outline-hidden focus:border-brand-primary font-mono">
                                        <span class="text-[10px] text-brand-warm-gray mt-0.5 block">Jika di nota hanya total</span>
                                    </div>
                                </div>

                                <!-- 3. Sisi Kanan: MODE SATUAN LANGSUNG (8 Kolom) -->
                                <div x-show="inputMode === 'direct'" style="display: none;" class="md:col-span-8 sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-8 gap-3 items-start">
                                    <!-- Kuantitas Langsung -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                            Jumlah ({{ $unitShort }}) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" step="any" min="0"
                                            x-model="qty"
                                            @input="onDirectChanged()"
                                            placeholder="0"
                                            class="w-full px-3 py-2 rounded-xl border border-brand-border text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary font-mono">
                                        @error("items.{$index}.quantity")
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Harga Satuan -->
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                            Harga / {{ $unitShort }} (Rp) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" step="any" min="0"
                                            x-model="costPerUnit"
                                            @input="onDirectChanged()"
                                            placeholder="0"
                                            class="w-full px-3 py-2 rounded-xl border border-brand-border text-sm text-brand-espresso bg-white focus:outline-hidden focus:border-brand-primary font-mono">
                                        @error("items.{$index}.cost_per_unit")
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                            Subtotal (Rp)
                                        </label>
                                        <input type="hidden" class="item-subtotal-input" :value="subtotal || 0">
                                        <div class="px-3 py-2 rounded-xl bg-neutral-100 text-sm font-bold text-brand-espresso text-right font-mono"
                                            x-text="formatRupiah(subtotal)">
                                            Rp {{ number_format((float) ($row['subtotal'] ?? 0), 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hasil Konversi Cerdas (Live Client-Side Result untuk Mode Kemasan) -->
                            <div x-show="inputMode === 'package' && qty > 0 && subtotal > 0"
                                class="p-3 bg-neutral-50 rounded-xl border border-brand-border/70 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2">
                                    <i class="ti ti-check text-emerald-600 text-sm"></i>
                                    <span class="text-brand-espresso">
                                        Stok Dapur Masuk: <strong class="font-bold text-brand-espresso" x-text="(parseFloat(qty) || 0).toLocaleString('id-ID') + ' ' + unitShort"></strong>
                                        <template x-if="pkgCount && content">
                                            <span class="text-brand-warm-gray" x-text="'(' + pkgCount + ' kemasan &times; ' + content + ' ' + unitShort + ')'"></span>
                                        </template>
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-4">
                                    <span class="text-brand-espresso">
                                        HPP Dapur: <strong class="font-bold font-mono text-brand-primary" x-text="'Rp ' + (parseFloat(costPerUnit) || 0).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 4})"></strong> / <span x-text="unitShort"></span>
                                    </span>
                                    <template x-if="pricePerPkg">
                                        <span class="text-brand-espresso">
                                            Harga Satuan Kemasan: <strong class="font-bold font-mono text-brand-espresso" x-text="formatRupiah(pricePerPkg)"></strong>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Keterangan Opsional -->
                            <div>
                                <input type="text" wire:model="items.{{ $index }}.notes"
                                    placeholder="Catatan tambahan (opsional, misal: Galon isi ulang, Tepung Segitiga Biru pouch)..."
                                    class="w-full px-3 py-1.5 rounded-lg border border-brand-border/60 text-xs text-brand-espresso placeholder-brand-warm-gray/60 bg-white focus:outline-hidden focus:border-brand-primary">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Grand Total Ringkasan -->
            <div
                class="pt-4 border-t border-brand-border flex flex-col sm:flex-row items-end sm:items-center justify-between gap-4">
                <span class="text-sm font-bold text-brand-espresso">Total Biaya Pengadaan:</span>
                <span class="text-2xl font-extrabold text-brand-espresso"
                    x-text="'Rp ' + Number(grandTotal).toLocaleString('id-ID')">
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
