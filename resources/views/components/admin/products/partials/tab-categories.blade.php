<!-- TAB 4: KATEGORI PRODUK -->
<div x-show="activeTab === 'categories'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
            <input type="text" x-model="categorySearch" placeholder="Cari nama kategori..."
                class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
        </div>

        <button type="button" @click="openCreateCategoryModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Kategori Baru</span>
        </button>
    </div>

    <!-- Categories Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Nama Kategori</th>
                    <th class="py-3.5 px-6">Slug URL</th>
                    <th class="py-3.5 px-6">Jumlah Produk</th>
                    <th class="py-3.5 px-6">Deskripsi</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($categories as $cat)
                    <tr x-show="!categorySearch || $el.textContent.toLowerCase().includes(categorySearch.toLowerCase())"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Name -->
                        <td class="py-4 px-6 align-top font-bold text-brand-espresso text-base">
                            <div class="flex items-center gap-2.5">
                                <i class="ti ti-category text-brand-primary text-xl"></i>
                                <span>{{ $cat->name }}</span>
                            </div>
                        </td>

                        <!-- Slug -->
                        <td class="py-4 px-6 align-top text-sm font-mono text-brand-warm-gray">
                            /{{ $cat->slug }}
                        </td>

                        <!-- Products Count -->
                        <td class="py-4 px-6 align-top">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-brand-soft-cream text-brand-primary">
                                <i class="ti ti-box"></i>
                                {{ $cat->products_count }} Produk
                            </span>
                        </td>

                        <!-- Description -->
                        <td class="py-4 px-6 align-top text-sm text-brand-warm-gray max-w-sm">
                            {{ $cat->description ?: '-' }}
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="openEditCategoryById({{ $cat->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                    <i class="ti ti-edit text-base"></i>
                                    <span>Edit</span>
                                </button>

                                <button type="button" @click="confirmDeleteCategoryById({{ $cat->id }})"
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
                            <i class="ti ti-folder-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada kategori terdaftar</p>
                            <p class="text-sm mt-1">Klik tombol "Tambah Kategori Baru" untuk menambahkan kategori camilan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
