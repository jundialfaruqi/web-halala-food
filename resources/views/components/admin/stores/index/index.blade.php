<div x-data="{
    stores: {{ \Illuminate\Support\Js::from($stores) }},
    routes: {{ \Illuminate\Support\Js::from($routes) }},
    searchQuery: '',
    selectedRoute: 'all',
    selectedStatus: 'all',
    isProcessing: false,

    // Modal Delete State
    showDeleteModal: false,
    deleteTarget: null,
    isDeleting: false,

    // Toast State
    toastMessage: '',
    toastType: 'success',
    showToast: false,

    triggerToast(message, type = 'success') {
        this.toastMessage = message;
        this.toastType = type;
        this.showToast = true;
        setTimeout(() => { this.showToast = false; }, 3500);
    },

    init() {
        @if ($flashToast = session('toast') ?? (session('success') ? ['message' => session('success'), 'type' => 'success'] : null))
            const toastData = {{ \Illuminate\Support\Js::from($flashToast) }};
            this.$nextTick(() => {
                if (typeof toastData === 'object' && toastData.message) {
                    this.triggerToast(toastData.message, toastData.type || 'success');
                } else {
                    this.triggerToast(toastData, 'success');
                }
            });
        @endif
    },

    get filteredStores() {
        return this.stores.filter(store => {
            const q = this.searchQuery.toLowerCase().trim();
            const matchSearch = !q || 
                (store.name && store.name.toLowerCase().includes(q)) ||
                (store.owner_name && store.owner_name.toLowerCase().includes(q)) ||
                (store.phone && store.phone.toLowerCase().includes(q)) ||
                (store.address && store.address.toLowerCase().includes(q)) ||
                (store.route && store.route.toLowerCase().includes(q));

            const matchRoute = this.selectedRoute === 'all' || store.route === this.selectedRoute;

            const matchStatus = this.selectedStatus === 'all' || 
                (this.selectedStatus === 'active' && store.is_active) ||
                (this.selectedStatus === 'inactive' && !store.is_active);

            return matchSearch && matchRoute && matchStatus;
        });
    },

    confirmDelete(store) {
        this.deleteTarget = store;
        this.showDeleteModal = true;
    },

    async deleteStore() {
        if (!this.deleteTarget) return;

        this.isDeleting = true;
        try {
            const res = await $wire.deleteStore(this.deleteTarget.id);
            if (res.success) {
                this.stores = this.stores.filter(s => s.id !== this.deleteTarget.id);
                this.showDeleteModal = false;
                this.deleteTarget = null;
                this.triggerToast(res.message, 'success');
            } else {
                this.triggerToast(res.message, 'error');
            }
        } catch (e) {
            this.triggerToast('Gagal menghapus toko mitra.', 'error');
        } finally {
            this.isDeleting = false;
        }
    },

    async toggleStatus(store) {
        try {
            const res = await $wire.toggleStatus(store.id);
            if (res.success) {
                store.is_active = !store.is_active;
                this.triggerToast(res.message, 'success');
            } else {
                this.triggerToast(res.message, 'error');
            }
        } catch (e) {
            this.triggerToast('Gagal mengubah status toko.', 'error');
        }
    },

    // Photo Modal & Compressor State
    showPhotoModal: false,
    showViewPhotoModal: false,
    viewPhotoUrl: '',
    viewPhotoTitle: '',
    photoStore: null,
    photoDataUrl: null,
    photoOriginalSize: '',
    photoCompressedSize: '',
    photoFormat: '',
    photoProgress: 0,
    photoStatusText: '',
    isConvertingPhoto: false,
    photoError: '',
    isSavingPhoto: false,
    isDeletingPhoto: false,

    openPhotoModal(store) {
        this.photoStore = store;
        this.photoDataUrl = null;
        this.photoOriginalSize = '';
        this.photoCompressedSize = '';
        this.photoFormat = '';
        this.photoProgress = 0;
        this.photoStatusText = '';
        this.isConvertingPhoto = false;
        this.photoError = '';
        this.showPhotoModal = true;
    },

    openViewPhoto(store) {
        if (!store.photo_url) return;
        this.viewPhotoUrl = store.photo_url;
        this.viewPhotoTitle = store.name;
        this.showViewPhotoModal = true;
    },

    async processPhotoFile(event) {
        const file = event.target.files ? event.target.files[0] : null;
        if (!file) return;

        this.photoError = '';
        this.photoDataUrl = null;
        this.isConvertingPhoto = true;
        this.photoProgress = 0;
        this.photoStatusText = 'Mempersiapkan...';

        try {
            const result = await window.compressStorePhoto(file, (pct, status) => {
                this.photoProgress = pct;
                this.photoStatusText = status;
            });

            this.photoDataUrl = result.dataUrl;
            this.photoOriginalSize = result.originalSizeFormatted;
            this.photoCompressedSize = result.sizeFormatted;
            this.photoFormat = result.format;
        } catch (err) {
            this.photoError = err.message || 'Terjadi kesalahan saat memproses gambar.';
        } finally {
            this.isConvertingPhoto = false;
            event.target.value = '';
        }
    },

    async saveStorePhoto() {
        if (!this.photoStore || !this.photoDataUrl) return;

        this.isSavingPhoto = true;
        try {
            const res = await $wire.updateStorePhoto(this.photoStore.id, this.photoDataUrl);
            if (res.success) {
                this.photoStore.photo_url = res.photo_url;
                const idx = this.stores.findIndex(s => s.id === this.photoStore.id);
                if (idx !== -1) {
                    this.stores[idx].photo_url = res.photo_url;
                }
                this.showPhotoModal = false;
                this.triggerToast(res.message, 'success');
            } else {
                this.photoError = res.message;
            }
        } catch (e) {
            this.photoError = 'Gagal menyimpan foto toko.';
        } finally {
            this.isSavingPhoto = false;
        }
    },

    async deleteStorePhoto() {
        if (!this.photoStore) return;

        this.isDeletingPhoto = true;
        try {
            const res = await $wire.deleteStorePhoto(this.photoStore.id);
            if (res.success) {
                this.photoStore.photo_url = null;
                const idx = this.stores.findIndex(s => s.id === this.photoStore.id);
                if (idx !== -1) {
                    this.stores[idx].photo_url = null;
                }
                this.photoDataUrl = null;
                this.triggerToast(res.message, 'success');
            } else {
                this.photoError = res.message;
            }
        } catch (e) {
            this.photoError = 'Gagal menghapus foto toko.';
        } finally {
            this.isDeletingPhoto = false;
        }
    }
}" class="space-y-6">

    <!-- Toast Notification -->
    <div x-cloak x-show="showToast"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-lg border"
        :class="toastType === 'success' ? 'bg-emerald-800 text-white border-emerald-700' : 'bg-red-800 text-white border-red-700'">
        <i class="text-xl" :class="toastType === 'success' ? 'ti ti-circle-check' : 'ti ti-alert-triangle'"></i>
        <span class="text-sm font-semibold" x-text="toastMessage"></span>
    </div>

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1">
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Distribusi</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Toko Mitra</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Mitra Toko &amp; Distribusi</h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Kelola direktori toko mitra konsinyasi, rute pengantaran kurir, serta titik koordinat GPS lokasi toko.</p>
        </div>

        @can('toko-create')
            <div class="shrink-0">
                <a href="{{ route('admin.stores.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <i class="ti ti-plus text-lg"></i>
                    <span>Tambah Toko Baru</span>
                </a>
            </div>
        @endcan
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="searchQuery"
                    placeholder="Cari toko, pemilik, telepon, alamat, rute..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Route Filter -->
            <div class="w-full sm:w-auto">
                <select x-model="selectedRoute"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="all">Semua Rute Wilayah</option>
                    <template x-for="r in routes" :key="r">
                        <option :value="r" x-text="r"></option>
                    </template>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="w-full sm:w-auto">
                <select x-model="selectedStatus"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="all">Semua Status</option>
                    <option value="active">Toko Aktif</option>
                    <option value="inactive">Toko Non-aktif</option>
                </select>
            </div>
        </div>

        <div class="text-xs sm:text-sm text-brand-warm-gray font-medium self-center">
            Menampilkan <span class="font-bold text-brand-espresso" x-text="filteredStores.length"></span> toko mitra
        </div>
    </div>

    <!-- Stores Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">Nama Toko &amp; Pemilik</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Kontak &amp; Alamat</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Titik Lokasi (GPS)</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Rute Distribusi</th>
                    <th class="py-3.5 px-6 text-center whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-6 text-right whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm">
                <template x-for="store in filteredStores" :key="store.id">
                    <tr class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Nama Toko & Pemilik dengan Thumbnail Foto -->
                        <td class="py-4 px-6 align-middle whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <!-- Thumbnail / Photo trigger -->
                                <div class="relative shrink-0">
                                    <template x-if="store.photo_url">
                                        <button type="button" @click="openViewPhoto(store)"
                                            class="block w-11 h-11 rounded-xl overflow-hidden border border-brand-border bg-neutral-100 hover:opacity-90 transition cursor-pointer"
                                            title="Klik untuk perbesar foto toko">
                                            <img :src="store.photo_url" :alt="store.name" class="w-full h-full object-cover">
                                        </button>
                                    </template>
                                    <template x-if="!store.photo_url">
                                        @can('toko-edit')
                                            <button type="button" @click="openPhotoModal(store)"
                                                class="w-11 h-11 rounded-xl border border-dashed border-brand-border bg-neutral-50 hover:bg-neutral-100 flex flex-col items-center justify-center text-brand-warm-gray hover:text-brand-espresso transition cursor-pointer"
                                                title="Upload foto toko">
                                                <i class="ti ti-camera-plus text-base"></i>
                                            </button>
                                        @else
                                            <div class="w-11 h-11 rounded-xl border border-brand-border bg-neutral-100 flex items-center justify-center text-brand-espresso font-bold text-xs select-none"
                                                x-text="store.name ? store.name.substring(0, 2).toUpperCase() : 'TK'">
                                            </div>
                                        @endcan
                                    </template>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-brand-espresso text-base block" x-text="store.name"></span>
                                        @can('toko-edit')
                                            <button type="button" @click="openPhotoModal(store)"
                                                class="text-brand-warm-gray hover:text-brand-primary p-0.5 rounded transition cursor-pointer"
                                                :title="store.photo_url ? 'Ganti Foto Toko' : 'Upload Foto Toko'">
                                                <i class="ti ti-camera text-sm"></i>
                                            </button>
                                        @endcan
                                    </div>
                                    <span class="text-xs text-brand-warm-gray mt-0.5 block" x-text="store.owner_name ? 'PIC: ' + store.owner_name : 'Tanpa PIC'"></span>
                                </div>
                            </div>
                        </td>

                        <!-- Kontak & Alamat -->
                        <td class="py-4 px-6 align-middle whitespace-nowrap">
                            <div>
                                <template x-if="store.phone">
                                    <a :href="'https://wa.me/' + store.phone.replace(/[^0-9]/g, '')" target="_blank"
                                        class="inline-flex items-center gap-1.5 font-mono text-xs font-semibold text-brand-espresso hover:text-brand-primary transition">
                                        <i class="ti ti-brand-whatsapp text-sm"></i>
                                        <span x-text="store.phone"></span>
                                    </a>
                                </template>
                                <template x-if="!store.phone">
                                    <span class="text-xs text-brand-warm-gray font-mono">-</span>
                                </template>
                                <p class="text-xs text-brand-warm-gray mt-0.5 truncate max-w-xs" x-text="store.address || 'Alamat belum diatur'"></p>
                            </div>
                        </td>

                        <!-- Titik Lokasi GPS -->
                        <td class="py-4 px-6 align-middle whitespace-nowrap">
                            <template x-if="store.latitude && store.longitude">
                                <div class="space-y-0.5">
                                    <div class="font-mono text-xs text-brand-espresso font-medium"
                                        x-text="Number(store.latitude).toFixed(5) + ', ' + Number(store.longitude).toFixed(5)">
                                    </div>
                                    <a :href="'https://www.google.com/maps?q=' + store.latitude + ',' + store.longitude" target="_blank"
                                        class="inline-flex items-center gap-1 text-xs text-brand-primary hover:underline font-medium">
                                        <i class="ti ti-map-pin text-xs"></i>
                                        <span>Buka di Peta</span>
                                    </a>
                                </div>
                            </template>
                            <template x-if="!store.latitude || !store.longitude">
                                <span class="text-xs text-brand-warm-gray">-</span>
                            </template>
                        </td>

                        <!-- Rute Distribusi (Clean text, NO badge) -->
                        <td class="py-4 px-6 align-middle whitespace-nowrap">
                            <span class="text-sm font-medium text-brand-espresso" x-text="store.route || '-'"></span>
                        </td>

                        <!-- Status (Clean text toggle, NO badge) -->
                        <td class="py-4 px-6 align-middle text-center whitespace-nowrap">
                            @can('toko-edit')
                                <button type="button" @click="toggleStatus(store)"
                                    class="text-sm font-semibold hover:underline cursor-pointer transition"
                                    :class="store.is_active ? 'text-brand-espresso' : 'text-brand-warm-gray'"
                                    :title="store.is_active ? 'Klik untuk nonaktifkan toko' : 'Klik untuk aktifkan toko'"
                                    x-text="store.is_active ? 'Aktif' : 'Non-aktif'">
                                </button>
                            @else
                                <span class="text-sm font-semibold"
                                    :class="store.is_active ? 'text-brand-espresso' : 'text-brand-warm-gray'"
                                    x-text="store.is_active ? 'Aktif' : 'Non-aktif'">
                                </span>
                            @endcan
                        </td>

                        <!-- Aksi (No icon background, clean buttons) -->
                        <td class="py-4 px-6 align-middle text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                @can('toko-edit')
                                    <button type="button" @click="openPhotoModal(store)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-neutral-50 border border-brand-border transition cursor-pointer"
                                        :title="store.photo_url ? 'Kelola Foto Toko' : 'Upload Foto Toko'">
                                        <i class="ti ti-camera text-base"></i>
                                        <span x-text="store.photo_url ? 'Foto' : 'Upload Foto'"></span>
                                    </button>

                                    <a :href="store.edit_url" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-neutral-50 border border-brand-border transition cursor-pointer">
                                        <i class="ti ti-edit text-base"></i>
                                        <span>Ubah</span>
                                    </a>
                                @endcan

                                @can('toko-delete')
                                    <button type="button" @click="confirmDelete(store)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 border border-red-200 transition cursor-pointer">
                                        <i class="ti ti-trash text-base"></i>
                                        <span>Hapus</span>
                                    </button>
                                @endcan
                            </div>
                        </td>

                    </tr>
                </template>

                <!-- Empty State -->
                <template x-if="filteredStores.length === 0">
                    <tr>
                        <td colspan="6" class="py-12 text-center text-brand-warm-gray">
                            <div class="max-w-sm mx-auto space-y-2">
                                <i class="ti ti-building-store text-3xl text-brand-warm-gray"></i>
                                <p class="font-bold text-brand-espresso text-base">Tidak ada toko mitra yang ditemukan</p>
                                <p class="text-xs text-brand-warm-gray">Sesuaikan kata kunci pencarian atau daftarkan toko mitra baru untuk tujuan distribusi konsinyasi.</p>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- MODAL KONFIRMASI HAPUS (Minimalist, no icon background) -->
    <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity" @click="showDeleteModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-brand-border p-6 space-y-4"
                @click.outside="showDeleteModal = false">
                
                <div class="flex items-center gap-3">
                    <i class="ti ti-alert-triangle text-2xl text-red-600"></i>
                    <div>
                        <h3 class="text-lg font-bold text-brand-espresso">Hapus Toko Mitra?</h3>
                        <p class="text-xs text-brand-warm-gray">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <p class="text-sm text-brand-espresso">
                    Apakah Anda yakin ingin menghapus toko mitra <span class="font-bold" x-text="deleteTarget ? deleteTarget.name : ''"></span>? Data toko dan riwayat yang terkait akan terhapus dari sistem.
                </p>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showDeleteModal = false"
                        class="px-4 py-2 rounded-xl border border-brand-border text-sm font-semibold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="deleteStore()" :disabled="isDeleting"
                        class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold shadow-xs transition cursor-pointer disabled:opacity-50 inline-flex items-center gap-2">
                        <span x-show="isDeleting" class="loading loading-spinner loading-xs"></span>
                        <span>Hapus Toko</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL UPLOAD FOTO TOKO -->
    <div x-cloak x-show="showPhotoModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity" @click="showPhotoModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl border border-brand-border p-6 space-y-5"
                @click.outside="showPhotoModal = false">

                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-3 border-b border-brand-border">
                    <div>
                        <h3 class="text-lg font-bold text-brand-espresso" x-text="photoStore ? 'Foto Toko: ' + photoStore.name : 'Upload Foto Toko'"></h3>
                        <p class="text-xs text-brand-warm-gray mt-0.5">Ambil dari kamera langsung atau unggah dari file.</p>
                    </div>
                    <button type="button" @click="showPhotoModal = false" class="text-brand-warm-gray hover:text-brand-espresso transition cursor-pointer">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <!-- Error Banner -->
                <template x-if="photoError">
                    <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2">
                        <i class="ti ti-alert-triangle text-base shrink-0 mt-0.5"></i>
                        <span x-text="photoError"></span>
                    </div>
                </template>

                <!-- Current / Converted Preview Card -->
                <div class="flex flex-col items-center justify-center p-4 bg-neutral-50 rounded-xl border border-brand-border space-y-3">
                    <template x-if="photoDataUrl">
                        <img :src="photoDataUrl" alt="Pratinjau Hasil Konversi" class="w-full max-w-xs h-52 sm:h-56 object-contain rounded-lg">
                    </template>
                    <template x-if="!photoDataUrl && photoStore && photoStore.photo_url">
                        <img :src="photoStore.photo_url" alt="Foto Saat Ini" class="w-full max-w-xs h-52 sm:h-56 object-contain rounded-lg">
                    </template>
                    <template x-if="!photoDataUrl && (!photoStore || !photoStore.photo_url)">
                        <div class="py-8 text-center text-brand-warm-gray">
                            <i class="ti ti-photo-off text-4xl block mb-1"></i>
                            <span class="text-xs">Belum ada foto</span>
                        </div>
                    </template>

                    <!-- Info badges after client conversion -->
                    <template x-if="photoDataUrl">
                        <div class="text-center space-y-1">
                            <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-espresso bg-white px-3 py-1 rounded-lg border border-brand-border">
                                <i class="ti ti-check text-emerald-600"></i>
                                <span>Format: <strong x-text="photoFormat"></strong></span>
                                <span>&bull;</span>
                                <span x-text="photoCompressedSize"></span>
                                <span class="text-brand-warm-gray font-normal" x-text="'(dari ' + photoOriginalSize + ')'"></span>
                            </div>
                            <p class="text-[11px] text-emerald-700 font-medium">Otomatis dioptimasi &le; 50KB.</p>
                        </div>
                    </template>

                    <!-- Option to remove existing photo -->
                    <template x-if="!photoDataUrl && photoStore && photoStore.photo_url">
                        @can('toko-edit')
                            <button type="button" @click="deleteStorePhoto()" :disabled="isDeletingPhoto"
                                class="inline-flex items-center gap-1.5 text-xs text-red-600 hover:text-red-700 font-semibold cursor-pointer">
                                <span x-show="isDeletingPhoto" class="loading loading-spinner loading-xs"></span>
                                <i x-show="!isDeletingPhoto" class="ti ti-trash"></i>
                                <span>Hapus Foto Saat Ini</span>
                            </button>
                        @endcan
                    </template>
                </div>

                <!-- Progress Bar during Conversion -->
                <div x-show="isConvertingPhoto" class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs font-semibold text-brand-espresso">
                        <span x-text="photoStatusText"></span>
                        <span x-text="photoProgress + '%'"></span>
                    </div>
                    <div class="w-full bg-neutral-200 rounded-full h-2 overflow-hidden">
                        <div class="bg-brand-primary h-2 rounded-full transition-all duration-150" :style="'width: ' + photoProgress + '%'"></div>
                    </div>
                </div>

                <!-- Hidden Input 1: Kamera (capture="environment") -->
                <input type="file" x-ref="cameraInput" @change="processPhotoFile($event)"
                    accept="image/jpeg,image/png,image/webp,image/jpg" capture="environment" class="hidden">

                <!-- Hidden Input 2: File Galeri -->
                <input type="file" x-ref="fileInput" @change="processPhotoFile($event)"
                    accept="image/jpeg,image/png,image/webp,image/jpg" class="hidden">

                <!-- Dua Tombol Pilihan Input: Kamera & File -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button type="button" @click="$refs.cameraInput.click()" :disabled="isConvertingPhoto"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-white border border-brand-border hover:bg-neutral-50 rounded-xl text-sm font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                        <i class="ti ti-camera text-lg text-brand-primary"></i>
                        <span>Ambil dari Kamera</span>
                    </button>
                    <button type="button" @click="$refs.fileInput.click()" :disabled="isConvertingPhoto"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-white border border-brand-border hover:bg-neutral-50 rounded-xl text-sm font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                        <i class="ti ti-photo text-lg text-brand-espresso"></i>
                        <span>Pilih dari File</span>
                    </button>
                </div>

                <!-- Info Kriteria Validasi -->
                <div class="text-[11px] text-brand-warm-gray space-y-0.5 text-center">
                    <p>Format diizinkan: <strong>JPG, JPEG, PNG, WEBP</strong>. Maksimal file <strong>10MB</strong>.</p>
                    <p>Otomatis dikonversi ke WebP &le; 50KB (atau JPEG &le; 50KB untuk Safari/iPhone) di browser Anda.</p>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-border">
                    <button type="button" @click="showPhotoModal = false"
                        class="px-4 py-2 rounded-xl border border-brand-border text-sm font-semibold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="saveStorePhoto()" :disabled="!photoDataUrl || isSavingPhoto || isConvertingPhoto"
                        class="px-5 py-2 rounded-xl bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold shadow-xs transition cursor-pointer disabled:opacity-50 inline-flex items-center gap-2">
                        <span x-show="isSavingPhoto" class="loading loading-spinner loading-xs"></span>
                        <span>Simpan Foto</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL PREVIEW FOTO BESAR -->
    <div x-cloak x-show="showViewPhotoModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-brand-espresso/80 backdrop-blur-xs transition-opacity" @click="showViewPhotoModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-brand-border overflow-hidden"
                @click.outside="showViewPhotoModal = false">

                <div class="flex items-center justify-between p-4 border-b border-brand-border">
                    <h3 class="text-base font-bold text-brand-espresso" x-text="viewPhotoTitle"></h3>
                    <button type="button" @click="showViewPhotoModal = false" class="text-brand-warm-gray hover:text-brand-espresso transition cursor-pointer">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <div class="p-4 bg-neutral-900 flex items-center justify-center max-h-[75vh]">
                    <img :src="viewPhotoUrl" :alt="viewPhotoTitle" class="max-w-full max-h-[70vh] object-contain rounded-lg">
                </div>
            </div>
        </div>
    </div>

    <x-admin.photo-compressor-script />

</div>
