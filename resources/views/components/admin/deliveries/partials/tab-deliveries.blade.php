<!-- Tab 1: Semua Surat Jalan Distribusi -->
<div x-show="activeTab === 'deliveries'" x-cloak class="space-y-4">

    <!-- Action Bar (Consistent Search, Filter & Add Button) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="deliverySearch"
                    placeholder="Cari no. SJ, nama mitra toko, kurir, kota..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Status Filter -->
            <div class="shrink-0">
                <select x-model="deliveryStatusFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="all">Semua Status</option>
                    <option value="draft">Draft (Dikemas)</option>
                    <option value="on_the_way">Dalam Perjalanan (Kurir)</option>
                    <option value="delivered">Telah Diterima (Selesai)</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>

            <!-- Partner Filter -->
            <div class="shrink-0">
                <select x-model="deliveryPartnerFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="all">Semua Mitra Toko</option>
                    <template x-for="partner in partners" :key="partner.id">
                        <option :value="partner.id" x-text="partner.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Add Delivery Button -->
        <button type="button" @click="openCreateDeliveryModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Buat Surat Jalan</span>
        </button>
    </div>

    <!-- Deliveries Table Container -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">No. Surat Jalan</th>
                    <th class="py-3.5 px-6">Toko Tujuan</th>
                    <th class="py-3.5 px-6">Kurir Pengantar</th>
                    <th class="py-3.5 px-6">Rincian Muatan Produk</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Total Nilai Tagihan</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Status</th>
                    <th class="py-3.5 px-6 text-right whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                <template x-for="delivery in filteredDeliveries" :key="delivery.id">
                    <tr class="hover:bg-neutral-50/50 transition">
                        <!-- No. Surat Jalan & Tanggal -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <span class="font-mono font-bold text-brand-primary text-base block" x-text="delivery.delivery_number"></span>
                            <span class="text-xs text-brand-warm-gray mt-0.5 block" x-text="delivery.created_at"></span>
                        </td>

                        <!-- Toko Tujuan -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-bold text-brand-espresso text-base" x-text="delivery.partner_name"></div>
                            <div class="text-xs text-brand-warm-gray mt-0.5">
                                <span x-text="delivery.partner_city"></span>
                                <span x-show="delivery.partner_address" class="text-neutral-400">•</span>
                                <span x-show="delivery.partner_address" class="line-clamp-1" x-text="delivery.partner_address"></span>
                            </div>
                        </td>

                        <!-- Kurir Pengantar -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <div class="font-semibold text-brand-espresso text-sm sm:text-base" x-text="delivery.courier_name"></div>
                            <div class="text-xs text-brand-warm-gray mt-0.5">
                                <span x-show="delivery.dispatched_at" x-text="'Berangkat: ' + delivery.dispatched_at"></span>
                                <span x-show="!delivery.dispatched_at" class="italic">Belum berangkat</span>
                            </div>
                        </td>

                        <!-- Rincian Muatan Produk -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-1">
                                <template x-for="item in delivery.items" :key="item.id">
                                    <div class="text-xs text-brand-espresso flex items-center justify-between gap-2">
                                        <span class="font-medium" x-text="item.full_name"></span>
                                        <span class="font-mono font-semibold text-brand-primary shrink-0"
                                            x-text="delivery.status === 'delivered' ? `${item.qty_accepted}/${item.qty_sent} pack` : `${item.qty_sent} pack`"></span>
                                    </div>
                                </template>
                            </div>
                        </td>

                        <!-- Total Nilai Tagihan (No Line Break) -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <div class="font-bold text-brand-espresso text-sm sm:text-base whitespace-nowrap" x-text="delivery.formatted_total_amount"></div>
                            <div class="text-xs text-brand-warm-gray mt-0.5 whitespace-nowrap" x-text="`${delivery.total_sent_qty} pack muatan`"></div>
                        </td>

                        <!-- Status Pengantaran -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <div class="flex items-center gap-1.5 font-medium text-xs sm:text-sm">
                                <span class="size-2 rounded-full"
                                    :class="{
                                        'bg-neutral-400': delivery.status === 'draft',
                                        'bg-amber-500': delivery.status === 'on_the_way',
                                        'bg-emerald-500': delivery.status === 'delivered',
                                        'bg-red-500': delivery.status === 'cancelled'
                                    }"></span>
                                <span :class="{
                                    'text-neutral-600 font-semibold': delivery.status === 'draft',
                                    'text-amber-700 font-bold': delivery.status === 'on_the_way',
                                    'text-emerald-700 font-bold': delivery.status === 'delivered',
                                    'text-red-600 font-semibold': delivery.status === 'cancelled'
                                }" x-text="delivery.status_label"></span>
                            </div>
                            <div x-show="delivery.receiver_name" class="text-xs text-brand-warm-gray mt-1">
                                Penerima: <span class="font-medium text-brand-espresso" x-text="delivery.receiver_name"></span>
                            </div>
                        </td>

                        <!-- Aksi -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- 1. Tombol Berangkatkan (jika status draft) -->
                                <template x-if="delivery.status === 'draft'">
                                    <button type="button" @click="dispatchDeliveryOrder(delivery.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 transition cursor-pointer border border-amber-200"
                                        title="Kurir Berangkat Antar Barang">
                                        <i class="ti ti-truck text-base"></i>
                                        <span>Berangkat</span>
                                    </button>
                                </template>

                                <!-- 2. Tombol Konfirmasi Serah Terima (jika draft / on_the_way) -->
                                <template x-if="delivery.status === 'on_the_way' || delivery.status === 'draft'">
                                    <button type="button" @click="openConfirmReceiptModal(delivery)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition cursor-pointer border border-emerald-200"
                                        title="Konfirmasi Serah Terima di Toko">
                                        <i class="ti ti-checkbox text-base"></i>
                                        <span>Serah Terima</span>
                                    </button>
                                </template>

                                <!-- 3. Cetak Surat Jalan -->
                                <button type="button" @click="openPrintDeliveryModal(delivery)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border"
                                    title="Cetak Surat Jalan (Delivery Order)">
                                    <i class="ti ti-printer text-base"></i>
                                    <span>Cetak</span>
                                </button>

                                <!-- 4. Edit (Hanya jika Draft) -->
                                <template x-if="delivery.status === 'draft'">
                                    <button type="button" @click="openEditDeliveryModal(delivery)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border"
                                        title="Edit Surat Jalan">
                                        <i class="ti ti-edit text-base"></i>
                                        <span>Edit</span>
                                    </button>
                                </template>

                                <!-- 5. Batal / Hapus -->
                                <template x-if="delivery.status !== 'delivered' && delivery.status !== 'cancelled'">
                                    <button type="button" @click="cancelDeliveryOrder(delivery.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200"
                                        title="Batalkan Surat Jalan">
                                        <i class="ti ti-x text-base"></i>
                                        <span>Batal</span>
                                    </button>
                                </template>

                                <template x-if="delivery.status === 'cancelled' || delivery.status === 'draft'">
                                    <button type="button" @click="confirmDeleteDelivery(delivery.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200"
                                        title="Hapus Surat Jalan">
                                        <i class="ti ti-trash text-base"></i>
                                        <span>Hapus</span>
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>

                <!-- Empty State -->
                <tr x-show="filteredDeliveries.length === 0">
                    <td colspan="7" class="py-12 text-center text-brand-warm-gray">
                        <i class="ti ti-truck text-4xl mb-2 block text-neutral-300"></i>
                        <div class="font-bold text-brand-espresso text-base">Tidak ada data surat jalan pengiriman</div>
                        <div class="text-xs sm:text-sm mt-1">Coba ubah kata kunci pencarian atau filter status pengantaran.</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>
