<!-- TAB 4: LAPORAN BAHAN RUSAK (WASTE & SPOILAGE) -->
<div x-show="activeTab === 'waste'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="wasteSearch" placeholder="Cari kode waste, bahan baku, atau alasan..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Total Loss Summary Badge -->
            <div class="px-4 py-2 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2 text-xs sm:text-sm font-bold text-red-700 shrink-0">
                <i class="ti ti-flame text-base"></i>
                <span>Total Kerugian Bahan: Rp {{ number_format($totalWasteCost, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Add Waste Log Button -->
        <button type="button" @click="openCreateWasteModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Catat Bahan Rusak / Waste</span>
        </button>
    </div>

    <!-- Waste Logs Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Kode & Waktu Laporan</th>
                    <th class="py-3.5 px-6">Bahan Baku Rusak</th>
                    <th class="py-3.5 px-6">Alasan Kerusakan</th>
                    <th class="py-3.5 px-6 text-right">Estimasi Kerugian (Rp)</th>
                    <th class="py-3.5 px-6">Pelapor & Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($wasteLogs as $waste)
                    <tr x-show="!wasteSearch || $el.textContent.toLowerCase().includes(wasteSearch.toLowerCase())"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Waste Code & Date -->
                        <td class="py-4 px-6 align-top">
                            <div>
                                <span class="font-mono font-bold text-brand-espresso text-sm block">
                                    {{ $waste->code }}
                                </span>
                                <span class="text-xs text-brand-warm-gray mt-0.5 block">
                                    {{ $waste->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </td>

                        <!-- Raw Material & Quantity -->
                        <td class="py-4 px-6 align-top">
                            <div>
                                <span class="font-bold text-brand-espresso text-base block">
                                    {{ $waste->rawMaterial?->name }}
                                </span>
                                <span class="font-mono font-bold text-red-600 text-sm mt-0.5 block">
                                    -{{ number_format((float) $waste->quantity, 0, ',', '.') }} {{ $waste->rawMaterial?->unit }}
                                </span>
                            </div>
                        </td>

                        <!-- Reason Badge -->
                        <td class="py-4 px-6 align-top">
                            @if ($waste->reason === 'expired')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    <i class="ti ti-calendar-time"></i> Kedaluwarsa
                                </span>
                            @elseif ($waste->reason === 'damaged')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                    <i class="ti ti-alert-triangle"></i> Kemasan Rusak
                                </span>
                            @elseif ($waste->reason === 'spilled')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                    <i class="ti ti-droplet"></i> Tumpah di Dapur
                                </span>
                            @elseif ($waste->reason === 'failed_batch')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                                    <i class="ti ti-x"></i> Gagal Adonan
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-neutral-100 text-neutral-800">
                                    {{ $waste->reason }}
                                </span>
                            @endif
                        </td>

                        <!-- Cost Loss -->
                        <td class="py-4 px-6 align-top text-right font-mono font-extrabold text-red-600 text-base">
                            {{ $waste->formattedCostLoss() }}
                        </td>

                        <!-- Reporter & Notes -->
                        <td class="py-4 px-6 align-top text-xs text-brand-warm-gray">
                            <span class="font-semibold text-brand-espresso block">
                                {{ $waste->reporter?->name ?? 'Staf Dapur' }}
                            </span>
                            <span class="mt-0.5 block line-clamp-2">
                                {{ $waste->notes ?: '-' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-brand-warm-gray">
                            <i class="ti ti-mood-smile text-3xl mb-2 text-emerald-600 block"></i>
                            <p class="font-bold text-base text-brand-espresso">Tidak ada catatan bahan rusak</p>
                            <p class="text-sm mt-1">Gudang dapur Halala Food berjalan bersih dan efisien.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
