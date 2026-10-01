<!-- Tab 2: Direktori Supplier -->
<div x-show="activeTab === 'suppliers'" x-cloak class="space-y-4">

    <!-- Action Bar (Search, Filter & Add Supplier Button) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="supplierSearch"
                    placeholder="Cari kode, nama supplier, PIC, kota..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Status Filter -->
            <div class="shrink-0">
                <select x-model="supplierStatusFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="all">Semua Status</option>
                    <option value="active">Supplier Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
        </div>

        <!-- Add Supplier Button -->
        <button type="button" @click="openCreateSupplierModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Supplier</span>
        </button>
    </div>

    <!-- Suppliers Table Container -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">Supplier</th>
                    <th class="py-3.5 px-6">Kontak & PIC</th>
                    <th class="py-3.5 px-6">Kota & Alamat</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Syarat Pembayaran</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Total Pembelian</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-6 text-right whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                <template x-for="supplier in filteredSuppliers" :key="supplier.id">
                    <tr class="hover:bg-neutral-50/50 transition">
                        <!-- Supplier (Kode & Nama) -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-bold text-brand-espresso text-base" x-text="supplier.name"></div>
                            <div class="text-xs text-brand-warm-gray mt-1 flex items-center gap-1.5">
                                <span class="font-mono font-semibold text-brand-primary" x-text="supplier.code"></span>
                                <span class="text-neutral-300">•</span>
                                <span x-text="`${supplier.purchase_orders_count} pesanan PO`"></span>
                            </div>
                            <p x-show="supplier.notes" class="text-xs text-neutral-500 mt-1 line-clamp-1 italic" x-text="supplier.notes"></p>
                        </td>

                        <!-- Kontak & PIC -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-medium text-brand-espresso text-sm sm:text-base" x-text="supplier.contact_person || '-'"></div>
                            <div class="text-xs text-brand-warm-gray mt-1 flex items-center gap-2">
                                <span x-text="supplier.formatted_phone || supplier.phone || '-'"></span>
                                <template x-if="supplier.whatsapp_url">
                                    <a :href="supplier.whatsapp_url" target="_blank"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold transition"
                                        title="Chat via WhatsApp">
                                        <i class="ti ti-brand-whatsapp text-xs"></i>
                                        <span>WA</span>
                                    </a>
                                </template>
                            </div>
                            <div x-show="supplier.email" class="text-xs text-neutral-400 mt-0.5" x-text="supplier.email"></div>
                        </td>

                        <!-- Kota & Alamat -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-semibold text-brand-espresso text-sm sm:text-base" x-text="supplier.city || '-'"></div>
                            <div class="text-xs text-brand-warm-gray mt-0.5 line-clamp-2" x-text="supplier.address || '-'"></div>
                        </td>

                        <!-- Syarat Pembayaran -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <span class="font-medium text-brand-espresso block" x-text="supplier.payment_term_label"></span>
                            <span class="text-xs text-brand-warm-gray mt-0.5 block"
                                x-text="supplier.payment_terms_days > 0 ? `Jatuh tempo ${supplier.payment_terms_days} hari setelah PO` : 'Bayar lunas di tempat'"></span>
                        </td>

                        <!-- Total Pembelian -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <span class="font-bold text-brand-espresso text-base block" x-text="supplier.formatted_total_purchases"></span>
                            <span class="text-xs text-brand-warm-gray mt-0.5 block">Total PO Selesai</span>
                        </td>

                        <!-- Status Aktif (Subtle Dot) -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <template x-if="supplier.is_active">
                                <div class="flex items-center gap-1.5 text-emerald-700 font-medium">
                                    <span class="size-2 rounded-full bg-emerald-500"></span>
                                    <span>Aktif</span>
                                </div>
                            </template>
                            <template x-if="!supplier.is_active">
                                <div class="flex items-center gap-1.5 text-neutral-500 font-medium">
                                    <span class="size-2 rounded-full bg-neutral-400"></span>
                                    <span>Nonaktif</span>
                                </div>
                            </template>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" @click="openEditSupplierModal(supplier)"
                                    class="size-8.5 rounded-lg flex items-center justify-center text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer"
                                    title="Edit Supplier">
                                    <i class="ti ti-edit text-lg"></i>
                                </button>
                                <button type="button" @click="confirmDeleteSupplier(supplier.id)"
                                    class="size-8.5 rounded-lg flex items-center justify-center text-brand-espresso hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                    title="Hapus Supplier">
                                    <i class="ti ti-trash text-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                <!-- Empty State -->
                <tr x-show="filteredSuppliers.length === 0">
                    <td colspan="7" class="py-12 text-center text-brand-warm-gray">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i class="ti ti-building-off text-4xl text-neutral-300"></i>
                            <span class="text-base font-medium">Tidak ada data supplier yang sesuai filter.</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
