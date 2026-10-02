<div class="space-y-6" x-data="{
    // Tab State
    activeTab: (function() {
        const hash = window.location.hash.replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        if (['batches', 'mutations'].includes(hash)) return hash;
        if (['batches', 'mutations'].includes(queryTab)) return queryTab;
        return 'batches';
    })(),

    init() {
        this.$watch('activeTab', (tab) => {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.replaceState(null, '', url.toString());
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (['batches', 'mutations'].includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });
    },

    // Search and Filter State
    searchQuery: '',
    statusFilter: 'all',
    mutationTypeFilter: 'all',
    mutationMaterialFilter: 'all',

    // Modals
    showCreateModal: false,
    showDetailModal: false,
    showCancelModal: false,
    isProcessing: false,
    errorMessage: '',

    // Data collections passed from backend
    products: {{ \Illuminate\Support\Js::from($products) }},
    batches: {{ \Illuminate\Support\Js::from($batches) }},
    mutations: {{ \Illuminate\Support\Js::from($mutations) }},
    rawMaterials: {{ \Illuminate\Support\Js::from($rawMaterials) }},

    // Create Batch Form State
    form: {
        product_id: '',
        planned_qty: 50,
        actual_qty_good: 50,
        actual_qty_bad: 0,
        notes: ''
    },

    // Detail Modal Target
    selectedBatch: null,

    // Cancel Modal Target
    cancelTarget: null,

    // Reset Create Form
    resetForm() {
        this.form = {
            product_id: this.products.length > 0 ? this.products[0].id : '',
            planned_qty: 50,
            actual_qty_good: 50,
            actual_qty_bad: 0,
            notes: ''
        };
        this.errorMessage = '';
        this.onProductChange();
    },

    openCreateModal() {
        this.resetForm();
        this.showCreateModal = true;
    },

    // Selected product helper
    get selectedProduct() {
        return this.products.find(p => p.id == this.form.product_id) || null;
    },

    // Live calculation of ingredients needed for create form
    get calculatedIngredients() {
        const prod = this.selectedProduct;
        if (!prod || !prod.recipes) return [];
        const qty = parseInt(this.form.planned_qty) || 0;

        return prod.recipes.map(recipe => {
            const raw = recipe.raw_material || this.rawMaterials.find(m => m.id == recipe.raw_material_id) || {};
            const neededPerUnit = parseFloat(recipe.quantity_needed) || 0;
            const totalNeeded = neededPerUnit * qty;
            const currentStock = parseFloat(raw.stock) || 0;
            const isShortage = currentStock < totalNeeded;
            const costPerUnit = parseFloat(raw.cost_per_unit) || 0;
            const subtotal = totalNeeded * costPerUnit;

            return {
                id: raw.id,
                name: raw.name || 'Bahan Tidak Diketahui',
                unit: raw.display_unit || raw.unit || 'g',
                needed_per_unit: neededPerUnit,
                total_needed: totalNeeded,
                current_stock: currentStock,
                is_shortage: isShortage,
                deficit: isShortage ? (totalNeeded - currentStock) : 0,
                cost_per_unit: costPerUnit,
                subtotal: subtotal
            };
        });
    },

    // Check if any ingredient is short in create form
    get hasStockShortage() {
        return this.calculatedIngredients.some(item => item.is_shortage);
    },

    // Estimated total material cost for form
    get estimatedTotalCost() {
        return this.calculatedIngredients.reduce((sum, item) => sum + item.subtotal, 0);
    },

    // Estimated realized unit cost
    get estimatedUnitCost() {
        const good = parseInt(this.form.actual_qty_good) || 0;
        if (good <= 0) return 0;
        return this.estimatedTotalCost / good;
    },

    onProductChange() {
        this.form.actual_qty_good = this.form.planned_qty;
    },

    onPlannedQtyChange() {
        const planned = parseInt(this.form.planned_qty) || 0;
        const bad = parseInt(this.form.actual_qty_bad) || 0;
        this.form.actual_qty_good = Math.max(0, planned - bad);
    },

    onBadQtyChange() {
        const planned = parseInt(this.form.planned_qty) || 0;
        const bad = parseInt(this.form.actual_qty_bad) || 0;
        this.form.actual_qty_good = Math.max(0, planned - bad);
    },

    // Submit New Batch
    async submitBatch() {
        if (!this.form.product_id) {
            this.errorMessage = 'Pilih produk yang akan diproduksi.';
            return;
        }

        if (!this.form.planned_qty || this.form.planned_qty <= 0) {
            this.errorMessage = 'Target produksi harus lebih dari 0.';
            return;
        }

        if (this.hasStockShortage) {
            this.errorMessage = 'Stok bahan baku tidak mencukupi. Silakan sesuaikan target atau restock bahan.';
            return;
        }

        this.isProcessing = true;
        this.errorMessage = '';

        try {
            const res = await $wire.executeBatch(this.form);
            this.isProcessing = false;

            if (res.success) {
                this.showCreateModal = false;
                window.toast(res.message, 'success');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                this.errorMessage = res.message;
                window.toast(res.message, 'error');
            }
        } catch (e) {
            this.isProcessing = false;
            this.errorMessage = 'Terjadi kesalahan sistem saat mengeksekusi batch.';
            window.toast(this.errorMessage, 'error');
        }
    },

    // Open detail modal
    openDetail(batch) {
        this.selectedBatch = batch;
        this.showDetailModal = true;
    },

    // Confirm cancel batch
    confirmCancel(batch) {
        this.cancelTarget = batch;
        this.showCancelModal = true;
    },

    async submitCancel() {
        if (!this.cancelTarget) return;

        this.isProcessing = true;
        try {
            const res = await $wire.cancelBatch(this.cancelTarget.id);
            this.isProcessing = false;

            if (res.success) {
                this.showCancelModal = false;
                window.toast(res.message, 'success');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                window.toast(res.message, 'error');
            }
        } catch (e) {
            this.isProcessing = false;
            window.toast('Terjadi kesalahan saat membatalkan batch.', 'error');
        }
    },

    // Filtered Batches
    get filteredBatches() {
        const q = this.searchQuery.toLowerCase().trim();
        return this.batches.filter(b => {
            const matchesSearch = !q ||
                b.batch_code.toLowerCase().includes(q) ||
                (b.product && b.product.name.toLowerCase().includes(q)) ||
                (b.notes && b.notes.toLowerCase().includes(q)) ||
                (b.user && b.user.name.toLowerCase().includes(q));

            const matchesStatus = this.statusFilter === 'all' || b.status === this.statusFilter;

            return matchesSearch && matchesStatus;
        });
    },

    // Filtered Mutations
    get filteredMutations() {
        const q = this.searchQuery.toLowerCase().trim();
        return this.mutations.filter(m => {
            const matchesSearch = !q ||
                (m.raw_material && m.raw_material.name.toLowerCase().includes(q)) ||
                (m.reference_number && m.reference_number.toLowerCase().includes(q)) ||
                (m.notes && m.notes.toLowerCase().includes(q));

            const matchesType = this.mutationTypeFilter === 'all' || m.type === this.mutationTypeFilter;
            const matchesMaterial = this.mutationMaterialFilter === 'all' || m.raw_material_id == this.mutationMaterialFilter;

            return matchesSearch && matchesType && matchesMaterial;
        });
    }
}">

    <!-- Header Section (Identik dengan Header Role & Permission) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Produksi &amp; Manufaktur
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola proses masak dapur, konversi bahan baku ke produk jadi, dan mutasi kartu stok real-time.
            </p>
        </div>
    </div>

    <!-- Main Section (Clean Underline Tab Navigation - Identik dengan Role & Permission) -->
    <div class="space-y-6">

        <!-- Tab Navigation Bar -->
        <div class="flex border-b border-brand-border gap-4 sm:gap-8 overflow-x-auto">
            <!-- 1. Tab Riwayat Batch Masak -->
            <button type="button" @click="activeTab = 'batches'"
                :class="activeTab === 'batches' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' :
                    'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-flame text-xl"></i>
                <span>Riwayat Batch Masak</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'batches' ? 'bg-brand-soft-cream text-brand-primary' :
                        'bg-neutral-100 text-brand-warm-gray'"
                    x-text="batches.length">
                </span>
            </button>

            <!-- 2. Tab Kartu Stok Bahan Baku -->
            <button type="button" @click="activeTab = 'mutations'"
                :class="activeTab === 'mutations' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' :
                    'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-file-analytics text-xl"></i>
                <span>Kartu Stok Bahan Baku</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'mutations' ? 'bg-brand-soft-cream text-brand-primary' :
                        'bg-neutral-100 text-brand-warm-gray'"
                    x-text="mutations.length">
                </span>
            </button>
        </div>

        <!-- TAB 1: RIWAYAT BATCH MASAK -->
        <div x-show="activeTab === 'batches'" class="space-y-4">

            <!-- Action & Filter Bar (Identik dengan UI Tab Roles / Permissions) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <i
                            class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                        <input type="text" x-model="searchQuery"
                            placeholder="Cari kode batch, nama produk, catatan, staf..."
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                    </div>

                    <!-- Filter by Status Dropdown -->
                    <div class="shrink-0">
                        <select x-model="statusFilter"
                            class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                            <option value="all">Semua Status Batch</option>
                            <option value="completed">Selesai (Completed)</option>
                            <option value="cancelled">Dibatalkan (Cancelled)</option>
                        </select>
                    </div>
                </div>

                @can('produksi-create')
                    <button type="button" @click="openCreateModal()"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
                        <i class="ti ti-plus text-lg"></i>
                        <span>Mulai Batch Masak Baru</span>
                    </button>
                @endcan
            </div>

            <!-- Batches Table (Identik dengan Tabel Role & Permission) -->
            <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-6 whitespace-nowrap">Kode Batch</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Produk Hasil Masak</th>
                            <th class="py-3.5 px-6 text-center whitespace-nowrap">Target vs Hasil QC</th>
                            <th class="py-3.5 px-6 text-right whitespace-nowrap">Biaya Bahan (HPP)</th>
                            <th class="py-3.5 px-6 text-center whitespace-nowrap">Status</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Waktu &amp; Operator</th>
                            <th class="py-3.5 px-6 text-right whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                        <template x-for="batch in filteredBatches" :key="batch.id">
                            <tr class="hover:bg-neutral-50/50 transition">

                                <!-- Kode Batch -->
                                <td class="py-4 px-6 align-middle whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <i class="ti ti-flame text-brand-primary text-lg shrink-0"></i>
                                        <code
                                            class="font-mono font-bold text-brand-espresso text-sm bg-neutral-100 px-2 py-0.5 rounded"
                                            x-text="batch.batch_code"></code>
                                    </div>
                                    <p class="text-xs text-brand-warm-gray mt-1 truncate max-w-50"
                                        x-text="batch.notes || 'Tanpa catatan'"></p>
                                </td>

                                <!-- Produk Hasil Masak -->
                                <td class="py-4 px-6 align-middle whitespace-nowrap">
                                    <span class="font-bold text-brand-espresso text-base block"
                                        x-text="batch.product ? batch.product.name : 'Produk Tidak Ditemukan'"></span>
                                    <span class="text-xs text-brand-warm-gray block"
                                        x-text="'Kemasan: ' + (batch.product?.unit || 'pcs')"></span>
                                </td>

                                <!-- Target vs Hasil QC -->
                                <td class="py-4 px-6 align-middle text-center whitespace-nowrap">
                                    <div class="inline-flex flex-col items-center gap-1">
                                        <div class="flex items-center gap-1.5 font-mono text-xs font-bold">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-50 text-green-700 border border-green-200"
                                                x-text="batch.actual_qty_good + ' Lolos'"></span>
                                            <template x-if="batch.actual_qty_bad > 0">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-50 text-red-700 border border-red-200"
                                                    x-text="batch.actual_qty_bad + ' Reject'"></span>
                                            </template>
                                        </div>
                                        <span class="text-xs text-brand-warm-gray font-mono"
                                            x-text="'Target: ' + batch.planned_qty + ' ' + (batch.product?.unit || 'pcs')"></span>
                                    </div>
                                </td>

                                <!-- Biaya Bahan & HPP Riil -->
                                <td class="py-4 px-6 align-middle text-right whitespace-nowrap">
                                    <p class="font-mono font-bold text-brand-espresso"
                                        x-text="'Rp ' + Number(batch.total_material_cost).toLocaleString('id-ID')"></p>
                                    <p class="text-xs font-mono text-brand-primary mt-0.5"
                                        x-text="'Rp ' + Number(batch.unit_cost_produced).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' / unit'">
                                    </p>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-6 align-middle text-center whitespace-nowrap">
                                    <template x-if="batch.status === 'completed'">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">
                                            <i class="ti ti-circle-check text-sm"></i>
                                            <span>Selesai</span>
                                        </span>
                                    </template>
                                    <template x-if="batch.status === 'cancelled'">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                            <i class="ti ti-ban text-sm"></i>
                                            <span>Dibatalkan</span>
                                        </span>
                                    </template>
                                </td>

                                <!-- Waktu & Operator -->
                                <td class="py-4 px-6 align-middle whitespace-nowrap">
                                    <p class="text-xs font-semibold text-brand-espresso"
                                        x-text="batch.completed_at ? new Date(batch.completed_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'">
                                    </p>
                                    <div
                                        class="inline-flex items-center gap-1.5 text-brand-warm-gray font-medium text-xs mt-0.5">
                                        <i class="ti ti-user text-sm"></i>
                                        <span x-text="batch.user ? batch.user.name : 'Sistem'"></span>
                                    </div>
                                </td>

                                <!-- Actions (Identik dengan Tombol Aksi di Role & Permission) -->
                                <td class="py-4 px-6 align-middle text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="openDetail(batch)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                            <i class="ti ti-file-text text-base"></i>
                                            <span>Rincian</span>
                                        </button>

                                        @can('produksi-delete')
                                            <template x-if="batch.status === 'completed'">
                                                <button type="button" @click="confirmCancel(batch)"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200">
                                                    <i class="ti ti-ban text-base"></i>
                                                    <span>Batalkan</span>
                                                </button>
                                            </template>
                                        @endcan
                                    </div>
                                </td>

                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="filteredBatches.length === 0">
                            <tr>
                                <td colspan="7" class="py-12 text-center text-brand-warm-gray">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <p class="font-bold text-brand-espresso">Belum ada riwayat batch masak</p>
                                        <p class="text-xs text-brand-warm-gray">Mulai proses produksi pertama Anda
                                            untuk mengonversi stok bahan baku menjadi produk jadi siap jual.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- TAB 2: KARTU STOK BAHAN BAKU (MUTASI) -->
        <div x-show="activeTab === 'mutations'" class="space-y-4">

            <!-- Action & Filter Bar (Identik dengan UI Tab Roles / Permissions) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <!-- Search Input -->
                    <div class="relative flex-1 max-w-md">
                        <i
                            class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                        <input type="text" x-model="searchQuery"
                            placeholder="Cari nama bahan, kode referensi batch, keterangan..."
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                    </div>

                    <!-- Filter Dropdown Bahan Baku Dinamis -->
                    <div class="shrink-0">
                        <select x-model="mutationMaterialFilter"
                            class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                            <option value="all">Semua Bahan Baku</option>
                            <template x-for="mat in rawMaterials" :key="mat.id">
                                <option :value="mat.id" x-text="mat.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Filter Dropdown Arah Mutasi -->
                    <div class="shrink-0">
                        <select x-model="mutationTypeFilter"
                            class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                            <option value="all">Semua Arah Mutasi</option>
                            <option value="out">Keluar (Pemakaian Produksi)</option>
                            <option value="in">Masuk (Pengembalian / Koreksi)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Mutations Table (Identik dengan Tabel Role & Permission) -->
            <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                            <th class="py-3.5 px-6 whitespace-nowrap">Waktu Mutasi</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Bahan Baku</th>
                            <th class="py-3.5 px-6 text-center whitespace-nowrap">Jenis &amp; Referensi</th>
                            <th class="py-3.5 px-6 text-right whitespace-nowrap">Jumlah Perubahan</th>
                            <th class="py-3.5 px-6 text-right whitespace-nowrap">Saldo Stok</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Keterangan &amp; Staf</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                        <template x-for="mut in filteredMutations" :key="mut.id">
                            <tr class="hover:bg-neutral-50/50 transition">

                                <!-- Waktu -->
                                <td class="py-4 px-6 align-middle font-mono text-xs text-brand-espresso whitespace-nowrap"
                                    x-text="new Date(mut.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })">
                                </td>

                                <!-- Nama Bahan Baku -->
                                <td class="py-4 px-6 align-middle whitespace-nowrap">
                                    <span class="font-bold text-brand-espresso text-base block"
                                        x-text="mut.raw_material ? mut.raw_material.name : 'Bahan Baku'"></span>
                                    <span class="text-xs text-brand-warm-gray block"
                                        x-text="'Satuan: ' + (mut.raw_material?.display_unit || mut.raw_material?.unit || 'g')"></span>
                                </td>

                                <!-- Jenis & Referensi -->
                                <td class="py-4 px-6 align-middle text-center whitespace-nowrap">
                                    <template x-if="mut.type === 'out'">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            <i class="ti ti-arrow-up-right text-xs"></i>
                                            <span>Produksi (Keluar)</span>
                                        </span>
                                    </template>
                                    <template x-if="mut.type === 'in'">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">
                                            <i class="ti ti-arrow-down-left text-xs"></i>
                                            <span>Masuk (Koreksi)</span>
                                        </span>
                                    </template>
                                    <p class="font-mono text-xs text-brand-primary mt-1"
                                        x-text="mut.reference_number || '-'"></p>
                                </td>

                                <!-- Jumlah Perubahan -->
                                <td class="py-4 px-6 align-middle text-right whitespace-nowrap">
                                    <span class="font-mono font-bold text-sm"
                                        :class="mut.type === 'out' ? 'text-red-600' : 'text-emerald-600'"
                                        x-text="(mut.type === 'out' ? '- ' : '+ ') + Number(mut.quantity).toLocaleString('id-ID') + ' ' + (mut.raw_material?.display_unit || mut.raw_material?.unit || 'g')">
                                    </span>
                                </td>

                                <!-- Saldo Stok -->
                                <td class="py-4 px-6 align-middle text-right font-mono text-xs whitespace-nowrap">
                                    <div class="text-brand-warm-gray line-through"
                                        x-text="Number(mut.stock_before).toLocaleString('id-ID')"></div>
                                    <div class="font-bold text-brand-espresso text-sm"
                                        x-text="Number(mut.stock_after).toLocaleString('id-ID')"></div>
                                </td>

                                <!-- Keterangan & Staf -->
                                <td class="py-4 px-6 align-middle whitespace-nowrap">
                                    <p class="text-xs text-brand-espresso truncate max-w-55"
                                        x-text="mut.notes || '-'"></p>
                                    <div
                                        class="inline-flex items-center gap-1.5 text-brand-warm-gray font-medium text-xs mt-0.5">
                                        <i class="ti ti-user text-sm"></i>
                                        <span x-text="mut.user ? mut.user.name : 'Sistem'"></span>
                                    </div>
                                </td>

                            </tr>
                        </template>

                        <!-- Empty State -->
                        <template x-if="filteredMutations.length === 0">
                            <tr>
                                <td colspan="6" class="py-12 text-center text-brand-warm-gray">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <p class="font-bold text-brand-espresso">Tidak ada catatan mutasi stok</p>
                                        <p class="text-xs text-brand-warm-gray">Semua pergerakan bahan baku keluar dan
                                            masuk akan dicatat secara otomatis di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- MODAL 1: MULAI BATCH MASAK BARU (Identik dengan Modal Role & Permission) -->
    <div x-cloak x-show="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity"
            @click="showCreateModal = false"></div>

        <!-- Modal Box -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]"
                @click.outside="showCreateModal = false">

                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
                    <div class="flex items-center gap-2.5">
                        <i class="ti ti-flame text-brand-primary text-2xl"></i>
                        <h2 class="text-lg sm:text-xl font-bold text-brand-espresso">Mulai Batch Masak Baru</h2>
                    </div>
                    <button type="button" @click="showCreateModal = false"
                        class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 overflow-y-auto flex-1">

                    <!-- Error Message -->
                    <div x-cloak x-show="errorMessage"
                        class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-start gap-2 whitespace-pre-line">
                        <i class="ti ti-alert-circle text-lg shrink-0 mt-0.5"></i>
                        <span x-text="errorMessage"></span>
                    </div>

                    <!-- DYNAMIC PRODUCT SELECT -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                            Pilih Produk yang Dimasak <span class="text-red-500">*</span>
                        </label>
                        <select x-model="form.product_id" @change="onProductChange()"
                            class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium focus:outline-none focus:border-brand-primary">
                            <option value="">-- Pilih Produk Jadi --</option>
                            <template x-for="prod in products" :key="prod.id">
                                <option :value="prod.id"
                                    x-text="prod.name + ' (Kemasan: ' + (prod.unit || 'pcs') + ')'"></option>
                            </template>
                        </select>
                        <p class="text-xs text-brand-warm-gray mt-1">Produk jadi yang akan diproduksi oleh dapur Halala
                            Food.</p>
                    </div>

                    <!-- Target Produksi & Hasil QC -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-brand-espresso mb-1.5">
                                Target Masak (Unit) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" min="1" x-model="form.planned_qty"
                                @input="onPlannedQtyChange()"
                                class="w-full px-4 py-2.5 font-mono font-bold text-base bg-white border border-brand-border rounded-xl text-brand-espresso focus:outline-none focus:border-brand-primary">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-emerald-700 mb-1.5">
                                Hasil Lolos QC (Siap Jual) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" min="0" x-model="form.actual_qty_good"
                                class="w-full px-4 py-2.5 font-mono font-bold text-base bg-green-50/50 border border-green-300 rounded-xl text-green-800 focus:outline-none focus:border-green-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-red-700 mb-1.5">
                                Gagal / Reject (Pecah/Sobek)
                            </label>
                            <input type="number" min="0" x-model="form.actual_qty_bad"
                                @input="onBadQtyChange()"
                                class="w-full px-4 py-2.5 font-mono font-bold text-base bg-red-50/40 border border-red-200 rounded-xl text-red-700 focus:outline-none focus:border-red-400">
                        </div>
                    </div>

                    <!-- LIVE RECIPE & INGREDIENT AVAILABILITY BREAKDOWN -->
                    <div class="p-4 bg-neutral-50/70 rounded-xl border border-brand-border space-y-3">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-brand-espresso flex items-center gap-1.5">
                                <i class="ti ti-receipt text-brand-primary text-base"></i>
                                <span>Kalkulasi Alokasi Bahan Baku (BOM)</span>
                            </span>
                            <span class="text-xs font-mono text-brand-warm-gray"
                                x-text="calculatedIngredients.length + ' Bahan Baku'"></span>
                        </div>

                        <!-- Ingredients List -->
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <template x-for="item in calculatedIngredients" :key="item.id">
                                <div class="p-2.5 rounded-lg border flex items-center justify-between gap-3 text-xs"
                                    :class="item.is_shortage ? 'bg-red-50/80 border-red-200 text-red-900' :
                                        'bg-white border-brand-border text-brand-espresso'">

                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold truncate" x-text="item.name"></p>
                                        <p class="text-xs text-brand-warm-gray mt-0.5"
                                            x-text="'Takaran: ' + item.needed_per_unit + ' ' + item.unit + ' / unit'">
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="font-mono font-bold"
                                            x-text="'Butuh: ' + Number(item.total_needed).toLocaleString('id-ID') + ' ' + item.unit">
                                        </p>
                                        <p class="text-xs font-mono mt-0.5"
                                            :class="item.is_shortage ? 'text-red-700 font-bold' : 'text-emerald-600'"
                                            x-text="'Stok: ' + Number(item.current_stock).toLocaleString('id-ID') + ' ' + item.unit">
                                        </p>
                                    </div>

                                    <!-- Warning Badge if Shortage -->
                                    <template x-if="item.is_shortage">
                                        <span
                                            class="px-2 py-0.5 rounded-md bg-red-600 text-white text-xs font-bold shrink-0">
                                            Kurang <span x-text="Number(item.deficit).toLocaleString('id-ID')"></span>
                                        </span>
                                    </template>
                                </div>
                            </template>

                            <template x-if="calculatedIngredients.length === 0">
                                <p class="text-xs text-brand-warm-gray text-center py-2">
                                    Pilih produk terlebih dahulu untuk melihat formula takaran bahan.
                                </p>
                            </template>
                        </div>

                        <!-- Shortage Alert Banner -->
                        <div x-show="hasStockShortage"
                            class="p-3 bg-red-100 border border-red-300 rounded-xl text-xs font-bold text-red-800 flex items-start gap-2">
                            <i class="ti ti-alert-triangle text-base text-red-600 shrink-0 mt-0.5"></i>
                            <div>
                                <p>Stok bahan baku tidak mencukupi untuk target ini!</p>
                                <p class="font-normal text-red-700 mt-0.5">Harap kurangi target masak atau restock
                                    bahan di gudang terlebih dahulu.</p>
                            </div>
                        </div>

                        <!-- Financial Summary Footer -->
                        <div class="pt-3 border-t border-brand-border/60 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-brand-warm-gray">Total Biaya Bahan:</span>
                                <span class="font-mono font-bold text-brand-espresso ml-1"
                                    x-text="'Rp ' + Math.round(estimatedTotalCost).toLocaleString('id-ID')"></span>
                            </div>
                            <div>
                                <span class="text-brand-warm-gray">Estimasi HPP Riil:</span>
                                <span class="font-mono font-black text-brand-primary ml-1"
                                    x-text="'Rp ' + Number(estimatedUnitCost).toLocaleString('id-ID', { minimumFractionDigits: 2 }) + ' / unit'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Produksi -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1.5">Catatan Batch / QC Dapur
                            (Opsional)</label>
                        <textarea x-model="form.notes" rows="2"
                            placeholder="Misal: Batch pagi, kematangan adonan optimal, 2 bungkus reject pada sealing kemasan."
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary"></textarea>
                    </div>

                </div>

                <!-- Modal Footer (Identik dengan Modal Role & Permission) -->
                <div
                    class="flex items-center justify-end gap-3 px-6 py-4 border-t border-brand-border bg-neutral-50/60 shrink-0">
                    <button type="button" @click="showCreateModal = false" :disabled="isProcessing"
                        class="px-5 py-2.5 rounded-xl border border-brand-border font-bold text-sm text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="submitBatch()"
                        :disabled="isProcessing || hasStockShortage || calculatedIngredients.length === 0"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover disabled:opacity-50 text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                        <span
                            x-text="isProcessing ? 'Memproses Eksekusi...' : 'Eksekusi &amp; Potong Stok Bahan'"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL 2: DETAIL RINCIAN BAHAN BATCH (Identik dengan Modal Role & Permission) -->
    <div x-cloak x-show="showDetailModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity"
            @click="showDetailModal = false"></div>

        <!-- Modal Box -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]"
                @click.outside="showDetailModal = false">

                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
                    <div class="flex items-center gap-2.5">
                        <i class="ti ti-file-text text-brand-primary text-2xl"></i>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-brand-primary"
                                    x-text="selectedBatch ? selectedBatch.batch_code : ''"></span>
                                <span class="text-xs px-2 py-0.5 rounded-md font-bold"
                                    :class="selectedBatch?.status === 'completed' ? 'bg-green-50 text-green-700' :
                                        'bg-red-50 text-red-700'"
                                    x-text="selectedBatch?.status === 'completed' ? 'Selesai' : 'Dibatalkan'"></span>
                            </div>
                            <h2 class="text-base sm:text-lg font-bold text-brand-espresso mt-0.5"
                                x-text="selectedBatch?.product ? selectedBatch.product.name : 'Detail Batch'"></h2>
                        </div>
                    </div>
                    <button type="button" @click="showDetailModal = false"
                        class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 overflow-y-auto flex-1">

                    <!-- Stats Grid -->
                    <div
                        class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 bg-brand-soft-cream/40 rounded-xl border border-brand-border text-xs">
                        <div>
                            <p class="text-brand-warm-gray">Target Masak</p>
                            <p class="font-mono font-bold text-sm text-brand-espresso mt-0.5"
                                x-text="(selectedBatch?.planned_qty || 0) + ' Unit'"></p>
                        </div>
                        <div>
                            <p class="text-brand-warm-gray">Lolos QC</p>
                            <p class="font-mono font-bold text-sm text-green-700 mt-0.5"
                                x-text="(selectedBatch?.actual_qty_good || 0) + ' Unit'"></p>
                        </div>
                        <div>
                            <p class="text-brand-warm-gray">Reject</p>
                            <p class="font-mono font-bold text-sm text-red-600 mt-0.5"
                                x-text="(selectedBatch?.actual_qty_bad || 0) + ' Unit'"></p>
                        </div>
                        <div>
                            <p class="text-brand-warm-gray">HPP Riil / Unit</p>
                            <p class="font-mono font-bold text-sm text-brand-primary mt-0.5"
                                x-text="'Rp ' + Number(selectedBatch?.unit_cost_produced || 0).toLocaleString('id-ID', { minimumFractionDigits: 2 })">
                            </p>
                        </div>
                    </div>

                    <!-- Material Items Breakdown -->
                    <div>
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-brand-espresso mb-2.5 flex items-center gap-1.5">
                            <i class="ti ti-leaf text-brand-primary text-base"></i>
                            <span>Bahan Baku yang Digunakan</span>
                        </h4>
                        <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
                            <table class="w-full text-left border-collapse text-xs sm:text-sm">
                                <thead>
                                    <tr
                                        class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso font-bold uppercase tracking-wider text-xs">
                                        <th class="py-2.5 px-4 whitespace-nowrap">Bahan Baku</th>
                                        <th class="py-2.5 px-4 text-right whitespace-nowrap">Pemakaian</th>
                                        <th class="py-2.5 px-4 text-right whitespace-nowrap">Biaya Satuan</th>
                                        <th class="py-2.5 px-4 text-right whitespace-nowrap">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-brand-border">
                                    <template x-for="bm in (selectedBatch?.batch_materials || [])"
                                        :key="bm.id">
                                        <tr class="hover:bg-neutral-50/50">
                                            <td class="py-3 px-4 font-semibold text-brand-espresso whitespace-nowrap"
                                                x-text="bm.raw_material ? bm.raw_material.name : 'Bahan Baku'"></td>
                                            <td class="py-3 px-4 text-right font-mono whitespace-nowrap"
                                                x-text="Number(bm.actual_used_qty).toLocaleString('id-ID') + ' ' + (bm.unit_name || 'g')">
                                            </td>
                                            <td class="py-3 px-4 text-right font-mono whitespace-nowrap"
                                                x-text="'Rp ' + Number(bm.cost_per_unit).toLocaleString('id-ID')"></td>
                                            <td class="py-3 px-4 text-right font-mono font-bold text-brand-espresso whitespace-nowrap"
                                                x-text="'Rp ' + Number(bm.subtotal_cost).toLocaleString('id-ID')"></td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-brand-soft-cream/30 font-bold border-t border-brand-border">
                                        <td colspan="3" class="py-3 px-4 text-brand-warm-gray">Total Biaya Bahan
                                            Baku:</td>
                                        <td class="py-3 px-4 text-right font-mono text-sm text-brand-espresso font-extrabold"
                                            x-text="'Rp ' + Number(selectedBatch?.total_material_cost || 0).toLocaleString('id-ID')">
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Notes & Audit -->
                    <div class="text-xs p-3.5 bg-neutral-50/60 rounded-xl border border-brand-border space-y-1">
                        <p class="font-bold text-brand-espresso">Catatan Produksi:</p>
                        <p class="text-brand-warm-gray" x-text="selectedBatch?.notes || 'Tidak ada catatan khusus.'">
                        </p>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div
                    class="flex items-center justify-end px-6 py-4 border-t border-brand-border bg-neutral-50/60 shrink-0">
                    <button type="button" @click="showDetailModal = false"
                        class="px-5 py-2.5 rounded-xl border border-brand-border font-bold text-sm text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL 3: KONFIRMASI BATALKAN BATCH (Identik dengan Modal Delete Role & Permission) -->
    <div x-cloak x-show="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity"
            @click="showCancelModal = false"></div>

        <!-- Modal Box -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-brand-border p-6 text-center"
                @click.outside="showCancelModal = false">

                <div
                    class="size-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4 border border-red-100">
                    <i class="ti ti-alert-triangle text-2xl"></i>
                </div>

                <h3 class="text-lg font-bold text-brand-espresso">Batalkan Batch Produksi?</h3>

                <p class="text-sm text-brand-warm-gray mt-2 leading-relaxed">
                    Apakah Anda yakin ingin membatalkan batch <span class="font-mono font-bold text-brand-espresso"
                        x-text="cancelTarget ? cancelTarget.batch_code : ''"></span>?
                    Seluruh stok bahan baku yang terpakai akan dikembalikan ke gudang, dan stok produk jadi di gudang
                    akan dikurangi kembali.
                </p>

                <div class="flex items-center justify-center gap-3 mt-6">
                    <button type="button" @click="showCancelModal = false" :disabled="isProcessing"
                        class="px-5 py-2.5 rounded-xl border border-brand-border font-bold text-sm text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Kembali
                    </button>
                    <button type="button" @click="submitCancel()" :disabled="isProcessing"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                        <span x-text="isProcessing ? 'Membatalkan...' : 'Ya, Batalkan Batch'"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>
