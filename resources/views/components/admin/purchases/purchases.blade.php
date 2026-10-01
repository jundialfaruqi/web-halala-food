<div x-data="{
    activeTab: (function() {
        const hash = window.location.hash.replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        if (['orders', 'suppliers'].includes(hash)) return hash;
        if (['orders', 'suppliers'].includes(queryTab)) return queryTab;
        return 'orders';
    })(),

    // Search & Filter Models
    orderSearch: '',
    orderStatusFilter: 'all',
    orderSupplierFilter: 'all',

    supplierSearch: '',
    supplierStatusFilter: 'all',
    isProcessing: false,

    init() {
        this.$watch('activeTab', (tab) => {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.replaceState(null, '', url.toString());
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (['orders', 'suppliers'].includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });
    },

    // Datasets from Backend
    purchaseOrders: {{ \Illuminate\Support\Js::from($purchaseOrders) }},
    suppliers: {{ \Illuminate\Support\Js::from($suppliers) }},
    rawMaterials: {{ \Illuminate\Support\Js::from($rawMaterials) }},
    todayDate: {{ \Illuminate\Support\Js::from($todayDate) }},
    nextSupplierCode: {{ \Illuminate\Support\Js::from($nextSupplierCode) }},

    // Computed Filtered Orders
    get filteredOrders() {
        return this.purchaseOrders.filter(po => {
            const matchesSearch = !this.orderSearch ||
                po.po_number.toLowerCase().includes(this.orderSearch.toLowerCase()) ||
                po.supplier_name.toLowerCase().includes(this.orderSearch.toLowerCase()) ||
                (po.notes && po.notes.toLowerCase().includes(this.orderSearch.toLowerCase()));

            const matchesStatus = this.orderStatusFilter === 'all' || po.status === this.orderStatusFilter;
            const matchesSupplier = this.orderSupplierFilter === 'all' || po.supplier_id == this.orderSupplierFilter;

            return matchesSearch && matchesStatus && matchesSupplier;
        });
    },

    // Computed Filtered Suppliers
    get filteredSuppliers() {
        return this.suppliers.filter(supplier => {
            const matchesSearch = !this.supplierSearch ||
                supplier.name.toLowerCase().includes(this.supplierSearch.toLowerCase()) ||
                supplier.code.toLowerCase().includes(this.supplierSearch.toLowerCase()) ||
                (supplier.contact_person && supplier.contact_person.toLowerCase().includes(this.supplierSearch.toLowerCase())) ||
                (supplier.city && supplier.city.toLowerCase().includes(this.supplierSearch.toLowerCase()));

            const matchesStatus = this.supplierStatusFilter === 'all' ||
                (this.supplierStatusFilter === 'active' && supplier.is_active) ||
                (this.supplierStatusFilter === 'inactive' && !supplier.is_active);

            return matchesSearch && matchesStatus;
        });
    },

    // Create / Edit PO Modal State
    showOrderModal: false,
    orderModalTitle: 'Buat Pesanan Pembelian (PO)',
    orderForm: {
        id: null,
        supplier_id: '',
        order_date: '',
        due_date: '',
        status: 'draft',
        notes: '',
        items: []
    },
    orderErrors: {},
    orderFormError: '',

    get calculatedOrderTotalFormatted() {
        const total = this.orderForm.items.reduce((sum, it) => {
            return sum + ((parseFloat(it.qty_ordered) || 0) * (parseFloat(it.unit_price) || 0));
        }, 0);
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    },

    // Receive Modal State
    showReceiveModal: false,
    receivePoData: null,
    receiveForm: {
        id: null,
        po_number: '',
        items: []
    },

    // Supplier Modal State
    showSupplierModal: false,
    supplierModalTitle: 'Tambah Supplier Baru',
    supplierForm: {
        id: null,
        code: '',
        name: '',
        contact_person: '',
        phone: '',
        whatsapp_number: '',
        email: '',
        address: '',
        city: '',
        payment_terms_days: 0,
        notes: '',
        is_active: true
    },
    supplierErrors: {},
    supplierFormError: '',

    // Print PO Modal State
    showPrintPoModal: false,
    printPoData: null,

    // Delete Modal State
    showDeleteModal: false,
    deleteTargetType: null, // 'order' or 'supplier'
    deleteTargetId: null,
    deleteTargetDescription: '',

    // Toast Notification
    notify(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    // --- PO Modal Methods ---
    openCreateOrderModal() {
        const defaultSupplier = this.suppliers[0] || null;
        const defaultMaterial = this.rawMaterials[0] || null;

        let dueDate = '';
        if (defaultSupplier && defaultSupplier.payment_terms_days > 0) {
            const d = new Date();
            d.setDate(d.getDate() + defaultSupplier.payment_terms_days);
            dueDate = d.toISOString().split('T')[0];
        }

        this.orderForm = {
            id: null,
            supplier_id: defaultSupplier ? defaultSupplier.id : '',
            order_date: this.todayDate,
            due_date: dueDate,
            status: 'draft',
            notes: '',
            items: defaultMaterial ? [
                {
                    raw_material_id: defaultMaterial.id,
                    qty_ordered: 10,
                    unit_price: defaultMaterial.average_cost > 0 ? defaultMaterial.average_cost : 10000,
                    notes: ''
                }
            ] : []
        };
        this.orderModalTitle = 'Buat Pesanan Pembelian (PO) Baru';
        this.orderErrors = {};
        this.orderFormError = '';
        this.showOrderModal = true;
    },

    openEditOrderModal(po) {
        this.orderForm = {
            id: po.id,
            supplier_id: po.supplier_id,
            order_date: po.order_date,
            due_date: po.due_date || '',
            status: po.status,
            notes: po.notes || '',
            items: po.items.map(it => ({
                raw_material_id: it.raw_material_id,
                qty_ordered: it.qty_ordered,
                unit_price: it.unit_price,
                notes: it.notes || ''
            }))
        };
        this.orderModalTitle = 'Edit Pesanan Pembelian: ' + po.po_number;
        this.orderErrors = {};
        this.orderFormError = '';
        this.showOrderModal = true;
    },

    onSupplierChange() {
        const supplier = this.suppliers.find(s => s.id == this.orderForm.supplier_id);
        if (supplier && supplier.payment_terms_days > 0 && this.orderForm.order_date) {
            const d = new Date(this.orderForm.order_date);
            d.setDate(d.getDate() + supplier.payment_terms_days);
            this.orderForm.due_date = d.toISOString().split('T')[0];
        }
    },

    addOrderItemRow() {
        const defaultMaterial = this.rawMaterials[0] || null;
        if (!defaultMaterial) return;

        this.orderForm.items.push({
            raw_material_id: defaultMaterial.id,
            qty_ordered: 1,
            unit_price: defaultMaterial.average_cost > 0 ? defaultMaterial.average_cost : 10000,
            notes: ''
        });
    },

    removeOrderItemRow(index) {
        if (this.orderForm.items.length > 1) {
            this.orderForm.items.splice(index, 1);
        }
    },

    onItemMaterialChange(item) {
        const material = this.rawMaterials.find(m => m.id == item.raw_material_id);
        if (material && material.average_cost > 0) {
            item.unit_price = material.average_cost;
        }
    },

    async submitOrder() {
        this.orderErrors = {};
        this.orderFormError = '';

        if (!this.orderForm.supplier_id) {
            this.orderErrors.supplierId = ['Pilih supplier tujuan pemesanan.'];
            return;
        }
        if (!this.orderForm.order_date) {
            this.orderErrors.orderDate = ['Tanggal pemesanan wajib diisi.'];
            return;
        }
        if (this.orderForm.items.length === 0) {
            this.orderFormError = 'Tambahkan minimal 1 item bahan baku pesanan.';
            return;
        }

        this.isProcessing = true;
        const res = await $wire.savePurchaseOrder(
            this.orderForm.id,
            parseInt(this.orderForm.supplier_id),
            this.orderForm.order_date,
            this.orderForm.due_date || null,
            this.orderForm.status,
            this.orderForm.notes,
            this.orderForm.items
        );
        this.isProcessing = false;

        if (res.success) {
            this.showOrderModal = false;
            this.notify(res.message, 'success');
        } else {
            if (res.errors) {
                this.orderErrors = res.errors;
            }
            this.orderFormError = res.message || 'Silakan periksa kembali formulir pesanan.';
            this.notify(this.orderFormError, 'error');
        }
    },

    // --- Receive Goods Modal Methods ---
    openReceiveModal(po) {
        this.receivePoData = po;
        this.receiveForm = {
            id: po.id,
            po_number: po.po_number,
            items: po.items.map(it => ({
                item_id: it.id,
                material_name: it.material_name,
                material_unit: it.material_unit,
                qty_ordered: it.qty_ordered,
                qty_received: it.qty_ordered // default to full ordered qty
            }))
        };
        this.showReceiveModal = true;
    },

    async submitReceiveGoods() {
        this.isProcessing = true;
        const res = await $wire.receivePurchaseOrder(
            this.receiveForm.id,
            this.receiveForm.items
        );
        this.isProcessing = false;

        if (res.success) {
            this.showReceiveModal = false;
            this.notify(res.message, 'success');
        } else {
            this.notify(res.message || 'Gagal mengonfirmasi penerimaan barang.', 'error');
        }
    },

    async cancelOrder(id) {
        const po = this.purchaseOrders.find(p => p.id === id);
        if (!po) return;

        if (confirm(`Apakah Anda yakin ingin membatalkan Pesanan ${po.po_number}?`)) {
            this.isProcessing = true;
            const res = await $wire.cancelPurchaseOrder(id);
            this.isProcessing = false;

            if (res.success) {
                this.notify(res.message, 'success');
            } else {
                this.notify(res.message, 'error');
            }
        }
    },

    confirmDeleteOrder(id) {
        const po = this.purchaseOrders.find(p => p.id === id);
        if (!po) return;

        this.deleteTargetType = 'order';
        this.deleteTargetId = po.id;
        this.deleteTargetDescription = `Apakah Anda yakin ingin menghapus PO '${po.po_number}'? Tindakan ini tidak dapat dibatalkan.`;
        this.showDeleteModal = true;
    },

    // --- Supplier Modal Methods ---
    openCreateSupplierModal() {
        this.supplierForm = {
            id: null,
            code: this.nextSupplierCode,
            name: '',
            contact_person: '',
            phone: '',
            whatsapp_number: '',
            email: '',
            address: '',
            city: '',
            payment_terms_days: 0,
            notes: '',
            is_active: true
        };
        this.supplierModalTitle = 'Tambah Supplier Baru';
        this.supplierErrors = {};
        this.supplierFormError = '';
        this.showSupplierModal = true;
    },

    openEditSupplierModal(supplier) {
        this.supplierForm = {
            id: supplier.id,
            code: supplier.code,
            name: supplier.name,
            contact_person: supplier.contact_person || '',
            phone: supplier.phone || '',
            whatsapp_number: supplier.whatsapp_number || supplier.phone || '',
            email: supplier.email || '',
            address: supplier.address || '',
            city: supplier.city || '',
            payment_terms_days: supplier.payment_terms_days,
            notes: supplier.notes || '',
            is_active: supplier.is_active
        };
        this.supplierModalTitle = 'Edit Data Supplier: ' + supplier.name;
        this.supplierErrors = {};
        this.supplierFormError = '';
        this.showSupplierModal = true;
    },

    async submitSupplier() {
        this.supplierErrors = {};
        this.supplierFormError = '';

        if (!this.supplierForm.code.trim()) {
            this.supplierErrors.code = ['Kode supplier wajib diisi.'];
            return;
        }
        if (!this.supplierForm.name.trim()) {
            this.supplierErrors.name = ['Nama supplier wajib diisi.'];
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveSupplier(
            this.supplierForm.id,
            this.supplierForm.code,
            this.supplierForm.name,
            this.supplierForm.contact_person,
            this.supplierForm.phone,
            this.supplierForm.whatsapp_number,
            this.supplierForm.email,
            this.supplierForm.address,
            this.supplierForm.city,
            parseInt(this.supplierForm.payment_terms_days) || 0,
            this.supplierForm.notes,
            this.supplierForm.is_active
        );
        this.isProcessing = false;

        if (res.success) {
            this.showSupplierModal = false;
            this.notify(res.message, 'success');
        } else {
            if (res.errors) {
                this.supplierErrors = res.errors;
            }
            this.supplierFormError = res.message || 'Silakan periksa kembali formulir supplier.';
            this.notify(this.supplierFormError, 'error');
        }
    },

    confirmDeleteSupplier(id) {
        const supplier = this.suppliers.find(s => s.id === id);
        if (!supplier) return;

        this.deleteTargetType = 'supplier';
        this.deleteTargetId = supplier.id;
        this.deleteTargetDescription = `Apakah Anda yakin ingin menghapus data supplier '${supplier.name}' (${supplier.code})?`;
        this.showDeleteModal = true;
    },

    async executeDelete() {
        if (!this.deleteTargetId) return;

        this.isProcessing = true;
        let res;
        if (this.deleteTargetType === 'order') {
            res = await $wire.deletePurchaseOrder(this.deleteTargetId);
        } else if (this.deleteTargetType === 'supplier') {
            res = await $wire.deleteSupplier(this.deleteTargetId);
        }
        this.isProcessing = false;

        if (res && res.success) {
            this.showDeleteModal = false;
            this.notify(res.message, 'success');
        } else {
            this.notify((res && res.message) || 'Gagal menghapus data.', 'error');
        }
    },

    openPrintPoModal(po) {
        this.printPoData = po;
        this.showPrintPoModal = true;
    },

    triggerPrintPo() {
        window.print();
    }
}" class="space-y-6">

    <!-- Header Section (Clean standard pattern matching Roles & Products) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Pengadaan Bahan Baku & Supplier
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola pesanan pembelian (PO), direktori pemasok, penerimaan gudang, dan kalkulasi HPP rata-rata berjalan.
            </p>
        </div>
    </div>

    <!-- Main Navigation Bar (Clean Underline Tab Pattern) -->
    <div class="space-y-6">
        <div class="flex border-b border-brand-border gap-6 sm:gap-8 overflow-x-auto">
            <!-- 1. Tab Pesanan Pembelian (PO) -->
            <button type="button" @click="activeTab = 'orders'"
                :class="activeTab === 'orders' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-file-invoice text-xl"></i>
                <span>Pesanan Pembelian (PO)</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'orders' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalOrders }}
                </span>
            </button>

            <!-- 2. Tab Direktori Supplier -->
            <button type="button" @click="activeTab = 'suppliers'"
                :class="activeTab === 'suppliers' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-building-warehouse text-xl"></i>
                <span>Direktori Supplier</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'suppliers' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalSuppliers }}
                </span>
            </button>
        </div>

        <!-- TAB CONTENT PARTIALS -->
        @include('components.admin.purchases.partials.tab-orders')
        @include('components.admin.purchases.partials.tab-suppliers')

    </div>

    <!-- MODALS -->
    @include('components.admin.purchases.partials.modal-order')
    @include('components.admin.purchases.partials.modal-receive')
    @include('components.admin.purchases.partials.modal-supplier')
    @include('components.admin.purchases.partials.modal-print-order')
    @include('components.admin.purchases.partials.modal-delete')

</div>
