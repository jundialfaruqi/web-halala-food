<!-- Tab 2: Tugas Pengantaran Kurir -->
<div x-show="activeTab === 'my-tasks'" x-cloak class="space-y-4">

    <!-- Action & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Filter Staf Kurir -->
            <div class="shrink-0">
                <select x-model="courierTaskFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="all">Semua Staf Kurir</option>
                    <template x-for="courier in couriers" :key="courier.id">
                        <option :value="courier.id" x-text="`Kurir: ${courier.name}`"></option>
                    </template>
                    <option value="unassigned">Belum Ditugaskan</option>
                </select>
            </div>

            <!-- Filter Status Tugas -->
            <div class="shrink-0">
                <select x-model="courierStatusFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium capitalize">
                    <option value="active">Tugas Aktif (Draft & Perjalanan)</option>
                    <option value="on_the_way">Dalam Perjalanan (On The Way)</option>
                    <option value="draft">Draft (Menunggu Berangkat)</option>
                    <option value="delivered">Riwayat Selesai (Delivered)</option>
                    <option value="all">Semua Status</option>
                </select>
            </div>
        </div>

        <span class="text-xs sm:text-sm text-brand-warm-gray" x-text="`${myAssignedDeliveries.length} antrean pengiriman`"></span>
    </div>

    <!-- Courier Task Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="delivery in myAssignedDeliveries" :key="delivery.id">
            <div class="bg-white rounded-xl border border-brand-border p-5 space-y-4 shadow-2xs flex flex-col justify-between">

                <div class="space-y-3">
                    <!-- Top: SJ Number & Status -->
                    <div class="flex items-center justify-between border-b border-brand-border/60 pb-3">
                        <div>
                            <span class="font-mono font-bold text-brand-primary text-base" x-text="delivery.delivery_number"></span>
                            <span class="text-xs text-brand-warm-gray block mt-0.5" x-text="`Kurir: ${delivery.courier_name}`"></span>
                        </div>
                        <div class="flex items-center gap-1.5 font-bold text-xs">
                            <span class="size-2 rounded-full"
                                :class="{
                                    'bg-neutral-400': delivery.status === 'draft',
                                    'bg-amber-500': delivery.status === 'on_the_way',
                                    'bg-emerald-500': delivery.status === 'delivered',
                                    'bg-red-500': delivery.status === 'cancelled'
                                }"></span>
                            <span :class="{
                                'text-neutral-600': delivery.status === 'draft',
                                'text-amber-700': delivery.status === 'on_the_way',
                                'text-emerald-700': delivery.status === 'delivered',
                                'text-red-600': delivery.status === 'cancelled'
                            }" x-text="delivery.status_label"></span>
                        </div>
                    </div>

                    <!-- Store Info -->
                    <div>
                        <h3 class="font-bold text-brand-espresso text-base" x-text="delivery.partner_name"></h3>
                        <div class="text-xs text-brand-warm-gray mt-1 flex items-start gap-1">
                            <i class="ti ti-map-pin text-sm text-brand-primary shrink-0 mt-0.5"></i>
                            <span x-text="delivery.partner_address || delivery.partner_city"></span>
                        </div>
                    </div>

                    <!-- Direct WA Chat -->
                    <template x-if="delivery.partner_wa_url">
                        <div class="pt-1">
                            <a :href="delivery.partner_wa_url" target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition border border-emerald-200">
                                <i class="ti ti-brand-whatsapp text-sm"></i>
                                <span>Hubungi Toko (WhatsApp)</span>
                            </a>
                        </div>
                    </template>

                    <!-- Items Summary -->
                    <div class="bg-neutral-50 rounded-lg p-3 border border-brand-border/60 space-y-1.5">
                        <div class="text-xs font-bold text-brand-espresso uppercase tracking-wider">Muatan Kemasan:</div>
                        <template x-for="item in delivery.items" :key="item.id">
                            <div class="text-xs text-brand-espresso flex justify-between gap-2">
                                <span class="truncate" x-text="item.full_name"></span>
                                <span class="font-mono font-bold text-brand-primary shrink-0" x-text="`${item.qty_sent} pack`"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-3 border-t border-brand-border/60 flex items-center gap-2">
                    <template x-if="delivery.status === 'draft'">
                        <button type="button" @click="dispatchDeliveryOrder(delivery.id)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl transition shadow-xs cursor-pointer">
                            <i class="ti ti-truck text-lg"></i>
                            <span>Berangkat Sekarang</span>
                        </button>
                    </template>

                    <template x-if="delivery.status === 'on_the_way'">
                        <button type="button" @click="openConfirmReceiptModal(delivery)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-xs cursor-pointer">
                            <i class="ti ti-checkbox text-lg"></i>
                            <span>Konfirmasi Serah Terima</span>
                        </button>
                    </template>

                    <template x-if="delivery.status === 'delivered'">
                        <div class="w-full text-center py-2 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-xl border border-emerald-200">
                            ✓ Telah Diterima oleh <span x-text="delivery.receiver_name || 'Toko'"></span>
                        </div>
                    </template>
                </div>

            </div>
        </template>

        <!-- Empty State -->
        <div x-show="myAssignedDeliveries.length === 0" class="col-span-full py-12 text-center text-brand-warm-gray bg-white rounded-xl border border-brand-border p-8">
            <i class="ti ti-calendar-check text-4xl mb-2 block text-neutral-300"></i>
            <div class="font-bold text-brand-espresso text-base">Tidak ada tugas pengantaran aktif</div>
            <div class="text-xs sm:text-sm mt-1">Gunakan filter di atas untuk melihat tugas staf kurir lain atau riwayat pengantaran yang sudah selesai.</div>
        </div>
    </div>

</div>
