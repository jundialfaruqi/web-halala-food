<div x-data="{
    stores: @js($stores),
    routes: @js($routes),
    searchQuery: '',
    selectedRoute: 'all',
    selectedStatus: 'all',
    
    // Modal Form State
    showFormModal: false,
    isEditMode: false,
    isSaving: false,
    formError: '',
    form: {
        id: null,
        name: '',
        owner_name: '',
        phone: '',
        address: '',
        route: '',
        commission_rate: 0,
        is_active: true,
        notes: '',
    },

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

    get filteredStores() {
        return this.stores.filter(store => {
            // Search query
            const q = this.searchQuery.toLowerCase().trim();
            const matchSearch = !q || 
                (store.name && store.name.toLowerCase().includes(q)) ||
                (store.owner_name && store.owner_name.toLowerCase().includes(q)) ||
                (store.phone && store.phone.toLowerCase().includes(q)) ||
                (store.address && store.address.toLowerCase().includes(q)) ||
                (store.route && store.route.toLowerCase().includes(q));

            // Route filter
            const matchRoute = this.selectedRoute === 'all' || store.route === this.selectedRoute;

            // Status filter
            const matchStatus = this.selectedStatus === 'all' || 
                (this.selectedStatus === 'active' && store.is_active) ||
                (this.selectedStatus === 'inactive' && !store.is_active);

            return matchSearch && matchRoute && matchStatus;
        });
    },

    openCreate() {
        this.isEditMode = false;
        this.formError = '';
        this.form = {
            id: null,
            name: '',
            owner_name: '',
            phone: '',
            address: '',
            route: '',
            commission_rate: 0,
            is_active: true,
            notes: '',
        };
        this.showFormModal = true;
    },

    openEdit(store) {
        this.isEditMode = true;
        this.formError = '';
        this.form = {
            id: store.id,
            name: store.name,
            owner_name: store.owner_name || '',
            phone: store.phone || '',
            address: store.address || '',
            route: store.route || '',
            commission_rate: Number(store.commission_rate) || 0,
            is_active: Boolean(store.is_active),
            notes: store.notes || '',
        };
        this.showFormModal = true;
    },

    async saveStore() {
        if (!this.form.name.trim()) {
            this.formError = 'Nama toko mitra wajib diisi.';
            return;
        }

        this.isSaving = true;
        this.formError = '';

        try {
            let res;
            if (this.isEditMode) {
                res = await $wire.updateStore(this.form.id, this.form);
            } else {
                res = await $wire.createStore(this.form);
            }

            if (res.success) {
                this.showFormModal = false;
                this.triggerToast(res.message, 'success');
                // Refresh full data
                window.location.reload();
            } else {
                this.formError = res.message;
            }
        } catch (e) {
            this.formError = 'Terjadi kesalahan sistem saat menyimpan data.';
        } finally {
            this.isSaving = false;
        }
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
    }
}" class="space-y-8">

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

    <!-- Page Header (Identik dengan Role & Permission dan Produksi) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Distribusi</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Toko Mitra</span>
            </nav>
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-xl bg-brand-soft-cream flex items-center justify-center text-brand-primary">
                    <i class="ti ti-building-store text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso">Mitra Toko &amp; Distribusi</h1>
                    <p class="text-sm sm:text-base text-brand-warm-gray mt-0.5">Kelola direktori toko mitra, rute pengantaran kurir, alamat, dan skema bagi hasil/komisi titip jual.</p>
                </div>
            </div>
        </div>

        @can('toko-create')
            <button type="button" @click="openCreate()"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
                <i class="ti ti-plus text-lg"></i>
                <span>Tambah Toko Baru</span>
            </button>
        @endcan
    </div>

    <!-- Quick Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Toko -->
        <div class="bg-white p-5 rounded-2xl border border-brand-border shadow-2xs flex items-center gap-4">
            <div class="size-12 rounded-xl bg-brand-soft-cream/80 text-brand-primary flex items-center justify-center shrink-0">
                <i class="ti ti-building-store text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Total Toko</p>
                <p class="text-xl sm:text-2xl font-black text-brand-espresso mt-0.5">{{ $stats['total_stores'] }}</p>
                <p class="text-xs text-brand-warm-gray mt-0.5">Terdaftar di sistem</p>
            </div>
        </div>

        <!-- Card 2: Toko Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-brand-border shadow-2xs flex items-center gap-4">
            <div class="size-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="ti ti-circle-check text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Toko Aktif</p>
                <p class="text-xl sm:text-2xl font-black text-emerald-700 mt-0.5">{{ $stats['active_stores'] }}</p>
                <p class="text-xs text-brand-warm-gray mt-0.5">Siap menerima titip jual</p>
            </div>
        </div>

        <!-- Card 3: Rute Pengiriman -->
        <div class="bg-white p-5 rounded-2xl border border-brand-border shadow-2xs flex items-center gap-4">
            <div class="size-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i class="ti ti-map-pin text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Rute Wilayah</p>
                <p class="text-xl sm:text-2xl font-black text-amber-700 mt-0.5">{{ $stats['total_routes'] }}</p>
                <p class="text-xs text-brand-warm-gray mt-0.5">Jalur distribusi kurir</p>
            </div>
        </div>

        <!-- Card 4: Rata-rata Komisi -->
        <div class="bg-white p-5 rounded-2xl border border-brand-border shadow-2xs flex items-center gap-4">
            <div class="size-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i class="ti ti-percentage text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Rata-rata Komisi</p>
                <p class="text-xl sm:text-2xl font-black text-brand-espresso mt-0.5">{{ number_format($stats['avg_commission'], 1, ',', '.') }}%</p>
                <p class="text-xs text-brand-warm-gray mt-0.5">Bagi hasil mitra toko</p>
            </div>
        </div>
    </div>

    <!-- Action & Filter Bar (Identik dengan UI Tab Roles / Permissions) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="searchQuery"
                    placeholder="Cari nama toko, pemilik, telepon, alamat..."
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
    </div>

    <!-- Stores Table (Identik dengan Tabel Role & Permission) -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">Nama Toko &amp; Pemilik</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Kontak &amp; Alamat</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Rute Pengiriman</th>
                    <th class="py-3.5 px-6 text-center whitespace-nowrap">Bagi Hasil (Komisi)</th>
                    <th class="py-3.5 px-6 text-center whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-6 text-right whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                <template x-for="store in filteredStores" :key="store.id">
                    <tr class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Nama Toko & Pemilik -->
                        <td class="py-4 px-6 align-middle whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-xl bg-brand-soft-cream/80 text-brand-primary flex items-center justify-center font-bold text-sm shrink-0">
                                    <i class="ti ti-building-store text-xl"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-brand-espresso text-base block" x-text="store.name"></span>
                                    <div class="inline-flex items-center gap-1.5 text-xs text-brand-warm-gray mt-0.5">
                                        <i class="ti ti-user text-xs"></i>
                                        <span x-text="store.owner_name || 'Tanpa nama pemilik'"></span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Kontak & Alamat -->
                        <td class="py-4 px-6 align-middle whitespace-nowrap">
                            <div>
                                <template x-if="store.phone">
                                    <a :href="'https://wa.me/' + store.phone.replace(/[^0-9]/g, '')" target="_blank"
                                        class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-emerald-700 hover:text-emerald-800 transition">
                                        <i class="ti ti-brand-whatsapp text-sm"></i>
                                        <span x-text="store.phone"></span>
                                    </a>
                                </template>
                                <template x-if="!store.phone">
                                    <span class="text-xs text-brand-warm-gray font-mono">-</span>
                                </template>
                                <p class="text-xs text-brand-warm-gray mt-1 truncate max-w-xs" x-text="store.address || 'Alamat belum diatur'"></p>
                            </div>
                        </td>

                        <!-- Rute Pengiriman -->
                        <td class="py-4 px-6 align-middle whitespace-nowrap">
                            <template x-if="store.route">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <i class="ti ti-map-pin text-xs"></i>
                                    <span x-text="store.route"></span>
                                </span>
                            </template>
                            <template x-if="!store.route">
                                <span class="text-xs text-brand-warm-gray font-medium">-</span>
                            </template>
                        </td>

                        <!-- Bagi Hasil / Komisi -->
                        <td class="py-4 px-6 align-middle text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold font-mono"
                                :class="Number(store.commission_rate) > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-neutral-100 text-brand-warm-gray'">
                                <span x-text="Number(store.commission_rate).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + '%'"></span>
                            </span>
                        </td>

                        <!-- Status Badge & Quick Toggle -->
                        <td class="py-4 px-6 align-middle text-center whitespace-nowrap">
                            @can('toko-edit')
                                <button type="button" @click="toggleStatus(store)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition cursor-pointer border"
                                    :class="store.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-neutral-100 text-neutral-600 border-neutral-200 hover:bg-neutral-200'"
                                    :title="store.is_active ? 'Klik untuk nonaktifkan toko' : 'Klik untuk aktifkan toko'">
                                    <span class="size-1.5 rounded-full" :class="store.is_active ? 'bg-emerald-500' : 'bg-neutral-400'"></span>
                                    <span x-text="store.is_active ? 'Aktif' : 'Non-aktif'"></span>
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border"
                                    :class="store.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-neutral-100 text-neutral-600 border-neutral-200'">
                                    <span class="size-1.5 rounded-full" :class="store.is_active ? 'bg-emerald-500' : 'bg-neutral-400'"></span>
                                    <span x-text="store.is_active ? 'Aktif' : 'Non-aktif'"></span>
                                </span>
                            @endcan
                        </td>

                        <!-- Actions (Identik dengan Tombol Aksi di Role & Permission) -->
                        <td class="py-4 px-6 align-middle text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                @can('toko-edit')
                                    <button type="button" @click="openEdit(store)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                        <i class="ti ti-edit text-base"></i>
                                        <span>Ubah</span>
                                    </button>
                                @endcan

                                @can('toko-delete')
                                    <button type="button" @click="confirmDelete(store)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200">
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
                                <div class="size-12 rounded-2xl bg-neutral-100 text-brand-warm-gray flex items-center justify-center mx-auto mb-2">
                                    <i class="ti ti-building-store text-2xl"></i>
                                </div>
                                <p class="font-bold text-brand-espresso">Tidak ada toko mitra yang ditemukan</p>
                                <p class="text-xs text-brand-warm-gray">Sesuaikan kata kunci pencarian atau daftarkan toko mitra baru untuk tujuan distribusi konsinyasi.</p>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- MODAL 1: TAMBAH / UBAH TOKO MITRA (Identik dengan Modal Role & Permission) -->
    <div x-cloak x-show="showFormModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity" @click="showFormModal = false"></div>

        <!-- Modal Box -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]"
                @click.outside="showFormModal = false">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
                    <div class="flex items-center gap-2.5">
                        <i class="ti ti-building-store text-brand-primary text-2xl"></i>
                        <h2 class="text-lg sm:text-xl font-bold text-brand-espresso" x-text="isEditMode ? 'Ubah Data Toko Mitra' : 'Tambah Toko Mitra Baru'"></h2>
                    </div>
                    <button type="button" @click="showFormModal = false"
                        class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    
                    <!-- Error Message -->
                    <div x-cloak x-show="formError"
                        class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-start gap-2 whitespace-pre-line">
                        <i class="ti ti-alert-circle text-lg shrink-0 mt-0.5"></i>
                        <span x-text="formError"></span>
                    </div>

                    <!-- Nama Toko -->
                    <div>
                        <label class="block text-xs font-bold text-brand-espresso mb-1.5">
                            Nama Toko Mitra <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.name" placeholder="Misal: Pusat Oleh-Oleh Barokah, Toko Snack Berkah"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                    </div>

                    <!-- Pemilik & No HP (2 kolom) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-brand-espresso mb-1.5">
                                Nama Pemilik / Penanggung Jawab
                            </label>
                            <input type="text" x-model="form.owner_name" placeholder="Misal: Ibu Hj. Aminah"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-brand-espresso mb-1.5">
                                No. Telepon / WhatsApp
                            </label>
                            <input type="text" x-model="form.phone" placeholder="Misal: 081234567890"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                        </div>
                    </div>

                    <!-- Rute Wilayah & Komisi Toko (2 kolom) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-brand-espresso mb-1.5">
                                Rute Wilayah Pengiriman
                            </label>
                            <input type="text" list="routeOptions" x-model="form.route" placeholder="Misal: Rute Pasar Besar"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                            <datalist id="routeOptions">
                                <template x-for="r in routes" :key="r">
                                    <option :value="r"></option>
                                </template>
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-brand-espresso mb-1.5">
                                Komisi Bagi Hasil Toko (%)
                            </label>
                            <input type="number" step="0.1" min="0" max="100" x-model="form.commission_rate" placeholder="0"
                                class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                        </div>
                    </div>

                    <!-- Alamat Toko -->
                    <div>
                        <label class="block text-xs font-bold text-brand-espresso mb-1.5">
                            Alamat Lengkap Toko
                        </label>
                        <textarea x-model="form.address" rows="2" placeholder="Misal: Jl. Raya Pasar Besar No. 12, Kel. Sukoharjo, Kota Malang"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary"></textarea>
                    </div>

                    <!-- Catatan Khusus Toko -->
                    <div>
                        <label class="block text-xs font-bold text-brand-espresso mb-1.5">
                            Catatan Khusus Pengantaran / Display (Opsional)
                        </label>
                        <textarea x-model="form.notes" rows="2" placeholder="Misal: Buka jam 07.00 pagi, titip di rak display kaca depan kasir."
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary"></textarea>
                    </div>

                    <!-- Status Aktif Toggle -->
                    <div class="flex items-center gap-3 pt-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
                        </label>
                        <div>
                            <p class="text-xs font-bold text-brand-espresso">Status Toko Aktif</p>
                            <p class="text-xs text-brand-warm-gray">Toko aktif dapat dipilih saat membuat pengiriman konsinyasi.</p>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer (Identik dengan Modal Role & Permission) -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-brand-border bg-neutral-50/60 shrink-0">
                    <button type="button" @click="showFormModal = false" :disabled="isSaving"
                        class="px-5 py-2.5 rounded-xl border border-brand-border font-bold text-sm text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="saveStore()" :disabled="isSaving"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover disabled:opacity-50 text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <span x-show="isSaving" class="loading loading-spinner loading-xs"></span>
                        <span x-text="isSaving ? 'Menyimpan...' : (isEditMode ? 'Simpan Perubahan' : 'Tambah Toko')"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL 2: KONFIRMASI HAPUS TOKO (Identik dengan Modal Delete Role & Permission) -->
    <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity" @click="showDeleteModal = false"></div>

        <!-- Modal Box -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-brand-border overflow-hidden p-6 text-center"
                @click.outside="showDeleteModal = false">
                
                <div class="size-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 border border-red-100">
                    <i class="ti ti-trash text-2xl"></i>
                </div>

                <h3 class="text-lg font-bold text-brand-espresso">Hapus Toko Mitra?</h3>
                
                <p class="text-sm text-brand-warm-gray mt-2 leading-relaxed">
                    Apakah Anda yakin ingin menghapus toko mitra <span class="font-bold text-brand-espresso" x-text="deleteTarget ? deleteTarget.name : ''"></span>?
                    Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="flex items-center justify-center gap-3 mt-6">
                    <button type="button" @click="showDeleteModal = false" :disabled="isDeleting"
                        class="px-5 py-2.5 rounded-xl border border-brand-border font-bold text-sm text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="deleteStore()" :disabled="isDeleting"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <span x-show="isDeleting" class="loading loading-spinner loading-xs"></span>
                        <span x-text="isDeleting ? 'Menghapus...' : 'Ya, Hapus Toko'"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>
