<!-- MODAL 3: PERMISSION (Create / Edit + Select Group) -->
<div x-cloak x-show="showPermissionModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="showPermissionModal" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"
        @click="showPermissionModal = false"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showPermissionModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl border border-brand-border overflow-hidden">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border">
                <div class="flex items-center gap-2.5">
                    <i class="ti ti-key text-brand-primary text-2xl"></i>
                    <h2 class="text-lg sm:text-xl font-bold text-brand-espresso" x-text="permissionModalTitle"></h2>
                </div>
                <button type="button" @click="showPermissionModal = false"
                    class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4">
                <!-- Error Message -->
                <div x-cloak x-show="permissionFormError"
                    class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-center gap-2">
                    <i class="ti ti-alert-circle text-lg shrink-0"></i>
                    <span x-text="permissionFormError"></span>
                </div>

                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Nama Permission (Slug) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="permissionForm.name"
                        @input="if (permissionErrors.name) delete permissionErrors.name"
                        placeholder="Contoh: order-create, invoice-print, promo-manage"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base font-mono text-brand-espresso focus:outline-none focus:ring-2 font-medium transition"
                        :class="permissionErrors.name ? 'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50/20' :
                            'border-brand-border focus:ring-brand-primary focus:border-brand-primary'">
                    <p x-cloak x-show="permissionErrors.name"
                        class="text-xs text-red-600 mt-1.5 font-semibold flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span x-text="permissionErrors.name?.[0] || permissionErrors.name"></span>
                    </p>
                    <p x-show="!permissionErrors.name" class="text-xs text-brand-warm-gray mt-1">
                        Gunakan format standar <code class="font-mono text-brand-primary font-bold">modul-aksi</code>
                        (huruf kecil dan tanda strip).
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Pilih Grup Permission
                    </label>
                    <select x-model="permissionForm.group_id"
                        @change="if (permissionErrors.groupId) delete permissionErrors.groupId"
                        class="select select-lg select-bordered w-full bg-white rounded-xl text-base text-brand-espresso focus:outline-none font-medium transition"
                        :class="permissionErrors.groupId ? 'border-red-500!' : ''">
                        <option value="">-- Tanpa Grup (Belum Dikelompokkan) --</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <p x-cloak x-show="permissionErrors.groupId"
                        class="text-xs text-red-600 mt-1.5 font-semibold flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span x-text="permissionErrors.groupId?.[0] || permissionErrors.groupId"></span>
                    </p>
                    <p x-show="!permissionErrors.groupId" class="text-xs text-brand-warm-gray mt-1">
                        Permission ini akan dikelompokkan ke dalam kategori grup yang dipilih.
                    </p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-brand-border bg-neutral-50/50">
                <button type="button" @click="showPermissionModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                    Batal
                </button>
                <button type="button" @click="submitPermission()" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold shadow-xs transition cursor-pointer disabled:opacity-60">
                    <i x-show="isProcessing" class="ti ti-loader animate-spin text-base"></i>
                    <span x-text="isProcessing ? 'Menyimpan...' : 'Simpan Permission'"></span>
                </button>
            </div>

        </div>
    </div>
</div>
