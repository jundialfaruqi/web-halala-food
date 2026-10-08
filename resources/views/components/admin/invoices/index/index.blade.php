<div x-data="{
    invoices: {{ \Illuminate\Support\Js::from($invoices) }},
    stores: {{ \Illuminate\Support\Js::from($stores) }},

    searchQuery: '',
    selectedStatus: (new URLSearchParams(window.location.search)).get('status') || 'all',
    selectedStore: 'all',

    getStatusCount(status) {
        if (status === 'all') return this.invoices.length;
        if (status === 'overdue') return this.invoices.filter(item => item.is_overdue).length;
        return this.invoices.filter(item => item.status === status).length;
    },

    // Modal Confirmation State
    showCancelModal: false,
    cancelTarget: null,
    isCancelling: false,

    showDeleteModal: false,
    deleteTarget: null,
    isDeleting: false,

    triggerToast(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    get filteredInvoices() {
        return this.invoices.filter(item => {
            const q = this.searchQuery.toLowerCase().trim();
            const matchSearch = !q ||
                (item.invoice_number && item.invoice_number.toLowerCase().includes(q)) ||
                (item.store && item.store.name && item.store.name.toLowerCase().includes(q)) ||
                (item.store && item.store.owner_name && item.store.owner_name.toLowerCase().includes(q)) ||
                (item.notes && item.notes.toLowerCase().includes(q));

            let matchStatus = true;
            if (this.selectedStatus === 'overdue') {
                matchStatus = item.is_overdue;
            } else if (this.selectedStatus !== 'all') {
                matchStatus = item.status === this.selectedStatus;
            }

            const matchStore = this.selectedStore === 'all' ||
                (item.store && String(item.store.id) === String(this.selectedStore));

            return matchSearch && matchStatus && matchStore;
        });
    },

    confirmCancel(item) {
        this.cancelTarget = item;
        this.showCancelModal = true;
    },

    async cancelInvoice() {
        if (!this.cancelTarget) return;
        this.isCancelling = true;
        try {
            const res = await $wire.cancelInvoice(this.cancelTarget.id);
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
            this.triggerToast('Gagal membatalkan faktur.', 'error');
        } finally {
            this.isCancelling = false;
        }
    },

    confirmDelete(item) {
        this.deleteTarget = item;
        this.showDeleteModal = true;
    },

    async deleteInvoice() {
        if (!this.deleteTarget) return;
        this.isDeleting = true;
        try {
            const res = await $wire.deleteInvoice(this.deleteTarget.id);
            if (res.success) {
                this.invoices = this.invoices.filter(inv => inv.id !== this.deleteTarget.id);
                this.showDeleteModal = false;
                this.deleteTarget = null;
                this.triggerToast(res.message, 'success');
            } else {
                this.triggerToast(res.message, 'error');
            }
        } catch (e) {
            this.triggerToast('Gagal menghapus faktur.', 'error');
        } finally {
            this.isDeleting = false;
        }
    }
}" class="space-y-6">


    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1">
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Keuangan</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Faktur &amp; Piutang</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">Faktur &amp; Piutang Toko</h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">Kelola faktur tagihan konsinyasi, jatuh tempo piutang toko mitra, dan rekap pembayaran pelunasan.</p>
        </div>

        @can('faktur-create')
            <div class="shrink-0">
                <a href="{{ route('admin.invoices.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <i class="ti ti-plus text-lg"></i>
                    <span>Buat Faktur Tagihan</span>
                </a>
            </div>
        @endcan
    </div>

    <!-- Status Pelunasan Navigation Tabs (Underline Navigation, Screen Only - Identik dengan Laporan & Pengantaran) -->
    <div class="border-b border-brand-border flex gap-6 overflow-x-auto print:hidden">
        <button type="button" @click="selectedStatus = 'all'"
            :class="selectedStatus === 'all' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso'"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 flex items-center gap-1.5">
            <span>Semua</span>
            <span class="text-xs" :class="selectedStatus === 'all' ? 'text-brand-primary font-bold' : 'text-brand-warm-gray'">(<span x-text="getStatusCount('all')"></span>)</span>
        </button>

        <button type="button" @click="selectedStatus = 'belum_dibayar'"
            :class="selectedStatus === 'belum_dibayar' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso'"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 flex items-center gap-1.5">
            <span>Belum Dibayar</span>
            <span class="text-xs" :class="selectedStatus === 'belum_dibayar' ? 'text-brand-primary font-bold' : 'text-brand-warm-gray'">(<span x-text="getStatusCount('belum_dibayar')"></span>)</span>
        </button>

        <button type="button" @click="selectedStatus = 'sebagian'"
            :class="selectedStatus === 'sebagian' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso'"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 flex items-center gap-1.5">
            <span>Dibayar Sebagian</span>
            <span class="text-xs" :class="selectedStatus === 'sebagian' ? 'text-brand-primary font-bold' : 'text-brand-warm-gray'">(<span x-text="getStatusCount('sebagian')"></span>)</span>
        </button>

        <button type="button" @click="selectedStatus = 'lunas'"
            :class="selectedStatus === 'lunas' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso'"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 flex items-center gap-1.5">
            <span>Lunas</span>
            <span class="text-xs" :class="selectedStatus === 'lunas' ? 'text-brand-primary font-bold' : 'text-brand-warm-gray'">(<span x-text="getStatusCount('lunas')"></span>)</span>
        </button>

        <button type="button" @click="selectedStatus = 'overdue'"
            :class="selectedStatus === 'overdue' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso'"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 flex items-center gap-1.5">
            <span>Jatuh Tempo</span>
            <span class="text-xs" :class="selectedStatus === 'overdue' ? 'text-brand-primary font-bold' : (getStatusCount('overdue') > 0 ? 'text-red-600 font-bold' : 'text-brand-warm-gray')">(<span x-text="getStatusCount('overdue')"></span>)</span>
        </button>

        <button type="button" @click="selectedStatus = 'dibatalkan'"
            :class="selectedStatus === 'dibatalkan' ? 'border-b-2 border-brand-primary text-brand-primary' : 'text-brand-warm-gray hover:text-brand-espresso'"
            class="pb-3 text-sm sm:text-base font-bold transition cursor-pointer shrink-0 flex items-center gap-1.5">
            <span>Dibatalkan</span>
            <span class="text-xs" :class="selectedStatus === 'dibatalkan' ? 'text-brand-primary font-bold' : 'text-brand-warm-gray'">(<span x-text="getStatusCount('dibatalkan')"></span>)</span>
        </button>
    </div>

    <!-- Filter & Search Bar (Identik dengan Toko Mitra & Pengantaran) -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="searchQuery"
                    placeholder="Cari nomor faktur, toko mitra, catatan..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Store Filter -->
            <div class="w-full sm:w-auto">
                <select x-model="selectedStore"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                    <option value="all">Semua Toko Mitra</option>
                    <template x-for="s in stores" :key="s.id">
                        <option :value="s.id" x-text="s.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Total Filtered Indicator (Identik dengan Toko Mitra, Satuan & Pengantaran) -->
        <div class="text-xs sm:text-sm text-brand-warm-gray font-medium self-center shrink-0">
            Menampilkan <span class="font-bold text-brand-espresso" x-text="filteredInvoices.length"></span> faktur tagihan
        </div>
    </div>

    <!-- Table Listing (Identik dengan Toko Mitra, Satuan & Pengantaran) -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">No. Faktur &amp; Tanggal</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Toko Mitra</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Jatuh Tempo</th>
                    <th class="py-3.5 px-6 whitespace-nowrap text-right">Total Tagihan</th>
                    <th class="py-3.5 px-6 whitespace-nowrap text-right">Sisa Piutang</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-6 whitespace-nowrap text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border/60 text-sm">
                <template x-for="item in filteredInvoices" :key="item.id">
                    <tr class="hover:bg-neutral-50/70 transition">
                        <!-- 1. Nomor Faktur & Tanggal -->
                        <td class="px-6 py-4">
                            <a :href="'/admin/invoices/' + item.id" wire:navigate
                                class="font-mono font-bold text-brand-espresso hover:text-brand-primary transition block">
                                <span x-text="item.invoice_number"></span>
                            </a>
                            <span class="text-xs text-brand-warm-gray block mt-0.5" x-text="item.invoice_date_formatted"></span>
                        </td>

                        <!-- 2. Toko Mitra -->
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

                        <!-- 3. Jatuh Tempo -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-mono text-sm"
                                :class="item.is_overdue ? 'text-red-700 font-bold' : 'text-brand-espresso'"
                                x-text="item.due_date_formatted"></div>
                            <template x-if="item.is_overdue">
                                <span class="text-xs text-red-600 block mt-0.5 font-medium">Lewat Jatuh Tempo</span>
                            </template>
                        </td>

                        <!-- 4. Total Tagihan -->
                        <td class="px-6 py-4 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                            Rp <span x-text="item.total_amount.toLocaleString('id-ID')"></span>
                        </td>

                        <!-- 5. Sisa Piutang -->
                        <td class="px-6 py-4 text-right font-mono whitespace-nowrap"
                            :class="item.remaining_balance > 0 ? 'text-amber-800 font-bold' : 'text-stone-500 font-normal'">
                            Rp <span x-text="item.remaining_balance.toLocaleString('id-ID')"></span>
                        </td>

                        <!-- 6. Status (Clean text, NO BADGE) -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="inline-block size-2 rounded-full"
                                    :class="{
                                        'bg-red-500': item.status === 'belum_dibayar',
                                        'bg-amber-500': item.status === 'sebagian',
                                        'bg-emerald-600': item.status === 'lunas',
                                        'bg-stone-400': item.status === 'dibatalkan'
                                    }"></span>
                                <span class="text-xs font-semibold uppercase tracking-wider"
                                    :class="{
                                        'text-red-800': item.status === 'belum_dibayar',
                                        'text-amber-800': item.status === 'sebagian',
                                        'text-emerald-800': item.status === 'lunas',
                                        'text-stone-500 line-through': item.status === 'dibatalkan'
                                    }"
                                    x-text="item.status_label"></span>
                            </div>
                        </td>

                        <!-- 7. Aksi -->
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Link to Detail -->
                                <a :href="'/admin/invoices/' + item.id" wire:navigate
                                    class="inline-flex items-center gap-1 px-3 py-1.5 border border-brand-border rounded-lg text-xs font-semibold text-brand-espresso hover:bg-neutral-100 transition">
                                    <span>Detail</span>
                                    <i class="ti ti-arrow-right text-xs"></i>
                                </a>

                                <!-- Edit Link -->
                                @can('faktur-edit')
                                    <template x-if="item.can_edit">
                                        <a :href="'/admin/invoices/' + item.id + '/edit'" wire:navigate
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition"
                                            title="Edit Faktur">
                                            <i class="ti ti-edit text-base"></i>
                                        </a>
                                    </template>
                                @endcan

                                <!-- Cancel Action -->
                                @can('faktur-edit')
                                    <template x-if="item.status !== 'lunas' && item.status !== 'dibatalkan'">
                                        <button type="button" @click="confirmCancel(item)"
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-amber-700 hover:bg-amber-50 transition cursor-pointer"
                                            title="Batalkan Faktur">
                                            <i class="ti ti-ban text-base"></i>
                                        </button>
                                    </template>
                                @endcan

                                <!-- Delete Action -->
                                @can('faktur-delete')
                                    <template x-if="item.status !== 'lunas'">
                                        <button type="button" @click="confirmDelete(item)"
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-red-700 hover:bg-red-50 transition cursor-pointer"
                                            title="Hapus Faktur">
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                    </template>
                                @endcan
                            </div>
                        </td>
                    </tr>
                </template>

                <!-- Empty State -->
                <tr x-show="filteredInvoices.length === 0">
                    <td colspan="7" class="py-12 text-center text-brand-warm-gray">
                        <div class="max-w-sm mx-auto space-y-2">
                            <i class="ti ti-file-off text-3xl text-brand-warm-gray"></i>
                            <p class="font-bold text-brand-espresso text-base">Tidak ada faktur tagihan yang cocok</p>
                            <p class="text-xs text-brand-warm-gray">Coba sesuaikan kata kunci pencarian atau filter status yang dipilih.</p>
                        </div>
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
                <h3 class="text-lg font-bold text-brand-espresso">Batalkan Faktur Tagihan?</h3>
                <p class="text-sm text-brand-warm-gray mt-2">
                    Apakah Anda yakin ingin membatalkan faktur <span class="font-bold text-brand-espresso font-mono" x-text="cancelTarget ? cancelTarget.invoice_number : ''"></span>?
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="showCancelModal = false"
                        class="px-4 py-2 text-sm font-semibold text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 rounded-xl transition cursor-pointer">
                        Kembali
                    </button>
                    <button type="button" @click="cancelInvoice" :disabled="isCancelling"
                        class="px-4 py-2 text-sm font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-xl transition cursor-pointer disabled:opacity-50">
                        <span x-show="!isCancelling">Ya, Batalkan Faktur</span>
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
                <h3 class="text-lg font-bold text-brand-espresso">Hapus Faktur Tagihan?</h3>
                <p class="text-sm text-brand-warm-gray mt-2">
                    Faktur <span class="font-bold text-brand-espresso font-mono" x-text="deleteTarget ? deleteTarget.invoice_number : ''"></span> beserta data rinciannya akan dihapus permanen.
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-semibold text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="deleteInvoice" :disabled="isDeleting"
                        class="px-4 py-2 text-sm font-bold bg-red-700 hover:bg-red-800 text-white rounded-xl transition cursor-pointer disabled:opacity-50">
                        <span x-show="!isDeleting">Hapus Permanen</span>
                        <span x-show="isDeleting">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
