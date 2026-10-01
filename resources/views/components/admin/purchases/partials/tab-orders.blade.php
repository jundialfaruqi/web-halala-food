<!-- Tab 1: Semua Pesanan Pembelian (PO) -->
<div x-show="activeTab === 'orders'" x-cloak class="space-y-4">

    <!-- Action Bar (Consistent Search, Filter & Add Button) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="orderSearch"
                    placeholder="Cari no. PO, nama supplier, catatan..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Status Filter -->
            <div class="shrink-0">
                <select x-model="orderStatusFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="all">Semua Status</option>
                    <option value="draft">Draft (Rencana)</option>
                    <option value="ordered">Dipesan (Menunggu Barang)</option>
                    <option value="received">Diterima di Gudang</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>

            <!-- Supplier Filter -->
            <div class="shrink-0">
                <select x-model="orderSupplierFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="all">Semua Supplier</option>
                    <template x-for="supplier in suppliers" :key="supplier.id">
                        <option :value="supplier.id" x-text="supplier.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Add PO Button -->
        <button type="button" @click="openCreateOrderModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Buat PO Baru</span>
        </button>
    </div>

    <!-- Orders Table Container -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6 whitespace-nowrap">No. PO & Tanggal</th>
                    <th class="py-3.5 px-6">Supplier</th>
                    <th class="py-3.5 px-6">Bahan Baku Dipesan</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Total Pembelian</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Status Pesanan</th>
                    <th class="py-3.5 px-6 whitespace-nowrap">Status Bayar</th>
                    <th class="py-3.5 px-6 text-right whitespace-nowrap">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                <template x-for="po in filteredOrders" :key="po.id">
                    <tr class="hover:bg-neutral-50/50 transition">
                        <!-- No. PO & Tanggal -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <span class="font-mono font-bold text-brand-primary text-base block" x-text="po.po_number"></span>
                            <div class="text-xs text-brand-warm-gray mt-1 flex flex-col gap-0.5">
                                <span>Pesan: <strong class="text-brand-espresso" x-text="po.formatted_order_date"></strong></span>
                                <span x-show="po.due_date">Tempo: <span x-text="po.formatted_due_date"></span></span>
                            </div>
                        </td>

                        <!-- Supplier -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-bold text-brand-espresso text-base" x-text="po.supplier_name"></div>
                            <div class="text-xs text-brand-warm-gray mt-1 flex items-center gap-2">
                                <span class="font-mono text-neutral-500" x-text="po.supplier_code"></span>
                                <span class="text-neutral-300">•</span>
                                <span x-text="po.supplier_payment_terms"></span>
                                <template x-if="po.supplier_wa_url">
                                    <a :href="po.supplier_wa_url" target="_blank" class="text-emerald-600 hover:text-emerald-700 font-medium inline-flex items-center gap-1" title="Chat WhatsApp">
                                        <i class="ti ti-brand-whatsapp text-sm"></i>
                                    </a>
                                </template>
                            </div>
                        </td>

                        <!-- Bahan Baku Dipesan -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-1.5 min-w-[200px]">
                                <template x-for="item in po.items" :key="item.id">
                                    <div class="text-xs text-brand-espresso flex items-center justify-between gap-3">
                                        <span class="font-medium truncate" x-text="item.material_name"></span>
                                        <span class="font-mono font-semibold text-brand-primary shrink-0 whitespace-nowrap"
                                            x-text="po.status === 'received' ? `${item.qty_received}/${item.qty_ordered} ${item.material_unit}` : `${item.qty_ordered} ${item.material_unit}`"></span>
                                    </div>
                                </template>
                            </div>
                        </td>

                        <!-- Total Nilai Pembelian -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <span class="font-bold text-brand-espresso text-base block" x-text="po.formatted_total_amount"></span>
                            <span class="text-xs text-brand-warm-gray mt-0.5 block" x-text="`${po.items_count} item bahan`"></span>
                        </td>

                        <!-- Status Pesanan (Subtle Dot Indicator) -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <template x-if="po.status === 'draft'">
                                <div>
                                    <div class="flex items-center gap-1.5 text-neutral-600 font-medium">
                                        <span class="size-2 rounded-full bg-neutral-400"></span>
                                        <span>Draft</span>
                                    </div>
                                    <span class="text-xs text-neutral-400 mt-0.5 block">Rencana Pengadaan</span>
                                </div>
                            </template>

                            <template x-if="po.status === 'ordered'">
                                <div>
                                    <div class="flex items-center gap-1.5 text-amber-700 font-bold">
                                        <span class="size-2 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span>Dipesan</span>
                                    </div>
                                    <span class="text-xs text-amber-600/80 mt-0.5 block">Menunggu Pengiriman</span>
                                </div>
                            </template>

                            <template x-if="po.status === 'received'">
                                <div>
                                    <div class="flex items-center gap-1.5 text-emerald-700 font-bold">
                                        <span class="size-2 rounded-full bg-emerald-500"></span>
                                        <span>Diterima Gudang</span>
                                    </div>
                                    <span class="text-xs text-emerald-600/80 mt-0.5 block" x-text="po.received_at ? 'Tgl ' + po.received_at : 'Stok bertambah'"></span>
                                </div>
                            </template>

                            <template x-if="po.status === 'cancelled'">
                                <div>
                                    <div class="flex items-center gap-1.5 text-rose-600 font-medium">
                                        <span class="size-2 rounded-full bg-rose-400"></span>
                                        <span>Dibatalkan</span>
                                    </div>
                                </div>
                            </template>
                        </td>

                        <!-- Status Bayar -->
                        <td class="py-4 px-6 align-top whitespace-nowrap">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg inline-block"
                                :class="{
                                    'bg-amber-50 text-amber-800 border border-amber-200': po.payment_status === 'unpaid',
                                    'bg-blue-50 text-blue-800 border border-blue-200': po.payment_status === 'partial',
                                    'bg-emerald-50 text-emerald-800 border border-emerald-200': po.payment_status === 'paid'
                                }"
                                x-text="po.payment_status_label">
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <!-- Print PO Slip -->
                                <button type="button" @click="openPrintPoModal(po)"
                                    class="size-8.5 rounded-lg flex items-center justify-center text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer"
                                    title="Cetak Purchase Order">
                                    <i class="ti ti-printer text-lg"></i>
                                </button>

                                <!-- Receive Goods Button (Draft or Ordered) -->
                                <template x-if="['draft', 'ordered'].includes(po.status)">
                                    <button type="button" @click="openReceiveModal(po)"
                                        class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition flex items-center gap-1 cursor-pointer border border-emerald-200"
                                        title="Konfirmasi Penerimaan Barang di Gudang">
                                        <i class="ti ti-package-import text-sm"></i>
                                        <span>Terima Barang</span>
                                    </button>
                                </template>

                                <!-- Edit PO Button (Draft or Ordered) -->
                                <template x-if="['draft', 'ordered'].includes(po.status)">
                                    <button type="button" @click="openEditOrderModal(po)"
                                        class="size-8.5 rounded-lg flex items-center justify-center text-brand-espresso hover:text-amber-600 hover:bg-amber-50 transition cursor-pointer"
                                        title="Edit PO">
                                        <i class="ti ti-edit text-lg"></i>
                                    </button>
                                </template>

                                <!-- Cancel PO Button (Draft or Ordered) -->
                                <template x-if="['draft', 'ordered'].includes(po.status)">
                                    <button type="button" @click="cancelOrder(po.id)"
                                        class="size-8.5 rounded-lg flex items-center justify-center text-brand-espresso hover:text-amber-700 hover:bg-amber-50 transition cursor-pointer"
                                        title="Batalkan PO">
                                        <i class="ti ti-ban text-lg"></i>
                                    </button>
                                </template>

                                <!-- Delete PO Button (Draft or Cancelled) -->
                                <template x-if="['draft', 'cancelled'].includes(po.status)">
                                    <button type="button" @click="confirmDeleteOrder(po.id)"
                                        class="size-8.5 rounded-lg flex items-center justify-center text-brand-espresso hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                        title="Hapus PO">
                                        <i class="ti ti-trash text-lg"></i>
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>

                <!-- Empty State -->
                <tr x-show="filteredOrders.length === 0">
                    <td colspan="7" class="py-12 text-center text-brand-warm-gray">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i class="ti ti-file-invoice-off text-4xl text-neutral-300"></i>
                            <span class="text-base font-medium">Tidak ada data pesanan pembelian yang sesuai filter.</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
