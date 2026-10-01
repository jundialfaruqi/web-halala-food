<!-- MODAL: KATEGORI PRODUK -->
<div x-cloak x-show="showCategoryModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showCategoryModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showCategoryModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showCategoryModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-brand-border flex flex-col my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
            <div>
                <h3 class="text-xl font-bold text-brand-espresso" x-text="categoryModalTitle"></h3>
                <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5">
                    Kelola kategori utama produk camilan Halala Food.
                </p>
            </div>
            <button type="button" @click="showCategoryModal = false"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="submitCategory()" class="p-6 space-y-4">

            <!-- Global Error Banner -->
            <template x-if="categoryFormError">
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3">
                    <i class="ti ti-alert-circle text-red-600 text-xl shrink-0 mt-0.5"></i>
                    <p class="text-sm font-semibold text-red-700" x-text="categoryFormError"></p>
                </div>
            </template>

            <!-- Name -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Nama Kategori <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="categoryForm.name" placeholder="Contoh: Marie Wijen, Ting-Ting Susu"
                    @input="delete categoryErrors.name; categoryFormError = ''"
                    :class="categoryErrors.name ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                    class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                <template x-if="categoryErrors.name">
                    <p class="text-xs text-red-600 font-semibold mt-1" x-text="categoryErrors.name[0]"></p>
                </template>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Deskripsi Kategori (Opsional)
                </label>
                <textarea x-model="categoryForm.description" rows="3" placeholder="Penjelasan kategori camilan..."
                    class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-brand-border">
                <button type="button" @click="showCategoryModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                    <template x-if="isProcessing">
                        <i class="ti ti-loader-2 animate-spin text-lg"></i>
                    </template>
                    <span>Simpan Kategori</span>
                </button>
            </div>
        </form>
    </div>
</div>
