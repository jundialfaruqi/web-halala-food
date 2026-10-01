<!-- Modal Print Preview & Printable Area -->
<div x-cloak x-show="showPrintModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="showPrintModal" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"
        @click="showPrintModal = false"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showPrintModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between shrink-0 bg-white">
                <div class="flex items-center gap-3">
                    <i class="ti ti-printer text-2xl text-brand-primary"></i>
                    <div>
                        <h2 class="text-xl font-bold text-brand-espresso">Pratinjau Cetak Label Barcode</h2>
                        <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5" x-text="`Total ${printQuantity} label • Format: ${paperFormat === 'thermal_40x30' ? 'Thermal 40x30mm' : (paperFormat === 'thermal_50x30' ? 'Thermal 50x30mm' : 'A4 Grid 3x8 (24/lembar)')}`"></p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="triggerPrint()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm sm:text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <i class="ti ti-printer text-lg"></i>
                        <span>Cetak Sekarang</span>
                    </button>
                    <button type="button" @click="showPrintModal = false"
                        class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Preview Scroll Container (Screen Mode) -->
            <div class="p-6 overflow-y-auto flex-1 bg-neutral-100">

                <!-- Printable Root Container (Targeted by @media print) -->
                <div id="printable-labels-area" class="bg-white mx-auto shadow-sm p-4 rounded-xl">

                    <!-- 1. Layout Thermal (40x30mm / 50x30mm) -->
                    <template x-if="paperFormat === 'thermal_40x30' || paperFormat === 'thermal_50x30'">
                        <div class="flex flex-wrap gap-4 justify-center thermal-print-container">
                            <template x-for="i in Array.from({length: printQuantity}, (_, index) => index + 1)" :key="i">
                                <div class="thermal-label border border-neutral-300 p-2.5 bg-white text-neutral-900 flex flex-col justify-between select-none box-border"
                                    :class="paperFormat === 'thermal_40x30' ? 'w-[40mm] min-h-[30mm]' : 'w-[50mm] min-h-[30mm]'">

                                    <!-- Brand Header -->
                                    <div class="text-center" x-show="showBrandName">
                                        <div class="font-black text-[9px] uppercase tracking-wider text-neutral-900 leading-tight">HALALA FOOD</div>
                                    </div>

                                    <!-- Product Info -->
                                    <div class="text-center my-0.5" x-show="showVariantName">
                                        <div class="font-extrabold text-[10px] leading-tight text-neutral-900" x-text="selectedVariant?.product_name || 'Ting-Ting Susu'"></div>
                                        <div class="text-[8.5px] font-semibold text-neutral-700" x-text="selectedVariant?.variant_name || 'Pouch 200g'"></div>
                                    </div>

                                    <!-- Netto / Exp -->
                                    <div class="flex justify-between items-center text-[7.5px] font-medium text-neutral-600 px-0.5" x-show="showNetWeight || customExpText">
                                        <span x-show="showNetWeight" x-text="`Netto: ${selectedVariant?.weight_grams || 200}g (${selectedVariant?.pcs_per_package || 10} pcs)`"></span>
                                        <span x-show="customExpText" class="font-bold text-neutral-800" x-text="customExpText"></span>
                                    </div>

                                    <!-- Barcode SVG -->
                                    <div class="my-0.5 flex flex-col items-center justify-center">
                                        <div class="w-full flex justify-center overflow-hidden max-h-8" x-html="currentBarcodeSvg"></div>
                                        <div class="font-mono text-[8.5px] tracking-widest font-bold mt-0.5" x-show="showBarcodeText" x-text="barcodeCode"></div>
                                    </div>

                                    <!-- Price Footer -->
                                    <div class="flex justify-between items-center border-t border-neutral-300 pt-0.5 px-0.5 text-[8.5px] font-bold">
                                        <span x-show="showWholesalePrice" class="text-neutral-700" x-text="`Grosir: Rp ${new Intl.NumberFormat('id-ID').format(selectedVariant?.wholesale_price || 0)}`"></span>
                                        <span x-show="showRetailPrice" class="text-neutral-900 font-extrabold ml-auto" x-text="`HET: Rp ${new Intl.NumberFormat('id-ID').format(selectedVariant?.retail_price || 0)}`"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- 2. Layout A4 Grid (Tom & Jerry 3 x 8 = 24 labels per sheet) -->
                    <template x-if="paperFormat === 'a4_grid_24'">
                        <div class="a4-print-sheet mx-auto bg-white p-6 max-w-[210mm]">
                            <div class="grid grid-cols-3 gap-3">
                                <template x-for="i in Array.from({length: printQuantity}, (_, index) => index + 1)" :key="i">
                                    <div class="a4-grid-label border border-neutral-300 rounded-sm p-3 bg-white text-neutral-900 flex flex-col justify-between select-none min-h-[34mm] box-border">

                                        <!-- Brand Header -->
                                        <div class="text-center" x-show="showBrandName">
                                            <div class="font-black text-[10px] uppercase tracking-wider text-neutral-900 leading-tight">HALALA FOOD</div>
                                        </div>

                                        <!-- Product Info -->
                                        <div class="text-center my-0.5" x-show="showVariantName">
                                            <div class="font-extrabold text-[11px] leading-tight text-neutral-900" x-text="selectedVariant?.product_name || 'Ting-Ting Susu'"></div>
                                            <div class="text-[9.5px] font-semibold text-neutral-700" x-text="selectedVariant?.variant_name || 'Pouch 200g'"></div>
                                        </div>

                                        <!-- Netto / Exp -->
                                        <div class="flex justify-between items-center text-[8.5px] font-medium text-neutral-600 px-0.5" x-show="showNetWeight || customExpText">
                                            <span x-show="showNetWeight" x-text="`Netto: ${selectedVariant?.weight_grams || 200}g (${selectedVariant?.pcs_per_package || 10} pcs)`"></span>
                                            <span x-show="customExpText" class="font-bold text-neutral-800" x-text="customExpText"></span>
                                        </div>

                                        <!-- Barcode SVG -->
                                        <div class="my-0.5 flex flex-col items-center justify-center">
                                            <div class="w-full flex justify-center overflow-hidden max-h-9" x-html="currentBarcodeSvg"></div>
                                            <div class="font-mono text-[9px] tracking-widest font-bold mt-0.5" x-show="showBarcodeText" x-text="barcodeCode"></div>
                                        </div>

                                        <!-- Price Footer -->
                                        <div class="flex justify-between items-center border-t border-neutral-300 pt-0.5 px-0.5 text-[9px] font-bold">
                                            <span x-show="showWholesalePrice" class="text-neutral-700" x-text="`Grosir: Rp ${new Intl.NumberFormat('id-ID').format(selectedVariant?.wholesale_price || 0)}`"></span>
                                            <span x-show="showRetailPrice" class="text-neutral-900 font-extrabold ml-auto" x-text="`HET: Rp ${new Intl.NumberFormat('id-ID').format(selectedVariant?.retail_price || 0)}`"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-brand-border bg-white flex items-center justify-between shrink-0">
                <span class="text-xs sm:text-sm text-brand-warm-gray">Pastikan printer terhubung dan ukuran kertas sesuai sebelum menekan Cetak.</span>
                <div class="flex items-center gap-3">
                    <button type="button" @click="showPrintModal = false"
                        class="px-5 py-2.5 rounded-xl border border-brand-border text-sm sm:text-base font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        Tutup
                    </button>
                    <button type="button" @click="triggerPrint()"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm sm:text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <i class="ti ti-printer text-lg"></i>
                        <span>Cetak Sekarang</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Print Stylesheet -->
<style>
@media print {
    /* Hide everything on page except #printable-labels-area */
    body * {
        visibility: hidden !important;
    }
    #printable-labels-area, #printable-labels-area * {
        visibility: visible !important;
    }
    #printable-labels-area {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
    }
    .thermal-label {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin-bottom: 2mm !important;
    }
    .a4-grid-label {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    @page {
        margin: 4mm;
        size: auto;
    }
}
</style>
