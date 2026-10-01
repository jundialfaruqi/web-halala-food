<!-- MODAL: RENCANA BATCH PRODUKSI -->
<div x-cloak x-show="showBatchModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showBatchModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showBatchModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showBatchModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-brand-border flex flex-col max-h-[90vh] my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
            <div>
                <h3 class="text-xl font-bold text-brand-espresso" x-text="batchModalTitle"></h3>
                <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5">
                    Buat jadwal dan takaran rencana masak batch adonan camilan di dapur.
                </p>
            </div>
            <button type="button" @click="showBatchModal = false"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <form @submit.prevent="submitBatch()" class="flex-1 overflow-y-auto p-6 space-y-4">

            <!-- Global Error Banner -->
            <template x-if="batchFormError">
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3">
                    <i class="ti ti-alert-circle text-red-600 text-xl shrink-0 mt-0.5"></i>
                    <p class="text-sm font-semibold text-red-700" x-text="batchFormError"></p>
                </div>
            </template>

            <!-- Recipe Select -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Pilih Formula Resep Produk <span class="text-red-500">*</span>
                </label>
                <select x-model="batchForm.recipe_id"
                    @input="delete batchErrors.recipeId; batchFormError = ''"
                    :class="batchErrors.recipeId ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                    class="select select-lg w-full bg-white border rounded-xl text-base text-brand-espresso font-medium">
                    <option value="" disabled>Pilih Formula Resep Masak</option>
                    @foreach ($recipes as $rcp)
                        <option value="{{ $rcp->id }}">
                            {{ $rcp->name }} (Output Standar: {{ $rcp->batch_output_qty }} pack)
                        </option>
                    @endforeach
                </select>
                <template x-if="batchErrors.recipeId">
                    <p class="text-xs text-red-600 font-semibold mt-1" x-text="batchErrors.recipeId[0]"></p>
                </template>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Planned Output Qty -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Target Jumlah Rencana (Pack) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" min="1" x-model="batchForm.planned_qty" placeholder="Contoh: 50"
                        @input="delete batchErrors.plannedQty; batchFormError = ''"
                        :class="batchErrors.plannedQty ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                    <template x-if="batchErrors.plannedQty">
                        <p class="text-xs text-red-600 font-semibold mt-1" x-text="batchErrors.plannedQty[0]"></p>
                    </template>
                </div>

                <!-- Cook Assigned -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Juru Masak / Penanggung Jawab
                    </label>
                    <select x-model="batchForm.user_id"
                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                        <option value="">Pilih Juru Masak</option>
                        @foreach ($cooks as $cook)
                            <option value="{{ $cook->id }}">{{ $cook->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Catatan Rencana Produksi (Opsional)
                </label>
                <textarea x-model="batchForm.notes" rows="3" placeholder="Catatan shift pagi/siang, permintaan kemasan khusus, dll..."
                    class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-brand-border">
                <button type="button" @click="showBatchModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                    <template x-if="isProcessing">
                        <i class="ti ti-loader-2 animate-spin text-lg"></i>
                    </template>
                    <span>Simpan Rencana Produksi</span>
                </button>
            </div>
        </form>
    </div>
</div>
