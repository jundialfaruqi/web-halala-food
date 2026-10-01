<!-- Modal Konfirmasi Serah Terima di Toko -->
<div x-cloak x-show="showReceiptModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-brand-espresso/60 backdrop-blur-xs flex items-center justify-center p-4">

    <div @click.away="if (!isProcessing) showReceiptModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-2xl border border-brand-border shadow-2xl w-full max-w-2xl overflow-hidden my-8">

        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-espresso">Konfirmasi Serah Terima Toko</h2>
                <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5" x-text="`No. Surat Jalan: ${receiptData.delivery_number} • ${receiptData.partner_name}`"></p>
            </div>
            <button type="button" @click="showReceiptModal = false" :disabled="isProcessing"
                class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitReceiptConfirmation" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 1. Nama Penerima di Toko -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Nama Staf Penerima Toko <span class="text-error">*</span>
                    </label>
                    <input type="text" x-model="receiptForm.receiver_name"
                        placeholder="Contoh: Bpk. Hendra Wijaya"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                </div>

                <!-- 2. No. HP Penerima -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        No. HP / WhatsApp Penerima
                    </label>
                    <input type="text" x-model="receiptForm.receiver_phone"
                        placeholder="Contoh: 08123456789"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                </div>
            </div>

            <!-- 3. Rincian Barang Diterima vs Retur -->
            <div class="space-y-2 pt-2 border-t border-brand-border/60">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Verifikasi Kuantitas Barang Serah Terima:
                </label>

                <div class="space-y-3">
                    <template x-for="item in receiptForm.items" :key="item.product_variant_id">
                        <div class="p-3.5 bg-neutral-50 rounded-xl border border-brand-border space-y-2">
                            <div class="flex justify-between items-center text-sm font-bold text-brand-espresso">
                                <span x-text="item.full_name"></span>
                                <span class="text-xs font-semibold text-brand-warm-gray" x-text="`Dikirim: ${item.qty_sent} pack`"></span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <!-- Qty Diterima Baik -->
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-emerald-700">Diterima Baik (Pack)</label>
                                    <input type="number" x-model.number="item.qty_accepted" min="0" :max="item.qty_sent"
                                        @input="item.qty_returned = Math.max(0, item.qty_sent - item.qty_accepted)"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm font-bold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Qty Retur / Rusak -->
                                <div class="space-y-1">
                                    <label class="block text-xs font-semibold text-red-600">Retur / Batal (Pack)</label>
                                    <input type="number" x-model.number="item.qty_returned" min="0" :max="item.qty_sent"
                                        @input="item.qty_accepted = Math.max(0, item.qty_sent - item.qty_returned)"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm font-bold text-red-600 focus:outline-none focus:border-brand-primary">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Actions -->
            <div class="pt-4 border-t border-brand-border flex items-center justify-end gap-3">
                <button type="button" @click="showReceiptModal = false" :disabled="isProcessing"
                    class="px-5 py-2.5 text-base font-semibold text-brand-warm-gray hover:text-brand-espresso transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                    <i class="ti ti-checkbox text-lg"></i>
                    <span>Konfirmasi Serah Terima Selesai</span>
                </button>
            </div>

        </form>

    </div>
</div>
