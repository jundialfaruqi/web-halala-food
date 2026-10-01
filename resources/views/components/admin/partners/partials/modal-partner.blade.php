<!-- Modal Tambah / Edit Mitra Toko -->
<div x-cloak x-show="showPartnerModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-brand-espresso/60 backdrop-blur-xs flex items-center justify-center p-4">

    <div @click.away="if (!isProcessing) showPartnerModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-2xl border border-brand-border shadow-2xl w-full max-w-2xl overflow-hidden my-8">

        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between">
            <h2 class="text-xl font-bold text-brand-espresso" x-text="partnerModalTitle"></h2>
            <button type="button" @click="showPartnerModal = false" :disabled="isProcessing"
                class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- General Form Error Alert -->
        <div x-show="partnerFormError" class="mx-6 mt-4 p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-700 font-medium flex items-center gap-2">
            <i class="ti ti-alert-circle text-base shrink-0"></i>
            <span x-text="partnerFormError"></span>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitPartner" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 1. Kode Mitra -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Kode Mitra <span class="text-error">*</span>
                    </label>
                    <input type="text" x-model="partnerForm.code"
                        placeholder="Contoh: MTR-001"
                        :class="partnerErrors.code ? 'border-error' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl font-mono text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                    <p x-show="partnerErrors.code" class="text-xs text-error mt-1" x-text="partnerErrors.code[0]"></p>
                </div>

                <!-- 2. Tipe Mitra -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Kategori Tipe Mitra <span class="text-error">*</span>
                    </label>
                    <select x-model="partnerForm.type"
                        :class="partnerErrors.type ? 'border-error' : 'border-brand-border'"
                        class="select select-lg w-full bg-white border rounded-xl text-base text-brand-espresso font-medium focus:border-brand-primary capitalize">
                        <option value="supermarket">Supermarket B2B (Jaringan)</option>
                        <option value="grocery_store">Toko Kelontong Harian (Tradisional)</option>
                        <option value="distributor">Distributor / Agen Wilayah</option>
                        <option value="retail_reseller">Reseller Eceran</option>
                    </select>
                    <p x-show="partnerErrors.type" class="text-xs text-error mt-1" x-text="partnerErrors.type[0]"></p>
                </div>
            </div>

            <!-- 3. Nama Toko / Mitra -->
            <div class="space-y-1.5">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Nama Mitra Toko / Supermarket <span class="text-error">*</span>
                </label>
                <input type="text" x-model="partnerForm.name"
                    placeholder="Contoh: Supermarket Tip Top Rawamangun"
                    :class="partnerErrors.name ? 'border-error' : 'border-brand-border'"
                    class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                <p x-show="partnerErrors.name" class="text-xs text-error mt-1" x-text="partnerErrors.name[0]"></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 4. Penanggung Jawab (PIC) -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Nama Penanggung Jawab (PIC)
                    </label>
                    <input type="text" x-model="partnerForm.contact_person"
                        placeholder="Contoh: Bpk. Hendra Wijaya"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                </div>

                <!-- 5. No. Telepon / WhatsApp -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Nomor WhatsApp / HP Toko
                    </label>
                    <input type="text" x-model="partnerForm.phone"
                        placeholder="Contoh: 08123456789"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 6. Syarat Pembayaran (Jatuh Tempo Hari) -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Jatuh Tempo Pembayaran (Hari) <span class="text-error">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" x-model.number="partnerForm.payment_term_days" min="0" max="365"
                            :class="partnerErrors.payment_term_days ? 'border-error' : 'border-brand-border'"
                            class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                        <span class="text-xs text-brand-warm-gray shrink-0 font-medium" x-text="partnerForm.payment_term_days == 0 ? 'Tunai (COD)' : 'Hari Tempo'"></span>
                    </div>
                    <p x-show="partnerErrors.payment_term_days" class="text-xs text-error mt-1" x-text="partnerErrors.payment_term_days[0]"></p>
                </div>

                <!-- 7. Batas Plafon Piutang (Credit Limit) -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Batas Plafon Piutang (Rp) <span class="text-error">*</span>
                    </label>
                    <input type="number" x-model.number="partnerForm.credit_limit" min="0" step="100000"
                        :class="partnerErrors.credit_limit ? 'border-error' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                    <p x-show="partnerErrors.credit_limit" class="text-xs text-error mt-1" x-text="partnerErrors.credit_limit[0]"></p>
                </div>
            </div>

            <!-- 8. Saldo Piutang Berjalan (Hanya saat edit atau koreksi) -->
            <div class="space-y-1.5">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Saldo Piutang Berjalan Saat Ini (Rp)
                </label>
                <input type="number" x-model.number="partnerForm.current_receivable" min="0" step="1000"
                    :class="partnerErrors.current_receivable ? 'border-error' : 'border-brand-border'"
                    class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                <p class="text-xs text-brand-warm-gray">Saldo piutang akan otomatis terakumulasi saat pengiriman faktur dan berkurang saat pembayaran kas masuk.</p>
                <p x-show="partnerErrors.current_receivable" class="text-xs text-error mt-1" x-text="partnerErrors.current_receivable[0]"></p>
            </div>

            <!-- 9. Alamat Lengkap & Kota -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2 space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Alamat Lengkap Toko
                    </label>
                    <input type="text" x-model="partnerForm.address"
                        placeholder="Contoh: Jl. Balai Pustaka Timur No. 35"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Kota / Wilayah
                    </label>
                    <input type="text" x-model="partnerForm.city"
                        placeholder="Contoh: Jakarta Timur"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                </div>
            </div>

            <!-- 10. Catatan Khusus -->
            <div class="space-y-1.5">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Catatan Khusus / Jadwal Pengantaran
                </label>
                <textarea x-model="partnerForm.notes" rows="2"
                    placeholder="Contoh: Pengiriman hanya hari Selasa & Kamis pagi sebelum jam 10:00 WIB"
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- 11. Status Aktif -->
            <div class="pt-2">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" x-model="partnerForm.is_active" class="checkbox checkbox-sm checkbox-primary rounded">
                    <span class="text-sm sm:text-base font-bold text-brand-espresso">Mitra Toko Aktif (Dapat Menerima Pesanan & Faktur)</span>
                </label>
            </div>

            <!-- Modal Actions -->
            <div class="pt-4 border-t border-brand-border flex items-center justify-end gap-3">
                <button type="button" @click="showPartnerModal = false" :disabled="isProcessing"
                    class="px-5 py-2.5 text-base font-semibold text-brand-warm-gray hover:text-brand-espresso transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                    <span x-text="partnerForm.id ? 'Simpan Perubahan' : 'Tambah Mitra'"></span>
                </button>
            </div>

        </form>

    </div>
</div>
