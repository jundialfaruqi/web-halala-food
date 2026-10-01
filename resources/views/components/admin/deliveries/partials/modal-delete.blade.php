<!-- Modal Konfirmasi Hapus Surat Jalan -->
<div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="showDeleteModal" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"
        @click="if (!isProcessing) showDeleteModal = false"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-brand-border p-6 text-center space-y-4">

            <div>
                <i class="ti ti-trash text-4xl text-red-600"></i>
            </div>

            <div>
                <h3 class="text-xl font-bold text-brand-espresso">Konfirmasi Hapus Surat Jalan</h3>
                <p class="text-sm text-brand-warm-gray mt-2 leading-relaxed" x-text="deleteTargetDescription"></p>
            </div>

            <div class="flex items-center justify-center gap-3 pt-2">
                <button type="button" @click="showDeleteModal = false" :disabled="isProcessing"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-sm sm:text-base font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                    Batal
                </button>

                <button type="button" @click="executeDelete()" :disabled="isProcessing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm sm:text-base font-bold shadow-xs transition cursor-pointer disabled:opacity-60">
                    <i x-show="isProcessing" class="ti ti-loader animate-spin text-base"></i>
                    <span x-text="isProcessing ? 'Menghapus...' : 'Ya, Hapus'"></span>
                </button>
            </div>

        </div>
    </div>
</div>
