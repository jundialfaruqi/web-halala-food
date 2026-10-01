<!-- MODAL: BAHAN BAKU & KEMASAN -->
<div x-cloak x-show="showRawMaterialModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showRawMaterialModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showRawMaterialModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showRawMaterialModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-brand-border flex flex-col max-h-[90vh] my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
            <div>
                <h3 class="text-xl font-bold text-brand-espresso" x-text="materialModalTitle"></h3>
                <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5">
                    Informasi master bahan baku dapur, kemasan, satuan unit, dan stok minimum gudang.
                </p>
            </div>
            <button type="button" @click="showRawMaterialModal = false"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <form @submit.prevent="submitRawMaterial()" class="flex-1 overflow-y-auto p-6 space-y-4">

            <!-- Global Error Banner -->
            <template x-if="materialFormError">
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3">
                    <i class="ti ti-alert-circle text-red-600 text-xl shrink-0 mt-0.5"></i>
                    <p class="text-sm font-semibold text-red-700" x-text="materialFormError"></p>
                </div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Code -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Kode Bahan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="materialForm.code" placeholder="Contoh: BB-SSU-01"
                        @input="delete materialErrors.code; materialFormError = ''"
                        :class="materialErrors.code ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso uppercase font-mono placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                    <template x-if="materialErrors.code">
                        <p class="text-xs text-red-600 font-semibold mt-1" x-text="materialErrors.code[0]"></p>
                    </template>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Kategori Bahan <span class="text-red-500">*</span>
                    </label>
                    <select x-model="materialForm.category"
                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                        <option value="ingredient">Bahan Masak Dapur</option>
                        <option value="packaging">Bahan Kemasan & Label</option>
                        <option value="other">Lain-lain</option>
                    </select>
                </div>
            </div>

            <!-- Name -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Nama Bahan Baku <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="materialForm.name" placeholder="Contoh: Susu Bubuk Full Cream"
                    @input="delete materialErrors.name; materialFormError = ''"
                    :class="materialErrors.name ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                    class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                <template x-if="materialErrors.name">
                    <p class="text-xs text-red-600 font-semibold mt-1" x-text="materialErrors.name[0]"></p>
                </template>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Unit -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Satuan Unit <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="materialForm.unit" placeholder="gram, kg, pcs, ml, liter, lembar"
                        @input="delete materialErrors.unit; materialFormError = ''"
                        :class="materialErrors.unit ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso lowercase placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                    <template x-if="materialErrors.unit">
                        <p class="text-xs text-red-600 font-semibold mt-1" x-text="materialErrors.unit[0]"></p>
                    </template>
                </div>

                <!-- Stock Qty -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Stok Saat Ini <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" x-model="materialForm.stock_qty"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Min Stock Alert -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Batas Stok Minimum <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" x-model="materialForm.min_stock_alert"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                </div>
            </div>

            <!-- Average Cost -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Harga Beli Rata-rata per Unit (HPP) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-brand-warm-gray font-bold">Rp</span>
                    <input type="number" step="0.01" min="0" x-model="materialForm.average_cost" placeholder="0"
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-mono font-bold focus:outline-none focus:border-brand-primary">
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Catatan Bahan (Opsional)
                </label>
                <textarea x-model="materialForm.notes" rows="2" placeholder="Catatan supplier, merek, atau penyimpanan..."
                    class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-brand-border">
                <button type="button" @click="showRawMaterialModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                    <template x-if="isProcessing">
                        <i class="ti ti-loader-2 animate-spin text-lg"></i>
                    </template>
                    <span>Simpan Bahan Baku</span>
                </button>
            </div>
        </form>
    </div>
</div>
