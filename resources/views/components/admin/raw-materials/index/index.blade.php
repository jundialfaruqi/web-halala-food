<div class="space-y-6" x-data="{
    activeTab: (function() {
        const hash = window.location.hash.replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        if (['materials', 'recipes'].includes(hash)) return hash;
        if (['materials', 'recipes'].includes(queryTab)) return queryTab;
        return 'materials';
    })(),

    init() {
        this.$watch('activeTab', (tab) => {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.replaceState(null, '', url.toString());
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (['materials', 'recipes'].includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });
    },

    search: '',
    statusFilter: 'all',
    isProcessing: false,

    // Backend data
    materials: {{ \Illuminate\Support\Js::from($materials) }},
    products: {{ \Illuminate\Support\Js::from($products) }},
    units: {{ \Illuminate\Support\Js::from($units) }},

    // Material Modal State
    showMaterialModal: false,
    materialModalTitle: 'Tambah Bahan Baku',
    materialForm: { id: null, name: '', unit_id: '', stock: 0, min_stock: 0, cost_per_unit: 0 },
    materialErrors: {},
    materialFormError: '',

    // Calculator State inside Material Modal
    showCalculator: false,
    calcPackageCount: 1,
    calcContentPerPackage: 1000,
    calcPricePerPackage: '',
    calcTotalPrice: '',
    calcSelectedUnitId: '',
    calcResult: null,
    calcError: '',

    // Delete Material Modal State
    showDeleteModal: false,
    deleteTarget: { id: null, name: '', recipes_count: 0 },

    // Recipe Modal State
    showRecipeModal: false,
    selectedProduct: null,
    recipeRows: [],
    recipeError: '',

    // Toast Notification helper
    notify(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    // Filtered materials
    get filteredMaterials() {
        return this.materials.filter(mat => {
            const query = this.search.toLowerCase().trim();
            const matchesSearch = !query ||
                mat.name.toLowerCase().includes(query) ||
                mat.unit_short.toLowerCase().includes(query);

            const matchesStatus = this.statusFilter === 'all' ||
                (this.statusFilter === 'safe' && mat.stock_status === 'safe') ||
                (this.statusFilter === 'warning' && mat.stock_status === 'warning') ||
                (this.statusFilter === 'danger' && mat.stock_status === 'danger');

            return matchesSearch && matchesStatus;
        });
    },

    // Sync price fields in calculator
    updateCalcFromPricePerPackage() {
        this.calcResult = null;
        this.calcError = '';
        const count = parseFloat(this.calcPackageCount) || 0;
        const pricePerPkg = parseFloat(this.calcPricePerPackage) || 0;
        if (count > 0 && pricePerPkg > 0) {
            this.calcTotalPrice = Math.round(count * pricePerPkg * 100) / 100;
        } else if (!this.calcPricePerPackage) {
            this.calcTotalPrice = '';
        }
    },

    updateCalcFromTotalPrice() {
        this.calcResult = null;
        this.calcError = '';
        const count = parseFloat(this.calcPackageCount) || 0;
        const total = parseFloat(this.calcTotalPrice) || 0;
        if (count > 0 && total > 0) {
            this.calcPricePerPackage = Math.round((total / count) * 100) / 100;
        } else if (!this.calcTotalPrice) {
            this.calcPricePerPackage = '';
        }
    },

    updateCalcFromPackageCount() {
        this.calcResult = null;
        this.calcError = '';
        const count = parseFloat(this.calcPackageCount) || 0;
        const pricePerPkg = parseFloat(this.calcPricePerPackage) || 0;
        if (count > 0 && pricePerPkg > 0) {
            this.calcTotalPrice = Math.round(count * pricePerPkg * 100) / 100;
        }
    },

    // Toggle Smart Calculator
    toggleCalculator() {
        this.showCalculator = !this.showCalculator;
        if (this.showCalculator) {
            if (this.materialForm.unit_id) {
                this.calcSelectedUnitId = this.materialForm.unit_id;
            }
            if (this.materialForm.cost_per_unit > 0 && !this.calcPricePerPackage) {
                const content = parseFloat(this.calcContentPerPackage) || 1;
                this.calcPricePerPackage = Math.round(this.materialForm.cost_per_unit * content);
                const count = parseFloat(this.calcPackageCount) || 1;
                this.calcTotalPrice = Math.round(this.calcPricePerPackage * count);
            }
            this.calcResult = null;
            this.calcError = '';
        }
    },

    // Open Material Modal (Create)
    openCreateMaterialModal() {
        const defaultUnit = this.units.length > 0 ? this.units[0].id : '';
        this.materialForm = {
            id: null,
            name: '',
            unit_id: defaultUnit,
            stock: 0,
            min_stock: 0,
            cost_per_unit: 0
        };
        this.materialModalTitle = 'Tambah Bahan Baku Baru';
        this.materialErrors = {};
        this.materialFormError = '';
        this.showCalculator = false;
        this.calcPackageCount = 1;
        this.calcContentPerPackage = 1000;
        this.calcPricePerPackage = '';
        this.calcTotalPrice = '';
        this.calcSelectedUnitId = defaultUnit;
        this.calcResult = null;
        this.calcError = '';
        this.showMaterialModal = true;
    },

    // Open Material Modal (Edit)
    openEditMaterialModal(mat) {
        this.materialForm = {
            id: mat.id,
            name: mat.name,
            unit_id: mat.unit_id || (this.units[0]?.id ?? ''),
            stock: mat.stock,
            min_stock: mat.min_stock,
            cost_per_unit: mat.cost_per_unit
        };
        this.materialModalTitle = 'Edit Bahan: ' + mat.name;
        this.materialErrors = {};
        this.materialFormError = '';
        this.showCalculator = false;
        this.calcPackageCount = 1;
        this.calcContentPerPackage = 1000;
        this.calcPricePerPackage = '';
        this.calcTotalPrice = '';
        this.calcSelectedUnitId = this.materialForm.unit_id;
        this.calcResult = null;
        this.calcError = '';
        this.showMaterialModal = true;
    },

    // Hitung Konversi Pembelian Grosir/Kemasan
    calculateConversion() {
        this.calcError = '';
        const count = parseFloat(this.calcPackageCount) || 0;
        const content = parseFloat(this.calcContentPerPackage) || 0;
        let price = parseFloat(this.calcPricePerPackage) || 0;
        const totalPrice = parseFloat(this.calcTotalPrice) || 0;

        // Jika user hanya mengisi total belanja, hitung harga per kemasan
        if (price <= 0 && totalPrice > 0 && count > 0) {
            price = Math.round((totalPrice / count) * 100) / 100;
            this.calcPricePerPackage = price;
        }

        if (count <= 0) {
            this.calcError = 'Jumlah kemasan harus lebih dari 0.';
            return false;
        }

        if (content <= 0) {
            this.calcError = 'Isi per kemasan harus lebih dari 0.';
            return false;
        }

        if (!this.calcSelectedUnitId) {
            this.calcError = 'Pilih satuan dasar terlebih dahulu.';
            return false;
        }

        if (price <= 0) {
            this.calcError = 'Harga beli per kemasan atau total belanja wajib diisi (lebih dari 0) agar harga pokok per satuan dapat dihitung.';
            return false;
        }

        const selectedUnit = this.units.find(u => u.id == this.calcSelectedUnitId);
        const totalStock = Math.round((count * content) * 100) / 100;
        const costPerUnit = content > 0 ? Math.round((price / content) * 100) / 100 : 0;
        const totalCost = totalPrice > 0 ? totalPrice : Math.round((count * price) * 100) / 100;

        this.calcResult = {
            total_stock: totalStock,
            cost_per_unit: costPerUnit,
            total_cost: totalCost,
            unit_id: this.calcSelectedUnitId,
            unit_name: selectedUnit ? selectedUnit.name : '',
            unit_short: selectedUnit ? selectedUnit.short_name : ''
        };
        return true;
    },

    // Terapkan (Gunakan) Hasil Kalkulator ke Material Form
    applyCalculator() {
        if (!this.calcResult) {
            const success = this.calculateConversion();
            if (!success) return;
        }

        if (this.calcResult) {
            if (this.calcResult.unit_id) {
                this.materialForm.unit_id = this.calcResult.unit_id;
            }
            this.materialForm.stock = this.calcResult.total_stock;
            this.materialForm.cost_per_unit = this.calcResult.cost_per_unit;
            this.showCalculator = false;
            this.notify('Hasil perhitungan konversi berhasil diterapkan ke form!', 'info');
        }
    },

    // Submit Material Form
    async submitMaterial() {
        this.materialErrors = {};
        this.materialFormError = '';

        if (!this.materialForm.name.trim()) {
            this.materialErrors.name = ['Nama bahan baku wajib diisi.'];
            return;
        }
        if (!this.materialForm.unit_id) {
            this.materialErrors.unit_id = ['Pilih salah satu satuan pengukuran.'];
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveMaterial(
            this.materialForm.id,
            this.materialForm.name,
            parseInt(this.materialForm.unit_id),
            parseFloat(this.materialForm.stock) || 0,
            parseFloat(this.materialForm.min_stock) || 0,
            parseFloat(this.materialForm.cost_per_unit) || 0
        );
        this.isProcessing = false;

        if (res.success) {
            this.showMaterialModal = false;
            this.notify(res.message, 'success');
            // Refresh data from server
            $wire.$refresh();
        } else {
            this.materialErrors = res.errors || {};
            if (Object.keys(this.materialErrors).length === 0) {
                this.materialFormError = res.message;
            }
        }
    },

    // Confirm Delete Material
    confirmDeleteMaterial(mat) {
        this.deleteTarget = { id: mat.id, name: mat.name, recipes_count: mat.recipes_count };
        this.showDeleteModal = true;
    },

    // Execute Delete Material
    async executeDeleteMaterial() {
        if (!this.deleteTarget.id) return;
        this.isProcessing = true;
        const res = await $wire.deleteMaterial(this.deleteTarget.id);
        this.isProcessing = false;

        if (res.success) {
            this.showDeleteModal = false;
            this.notify(res.message, 'success');
            $wire.$refresh();
        } else {
            this.notify(res.message, 'error');
            this.showDeleteModal = false;
        }
    },

    // Opname Material Modal State
    showOpnameModal: false,
    opnameTarget: { id: null, name: '', stock: 0, unit_short: '', physical_stock: 0, reason: '', date: '' },

    openOpnameMaterialModal(mat) {
        this.opnameTarget = {
            id: mat.id,
            name: mat.name,
            stock: mat.stock,
            unit_short: mat.unit_short,
            physical_stock: mat.stock,
            reason: '',
            date: new Date().toISOString().slice(0, 10)
        };
        this.showOpnameModal = true;
    },

    async executeMaterialOpname() {
        if (!this.opnameTarget.id) return;
        this.isProcessing = true;
        const res = await $wire.adjustMaterialStock(
            this.opnameTarget.id,
            parseFloat(this.opnameTarget.physical_stock) || 0,
            this.opnameTarget.reason,
            this.opnameTarget.date
        );
        this.isProcessing = false;

        if (res.success) {
            const m = this.materials.find(item => item.id === this.opnameTarget.id);
            if (m) m.stock = parseFloat(this.opnameTarget.physical_stock) || 0;
            this.showOpnameModal = false;
            this.notify(res.message, 'success');
            $wire.$refresh();
        } else {
            this.notify(res.message, 'error');
        }
    },

    // Open Recipe Modal
    openRecipeModal(product) {
        this.selectedProduct = product;
        this.recipeRows = [];
        this.recipeError = '';

        if (product.recipes && product.recipes.length > 0) {
            this.recipeRows = product.recipes.map(r => ({
                raw_material_id: r.raw_material_id,
                quantity_needed: r.quantity_needed
            }));
        } else {
            const firstMat = this.materials.length > 0 ? this.materials[0].id : '';
            this.recipeRows = [{ raw_material_id: firstMat, quantity_needed: 10 }];
        }

        this.showRecipeModal = true;
    },

    // Add row to Recipe Form
    addRecipeRow() {
        const firstMat = this.materials.length > 0 ? this.materials[0].id : '';
        this.recipeRows.push({ raw_material_id: firstMat, quantity_needed: 0 });
    },

    // Remove row from Recipe Form
    removeRecipeRow(index) {
        this.recipeRows.splice(index, 1);
    },

    // Realtime calculated Recipe Modal Total Material Cost
    get modalRecipeCost() {
        let total = 0;
        this.recipeRows.forEach(row => {
            const mat = this.materials.find(m => m.id == row.raw_material_id);
            const qty = parseFloat(row.quantity_needed) || 0;
            if (mat && qty > 0) {
                total += qty * mat.cost_per_unit;
            }
        });
        return Math.round(total * 100) / 100;
    },

    // Realtime calculated Gross Margin %
    get modalGrossMargin() {
        if (!this.selectedProduct || this.selectedProduct.consignment_price <= 0) return 0;
        const price = this.selectedProduct.consignment_price;
        const cost = this.modalRecipeCost;
        return Math.round(((price - cost) / price) * 1000) / 10;
    },

    // Submit Recipe Form
    async submitRecipe() {
        this.recipeError = '';
        if (!this.selectedProduct) return;

        const validRows = this.recipeRows.filter(r => r.raw_material_id && parseFloat(r.quantity_needed) > 0);
        if (validRows.length === 0) {
            this.recipeError = 'Minimal harus ada 1 bahan baku dengan takaran lebih dari 0.';
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveRecipe(this.selectedProduct.id, validRows);
        this.isProcessing = false;

        if (res.success) {
            this.showRecipeModal = false;
            this.notify(res.message, 'success');
            $wire.$refresh();
        } else {
            this.recipeError = res.message;
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
                <span>Master Data</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Bahan Baku &amp; Resep</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Master Bahan Baku & Resep Produk (BOM)
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola stok bahan baku, batas minimum peringatan, dan formula Bill of Materials (BOM) per produk.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @can('bahan-baku-create')
                <button type="button" @click="openCreateMaterialModal()"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
                    <i class="ti ti-plus text-lg"></i>
                    <span>Tambah Bahan</span>
                </button>
            @endcan
        </div>
    </div>

    <!-- Tab Navigation Bar -->
    <div class="flex border-b border-brand-border gap-4 sm:gap-8 overflow-x-auto">
        <!-- 1. Tab Master Bahan Baku -->
        <button type="button" @click="activeTab = 'materials'"
            :class="activeTab === 'materials' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' :
                'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
            class="flex items-center gap-1.5 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
            <span>Master Bahan Baku</span>
            <span class="text-xs text-brand-warm-gray">(<span x-text="materials.length"></span>)</span>
        </button>

        <!-- 2. Tab Resep Produk (BOM) -->
        <button type="button" @click="activeTab = 'recipes'"
            :class="activeTab === 'recipes' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' :
                'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
            class="flex items-center gap-1.5 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
            <span>Resep Produk (BOM)</span>
            <span class="text-xs text-brand-warm-gray">(<span x-text="products.length"></span>)</span>
        </button>
    </div>

    <!-- TAB 1: MASTER BAHAN BAKU -->
    <div x-show="activeTab === 'materials'" class="space-y-4">

        <!-- Action & Filter Bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                    <input type="text" x-model="search" placeholder="Cari nama bahan baku..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
                </div>

                <!-- Stock Status Filter -->
                <div class="shrink-0">
                    <select x-model="statusFilter"
                        class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                        <option value="all">Semua Status Stok</option>
                        <option value="safe">Stok Aman</option>
                        <option value="warning">Stok Menipis</option>
                        <option value="danger">Stok Habis</option>
                    </select>
                </div>
            </div>

            <div class="text-xs sm:text-sm text-brand-warm-gray font-medium self-center">
                Menampilkan <span class="font-bold text-brand-espresso" x-text="filteredMaterials.length"></span> bahan
                baku
            </div>
        </div>

        <!-- Materials Table -->
        <div class="overflow-x-auto bg-white rounded-xl border border-brand-border shadow-xs">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6 whitespace-nowrap">Bahan Baku</th>
                        <th class="py-3.5 px-6 whitespace-nowrap">Satuan Baku</th>
                        <th class="py-3.5 px-6 whitespace-nowrap">Stok Saat Ini</th>
                        <th class="py-3.5 px-6 whitespace-nowrap">Batas Minimum</th>
                        <th class="py-3.5 px-6 whitespace-nowrap">Harga Beli Satuan</th>
                        <th class="py-3.5 px-6 whitespace-nowrap">Status Stok</th>
                        <th class="py-3.5 px-6 whitespace-nowrap">Resep</th>
                        <th class="py-3.5 px-6 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border/60 text-sm">
                    <template x-for="mat in filteredMaterials" :key="mat.id">
                        <tr class="hover:bg-neutral-50/50 transition">

                            <!-- Nama Bahan (No icon background box) -->
                            <td class="py-4 px-6 font-bold text-brand-espresso">
                                <span x-text="mat.name"></span>
                            </td>

                            <!-- Satuan Pengukuran (No badge pill) -->
                            <td class="py-4 px-6">
                                <span class="font-mono font-bold text-brand-espresso text-sm" x-text="mat.unit_short">
                                </span>
                            </td>

                            <!-- Stok Saat Ini -->
                            <td class="py-4 px-6 font-mono font-bold text-base text-brand-espresso">
                                <span x-text="Number(mat.stock).toLocaleString('id-ID')"></span>
                                <span class="text-xs text-brand-warm-gray font-normal" x-text="mat.unit_short"></span>
                            </td>

                            <!-- Batas Minimum -->
                            <td class="py-4 px-6 font-mono text-sm text-brand-warm-gray">
                                <span x-text="Number(mat.min_stock).toLocaleString('id-ID')"></span>
                                <span class="text-xs" x-text="mat.unit_short"></span>
                            </td>

                            <!-- Harga Beli -->
                            <td class="py-4 px-6 font-semibold text-brand-espresso whitespace-nowrap">
                                <span
                                    x-text="'Rp ' + Number(mat.cost_per_unit).toLocaleString('id-ID', { minimumFractionDigits: 2 })"></span>
                                <span class="text-xs text-brand-warm-gray">/ <span
                                        x-text="mat.unit_short"></span></span>
                            </td>

                            <!-- Status Stok (Clean text, no badge pill) -->
                            <td class="py-4 px-6">
                                <span class="text-sm font-semibold text-brand-espresso"
                                    x-text="mat.stock_status === 'safe' ? 'Aman' : (mat.stock_status === 'warning' ? 'Menipis' : 'Habis')">
                                </span>
                            </td>

                            <!-- Resep Terkait (Clean text, no badge pill) -->
                            <td class="py-4 px-6">
                                <span class="text-sm text-brand-warm-gray" x-text="mat.recipes_count + ' Produk'">
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center gap-1">
                                    @can('bahan-baku-edit')
                                        <button type="button" @click="openOpnameMaterialModal(mat)"
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-amber-700 hover:bg-brand-soft-cream/60 transition cursor-pointer"
                                            title="Stock Opname / Sesuaikan Fisik">
                                            <i class="ti ti-clipboard-check text-lg"></i>
                                        </button>
                                        <button type="button" @click="openEditMaterialModal(mat)"
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer"
                                            title="Ubah Data Bahan">
                                            <i class="ti ti-edit text-lg"></i>
                                        </button>
                                    @endcan

                                    @can('bahan-baku-delete')
                                        <button type="button" @click="confirmDeleteMaterial(mat)"
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-red-600 hover:bg-red-50 transition cursor-pointer"
                                            title="Hapus Bahan">
                                            <i class="ti ti-trash text-lg"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>

                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="filteredMaterials.length === 0">
                        <td colspan="8" class="py-12 text-center text-brand-warm-gray">
                            <div class="max-w-sm mx-auto space-y-2">
                                <i class="ti ti-box-off text-3xl text-brand-warm-gray"></i>
                                <p class="font-bold text-brand-espresso text-base">Tidak ada bahan baku ditemukan</p>
                                <p class="text-xs text-brand-warm-gray">Coba ubah kata kunci pencarian atau filter status untuk menemukan bahan.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- TAB 2: RESEP PRODUK (BOM) -->
    <div x-show="activeTab === 'recipes'" class="space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <template x-for="prod in products" :key="prod.id">
                <div
                    class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-5 flex flex-col justify-between">

                    <!-- Card Header -->
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="size-12 rounded-xl bg-brand-soft-cream/60 text-brand-primary flex items-center justify-center font-bold text-lg shrink-0">
                                    <i class="ti ti-cookie text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-brand-espresso" x-text="prod.name"></h3>
                                    <p class="text-xs text-brand-warm-gray">
                                        Satuan Kemasan: <span class="font-semibold text-brand-espresso"
                                            x-text="prod.unit_name"></span>
                                    </p>
                                </div>
                            </div>

                            @can('resep-manage')
                                <button type="button" @click="openRecipeModal(prod)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-brand-border text-xs font-bold text-brand-espresso hover:bg-brand-soft-cream/60 transition cursor-pointer shrink-0">
                                    <i class="ti ti-settings text-sm"></i>
                                    <span>Atur Resep</span>
                                </button>
                            @endcan
                        </div>

                        <!-- Price & BOM Cost Summary Cards -->
                        <div
                            class="grid grid-cols-3 gap-3 p-3.5 bg-neutral-50/80 rounded-xl border border-neutral-100 text-center">
                            <div>
                                <p class="text-xs text-brand-warm-gray">Harga Titip</p>
                                <p class="text-sm sm:text-base font-extrabold text-brand-espresso mt-0.5 whitespace-nowrap"
                                    x-text="prod.consignment_formatted"></p>
                            </div>
                            <div class="border-x border-neutral-200">
                                <p class="text-xs text-brand-warm-gray">Biaya Bahan (HPP)</p>
                                <p class="text-sm sm:text-base font-extrabold text-brand-primary mt-0.5 whitespace-nowrap"
                                    x-text="prod.material_cost_formatted"></p>
                            </div>
                            <div>
                                <p class="text-xs text-brand-warm-gray">Gross Margin</p>
                                <p class="text-sm sm:text-base font-black mt-0.5 text-emerald-600 whitespace-nowrap"
                                    x-text="prod.gross_margin + '%'"></p>
                            </div>
                        </div>

                        <!-- Ingredients Table -->
                        <div class="space-y-2">
                            <p class="text-xs font-bold uppercase tracking-wider text-brand-warm-gray">Komposisi
                                Takaran (per 1 pcs produk):</p>
                            <div class="overflow-hidden rounded-xl border border-brand-border/60">
                                <table class="w-full text-left text-xs">
                                    <thead
                                        class="bg-neutral-50 border-b border-brand-border/60 text-brand-espresso font-bold">
                                        <tr>
                                            <th class="py-2.5 px-3 whitespace-nowrap">Bahan Baku</th>
                                            <th class="py-2.5 px-3 text-right whitespace-nowrap">Takaran</th>
                                            <th class="py-2.5 px-3 text-right whitespace-nowrap">Subtotal Biaya</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-brand-border/40">
                                        <template x-for="item in prod.recipes" :key="item.id">
                                            <tr class="hover:bg-neutral-50/50">
                                                <td class="py-2 px-3 font-semibold text-brand-espresso"
                                                    x-text="item.material_name"></td>
                                                <td class="py-2 px-3 text-right font-mono"
                                                    x-text="item.quantity_needed + ' ' + item.unit_short"></td>
                                                <td class="py-2 px-3 text-right font-semibold text-brand-espresso whitespace-nowrap"
                                                    x-text="item.subtotal_formatted"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="!prod.recipes || prod.recipes.length === 0">
                                            <td colspan="3" class="py-4 text-center text-brand-warm-gray italic">
                                                Belum ada formula bahan baku untuk produk ini.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                </div>
            </template>
        </div>

    </div>

    <!-- MODAL 1: TAMBAH / EDIT BAHAN BAKU -->
    <div x-cloak x-show="showMaterialModal" class="fixed inset-0 z-50 overflow-y-auto"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity"
            @click="showMaterialModal = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-xl border border-brand-border p-6 sm:p-8 text-left"
                @click.outside="showMaterialModal = false">

                <div class="flex items-center justify-between pb-4 border-b border-brand-border/60">
                    <h3 class="text-xl font-extrabold text-brand-espresso" x-text="materialModalTitle"></h3>
                    <button type="button" @click="showMaterialModal = false"
                        class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <form @submit.prevent="submitMaterial()" class="space-y-4 pt-4">

                    <div x-show="materialFormError"
                        class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs font-medium text-red-700"
                        x-text="materialFormError"></div>

                    <!-- Nama Bahan Baku -->
                    <div>
                        <label class="block text-sm font-bold text-brand-espresso mb-1">
                            Nama Bahan Baku <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="materialForm.name"
                            placeholder="Misal: Wijen Putih, Gula Pasir, Kacang Tanah"
                            class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                        <p x-show="materialErrors.name" class="text-xs text-red-600 mt-1"
                            x-text="materialErrors.name?.[0]"></p>
                    </div>

                    <!-- DYNAMIC Satuan Pengukuran SELECT -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-sm font-bold text-brand-espresso">
                                Satuan Pengukuran Baku <span class="text-red-500">*</span>
                            </label>
                        </div>
                        <select x-model="materialForm.unit_id"
                            class="select select-lg w-full bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                            <option value="">-- Pilih Satuan Baku --</option>
                            <template x-for="u in units" :key="u.id">
                                <option :value="u.id" x-text="u.name + ' (' + u.short_name + ')'"></option>
                            </template>
                        </select>
                        <p x-show="materialErrors.unit_id" class="text-xs text-red-600 mt-1"
                            x-text="materialErrors.unit_id?.[0]"></p>
                    </div>

                    <!-- Stok & Batas Minimal -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-brand-espresso mb-1">
                                Stok Saat Ini <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" x-model="materialForm.stock"
                                class="w-full px-4 py-2.5 font-mono bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                            <p x-show="materialErrors.stock" class="text-xs text-red-600 mt-1"
                                x-text="materialErrors.stock?.[0]"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-brand-espresso mb-1">
                                Batas Minimum Stok <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" x-model="materialForm.min_stock"
                                class="w-full px-4 py-2.5 font-mono bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                            <p x-show="materialErrors.min_stock" class="text-xs text-red-600 mt-1"
                                x-text="materialErrors.min_stock?.[0]"></p>
                        </div>
                    </div>

                    <!-- Harga Beli per Satuan -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-sm font-bold text-brand-espresso">
                                Harga Beli per Satuan (Rp) <span class="text-red-500">*</span>
                            </label>
                            <!-- Smart Calculator Toggle -->
                            <button type="button" @click="toggleCalculator()"
                                class="text-xs font-bold text-brand-primary hover:underline cursor-pointer flex items-center gap-1">
                                <i class="ti ti-calculator text-sm"></i>
                                <span
                                    x-text="showCalculator ? 'Tutup Kalkulator' : 'Hitung dari Pembelian Kemasan'"></span>
                            </button>
                        </div>
                        <div class="relative">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-brand-warm-gray">Rp</span>
                            <input type="number" step="0.01" min="0" x-model="materialForm.cost_per_unit"
                                class="w-full pl-12 pr-4 py-2.5 font-mono font-bold bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:border-brand-primary">
                        </div>
                        <p x-show="materialErrors.cost_per_unit" class="text-xs text-red-600 mt-1"
                            x-text="materialErrors.cost_per_unit?.[0]"></p>
                    </div>

                    <!-- Smart Purchase Converter Card -->
                    <div x-show="showCalculator"
                        class="p-4 bg-brand-soft-cream/40 rounded-xl border border-brand-border/60 space-y-3">
                        <div
                            class="flex items-center gap-2 text-xs font-bold text-brand-primary uppercase tracking-wider">
                            <i class="ti ti-calculator"></i>
                            <span>Kalkulator Konversi Pembelian Grosir / Kemasan</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div>
                                <label class="block font-semibold text-brand-espresso mb-1">
                                    Beli Berapa Kemasan? <span class="text-red-500">*</span>
                                </label>
                                <input type="number" min="1" x-model="calcPackageCount"
                                    @input="updateCalcFromPackageCount()"
                                    @keydown.enter.prevent="calculateConversion()"
                                    placeholder="Misal: 20"
                                    class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block font-semibold text-brand-espresso mb-1">
                                    Isi per Kemasan <span class="text-red-500">*</span>
                                </label>
                                <input type="number" min="1" x-model="calcContentPerPackage"
                                    @input="calcResult = null; calcError = ''"
                                    @keydown.enter.prevent="calculateConversion()"
                                    placeholder="Misal: 1000"
                                    class="w-full px-3 py-2 bg-white border border-brand-border rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="block font-semibold text-brand-espresso mb-1">
                                    Satuan Dasar <span class="text-red-500">*</span>
                                </label>
                                <select x-model="calcSelectedUnitId"
                                    @change="calcResult = null; calcError = ''"
                                    class="select w-full bg-white border border-brand-border rounded-lg text-xs capitalize">
                                    <template x-for="u in units" :key="u.id">
                                        <option :value="u.id" x-text="u.name + ' (' + u.short_name + ')'">
                                        </option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Input Harga Beli (Bisa isi Harga per Kemasan atau Total Belanja Nota) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div>
                                <label class="block font-semibold text-brand-espresso mb-1">
                                    Harga Beli per Kemasan (Rp) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-brand-warm-gray">Rp</span>
                                    <input type="number" min="0" x-model="calcPricePerPackage"
                                        @input="updateCalcFromPricePerPackage()"
                                        @keydown.enter.prevent="calculateConversion()"
                                        placeholder="Misal: 25000"
                                        class="w-full pl-9 pr-3 py-2 bg-white border border-brand-border rounded-lg text-sm font-bold text-brand-espresso">
                                </div>
                                <p class="text-[11px] text-brand-warm-gray mt-1">Harga 1 kemasan / sak / dus</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-brand-espresso mb-1">
                                    Atau Total Belanja Semua Kemasan (Rp)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-brand-warm-gray">Rp</span>
                                    <input type="number" min="0" x-model="calcTotalPrice"
                                        @input="updateCalcFromTotalPrice()"
                                        @keydown.enter.prevent="calculateConversion()"
                                        placeholder="Misal: 500000"
                                        class="w-full pl-9 pr-3 py-2 bg-white border border-brand-border rounded-lg text-sm font-bold text-brand-espresso">
                                </div>
                                <p class="text-[11px] text-brand-warm-gray mt-1">Total nota (terisi otomatis)</p>
                            </div>
                        </div>

                        <!-- Pesan Kesalahan jika validasi gagal -->
                        <div x-show="calcError" x-cloak class="p-2.5 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600 font-medium" x-text="calcError"></div>

                        <!-- Hasil Perhitungan (Tampil setelah klik Hitung) -->
                        <div x-show="calcResult" x-cloak class="p-3 bg-white rounded-xl border border-brand-border/80 space-y-2">
                            <div class="flex items-center justify-between text-xs border-b border-neutral-100 pb-2">
                                <span class="font-bold text-brand-espresso">Hasil Perhitungan Konversi</span>
                                <span class="text-emerald-600 font-semibold flex items-center gap-1 text-[11px]">
                                    <i class="ti ti-check text-xs"></i> Siap Diterapkan
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                <div class="bg-neutral-50 p-2.5 rounded-lg border border-neutral-100">
                                    <span class="text-brand-warm-gray text-[11px] block mb-0.5">Total Stok Didapat</span>
                                    <span class="font-bold text-brand-espresso text-sm font-mono"
                                        x-text="calcResult ? (calcResult.total_stock.toLocaleString('id-ID') + ' ' + calcResult.unit_short) : ''"></span>
                                    <span class="text-[10px] text-brand-warm-gray block"
                                        x-text="calcResult ? ('(' + calcPackageCount + ' kemasan × ' + calcContentPerPackage + ' ' + calcResult.unit_short + ')') : ''"></span>
                                </div>
                                <div class="bg-neutral-50 p-2.5 rounded-lg border border-neutral-100">
                                    <span class="text-brand-warm-gray text-[11px] block mb-0.5">Harga Pokok per Satuan</span>
                                    <span class="font-bold text-brand-primary text-sm font-mono"
                                        x-text="calcResult ? ('Rp ' + calcResult.cost_per_unit.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })) : ''"></span>
                                    <span class="text-[10px] text-brand-warm-gray block"
                                        x-text="calcResult ? ('per ' + calcResult.unit_short) : ''"></span>
                                </div>
                                <div class="bg-neutral-50 p-2.5 rounded-lg border border-neutral-100">
                                    <span class="text-brand-warm-gray text-[11px] block mb-0.5">Total Belanja</span>
                                    <span class="font-bold text-brand-espresso text-sm font-mono"
                                        x-text="calcResult ? ('Rp ' + calcResult.total_cost.toLocaleString('id-ID')) : ''"></span>
                                    <span class="text-[10px] text-brand-warm-gray block"
                                        x-text="calcResult ? ('(' + calcPackageCount + ' × Rp ' + (parseFloat(calcPricePerPackage) || 0).toLocaleString('id-ID') + ')') : ''"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi: Hitung & Gunakan -->
                        <div class="flex items-center gap-2 pt-1">
                            <button type="button" @click="calculateConversion()"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-brand-espresso text-white rounded-lg text-xs font-bold hover:bg-neutral-800 transition cursor-pointer">
                                <i class="ti ti-calculator text-sm"></i>
                                <span>Hitung</span>
                            </button>
                            <button type="button" @click="applyCalculator()" :disabled="!calcResult"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-brand-primary text-white rounded-lg text-xs font-bold hover:bg-brand-primary-hover transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                                <i class="ti ti-check text-sm"></i>
                                <span>Gunakan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-brand-border/60 flex items-center justify-end gap-3">
                        <button type="button" @click="showMaterialModal = false" :disabled="isProcessing"
                            class="px-5 py-2.5 rounded-xl border border-brand-border text-sm font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                            <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                            <span>Simpan Bahan</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- MODAL 2: ATUR FORMULA RESEP PRODUK (BOM) -->
    <div x-cloak x-show="showRecipeModal" class="fixed inset-0 z-50 overflow-y-auto"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity"
            @click="showRecipeModal = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-brand-border p-6 sm:p-8 text-left"
                @click.outside="showRecipeModal = false">

                <div class="flex items-center justify-between pb-4 border-b border-brand-border/60">
                    <div>
                        <h3 class="text-xl font-extrabold text-brand-espresso">
                            Atur Formula Resep: <span class="text-brand-primary"
                                x-text="selectedProduct ? selectedProduct.name : ''"></span>
                        </h3>
                        <p class="text-xs text-brand-warm-gray mt-0.5">
                            Tentukan takaran bahan baku yang dibutuhkan untuk memproduksi 1 unit produk jadi.
                        </p>
                    </div>
                    <button type="button" @click="showRecipeModal = false"
                        class="text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>

                <div x-show="recipeError"
                    class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs font-medium text-red-700"
                    x-text="recipeError"></div>

                <div class="space-y-4 pt-4">

                    <!-- Recipe Rows Container -->
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                        <template x-for="(row, idx) in recipeRows" :key="idx">
                            <div
                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-3 bg-neutral-50 rounded-xl border border-neutral-200/70">

                                <!-- DYNAMIC Raw Material Select -->
                                <div class="flex-1">
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Pilih Bahan
                                        Baku</label>
                                    <select x-model="row.raw_material_id"
                                        class="select select-sm w-full bg-white border border-brand-border rounded-lg text-sm text-brand-espresso">
                                        <option value="">-- Pilih Bahan Baku --</option>
                                        <template x-for="mat in materials" :key="mat.id">
                                            <option :value="mat.id"
                                                x-text="mat.name + ' (Rp ' + Number(mat.cost_per_unit).toLocaleString('id-ID') + '/' + mat.unit_short + ')'">
                                            </option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Takaran Input -->
                                <div class="w-full sm:w-44">
                                    <label class="block text-xs font-bold text-brand-espresso mb-1">Takaran
                                        Bahan</label>
                                    <div
                                        class="flex items-center rounded-lg border border-brand-border bg-white overflow-hidden focus-within:ring-2 focus-within:ring-brand-primary/20 focus-within:border-brand-primary transition">
                                        <input type="number" step="0.0001" min="0"
                                            x-model="row.quantity_needed" placeholder="0"
                                            class="w-full px-2.5 py-1.5 font-mono text-sm text-brand-espresso bg-transparent border-0 focus:outline-none focus:ring-0">
                                        <div class="px-2.5 py-1.5 bg-brand-soft-cream/60 border-l border-brand-border text-xs font-mono font-bold text-brand-warm-gray shrink-0 select-none min-w-11 text-center"
                                            x-text="materials.find(m => m.id == row.raw_material_id)?.unit_short || '-'">
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtotal Preview -->
                                <div class="w-full sm:w-32 text-right">
                                    <label class="block text-xs text-brand-warm-gray mb-1">Subtotal Biaya</label>
                                    <span class="text-xs font-bold text-brand-espresso whitespace-nowrap"
                                        x-text="'Rp ' + (Math.round((parseFloat(row.quantity_needed) || 0) * (materials.find(m => m.id == row.raw_material_id)?.cost_per_unit || 0) * 100) / 100).toLocaleString('id-ID', { minimumFractionDigits: 2 })">
                                    </span>
                                </div>

                                <!-- Remove Button -->
                                <div class="self-end sm:self-center pt-2 sm:pt-4">
                                    <button type="button" @click="removeRecipeRow(idx)"
                                        class="size-8 rounded-lg flex items-center justify-center text-red-500 hover:bg-red-50 transition cursor-pointer"
                                        title="Hapus baris ini">
                                        <i class="ti ti-trash text-base"></i>
                                    </button>
                                </div>

                            </div>
                        </template>
                    </div>

                    <!-- Add Row Button -->
                    <button type="button" @click="addRecipeRow()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 border border-dashed border-brand-primary text-brand-primary hover:bg-brand-soft-cream/60 rounded-xl text-xs font-bold transition cursor-pointer">
                        <i class="ti ti-plus text-sm"></i>
                        <span>Tambah Baris Bahan Baku</span>
                    </button>

                    <!-- Realtime Summary Box -->
                    <div
                        class="p-4 bg-brand-soft-cream/50 rounded-xl border border-brand-border flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                        <div>
                            <p class="text-xs text-brand-warm-gray">Harga Titip (Konsinyasi)</p>
                            <p class="text-lg font-bold text-brand-espresso"
                                x-text="selectedProduct ? selectedProduct.consignment_formatted : 'Rp 0'"></p>
                        </div>
                        <div>
                            <p class="text-xs text-brand-warm-gray">Total Estimasi HPP Bahan</p>
                            <p class="text-lg font-extrabold text-brand-primary"
                                x-text="'Rp ' + modalRecipeCost.toLocaleString('id-ID', { minimumFractionDigits: 2 })">
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-brand-warm-gray">Estimasi Margin Laba</p>
                            <p class="text-lg font-black text-emerald-600" x-text="modalGrossMargin + '%'"></p>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-brand-border/60 flex items-center justify-end gap-3">
                        <button type="button" @click="showRecipeModal = false" :disabled="isProcessing"
                            class="px-5 py-2.5 rounded-xl border border-brand-border text-sm font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="submitRecipe()" :disabled="isProcessing"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                            <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                            <span>Simpan Formula Resep</span>
                        </button>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- MODAL 3: KONFIRMASI HAPUS BAHAN BAKU -->
    <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs transition-opacity"
            @click="showDeleteModal = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-brand-border p-6 text-left"
                @click.outside="showDeleteModal = false">

                <div class="flex items-center gap-4">
                    <div class="size-12 rounded-xl bg-red-50 flex items-center justify-center text-red-600 shrink-0">
                        <i class="ti ti-alert-triangle text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-brand-espresso">Konfirmasi Hapus Bahan</h3>
                        <p class="text-sm text-brand-warm-gray mt-0.5">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-neutral-50 rounded-xl border border-neutral-100 text-sm">
                    <p class="text-brand-espresso">
                        Apakah Anda yakin ingin menghapus data bahan baku:
                        <strong class="font-bold text-red-600" x-text="deleteTarget.name"></strong>?
                    </p>
                    <template x-if="deleteTarget.recipes_count > 0">
                        <p class="mt-2 text-xs font-semibold text-amber-600">
                            Peringatan: Bahan ini sedang terhubung ke <span x-text="deleteTarget.recipes_count"></span>
                            resep produk.
                        </p>
                    </template>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="showDeleteModal = false" :disabled="isProcessing"
                        class="px-4 py-2.5 rounded-xl border border-brand-border text-sm font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="executeDeleteMaterial()" :disabled="isProcessing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl transition cursor-pointer shadow-xs">
                        <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                        <span>Hapus Sekarang</span>
                    </button>
                </div>

    <!-- Modal Stock Opname Bahan Baku -->
    <div x-cloak x-show="showOpnameModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div x-show="showOpnameModal" x-transition.opacity.duration.200ms
                class="fixed inset-0 bg-neutral-900/40 backdrop-blur-xs" @click="showOpnameModal = false"></div>

            <div x-show="showOpnameModal" x-transition.scale.duration.200ms
                class="relative bg-white rounded-2xl max-w-lg w-full p-6 text-left shadow-xl border border-brand-border space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-brand-border">
                    <div>
                        <h3 class="text-base font-bold text-brand-espresso">Stock Opname Bahan Baku</h3>
                        <p class="text-xs text-brand-warm-gray" x-text="opnameTarget.name"></p>
                    </div>
                    <button type="button" @click="showOpnameModal = false" class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        <i class="ti ti-x text-lg"></i>
                    </button>
                </div>

                <div class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl space-y-2">
                    <div class="flex justify-between text-xs text-amber-900 font-medium">
                        <span>Stok Sistem Saat Ini:</span>
                        <span class="font-mono font-bold" x-text="opnameTarget.stock + ' ' + opnameTarget.unit_short"></span>
                    </div>
                    <div class="flex justify-between text-xs text-amber-900 font-medium">
                        <span>Stok Fisik Dihitung:</span>
                        <span class="font-mono font-bold" x-text="(opnameTarget.physical_stock || 0) + ' ' + opnameTarget.unit_short"></span>
                    </div>
                    <div class="border-t border-amber-200/80 pt-1.5 flex justify-between text-sm font-bold"
                        :class="(opnameTarget.physical_stock - opnameTarget.stock) < 0 ? 'text-red-700' : ((opnameTarget.physical_stock - opnameTarget.stock) > 0 ? 'text-emerald-700' : 'text-brand-warm-gray')">
                        <span>Selisih:</span>
                        <span class="font-mono" x-text="((opnameTarget.physical_stock - opnameTarget.stock) > 0 ? '+' : '') + Math.round((opnameTarget.physical_stock - opnameTarget.stock) * 100) / 100 + ' ' + opnameTarget.unit_short"></span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-brand-espresso mb-1">
                                Tanggal Opname <span class="text-red-500">*</span>
                            </label>
                            <input type="date" x-model="opnameTarget.date"
                                class="w-full px-3.5 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-medium text-brand-espresso focus:outline-none focus:border-brand-primary" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-brand-espresso mb-1">
                                Stok Fisik Riil <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="any" min="0" x-model="opnameTarget.physical_stock"
                                class="w-full px-3.5 py-2.5 bg-white border border-brand-border rounded-xl text-sm font-mono font-bold text-brand-espresso focus:outline-none focus:border-brand-primary" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-brand-espresso mb-1">
                            Alasan / Keterangan Penyesuaian
                        </label>
                        <input type="text" x-model="opnameTarget.reason"
                            placeholder="Contoh: Selisih timbangan dapur, bahan tercecer/rusak, dll"
                            class="w-full px-3.5 py-2.5 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-brand-border">
                    <button type="button" @click="showOpnameModal = false" :disabled="isProcessing"
                        class="px-4 py-2 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="executeMaterialOpname()" :disabled="isProcessing"
                        class="px-5 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-primary/90 shadow-xs transition cursor-pointer disabled:opacity-50">
                        <span x-show="!isProcessing">Simpan & Posting Jurnal</span>
                        <span x-show="isProcessing">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
