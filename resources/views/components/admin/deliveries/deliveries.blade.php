<div x-data="{
    activeTab: (function() {
        const hash = window.location.hash.replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        if (['deliveries', 'my-tasks'].includes(hash)) return hash;
        if (['deliveries', 'my-tasks'].includes(queryTab)) return queryTab;
        return 'deliveries';
    })(),

    // Search & Filter Models
    deliverySearch: '',
    deliveryStatusFilter: 'all',
    deliveryPartnerFilter: 'all',
    isProcessing: false,

    init() {
        this.$watch('activeTab', (tab) => {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.replaceState(null, '', url.toString());
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (['deliveries', 'my-tasks'].includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });
    },

    // Datasets from Backend
    deliveries: {{ \Illuminate\Support\Js::from($deliveries) }},
    partners: {{ \Illuminate\Support\Js::from($partners) }},
    couriers: {{ \Illuminate\Support\Js::from($couriers) }},
    productVariants: {{ \Illuminate\Support\Js::from($productVariants) }},
    currentUserId: {{ \Illuminate\Support\Js::from($currentUserId) }},

    // Computed Filtered Deliveries
    get filteredDeliveries() {
        return this.deliveries.filter(delivery => {
            const matchesSearch = !this.deliverySearch ||
                delivery.delivery_number.toLowerCase().includes(this.deliverySearch.toLowerCase()) ||
                delivery.partner_name.toLowerCase().includes(this.deliverySearch.toLowerCase()) ||
                (delivery.courier_name && delivery.courier_name.toLowerCase().includes(this.deliverySearch.toLowerCase())) ||
                (delivery.partner_city && delivery.partner_city.toLowerCase().includes(this.deliverySearch.toLowerCase()));

            const matchesStatus = this.deliveryStatusFilter === 'all' || delivery.status === this.deliveryStatusFilter;
            const matchesPartner = this.deliveryPartnerFilter === 'all' || delivery.partner_id == this.deliveryPartnerFilter;

            return matchesSearch && matchesStatus && matchesPartner;
        });
    },

    // Computed Courier Tasks
    get myAssignedDeliveries() {
        return this.deliveries.filter(delivery => {
            return (delivery.courier_id == this.currentUserId || !delivery.courier_id) && delivery.status !== 'cancelled';
        });
    },

    // Create / Edit Delivery Modal State
    showDeliveryModal: false,
    deliveryModalTitle: 'Buat Surat Jalan Baru',
    deliveryForm: {
        id: null,
        partner_id: '',
        courier_id: '',
        notes: '',
        items: []
    },
    deliveryErrors: {},
    deliveryFormError: '',

    get calculatedDeliveryTotalFormatted() {
        const total = this.deliveryForm.items.reduce((sum, it) => {
            return sum + ((it.qty_sent || 0) * (it.unit_price || 0));
        }, 0);
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    },

    // Receipt Confirmation Modal State
    showReceiptModal: false,
    receiptData: {},
    receiptForm: {
        id: null,
        receiver_name: '',
        receiver_phone: '',
        items: []
    },

    // Print Delivery Modal State
    showPrintDeliveryModal: false,
    printDeliveryData: null,

    // Delete Modal State
    showDeleteModal: false,
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

    // --- Delivery Methods ---
    openCreateDeliveryModal() {
        const defaultPartnerId = this.partners[0]?.id || '';
        const defaultCourierId = this.couriers[0]?.id || '';
        const defaultVariant = this.productVariants[0] || null;

        this.deliveryForm = {
            id: null,
            partner_id: defaultPartnerId,
            courier_id: defaultCourierId,
            notes: '',
            items: defaultVariant ? [
                {
                    product_variant_id: defaultVariant.id,
                    qty_sent: 20,
                    unit_price: defaultVariant.wholesale_price || 0,
                    notes: ''
                }
            ] : []
        };
        this.deliveryModalTitle = 'Buat Surat Jalan Baru';
        this.deliveryErrors = {};
        this.deliveryFormError = '';
        this.showDeliveryModal = true;
    },

    openEditDeliveryModal(delivery) {
        this.deliveryForm = {
            id: delivery.id,
            partner_id: delivery.partner_id,
            courier_id: delivery.courier_id || '',
            notes: delivery.notes || '',
            items: delivery.items.map(it => ({
                product_variant_id: it.product_variant_id,
                qty_sent: it.qty_sent,
                unit_price: it.unit_price,
                notes: it.notes || ''
            }))
        };
        this.deliveryModalTitle = 'Edit Surat Jalan: ' + delivery.delivery_number;
        this.deliveryErrors = {};
        this.deliveryFormError = '';
        this.showDeliveryModal = true;
    },

    addDeliveryItemRow() {
        const defaultVariant = this.productVariants[0] || null;
        if (!defaultVariant) return;

        this.deliveryForm.items.push({
            product_variant_id: defaultVariant.id,
            qty_sent: 10,
            unit_price: defaultVariant.wholesale_price || 0,
            notes: ''
        });
    },

    removeDeliveryItemRow(index) {
        if (this.deliveryForm.items.length > 1) {
            this.deliveryForm.items.splice(index, 1);
        }
    },

    onItemVariantChange(item) {
        const variant = this.productVariants.find(v => v.id == item.product_variant_id);
        if (variant) {
            item.unit_price = variant.wholesale_price || 0;
        }
    },

    recalcItemSubtotal(item) {
        // Automatically triggers computed getter
    },

    async submitDelivery() {
        this.deliveryErrors = {};
        this.deliveryFormError = '';

        if (!this.deliveryForm.partner_id) {
            this.deliveryErrors.partnerId = ['Pilih mitra toko tujuan pengiriman.'];
            return;
        }
        if (this.deliveryForm.items.length === 0) {
            this.deliveryFormError = 'Tambahkan minimal 1 item produk muatan kemasan.';
            return;
        }

        this.isProcessing = true;
        const res = await $wire.saveDelivery(
            this.deliveryForm.id,
            parseInt(this.deliveryForm.partner_id),
            this.deliveryForm.courier_id ? parseInt(this.deliveryForm.courier_id) : null,
            this.deliveryForm.notes,
            this.deliveryForm.items
        );
        this.isProcessing = false;

        if (res.success) {
            this.showDeliveryModal = false;
            this.notify(res.message, 'success');
        } else {
            if (res.errors) {
                this.deliveryErrors = res.errors;
            }
            this.deliveryFormError = res.message || 'Silakan periksa kembali formulir pengiriman.';
            this.notify(this.deliveryFormError, 'error');
        }
    },

    async dispatchDeliveryOrder(id) {
        const delivery = this.deliveries.find(d => d.id === id);
        if (!delivery) return;

        if (confirm(`Apakah kurir siap diberangkatkan untuk pengiriman ${delivery.delivery_number} menuju ${delivery.partner_name}? Stok barang jadi akan otomatis dialokasikan.`)) {
            this.isProcessing = true;
            const res = await $wire.dispatchDelivery(id);
            this.isProcessing = false;

            if (res.success) {
                this.notify(res.message, 'success');
            } else {
                this.notify(res.message, 'error');
            }
        }
    },

    openConfirmReceiptModal(delivery) {
        this.receiptData = delivery;
        this.receiptForm = {
            id: delivery.id,
            receiver_name: delivery.receiver_name || (delivery.partner_name.split(' ')[0] + ' Staff'),
            receiver_phone: delivery.receiver_phone || delivery.partner_phone || '',
            items: delivery.items.map(it => ({
                product_variant_id: it.product_variant_id,
                full_name: it.full_name,
                qty_sent: it.qty_sent,
                qty_accepted: it.qty_accepted > 0 ? it.qty_accepted : it.qty_sent,
                qty_returned: it.qty_returned || 0
            }))
        };
        this.showReceiptModal = true;
    },

    async submitReceiptConfirmation() {
        if (!this.receiptForm.receiver_name.trim()) {
            this.notify('Nama staf penerima toko wajib diisi.', 'error');
            return;
        }

        this.isProcessing = true;
        const res = await $wire.confirmDeliveryReceipt(
            this.receiptForm.id,
            this.receiptForm.receiver_name,
            this.receiptForm.receiver_phone,
            this.receiptForm.items
        );
        this.isProcessing = false;

        if (res.success) {
            this.showReceiptModal = false;
            this.notify(res.message, 'success');
        } else {
            this.notify(res.message || 'Gagal mengonfirmasi serah terima.', 'error');
        }
    },

    async cancelDeliveryOrder(id) {
        const delivery = this.deliveries.find(d => d.id === id);
        if (!delivery) return;

        if (confirm(`Apakah Anda yakin ingin membatalkan Surat Jalan ${delivery.delivery_number}?`)) {
            this.isProcessing = true;
            const res = await $wire.cancelDelivery(id);
            this.isProcessing = false;

            if (res.success) {
                this.notify(res.message, 'success');
            } else {
                this.notify(res.message, 'error');
            }
        }
    },

    confirmDeleteDelivery(id) {
        const delivery = this.deliveries.find(d => d.id === id);
        if (!delivery) return;

        this.deleteTargetId = delivery.id;
        this.deleteTargetDescription = `Apakah Anda yakin ingin menghapus Surat Jalan '${delivery.delivery_number}'? Tindakan ini tidak dapat dibatalkan.`;
        this.showDeleteModal = true;
    },

    async executeDelete() {
        if (!this.deleteTargetId) return;

        this.isProcessing = true;
        const res = await $wire.deleteDelivery(this.deleteTargetId);
        this.isProcessing = false;

        if (res.success) {
            this.showDeleteModal = false;
            this.notify(res.message, 'success');
        } else {
            this.notify(res.message || 'Gagal menghapus Surat Jalan.', 'error');
        }
    },

    openPrintDeliveryModal(delivery) {
        this.printDeliveryData = delivery;
        this.showPrintDeliveryModal = true;
    },

    triggerPrintDelivery() {
        window.print();
    }
}" class="space-y-6">

    <!-- Header Section (Clean standard pattern matching Roles & Products) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Distribusi Surat Jalan & Kurir
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola surat jalan digital, penugasan kurir antar toko, alokasi stok muatan, dan konfirmasi serah terima.
            </p>
        </div>
    </div>

    <!-- Main Navigation Bar (Clean Underline Tab Pattern) -->
    <div class="space-y-6">
        <div class="flex border-b border-brand-border gap-6 sm:gap-8 overflow-x-auto">
            <!-- 1. Tab Semua Surat Jalan -->
            <button type="button" @click="activeTab = 'deliveries'"
                :class="activeTab === 'deliveries' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-truck text-xl"></i>
                <span>Semua Surat Jalan</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'deliveries' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalDeliveries }}
                </span>
            </button>

            <!-- 2. Tab Tugas Kurir Saya -->
            <button type="button" @click="activeTab = 'my-tasks'"
                :class="activeTab === 'my-tasks' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-steering-wheel text-xl"></i>
                <span>Tugas Pengantaran Kurir</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'my-tasks' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $onTheWayCount }} Berjalan
                </span>
            </button>
        </div>

        <!-- TAB CONTENT PARTIALS -->
        @include('components.admin.deliveries.partials.tab-deliveries')
        @include('components.admin.deliveries.partials.tab-my-tasks')

    </div>

    <!-- MODALS -->
    @include('components.admin.deliveries.partials.modal-delivery')
    @include('components.admin.deliveries.partials.modal-confirm-receipt')
    @include('components.admin.deliveries.partials.modal-print-delivery')
    @include('components.admin.deliveries.partials.modal-delete')

</div>
