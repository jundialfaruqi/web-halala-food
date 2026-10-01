<!-- Modal Konfirmasi Penerimaan Barang di Gudang -->
<div x-cloak x-show="showReceiveModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-brand-espresso/60 backdrop-blur-xs flex items-center justify-center p-4">

    <div @click.away="if (!isProcessing) showReceiveModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-2xl border border-brand-border shadow-2xl w-full max-w-2xl overflow-hidden my-8">

        <!-- Header -->
        <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-brand-espresso">Konfirmasi Penerimaan Barang di Gudang</h2>
                <p class="text-xs text-brand-warm-gray mt-0.5">
                    Nomor PO: <strong class="font-mono text-brand-primary" x-text="receiveForm.po_number"></strong>
                </p>
            </div>
            <button type="button" @click="showReceiveModal = false" :disabled="isProcessing"
                class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Automation Notice Alert -->
        <div class="mx-6 mt-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs sm:text-sm text-emerald-800 flex items-start gap-2.5">
            <i class="ti ti-info-circle text-lg shrink-0 text-emerald-600 mt-0.5"></i>
            <div>
                <p class="font-bold">Otomatisasi Inventori & HPP Berjalan:</p>
                <p class="mt-0.5 text-emerald-700/90">
                    Konfirmasi ini akan otomatis menambah kuantitas stok bahan baku di gudang dan memperbarui <strong>HPP Rata-Rata Berjalan (Moving Average Cost)</strong> secara akurat.
                </p>
            </div>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitReceiveGoods" class="p-6 space-y-5">
            <div class="space-y-3">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Kuantitas Aktual yang Diterima di Gudang
                </label>

                <div class="space-y-2.5 max-h-[50vh] overflow-y-auto pr-1">
                    <template x-for="item in receiveForm.items" :key="item.item_id">
                        <div class="p-3.5 bg-neutral-50 rounded-xl border border-brand-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="font-bold text-brand-espresso text-sm sm:text-base" x-text="item.material_name"></div>
                                <div class="text-xs text-brand-warm-gray mt-0.5">
                                    Dipesan: <strong class="text-brand-espresso font-mono" x-text="`${item.qty_ordered} ${item.material_unit}`"></strong>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <label class="text-xs font-semibold text-brand-espresso whitespace-nowrap">Qty Masuk:</label>
                                <div class="relative w-36">
                                    <input type="number" step="0.01" min="0" x-model.number="item.qty_received" required
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-xl text-sm font-bold text-brand-espresso focus:outline-none focus:border-brand-primary text-right pr-10">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-brand-warm-gray font-medium pointer-events-none" x-text="item.material_unit"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-brand-border flex items-center justify-end gap-3">
                <button type="button" @click="showReceiveModal = false" :disabled="isProcessing"
                    class="px-5 py-2.5 bg-neutral-100 hover:bg-neutral-200 text-brand-espresso text-base font-semibold rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                    <i x-show="isProcessing" class="ti ti-loader animate-spin text-lg"></i>
                    <span x-text="isProcessing ? 'Memproses...' : 'Konfirmasi Masuk Gudang'"></span>
                </button>
            </div>
        </form>

    </div>
</div>
