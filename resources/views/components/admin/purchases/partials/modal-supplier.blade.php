<!-- Modal Tambah / Edit Supplier -->
<div x-cloak x-show="showSupplierModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-brand-espresso/60 backdrop-blur-xs flex items-center justify-center p-4">

    <div @click.away="if (!isProcessing) showSupplierModal = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-2xl border border-brand-border shadow-2xl w-full max-w-2xl overflow-hidden my-8">

        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-brand-border flex items-center justify-between">
            <h2 class="text-xl font-bold text-brand-espresso" x-text="supplierModalTitle"></h2>
            <button type="button" @click="showSupplierModal = false" :disabled="isProcessing"
                class="size-9 rounded-xl flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- General Form Error Alert -->
        <div x-show="supplierFormError" class="mx-6 mt-4 p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-700 font-medium flex items-center gap-2">
            <i class="ti ti-alert-circle text-base shrink-0"></i>
            <span x-text="supplierFormError"></span>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitSupplier" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Kode Supplier -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Kode Supplier <span class="text-error">*</span>
                    </label>
                    <input type="text" x-model="supplierForm.code" placeholder="Misal: SPL-001"
                        :class="supplierErrors.code ? 'border-error' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso font-mono font-bold uppercase focus:outline-none focus:border-brand-primary">
                    <p x-show="supplierErrors.code" class="text-xs text-error mt-1" x-text="supplierErrors.code[0]"></p>
                </div>

                <!-- Nama Supplier -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Nama Perusahaan / Toko <span class="text-error">*</span>
                    </label>
                    <input type="text" x-model="supplierForm.name" placeholder="Misal: PT Berkah Pangan Nusantara"
                        :class="supplierErrors.name ? 'border-error' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <p x-show="supplierErrors.name" class="text-xs text-error mt-1" x-text="supplierErrors.name[0]"></p>
                </div>

                <!-- Kontak Person (PIC) -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Kontak Person (PIC)
                    </label>
                    <input type="text" x-model="supplierForm.contact_person" placeholder="Misal: Bpk. Hendri (Sales Manager)"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Email Supplier -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Email Supplier
                    </label>
                    <input type="email" x-model="supplierForm.email" placeholder="sales@berkahpangan.co.id"
                        :class="supplierErrors.email ? 'border-error' : 'border-brand-border'"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <p x-show="supplierErrors.email" class="text-xs text-error mt-1" x-text="supplierErrors.email[0]"></p>
                </div>

                <!-- No. Telepon / HP -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        No. Telepon / HP
                    </label>
                    <input type="text" x-model="supplierForm.phone" placeholder="081234567890"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                </div>

                <!-- No. WhatsApp -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        No. WhatsApp (Chat Cepat)
                    </label>
                    <input type="text" x-model="supplierForm.whatsapp_number" placeholder="081234567890"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Kota -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Kota / Wilayah
                    </label>
                    <input type="text" x-model="supplierForm.city" placeholder="Misal: Surabaya / Jakarta Timur"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Syarat Pembayaran (Hari Tempo) -->
                <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                        Syarat Tempo (Hari) <span class="text-error">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" min="0" x-model.number="supplierForm.payment_terms_days"
                            :class="supplierErrors.paymentTermsDays ? 'border-error' : 'border-brand-border'"
                            class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso font-bold focus:outline-none focus:border-brand-primary pr-16">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-brand-warm-gray font-medium pointer-events-none">
                            <span x-text="supplierForm.payment_terms_days == 0 ? 'Tunai (COD)' : 'Hari Tempo'"></span>
                        </span>
                    </div>
                    <p x-show="supplierErrors.paymentTermsDays" class="text-xs text-error mt-1" x-text="supplierErrors.paymentTermsDays[0]"></p>
                </div>
            </div>

            <!-- Alamat Lengkap -->
            <div class="space-y-1.5">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Alamat Lengkap Kantor / Gudang
                </label>
                <textarea x-model="supplierForm.address" rows="2" placeholder="Nama jalan, gedung, kawasan industri..."
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Catatan Tambahan -->
            <div class="space-y-1.5">
                <label class="block text-xs sm:text-sm font-bold uppercase tracking-wider text-brand-espresso">
                    Catatan Khusus / Spesialisasi Bahan
                </label>
                <textarea x-model="supplierForm.notes" rows="2" placeholder="Pemasok bahan kemasan pouch, susu bubuk, wijen sangrai..."
                    class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Status Aktif Toggle -->
            <div class="flex items-center justify-between p-3.5 bg-neutral-50 rounded-xl border border-brand-border">
                <div>
                    <span class="text-sm font-bold text-brand-espresso block">Status Aktif Supplier</span>
                    <span class="text-xs text-brand-warm-gray block">Supplier aktif dapat dipilih pada form pembuatan PO baru.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="supplierForm.is_active" class="sr-only peer">
                    <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
                </label>
            </div>

            <!-- Modal Actions -->
            <div class="pt-4 border-t border-brand-border flex items-center justify-end gap-3">
                <button type="button" @click="showSupplierModal = false" :disabled="isProcessing"
                    class="px-5 py-2.5 bg-neutral-100 hover:bg-neutral-200 text-brand-espresso text-base font-semibold rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" :disabled="isProcessing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                    <i x-show="isProcessing" class="ti ti-loader animate-spin text-lg"></i>
                    <span x-text="isProcessing ? 'Menyimpan...' : 'Simpan Supplier'"></span>
                </button>
            </div>

        </form>

    </div>
</div>
