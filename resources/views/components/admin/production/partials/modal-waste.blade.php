<!-- MODAL: CATAT BAHAN RUSAK / WASTE -->
<div x-cloak x-show="showWasteModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showWasteModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showWasteModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showWasteModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-brand-border flex flex-col my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <i class="ti ti-trash text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-brand-espresso">Catat Bahan Rusak / Waste</h3>
                    <p class="text-xs text-brand-warm-gray">Pengurangan stok akibat bahan kedaluwarsa, tumpah, atau cacat.</p>
                </div>
            </div>
            <button type="button" @click="showWasteModal = false"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="submitWaste()" class="p-6 space-y-4">

            <!-- Raw Material Select -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Pilih Bahan Baku <span class="text-red-500">*</span>
                </label>
                <select x-model="wasteForm.raw_material_id"
                    class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                    <option value="" disabled>Pilih Bahan Baku</option>
                    @foreach ($rawMaterials as $mat)
                        <option value="{{ $mat->id }}">
                            {{ $mat->name }} (Stok Saat Ini: {{ number_format((float) $mat->stock_qty, 0, ',', '.') }} {{ $mat->unit }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Quantity Lost -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Jumlah Rusak / Terbuang <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" x-model="wasteForm.quantity" placeholder="Contoh: 500"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Reason -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Alasan Kerusakan <span class="text-red-500">*</span>
                    </label>
                    <select x-model="wasteForm.reason"
                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                        <option value="spilled">Tumpah di Dapur</option>
                        <option value="damaged">Kemasan Rusak / Sobek</option>
                        <option value="expired">Kedaluwarsa / Basi</option>
                        <option value="failed_batch">Adonan Gagal Masak</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Kronologi & Keterangan (Opsional)
                </label>
                <textarea x-model="wasteForm.notes" rows="2" placeholder="Penjelasan penyebab kerusakan bahan..."
                    class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-brand-border">
                <button type="button" @click="showWasteModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                    <template x-if="isProcessing">
                        <i class="ti ti-loader-2 animate-spin text-lg"></i>
                    </template>
                    <span>Simpan Laporan Waste</span>
                </button>
            </div>
        </form>
    </div>
</div>
