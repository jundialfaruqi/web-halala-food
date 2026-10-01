<!-- Tab 2: Generator & Cetak Label Barcode -->
<div x-show="activeTab === 'barcodes'" x-cloak class="space-y-6">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Form Konfigurasi Cetak Label (Left 7 Cols) -->
        <div class="lg:col-span-7 space-y-5">
            <div class="bg-white p-6 rounded-xl border border-brand-border space-y-5">
                <div class="border-b border-brand-border pb-3">
                    <h2 class="text-lg font-bold text-brand-espresso">Konfigurasi Label & Barcode</h2>
                    <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5">Pilih varian kemasan camilan dan sesuaikan format stiker barcode sebelum mencetak.</p>
                </div>

                <!-- 1. Pilih Varian Produk -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Pilih Varian Kemasan Produk <span class="text-error">*</span>
                    </label>
                    <select x-model="selectedVariantId" @change="onVariantChange()"
                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize focus:border-brand-primary">
                        <template x-for="variant in productVariants" :key="variant.id">
                            <option :value="variant.id" x-text="`${variant.full_name} (${variant.packaging_type.toUpperCase()} - Rp ${new Intl.NumberFormat('id-ID').format(variant.retail_price)})`"></option>
                        </template>
                    </select>
                </div>

                <!-- 2. Kode Barcode (EAN-13 / Custom) -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Kode Barcode (EAN-13 / Code-128) <span class="text-error">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input type="text" x-model="barcodeCode" @input="debounceUpdateBarcode()"
                            placeholder="Contoh: 8991234001011"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base font-mono text-brand-espresso focus:outline-none focus:border-brand-primary">
                        <button type="button" @click="saveBarcodeToVariant()"
                            :disabled="isProcessing"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border shrink-0">
                            <i class="ti ti-device-floppy text-base"></i>
                            <span>Simpan ke Master</span>
                        </button>
                    </div>
                    <p class="text-xs text-brand-warm-gray">Barcode standar EAN-13 Indonesia menggunakan awalan prefix 899.</p>
                </div>

                <!-- 3. Format Kertas / Layout Stiker -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Ukuran Kertas / Jenis Label <span class="text-error">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Format 1: Thermal 40x30 -->
                        <label class="flex flex-col p-3.5 rounded-xl border cursor-pointer transition"
                            :class="paperFormat === 'thermal_40x30' ? 'border-brand-primary bg-brand-soft-cream/30 text-brand-espresso' : 'border-brand-border bg-white text-brand-warm-gray hover:border-brand-warm-gray'">
                            <input type="radio" name="paperFormat" value="thermal_40x30" x-model="paperFormat" class="sr-only">
                            <span class="font-bold text-sm text-brand-espresso">Thermal 40x30 mm</span>
                            <span class="text-xs mt-0.5">Printer Thermal Satuan (Xprinter, Zebra)</span>
                        </label>

                        <!-- Format 2: Thermal 50x30 -->
                        <label class="flex flex-col p-3.5 rounded-xl border cursor-pointer transition"
                            :class="paperFormat === 'thermal_50x30' ? 'border-brand-primary bg-brand-soft-cream/30 text-brand-espresso' : 'border-brand-border bg-white text-brand-warm-gray hover:border-brand-warm-gray'">
                            <input type="radio" name="paperFormat" value="thermal_50x30" x-model="paperFormat" class="sr-only">
                            <span class="font-bold text-sm text-brand-espresso">Thermal 50x30 mm</span>
                            <span class="text-xs mt-0.5">Printer Thermal Sedang / Lebar</span>
                        </label>

                        <!-- Format 3: A4 Grid 3x8 -->
                        <label class="flex flex-col p-3.5 rounded-xl border cursor-pointer transition"
                            :class="paperFormat === 'a4_grid_24' ? 'border-brand-primary bg-brand-soft-cream/30 text-brand-espresso' : 'border-brand-border bg-white text-brand-warm-gray hover:border-brand-warm-gray'">
                            <input type="radio" name="paperFormat" value="a4_grid_24" x-model="paperFormat" class="sr-only">
                            <span class="font-bold text-sm text-brand-espresso">Stiker A4 Grid</span>
                            <span class="text-xs mt-0.5">Tom & Jerry 3x8 (24 Label / Lembar)</span>
                        </label>
                    </div>
                </div>

                <!-- 4. Jumlah Lembar / Label -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                            Jumlah Cetak Label <span class="text-error">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" x-model.number="printQuantity" min="1" max="500"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                            <span class="text-xs text-brand-warm-gray shrink-0" x-text="paperFormat === 'a4_grid_24' ? 'Label (' + Math.ceil(printQuantity/24) + ' Lbr A4)' : 'Stiker'"></span>
                        </div>
                    </div>

                    <!-- Tanggal Exp / Batch (Opsional) -->
                    <div class="space-y-1.5">
                        <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                            Keterangan Batch / Exp (Opsional)
                        </label>
                        <input type="text" x-model="customExpText" placeholder="Contoh: Exp: 12/2026 | B2610"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                    </div>
                </div>

                <!-- 5. Opsi Elemen Cetak (Checkboxes) -->
                <div class="space-y-2 pt-2 border-t border-brand-border/60">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Elemen yang Ditampilkan pada Stiker:
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs sm:text-sm">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showBrandName" class="checkbox checkbox-sm checkbox-primary rounded">
                            <span class="font-medium text-brand-espresso">Brand Halala Food</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showVariantName" class="checkbox checkbox-sm checkbox-primary rounded">
                            <span class="font-medium text-brand-espresso">Nama Produk & Varian</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showNetWeight" class="checkbox checkbox-sm checkbox-primary rounded">
                            <span class="font-medium text-brand-espresso">Berat Bersih / Pcs</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showRetailPrice" class="checkbox checkbox-sm checkbox-primary rounded">
                            <span class="font-medium text-brand-espresso">Harga Eceran (HET)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showWholesalePrice" class="checkbox checkbox-sm checkbox-primary rounded">
                            <span class="font-medium text-brand-espresso">Harga Grosir Toko</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showBarcodeText" class="checkbox checkbox-sm checkbox-primary rounded">
                            <span class="font-medium text-brand-espresso">Nomor Barcode</span>
                        </label>
                    </div>
                </div>

                <!-- Action Button: Open Print Preview -->
                <div class="pt-3 border-t border-brand-border">
                    <button type="button" @click="openPrintPreview()"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer w-full sm:w-auto">
                        <i class="ti ti-printer text-xl"></i>
                        <span>Pratinjau & Cetak Label</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Realtime Live Preview (Right 5 Cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white p-6 rounded-xl border border-brand-border space-y-4 sticky top-6">
                <div class="flex items-center justify-between border-b border-brand-border pb-3">
                    <h3 class="text-base font-bold text-brand-espresso">Pratinjau Label Real-Time</h3>
                    <span class="text-xs font-mono text-brand-warm-gray" x-text="paperFormat === 'thermal_40x30' ? '40 x 30 mm' : (paperFormat === 'thermal_50x30' ? '50 x 30 mm' : 'A4 Grid 3x8')"></span>
                </div>

                <!-- Visual Label Box (Scaled for display) -->
                <div class="bg-neutral-50 p-6 rounded-xl border border-dashed border-brand-border flex items-center justify-center min-h-64">

                    <!-- Single Label Mockup (40x30 / 50x30 / Standard Preview) -->
                    <div class="bg-white border-2 border-neutral-800 shadow-md p-3 text-neutral-900 rounded-sm select-none transition-all flex flex-col justify-between"
                        :class="paperFormat === 'thermal_40x30' ? 'w-56 h-44 text-xs' : (paperFormat === 'thermal_50x30' ? 'w-64 h-44 text-xs' : 'w-60 h-44 text-xs')">

                        <!-- Label Header: Brand -->
                        <div class="text-center" x-show="showBrandName">
                            <div class="font-black text-xs uppercase tracking-wider text-neutral-900 leading-tight">HALALA FOOD</div>
                            <div class="text-[9px] text-neutral-500 font-medium">Camilan Tradisional Berkualitas</div>
                        </div>

                        <!-- Product & Variant Name -->
                        <div class="text-center my-0.5" x-show="showVariantName">
                            <div class="font-extrabold text-[11px] leading-tight text-neutral-900" x-text="selectedVariant?.product_name || 'Ting-Ting Susu'"></div>
                            <div class="text-[10px] font-semibold text-neutral-700" x-text="selectedVariant?.variant_name || 'Pouch 200g'"></div>
                        </div>

                        <!-- Netto & Exp info -->
                        <div class="flex justify-between items-center text-[9px] font-medium text-neutral-600 px-1" x-show="showNetWeight || customExpText">
                            <span x-show="showNetWeight" x-text="`Netto: ${selectedVariant?.weight_grams || 200}g (${selectedVariant?.pcs_per_package || 10} pcs)`"></span>
                            <span x-show="customExpText" class="font-bold text-neutral-800" x-text="customExpText"></span>
                        </div>

                        <!-- Barcode SVG Render -->
                        <div class="my-1 flex flex-col items-center justify-center">
                            <div class="w-full flex justify-center overflow-hidden max-h-12" x-html="currentBarcodeSvg"></div>
                            <div class="font-mono text-[10px] tracking-widest font-bold mt-0.5" x-show="showBarcodeText" x-text="barcodeCode"></div>
                        </div>

                        <!-- Price Info -->
                        <div class="flex justify-between items-center border-t border-neutral-300 pt-1 px-1 font-bold text-[10px]">
                            <span x-show="showWholesalePrice" class="text-neutral-700" x-text="`Grosir: Rp ${new Intl.NumberFormat('id-ID').format(selectedVariant?.wholesale_price || 0)}`"></span>
                            <span x-show="showRetailPrice" class="text-neutral-900 font-extrabold ml-auto" x-text="`HET: Rp ${new Intl.NumberFormat('id-ID').format(selectedVariant?.retail_price || 0)}`"></span>
                        </div>
                    </div>

                </div>

                <!-- Guidance Info -->
                <div class="text-xs text-brand-warm-gray space-y-1 bg-brand-soft-cream/30 p-3.5 rounded-xl">
                    <div class="font-bold text-brand-espresso flex items-center gap-1.5">
                        <i class="ti ti-info-circle text-brand-primary text-sm"></i>
                        <span>Petunjuk Pencetakan:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-neutral-600 pl-1 text-[11px]">
                        <li>Untuk printer thermal, pilih ukuran kertas <b>40x30mm</b> atau <b>50x30mm</b> pada dialog cetak browser.</li>
                        <li>Untuk kertas Tom & Jerry A4, pilih orientasi <b>Potret (Portrait)</b> dan margin <b>None / Minimum</b>.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

</div>
