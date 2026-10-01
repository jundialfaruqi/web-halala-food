<!-- Modal Buat / Edit Purchase Order (PO) -->
<div x-cloak x-show="showOrderModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-brand-espresso/60 backdrop-blur-xs flex items-center justify-center p-4">

    <div @click.away="if (!isProcessing) showOrderModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-2xl border border-brand-border shadow-2xl w-full max-w-3xl overflow-hidden my-8">

        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between">
            <h2 class="text-xl font-bold text-brand-espresso" x-text="orderModalTitle"></h2>
            <button type="button" @click="showOrderModal = false" :disabled="isProcessing"
                class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- General Form Error Alert -->
        <div x-show="orderFormError" class="mx-6 mt-4 p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-700 font-medium flex items-center gap-2">
            <i class="ti ti-alert-circle text-base shrink-0"></i>
            <span x-text="orderFormError"></span>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitOrder" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- 1. Supplier -->
                <div class="sm:col-span-3 space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Supplier Pemasok <span class="text-error">*</span>
                    </label>
                    <select x-model="orderForm.supplier_id" @change="onSupplierChange()"
                        :class="orderErrors.supplierId ? 'border-error' : 'border-brand-border'"
                        class="select select-lg w-full bg-white border rounded-xl text-base text-brand-espresso font-medium focus:border-brand-primary">
                        <option value="">-- Pilih Supplier --</option>
                        <template x-for="supplier in suppliers" :key="supplier.id">
                            <option :value="supplier.id" x-text="`${supplier.name} (${supplier.payment_term_label}) - ${supplier.city || 'Lokal'}`"></option>
                        </template>
                    </select>
                    <p x-show="orderErrors.supplierId" class="text-xs text-error mt-1" x-text="orderErrors.supplierId[0]"></p>
                </div>

                <!-- 2. Tanggal Pesan -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Tanggal Pesan <span class="text-error">*</span>
                    </label>
                    <input type="date" x-model="orderForm.order_date" @change="onSupplierChange()"
                        :class="orderErrors.orderDate ? 'border-error' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <p x-show="orderErrors.orderDate" class="text-xs text-error mt-1" x-text="orderErrors.orderDate[0]"></p>
                </div>

                <!-- 3. Tanggal Jatuh Tempo -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Jatuh Tempo Pembayaran
                    </label>
                    <input type="date" x-model="orderForm.due_date"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                </div>

                <!-- 4. Status Awal -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Status PO <span class="text-error">*</span>
                    </label>
                    <select x-model="orderForm.status"
                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:border-brand-primary">
                        <option value="draft">Draft (Rencana Pengadaan)</option>
                        <option value="ordered">Dipesan (Kirim ke Supplier)</option>
                    </select>
                </div>
            </div>

            <!-- 5. Daftar Bahan Baku yang Dipesan -->
            <div class="space-y-3 pt-2 border-t border-brand-border/60">
                <div class="flex items-center justify-between">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Daftar Item Bahan Baku <span class="text-error">*</span>
                    </label>
                    <button type="button" @click="addOrderItemRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-brand-primary bg-brand-soft-cream/60 hover:bg-brand-soft-cream transition cursor-pointer border border-brand-border">
                        <i class="ti ti-plus text-sm"></i>
                        <span>Tambah Item Bahan</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in orderForm.items" :key="index">
                        <div class="p-3.5 bg-neutral-50 rounded-xl border border-brand-border space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <!-- Pilih Bahan Baku -->
                                <div class="sm:col-span-5 space-y-1">
                                    <label class="block text-xs font-semibold text-brand-espresso">Bahan Baku</label>
                                    <select x-model="item.raw_material_id" @change="onItemMaterialChange(item)"
                                        class="select select-lg w-full bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso font-medium focus:border-brand-primary">
                                        <template x-for="material in rawMaterials" :key="material.id">
                                            <option :value="material.id" x-text="`${material.name} (${material.unit}) - Stok: ${material.stock_qty}`"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Jumlah Pesan -->
                                <div class="sm:col-span-3 space-y-1">
                                    <label class="block text-xs font-semibold text-brand-espresso">Kuantitas Pesan</label>
                                    <input type="number" step="0.01" min="0.01" x-model.number="item.qty_ordered"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-xl text-sm font-bold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Harga Satuan Beli (Rp) -->
                                <div class="sm:col-span-3 space-y-1">
                                    <label class="block text-xs font-semibold text-brand-espresso">Harga Satuan (Rp)</label>
                                    <input type="number" step="0.01" min="0" x-model.number="item.unit_price"
                                        class="w-full px-3 py-2 bg-white border border-brand-border rounded-xl text-sm font-bold text-brand-espresso focus:outline-none focus:border-brand-primary">
                                </div>

                                <!-- Hapus Row Button -->
                                <div class="sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeOrderItemRow(index)" :disabled="orderForm.items.length <= 1"
                                        class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                                        <i class="ti ti-trash text-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Baris Subtotal & Catatan Item -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 pt-2 border-t border-brand-border/40 text-xs text-brand-warm-gray">
                                <div class="flex-1">
                                    <input type="text" x-model="item.notes" placeholder="Catatan spesifikasi item (opsional)..."
                                        class="w-full px-2.5 py-1 bg-white border border-brand-border/80 rounded-lg text-xs text-brand-espresso focus:outline-none focus:border-brand-primary">
                                </div>
                                <div class="text-right shrink-0 whitespace-nowrap font-medium text-brand-espresso">
                                    Subtotal: <strong class="text-brand-primary text-sm whitespace-nowrap"
                                        x-text="'Rp ' + new Intl.NumberFormat('id-ID').format((parseFloat(item.qty_ordered) || 0) * (parseFloat(item.unit_price) || 0))"></strong>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Kalkulasi Order -->
                <div class="p-3.5 bg-brand-soft-cream/40 rounded-xl border border-brand-border flex items-center justify-between">
                    <span class="text-sm font-bold text-brand-espresso">Total Nilai Pesanan Pembelian:</span>
                    <span class="text-lg font-extrabold text-brand-primary whitespace-nowrap" x-text="calculatedOrderTotalFormatted"></span>
                </div>
            </div>

            <!-- 6. Catatan Tambahan PO -->
            <div class="space-y-1.5 pt-2 border-t border-brand-border/60">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Catatan Pesanan Pembelian (PO)
                </label>
                <textarea x-model="orderForm.notes" rows="2"
                    placeholder="Instruksi pengiriman, terms diskon khusus, jadwal penerimaan gudang..."
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Modal Actions -->
            <div class="pt-4 border-t border-brand-border flex items-center justify-end gap-3">
                <button type="button" @click="showOrderModal = false" :disabled="isProcessing"
                    class="px-5 py-2.5 bg-neutral-100 hover:bg-neutral-200 text-brand-espresso text-base font-semibold rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                    <i x-show="isProcessing" class="ti ti-loader animate-spin text-lg"></i>
                    <span x-text="isProcessing ? 'Menyimpan...' : 'Simpan Pesanan (PO)'"></span>
                </button>
            </div>

        </form>

    </div>
</div>
