<div x-data="{
    activeTab: (function() {
        const hash = window.location.hash.replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        if (['batches', 'raw-stock', 'finished-stock', 'waste'].includes(hash)) return hash;
        if (['batches', 'raw-stock', 'finished-stock', 'waste'].includes(queryTab)) return queryTab;
        return 'batches';
    })(),

    // Search & Filter Models
    batchSearch: '',
    batchStatusFilter: 'all',
    rawStockSearch: '',
    finishedStockSearch: '',
    wasteSearch: '',
    isProcessing: false,

    init() {
        this.$watch('activeTab', (tab) => {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.replaceState(null, '', url.toString());
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (['batches', 'raw-stock', 'finished-stock', 'waste'].includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });
    },

    // Datasets from Backend
    batches: {{ \Illuminate\Support\Js::from($batches) }},
    rawStockMutations: {{ \Illuminate\Support\Js::from($rawStockMutations) }},
    finishedStockMutations: {{ \Illuminate\Support\Js::from($finishedStockMutations) }},
    wasteLogs: {{ \Illuminate\Support\Js::from($wasteLogs) }},
    recipes: {{ \Illuminate\Support\Js::from($recipes) }},
    rawMaterials: {{ \Illuminate\Support\Js::from($rawMaterials) }},
    productVariants: {{ \Illuminate\Support\Js::from($productVariants) }},
    cooks: {{ \Illuminate\Support\Js::from($cooks) }},

    // Batch Modal State
    showBatchModal: false,
    batchModalTitle: 'Buat Rencana Produksi Baru',
    batchForm: { id: null, recipe_id: '', user_id: '', planned_qty: 50, notes: '' },
    batchErrors: {},
    batchFormError: '',

    // Finish Batch Modal State
    showFinishBatchModal: false,
    finishBatchData: { id: null, batch_number: '', planned_qty: 0, actual_qty_good: 0, actual_qty_bad: 0, notes: '' },

    // Waste Modal State
    showWasteModal: false,
    wasteForm: { raw_material_id: '', quantity: 100, reason: 'spilled', notes: '' },

    // Adjustment Modal State
    showAdjustmentModal: false,
    adjustmentModalTitle: 'Penyesuaian Stok Opname',
    adjustmentTargetType: 'raw_material', // raw_material, finished_good
    adjustmentForm: { target_id: '', actual_stock: 0, reason: '' },

    // Delete Modal State
    showDeleteModal: false,
    deleteTarget: { type: '', id: null, description: '' },

    // Toast Notification Dispatcher
    notify(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    // --- Batch Methods ---
    openCreateBatchModal() {
        this.batchForm = {
            id: null,
            recipe_id: this.recipes[0]?.id || '',
            user_id: this.cooks[0]?.id || '',
            planned_qty: this.recipes[0]?.batch_output_qty || 50,
            notes: ''
        };
        this.batchModalTitle = 'Buat Rencana Produksi Baru';
        this.batchErrors = {};
        this.batchFormError = '';
        this.showBatchModal = true;
    },
    async submitBatch() {
        this.batchErrors = {};
        this.batchFormError = '';

        if (!this.batchForm.recipe_id) {
            this.batchErrors.recipeId = ['Pilih formula resep produksi.'];
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveBatch(
            this.batchForm.id,
            parseInt(this.batchForm.recipe_id),
            this.batchForm.user_id ? parseInt(this.batchForm.user_id) : null,
            parseInt(this.batchForm.planned_qty),
            this.batchForm.notes
        );
        this.isProcessing = false;

        if (res.success) {
            this.showBatchModal = false;
        } else {
            this.batchErrors = res.errors || {};
            if (Object.keys(this.batchErrors).length === 0) {
                this.batchFormError = res.message;
            }
        }
    },
    async startBatch(id) {
        this.isProcessing = true;
        await $wire.startBatch(id);
        this.isProcessing = false;
    },
    openFinishBatchModal(id) {
        const batch = this.batches.find(b => b.id === id);
        if (!batch) return;
        this.finishBatchData = {
            id: batch.id,
            batch_number: batch.batch_number,
            planned_qty: batch.planned_qty,
            actual_qty_good: batch.planned_qty,
            actual_qty_bad: 0,
            notes: ''
        };
        this.showFinishBatchModal = true;
    },
    async submitFinishBatch() {
        this.isProcessing = true;
        const res = await $wire.finishBatch(
            this.finishBatchData.id,
            parseInt(this.finishBatchData.actual_qty_good),
            parseInt(this.finishBatchData.actual_qty_bad) || 0,
            this.finishBatchData.notes
        );
        this.isProcessing = false;

        if (res.success) {
            this.showFinishBatchModal = false;
        }
    },
    async cancelBatch(id) {
        const batch = this.batches.find(b => b.id === id);
        if (!batch) return;
        if (confirm(`Apakah Anda yakin ingin membatalkan batch ${batch.batch_number}?`)) {
            this.isProcessing = true;
            await $wire.cancelBatch(id);
            this.isProcessing = false;
        }
    },
    confirmDeleteBatch(id) {
        const batch = this.batches.find(b => b.id === id);
        if (!batch) return;
        this.deleteTarget = {
            type: 'batch',
            id: batch.id,
            description: `Apakah Anda yakin ingin menghapus rencana batch '${batch.batch_number}'?`
        };
        this.showDeleteModal = true;
    },

    // --- Waste Methods ---
    openCreateWasteModal() {
        this.wasteForm = {
            raw_material_id: this.rawMaterials[0]?.id || '',
            quantity: 100,
            reason: 'spilled',
            notes: ''
        };
        this.showWasteModal = true;
    },
    async submitWaste() {
        if (!this.wasteForm.raw_material_id) return;
        this.isProcessing = true;
        const res = await $wire.saveWasteLog(
            parseInt(this.wasteForm.raw_material_id),
            parseFloat(this.wasteForm.quantity) || 0,
            this.wasteForm.reason,
            this.wasteForm.notes
        );
        this.isProcessing = false;

        if (res.success) {
            this.showWasteModal = false;
        }
    },

    // --- Adjustment Methods ---
    openRawStockAdjustmentModal() {
        this.adjustmentTargetType = 'raw_material';
        this.adjustmentModalTitle = 'Penyesuaian Stok Opname Bahan Baku';
        this.adjustmentForm = {
            target_id: this.rawMaterials[0]?.id || '',
            actual_stock: this.rawMaterials[0]?.stock_qty || 0,
            reason: 'Hasil audit fisik opname gudang'
        };
        this.showAdjustmentModal = true;
    },
    openFinishedStockAdjustmentModal() {
        this.adjustmentTargetType = 'finished_good';
        this.adjustmentModalTitle = 'Penyesuaian Stok Opname Barang Jadi';
        this.adjustmentForm = {
            target_id: this.productVariants[0]?.id || '',
            actual_stock: this.productVariants[0]?.stock_qty || 0,
            reason: 'Hasil audit fisik display toko'
        };
        this.showAdjustmentModal = true;
    },
    async submitAdjustment() {
        if (!this.adjustmentForm.target_id) return;
        this.isProcessing = true;

        if (this.adjustmentTargetType === 'raw_material') {
            await $wire.adjustRawStock(
                parseInt(this.adjustmentForm.target_id),
                parseFloat(this.adjustmentForm.actual_stock) || 0,
                this.adjustmentForm.reason
            );
        } else {
            await $wire.adjustFinishedStock(
                parseInt(this.adjustmentForm.target_id),
                parseInt(this.adjustmentForm.actual_stock) || 0,
                this.adjustmentForm.reason
            );
        }

        this.isProcessing = false;
        this.showAdjustmentModal = false;
    },

    // --- Delete Execution ---
    async executeDelete() {
        if (!this.deleteTarget.id) return;
        this.isProcessing = true;
        if (this.deleteTarget.type === 'batch') {
            await $wire.deleteBatch(this.deleteTarget.id);
        }
        this.isProcessing = false;
        this.showDeleteModal = false;
    }
}" class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Dapur & Produksi Manufaktur
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola antrean masak adonan, eksekusi batch produksi, kartu mutasi stok, dan pencatatan bahan rusak.
            </p>
        </div>

        <!-- Quick Metrik Badges -->
        <div class="flex items-center gap-3 shrink-0">
            <div class="px-4 py-2 bg-white border border-brand-border rounded-xl shadow-2xs text-center">
                <span class="text-xs font-bold text-brand-warm-gray block">Sedang Dimasak</span>
                <span class="text-lg font-extrabold text-amber-600 font-mono">{{ $inProgressBatchesCount }} Batch</span>
            </div>
            <div class="px-4 py-2 bg-white border border-brand-border rounded-xl shadow-2xs text-center">
                <span class="text-xs font-bold text-brand-warm-gray block">Selesai Hari Ini</span>
                <span class="text-lg font-extrabold text-green-600 font-mono">{{ $completedTodayCount }} Batch</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar (Clean Underline Tab Pattern) -->
    <div class="space-y-6">
        <div class="flex border-b border-brand-border gap-4 sm:gap-8 overflow-x-auto">
            <!-- 1. Tab Batch Produksi -->
            <button type="button" @click="activeTab = 'batches'"
                :class="activeTab === 'batches' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-flame text-xl"></i>
                <span>Batch Produksi</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'batches' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalBatches }}
                </span>
            </button>

            <!-- 2. Tab Kartu Stok Bahan Baku -->
            <button type="button" @click="activeTab = 'raw-stock'"
                :class="activeTab === 'raw-stock' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-archive text-xl"></i>
                <span>Mutasi Bahan Baku</span>
            </button>

            <!-- 3. Tab Kartu Stok Barang Jadi -->
            <button type="button" @click="activeTab = 'finished-stock'"
                :class="activeTab === 'finished-stock' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-box text-xl"></i>
                <span>Mutasi Barang Jadi</span>
            </button>

            <!-- 4. Tab Laporan Waste / Bahan Rusak -->
            <button type="button" @click="activeTab = 'waste'"
                :class="activeTab === 'waste' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-trash text-xl"></i>
                <span>Bahan Rusak (Waste)</span>
            </button>
        </div>

        <!-- TAB CONTENT PARTIALS -->
        @include('components.admin.production.partials.tab-batches')
        @include('components.admin.production.partials.tab-raw-stock')
        @include('components.admin.production.partials.tab-finished-stock')
        @include('components.admin.production.partials.tab-waste')

    </div>

    <!-- MODALS -->
    @include('components.admin.production.partials.modal-batch')
    @include('components.admin.production.partials.modal-finish-batch')
    @include('components.admin.production.partials.modal-waste')
    @include('components.admin.production.partials.modal-stock-adjustment')
    @include('components.admin.production.partials.modal-delete')

</div>
