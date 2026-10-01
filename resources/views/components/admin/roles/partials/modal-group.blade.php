<!-- MODAL 2: GRUP PERMISSION (Create / Edit + Assign Permissions) -->
<div x-cloak x-show="showGroupModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="showGroupModal" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"
        @click="showGroupModal = false"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showGroupModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
                <div class="flex items-center gap-2.5">
                    <i class="ti ti-folder-plus text-brand-primary text-2xl"></i>
                    <h2 class="text-lg sm:text-xl font-bold text-brand-espresso" x-text="groupModalTitle"></h2>
                </div>
                <button type="button" @click="showGroupModal = false"
                    class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                <!-- Error Message -->
                <div x-cloak x-show="groupFormError" class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-center gap-2">
                    <i class="ti ti-alert-circle text-lg shrink-0"></i>
                    <span x-text="groupFormError"></span>
                </div>

                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Nama Grup Permission <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="groupForm.name" placeholder="Contoh: Logistik & Pengiriman, Stok & Gudang"
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-base text-brand-espresso focus:outline-none focus:ring-2 focus:ring-brand-primary focus:border-brand-primary font-medium">
                </div>

                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Deskripsi Singkat (Opsional)
                    </label>
                    <textarea x-model="groupForm.description" rows="2" placeholder="Jelaskan modul atau fungsi hak akses yang tercakup dalam grup ini..."
                        class="w-full px-4 py-2.5 bg-white border border-brand-border rounded-xl text-sm sm:text-base text-brand-espresso focus:outline-none focus:ring-2 focus:ring-brand-primary focus:border-brand-primary font-medium resize-none"></textarea>
                </div>

                <!-- Permission Assignment Section for Group -->
                <div class="border-t border-brand-border pt-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-brand-espresso">
                                Pilih Permission untuk Grup Ini
                            </h3>
                            <p class="text-xs text-brand-warm-gray">
                                Tentukan hak akses mana saja yang masuk ke dalam kelompok grup ini.
                            </p>
                        </div>

                        <!-- Select All Toggle -->
                        <button type="button"
                            @click="toggleAllGroupModalPermissions()"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-primary hover:underline cursor-pointer shrink-0">
                            <i class="ti" :class="areAllSelectedInGroup(permissions.map(p => p.id)) ? 'ti-square-check' : 'ti-square'"></i>
                            <span x-text="areAllSelectedInGroup(permissions.map(p => p.id)) ? 'Batalkan Semua' : 'Pilih Semua Permission'"></span>
                        </button>
                    </div>

                    <!-- Filter Search Inside Modal -->
                    <div class="relative">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-brand-warm-gray text-base"></i>
                        <input type="text" x-model="groupPermSearch" placeholder="Cari permission..."
                            class="w-full pl-9 pr-3 py-2 bg-neutral-50 border border-brand-border rounded-lg text-xs sm:text-sm text-brand-espresso focus:outline-none focus:ring-2 focus:ring-brand-primary">
                    </div>

                    <!-- Permission Checkbox List -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto pr-1">
                        @foreach ($permissions as $perm)
                            <label x-show="!groupPermSearch || $el.textContent.toLowerCase().includes(groupPermSearch.toLowerCase())"
                                class="flex items-start gap-2.5 p-2.5 rounded-xl border border-brand-border/60 hover:bg-neutral-50 transition cursor-pointer select-none"
                                :class="groupForm.permission_ids.includes({{ $perm->id }}) ? 'bg-brand-soft-cream/40 border-brand-primary/40' : 'bg-white'">
                                <input type="checkbox"
                                    :checked="groupForm.permission_ids.includes({{ $perm->id }})"
                                    @change="toggleGroupModalPermission({{ $perm->id }})"
                                    class="size-4 mt-0.5 rounded text-brand-primary focus:ring-brand-primary border-brand-border shrink-0">
                                <div class="flex-1 min-w-0">
                                    <span class="font-mono text-xs font-bold text-brand-espresso block truncate">
                                        {{ $perm->name }}
                                    </span>
                                    @if ($perm->group)
                                        <span class="text-[11px] text-brand-warm-gray block truncate"
                                            x-show="groupForm.id !== {{ $perm->permission_group_id ?: 'null' }}">
                                            Saat ini di: {{ $perm->group->name }}
                                        </span>
                                    @else
                                        <span class="text-[11px] text-amber-600 block truncate">
                                            Belum ada grup
                                        </span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-brand-border bg-neutral-50/50 shrink-0">
                <div class="text-xs font-bold text-brand-warm-gray">
                    <span x-text="groupForm.permission_ids.length"></span> permission dipilih
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="showGroupModal = false"
                        class="px-5 py-2.5 rounded-xl border border-brand-border text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button" @click="submitGroup()" :disabled="isProcessing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold shadow-xs transition cursor-pointer disabled:opacity-60">
                        <i x-show="isProcessing" class="ti ti-loader animate-spin text-base"></i>
                        <span x-text="isProcessing ? 'Menyimpan...' : 'Simpan Grup'"></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
