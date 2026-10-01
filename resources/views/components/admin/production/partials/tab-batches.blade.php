<!-- TAB 1: RENCANA & BATCH PRODUKSI DAPUR -->
<div x-show="activeTab === 'batches'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="batchSearch" placeholder="Cari nomor batch, produk, atau juru masak..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Status Filter -->
            <div class="shrink-0">
                <select x-model="batchStatusFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                    <option value="all">Semua Status Batch</option>
                    <option value="draft">Rencana (Draft)</option>
                    <option value="in_progress">Sedang Dimasak (In Progress)</option>
                    <option value="completed">Selesai (Completed)</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>
        </div>

        <!-- Create Batch Button -->
        <button type="button" @click="openCreateBatchModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Buat Rencana Produksi</span>
        </button>
    </div>

    <!-- Batches Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Batch & Produk</th>
                    <th class="py-3.5 px-6">Juru Masak (Kitchen)</th>
                    <th class="py-3.5 px-6">Target & Hasil Output</th>
                    <th class="py-3.5 px-6">Status & HPP Aktual</th>
                    <th class="py-3.5 px-6 text-right">Aksi Dapur</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($batches as $batch)
                    <tr x-show="(!batchSearch || $el.textContent.toLowerCase().includes(batchSearch.toLowerCase())) && (batchStatusFilter === 'all' || '{{ $batch->status }}' === batchStatusFilter)"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Batch Number & Product Target -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex items-start gap-3">
                                <div class="size-11 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                                    <i class="ti ti-flame text-2xl"></i>
                                </div>
                                <div>
                                    <span class="font-mono font-bold text-brand-espresso text-base block">
                                        {{ $batch->batch_number }}
                                    </span>
                                    <span class="font-bold text-brand-primary text-sm block mt-0.5">
                                        {{ $batch->recipe?->productVariant?->product?->name }}
                                    </span>
                                    <span class="text-xs px-2 py-0.5 bg-neutral-100 text-brand-espresso font-semibold rounded mt-0.5 inline-block">
                                        {{ $batch->recipe?->productVariant?->name }}
                                    </span>
                                    @if ($batch->notes)
                                        <p class="text-xs text-brand-warm-gray mt-1 max-w-xs line-clamp-1">
                                            {{ $batch->notes }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Cook Info -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-1">
                                <div class="flex items-center gap-1.5 font-bold text-brand-espresso text-sm">
                                    <i class="ti ti-user text-brand-primary"></i>
                                    <span>{{ $batch->user?->name ?? 'Belum Ditugaskan' }}</span>
                                </div>
                                <span class="text-xs text-brand-warm-gray block">
                                    Dibuat: {{ $batch->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                        </td>

                        <!-- Target & Actual Output -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-1">
                                <div class="text-xs text-brand-warm-gray">
                                    Rencana: <strong class="text-brand-espresso font-mono">{{ $batch->planned_qty }} pack</strong>
                                </div>
                                @if ($batch->status === 'completed')
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="px-2 py-0.5 bg-green-100 text-green-800 font-bold rounded font-mono">
                                            Bagus: {{ $batch->actual_qty_good }}
                                        </span>
                                        @if ($batch->actual_qty_bad > 0)
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 font-bold rounded font-mono">
                                                Cacat: {{ $batch->actual_qty_bad }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-brand-warm-gray italic">Menunggu eksekusi dapur</span>
                                @endif
                            </div>
                        </td>

                        <!-- Status & Cost -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-1.5">
                                <!-- Status Badge -->
                                @if ($batch->status === 'draft')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-neutral-100 text-brand-warm-gray">
                                        <i class="ti ti-file-text"></i> Draft Rencana
                                    </span>
                                @elseif ($batch->status === 'in_progress')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 animate-pulse">
                                        <i class="ti ti-flame"></i> Sedang Dimasak
                                    </span>
                                @elseif ($batch->status === 'completed')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                                        <i class="ti ti-check"></i> Selesai
                                    </span>
                                @elseif ($batch->status === 'cancelled')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                        <i class="ti ti-x"></i> Dibatalkan
                                    </span>
                                @endif

                                <!-- HPP Cost -->
                                @if ($batch->status === 'completed')
                                    <div class="text-xs">
                                        <span class="text-brand-warm-gray">HPP Aktual:</span>
                                        <span class="font-bold text-brand-primary font-mono block">
                                            {{ $batch->formattedUnitCost() }} / pack
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- Kitchen Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Start Button (for Draft) -->
                                @if ($batch->status === 'draft')
                                    <button type="button" @click="startBatch({{ $batch->id }})"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-bold bg-amber-500 hover:bg-amber-600 text-white transition cursor-pointer shadow-2xs">
                                        <i class="ti ti-player-play-filled"></i>
                                        <span>Mulai Masak</span>
                                    </button>

                                    <button type="button" @click="confirmDeleteBatch({{ $batch->id }})"
                                        class="inline-flex items-center justify-center size-8 rounded-lg text-red-600 hover:bg-red-50 border border-red-200 transition cursor-pointer">
                                        <i class="ti ti-trash text-base"></i>
                                    </button>
                                @endif

                                <!-- Finish Button (for In Progress) -->
                                @if ($batch->status === 'in_progress')
                                    <button type="button" @click="openFinishBatchModal({{ $batch->id }})"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-bold bg-green-600 hover:bg-green-700 text-white transition cursor-pointer shadow-2xs">
                                        <i class="ti ti-check"></i>
                                        <span>Selesai Masak</span>
                                    </button>

                                    <button type="button" @click="cancelBatch({{ $batch->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-red-600 hover:bg-red-50 border border-red-200 transition cursor-pointer">
                                        <i class="ti ti-x"></i>
                                        <span>Batal</span>
                                    </button>
                                @endif

                                <!-- Completed Status Badge -->
                                @if ($batch->status === 'completed')
                                    <span class="text-xs text-brand-warm-gray font-medium">
                                        {{ $batch->completed_at?->format('d/m H:i') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-brand-warm-gray">
                            <i class="ti ti-soup-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada batch produksi</p>
                            <p class="text-sm mt-1">Klik tombol "Buat Rencana Produksi" untuk memulai batch masak dapur.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
