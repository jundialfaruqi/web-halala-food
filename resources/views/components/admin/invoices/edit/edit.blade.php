<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb"
            class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Keuangan</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.invoices') }}" wire:navigate class="hover:text-brand-primary transition">Faktur &amp;
                Piutang</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.invoices.show', $invoice) }}" wire:navigate
                class="hover:text-brand-primary transition font-mono">{{ $invoice->invoice_number }}</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Edit</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Edit Faktur Tagihan</h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Perbarui rincian produk, potongan diskon, atau tanggal
            jatuh tempo untuk nomor faktur <span
                class="font-mono font-bold text-brand-espresso">{{ $invoice->invoice_number }}</span>.</p>
    </div>

    <!-- Main Form Container -->
    <form wire:submit="save" class="space-y-6">

        <!-- Card 1: Informasi Dokumen & Penagihan -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-brand-espresso pb-3 border-b border-brand-border">Informasi Dokumen
                &amp; Penagihan</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Nomor Faktur (Readonly) -->
                <div>
                    <label for="invoice_number" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nomor Faktur
                    </label>
                    <input type="text" id="invoice_number" wire:model="invoice_number" readonly
                        class="w-full px-4 py-2.5 bg-neutral-100 border border-brand-border rounded-xl text-sm font-mono font-bold text-brand-warm-gray cursor-not-allowed">
                    <p class="text-xs text-brand-warm-gray mt-1">Nomor faktur tidak dapat diubah setelah diterbitkan.
                    </p>
                </div>

                <!-- 2. Status & Surat Jalan Acuan -->
                <div>
                    <label class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Terkait Surat Jalan
                    </label>
                    <div
                        class="px-4 py-2.5 bg-neutral-50 border border-brand-border rounded-xl text-sm text-brand-espresso flex items-center justify-between">
                        @if ($invoice->delivery)
                            <span class="font-mono font-bold">{{ $invoice->delivery->delivery_number }}</span>
                            <span
                                class="text-xs text-brand-warm-gray">({{ $invoice->delivery->delivery_date?->format('d/m/Y') }})</span>
                        @else
                            <span class="text-brand-warm-gray italic">Faktur Dibuat Manual (Tanpa Tautan SJ)</span>
                        @endif
                    </div>
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

                    <!-- Info Box Toko Terpilih -->
                    @if ($selectedStore)
                        <div
                            class="mt-3 p-3.5 bg-neutral-50 rounded-xl border border-brand-border/80 text-xs space-y-1">
                            <div class="font-bold text-brand-espresso">{{ $selectedStore->name }}</div>
                            <div class="text-brand-warm-gray">{{ $selectedStore->address ?? 'Alamat belum diatur' }}
                            </div>
                            <div class="flex items-center gap-3 pt-1 text-brand-warm-gray">
                                @if ($selectedStore->route)
                                    <span>Rute: <strong
                                            class="text-brand-espresso">{{ $selectedStore->route }}</strong></span>
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

                <!-- Kurir Pengantar / Penagih (Opsional) -->
                <div>
                    <label for="courier_id" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Kurir Pengantar / Penagih (Opsional)
                    </label>
                    <select id="courier_id" wire:model="courier_id"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                        <option value="">-- Tanpa Kurir Spesifik --</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier->id }}">
                                {{ $courier->name }} {{ $courier->phone ? "({$courier->phone})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('courier_id')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Pilih kurir yang ditugaskan mengantar faktur ini ke toko mitra.</p>
                </div>

                <!-- 4. Tanggal Faktur & Jatuh Tempo -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="invoice_date" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                            Tanggal Faktur <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="invoice_date" wire:model="invoice_date"
                            class="w-full px-3.5 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                        @error('invoice_date')
                            <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="due_date" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                            Jatuh Tempo <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="due_date" wire:model="due_date"
                            class="w-full px-3.5 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                        @error('due_date')
                            <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 5. Catatan Faktur -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Catatan Penagihan (Opsional)
                    </label>
                    <textarea id="notes" wire:model="notes" rows="2"
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"
                        placeholder="Contoh: Kesepakatan jatuh tempo 14 hari kerja, pelunasan transfer ke rekening BCA Halala Food..."></textarea>
                    @error('notes')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Card 2: Daftar Produk yang Ditagihkan -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Daftar Produk yang Ditagihkan</h2>
                    <p class="text-xs text-brand-warm-gray mt-0.5">Rincian produk kemasan konsinyasi yang diserahkan dan
                        ditagihkan ke mitra.</p>
                </div>

                <button type="button" wire:click="addItem"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 border border-brand-border rounded-xl text-xs font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                    <i class="ti ti-plus text-sm"></i>
                    <span>Tambah Baris</span>
                </button>
            </div>

            <!-- Table Items with Consignment Reconciliation -->
            <!-- Table Items for Consignment Line Items -->
            <div class="border border-brand-border rounded-xl overflow-hidden overflow-x-auto">
                <table class="w-full text-left text-sm text-brand-espresso">
                    <thead
                        class="bg-neutral-50 border-b border-brand-border text-xs uppercase tracking-wider font-semibold text-brand-warm-gray">
                        <tr>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap min-w-50">Produk Jadi</th>
                            <th scope="col" class="px-3 py-3 whitespace-nowrap w-36 text-center">Jumlah Dititipkan</th>
                            <th scope="col" class="px-3 py-3 whitespace-nowrap w-40 text-right">Harga Setor (Rp)</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-44 text-right">Subtotal</th>
                            <th scope="col" class="px-2 py-3 w-10 text-center whitespace-nowrap"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border/60">
                        @foreach ($items as $index => $item)
                            <tr class="hover:bg-neutral-50/50 transition">
                                <!-- 1. Produk -->
                                <td class="px-4 py-2.5">
                                    <select wire:model.live="items.{{ $index }}.product_id"
                                        class="w-full px-2.5 py-1.5 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->name }} (Rp
                                                {{ number_format($product->consignment_price, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.{$index}.product_id")
                                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- 2. Jumlah Dititipkan / Dikirim -->
                                <td class="px-3 py-2.5">
                                    <input type="number" min="1"
                                        wire:model.live="items.{{ $index }}.quantity"
                                        class="w-full px-2.5 py-1.5 bg-white border border-brand-border rounded-lg text-sm font-bold text-center text-brand-espresso font-mono focus:outline-none focus:border-brand-primary"
                                        placeholder="1">
                                    @error("items.{$index}.quantity")
                                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- 3. Harga Satuan Konsinyasi -->
                                <td class="px-3 py-2.5">
                                    <div class="relative">
                                        <span
                                            class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-brand-warm-gray">Rp</span>
                                        <input type="number" step="500" min="0"
                                            wire:model.live="items.{{ $index }}.unit_price"
                                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso focus:outline-none focus:border-brand-primary font-mono text-right">
                                    </div>
                                    @error("items.{$index}.unit_price")
                                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- 4. Subtotal -->
                                <td
                                    class="px-4 py-2.5 text-right whitespace-nowrap font-mono font-bold text-brand-espresso">
                                    Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}
                                </td>

                                <!-- 5. Hapus Baris -->
                                <td class="px-2 py-2.5 text-center whitespace-nowrap">
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
                    <tfoot class="bg-neutral-50 border-t border-brand-border text-sm">
                        <tr>
                            <td colspan="3" class="px-4 py-2.5 text-right font-medium text-brand-warm-gray">
                                Subtotal
                            </td>
                            <td
                                class="px-4 py-2.5 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                                Rp {{ number_format($this->subtotal, 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="px-4 py-2.5 text-right font-medium text-brand-warm-gray">
                                Potongan / Diskon (Rp)
                            </td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" min="0" step="1000" wire:model.live="discount"
                                    class="w-36 text-right px-3 py-1 bg-white border border-brand-border rounded-lg text-sm font-mono text-brand-espresso focus:outline-none focus:border-brand-primary">
                            </td>
                            <td></td>
                        </tr>
                        <tr class="font-bold text-brand-espresso border-t border-brand-border">
                            <td colspan="3" class="px-4 py-3 text-right text-base uppercase tracking-wider">
                                Total Tagihan Akhir
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-base whitespace-nowrap">
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
            @error('discount')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- Form Actions Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.invoices.show', $invoice) }}" wire:navigate
                class="px-5 py-2.5 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                <span wire:loading.remove>Simpan Perubahan Faktur</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>

    </form>
</div>
