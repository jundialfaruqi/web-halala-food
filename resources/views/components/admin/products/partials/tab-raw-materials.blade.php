<!-- TAB 3: BAHAN BAKU & KEMASAN -->
<div x-show="activeTab === 'raw-materials'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="materialSearch" placeholder="Cari nama bahan baku atau kode..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Category Filter -->
            <div class="shrink-0">
                <select x-model="materialCategoryFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                    <option value="all">Semua Kategori Bahan</option>
                    <option value="ingredient">Bahan Masak Dapur</option>
                    <option value="packaging">Bahan Kemasan & Label</option>
                    <option value="other">Lain-lain</option>
                </select>
            </div>
        </div>

        <!-- Add Button -->
        <button type="button" @click="openCreateRawMaterialModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Bahan Baku</span>
        </button>
    </div>

    <!-- Raw Materials Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Bahan Baku & Kode</th>
                    <th class="py-3.5 px-6">Kategori</th>
                    <th class="py-3.5 px-6">Stok di Gudang</th>
                    <th class="py-3.5 px-6">Harga Beli Rata-rata (HPP)</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($rawMaterials as $mat)
                    <tr x-show="(!materialSearch || $el.textContent.toLowerCase().includes(materialSearch.toLowerCase())) && (materialCategoryFilter === 'all' || '{{ $mat->category }}' === materialCategoryFilter)"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Material Name & Code -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex items-start gap-3">
                                <div class="size-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0">
                                    @if ($mat->category === 'packaging')
                                        <i class="ti ti-package text-2xl"></i>
                                    @else
                                        <i class="ti ti-flame text-2xl"></i>
                                    @endif
                                </div>
                                <div>
                                    <span class="font-bold text-brand-espresso text-base block">
                                        {{ $mat->name }}
                                    </span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="font-mono text-xs px-2 py-0.5 bg-neutral-100 rounded text-brand-warm-gray font-bold">
                                            {{ $mat->code }}
                                        </span>
                                        <span class="text-xs text-brand-warm-gray">
                                            Satuan: <strong class="text-brand-espresso">{{ $mat->unit }}</strong>
                                        </span>
                                    </div>
                                    @if ($mat->notes)
                                        <p class="text-xs text-brand-warm-gray mt-1">
                                            {{ $mat->notes }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Category Badge -->
                        <td class="py-4 px-6 align-top">
                            @if ($mat->category === 'ingredient')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    <i class="ti ti-soup text-sm"></i> Bahan Dapur
                                </span>
                            @elseif ($mat->category === 'packaging')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                    <i class="ti ti-box text-sm"></i> Bahan Kemasan
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-neutral-100 text-neutral-800">
                                    Lainnya
                                </span>
                            @endif
                        </td>

                        <!-- Stock & Alert -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-base text-brand-espresso">
                                        {{ number_format((float) $mat->stock_qty, 0, ',', '.') }} {{ $mat->unit }}
                                    </span>
                                    @if ($mat->isLowStock())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-red-100 text-red-700">
                                            <i class="ti ti-alert-triangle"></i> Di Bawah Minimum
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-700">
                                            Cukup
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-brand-warm-gray block">
                                    Batas Minimum Restock: {{ number_format((float) $mat->min_stock_alert, 0, ',', '.') }} {{ $mat->unit }}
                                </span>
                            </div>
                        </td>

                        <!-- Average Cost -->
                        <td class="py-4 px-6 align-top">
                            <div class="font-mono">
                                <span class="font-bold text-brand-espresso text-base block">
                                    {{ $mat->formattedAverageCost() }}
                                </span>
                                <span class="text-xs text-brand-warm-gray">
                                    per {{ $mat->unit }}
                                </span>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="openEditRawMaterialById({{ $mat->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                    <i class="ti ti-edit text-base"></i>
                                    <span>Edit</span>
                                </button>

                                <button type="button" @click="confirmDeleteRawMaterialById({{ $mat->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200">
                                    <i class="ti ti-trash text-base"></i>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-brand-warm-gray">
                            <i class="ti ti-archive-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada bahan baku terdaftar</p>
                            <p class="text-sm mt-1">Klik tombol "Tambah Bahan Baku" untuk mencatat persediaan gudang.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
