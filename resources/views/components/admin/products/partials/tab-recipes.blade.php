<!-- TAB 2: FORMULA RESEP (BOM) -->
<div x-show="activeTab === 'recipes'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
            <input type="text" x-model="recipeSearch" placeholder="Cari nama resep atau produk..."
                class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
        </div>

        <button type="button" @click="openCreateRecipeModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Resep Baru</span>
        </button>
    </div>

    <!-- Recipes Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Formula Resep</th>
                    <th class="py-3.5 px-6">Target Varian & Output</th>
                    <th class="py-3.5 px-6">Bahan Baku & Takaran (BOM)</th>
                    <th class="py-3.5 px-6">Estimasi HPP / Pack</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($recipes as $rcp)
                    <tr x-show="!recipeSearch || $el.textContent.toLowerCase().includes(recipeSearch.toLowerCase())"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Recipe Info -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex items-start gap-3">
                                <div class="size-10 rounded-xl bg-brand-soft-cream text-brand-primary flex items-center justify-center shrink-0">
                                    <i class="ti ti-chef-hat text-2xl"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-brand-espresso text-base block">
                                        {{ $rcp->name }}
                                    </span>
                                    @if ($rcp->notes)
                                        <p class="text-xs text-brand-warm-gray mt-1 max-w-sm line-clamp-2">
                                            {{ $rcp->notes }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <!-- Target Variant & Output -->
                        <td class="py-4 px-6 align-top">
                            <div>
                                <span class="font-bold text-brand-espresso text-sm block">
                                    {{ $rcp->productVariant?->product?->name }}
                                </span>
                                <span class="text-xs px-2 py-0.5 bg-neutral-100 text-brand-espresso font-semibold rounded mt-0.5 inline-block">
                                    {{ $rcp->productVariant?->name }}
                                </span>
                                <div class="mt-1.5 text-xs text-brand-warm-gray">
                                    <span class="font-semibold text-brand-espresso">1 Batch = {{ $rcp->batch_output_qty }} pack hasil jadi</span>
                                </div>
                            </div>
                        </td>

                        <!-- BOM Ingredients -->
                        <td class="py-4 px-6 align-top">
                            <div class="space-y-1 max-w-md">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-brand-espresso mb-1">
                                    <span class="px-2 py-0.5 bg-green-50 text-green-700 rounded">
                                        {{ $rcp->items->count() }} Bahan Terpakai
                                    </span>
                                    <span class="text-brand-warm-gray">
                                        (Total Bahan: Rp {{ number_format($rcp->calculateTotalMaterialCost(), 0, ',', '.') }})
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($rcp->items as $item)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-neutral-100 text-brand-espresso text-xs">
                                            <span class="font-medium">{{ $item->rawMaterial?->name }}:</span>
                                            <span class="font-bold font-mono text-brand-primary">{{ (float) $item->quantity_required }} {{ $item->rawMaterial?->unit }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </td>

                        <!-- Estimated HPP -->
                        <td class="py-4 px-6 align-top">
                            <div class="p-2.5 rounded-lg bg-brand-soft-cream/40 border border-brand-border/60">
                                <span class="text-xs text-brand-warm-gray block">Estimasi HPP / Pack:</span>
                                <span class="font-extrabold text-brand-primary font-mono text-base block">
                                    {{ $rcp->formattedEstimatedHpp() }}
                                </span>
                                <span class="text-[11px] text-brand-warm-gray mt-0.5 block">
                                    Tenaga & Gas: Rp {{ number_format((float) $rcp->labor_cost, 0, ',', '.') }}
                                </span>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="openEditRecipeById({{ $rcp->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                    <i class="ti ti-edit text-base"></i>
                                    <span>Edit</span>
                                </button>

                                <button type="button" @click="confirmDeleteRecipeById({{ $rcp->id }})"
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
                            <i class="ti ti-receipt-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada formula resep terdaftar</p>
                            <p class="text-sm mt-1">Klik tombol "Tambah Resep Baru" untuk mengatur takaran bahan baku.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
