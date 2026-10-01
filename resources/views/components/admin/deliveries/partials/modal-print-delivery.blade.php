<!-- Modal Cetak Surat Jalan Resmi (Delivery Order) -->
<div x-cloak x-show="showPrintDeliveryModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="showPrintDeliveryModal" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"
        @click="showPrintDeliveryModal = false"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showPrintDeliveryModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Modal Header (Screen Only) -->
            <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between shrink-0 bg-white">
                <div class="flex items-center gap-3">
                    <i class="ti ti-printer text-2xl text-brand-primary"></i>
                    <div>
                        <h2 class="text-xl font-bold text-brand-espresso">Pratinjau Surat Jalan Resmi</h2>
                        <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5" x-text="printDeliveryData?.delivery_number || ''"></p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="triggerPrintDelivery()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm sm:text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <i class="ti ti-printer text-lg"></i>
                        <span>Cetak Surat Jalan</span>
                    </button>
                    <button type="button" @click="showPrintDeliveryModal = false"
                        class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Scrollable Printable Container -->
            <div class="p-6 overflow-y-auto flex-1 bg-neutral-100">

                <div id="printable-delivery-area" class="bg-white p-8 max-w-[210mm] mx-auto shadow-sm rounded-xl text-neutral-900 border border-neutral-200">

                    <!-- Header Kop Surat -->
                    <div class="flex items-start justify-between border-b-2 border-neutral-800 pb-4">
                        <div class="space-y-1">
                            <h1 class="text-2xl font-black tracking-tight text-neutral-900 uppercase">HALALA FOOD</h1>
                            <p class="text-xs font-semibold text-neutral-600">Produsen Camilan Tradisional Ting-Ting Susu & Marie Wijen</p>
                            <p class="text-xs text-neutral-500">Jl. Raya Industri Pangan No. 88, Jakarta • Telp/WA: 0812-3456-7890</p>
                        </div>

                        <div class="text-right">
                            <div class="inline-block bg-neutral-900 text-white text-xs font-bold uppercase tracking-widest px-3 py-1 rounded">
                                SURAT JALAN (DELIVERY ORDER)
                            </div>
                            <div class="font-mono font-bold text-lg text-neutral-900 mt-2" x-text="printDeliveryData?.delivery_number"></div>
                            <div class="text-xs text-neutral-600 font-medium" x-text="`Tanggal: ${printDeliveryData?.created_at || ''}`"></div>
                        </div>
                    </div>

                    <!-- Meta Information Grid -->
                    <div class="grid grid-cols-2 gap-6 my-5 text-xs">
                        <div class="bg-neutral-50 p-3.5 rounded-lg border border-neutral-200 space-y-1">
                            <span class="font-bold text-neutral-500 uppercase tracking-wider block text-[10px]">Tujuan Pengiriman:</span>
                            <div class="font-bold text-sm text-neutral-900" x-text="printDeliveryData?.partner_name"></div>
                            <div class="text-neutral-700" x-text="printDeliveryData?.partner_address"></div>
                            <div class="text-neutral-600" x-text="`Kota: ${printDeliveryData?.partner_city || '-'} • Telp/WA: ${printDeliveryData?.partner_phone || '-'}`"></div>
                        </div>

                        <div class="bg-neutral-50 p-3.5 rounded-lg border border-neutral-200 space-y-1">
                            <span class="font-bold text-neutral-500 uppercase tracking-wider block text-[10px]">Informasi Kurir & Status:</span>
                            <div class="font-bold text-sm text-neutral-900" x-text="`Kurir: ${printDeliveryData?.courier_name || 'Belum Ditugaskan'}`"></div>
                            <div class="text-neutral-700" x-text="`Status: ${printDeliveryData?.status_label}`"></div>
                            <div class="text-neutral-600" x-text="`Waktu Berangkat: ${printDeliveryData?.dispatched_at || '-'}`"></div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table class="w-full text-left border-collapse my-4 text-xs">
                        <thead>
                            <tr class="border-y-2 border-neutral-800 bg-neutral-100 font-bold uppercase tracking-wider text-neutral-900">
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3">Nama Produk & Kemasan</th>
                                <th class="py-2.5 px-3 w-28 text-center">Qty Kirim</th>
                                <th class="py-2.5 px-3 w-28 text-center">Qty Diterima</th>
                                <th class="py-2.5 px-3 w-28 text-right">Harga Satuan</th>
                                <th class="py-2.5 px-3 w-32 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            <template x-for="(item, idx) in printDeliveryData?.items" :key="item.id">
                                <tr>
                                    <td class="py-2.5 px-3 text-center font-medium" x-text="idx + 1"></td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-neutral-900" x-text="item.full_name"></div>
                                        <div class="text-[10px] text-neutral-500 font-mono" x-text="`SKU: ${item.sku_code}`"></div>
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-bold text-neutral-900" x-text="`${item.qty_sent} pack`"></td>
                                    <td class="py-2.5 px-3 text-center font-semibold text-neutral-700" x-text="item.qty_accepted ? `${item.qty_accepted} pack` : '-'"></td>
                                    <td class="py-2.5 px-3 text-right font-medium whitespace-nowrap" x-text="item.formatted_unit_price"></td>
                                    <td class="py-2.5 px-3 text-right font-bold text-neutral-900 whitespace-nowrap" x-text="item.formatted_subtotal"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-neutral-800 font-bold text-xs bg-neutral-50">
                                <td colspan="4" class="py-2.5 px-3 text-right uppercase">Total Nilai Tagihan Pengiriman:</td>
                                <td colspan="2" class="py-2.5 px-3 text-right text-sm font-black whitespace-nowrap" x-text="printDeliveryData?.formatted_total_amount"></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Notes -->
                    <div x-show="printDeliveryData?.notes" class="my-4 text-xs bg-neutral-50 p-3 rounded border border-neutral-200">
                        <span class="font-bold block">Catatan Pengantaran:</span>
                        <span class="text-neutral-700 italic" x-text="printDeliveryData?.notes"></span>
                    </div>

                    <!-- 3 Signature Boxes -->
                    <div class="grid grid-cols-3 gap-6 pt-8 mt-6 border-t border-neutral-300 text-center text-xs">
                        <div class="space-y-12">
                            <span class="font-bold text-neutral-700 block">Pengirim (Gudang / Dapur)</span>
                            <div class="border-b border-neutral-400 w-36 mx-auto"></div>
                            <span class="text-[10px] text-neutral-500">( Tanda Tangan & Nama Terang )</span>
                        </div>

                        <div class="space-y-12">
                            <span class="font-bold text-neutral-700 block">Kurir Pengantar</span>
                            <div class="border-b border-neutral-400 w-36 mx-auto"></div>
                            <span class="text-[10px] text-neutral-500" x-text="`(${printDeliveryData?.courier_name || '........................'})`"></span>
                        </div>

                        <div class="space-y-12">
                            <span class="font-bold text-neutral-700 block">Penerima (Mitra Toko)</span>
                            <div class="border-b border-neutral-400 w-36 mx-auto"></div>
                            <span class="text-[10px] text-neutral-500" x-text="printDeliveryData?.receiver_name ? `(${printDeliveryData.receiver_name})` : '( Cap Toko & TTD Penerima )'"></span>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Modal Footer (Screen Only) -->
            <div class="px-6 py-4 border-t border-brand-border bg-white flex items-center justify-between shrink-0">
                <span class="text-xs sm:text-sm text-brand-warm-gray">Pastikan format kertas A4 atau Continuous Form sebelum mencetak.</span>
                <div class="flex items-center gap-3">
                    <button type="button" @click="showPrintDeliveryModal = false"
                        class="px-5 py-2.5 rounded-xl border border-brand-border text-sm sm:text-base font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        Tutup
                    </button>
                    <button type="button" @click="triggerPrintDelivery()"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm sm:text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <i class="ti ti-printer text-lg"></i>
                        <span>Cetak Sekarang</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printable-delivery-area, #printable-delivery-area * {
        visibility: visible !important;
    }
    #printable-delivery-area {
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
    @page {
        margin: 10mm;
        size: auto;
    }
}
</style>
