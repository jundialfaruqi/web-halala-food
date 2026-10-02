<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header & Breadcrumb -->
    <div class="space-y-1">
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span>Distribusi</span>
            <i class="ti ti-chevron-right text-xs"></i>
            <a href="{{ route('admin.stores') }}" class="hover:text-brand-primary transition">Toko Mitra</a>
            <i class="ti ti-chevron-right text-xs"></i>
            <span class="text-brand-primary">Ubah Data Toko</span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
            Ubah Data Toko Mitra
        </h1>
        <p class="text-sm sm:text-base text-brand-warm-gray">
            Perbarui informasi profil toko mitra, rute pengantaran kurir, atau sesuaikan titik lokasi pada peta.
        </p>
    </div>

    <!-- Form Card -->
    <div class="bg-white border border-brand-border rounded-2xl shadow-xs p-6 sm:p-8">
        <form wire:submit="update" class="space-y-6">

            <!-- Grid 1: Informasi Toko -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Nama Toko -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Nama Toko Mitra <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="Misal: Pusat Oleh-Oleh Barokah"
                        class="w-full px-4 py-2.5 bg-white border {{ $errors->has('name') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition">
                    @error('name')
                        <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                            <i class="ti ti-alert-circle text-sm"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Nama Pemilik / Penanggung Jawab -->
                <div>
                    <label for="owner_name" class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Nama Pemilik / PIC <span class="text-xs font-normal text-brand-warm-gray">(Opsional)</span>
                    </label>
                    <input type="text" id="owner_name" wire:model="owner_name" placeholder="Misal: Ibu Hj. Aminah"
                        class="w-full px-4 py-2.5 bg-white border {{ $errors->has('owner_name') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition">
                    @error('owner_name')
                        <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                            <i class="ti ti-alert-circle text-sm"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Kontak Telepon / WhatsApp -->
                <div>
                    <label for="phone" class="block text-sm font-bold text-brand-espresso mb-1.5">
                        No. Telepon / WhatsApp <span class="text-xs font-normal text-brand-warm-gray">(Opsional)</span>
                    </label>
                    <input type="text" id="phone" wire:model="phone" placeholder="Misal: 081234567890"
                        class="w-full px-4 py-2.5 font-mono bg-white border {{ $errors->has('phone') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition">
                    @error('phone')
                        <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                            <i class="ti ti-alert-circle text-sm"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Rute Pengiriman -->
                <div class="md:col-span-2">
                    <label for="route" class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Rute Wilayah Distribusi <span class="text-xs font-normal text-brand-warm-gray">(Opsional)</span>
                    </label>
                    <div class="relative">
                        <input type="text" id="route" wire:model="route" list="routes-list" placeholder="Misal: Rute Pasar Besar, Rute Sukun, Rute Klojen"
                            class="w-full px-4 py-2.5 bg-white border {{ $errors->has('route') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition">
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
                        <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                            <i class="ti ti-alert-circle text-sm"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Alamat Fisik Lengkap -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Alamat Lengkap Toko <span class="text-xs font-normal text-brand-warm-gray">(Opsional)</span>
                    </label>
                    <textarea id="address" wire:model="address" rows="2" placeholder="Nama jalan, nomor ruko/gedung, patokan lokasi..."
                        class="w-full px-4 py-2.5 bg-white border {{ $errors->has('address') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition"></textarea>
                    @error('address')
                        <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                            <i class="ti ti-alert-circle text-sm"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
            </div>

            <!-- Bagian Peta Interaktif & Koordinat GPS -->
            <div class="pt-4 border-t border-brand-border/60 space-y-3"
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

                        // Watch manual changes to livewire inputs
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

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-bold text-brand-espresso flex items-center gap-1.5">
                            <i class="ti ti-map-pin text-brand-primary text-base"></i>
                            <span>Titik Lokasi &amp; Koordinat GPS</span>
                        </h2>
                        <p class="text-xs text-brand-warm-gray">Klik pada peta, geser pin penanda, atau gunakan fitur cari dan GPS untuk memperbarui titik toko.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="getCurrentGpsLocation()" :disabled="isLocating"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-brand-border bg-white hover:bg-neutral-50 text-xs font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                            <span x-show="isLocating" class="loading loading-spinner loading-xs"></span>
                            <i x-show="!isLocating" class="ti ti-current-location text-sm text-brand-espresso"></i>
                            <span x-text="isLocating ? 'Mencari GPS...' : 'Lokasi GPS Saya'"></span>
                        </button>

                        <button type="button" @click="clearLocation()" x-show="$wire.latitude"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-neutral-200 text-xs text-neutral-600 hover:text-red-600 hover:bg-neutral-50 transition cursor-pointer">
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

            <!-- Catatan Khusus -->
            <div class="pt-4 border-t border-brand-border/60">
                <label for="notes" class="block text-sm font-bold text-brand-espresso mb-1.5">
                    Catatan Toko / Penagihan <span class="text-xs font-normal text-brand-warm-gray">(Opsional)</span>
                </label>
                <textarea id="notes" wire:model="notes" rows="2" placeholder="Misal: Posisi rak kaca dekat kasir, jadwal antar hari sabtu pagi..."
                    class="w-full px-4 py-2.5 bg-white border {{ $errors->has('notes') ? 'border-red-500' : 'border-brand-border' }} rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary transition"></textarea>
                @error('notes')
                    <p class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Status Aktif Switch (Clean text without badges) -->
            <div class="pt-2 border-t border-brand-border/60">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="checkbox checkbox-primary rounded-lg">
                    <div>
                        <span class="text-sm font-bold text-brand-espresso">Toko Aktif</span>
                        <p class="text-xs text-brand-warm-gray">Toko aktif dapat dipilih saat membuat pengiriman barang konsinyasi.</p>
                    </div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-brand-border/60 flex items-center justify-end gap-3">
                <a href="{{ route('admin.stores') }}"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-base font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                    Batal
                </a>
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                    <span wire:loading class="loading loading-spinner loading-xs"></span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>

        </form>
    </div>

</div>
