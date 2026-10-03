<div x-data="{
    search: '',
    statusFilter: 'all',
    unitFilter: 'all',
    isProcessing: false,

    // Data from backend
    products: {{ \Illuminate\Support\Js::from($products) }},
    units: {{ \Illuminate\Support\Js::from($units) }},

    // Delete Modal State
    showDeleteModal: false,
    deleteTarget: { id: null, name: '' },

    // Toast helper
    notify(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    // Handle incoming flash message after redirect
    init() {
        @if ($flashToast = session('toast') ?? (session('success') ? ['message' => session('success'), 'type' => 'success'] : null))
            const toastData = {{ \Illuminate\Support\Js::from($flashToast) }};
            this.$nextTick(() => {
                if (typeof toastData === 'object' && toastData.message) {
                    this.notify(toastData.message, toastData.type || 'success');
                } else {
                    this.notify(toastData, 'success');
                }
            });
        @endif
    },

    // Filter products computed list
    get filteredProducts() {
        return this.products.filter(product => {
            const query = this.search.toLowerCase().trim();
            const matchesSearch = !query ||
                product.name.toLowerCase().includes(query) ||
                (product.unit_name && product.unit_name.toLowerCase().includes(query)) ||
                (product.unit_short && product.unit_short.toLowerCase().includes(query)) ||
                (product.description && product.description.toLowerCase().includes(query));

            const matchesStatus = this.statusFilter === 'all' ||
                (this.statusFilter === 'active' && product.is_active) ||
                (this.statusFilter === 'inactive' && !product.is_active);

            const matchesUnit = this.unitFilter === 'all' ||
                String(product.unit_id) === String(this.unitFilter);

            return matchesSearch && matchesStatus && matchesUnit;
        });
    },

    // Toggle product active status
    async toggleStatus(id) {
        this.isProcessing = true;
        const res = await $wire.toggleStatus(id);
        this.isProcessing = false;

        if (res.success) {
            const item = this.products.find(p => p.id === id);
            if (item) item.is_active = !item.is_active;
            this.notify(res.message, 'success');
        } else {
            this.notify(res.message, 'error');
        }
    },

    // Open delete confirmation modal
    confirmDelete(product) {
        this.deleteTarget = { id: product.id, name: product.name };
        this.showDeleteModal = true;
    },

    // Execute delete
    async executeDelete() {
        if (!this.deleteTarget.id) return;
        this.isProcessing = true;
        const res = await $wire.deleteProduct(this.deleteTarget.id);
        this.isProcessing = false;

        if (res.success) {
            this.products = this.products.filter(p => p.id !== this.deleteTarget.id);
            this.showDeleteModal = false;
            this.notify(res.message, 'success');
        } else {
            this.notify(res.message, 'error');
        }
    }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <nav aria-label="Breadcrumb"
                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Operasional</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Produk Jadi</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Produk Jadi &amp; Harga
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola katalog produk kemasan jadi, harga setor konsinyasi toko, dan pantau stok siap kirim di gudang.
            </p>
        </div>

        @can('produk-create')
            <div class="shrink-0">
                <a href="{{ route('admin.products.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <i class="ti ti-plus text-lg"></i>
                    <span>Tambah Produk Baru</span>
                </a>
            </div>
        @endcan
    </div>

    <!-- Main Content Box -->
    <div class="space-y-4">

        <!-- Filter & Search Bar (Identik dengan Satuan, Toko Mitra, & Pengantaran) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                    <input type="text" x-model="search" placeholder="Cari nama produk, kemasan..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Status Filter -->
                <div class="shrink-0">
                    <select x-model="statusFilter"
                        class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                        <option value="all">Semua Status</option>
                        <option value="active">Aktif Saja</option>
                        <option value="inactive">Nonaktif Saja</option>
                    </select>
                </div>

                <!-- Satuan Kemasan Filter -->
                <div class="shrink-0">
                    <select x-model="unitFilter"
                        class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                        <option value="all">Semua Satuan</option>
                        <template x-for="u in units" :key="u.id">
                            <option :value="u.id" x-text="u.name + ' (' + u.short_name + ')'"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Total Filtered Indicator -->
            <div class="text-xs sm:text-sm text-brand-warm-gray font-medium self-center">
                Menampilkan <span class="font-bold text-brand-espresso" x-text="filteredProducts.length"></span> produk
            </div>
        </div>

        <!-- Table Listing -->
        <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">Nama Produk Kemasan</th>
                        <th class="py-3.5 px-6">Satuan</th>
                        <th class="py-3.5 px-6 text-right">Harga Setor Konsinyasi</th>
                        <th class="py-3.5 px-6 text-right">Harga Eceran Toko</th>
                        <th class="py-3.5 px-6 text-center">Stok Siap Kirim</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border/60 text-sm">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <tr class="hover:bg-neutral-50/50 transition">

                            <!-- Product Name (Bare icon / Text, NO icon bg) -->
                            <td class="py-4 px-6 font-semibold text-brand-espresso">
                                <div>
                                    <div class="font-bold text-brand-espresso text-base" x-text="product.name"></div>
                                    <div class="text-xs text-brand-warm-gray mt-0.5" x-text="product.description"></div>
                                </div>
                            </td>

                            <!-- Satuan Kemasan -->
                            <td class="py-4 px-6">
                                <span class="font-mono text-sm text-brand-espresso font-medium"
                                    x-text="product.unit_name || product.unit_short">
                                </span>
                            </td>

                            <!-- Harga Setor Konsinyasi -->
                            <td class="py-4 px-6 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                                <span x-text="product.consignment_price_formatted"></span>
                            </td>

                            <!-- Harga Eceran Toko -->
                            <td class="py-4 px-6 text-right font-mono text-brand-warm-gray whitespace-nowrap">
                                <span x-text="product.retail_price_formatted"></span>
                            </td>

                            <!-- Stok Siap Kirim Gudang -->
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <span class="font-mono font-bold text-brand-espresso" x-text="product.stock_ready.toLocaleString('id-ID')"></span>
                                <span class="text-xs text-brand-warm-gray" x-text="product.unit_short"></span>
                            </td>

                            <!-- Status (Text Only with Dot Indicator, NO BADGE) -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="size-2 rounded-full"
                                        :class="product.is_active ? 'bg-emerald-600' : 'bg-stone-400'"></span>
                                    @can('produk-edit')
                                        <button type="button" @click="toggleStatus(product.id)" :disabled="isProcessing"
                                            class="text-xs font-semibold uppercase tracking-wider hover:underline cursor-pointer transition select-none"
                                            :class="product.is_active ? 'text-emerald-800' : 'text-stone-500 line-through'"
                                            title="Klik untuk mengubah status aktif/nonaktif"
                                            x-text="product.is_active ? 'Aktif' : 'Nonaktif'">
                                        </button>
                                    @else
                                        <span class="text-xs font-semibold uppercase tracking-wider"
                                            :class="product.is_active ? 'text-emerald-800' : 'text-stone-500 line-through'"
                                            x-text="product.is_active ? 'Aktif' : 'Nonaktif'">
                                        </span>
                                    @endcan
                                </div>
                            </td>

                            <!-- Actions (Bare Icons, NO background box) -->
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    @can('produk-edit')
                                        <a :href="product.edit_url" wire:navigate
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer"
                                            title="Ubah Data Produk">
                                            <i class="ti ti-edit text-base"></i>
                                        </a>
                                    @endcan

                                    @can('produk-delete')
                                        <button type="button" @click="confirmDelete(product)"
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-red-700 hover:bg-red-50 transition cursor-pointer"
                                            title="Hapus Produk">
                                            <i class="ti ti-trash text-base"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>

                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="filteredProducts.length === 0">
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="size-16 rounded-2xl bg-neutral-100 flex items-center justify-center text-neutral-400 mb-3">
                                    <i class="ti ti-box-off text-3xl"></i>
                                </div>
                                <h3 class="text-base font-bold text-brand-espresso">Tidak ada produk ditemukan</h3>
                                <p class="text-sm text-brand-warm-gray mt-1">
                                    Coba ubah kata kunci pencarian atau sesuaikan filter status dan satuan.
                                </p>
                                <button type="button" @click="search = ''; statusFilter = 'all'; unitFilter = 'all'"
                                    class="mt-4 px-4 py-2 text-xs font-bold text-brand-primary hover:bg-neutral-100 rounded-xl transition cursor-pointer">
                                    Reset Filter
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Produk (Minimalist, Clean) -->
    <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div x-show="showDeleteModal" x-transition.opacity.duration.200ms
                class="fixed inset-0 bg-neutral-900/40 backdrop-blur-xs" @click="showDeleteModal = false"></div>

            <div x-show="showDeleteModal" x-transition.scale.duration.200ms
                class="relative bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-xl border border-brand-border space-y-4">
                <div>
                    <h3 class="text-lg font-bold text-brand-espresso">Hapus Produk Jadi</h3>
                    <p class="text-sm text-brand-warm-gray mt-1">
                        Apakah Anda yakin ingin menghapus produk <strong class="text-brand-espresso" x-text="deleteTarget.name"></strong>?
                        Tindakan ini tidak dapat dibatalkan jika produk belum memiliki riwayat transaksi.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showDeleteModal = false" :disabled="isProcessing"
                        class="px-4 py-2 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="executeDelete()" :disabled="isProcessing"
                        class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-xl text-sm font-bold shadow-xs transition cursor-pointer disabled:opacity-50">
                        <span x-show="!isProcessing">Hapus Sekarang</span>
                        <span x-show="isProcessing">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
