<!-- MODAL 1: ROLE (Create / Edit + Assign Permissions by Group) -->
<div x-cloak x-show="showRoleModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="showRoleModal" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"
        @click="showRoleModal = false"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showRoleModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
                <div class="flex items-center gap-2.5">
                    <i class="ti ti-shield-lock text-brand-primary text-2xl"></i>
                    <h2 class="text-lg sm:text-xl font-bold text-brand-espresso" x-text="roleModalTitle"></h2>
                </div>
                <button type="button" @click="showRoleModal = false"
                    class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-6 overflow-y-auto flex-1">
                
                <!-- Error Message -->
                <div x-cloak x-show="roleFormError" class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-center gap-2">
                    <i class="ti ti-alert-circle text-lg shrink-0"></i>
                    <span x-text="roleFormError"></span>
                </div>

                <!-- Input Role Name -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Nama Role <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="roleForm.name"
                        @input="if (roleErrors.name) delete roleErrors.name"
                        placeholder="Contoh: staff operasional, supervisor, kasir utama"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso focus:outline-none focus:ring-2 font-medium transition"
                        :class="roleErrors.name ? 'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50/20' : 'border-brand-border focus:ring-brand-primary focus:border-brand-primary'">
                    <p x-cloak x-show="roleErrors.name" class="text-xs text-red-600 mt-1.5 font-semibold flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span x-text="roleErrors.name?.[0] || roleErrors.name"></span>
                    </p>
                    <p x-show="!roleErrors.name" class="text-xs text-brand-warm-gray mt-1">
                        Nama peran identitas pengguna dalam sistem Halala Food.
                    </p>
                </div>

                <!-- Permission Matrix Section -->
                <div class="border-t border-brand-border pt-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-brand-espresso">
                                Pilih Hak Akses (Permissions)
                            </h3>
                            <p class="text-xs text-brand-warm-gray">
                                Pilih izin otorisasi yang dapat diakses oleh role ini.
                            </p>
                        </div>

                        <!-- Global Select All Button -->
                        <button type="button"
                            @click="toggleAllPermissions()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-primary hover:underline cursor-pointer shrink-0">
                            <i class="ti" :class="areAllSelected(permissions.map(p => p.id)) ? 'ti-square-check' : 'ti-square'"></i>
                            <span x-text="areAllSelected(permissions.map(p => p.id)) ? 'Batalkan Semua' : 'Pilih Semua Hak Akses'"></span>
                        </button>
                    </div>

                    <!-- Filter Search Inside Modal -->
                    <div class="relative">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-brand-warm-gray text-base"></i>
                        <input type="text" x-model="rolePermSearch" placeholder="Cari permission dalam daftar..."
                            class="w-full pl-9 pr-3 py-2 bg-neutral-50 border border-brand-border rounded-lg text-xs sm:text-sm text-brand-espresso focus:outline-none focus:ring-2 focus:ring-brand-primary">
                    </div>

                    <!-- Clean Grouped Permissions List (No Excess Boxes) -->
                    <div class="space-y-4 max-h-80 overflow-y-auto pr-1 divide-y divide-brand-border/60">
                        @foreach ($groups as $group)
                            <div class="pt-3 first:pt-0 space-y-2">
                                <!-- Group Header -->
                                <div class="flex items-center justify-between gap-2 pb-1">
                                    <div class="flex items-center gap-1.5">
                                        <i class="ti ti-folder text-brand-primary text-base"></i>
                                        <span class="font-bold text-sm text-brand-espresso">{{ $group->name }}</span>
                                        <span class="text-xs text-brand-warm-gray">({{ $group->permissions ? $group->permissions->count() : 0 }})</span>
                                    </div>

                                    <button type="button"
                                        @click="toggleGroupPermissionsById({{ $group->id }})"
                                        class="text-xs font-semibold text-brand-primary hover:underline cursor-pointer">
                                        Pilih Semua Grup Ini
                                    </button>
                                </div>

                                <!-- Permission Checkboxes in Group -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                    @if ($group->permissions)
                                        @foreach ($group->permissions as $perm)
                                            <label x-show="!rolePermSearch || $el.textContent.toLowerCase().includes(rolePermSearch.toLowerCase())"
                                                class="flex items-center gap-2.5 py-1.5 px-2 rounded-lg hover:bg-neutral-50 transition cursor-pointer select-none">
                                                <input type="checkbox"
                                                    :checked="roleForm.permission_ids.includes({{ $perm->id }})"
                                                    @change="togglePermission({{ $perm->id }})"
                                                    class="size-4 rounded text-brand-primary focus:ring-brand-primary border-brand-border">
                                                <span class="font-mono text-xs text-brand-espresso font-medium">{{ $perm->name }}</span>
                                            </label>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <!-- Ungrouped Permissions if any -->
                        @if ($ungroupedPermissions && $ungroupedPermissions->count() > 0)
                            <div class="pt-3 space-y-2">
                                <div class="flex items-center justify-between pb-1">
                                    <span class="font-bold text-sm text-brand-espresso">Lainnya (Tanpa Grup)</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                    @foreach ($ungroupedPermissions as $perm)
                                        <label x-show="!rolePermSearch || $el.textContent.toLowerCase().includes(rolePermSearch.toLowerCase())"
                                            class="flex items-center gap-2.5 py-1.5 px-2 rounded-lg hover:bg-neutral-50 transition cursor-pointer select-none">
                                            <input type="checkbox"
                                                :checked="roleForm.permission_ids.includes({{ $perm->id }})"
                                                @change="togglePermission({{ $perm->id }})"
                                                class="size-4 rounded text-brand-primary focus:ring-brand-primary border-brand-border">
                                            <span class="font-mono text-xs text-brand-espresso font-medium">{{ $perm->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-brand-border bg-neutral-50/50 shrink-0">
                <div class="text-xs font-bold text-brand-warm-gray">
                    <span x-text="roleForm.permission_ids.length"></span> permission dipilih
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="showRoleModal = false"
                        class="px-5 py-2.5 rounded-xl border border-brand-border text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="submitRole()" :disabled="isProcessing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold shadow-xs transition cursor-pointer disabled:opacity-60">
                        <i x-show="isProcessing" class="ti ti-loader animate-spin text-base"></i>
                        <span x-text="isProcessing ? 'Menyimpan...' : 'Simpan Role'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
