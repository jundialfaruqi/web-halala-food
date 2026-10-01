<!-- TAB 1: ROLE PENGGUNA -->
<div x-show="activeTab === 'roles'" class="space-y-4">
    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <i class="ti ti-search absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray text-lg"></i>
            <input type="text" x-model="roleSearch" placeholder="Cari nama role..."
                class="w-full pl-10 pr-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso placeholder-brand-warm-gray focus:outline-none focus:ring-2 focus:ring-brand-primary focus:border-brand-primary">
        </div>

        <button type="button" @click="openCreateRoleModal()"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm sm:text-base font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0">
            <i class="ti ti-plus text-lg"></i>
            <span>Tambah Role Baru</span>
        </button>
    </div>

    <!-- Role Table -->
    <div class="overflow-x-auto bg-white rounded-xl border border-brand-border">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-brand-border bg-neutral-50/60 text-brand-espresso text-xs sm:text-sm font-bold uppercase tracking-wider">
                    <th class="py-3.5 px-6">Nama Role</th>
                    <th class="py-3.5 px-6">Jumlah Pengguna</th>
                    <th class="py-3.5 px-6">Hak Akses (Permissions)</th>
                    <th class="py-3.5 px-6 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-border text-sm sm:text-base">
                @forelse ($roles as $role)
                    <tr x-show="!roleSearch || $el.textContent.toLowerCase().includes(roleSearch.toLowerCase())"
                        class="hover:bg-neutral-50/50 transition">
                        
                        <!-- Role Name -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex items-center gap-3">
                                <i class="ti ti-shield text-brand-primary text-2xl shrink-0"></i>
                                <div>
                                    <span class="font-bold text-brand-espresso text-base block capitalize">
                                        {{ $role->name }}
                                    </span>
                                    <span class="text-xs text-brand-warm-gray">
                                        Guard: {{ $role->guard_name }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Users Count -->
                        <td class="py-4 px-6 align-top">
                            <div class="inline-flex items-center gap-1.5 text-brand-espresso font-semibold text-sm">
                                <i class="ti ti-users text-brand-warm-gray text-base"></i>
                                <span>{{ $role->users_count }} Pengguna</span>
                            </div>
                        </td>

                        <!-- Permissions assigned -->
                        <td class="py-4 px-6 align-top">
                            <div class="flex flex-wrap items-center gap-1.5 max-w-xl">
                                @if ($role->permissions && $role->permissions->count() > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-50 text-green-700">
                                        {{ $role->permissions->count() }} Izin
                                    </span>
                                    @foreach ($role->permissions->take(4) as $perm)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-neutral-100 text-brand-espresso text-xs">
                                            {{ $perm->name }}
                                        </span>
                                    @endforeach
                                    @if ($role->permissions->count() > 4)
                                        <span class="text-xs text-brand-warm-gray font-medium">
                                            +{{ $role->permissions->count() - 4 }} lainnya
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-brand-warm-gray italic">Belum ada permission</span>
                                @endif
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 align-top text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    @click="openEditRoleById({{ $role->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/60 transition cursor-pointer border border-brand-border">
                                    <i class="ti ti-edit text-base"></i>
                                    <span>Edit</span>
                                </button>

                                @if (!in_array(strtolower($role->name), ['dev', 'developer']))
                                    <button type="button"
                                        @click="confirmDeleteRoleById({{ $role->id }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-red-600 hover:bg-red-50 transition cursor-pointer border border-red-200">
                                        <i class="ti ti-trash text-base"></i>
                                        <span>Hapus</span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-brand-warm-gray bg-neutral-100 border border-neutral-200" title="Role sistem utama tidak dapat dihapus">
                                        <i class="ti ti-lock"></i> Sistem
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-12 text-brand-warm-gray">
                            <i class="ti ti-shield-off text-3xl mb-2 text-brand-warm-gray block"></i>
                            <p class="font-bold text-base text-brand-espresso">Belum ada role terdaftar</p>
                            <p class="text-sm mt-1">Klik tombol "Tambah Role Baru" untuk membuat role baru.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
