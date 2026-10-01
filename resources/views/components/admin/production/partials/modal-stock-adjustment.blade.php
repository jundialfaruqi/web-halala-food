<!-- MODAL: PENYESUAIAN STOK OPNAME -->
<div x-cloak x-show="showAdjustmentModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showAdjustmentModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showAdjustmentModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showAdjustmentModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-brand-border flex flex-col my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center">
                    <i class="ti ti-adjustments text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-brand-espresso" x-text="adjustmentModalTitle"></h3>
                    <p class="text-xs text-brand-warm-gray">Sinkronisasi stok fisik aktual hasil audit opname gudang.</p>
                </div>
            </div>
            <button type="button" @click="showAdjustmentModal = false"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="submitAdjustment()" class="p-6 space-y-4">

            <!-- Item Target Select (Raw Material or Finished Good) -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Pilih Item Target <span class="text-red-500">*</span>
                </label>
                <template x-if="adjustmentTargetType === 'raw_material'">
                    <select x-model="adjustmentForm.target_id"
                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                        <option value="" disabled>Pilih Bahan Baku</option>
                        @foreach ($rawMaterials as $mat)
                            <option value="{{ $mat->id }}">
                                {{ $mat->name }} (Stok Sistem: {{ number_format((float) $mat->stock_qty, 0, ',', '.') }} {{ $mat->unit }})
                            </option>
                        @endforeach
                    </select>
                </template>

                <template x-if="adjustmentTargetType === 'finished_good'">
                    <select x-model="adjustmentForm.target_id"
                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                        <option value="" disabled>Pilih Varian Produk</option>
                        @foreach ($productVariants as $var)
                            <option value="{{ $var->id }}">
                                {{ $var->product?->name }} - {{ $var->name }} (Stok Sistem: {{ $var->stock_qty }} pack)
                            </option>
                        @endforeach
                    </select>
                </template>
            </div>

            <!-- Actual Physical Stock Count -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Stok Fisik Aktual Hasil Opname <span class="text-red-500">*</span>
                </label>
                <input type="number" step="0.01" min="0" x-model="adjustmentForm.actual_stock" placeholder="Masukkan jumlah fisik nyata..."
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-mono font-bold focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Reason / Notes -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Alasan / Keterangan Penyesuaian <span class="text-red-500">*</span>
                </label>
                <textarea x-model="adjustmentForm.reason" rows="2" placeholder="Contoh: Hasil audit opname fisik akhir bulan..."
                    class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-brand-border">
                <button type="button" @click="showAdjustmentModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                    <template x-if="isProcessing">
                        <i class="ti ti-loader-2 animate-spin text-lg"></i>
                    </template>
                    <span>Simpan Penyesuaian</span>
                </button>
            </div>
        </form>
    </div>
</div>
