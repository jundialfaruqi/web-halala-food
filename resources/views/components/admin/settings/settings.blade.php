<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Sistem</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Pengaturan Usaha</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Pengaturan Profil &amp; Usaha</h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Kelola identitas usaha, kontak resmi, rekening bank pembayaran, dan teks cetak nota faktur serta surat jalan.</p>
    </div>

    <!-- Form Container -->
    <form wire:submit="save" class="space-y-6">

        <!-- Card 1: Identitas Usaha & Kontak -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <div>
                <h2 class="text-base font-bold text-brand-espresso">Profil &amp; Kontak Resmi Usaha</h2>
                <p class="text-xs text-brand-warm-gray mt-0.5">Informasi ini akan muncul pada kop cetak surat jalan, faktur konsinyasi, dan kwitansi pembayaran.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2 border-t border-brand-border/60">
                <!-- Nama Usaha / Brand -->
                <div>
                    <label for="company_name" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nama Usaha / Merk Dagang <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="company_name" wire:model="company_name"
                        placeholder="Contoh: HALALA FOOD"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('company_name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Badan Usaha / PT / CV -->
                <div>
                    <label for="legal_name" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nama Badan Hukum (Opsional)
                    </label>
                    <input type="text" id="legal_name" wire:model="legal_name"
                        placeholder="Contoh: CV Halala Food Berkah"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('legal_name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slogan / Tagline -->
                <div class="md:col-span-2">
                    <label for="tagline" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Slogan / Deskripsi Singkat Usaha
                    </label>
                    <input type="text" id="tagline" wire:model="tagline"
                        placeholder="Contoh: Produksi & Distribusi Makanan Ringan Halal"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('tagline')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- No Telepon / WhatsApp -->
                <div>
                    <label for="phone" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        No. Telepon / WhatsApp Resmi
                    </label>
                    <input type="text" id="phone" wire:model="phone"
                        placeholder="Contoh: 0812-9988-7766"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-mono text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('phone')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email Resmi -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Alamat Email Usaha
                    </label>
                    <input type="email" id="email" wire:model="email"
                        placeholder="Contoh: kontak@halala-food.id"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('email')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alamat Lengkap / Workshop -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Alamat Pabrik / Workshop / Kantor
                    </label>
                    <textarea id="address" wire:model="address" rows="2"
                        placeholder="Contoh: Jl. Raya Mulyoagung No. 45, Dau, Malang, Jawa Timur"
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"></textarea>
                    @error('address')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Card 2: Rekening Bank Pembayaran Resmi -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Rekening Bank Resmi Pembayaran</h2>
                    <p class="text-xs text-brand-warm-gray mt-0.5">Daftar nomor rekening yang tercantum di faktur penagihan untuk setoran transfer dari toko mitra.</p>
                </div>

                <button type="button" wire:click="addBankAccount"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 border border-brand-border rounded-xl text-xs font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer shrink-0">
                    <i class="ti ti-plus text-sm"></i>
                    <span>Tambah Rekening</span>
                </button>
            </div>

            <!-- Repeater Daftar Rekening -->
            <div class="space-y-3 pt-2 border-t border-brand-border/60">
                @foreach ($bank_accounts as $index => $account)
                    <div class="p-4 bg-neutral-50/70 border border-brand-border rounded-xl grid grid-cols-1 sm:grid-cols-3 md:grid-cols-7 gap-3 items-center">
                        <!-- Nama Bank -->
                        <div class="sm:col-span-1 md:col-span-2">
                            <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                Nama Bank / Dompet Digital
                            </label>
                            <input type="text" wire:model="bank_accounts.{{ $index }}.bank_name"
                                placeholder="Contoh: BCA / Mandiri / BRI"
                                class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                        </div>

                        <!-- Nomor Rekening -->
                        <div class="sm:col-span-1 md:col-span-2">
                            <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                Nomor Rekening
                            </label>
                            <input type="text" wire:model="bank_accounts.{{ $index }}.account_number"
                                placeholder="Contoh: 816-1234-5678"
                                class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm font-mono font-bold text-brand-espresso focus:outline-none focus:border-brand-primary">
                        </div>

                        <!-- Atas Nama -->
                        <div class="sm:col-span-1 md:col-span-2">
                            <label class="block text-xs font-semibold text-brand-espresso mb-1">
                                Atas Nama Rekening
                            </label>
                            <input type="text" wire:model="bank_accounts.{{ $index }}.account_name"
                                placeholder="Contoh: CV Halala Food"
                                class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                        </div>

                        <!-- Tombol Hapus -->
                        <div class="flex items-center sm:justify-center md:col-span-1 pt-2 sm:pt-4">
                            @if (count($bank_accounts) > 1)
                                <button type="button" wire:click="removeBankAccount({{ $index }})"
                                    class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-red-700 hover:bg-red-50 transition cursor-pointer"
                                    title="Hapus rekening">
                                    <i class="ti ti-trash text-base"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @error('bank_accounts')
                <p class="text-xs text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- Card 3: Format Catatan Default Dokumen -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <div>
                <h2 class="text-base font-bold text-brand-espresso">Catatan Bawaan Dokumen Cetak</h2>
                <p class="text-xs text-brand-warm-gray mt-0.5">Teks keterangan atau syarat &amp; ketentuan default yang akan dicetak di bagian bawah dokumen resmi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2 border-t border-brand-border/60">
                <!-- Catatan Default Faktur Tagihan -->
                <div>
                    <label for="invoice_notes" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Catatan Default Faktur Tagihan
                    </label>
                    <textarea id="invoice_notes" wire:model="invoice_notes" rows="3"
                        placeholder="Contoh: Pembayaran dapat ditransfer ke rekening resmi terdaftar atau diserahkan tunai kepada petugas penagihan..."
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"></textarea>
                    @error('invoice_notes')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Catatan Default Surat Jalan -->
                <div>
                    <label for="delivery_notes" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Catatan Default Surat Jalan
                    </label>
                    <textarea id="delivery_notes" wire:model="delivery_notes" rows="3"
                        placeholder="Contoh: Mohon periksa fisik kemasan dan jumlah produk saat serah terima dilakukan..."
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"></textarea>
                    @error('delivery_notes')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit Button Bar -->
        @can('pengaturan-edit')
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                    <i class="ti ti-device-floppy text-lg"></i>
                    <span wire:loading.remove>Simpan Pengaturan Usaha</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </div>
        @endcan

    </form>
</div>
