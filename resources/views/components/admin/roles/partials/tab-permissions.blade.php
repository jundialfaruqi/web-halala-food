<!-- TAB 3: DAFTAR PERMISSION -->
<div x-show="activeTab === 'permissions'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-md">
                <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
                <input type="text" x-model="permissionSearch" placeholder="Cari nama permission (e.g. order-manage)..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:border-brand-primary">
            </div>

            <!-- Filter by Group Dropdown -->
            <div class="shrink-0">
                <select x-model="permissionGroupFilter"
                    class="select select-lg w-full sm:w-auto bg-white border border-brand-border rounded-xl text-base text-brand-espresso font-medium">
                    <option value="all">Semua Grup Permission</option>
                    <option value="none">Tanpa Grup (Belum Dikelompokkan)</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="button" @click="openCreatePermissionModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Permission</span>
        </button>
    </div>

    <!-- Permission Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Nama Permission (Slug)</th>
                    <th class="py-3.5 px-6">Grup Terkait</th>
                    <th class="py-3.5 px-6">Role yang Menggunakan</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($permissions as $permission)
                    <tr x-show="(!permissionSearch || $el.textContent.toLowerCase().includes(permissionSearch.toLowerCase())) && (permissionGroupFilter === 'all' || (permissionGroupFilter === 'none' && !{{ $permission->permission_group_id ? $permission->permission_group_id : '0' }}) || permissionGroupFilter === '{{ $permission->permission_group_id }}')"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Name Slug -->
                        <td class="py-4 px-6 align-middle">
                            <div class="flex items-center gap-2.5">
                                <i class="ti ti-key text-brand-primary text-lg shrink-0"></i>
                                <code class="font-mono font-bold text-brand-espresso text-sm bg-neutral-100 px-2 py-0.5 rounded">
                                    {{ $permission->name }}
                                </code>
                            </div>
                        </td>

                        <!-- Group -->
                        <td class="py-4 px-6 align-middle">
                            @if ($permission->group)
                                <span class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-medium text-brand-espresso">
                                    <i class="ti ti-folder text-brand-warm-gray text-sm"></i>
                                    <span>{{ $permission->group->name }}</span>
                                </span>
                            @else
                                <span class="text-xs text-brand-warm-gray italic">
                                    Tanpa Grup
                                </span>
                            @endif
                        </td>

                        <!-- Roles Count -->
                        <td class="py-4 px-6 align-middle">
                            <div class="flex flex-wrap gap-1">
                                @if ($permission->roles && $permission->roles->count() > 0)
                                    @foreach ($permission->roles as $r)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-neutral-100 text-brand-espresso text-xs capitalize">
                                            {{ $r->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-xs text-brand-warm-gray italic">Belum di-assign ke role</span>
                                @endif
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-middle text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    @click="openEditPermissionById({{ $permission->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                    <i class="ti ti-edit text-base"></i>
                                    <span>Edit</span>
                                </button>

                                <button type="button"
                                    @click="confirmDeletePermissionById({{ $permission->id }})"
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
                            <i class="ti ti-key-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada permission</p>
                            <p class="text-sm mt-1">Klik tombol "Tambah Permission" untuk membuat permission baru.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
