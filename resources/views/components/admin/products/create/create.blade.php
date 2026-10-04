<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Operasional</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.products') }}" wire:navigate class="hover:text-brand-primary transition">Produk Jadi</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Tambah Produk</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Tambah Produk Jadi Baru</h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Daftarkan varian produk camilan kemasan baru, atur harga setor konsinyasi, dan catat stok awal gudang.</p>
    </div>

    <!-- Main Form Container -->
    <form wire:submit="save" class="space-y-6">

        <!-- Card: Informasi Produk & Harga -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-brand-espresso pb-3 border-b border-brand-border">Informasi Produk &amp; Harga Konsinyasi</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Nama Produk -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nama Produk Kemasan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="Contoh: Marie Wijen Halala 150g"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Nama lengkap produk beserta varian rasa atau gramasi kemasan.</p>
                </div>

                <!-- 2. Satuan Kemasan -->
                <div>
                    <label for="unit_id" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Satuan Kemasan <span class="text-red-500">*</span>
                    </label>
                    <select id="unit_id" wire:model="unit_id"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                        <option value="">-- Pilih Satuan Kemasan --</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">
                                {{ $unit->name }} ({{ $unit->short_name }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Satuan fisik produk (misal: Pouch, Toples, Bungkus, Box).</p>
                </div>

                <!-- 3. Harga Setor Konsinyasi -->
                <div>
                    <label for="consignment_price" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Harga Setor Konsinyasi (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-brand-warm-gray">Rp</span>
                        <input type="number" id="consignment_price" wire:model="consignment_price" step="500" min="0" placeholder="12000"
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-mono font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    </div>
                    @error('consignment_price')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Harga yang ditagihkan kepada toko mitra pada surat jalan &amp; faktur penagihan.</p>
                </div>

                <!-- 4. Harga Eceran Toko (Rekomendasi) -->
                <div>
                    <label for="retail_price" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Harga Jual Eceran Toko (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-brand-warm-gray">Rp</span>
                        <input type="number" id="retail_price" wire:model="retail_price" step="500" min="0" placeholder="15000"
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-mono text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    </div>
                    @error('retail_price')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Harga jual rekomendasi kepada konsumen akhir (keuntungan toko mitra = Eceran - Setor).</p>
                </div>

                <!-- 5. Stok Awal Siap Kirim di Gudang -->
                <div>
                    <label for="stock_ready" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Stok Awal Siap Kirim (Kemasan) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="stock_ready" wire:model="stock_ready" min="0" placeholder="0"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-mono font-bold text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('stock_ready')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-brand-warm-gray mt-1">Jumlah fisik produk kemasan yang saat ini sudah tersedia di gudang pabrik.</p>
                </div>

                <!-- 6. Status Produk Aktif -->
                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="checkbox checkbox-primary rounded-lg">
                        <div>
                            <span class="text-sm font-semibold text-brand-espresso">Produk Aktif</span>
                            <p class="text-xs text-brand-warm-gray">Produk aktif dapat dipilih dalam formulir produksi, surat jalan, dan faktur tagihan.</p>
                        </div>
                    </label>
                </div>

                <!-- 7. Deskripsi & Catatan Kemasan -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Deskripsi / Keterangan Produk (Opsional)
                    </label>
                    <textarea id="description" wire:model="description" rows="3"
                        placeholder="Contoh: Kemasan pouch klip aluminium foil 150 gram, masa simpan 6 bulan, izin P-IRT dan sertifikasi Halal..."
                        class="w-full px-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"></textarea>
                    @error('description')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 8. Foto Produk Kemasan -->
                <div class="md:col-span-2 pt-2 border-t border-brand-border/60"
                    x-data="{
                        photoPreview: null,
                        originalSize: '',
                        compressedSize: '',
                        photoFormat: '',
                        progress: 0,
                        statusText: '',
                        isConverting: false,
                        errorMessage: '',

                        async handleFile(e) {
                            const file = e.target.files ? e.target.files[0] : null;
                            if (!file) return;

                            this.errorMessage = '';
                            this.photoPreview = null;
                            this.isConverting = true;
                            this.progress = 0;
                            this.statusText = 'Mempersiapkan gambar...';

                            try {
                                const res = await window.compressStorePhoto(file, (pct, status) => {
                                    this.progress = pct;
                                    this.statusText = status;
                                });

                                this.photoPreview = res.dataUrl;
                                this.originalSize = res.originalSizeFormatted;
                                this.compressedSize = res.sizeFormatted;
                                this.photoFormat = res.format;
                                $wire.set('photo_data', res.dataUrl);
                            } catch (err) {
                                this.errorMessage = err.message || 'Gagal memproses file foto.';
                                $wire.set('photo_data', null);
                            } finally {
                                this.isConverting = false;
                                e.target.value = '';
                            }
                        },

                        removePhoto() {
                            this.photoPreview = null;
                            this.originalSize = '';
                            this.compressedSize = '';
                            this.photoFormat = '';
                            this.errorMessage = '';
                            $wire.set('photo_data', null);
                        },

                        openCamera() {
                            const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                            if (isMobile && this.$refs.createCameraInput) {
                                this.$refs.createCameraInput.click();
                            } else if (window.openDeviceCamera) {
                                window.openDeviceCamera({
                                    onProgress: (pct, status) => {
                                        this.isConverting = true;
                                        this.progress = pct;
                                        this.statusText = status;
                                    },
                                    onCapture: (res) => {
                                        this.photoPreview = res.dataUrl;
                                        this.originalSize = res.originalSizeFormatted;
                                        this.compressedSize = res.sizeFormatted;
                                        this.photoFormat = res.format;
                                        this.isConverting = false;
                                        $wire.set('photo_data', res.dataUrl);
                                    },
                                    onError: (err) => {
                                        this.isConverting = false;
                                        if (this.$refs.createCameraInput) {
                                            this.$refs.createCameraInput.click();
                                        } else {
                                            this.errorMessage = err.message || 'Kamera tidak dapat diakses.';
                                        }
                                    },
                                    fallbackInput: this.$refs.createCameraInput
                                });
                            } else if (this.$refs.createCameraInput) {
                                this.$refs.createCameraInput.click();
                            }
                        }
                    }">
                    <label class="block text-sm font-semibold text-brand-espresso mb-1">
                        Foto Produk Kemasan (Opsional)
                    </label>
                    <p class="text-xs text-brand-warm-gray mb-3">Foto kemasan produk jadi untuk memudahkan pengenalan visual di katalog produk, surat jalan kurir, dan toko mitra.</p>

                    <!-- Error Alert -->
                    <template x-if="errorMessage">
                        <div class="mb-3 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2">
                            <i class="ti ti-alert-triangle text-base shrink-0 mt-0.5"></i>
                            <span x-text="errorMessage"></span>
                        </div>
                    </template>

                    <!-- Photo Upload / Preview Container -->
                    <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-xl border border-brand-border bg-neutral-50/60">
                        <!-- Preview Box -->
                        <div class="relative w-36 h-36 rounded-xl border border-brand-border bg-white flex items-center justify-center shrink-0 overflow-hidden shadow-xs">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" alt="Pratinjau Foto Produk" class="w-full h-full object-contain p-1">
                            </template>
                            <template x-if="!photoPreview">
                                <div class="text-center p-3 text-brand-warm-gray">
                                    <i class="ti ti-camera text-3xl block mb-1"></i>
                                    <span class="text-[11px] block">Belum ada foto</span>
                                </div>
                            </template>
                        </div>

                        <!-- Actions & Controls -->
                        <div class="flex-1 w-full space-y-3">
                            <!-- Progress Bar -->
                            <div x-show="isConverting" class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-semibold text-brand-espresso">
                                    <span x-text="statusText"></span>
                                    <span x-text="progress + '%'"></span>
                                </div>
                                <div class="w-full bg-neutral-200 rounded-full h-2 overflow-hidden">
                                    <div class="bg-brand-primary h-2 rounded-full transition-all duration-150" :style="'width: ' + progress + '%'"></div>
                                </div>
                            </div>

                            <!-- Success Conversion Details -->
                            <template x-if="photoPreview">
                                <div class="space-y-2">
                                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-espresso bg-white px-3 py-1 rounded-lg border border-brand-border">
                                        <i class="ti ti-check text-emerald-600"></i>
                                        <span>Format: <strong x-text="photoFormat"></strong></span>
                                        <span>&bull;</span>
                                        <span x-text="compressedSize"></span>
                                        <span class="text-brand-warm-gray font-normal" x-text="'(dari ' + originalSize + ')'"></span>
                                    </div>
                                    <div>
                                        <button type="button" @click="removePhoto()"
                                            class="text-xs text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 cursor-pointer">
                                            <i class="ti ti-trash"></i>
                                            <span>Hapus Foto</span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Hidden Inputs -->
                            <input type="file" x-ref="createCameraInput" @change="handleFile($event)"
                                accept="image/*" capture="environment" class="hidden">
                            <input type="file" x-ref="createFileInput" @change="handleFile($event)"
                                accept="image/jpeg,image/png,image/webp,image/jpg" class="hidden">

                            <!-- Upload Buttons -->
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" @click="openCamera()" :disabled="isConverting"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-brand-border hover:bg-neutral-50 rounded-xl text-xs sm:text-sm font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                                    <i class="ti ti-camera text-base text-brand-primary"></i>
                                    <span>Ambil dari Kamera</span>
                                </button>
                                <button type="button" @click="$refs.createFileInput.click()" :disabled="isConverting"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-brand-border hover:bg-neutral-50 rounded-xl text-xs sm:text-sm font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                                    <i class="ti ti-photo text-base text-brand-espresso"></i>
                                    <span>Pilih dari File</span>
                                </button>
                            </div>

                            <p class="text-[11px] text-brand-warm-gray">
                                Format: <strong>JPG, JPEG, PNG, WEBP</strong>. Maks file <strong>10MB</strong>. Otomatis dikonversi ke WebP / JPEG (Safari/iOS) &le; 50KB di browser.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.products') }}" wire:navigate
                class="px-5 py-2.5 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                <span wire:loading.remove>Simpan Produk Jadi</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>

    </form>

    <x-admin.photo-compressor-script />
</div>
