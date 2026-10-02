<div x-data="{
    deliveries: {{ \Illuminate\Support\Js::from($deliveries) }},
    routes: {{ \Illuminate\Support\Js::from($routes) }},
    currentUserId: {{ $currentUserId ?? 0 }},
    isCourier: {{ $isCourier ? 'true' : 'false' }},

    searchQuery: '',
    selectedStatus: 'all',
    selectedRoute: 'all',
    taskFilter: 'all',

    // Modal Confirmation State
    showCancelModal: false,
    cancelTarget: null,
    isCancelling: false,

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
        if (this.isCourier) {
            this.taskFilter = 'my';
        }

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

    get filteredDeliveries() {
        return this.deliveries.filter(item => {
            const q = this.searchQuery.toLowerCase().trim();
            const matchSearch = !q ||
                (item.delivery_number && item.delivery_number.toLowerCase().includes(q)) ||
                (item.store && item.store.name && item.store.name.toLowerCase().includes(q)) ||
                (item.store && item.store.address && item.store.address.toLowerCase().includes(q)) ||
                (item.courier && item.courier.name && item.courier.name.toLowerCase().includes(q)) ||
                (item.recipient_name && item.recipient_name.toLowerCase().includes(q));

            const matchStatus = this.selectedStatus === 'all' || item.status === this.selectedStatus;

            const matchRoute = this.selectedRoute === 'all' ||
                (item.store && item.store.route === this.selectedRoute);

            const matchTask = this.taskFilter === 'all' ||
                (this.taskFilter === 'my' && item.courier && item.courier.id === this.currentUserId);

            return matchSearch && matchStatus && matchRoute && matchTask;
        });
    },

    confirmCancel(item) {
        this.cancelTarget = item;
        this.showCancelModal = true;
    },

    async cancelDelivery() {
        if (!this.cancelTarget) return;
        this.isCancelling = true;
        try {
            const res = await $wire.cancelDelivery(this.cancelTarget.id);
            if (res.success) {
                this.cancelTarget.status = 'dibatalkan';
                this.cancelTarget.status_label = 'Dibatalkan';
                this.cancelTarget.can_edit = false;
                this.showCancelModal = false;
                this.cancelTarget = null;
                this.triggerToast(res.message, 'success');
            } else {
                this.triggerToast(res.message, 'error');
            }
        } catch (e) {
            this.triggerToast('Gagal membatalkan surat jalan.', 'error');
        } finally {
            this.isCancelling = false;
        }
    },

    confirmDelete(item) {
        this.deleteTarget = item;
        this.showDeleteModal = true;
    },

    async deleteDelivery() {
        if (!this.deleteTarget) return;
        this.isDeleting = true;
        try {
            const res = await $wire.deleteDelivery(this.deleteTarget.id);
            if (res.success) {
                this.deliveries = this.deliveries.filter(d => d.id !== this.deleteTarget.id);
                this.showDeleteModal = false;
                this.deleteTarget = null;
                this.triggerToast(res.message, 'success');
            } else {
                this.triggerToast(res.message, 'error');
            }
        } catch (e) {
            this.triggerToast('Gagal menghapus surat jalan.', 'error');
        } finally {
            this.isDeleting = false;
        }
    },

    async dispatchDelivery(item) {
        try {
            const res = await $wire.markAsDispatched(item.id);
            if (res.success) {
                item.status = 'dikirim';
                item.status_label = 'Sedang Dikirim';
                this.triggerToast(res.message, 'success');
            } else {
                this.triggerToast(res.message, 'error');
            }
        } catch (e) {
            this.triggerToast('Gagal mengubah status pengantaran.', 'error');
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
                <span class="text-brand-primary">Pengantaran</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Surat Jalan &amp; Pengantaran</h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Kelola surat jalan pengiriman produk, rute kurir, serta konfirmasi serah terima di toko mitra.</p>
        </div>

        @can('pengantaran-create')
            <div class="shrink-0">
                <a href="{{ route('admin.deliveries.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <i class="ti ti-plus text-lg"></i>
                    <span>Buat Surat Jalan</span>
                </a>
            </div>
        @endcan
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="searchQuery"
                    placeholder="Cari nomor SJ, toko, alamat, kurir, penerima..."
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
                    <option value="diproses">Menunggu Pengambilan</option>
                    <option value="dikirim">Sedang Dikirim</option>
                    <option value="selesai">Selesai Serah Terima</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>

            <!-- Penugasan / Courier Filter -->
            <div class="w-full sm:w-auto">
                <select x-model="taskFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="all">Semua Pengantaran</option>
                    <option value="my">Tugas Saya Saja</option>
                </select>
            </div>
        </div>

        <!-- Total Filtered Indicator (Identik dengan Toko Mitra & Satuan) -->
        <div class="text-xs sm:text-sm text-brand-warm-gray font-medium self-center shrink-0">
            Menampilkan <span class="font-bold text-brand-espresso" x-text="filteredDeliveries.length"></span> surat jalan
        </div>
    </div>

    <!-- Table Listing (Identik dengan Toko Mitra & Satuan) -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">Surat Jalan</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Toko Mitra &amp; Rute</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Kurir Bertugas</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Muatan Barang</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-6 whitespace-nowrap text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border/60 text-sm">
                    <template x-for="item in filteredDeliveries" :key="item.id">
                        <tr class="hover:bg-neutral-50/70 transition">
                            <!-- 1. Nomor Surat Jalan & Tanggal -->
                            <td class="px-6 py-4">
                                <a :href="'/admin/deliveries/' + item.id" wire:navigate
                                    class="font-mono font-bold text-brand-espresso hover:text-brand-primary transition block">
                                    <span x-text="item.delivery_number"></span>
                                </a>
                                <span class="text-xs text-brand-warm-gray block mt-0.5" x-text="item.delivery_date_formatted"></span>
                            </td>

                            <!-- 2. Toko Mitra & Rute -->
                            <td class="px-6 py-4">
                                <template x-if="item.store">
                                    <div>
                                        <div class="font-bold text-brand-espresso" x-text="item.store.name"></div>
                                        <div class="text-xs text-brand-warm-gray mt-0.5 flex items-center gap-1.5 flex-wrap">
                                            <span x-show="item.store.route" class="font-medium text-brand-espresso" x-text="item.store.route"></span>
                                            <span x-show="item.store.route && item.store.owner_name">&bull;</span>
                                            <span x-show="item.store.owner_name" x-text="item.store.owner_name"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!item.store">
                                    <span class="text-xs text-brand-warm-gray italic">Toko tidak ditemukan</span>
                                </template>
                            </td>

                            <!-- 3. Kurir Bertugas -->
                            <td class="px-6 py-4">
                                <template x-if="item.courier">
                                    <div>
                                        <div class="font-medium text-brand-espresso" x-text="item.courier.name"></div>
                                        <div class="text-xs text-brand-warm-gray mt-0.5 font-mono" x-show="item.courier.phone" x-text="item.courier.phone"></div>
                                    </div>
                                </template>
                                <template x-if="!item.courier">
                                    <span class="text-xs text-brand-warm-gray italic">Belum ditugaskan</span>
                                </template>
                            </td>

                            <!-- 4. Muatan Barang -->
                            <td class="px-6 py-4">
                                <div class="font-bold text-brand-espresso">
                                    <span x-text="item.total_items"></span> <span class="text-xs font-normal text-brand-warm-gray">kemasan</span>
                                </div>
                                <div class="text-xs text-brand-warm-gray mt-0.5 truncate max-w-xs" :title="item.items_summary" x-text="item.items_summary"></div>
                            </td>

                            <!-- 5. Status (Clean text, NO BADGE) -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block size-2 rounded-full"
                                        :class="{
                                            'bg-amber-500': item.status === 'diproses',
                                            'bg-blue-600': item.status === 'dikirim',
                                            'bg-emerald-600': item.status === 'selesai',
                                            'bg-stone-400': item.status === 'dibatalkan'
                                        }"></span>
                                    <span class="text-xs font-semibold uppercase tracking-wider"
                                        :class="{
                                            'text-amber-800': item.status === 'diproses',
                                            'text-blue-800': item.status === 'dikirim',
                                            'text-emerald-800': item.status === 'selesai',
                                            'text-stone-500 line-through': item.status === 'dibatalkan'
                                        }"
                                        x-text="item.status_label"></span>
                                </div>
                                <div x-show="item.status === 'selesai' && item.recipient_name" class="text-xs text-brand-warm-gray mt-1">
                                    Diterima: <span class="font-medium text-brand-espresso" x-text="item.recipient_name"></span>
                                </div>
                            </td>

                            <!-- 6. Aksi -->
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Quick Dispatch Button for Courier / Manager -->
                                    <template x-if="item.status === 'diproses' && (item.courier && item.courier.id === currentUserId || !isCourier)">
                                        <button type="button" @click="dispatchDelivery(item)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-lg text-xs font-bold transition cursor-pointer"
                                            title="Mulai Kirim Barang">
                                            <i class="ti ti-truck-delivery text-sm"></i>
                                            <span>Kirim</span>
                                        </button>
                                    </template>

                                    <!-- Link to Detail -->
                                    <a :href="'/admin/deliveries/' + item.id" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 border border-brand-border rounded-lg text-xs font-semibold text-brand-espresso hover:bg-neutral-100 transition">
                                        <span>Detail</span>
                                        <i class="ti ti-arrow-right text-xs"></i>
                                    </a>

                                    <!-- Edit Link -->
                                    @can('pengantaran-edit')
                                        <template x-if="item.can_edit">
                                            <a :href="'/admin/deliveries/' + item.id + '/edit'" wire:navigate
                                                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition"
                                                title="Edit Surat Jalan">
                                                <i class="ti ti-edit text-base"></i>
                                            </a>
                                        </template>
                                    @endcan

                                    <!-- Cancel Action -->
                                    @can('pengantaran-edit')
                                        <template x-if="item.status === 'diproses' || item.status === 'dikirim'">
                                            <button type="button" @click="confirmCancel(item)"
                                                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-amber-700 hover:bg-amber-50 transition cursor-pointer"
                                                title="Batalkan Surat Jalan">
                                                <i class="ti ti-ban text-base"></i>
                                            </button>
                                        </template>
                                    @endcan

                                    <!-- Delete Action -->
                                    @can('pengantaran-delete')
                                        <template x-if="item.status !== 'selesai'">
                                            <button type="button" @click="confirmDelete(item)"
                                                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-red-700 hover:bg-red-50 transition cursor-pointer"
                                                title="Hapus Surat Jalan">
                                                <i class="ti ti-trash text-base"></i>
                                            </button>
                                        </template>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="filteredDeliveries.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-brand-warm-gray">
                            <p class="text-base font-semibold text-brand-espresso">Tidak ada surat jalan yang cocok</p>
                            <p class="text-xs text-brand-warm-gray mt-1">Coba sesuaikan kata kunci pencarian atau filter status yang dipilih.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    <!-- Modal Cancel Confirmation -->
    <div x-cloak x-show="showCancelModal"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity" @click="showCancelModal = false"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 border border-brand-border shadow-xl">
                <h3 class="text-lg font-bold text-brand-espresso">Batalkan Surat Jalan?</h3>
                <p class="text-sm text-brand-warm-gray mt-2">
                    Apakah Anda yakin ingin membatalkan surat jalan <span class="font-bold text-brand-espresso font-mono" x-text="cancelTarget ? cancelTarget.delivery_number : ''"></span>?
                    Stok barang jadi yang dibawa akan otomatis dikembalikan ke gudang.
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="showCancelModal = false"
                        class="px-4 py-2 text-sm font-semibold text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 rounded-xl transition cursor-pointer">
                        Kembali
                    </button>
                    <button type="button" @click="cancelDelivery" :disabled="isCancelling"
                        class="px-4 py-2 text-sm font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-xl transition cursor-pointer disabled:opacity-50">
                        <span x-show="!isCancelling">Ya, Batalkan Surat Jalan</span>
                        <span x-show="isCancelling">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div x-cloak x-show="showDeleteModal"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity" @click="showDeleteModal = false"></div>
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl max-w-md w-full p-6 border border-brand-border shadow-xl">
                <h3 class="text-lg font-bold text-brand-espresso">Hapus Surat Jalan?</h3>
                <p class="text-sm text-brand-warm-gray mt-2">
                    Surat jalan <span class="font-bold text-brand-espresso font-mono" x-text="deleteTarget ? deleteTarget.delivery_number : ''"></span> akan dihapus permanen.
                    Jika belum dibatalkan, stok produk akan dikembalikan ke gudang.
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-semibold text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="deleteDelivery" :disabled="isDeleting"
                        class="px-4 py-2 text-sm font-bold bg-red-700 hover:bg-red-800 text-white rounded-xl transition cursor-pointer disabled:opacity-50">
                        <span x-show="!isDeleting">Hapus Permanen</span>
                        <span x-show="isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
