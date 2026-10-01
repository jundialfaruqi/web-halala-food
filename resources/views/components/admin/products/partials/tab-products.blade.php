<!-- TAB 1: PRODUK & KEMASAN VARIAN -->
<div x-show="activeTab === 'products'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="productSearch" placeholder="Cari nama produk, kode, atau barcode..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Category Filter -->
            <div class="shrink-0">
                <select x-model="productCategoryFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="all">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Add Button -->
        <button type="button" @click="openCreateProductModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Produk Baru</span>
        </button>
    </div>

    <!-- Products List Card/Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Produk & Kategori</th>
                    <th class="py-3.5 px-6">Varian Kemasan & Multi-Satuan</th>
                    <th class="py-3.5 px-6">Harga Grosir / Ecer</th>
                    <th class="py-3.5 px-6">Stok Barang Jadi</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($products as $prod)
                    <tr x-show="(!productSearch || $el.textContent.toLowerCase().includes(productSearch.toLowerCase())) && (productCategoryFilter === 'all' || '{{ $prod->category_id }}' === productCategoryFilter)"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Product Info -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex items-start gap-3">
                                <div class="size-11 rounded-xl bg-brand-soft-cream/60 border border-brand-border/60 flex items-center justify-center text-brand-primary shrink-0">
                                    <i class="ti ti-cookie text-2xl"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-brand-espresso text-base block">
                                        {{ $prod->name }}
                                    </span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="font-mono text-xs px-2 py-0.5 bg-neutral-100 rounded text-brand-warm-gray font-bold">
                                            {{ $prod->code }}
                                        </span>
                                        <span class="text-xs px-2 py-0.5 bg-brand-soft-cream text-brand-primary rounded font-bold">
                                            {{ $prod->category?->name ?? 'Uncategorized' }}
                                        </span>
                                    </div>
                                    @if ($prod->description)
                                        <p class="text-xs text-brand-warm-gray mt-1 line-clamp-1">
                                            {{ $prod->description }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Packaging Variants -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-2">
                                @forelse ($prod->variants as $variant)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded-lg bg-neutral-50 border border-brand-border/50 text-xs">
                                        <div>
                                            <div class="flex items-center gap-1.5 font-bold text-brand-espresso">
                                                @if ($variant->packaging_type === 'pouch')
                                                    <i class="ti ti-package text-brand-primary"></i>
                                                @elseif ($variant->packaging_type === 'jar')
                                                    <i class="ti ti-cylinder text-amber-600"></i>
                                                @else
                                                    <i class="ti ti-box text-emerald-600"></i>
                                                @endif
                                                <span>{{ $variant->name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-0.5 text-brand-warm-gray">
                                                <span>Isi: {{ $variant->pcs_per_package }} pcs</span>
                                                @if ($variant->weight_grams)
                                                    <span>• {{ (float) $variant->weight_grams }}g</span>
                                                @endif
                                                @if ($variant->barcode)
                                                    <span>• Barcode: <code class="font-mono">{{ $variant->barcode }}</code></span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-xs text-brand-warm-gray italic">Belum ada varian kemasan</span>
                                @endforelse
                            </div>
                        </td>

                        <!-- Pricing per Variant -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-2">
                                @foreach ($prod->variants as $variant)
                                    <div class="p-2.5 rounded-lg bg-neutral-50/50 border border-brand-border/40 text-xs space-y-0.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-brand-warm-gray">Grosir (Supermarket):</span>
                                            <span class="font-bold text-brand-espresso font-mono">
                                                {{ $variant->formattedWholesalePrice() }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-brand-warm-gray">Ecer (Kelontong):</span>
                                            <span class="font-bold text-brand-primary font-mono">
                                                {{ $variant->formattedRetailPrice() }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>

                        <!-- Stock Status -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-2">
                                @foreach ($prod->variants as $variant)
                                    <div class="p-2.5 rounded-lg bg-neutral-50/50 border border-brand-border/40 text-xs flex items-center justify-between gap-2">
                                        <span class="font-mono font-bold text-sm text-brand-espresso">
                                            {{ $variant->stock_qty }} pack
                                        </span>
                                        @if ($variant->isLowStock())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-red-100 text-red-700">
                                                <i class="ti ti-alert-triangle"></i> Kritis
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-700">
                                                Aman
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="openEditProductById({{ $prod->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                    <i class="ti ti-edit text-base"></i>
                                    <span>Edit</span>
                                </button>

                                <button type="button" @click="confirmDeleteProductById({{ $prod->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200">
                                    <i class="ti ti-trash text-base"></i>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-brand-warm-gray">
                            <i class="ti ti-box-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada produk terdaftar</p>
                            <p class="text-sm mt-1">Klik tombol "Tambah Produk Baru" untuk menambahkan camilan Halala Food.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
