<!-- MODAL: KONFIRMASI SELESAI MASAK BATCH -->
<div x-cloak x-show="showFinishBatchModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showFinishBatchModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showFinishBatchModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showFinishBatchModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-brand-border flex flex-col my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-xl bg-green-50 text-green-700 flex items-center justify-center">
                    <i class="ti ti-check text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-brand-espresso">Konfirmasi Selesai Masak</h3>
                    <p class="text-xs text-brand-warm-gray" x-text="'Batch: ' + finishBatchData.batch_number"></p>
                </div>
            </div>
            <button type="button" @click="showFinishBatchModal = false"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="submitFinishBatch()" class="p-6 space-y-4">

            <!-- Information Alert -->
            <div class="p-3.5 rounded-xl bg-brand-soft-cream/40 border border-brand-border/60 text-xs sm:text-sm text-brand-espresso">
                <div class="flex items-center justify-between font-bold">
                    <span>Target Rencana Awal:</span>
                    <span class="font-mono text-brand-primary" x-text="finishBatchData.planned_qty + ' pack'"></span>
                </div>
                <p class="text-xs text-brand-warm-gray mt-1">
                    Barang jadi yang bagus akan otomatis masuk ke stok display siap jual, dan HPP batch akan terkalkulasi secara akurat.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Actual Good Qty -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Hasil Bagus Siap Jual (Pack) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" min="1" x-model="finishBatchData.actual_qty_good"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-lg font-bold font-mono text-emerald-600 focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Actual Bad/Reject Qty -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Hasil Cacat / Gagal (Pack)
                    </label>
                    <input type="number" min="0" x-model="finishBatchData.actual_qty_bad"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-lg font-bold font-mono text-red-600 focus:outline-none focus:border-brand-primary">
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Catatan Selesai Masak (Opsional)
                </label>
                <textarea x-model="finishBatchData.notes" rows="2" placeholder="Kualitas kemasan, rasa, atau kendala penggorengan..."
                    class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-brand-border">
                <button type="button" @click="showFinishBatchModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                    <template x-if="isProcessing">
                        <i class="ti ti-loader-2 animate-spin text-lg"></i>
                    </template>
                    <span>Selesaikan & Tambah Stok</span>
                </button>
            </div>
        </form>
    </div>
</div>
