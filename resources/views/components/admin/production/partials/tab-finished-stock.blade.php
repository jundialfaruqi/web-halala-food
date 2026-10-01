<!-- TAB 3: KARTU STOK MUTASI BARANG JADI -->
<div x-show="activeTab === 'finished-stock'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
            <input type="text" x-model="finishedStockSearch" placeholder="Cari mutasi barang jadi atau produk..."
                class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
        </div>

        <!-- Opname Adjustment Button -->
        <button type="button" @click="openFinishedStockAdjustmentModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-espresso hover:bg-brand-espresso/90 text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-adjustments-horizontal text-lg"></i>
            <span>Penyesuaian Stok Barang Jadi</span>
        </button>
    </div>

    <!-- Finished Stock Mutations Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Waktu & Varian Produk</th>
                    <th class="py-3.5 px-6">Tipe & Referensi</th>
                    <th class="py-3.5 px-6 text-center">Perubahan Mutasi</th>
                    <th class="py-3.5 px-6 text-right">Saldo Stok Siap Jual</th>
                    <th class="py-3.5 px-6">Petugas / Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($finishedStockMutations as $mut)
                    <tr x-show="!finishedStockSearch || $el.textContent.toLowerCase().includes(finishedStockSearch.toLowerCase())"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Date & Product Variant -->
                        <td class="py-4 px-6 align-top">
                            <div>
                                <span class="font-bold text-brand-espresso text-base block">
                                    {{ $mut->productVariant?->product?->name }}
                                </span>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-brand-warm-gray">
                                    <span class="font-semibold text-brand-primary">{{ $mut->productVariant?->name }}</span>
                                    <span>•</span>
                                    <span>{{ $mut->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Reference Type -->
                        <td class="py-4 px-6 align-top">
                            <div>
                                @if ($mut->reference_type === 'production_in')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800">
                                        <i class="ti ti-flame"></i> Hasil Masak Selesai
                                    </span>
                                @elseif ($mut->reference_type === 'initial_stock')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800">
                                        <i class="ti ti-database"></i> Saldo Awal Display
                                    </span>
                                @elseif ($mut->reference_type === 'pos_sale')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">
                                        <i class="ti ti-cash"></i> Penjualan Kasir
                                    </span>
                                @elseif ($mut->reference_type === 'delivery_out')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-800">
                                        <i class="ti ti-truck"></i> Pengantaran Kurir Toko
                                    </span>
                                @elseif ($mut->reference_type === 'adjustment')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-800">
                                        <i class="ti ti-adjustments"></i> Stok Opname
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-neutral-100 text-neutral-800">
                                        {{ $mut->reference_type }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Quantity In / Out -->
                        <td class="py-4 px-6 align-top text-center">
                            @if ($mut->type === 'in')
                                <span class="font-mono font-bold text-emerald-600 text-base">
                                    +{{ $mut->quantity }} pack
                                </span>
                            @else
                                <span class="font-mono font-bold text-red-600 text-base">
                                    -{{ $mut->quantity }} pack
                                </span>
                            @endif
                        </td>

                        <!-- Remaining Stock Balance -->
                        <td class="py-4 px-6 align-top text-right font-mono font-extrabold text-brand-espresso text-base">
                            {{ $mut->current_stock }} pack
                        </td>

                        <!-- User & Notes -->
                        <td class="py-4 px-6 align-top text-xs text-brand-warm-gray">
                            <span class="font-semibold text-brand-espresso block">
                                {{ $mut->user?->name ?? 'Sistem' }}
                            </span>
                            <span class="mt-0.5 block line-clamp-1">
                                {{ $mut->notes ?: '-' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-brand-warm-gray">
                            <i class="ti ti-history-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada riwayat mutasi stok barang jadi</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
