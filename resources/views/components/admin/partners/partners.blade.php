<div x-data="{
    activeTab: (function() {
        const hash = window.location.hash.replace('#', '');
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        if (['partners', 'barcodes'].includes(hash)) return hash;
        if (['partners', 'barcodes'].includes(queryTab)) return queryTab;
        return 'partners';
    })(),

    // Search & Filter Models
    partnerSearch: '',
    partnerTypeFilter: 'all',
    isProcessing: false,

    init() {
        this.$watch('activeTab', (tab) => {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.replaceState(null, '', url.toString());
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (['partners', 'barcodes'].includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });

        // Initialize first variant for barcode generator
        if (this.productVariants.length > 0) {
            this.selectedVariantId = this.productVariants[0].id;
            this.barcodeCode = this.productVariants[0].barcode || '';
            this.currentBarcodeSvg = this.productVariants[0].barcode_svg || '';
        }
    },

    // Datasets from Backend
    partners: {{ \Illuminate\Support\Js::from($partners) }},
    productVariants: {{ \Illuminate\Support\Js::from($productVariants) }},

    // Computed Filtered Partners
    get filteredPartners() {
        return this.partners.filter(partner => {
            const matchesSearch = !this.partnerSearch ||
                partner.name.toLowerCase().includes(this.partnerSearch.toLowerCase()) ||
                partner.code.toLowerCase().includes(this.partnerSearch.toLowerCase()) ||
                (partner.contact_person && partner.contact_person.toLowerCase().includes(this.partnerSearch.toLowerCase())) ||
                (partner.city && partner.city.toLowerCase().includes(this.partnerSearch.toLowerCase()));

            const matchesType = this.partnerTypeFilter === 'all' || partner.type === this.partnerTypeFilter;

            return matchesSearch && matchesType;
        });
    },

    // Partner Modal State
    showPartnerModal: false,
    partnerModalTitle: 'Tambah Mitra Toko Baru',
    partnerForm: {
        id: null,
        code: '',
        name: '',
        type: 'grocery_store',
        contact_person: '',
        phone: '',
        whatsapp_number: '',
        address: '',
        city: '',
        payment_term_days: 7,
        credit_limit: 3000000,
        current_receivable: 0,
        notes: '',
        is_active: true
    },
    partnerErrors: {},
    partnerFormError: '',

    // Barcode Generator State
    selectedVariantId: null,
    barcodeCode: '',
    currentBarcodeSvg: '',
    paperFormat: 'thermal_40x30', // thermal_40x30, thermal_50x30, a4_grid_24
    printQuantity: 24,
    customExpText: '',
    showBrandName: true,
    showVariantName: true,
    showNetWeight: true,
    showRetailPrice: true,
    showWholesalePrice: false,
    showBarcodeText: true,
    showPrintModal: false,

    get selectedVariant() {
        return this.productVariants.find(v => v.id == this.selectedVariantId) || this.productVariants[0] || null;
    },

    // Delete Modal State
    showDeleteModal: false,
    deleteTargetId: null,
    deleteTargetDescription: '',

    // Toast Dispatcher
    notify(message, type = 'success') {
        if (typeof window.toast === 'function') {
            window.toast(message, type);
        } else {
            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type } }));
        }
    },

    // --- Partner Methods ---
    openCreatePartnerModal() {
        const nextNumber = this.partners.length + 1;
        const autoCode = 'MTR-' + String(nextNumber).padStart(3, '0');

        this.partnerForm = {
            id: null,
            code: autoCode,
            name: '',
            type: 'grocery_store',
            contact_person: '',
            phone: '',
            whatsapp_number: '',
            address: '',
            city: '',
            payment_term_days: 7,
            credit_limit: 3000000,
            current_receivable: 0,
            notes: '',
            is_active: true
        };
        this.partnerModalTitle = 'Tambah Mitra Toko Baru';
        this.partnerErrors = {};
        this.partnerFormError = '';
        this.showPartnerModal = true;
    },

    openEditPartnerModal(partner) {
        this.partnerForm = {
            id: partner.id,
            code: partner.code,
            name: partner.name,
            type: partner.type,
            contact_person: partner.contact_person || '',
            phone: partner.phone || '',
            whatsapp_number: partner.whatsapp_number || '',
            address: partner.address || '',
            city: partner.city || '',
            payment_term_days: partner.payment_term_days || 0,
            credit_limit: partner.credit_limit || 0,
            current_receivable: partner.current_receivable || 0,
            notes: partner.notes || '',
            is_active: Boolean(partner.is_active)
        };
        this.partnerModalTitle = 'Edit Data Mitra: ' + partner.name;
        this.partnerErrors = {};
        this.partnerFormError = '';
        this.showPartnerModal = true;
    },

    async submitPartner() {
        this.partnerErrors = {};
        this.partnerFormError = '';
        this.isProcessing = true;

        const res = await $wire.savePartner(this.partnerForm.id, this.partnerForm);
        this.isProcessing = false;

        if (res.success) {
            this.showPartnerModal = false;
            this.notify(res.message, 'success');
        } else {
            if (res.errors) {
                this.partnerErrors = res.errors;
            }
            this.partnerFormError = res.message || 'Silakan periksa kembali formulir yang diisi.';
            this.notify(this.partnerFormError, 'error');
        }
    },

    confirmDeletePartner(id) {
        const partner = this.partners.find(p => p.id === id);
        if (!partner) return;

        this.deleteTargetId = partner.id;
        this.deleteTargetDescription = `Apakah Anda yakin ingin menghapus mitra '${partner.name}' (${partner.code})? Tindakan ini tidak dapat dibatalkan.`;
        this.showDeleteModal = true;
    },

    async executeDelete() {
        if (!this.deleteTargetId) return;

        this.isProcessing = true;
        const res = await $wire.deletePartner(this.deleteTargetId);
        this.isProcessing = false;

        if (res.success) {
            this.showDeleteModal = false;
            this.notify(res.message, 'success');
        } else {
            this.notify(res.message || 'Gagal menghapus data mitra.', 'error');
        }
    },

    // --- Barcode Methods ---
    onVariantChange() {
        const variant = this.selectedVariant;
        if (variant) {
            this.barcodeCode = variant.barcode || '';
            this.currentBarcodeSvg = variant.barcode_svg || '';
        }
    },

    async debounceUpdateBarcode() {
        if (!this.barcodeCode) {
            this.currentBarcodeSvg = '';
            return;
        }
        const svg = await $wire.getBarcodeSvg(this.barcodeCode);
        this.currentBarcodeSvg = svg;
    },

    async saveBarcodeToVariant() {
        if (!this.selectedVariantId || !this.barcodeCode) {
            this.notify('Pilih varian produk dan masukkan kode barcode.', 'error');
            return;
        }

        this.isProcessing = true;
        const res = await $wire.updateVariantBarcode(this.selectedVariantId, this.barcodeCode);
        this.isProcessing = false;

        if (res.success) {
            this.notify(res.message, 'success');
            // Update local state
            const target = this.productVariants.find(v => v.id == this.selectedVariantId);
            if (target) {
                target.barcode = this.barcodeCode;
                target.barcode_svg = this.currentBarcodeSvg;
            }
        } else {
            this.notify(res.message, 'error');
        }
    },

    openPrintPreview() {
        if (!this.barcodeCode) {
            this.notify('Harap masukkan kode barcode sebelum mencetak label.', 'error');
            return;
        }
        if (this.printQuantity < 1) {
            this.printQuantity = 1;
        }
        this.showPrintModal = true;
    },

    triggerPrint() {
        window.print();
    }
}" class="space-y-6">

    <!-- Header Section (Clean standard pattern matching Roles & Products) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Mitra Toko & Barcode
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola direktori mitra supermarket B2B, toko kelontong harian, plafon piutang, dan cetak label barcode kemasan.
            </p>
        </div>
    </div>

    <!-- Main Navigation Bar (Clean Underline Tab Pattern) -->
    <div class="space-y-6">
        <div class="flex border-b border-brand-border gap-6 sm:gap-8 overflow-x-auto">
            <!-- 1. Tab Direktori Mitra Toko -->
            <button type="button" @click="activeTab = 'partners'"
                :class="activeTab === 'partners' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-building-store text-xl"></i>
                <span>Direktori Mitra Toko</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :class="activeTab === 'partners' ? 'bg-brand-soft-cream text-brand-primary' : 'bg-neutral-100 text-brand-warm-gray'">
                    {{ $totalPartners }}
                </span>
            </button>

            <!-- 2. Tab Generator & Cetak Barcode -->
            <button type="button" @click="activeTab = 'barcodes'"
                :class="activeTab === 'barcodes' ? 'border-b-2 border-brand-primary text-brand-primary font-bold' : 'border-b-2 border-transparent text-brand-warm-gray hover:text-brand-espresso font-medium'"
                class="flex items-center gap-2 pb-3.5 text-base sm:text-lg transition cursor-pointer shrink-0">
                <i class="ti ti-barcode text-xl"></i>
                <span>Generator & Cetak Barcode</span>
            </button>
        </div>

        <!-- TAB CONTENT PARTIALS -->
        @include('components.admin.partners.partials.tab-partners')
        @include('components.admin.partners.partials.tab-barcodes')

    </div>

    <!-- MODALS -->
    @include('components.admin.partners.partials.modal-partner')
    @include('components.admin.partners.partials.modal-print-preview')
    @include('components.admin.partners.partials.modal-delete')

</div>
