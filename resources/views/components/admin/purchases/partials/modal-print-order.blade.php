<!-- Modal Cetak Purchase Order (PO) Resmi -->
<div x-cloak x-show="showPrintPoModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="showPrintPoModal" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"
        @click="showPrintPoModal = false"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showPrintPoModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Modal Header (Screen Only) -->
            <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between shrink-0 bg-white">
                <div class="flex items-center gap-3">
                    <i class="ti ti-printer text-2xl text-brand-primary"></i>
                    <div>
                        <h2 class="text-xl font-bold text-brand-espresso">Pratinjau Purchase Order (PO)</h2>
                        <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5" x-text="printPoData?.po_number || ''"></p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="triggerPrintPo()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm sm:text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <i class="ti ti-printer text-lg"></i>
                        <span>Cetak Dokumen PO</span>
                    </button>
                    <button type="button" @click="showPrintPoModal = false"
                        class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Scrollable Printable Container -->
            <div class="p-6 overflow-y-auto flex-1 bg-neutral-100">

                <div id="printable-po-area" class="bg-white p-8 max-w-[210mm] mx-auto shadow-sm rounded-xl text-neutral-900 border border-neutral-200">

                    <!-- Header Kop Surat -->
                    <div class="flex items-start justify-between border-b-2 border-neutral-800 pb-4">
                        <div class="space-y-1">
                            <h1 class="text-2xl font-black tracking-tight text-neutral-900 uppercase">HALALA FOOD</h1>
                            <p class="text-xs font-semibold text-neutral-600">Produsen Camilan Tradisional Ting-Ting Susu & Marie Wijen</p>
                            <p class="text-xs text-neutral-500">Jl. Raya Industri Pangan No. 88, Jakarta • Telp/WA: 0812-3456-7890</p>
                        </div>

                        <div class="text-right">
                            <div class="inline-block bg-neutral-900 text-white text-xs font-bold uppercase tracking-widest px-3 py-1 rounded">
                                PURCHASE ORDER (PO)
                            </div>
                            <div class="font-mono font-bold text-lg text-neutral-900 mt-2" x-text="printPoData?.po_number"></div>
                            <div class="text-xs text-neutral-600 font-medium" x-text="`Tanggal: ${printPoData?.formatted_order_date || ''}`"></div>
                        </div>
                    </div>

                    <!-- Meta Information Grid -->
                    <div class="grid grid-cols-2 gap-6 my-5 text-xs">
                        <div class="bg-neutral-50 p-3.5 rounded-lg border border-neutral-200 space-y-1">
                            <span class="font-bold text-neutral-500 uppercase tracking-wider block text-[10px]">Supplier Dituju:</span>
                            <div class="font-bold text-sm text-neutral-900" x-text="printPoData?.supplier_name"></div>
                            <div class="text-neutral-700" x-text="printPoData?.supplier_code"></div>
                            <div class="text-neutral-600" x-text="`Syarat Pembayaran: ${printPoData?.supplier_payment_terms || '-'}`"></div>
                            <div class="text-neutral-600" x-text="`No. Kontak: ${printPoData?.supplier_phone || '-'}`"></div>
                        </div>

                        <div class="bg-neutral-50 p-3.5 rounded-lg border border-neutral-200 space-y-1">
                            <span class="font-bold text-neutral-500 uppercase tracking-wider block text-[10px]">Informasi Pesanan:</span>
                            <div class="font-bold text-sm text-neutral-900" x-text="`Status: ${printPoData?.status_label}`"></div>
                            <div class="text-neutral-700" x-text="`Jatuh Tempo: ${printPoData?.formatted_due_date || 'Tunai/Sesuai Kesepakatan'}`"></div>
                            <div class="text-neutral-600" x-text="`Dibuat Oleh: ${printPoData?.creator_name || 'Admin Pengadaan'}`"></div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <table class="w-full text-left border-collapse my-4 text-xs">
                        <thead>
                            <tr class="border-y-2 border-neutral-800 bg-neutral-100 font-bold uppercase tracking-wider text-neutral-900">
                                <th class="py-2.5 px-3 w-10 text-center">No</th>
                                <th class="py-2.5 px-3">Nama Bahan Baku</th>
                                <th class="py-2.5 px-3 w-28 text-center">Kuantitas Pesan</th>
                                <th class="py-2.5 px-3 w-28 text-center">Kuantitas Terima</th>
                                <th class="py-2.5 px-3 w-32 text-right">Harga Satuan</th>
                                <th class="py-2.5 px-3 w-36 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            <template x-for="(item, idx) in printPoData?.items" :key="item.id">
                                <tr>
                                    <td class="py-2.5 px-3 text-center font-medium" x-text="idx + 1"></td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-neutral-900" x-text="item.material_name"></div>
                                        <div class="text-[10px] text-neutral-500 font-mono" x-text="`Kode: ${item.material_code}`"></div>
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-bold" x-text="`${item.qty_ordered} ${item.material_unit}`"></td>
                                    <td class="py-2.5 px-3 text-center" x-text="`${item.qty_received} ${item.material_unit}`"></td>
                                    <td class="py-2.5 px-3 text-right font-mono whitespace-nowrap" x-text="item.formatted_unit_price"></td>
                                    <td class="py-2.5 px-3 text-right font-bold text-neutral-900 font-mono whitespace-nowrap" x-text="item.formatted_subtotal"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-neutral-800 bg-neutral-50 font-bold">
                                <td colspan="5" class="py-3 px-3 text-right text-xs uppercase tracking-wider">Total Nilai Pembelian:</td>
                                <td class="py-3 px-3 text-right text-sm font-black text-neutral-900 font-mono whitespace-nowrap" x-text="printPoData?.formatted_total_amount"></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Catatan Tambahan -->
                    <div x-show="printPoData?.notes" class="my-4 p-3 bg-neutral-50 border border-neutral-200 rounded text-xs">
                        <strong class="text-neutral-700 block mb-0.5">Catatan Instruksi Pengiriman / Pembelian:</strong>
                        <p class="text-neutral-600" x-text="printPoData?.notes"></p>
                    </div>

                    <!-- Tanda Tangan Pengesahan (Signatures) -->
                    <div class="grid grid-cols-3 gap-6 pt-10 text-center text-xs">
                        <div class="space-y-16">
                            <p class="font-semibold text-neutral-600">Dibuat Oleh,</p>
                            <div>
                                <div class="border-b border-neutral-400 w-36 mx-auto mb-1"></div>
                                <p class="font-bold text-neutral-900" x-text="printPoData?.creator_name || 'Staff Purchasing'"></p>
                                <p class="text-[10px] text-neutral-500">Purchasing / Admin</p>
                            </div>
                        </div>

                        <div class="space-y-16">
                            <p class="font-semibold text-neutral-600">Disetujui Oleh,</p>
                            <div>
                                <div class="border-b border-neutral-400 w-36 mx-auto mb-1"></div>
                                <p class="font-bold text-neutral-900">Manager Operasional</p>
                                <p class="text-[10px] text-neutral-500">Halala Food</p>
                            </div>
                        </div>

                        <div class="space-y-16">
                            <p class="font-semibold text-neutral-600">Konfirmasi Supplier,</p>
                            <div>
                                <div class="border-b border-neutral-400 w-36 mx-auto mb-1"></div>
                                <p class="font-bold text-neutral-900" x-text="printPoData?.supplier_name || 'Supplier'"></p>
                                <p class="text-[10px] text-neutral-500">Tanda Tangan & Cap</p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printable-po-area, #printable-po-area * {
        visibility: visible;
    }
    #printable-po-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 20px !important;
        box-shadow: none !important;
        border: none !important;
    }
}
</style>
