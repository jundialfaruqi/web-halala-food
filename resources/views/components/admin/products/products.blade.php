<div x-data="{
    activeTab: (function() {
        const hash = window.location.hash.replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        if (['products', 'recipes', 'raw-materials', 'categories'].includes(hash)) return hash;
        if (['products', 'recipes', 'raw-materials', 'categories'].includes(queryTab)) return queryTab;
        return 'products';
    })(),

    // Search and Filter States
    productSearch: '',
    productCategoryFilter: 'all',
    recipeSearch: '',
    materialSearch: '',
    materialCategoryFilter: 'all',
    categorySearch: '',
    isProcessing: false,

    init() {
        this.$watch('activeTab', (tab) => {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.replaceState(null, '', url.toString());
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (['products', 'recipes', 'raw-materials', 'categories'].includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });
    },

    // Datasets from Backend
    categories: {{ \Illuminate\Support\Js::from($categories) }},
    products: {{ \Illuminate\Support\Js::from($products) }},
    variants: {{ \Illuminate\Support\Js::from($variants) }},
    rawMaterials: {{ \Illuminate\Support\Js::from($rawMaterials) }},
    recipes: {{ \Illuminate\Support\Js::from($recipes) }},

    // Product Modal State
    showProductModal: false,
    productModalTitle: 'Tambah Produk Baru',
    productForm: { id: null, category_id: '', name: '', code: '', description: '', is_active: true, variants: [] },
    productErrors: {},
    productFormError: '',

    // Recipe Modal State
    showRecipeModal: false,
    recipeModalTitle: 'Tambah Formula Resep',
    recipeForm: { id: null, product_variant_id: '', name: '', batch_output_qty: 1, labor_cost: 0, overhead_cost: 0, notes: '', is_active: true, items: [] },
    recipeErrors: {},
    recipeFormError: '',

    // Raw Material Modal State
    showRawMaterialModal: false,
    materialModalTitle: 'Tambah Bahan Baku',
    materialForm: { id: null, code: '', name: '', category: 'ingredient', unit: 'gram', stock_qty: 0, min_stock_alert: 0, average_cost: 0, notes: '', is_active: true },
    materialErrors: {},
    materialFormError: '',

    // Category Modal State
    showCategoryModal: false,
    categoryModalTitle: 'Tambah Kategori Baru',
    categoryForm: { id: null, name: '', description: '', is_active: true },
    categoryErrors: {},
    categoryFormError: '',

    // Delete Modal State
    showDeleteModal: false,
    deleteTarget: { type: '', id: null, name: '', description: '' },

    // Toast Notification Dispatcher
    notify(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    // --- Product Methods ---
    openCreateProductModal() {
        this.productForm = {
            id: null,
            category_id: this.categories[0]?.id || '',
            name: '',
            code: '',
            description: '',
            is_active: true,
            variants: [
                { id: null, name: 'Kemasan Pouch 200g', packaging_type: 'pouch', pcs_per_package: 10, weight_grams: 200, barcode: '', sku_code: '', wholesale_price: 18000, retail_price: 22000, stock_qty: 0, min_stock_alert: 10 }
            ]
        };
        this.productModalTitle = 'Tambah Produk Baru';
        this.productErrors = {};
        this.productFormError = '';
        this.showProductModal = true;
    },
    openEditProductById(id) {
        const prod = this.products.find(p => p.id === id);
        if (!prod) return;
        this.productForm = {
            id: prod.id,
            category_id: prod.category_id,
            name: prod.name,
            code: prod.code,
            description: prod.description || '',
            is_active: prod.is_active,
            variants: prod.variants ? prod.variants.map(v => ({
                id: v.id,
                name: v.name,
                packaging_type: v.packaging_type,
                pcs_per_package: v.pcs_per_package,
                weight_grams: v.weight_grams,
                barcode: v.barcode || '',
                sku_code: v.sku_code || '',
                wholesale_price: v.wholesale_price,
                retail_price: v.retail_price,
                stock_qty: v.stock_qty,
                min_stock_alert: v.min_stock_alert
            })) : []
        };
        this.productModalTitle = 'Edit Produk: ' + prod.name;
        this.productErrors = {};
        this.productFormError = '';
        this.showProductModal = true;
    },
    addVariantRow() {
        this.productForm.variants.push({
            id: null,
            name: '',
            packaging_type: 'pouch',
            pcs_per_package: 1,
            weight_grams: null,
            barcode: '',
            sku_code: '',
            wholesale_price: 0,
            retail_price: 0,
            stock_qty: 0,
            min_stock_alert: 10
        });
    },
    removeVariantRow(idx) {
        if (this.productForm.variants.length > 1) {
            this.productForm.variants.splice(idx, 1);
        }
    },
    async submitProduct() {
        this.productErrors = {};
        this.productFormError = '';

        if (!this.productForm.category_id) {
            this.productErrors.categoryId = ['Kategori produk wajib dipilih.'];
            return;
        }
        if (!this.productForm.name.trim()) {
            this.productErrors.name = ['Nama produk wajib diisi.'];
            return;
        }
        if (!this.productForm.code.trim()) {
            this.productErrors.code = ['Kode produk wajib diisi.'];
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveProduct(
            this.productForm.id,
            parseInt(this.productForm.category_id),
            this.productForm.name,
            this.productForm.code,
            this.productForm.description,
            this.productForm.is_active,
            this.productForm.variants
        );
        this.isProcessing = false;

        if (res.success) {
            this.showProductModal = false;
        } else {
            this.productErrors = res.errors || {};
            if (Object.keys(this.productErrors).length === 0) {
                this.productFormError = res.message;
            }
        }
    },
    confirmDeleteProductById(id) {
        const prod = this.products.find(p => p.id === id);
        if (!prod) return;
        this.deleteTarget = {
            type: 'product',
            id: prod.id,
            name: prod.name,
            description: `Apakah Anda yakin ingin menghapus produk '${prod.name}' (${prod.code}) beserta seluruh varian kemasannya?`
        };
        this.showDeleteModal = true;
    },

    // --- Recipe Methods ---
    openCreateRecipeModal() {
        this.recipeForm = {
            id: null,
            product_variant_id: this.variants[0]?.id || '',
            name: '',
            batch_output_qty: 50,
            labor_cost: 50000,
            overhead_cost: 25000,
            notes: '',
            is_active: true,
            items: [
                { raw_material_id: this.rawMaterials[0]?.id || '', quantity_required: 1000 }
            ]
        };
        this.recipeModalTitle = 'Tambah Formula Resep Baru';
        this.recipeErrors = {};
        this.recipeFormError = '';
        this.showRecipeModal = true;
    },
    openEditRecipeById(id) {
        const rcp = this.recipes.find(r => r.id === id);
        if (!rcp) return;
        this.recipeForm = {
            id: rcp.id,
            product_variant_id: rcp.product_variant_id,
            name: rcp.name,
            batch_output_qty: rcp.batch_output_qty,
            labor_cost: rcp.labor_cost,
            overhead_cost: rcp.overhead_cost,
            notes: rcp.notes || '',
            is_active: rcp.is_active,
            items: rcp.items ? rcp.items.map(i => ({
                raw_material_id: i.raw_material_id,
                quantity_required: i.quantity_required
            })) : []
        };
        this.recipeModalTitle = 'Edit Resep: ' + rcp.name;
        this.recipeErrors = {};
        this.recipeFormError = '';
        this.showRecipeModal = true;
    },
    addRecipeItemRow() {
        this.recipeForm.items.push({
            raw_material_id: this.rawMaterials[0]?.id || '',
            quantity_required: 100
        });
    },
    removeRecipeItemRow(idx) {
        if (this.recipeForm.items.length > 1) {
            this.recipeForm.items.splice(idx, 1);
        }
    },
    async submitRecipe() {
        this.recipeErrors = {};
        this.recipeFormError = '';

        if (!this.recipeForm.product_variant_id) {
            this.recipeErrors.productVariantId = ['Pilih varian produk target.'];
            return;
        }
        if (!this.recipeForm.name.trim()) {
            this.recipeErrors.name = ['Nama formula resep wajib diisi.'];
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveRecipe(
            this.recipeForm.id,
            parseInt(this.recipeForm.product_variant_id),
            this.recipeForm.name,
            parseInt(this.recipeForm.batch_output_qty),
            parseFloat(this.recipeForm.labor_cost) || 0,
            parseFloat(this.recipeForm.overhead_cost) || 0,
            this.recipeForm.notes,
            this.recipeForm.is_active,
            this.recipeForm.items
        );
        this.isProcessing = false;

        if (res.success) {
            this.showRecipeModal = false;
        } else {
            this.recipeErrors = res.errors || {};
            if (Object.keys(this.recipeErrors).length === 0) {
                this.recipeFormError = res.message;
            }
        }
    },
    confirmDeleteRecipeById(id) {
        const rcp = this.recipes.find(r => r.id === id);
        if (!rcp) return;
        this.deleteTarget = {
            type: 'recipe',
            id: rcp.id,
            name: rcp.name,
            description: `Apakah Anda yakin ingin menghapus formula resep '${rcp.name}'?`
        };
        this.showDeleteModal = true;
    },

    // --- Raw Material Methods ---
    openCreateRawMaterialModal() {
        this.materialForm = {
            id: null,
            code: '',
            name: '',
            category: 'ingredient',
            unit: 'gram',
            stock_qty: 0,
            min_stock_alert: 1000,
            average_cost: 0,
            notes: '',
            is_active: true
        };
        this.materialModalTitle = 'Tambah Bahan Baku Baru';
        this.materialErrors = {};
        this.materialFormError = '';
        this.showRawMaterialModal = true;
    },
    openEditRawMaterialById(id) {
        const mat = this.rawMaterials.find(m => m.id === id);
        if (!mat) return;
        this.materialForm = {
            id: mat.id,
            code: mat.code,
            name: mat.name,
            category: mat.category,
            unit: mat.unit,
            stock_qty: mat.stock_qty,
            min_stock_alert: mat.min_stock_alert,
            average_cost: mat.average_cost,
            notes: mat.notes || '',
            is_active: mat.is_active
        };
        this.materialModalTitle = 'Edit Bahan Baku: ' + mat.name;
        this.materialErrors = {};
        this.materialFormError = '';
        this.showRawMaterialModal = true;
    },
    async submitRawMaterial() {
        this.materialErrors = {};
        this.materialFormError = '';

        if (!this.materialForm.code.trim()) {
            this.materialErrors.code = ['Kode bahan baku wajib diisi.'];
            return;
        }
        if (!this.materialForm.name.trim()) {
            this.materialErrors.name = ['Nama bahan baku wajib diisi.'];
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveRawMaterial(
            this.materialForm.id,
            this.materialForm.code,
            this.materialForm.name,
            this.materialForm.category,
            this.materialForm.unit,
            parseFloat(this.materialForm.stock_qty) || 0,
            parseFloat(this.materialForm.min_stock_alert) || 0,
            parseFloat(this.materialForm.average_cost) || 0,
            this.materialForm.notes,
            this.materialForm.is_active
        );
        this.isProcessing = false;

        if (res.success) {
            this.showRawMaterialModal = false;
        } else {
            this.materialErrors = res.errors || {};
            if (Object.keys(this.materialErrors).length === 0) {
                this.materialFormError = res.message;
            }
        }
    },
    confirmDeleteRawMaterialById(id) {
        const mat = this.rawMaterials.find(m => m.id === id);
        if (!mat) return;
        this.deleteTarget = {
            type: 'raw-material',
            id: mat.id,
            name: mat.name,
            description: `Apakah Anda yakin ingin menghapus bahan baku '${mat.name}' (${mat.code})?`
        };
        this.showDeleteModal = true;
    },

    // --- Category Methods ---
    openCreateCategoryModal() {
        this.categoryForm = { id: null, name: '', description: '', is_active: true };
        this.categoryModalTitle = 'Tambah Kategori Baru';
        this.categoryErrors = {};
        this.categoryFormError = '';
        this.showCategoryModal = true;
    },
    openEditCategoryById(id) {
        const cat = this.categories.find(c => c.id === id);
        if (!cat) return;
        this.categoryForm = {
            id: cat.id,
            name: cat.name,
            description: cat.description || '',
            is_active: cat.is_active
        };
        this.categoryModalTitle = 'Edit Kategori: ' + cat.name;
        this.categoryErrors = {};
        this.categoryFormError = '';
        this.showCategoryModal = true;
    },
    async submitCategory() {
        this.categoryErrors = {};
        this.categoryFormError = '';

        if (!this.categoryForm.name.trim()) {
            this.categoryErrors.name = ['Nama kategori wajib diisi.'];
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveCategory(
            this.categoryForm.id,
            this.categoryForm.name,
            this.categoryForm.description,
            this.categoryForm.is_active
        );
        this.isProcessing = false;

        if (res.success) {
            this.showCategoryModal = false;
        } else {
            this.categoryErrors = res.errors || {};
            if (Object.keys(this.categoryErrors).length === 0) {
                this.categoryFormError = res.message;
            }
        }
    },
    confirmDeleteCategoryById(id) {
        const cat = this.categories.find(c => c.id === id);
        if (!cat) return;
        this.deleteTarget = {
            type: 'category',
            id: cat.id,
            name: cat.name,
            description: `Apakah Anda yakin ingin menghapus kategori '${cat.name}'?`
        };
        this.showDeleteModal = true;
    },

    // --- Execute Delete ---
    async executeDelete() {
        if (!this.deleteTarget.id) return;
        this.isProcessing = true;

        if (this.deleteTarget.type === 'product') {
            await $wire.deleteProduct(this.deleteTarget.id);
        } else if (this.deleteTarget.type === 'recipe') {
            await $wire.deleteRecipe(this.deleteTarget.id);
        } else if (this.deleteTarget.type === 'raw-material') {
            await $wire.deleteRawMaterial(this.deleteTarget.id);
        } else if (this.deleteTarget.type === 'category') {
            await $wire.deleteCategory(this.deleteTarget.id);
        }

        this.isProcessing = false;
        this.showDeleteModal = false;
    }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Master Produk & Resep (BOM)
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola katalog camilan Halala Food, varian kemasan multi-satuan, persediaan bahan baku, dan formula resep standar.
            </p>
        </div>
    </div>

    <!-- Main Navigation Bar (Clean Underline Tab Pattern matching Role & Permission) -->
    <div class="space-y-6">
        <div class="flex border-b border-brand-border gap-4 sm:gap-8 overflow-x-auto">
            <!-- 1. Tab Produk & Kemasan Varian -->
            <button type="button" @click="activeTab = 'products'"
                :class="activeTab === 'products' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-cookie text-xl"></i>
                <span>Produk & Kemasan</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'products' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalProducts }} Produk
                </span>
            </button>

            <!-- 2. Tab Formula Resep (BOM) -->
            <button type="button" @click="activeTab = 'recipes'"
                :class="activeTab === 'recipes' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-receipt text-xl"></i>
                <span>Formula Resep (BOM)</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'recipes' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalRecipes }} Resep
                </span>
            </button>

            <!-- 3. Tab Bahan Baku & Gudang -->
            <button type="button" @click="activeTab = 'raw-materials'"
                :class="activeTab === 'raw-materials' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-archive text-xl"></i>
                <span>Bahan Baku & Kemasan</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'raw-materials' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalRawMaterials }} Bahan
                </span>
            </button>

            <!-- 4. Tab Kategori Produk -->
            <button type="button" @click="activeTab = 'categories'"
                :class="activeTab === 'categories' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-category text-xl"></i>
                <span>Kategori Produk</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'categories' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalCategories }}
                </span>
            </button>
        </div>

        <!-- TAB CONTENT PARTIALS -->
        @include('components.admin.products.partials.tab-products')
        @include('components.admin.products.partials.tab-recipes')
        @include('components.admin.products.partials.tab-raw-materials')
        @include('components.admin.products.partials.tab-categories')

    </div>

    <!-- MODAL PARTIALS -->
    @include('components.admin.products.partials.modal-product')
    @include('components.admin.products.partials.modal-recipe')
    @include('components.admin.products.partials.modal-raw-material')
    @include('components.admin.products.partials.modal-category')
    @include('components.admin.products.partials.modal-delete')

</div>
