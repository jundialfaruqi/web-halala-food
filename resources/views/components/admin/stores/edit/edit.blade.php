<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div>
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Distribusi</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.stores') }}" wire:navigate class="hover:text-brand-primary transition">Toko Mitra</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Ubah Toko</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Ubah Data Toko Mitra</h1>
        <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Perbarui informasi profil toko mitra, rute pengantaran kurir, atau sesuaikan titik lokasi pada peta.</p>
    </div>

    <!-- Main Form Container -->
    <form wire:submit="update" class="space-y-6">

        <!-- Card 1: Informasi Toko Mitra -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6">
            <h2 class="text-base font-bold text-brand-espresso pb-3 border-b border-brand-border">Informasi Toko Mitra</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nama Toko -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nama Toko Mitra <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="Misal: Pusat Oleh-Oleh Barokah"
                        class="w-full px-4 py-2.5 bg-white border {{ $errors->has('name') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Pemilik / PIC -->
                <div>
                    <label for="owner_name" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Nama Pemilik / PIC (Opsional)
                    </label>
                    <input type="text" id="owner_name" wire:model="owner_name" placeholder="Misal: Ibu Hj. Aminah"
                        class="w-full px-4 py-2.5 bg-white border {{ $errors->has('owner_name') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('owner_name')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kontak Telepon / WhatsApp -->
                <div>
                    <label for="phone" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        No. Telepon / WhatsApp (Opsional)
                    </label>
                    <input type="text" id="phone" wire:model="phone" placeholder="Misal: 081234567890"
                        class="w-full px-4 py-2.5 font-mono bg-white border {{ $errors->has('phone') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                    @error('phone')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Rute Pengiriman -->
                <div class="md:col-span-2">
                    <label for="route" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Rute Wilayah Distribusi (Opsional)
                    </label>
                    <div class="relative">
                        <input type="text" id="route" wire:model="route" list="routes-list" placeholder="Misal: Rute Pasar Besar, Rute Sukun, Rute Klojen"
                            class="w-full px-4 py-2.5 bg-white border {{ $errors->has('route') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition">
                        <datalist id="routes-list">
                            @foreach($existingRoutes as $r)
                                <option value="{{ $r }}">
                            @endforeach
                        </datalist>
                    </div>
                    @if(count($existingRoutes) > 0)
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-xs text-brand-warm-gray">Pilihan cepat:</span>
                            @foreach($existingRoutes as $r)
                                <button type="button" wire:click="$set('route', '{{ addslashes($r) }}')"
                                    class="px-2.5 py-1 text-xs font-medium rounded-lg border transition cursor-pointer {{ $route === $r ? 'border-brand-espresso bg-neutral-100 text-brand-espresso font-bold' : 'border-brand-border bg-white hover:bg-neutral-50 text-brand-espresso' }}">
                                    {{ $r }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <p class="text-xs text-brand-warm-gray mt-1.5">Mengelompokkan toko mitra agar kurir dapat mengantar produk secara berurutan dan efisien.</p>
                    @error('route')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alamat Fisik Lengkap -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Alamat Lengkap Toko (Opsional)
                    </label>
                    <textarea id="address" wire:model="address" rows="2" placeholder="Nama jalan, nomor ruko/gedung, patokan lokasi..."
                        class="w-full px-4 py-2 bg-white border {{ $errors->has('address') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"></textarea>
                    @error('address')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Catatan Khusus -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-semibold text-brand-espresso mb-1.5">
                        Catatan Toko / Penagihan (Opsional)
                    </label>
                    <textarea id="notes" wire:model="notes" rows="2" placeholder="Misal: Posisi rak kaca dekat kasir, jadwal antar hari sabtu pagi..."
                        class="w-full px-4 py-2 bg-white border {{ $errors->has('notes') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary transition resize-none"></textarea>
                    @error('notes')
                        <p class="text-xs text-red-600 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Foto Toko Mitra -->
                <div class="md:col-span-2 pt-2 border-t border-brand-border/60"
                    x-data="{
                        photoPreview: {{ \Illuminate\Support\Js::from($photo_url) }},
                        hasExistingPhoto: {{ \Illuminate\Support\Js::from(!empty($photo_url)) }},
                        isMarkedForDeletion: false,
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
                                this.isMarkedForDeletion = false;
                                $wire.set('photo_data', res.dataUrl);
                            } catch (err) {
                                this.errorMessage = err.message || 'Gagal memproses file foto.';
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
                            this.isMarkedForDeletion = true;
                            $wire.set('photo_data', 'DELETE');
                        },

                        openCamera() {
                            const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                            if (isMobile && this.$refs.editCameraInput) {
                                this.$refs.editCameraInput.click();
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
                                        this.isMarkedForDeletion = false;
                                        this.isConverting = false;
                                        $wire.set('photo_data', res.dataUrl);
                                    },
                                    onError: (err) => {
                                        this.isConverting = false;
                                        if (this.$refs.editCameraInput) {
                                            this.$refs.editCameraInput.click();
                                        } else {
                                            this.errorMessage = err.message || 'Kamera tidak dapat diakses.';
                                        }
                                    },
                                    fallbackInput: this.$refs.editCameraInput
                                });
                            } else if (this.$refs.editCameraInput) {
                                this.$refs.editCameraInput.click();
                            }
                        }
                    }">
                    <label class="block text-sm font-semibold text-brand-espresso mb-1">
                        Foto Toko Mitra (Opsional)
                    </label>
                    <p class="text-xs text-brand-warm-gray mb-3">Foto etalase/plang toko mitra untuk memudahkan kurir mengenali lokasi toko saat pengantaran konsinyasi.</p>

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
                                <img :src="photoPreview" alt="Pratinjau Foto Toko" class="w-full h-full object-contain p-1">
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
                            <template x-if="photoPreview && photoFormat">
                                <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-espresso bg-white px-3 py-1 rounded-lg border border-brand-border">
                                    <i class="ti ti-check text-emerald-600"></i>
                                    <span>Format: <strong x-text="photoFormat"></strong></span>
                                    <span>&bull;</span>
                                    <span x-text="compressedSize"></span>
                                    <span class="text-brand-warm-gray font-normal" x-text="'(dari ' + originalSize + ')'"></span>
                                </div>
                            </template>

                            <template x-if="photoPreview">
                                <div>
                                    <button type="button" @click="removePhoto()"
                                        class="text-xs text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 cursor-pointer">
                                        <i class="ti ti-trash"></i>
                                        <span>Hapus Foto</span>
                                    </button>
                                </div>
                            </template>

                            <template x-if="isMarkedForDeletion">
                                <p class="text-xs text-red-600 font-medium">Foto ditandai untuk dihapus saat Anda menekan tombol simpan.</p>
                            </template>

                            <!-- Hidden Inputs -->
                            <input type="file" x-ref="editCameraInput" @change="handleFile($event)"
                                accept="image/*" capture="environment" class="hidden">
                            <input type="file" x-ref="editFileInput" @change="handleFile($event)"
                                accept="image/jpeg,image/png,image/webp,image/jpg" class="hidden">

                            <!-- Upload Buttons -->
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" @click="openCamera()" :disabled="isConverting"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-brand-border hover:bg-neutral-50 rounded-xl text-xs sm:text-sm font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                                    <i class="ti ti-camera text-base text-brand-primary"></i>
                                    <span x-text="photoPreview ? 'Ganti via Kamera' : 'Ambil dari Kamera'"></span>
                                </button>
                                <button type="button" @click="$refs.editFileInput.click()" :disabled="isConverting"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-brand-border hover:bg-neutral-50 rounded-xl text-xs sm:text-sm font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                                    <i class="ti ti-photo text-base text-brand-espresso"></i>
                                    <span x-text="photoPreview ? 'Ganti dari File' : 'Pilih dari File'"></span>
                                </button>
                            </div>

                            <p class="text-[11px] text-brand-warm-gray">
                                Format: <strong>JPG, JPEG, PNG, WEBP</strong>. Maks file <strong>10MB</strong>. Otomatis dikonversi ke WebP / JPEG (Safari/iOS) &le; 50KB di browser.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status Aktif Switch -->
                <div class="md:col-span-2 pt-2 border-t border-brand-border/60">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="checkbox checkbox-primary rounded-lg">
                        <div>
                            <span class="text-sm font-semibold text-brand-espresso">Toko Aktif</span>
                            <p class="text-xs text-brand-warm-gray">Toko aktif dapat dipilih saat membuat pengiriman barang konsinyasi dan surat jalan.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Card 2: Peta Interaktif & Koordinat GPS -->
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-6"
            wire:ignore
            x-data="{
                map: null,
                marker: null,
                searchQuery: '',
                isSearching: false,
                isLocating: false,
                searchResults: [],
                searchError: '',
                gpsError: '',

                initMap() {
                    const checkLeaflet = setInterval(() => {
                        if (typeof L !== 'undefined') {
                            clearInterval(checkLeaflet);
                            this.setupLeaflet();
                        }
                    }, 50);
                },

                setupLeaflet() {
                    if (this.map) return;

                    delete L.Icon.Default.prototype._getIconUrl;
                    L.Icon.Default.mergeOptions({
                        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    });

                    const defaultLat = $wire.latitude ? parseFloat($wire.latitude) : -7.9839;
                    const defaultLng = $wire.longitude ? parseFloat($wire.longitude) : 112.6214;
                    const initialZoom = ($wire.latitude && $wire.longitude) ? 16 : 13;

                    this.map = L.map(this.$refs.mapContainer).setView([defaultLat, defaultLng], initialZoom);

                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(this.map);

                    if ($wire.latitude && $wire.longitude) {
                        this.setMarker(defaultLat, defaultLng);
                    }

                    this.map.on('click', (e) => {
                        this.setMarker(e.latlng.lat, e.latlng.lng);
                        this.updateInputs(e.latlng.lat, e.latlng.lng);
                    });

                    this.$watch('$wire.latitude', (val) => {
                        if (val && $wire.longitude) {
                            this.setMarker(parseFloat(val), parseFloat($wire.longitude), false);
                        }
                    });
                    this.$watch('$wire.longitude', (val) => {
                        if (val && $wire.latitude) {
                            this.setMarker(parseFloat($wire.latitude), parseFloat(val), false);
                        }
                    });
                },

                setMarker(lat, lng, pan = true) {
                    if (this.marker) {
                        this.marker.setLatLng([lat, lng]);
                    } else {
                        this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                        this.marker.on('dragend', (e) => {
                            const pos = e.target.getLatLng();
                            this.updateInputs(pos.lat, pos.lng);
                        });
                    }

                    if (pan && this.map) {
                        this.map.panTo([lat, lng]);
                    }
                },

                updateInputs(lat, lng) {
                    const formattedLat = parseFloat(lat.toFixed(7));
                    const formattedLng = parseFloat(lng.toFixed(7));
                    $wire.set('latitude', formattedLat);
                    $wire.set('longitude', formattedLng);
                },

                clearLocation() {
                    if (this.marker) {
                        this.map.removeLayer(this.marker);
                        this.marker = null;
                    }
                    $wire.set('latitude', null);
                    $wire.set('longitude', null);
                },

                async searchLocation() {
                    const q = this.searchQuery.trim();
                    if (!q) return;

                    this.isSearching = true;
                    this.searchError = '';
                    this.searchResults = [];

                    try {
                        const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(q));
                        const data = await res.json();
                        if (data && data.length > 0) {
                            this.searchResults = data;
                            this.selectSearchResult(data[0]);
                        } else {
                            this.searchError = 'Alamat atau lokasi tidak ditemukan.';
                        }
                    } catch (err) {
                        this.searchError = 'Gagal melakukan pencarian alamat.';
                    } finally {
                        this.isSearching = false;
                    }
                },

                selectSearchResult(item) {
                    const lat = parseFloat(item.lat);
                    const lng = parseFloat(item.lon);
                    this.setMarker(lat, lng);
                    this.updateInputs(lat, lng);
                    this.map.setView([lat, lng], 16);
                    this.searchResults = [];
                },

                getCurrentGpsLocation() {
                    if (!navigator.geolocation) {
                        this.gpsError = 'Browser Anda tidak mendukung deteksi lokasi GPS.';
                        return;
                    }

                    this.isLocating = true;
                    this.gpsError = '';

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            this.setMarker(lat, lng);
                            this.updateInputs(lat, lng);
                            this.map.setView([lat, lng], 17);
                            this.isLocating = false;
                        },
                        (error) => {
                            this.isLocating = false;
                            if (error.code === error.PERMISSION_DENIED) {
                                this.gpsError = 'Izin lokasi GPS ditolak oleh browser/perangkat.';
                            } else {
                                this.gpsError = 'Gagal mendeteksi lokasi GPS saat ini.';
                            }
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                }
            }"
            x-init="initMap()">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-brand-border">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Titik Lokasi &amp; Koordinat GPS</h2>
                    <p class="text-xs text-brand-warm-gray mt-0.5">Klik pada peta, geser pin penanda, atau gunakan fitur cari dan GPS untuk memperbarui titik toko.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="getCurrentGpsLocation()" :disabled="isLocating"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-brand-border bg-white hover:bg-neutral-50 text-xs font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                        <span x-show="isLocating" class="loading loading-spinner loading-xs"></span>
                        <i x-show="!isLocating" class="ti ti-current-location text-sm text-brand-espresso"></i>
                        <span x-text="isLocating ? 'Mencari GPS...' : 'Lokasi GPS Saya'"></span>
                    </button>

                    <button type="button" @click="clearLocation()" x-show="$wire.latitude"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-neutral-200 text-xs text-neutral-600 hover:text-red-600 hover:bg-neutral-50 transition cursor-pointer">
                        <i class="ti ti-x text-xs"></i>
                        <span>Hapus Titik</span>
                    </button>
                </div>
            </div>

            <!-- Search Map Bar -->
            <div class="relative">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-base"></i>
                        <input type="text" x-model="searchQuery" @keydown.enter.prevent="searchLocation()"
                            placeholder="Cari alamat, jalan, atau landmark pada peta..."
                            class="w-full pl-9 pr-4 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                    </div>
                    <button type="button" @click="searchLocation()" :disabled="isSearching"
                        class="px-4 py-2 bg-brand-espresso hover:bg-black text-white text-xs font-bold rounded-xl transition cursor-pointer disabled:opacity-50 inline-flex items-center gap-1.5">
                        <span x-show="isSearching" class="loading loading-spinner loading-xs"></span>
                        <span>Cari</span>
                    </button>
                </div>

                <!-- Search Error -->
                <p x-show="searchError" x-text="searchError" class="text-xs text-red-600 font-medium mt-1"></p>
                <p x-show="gpsError" x-text="gpsError" class="text-xs text-red-600 font-medium mt-1"></p>

                <!-- Search Suggestions List -->
                <div x-show="searchResults.length > 1" class="absolute z-20 left-0 right-0 mt-1 bg-white border border-brand-border rounded-xl shadow-lg overflow-hidden divide-y divide-brand-border">
                    <template x-for="(res, idx) in searchResults" :key="idx">
                        <button type="button" @click="selectSearchResult(res)"
                            class="w-full text-left px-4 py-2.5 text-xs text-brand-espresso hover:bg-neutral-50 transition flex items-start gap-2">
                            <i class="ti ti-map-pin text-sm text-brand-warm-gray mt-0.5 shrink-0"></i>
                            <span class="line-clamp-2" x-text="res.display_name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Leaflet Map Container -->
            <div class="rounded-xl border border-brand-border overflow-hidden bg-neutral-100 relative">
                <div x-ref="mapContainer" class="w-full h-72 sm:h-80 z-0"></div>
            </div>

            <!-- Koordinat Inputs (Latitude & Longitude) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <div>
                    <label for="latitude" class="block text-xs font-semibold text-brand-espresso mb-1">
                        Latitude
                    </label>
                    <input type="number" step="any" id="latitude" wire:model="latitude" placeholder="Contoh: -7.9839000"
                        class="w-full px-3.5 py-2 font-mono text-xs bg-white border {{ $errors->has('latitude') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-brand-espresso focus:outline-none focus:border-brand-primary">
                    @error('latitude')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="longitude" class="block text-xs font-semibold text-brand-espresso mb-1">
                        Longitude
                    </label>
                    <input type="number" step="any" id="longitude" wire:model="longitude" placeholder="Contoh: 112.6214000"
                        class="w-full px-3.5 py-2 font-mono text-xs bg-white border {{ $errors->has('longitude') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-brand-espresso focus:outline-none focus:border-brand-primary">
                    @error('longitude')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Form Actions Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.stores') }}" wire:navigate
                class="px-5 py-2.5 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                <span wire:loading.remove>Simpan Perubahan</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>

    </form>

    <x-admin.photo-compressor-script />
</div>
