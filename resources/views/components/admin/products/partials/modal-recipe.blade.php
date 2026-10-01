<!-- MODAL: FORMULA RESEP (BOM) -->
<div x-cloak x-show="showRecipeModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto" role="dialog"
    aria-modal="true">

    <!-- Backdrop -->
    <div x-show="showRecipeModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showRecipeModal = false"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"></div>

    <!-- Modal Content Box -->
    <div x-show="showRecipeModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-brand-border flex flex-col max-h-[90vh] my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
            <div>
                <h3 class="text-xl font-bold text-brand-espresso" x-text="recipeModalTitle"></h3>
                <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5">
                    Konfigurasi formula takaran bahan baku (Bill of Materials) & estimasi HPP per batch produksi.
                </p>
            </div>
            <button type="button" @click="showRecipeModal = false"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <form @submit.prevent="submitRecipe()" class="flex-1 overflow-y-auto p-6 space-y-6">

            <!-- Global Error Banner -->
            <template x-if="recipeFormError">
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 flex items-start gap-3">
                    <i class="ti ti-alert-circle text-red-600 text-xl shrink-0 mt-0.5"></i>
                    <p class="text-sm font-semibold text-red-700" x-text="recipeFormError"></p>
                </div>
            </template>

            <!-- Recipe General Info -->
            <div class="space-y-4">
                <h4 class="text-sm font-bold text-brand-espresso uppercase tracking-wider flex items-center gap-2 border-b border-brand-border pb-2">
                    <i class="ti ti-info-circle text-brand-primary"></i>
                    <span>1. Informasi Formula</span>
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Product Variant Target -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Target Varian Kemasan <span class="text-red-500">*</span>
                        </label>
                        <select x-model="recipeForm.product_variant_id"
                            @input="delete recipeErrors.productVariantId; recipeFormError = ''"
                            :class="recipeErrors.productVariantId ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                            class="select select-lg w-full bg-white border rounded-xl text-sm sm:text-base text-brand-espresso font-medium">
                            <option value="" disabled>Pilih Varian Produk</option>
                            @foreach ($variants as $var)
                                <option value="{{ $var->id }}">
                                    {{ $var->product?->name }} - {{ $var->name }}
                                </option>
                            @endforeach
                        </select>
                        <template x-if="recipeErrors.productVariantId">
                            <p class="text-xs text-red-600 font-semibold mt-1" x-text="recipeErrors.productVariantId[0]"></p>
                        </template>
                    </div>

                    <!-- Recipe Name -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Nama Formula Resep <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="recipeForm.name" placeholder="Contoh: Formula Standar Batch 50 Pouch"
                            @input="delete recipeErrors.name; recipeFormError = ''"
                            :class="recipeErrors.name ? 'border-red-500 ring-1 ring-red-500' : 'border-brand-border'"
                            class="w-full px-4 py-2.5 bg-white border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                        <template x-if="recipeErrors.name">
                            <p class="text-xs text-red-600 font-semibold mt-1" x-text="recipeErrors.name[0]"></p>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Batch Output Qty -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Output Hasil (Pack) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" min="1" x-model="recipeForm.batch_output_qty"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                    </div>

                    <!-- Labor / Gas Cost -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Biaya Tenaga & Gas / Batch
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-brand-warm-gray font-bold">Rp</span>
                            <input type="number" min="0" x-model="recipeForm.labor_cost"
                                class="w-full pl-9 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                        </div>
                    </div>

                    <!-- Overhead Cost -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Biaya Overhead Lainnya
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-brand-warm-gray font-bold">Rp</span>
                            <input type="number" min="0" x-model="recipeForm.overhead_cost"
                                class="w-full pl-9 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                        </div>
                    </div>
                </div>

                <!-- Cooking Notes -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Instruksi & Catatan Masak Dapur
                    </label>
                    <textarea x-model="recipeForm.notes" rows="2" placeholder="Langkah-langkah pengadukan, suhu pemanasan, dan standar kemasan..."
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
                </div>
            </div>

            <!-- Recipe Ingredients Section -->
            <div class="space-y-4 pt-2">
                <div class="flex items-center justify-between border-b border-brand-border pb-2">
                    <h4 class="text-sm font-bold text-brand-espresso uppercase tracking-wider flex items-center gap-2">
                        <i class="ti ti-list-details text-brand-primary"></i>
                        <span>2. Takaran Bahan Baku per 1 Batch</span>
                    </h4>
                    <button type="button" @click="addRecipeItemRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-brand-primary bg-brand-soft-cream/60 hover:bg-brand-soft-cream transition cursor-pointer">
                        <i class="ti ti-plus"></i>
                        <span>Tambah Baris Bahan</span>
                    </button>
                </div>

                <template x-if="recipeErrors.items">
                    <p class="text-xs text-red-600 font-semibold" x-text="recipeErrors.items[0]"></p>
                </template>

                <!-- Ingredients Table List -->
                <div class="space-y-3">
                    <template x-for="(item, idx) in recipeForm.items" :key="idx">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-3 rounded-xl bg-neutral-50 border border-brand-border">
                            <!-- Raw Material Select -->
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-brand-espresso mb-1">Pilih Bahan Baku *</label>
                                <select x-model="item.raw_material_id"
                                    class="select select-md w-full bg-white border border-brand-border rounded-lg text-sm text-brand-espresso font-medium">
                                    <option value="" disabled>Pilih Bahan Baku</option>
                                    @foreach ($rawMaterials as $rm)
                                        <option value="{{ $rm->id }}">
                                            {{ $rm->name }} ({{ $rm->code }} - Satuan: {{ $rm->unit }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Required Quantity -->
                            <div class="w-full sm:w-44">
                                <label class="block text-xs font-bold text-brand-espresso mb-1">Takaran Dibutuhkan *</label>
                                <input type="number" step="0.01" min="0.01" x-model="item.quantity_required" placeholder="Contoh: 2500"
                                    class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                            </div>

                            <!-- Delete Row -->
                            <div class="sm:pt-5 shrink-0 flex items-center justify-end">
                                <button type="button" @click="removeRecipeItemRow(idx)"
                                    class="size-9 rounded-lg flex items-center justify-center text-red-600 hover:bg-red-50 border border-red-200 transition cursor-pointer"
                                    title="Hapus baris bahan">
                                    <i class="ti ti-trash text-base"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-brand-border">
                <button type="button" @click="showRecipeModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-brand-espresso hover:bg-neutral-100 font-semibold text-sm transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover disabled:opacity-50 text-white font-bold text-sm rounded-xl shadow-xs transition cursor-pointer">
                    <template x-if="isProcessing">
                        <i class="ti ti-loader-2 animate-spin text-lg"></i>
                    </template>
                    <span>Simpan Resep</span>
                </button>
            </div>
        </form>
    </div>
</div>
