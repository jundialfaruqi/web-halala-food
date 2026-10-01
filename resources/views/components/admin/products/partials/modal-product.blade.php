<!-- MODAL: PRODUK & VARIAN KEMASAN -->
<div x-cloak x-show="showProductModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showProductModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showProductModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showProductModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-brand-border flex flex-col max-h-[90vh] my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
            <div>
                <h3 class="text-xl font-bold text-brand-espresso" x-text="productModalTitle"></h3>
                <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5">
                    Informasi master produk dan pengaturan multi-varian kemasan (Pouch, Toples, Eceran).
                </p>
            </div>
            <button type="button" @click="showProductModal = false"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <form @submit.prevent="submitProduct()" class="flex-1 overflow-y-auto p-6 space-y-6">

            <!-- Global Error Banner -->
            <template x-if="productFormError">
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3">
                    <i class="ti ti-alert-circle text-red-600 text-xl shrink-0 mt-0.5"></i>
                    <p class="text-sm font-semibold text-red-700" x-text="productFormError"></p>
                </div>
            </template>

            <!-- Product General Info Section -->
            <div class="space-y-4">
                <h4 class="text-sm font-bold text-brand-espresso uppercase tracking-wider flex items-center gap-2 border-b border-brand-border pb-2">
                    <i class="ti ti-info-circle text-brand-primary"></i>
                    <span>1. Informasi Utama Produk</span>
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Category Select -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Kategori Produk <span class="text-red-500">*</span>
                        </label>
                        <select x-model="productForm.category_id"
                            @input="delete productErrors.categoryId; productFormError = ''"
                            :class="productErrors.categoryId ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                            class="select select-lg w-full bg-white border rounded-xl text-base text-brand-espresso font-medium capitalize">
                            <option value="" disabled>Pilih Kategori</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <template x-if="productErrors.categoryId">
                            <p class="text-xs text-red-600 font-semibold mt-1" x-text="productErrors.categoryId[0]"></p>
                        </template>
                    </div>

                    <!-- Product Code -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Kode Produk <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="productForm.code" placeholder="Contoh: TTS-01"
                            @input="delete productErrors.code; productFormError = ''"
                            :class="productErrors.code ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                            class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso uppercase font-mono placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                        <template x-if="productErrors.code">
                            <p class="text-xs text-red-600 font-semibold mt-1" x-text="productErrors.code[0]"></p>
                        </template>
                    </div>

                    <!-- Product Name -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Nama Produk <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="productForm.name" placeholder="Contoh: Ting-Ting Susu Halala"
                            @input="delete productErrors.name; productFormError = ''"
                            :class="productErrors.name ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                            class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                        <template x-if="productErrors.name">
                            <p class="text-xs text-red-600 font-semibold mt-1" x-text="productErrors.name[0]"></p>
                        </template>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Deskripsi Produk (Opsional)
                    </label>
                    <textarea x-model="productForm.description" rows="2" placeholder="Deskripsi singkat produk camilan..."
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
                </div>
            </div>

            <!-- Packaging Variants Section -->
            <div class="space-y-4 pt-2">
                <div class="flex items-center justify-between border-b border-brand-border pb-2">
                    <h4 class="text-sm font-bold text-brand-espresso uppercase tracking-wider flex items-center gap-2">
                        <i class="ti ti-packages text-brand-primary"></i>
                        <span>2. Varian Kemasan & Multi-Satuan (Multi-UOM)</span>
                    </h4>
                    <button type="button" @click="addVariantRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-brand-primary bg-brand-soft-cream/60 hover:bg-brand-soft-cream transition cursor-pointer">
                        <i class="ti ti-plus"></i>
                        <span>Tambah Varian Kemasan</span>
                    </button>
                </div>

                <template x-if="productErrors.variants">
                    <p class="text-xs text-red-600 font-semibold" x-text="productErrors.variants[0]"></p>
                </template>

                <!-- Variant Rows List -->
                <div class="space-y-4">
                    <template x-for="(variant, idx) in productForm.variants" :key="idx">
                        <div class="p-4 rounded-xl bg-neutral-50/70 border border-brand-border relative space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="size-6 rounded-full bg-brand-primary text-white font-bold text-xs flex items-center justify-center" x-text="idx + 1"></span>
                                    <span class="text-sm font-bold text-brand-espresso" x-text="variant.name || 'Varian Kemasan Baru'"></span>
                                </div>
                                <template x-if="productForm.variants.length > 1">
                                    <button type="button" @click="removeVariantRow(idx)"
                                        class="text-red-500 hover:text-red-700 text-xs font-semibold inline-flex items-center gap-1 cursor-pointer">
                                        <i class="ti ti-trash"></i> Hapus Varian
                                    </button>
                                </template>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Variant Name -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Nama Kemasan *</label>
                                    <input type="text" x-model="variant.name" placeholder="Contoh: Kemasan Pouch 200g"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Packaging Type -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Bentuk Kemasan *</label>
                                    <select x-model="variant.packaging_type"
                                        class="select select-md w-full bg-white border border-brand-border rounded-lg text-sm text-brand-espresso">
                                        <option value="pouch">Pouch Standing Zipper</option>
                                        <option value="jar">Toples Plastik</option>
                                        <option value="piece">Satuan Eceran (Pcs)</option>
                                        <option value="box">Kardus / Box</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>

                                <!-- Pcs per pack -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Isi Pcs per Kemasan *</label>
                                    <input type="number" min="1" x-model="variant.pcs_per_package"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Grams Weight -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Berat Bersih (Gram)</label>
                                    <input type="number" step="0.01" min="0" x-model="variant.weight_grams" placeholder="Contoh: 200"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Barcode -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Barcode (EAN-13)</label>
                                    <input type="text" x-model="variant.barcode" placeholder="Contoh: 8991234001011"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- SKU Code -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">SKU / Kode Varian</label>
                                    <input type="text" x-model="variant.sku_code" placeholder="Contoh: TTS-PCH-200G"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso uppercase font-mono focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Wholesale Price -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Harga Grosir (Supermarket) *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-brand-warm-gray font-bold">Rp</span>
                                        <input type="number" min="0" x-model="variant.wholesale_price"
                                            class="w-full pl-9 pr-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso font-mono font-bold focus:outline-none focus:border-brand-primary">
                                    </div>
                                </div>

                                <!-- Retail Price -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Harga Eceran (Kelontong) *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-brand-warm-gray font-bold">Rp</span>
                                        <input type="number" min="0" x-model="variant.retail_price"
                                            class="w-full pl-9 pr-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso font-mono font-bold focus:outline-none focus:border-brand-primary">
                                    </div>
                                </div>

                                <!-- Stock Qty -->
                                <div>
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Stok Barang Jadi Saat Ini</label>
                                    <input type="number" min="0" x-model="variant.stock_qty"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-brand-border">
                <button type="button" @click="showProductModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                    <template x-if="isProcessing">
                        <i class="ti ti-loader-2 animate-spin text-lg"></i>
                    </template>
                    <span>Simpan Produk</span>
                </button>
            </div>
        </form>
    </div>
</div>
