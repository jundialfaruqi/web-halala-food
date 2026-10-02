<div x-data="{
    search: '',
    statusFilter: 'all',
    isProcessing: false,

    // Data from backend
    units: {{ \Illuminate\Support\Js::from($units) }},

    // Delete Modal State
    showDeleteModal: false,
    deleteTarget: { id: null, name: '', short_name: '' },

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

    // Filter units computed list
    get filteredUnits() {
        return this.units.filter(unit => {
            const query = this.search.toLowerCase().trim();
            const matchesSearch = !query ||
                unit.name.toLowerCase().includes(query) ||
                unit.short_name.toLowerCase().includes(query) ||
                (unit.description && unit.description.toLowerCase().includes(query));

            const matchesStatus = this.statusFilter === 'all' ||
                (this.statusFilter === 'active' && unit.is_active) ||
                (this.statusFilter === 'inactive' && !unit.is_active);

            return matchesSearch && matchesStatus;
        });
    },

    // Toggle unit active status
    async toggleStatus(id) {
        this.isProcessing = true;
        const res = await $wire.toggleStatus(id);
        this.isProcessing = false;

        if (res.success) {
            const item = this.units.find(u => u.id === id);
            if (item) item.is_active = !item.is_active;
            this.notify(res.message, 'success');
        } else {
            this.notify(res.message, 'error');
        }
    },

    // Open delete confirmation modal
    confirmDelete(unit) {
        this.deleteTarget = { id: unit.id, name: unit.name, short_name: unit.short_name };
        this.showDeleteModal = true;
    },

    // Execute delete
    async executeDelete() {
        if (!this.deleteTarget.id) return;
        this.isProcessing = true;
        const res = await $wire.deleteUnit(this.deleteTarget.id);
        this.isProcessing = false;

        if (res.success) {
            this.units = this.units.filter(u => u.id !== this.deleteTarget.id);
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
                <span>Master Data</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary">Satuan</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight">
                Master Satuan
            </h1>
            <p class="text-sm sm:text-base text-brand-warm-gray mt-1">
                Kelola data satuan pengukuran untuk bahan baku resep dan kemasan produk jadi.
            </p>
        </div>

        @can('satuan-create')
            <div class="shrink-0">
                <a href="{{ route('admin.units.create') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer">
                    <i class="ti ti-plus text-lg"></i>
                    <span>Tambah Satuan</span>
                </a>
            </div>
        @endcan
    </div>

    <!-- Main Content Box -->
    <div class="space-y-4">

        <!-- Filter & Search Bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                    <input type="text" x-model="search" placeholder="Cari nama satuan atau simbol..."
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
            </div>

            <!-- Total Filtered Indicator -->
            <div class="text-xs sm:text-sm text-brand-warm-gray font-medium self-center">
                Menampilkan <span class="font-bold text-brand-espresso" x-text="filteredUnits.length"></span> satuan
            </div>
        </div>

        <!-- Table Listing -->
        <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">Satuan</th>
                        <th class="py-3.5 px-6">Simbol / Singkatan</th>
                        <th class="py-3.5 px-6">Keterangan</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Terdaftar</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border/60 text-sm">
                    <template x-for="unit in filteredUnits" :key="unit.id">
                        <tr class="hover:bg-neutral-50/50 transition">

                            <!-- Satuan Name -->
                            <td class="py-4 px-6 font-semibold text-brand-espresso">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-9 rounded-lg bg-brand-soft-cream/60 text-brand-primary flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                        <span x-text="unit.short_name.substring(0, 3)"></span>
                                    </div>
                                    <div>
                                        <div class="font-bold text-brand-espresso" x-text="unit.name"></div>
                                        <div class="text-xs text-brand-warm-gray font-mono mt-0.5 sm:hidden"
                                            x-text="unit.short_name"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Simbol / Singkatan -->
                            <td class="py-4 px-6">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-md bg-neutral-100 text-brand-espresso font-mono font-bold text-xs border border-neutral-200"
                                    x-text="unit.short_name">
                                </span>
                            </td>

                            <!-- Description -->
                            <td class="py-4 px-6 text-brand-warm-gray max-w-xs truncate" x-text="unit.description"></td>

                            <!-- Status -->
                            <td class="py-4 px-6">
                                @can('satuan-edit')
                                    <button type="button" @click="toggleStatus(unit.id)" :disabled="isProcessing"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition cursor-pointer select-none"
                                        :class="unit.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' :
                                            'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'"
                                        title="Klik untuk mengubah status aktif/nonaktif">
                                        <span class="size-2 rounded-full"
                                            :class="unit.is_active ? 'bg-emerald-500' : 'bg-neutral-400'"></span>
                                        <span x-text="unit.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                    </button>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold select-none"
                                        :class="unit.is_active ? 'bg-emerald-50 text-emerald-700' :
                                            'bg-neutral-100 text-neutral-600'">
                                        <span class="size-2 rounded-full"
                                            :class="unit.is_active ? 'bg-emerald-500' : 'bg-neutral-400'"></span>
                                        <span x-text="unit.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                    </span>
                                @endcan
                            </td>

                            <!-- Created At -->
                            <td class="py-4 px-6 text-brand-warm-gray text-xs" x-text="unit.created_at_human"></td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                <div class="inline-flex items-center gap-1">
                                    @can('satuan-edit')
                                        <a :href="unit.edit_url"
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer"
                                            title="Ubah Satuan">
                                            <i class="ti ti-edit text-lg"></i>
                                        </a>
                                    @endcan

                                    @can('satuan-delete')
                                        <button type="button" @click="confirmDelete(unit)"
                                            class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-red-600 hover:bg-red-50 transition cursor-pointer"
                                            title="Hapus Satuan">
                                            <i class="ti ti-trash text-lg"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>

                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="filteredUnits.length === 0">
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div
                                    class="size-16 rounded-2xl bg-neutral-100 flex items-center justify-center text-neutral-400 mb-3">
                                    <i class="ti ti-scale-off text-3xl"></i>
                                </div>
                                <h3 class="text-base font-bold text-brand-espresso">Tidak ada satuan ditemukan</h3>
                                <p class="text-sm text-brand-warm-gray mt-1">
                                    Coba ubah kata kunci pencarian atau filter status untuk menemukan satuan.
                                </p>
                                <button type="button" @click="search = ''; statusFilter = 'all'"
                                    class="mt-4 px-4 py-2 text-xs font-bold text-brand-primary hover:bg-brand-soft-cream/60 rounded-xl transition cursor-pointer">
                                    Reset Filter
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Delete Confirmation Modal -->
    <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <!-- Backdrop -->
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
                        <h3 class="text-lg font-bold text-brand-espresso">Konfirmasi Hapus Satuan</h3>
                        <p class="text-sm text-brand-warm-gray mt-0.5">Tindakan ini permanen dan tidak dapat
                            dibatalkan.</p>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-neutral-50 rounded-xl border border-neutral-100 text-sm">
                    <p class="text-brand-espresso">
                        Apakah Anda yakin ingin menghapus data satuan:
                        <strong class="font-bold text-red-600"
                            x-text="deleteTarget.name + ' (' + deleteTarget.short_name + ')'"></strong>?
                    </p>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="showDeleteModal = false" :disabled="isProcessing"
                        class="px-4 py-2.5 rounded-xl border border-brand-border text-sm font-bold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="executeDelete()" :disabled="isProcessing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl transition cursor-pointer shadow-xs">
                        <span x-show="isProcessing" class="loading loading-spinner loading-xs"></span>
                        <span>Hapus Sekarang</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

</div>
