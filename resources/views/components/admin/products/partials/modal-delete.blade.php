<!-- DYNAMIC DELETE CONFIRMATION MODAL -->
<div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showDeleteModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-brand-border p-6 space-y-5">

        <!-- Icon & Header -->
        <div class="flex items-start gap-4">
            <div class="size-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                <i class="ti ti-alert-triangle text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-brand-espresso">Konfirmasi Hapus Data</h3>
                <p class="text-sm text-brand-warm-gray mt-1" x-text="deleteTarget.description"></p>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" @click="showDeleteModal = false"
                class="px-4 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                Batal
            </button>
            <button type="button" @click="executeDelete()" :disabled="isProcessing"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                <template x-if="isProcessing">
                    <i class="ti ti-loader-2 animate-spin text-lg"></i>
                </template>
                <span>Ya, Hapus Data</span>
            </button>
        </div>
    </div>
</div>
