<!-- Tab 1: Direktori Mitra Toko (Supermarket B2B & Toko Kelontong Harian) -->
<div x-show="activeTab === 'partners'" x-cloak class="space-y-4">

    <!-- Action Bar (Consistent Filter & Add Layout) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="partnerSearch"
                    placeholder="Cari nama mitra, kode, PIC, kota..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Partner Type Filter -->
            <div class="shrink-0">
                <select x-model="partnerTypeFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="all">Semua Tipe Mitra</option>
                    <option value="supermarket">Supermarket B2B</option>
                    <option value="grocery_store">Toko Kelontong Harian</option>
                    <option value="distributor">Distributor / Agen</option>
                    <option value="retail_reseller">Reseller Eceran</option>
                </select>
            </div>
        </div>

        <!-- Add Partner Button -->
        <button type="button" @click="openCreatePartnerModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Mitra Baru</span>
        </button>
    </div>

    <!-- Partner Table Container -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Kode & Nama Mitra</th>
                    <th class="py-3.5 px-6">Tipe Mitra</th>
                    <th class="py-3.5 px-6">Kontak & WhatsApp</th>
                    <th class="py-3.5 px-6">Syarat Pembayaran</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Limit & Piutang</th>
                    <th class="py-3.5 px-6">Status</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                <template x-for="partner in filteredPartners" :key="partner.id">
                    <tr class="hover:bg-neutral-50/50 transition">
                        <!-- Kode & Nama Mitra -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-bold text-brand-espresso text-base" x-text="partner.name"></div>
                            <div class="flex items-center gap-2 mt-0.5 text-xs text-brand-warm-gray">
                                <span class="font-mono font-semibold text-brand-primary" x-text="partner.code"></span>
                                <span x-show="partner.city" class="text-neutral-400">•</span>
                                <span x-show="partner.city" x-text="partner.city"></span>
                            </div>
                            <div x-show="partner.address" class="text-xs text-brand-warm-gray mt-1 line-clamp-1" x-text="partner.address"></div>
                        </td>

                        <!-- Tipe Mitra -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-semibold text-brand-espresso text-sm sm:text-base" x-text="partner.type_label"></div>
                            <div class="text-xs text-brand-warm-gray mt-0.5">
                                <span x-show="partner.type === 'supermarket'">Supermarket Jaringan</span>
                                <span x-show="partner.type === 'grocery_store'">Toko Tradisional / Grosir</span>
                                <span x-show="partner.type === 'distributor'">Distributor Wilayah</span>
                                <span x-show="partner.type === 'retail_reseller'">Reseller Langsung</span>
                            </div>
                        </td>

                        <!-- Kontak & WhatsApp -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-semibold text-brand-espresso text-sm sm:text-base" x-text="partner.contact_person || '-'"></div>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <span class="text-xs text-brand-warm-gray" x-text="partner.formatted_phone || '-'"></span>
                                <template x-if="partner.whatsapp_url">
                                    <a :href="partner.whatsapp_url" target="_blank"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition border border-emerald-200">
                                        <i class="ti ti-brand-whatsapp text-sm"></i>
                                        <span>Chat WA</span>
                                    </a>
                                </template>
                            </div>
                        </td>

                        <!-- Syarat Pembayaran -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <div class="font-semibold text-brand-espresso text-sm sm:text-base" x-text="partner.payment_term_label"></div>
                            <div class="text-xs text-brand-warm-gray mt-0.5">
                                <span x-show="partner.payment_term_days > 0">Jatuh tempo faktur</span>
                                <span x-show="partner.payment_term_days <= 0">Bayar saat serah terima</span>
                            </div>
                        </td>

                        <!-- Limit & Piutang Berjalan (No Line Break on Rp Currency & Nominal) -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <div class="font-bold text-sm sm:text-base whitespace-nowrap flex items-center gap-1.5"
                                :class="partner.is_over_limit ? 'text-red-600' : (partner.current_receivable > 0 ? 'text-brand-espresso' : 'text-brand-warm-gray')">
                                <span class="whitespace-nowrap" x-text="partner.formatted_receivable"></span>
                                <span x-show="partner.is_over_limit" class="text-xs text-red-600 font-semibold whitespace-nowrap">(Over Limit)</span>
                            </div>
                            <div class="text-xs text-brand-warm-gray mt-0.5 whitespace-nowrap">
                                Limit: <span class="font-medium text-brand-espresso whitespace-nowrap" x-text="partner.formatted_credit_limit"></span>
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex items-center gap-1.5 font-medium text-xs sm:text-sm">
                                <span class="size-2 rounded-full" :class="partner.is_active ? 'bg-emerald-500' : 'bg-neutral-400'"></span>
                                <span :class="partner.is_active ? 'text-emerald-700' : 'text-neutral-500'"
                                    x-text="partner.is_active ? 'Aktif' : 'Nonaktif'"></span>
                            </div>
                        </td>

                        <!-- Aksi (Consistent with Roles & Products action buttons) -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="openEditPartnerModal(partner)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border"
                                    title="Edit Data Mitra">
                                    <i class="ti ti-edit text-base"></i>
                                    <span>Edit</span>
                                </button>
                                <button type="button" @click="confirmDeletePartner(partner.id)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200"
                                    title="Hapus Mitra">
                                    <i class="ti ti-trash text-base"></i>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                <!-- Empty State -->
                <tr x-show="filteredPartners.length === 0">
                    <td colspan="7" class="py-12 text-center text-brand-warm-gray">
                        <i class="ti ti-building-store text-4xl mb-2 block text-neutral-300"></i>
                        <div class="font-bold text-brand-espresso text-base">Tidak ada data mitra toko</div>
                        <div class="text-xs sm:text-sm mt-1">Coba ubah kata kunci pencarian atau filter tipe mitra toko.</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
