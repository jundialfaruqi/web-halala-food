<!-- TAB 2: GRUP PERMISSION -->
<div x-show="activeTab === 'groups'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
            <input type="text" x-model="groupSearch" placeholder="Cari nama grup..."
                class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:ring-2 focus:ring-brand-primary focus:border-brand-primary">
        </div>

        <button type="button" @click="openCreateGroupModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm sm:text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Grup Permission</span>
        </button>
    </div>

    <!-- Group Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Nama Grup & Deskripsi</th>
                    <th class="py-3.5 px-6">Jumlah Permission</th>
                    <th class="py-3.5 px-6">Daftar Permission</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($groups as $group)
                    <tr x-show="!groupSearch || $el.textContent.toLowerCase().includes(groupSearch.toLowerCase())"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Group Name & Description -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex items-start gap-3">
                                <i class="ti ti-folder text-brand-primary text-2xl shrink-0 mt-0.5"></i>
                                <div>
                                    <span class="font-bold text-brand-espresso text-base block">
                                        {{ $group->name }}
                                    </span>
                                    <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5">
                                        {{ $group->description ?: 'Tidak ada deskripsi' }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Permissions Count -->
                        <td class="py-4 px-6 align-top">
                            <span class="inline-flex items-center gap-1.5 text-brand-espresso font-semibold text-sm">
                                <i class="ti ti-key text-brand-warm-gray text-base"></i>
                                <span>{{ $group->permissions_count }} Permission</span>
                            </span>
                        </td>

                        <!-- Permission Badges -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex flex-wrap gap-1.5 max-w-md">
                                @if ($group->permissions && $group->permissions->count() > 0)
                                    @foreach ($group->permissions as $p)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-neutral-100 text-brand-espresso text-xs font-mono">
                                            {{ $p->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-xs text-brand-warm-gray italic">Belum ada permission di grup ini</span>
                                @endif
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    @click="openEditGroupById({{ $group->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                    <i class="ti ti-edit text-base"></i>
                                    <span>Edit</span>
                                </button>

                                <button type="button"
                                    @click="confirmDeleteGroupById({{ $group->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200">
                                    <i class="ti ti-trash text-base"></i>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-12 text-brand-warm-gray">
                            <i class="ti ti-folder-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada grup permission</p>
                            <p class="text-sm mt-1">Klik tombol "Tambah Grup Permission" untuk membuat grup.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
