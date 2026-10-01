<!-- Modal Buat / Edit Surat Jalan -->
<div x-cloak x-show="showDeliveryModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-brand-espresso/60 backdrop-blur-xs flex items-center justify-center p-4">

    <div @click.away="if (!isProcessing) showDeliveryModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-2xl border border-brand-border shadow-2xl w-full max-w-3xl overflow-hidden my-8">

        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between">
            <h2 class="text-xl font-bold text-brand-espresso" x-text="deliveryModalTitle"></h2>
            <button type="button" @click="showDeliveryModal = false" :disabled="isProcessing"
                class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- General Form Error Alert -->
        <div x-show="deliveryFormError" class="mx-6 mt-4 p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-700 font-medium flex items-center gap-2">
            <i class="ti ti-alert-circle text-base shrink-0"></i>
            <span x-text="deliveryFormError"></span>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitDelivery" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 1. Mitra Toko Tujuan -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Mitra Toko Tujuan <span class="text-error">*</span>
                    </label>
                    <select x-model="deliveryForm.partner_id"
                        :class="deliveryErrors.partnerId ? 'border-error' : 'border-brand-border'"
                        class="select select-lg w-full bg-white border rounded-xl text-base text-brand-espresso font-medium focus:border-brand-primary capitalize">
                        <option value="">-- Pilih Toko / Supermarket --</option>
                        <template x-for="partner in partners" :key="partner.id">
                            <option :value="partner.id" x-text="`${partner.name} (${partner.city})`"></option>
                        </template>
                    </select>
                    <p x-show="deliveryErrors.partnerId" class="text-xs text-error mt-1" x-text="deliveryErrors.partnerId[0]"></p>
                </div>

                <!-- 2. Staf Kurir -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Penugasan Staf Kurir
                    </label>
                    <select x-model="deliveryForm.courier_id"
                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:border-brand-primary capitalize">
                        <option value="">-- Belum Ditugaskan --</option>
                        <template x-for="courier in couriers" :key="courier.id">
                            <option :value="courier.id" x-text="`${courier.name} (${courier.phone || 'Staf'})`"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- 3. Daftar Muatan Produk Kemasan -->
            <div class="space-y-3 pt-2 border-t border-brand-border/60">
                <div class="flex items-center justify-between">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Daftar Muatan Produk Kemasan <span class="text-error">*</span>
                    </label>
                    <button type="button" @click="addDeliveryItemRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-brand-primary bg-brand-soft-cream/60 hover:bg-brand-soft-cream transition cursor-pointer border border-brand-border">
                        <i class="ti ti-plus text-sm"></i>
                        <span>Tambah Item Produk</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in deliveryForm.items" :key="index">
                        <div class="p-3.5 bg-neutral-50 rounded-xl border border-brand-border space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <!-- Pilih Produk Varian -->
                                <div class="sm:col-span-6 space-y-1">
                                    <label class="block text-xs font-semibold text-brand-espresso">Varian Kemasan</label>
                                    <select x-model="item.product_variant_id" @change="onItemVariantChange(item)"
                                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso font-medium focus:border-brand-primary">
                                        <template x-for="variant in productVariants" :key="variant.id">
                                            <option :value="variant.id" x-text="`${variant.full_name} (Stok: ${variant.stock_qty})`"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Jumlah Kirim -->
                                <div class="sm:col-span-3 space-y-1">
                                    <label class="block text-xs font-semibold text-brand-espresso">Qty Kirim (Pack)</label>
                                    <input type="number" x-model.number="item.qty_sent" @input="recalcItemSubtotal(item)" min="1"
                                        class="w-full px-3 py-2.5 bg-white border border-brand-border rounded-xl text-sm sm:text-base font-semibold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Harga Satuan Grosir -->
                                <div class="sm:col-span-2 space-y-1">
                                    <label class="block text-xs font-semibold text-brand-espresso">Harga Satuan</label>
                                    <input type="number" x-model.number="item.unit_price" @input="recalcItemSubtotal(item)" min="0" step="100"
                                        class="w-full px-3 py-2.5 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Tombol Hapus Row -->
                                <div class="sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeDeliveryItemRow(index)"
                                        :disabled="deliveryForm.items.length <= 1"
                                        class="size-10 rounded-xl flex items-center justify-center text-red-600 hover:bg-red-50 transition cursor-pointer disabled:opacity-30">
                                        <i class="ti ti-trash text-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Subtotal display per row -->
                            <div class="flex justify-between items-center text-xs text-brand-warm-gray pt-1 border-t border-brand-border/40">
                                <span>Subtotal Item:</span>
                                <span class="font-bold text-brand-espresso text-sm whitespace-nowrap"
                                    x-text="`Rp ${new Intl.NumberFormat('id-ID').format((item.qty_sent || 0) * (item.unit_price || 0))}`"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Estimasi Faktur -->
                <div class="flex justify-between items-center p-4 bg-brand-soft-cream/30 rounded-xl border border-brand-border">
                    <span class="font-bold text-brand-espresso text-base">Total Nilai Surat Jalan:</span>
                    <span class="font-bold text-brand-primary text-lg sm:text-xl font-mono whitespace-nowrap" x-text="calculatedDeliveryTotalFormatted"></span>
                </div>
            </div>

            <!-- 4. Catatan Pengantaran -->
            <div class="space-y-1.5">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Catatan / Instruksi Pengantaran
                </label>
                <textarea x-model="deliveryForm.notes" rows="2"
                    placeholder="Contoh: Titip ke bagian receiving pintu timur sebelum jam 11:00 WIB"
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Modal Actions -->
            <div class="pt-4 border-t border-brand-border flex items-center justify-end gap-3">
                <button type="button" @click="showDeliveryModal = false" :disabled="isProcessing"
                    class="px-5 py-2.5 text-base font-semibold text-brand-warm-gray hover:text-brand-espresso transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                    <span x-text="deliveryForm.id ? 'Simpan Perubahan' : 'Buat Surat Jalan'"></span>
                </button>
            </div>

        </form>

    </div>
</div>
