<!-- MODAL: USER (Create / Edit) -->
<div x-cloak x-show="showUserModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="showUserModal" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-brand-espresso/60 backdrop-blur-xs"
        @click="showUserModal = false"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showUserModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-xl bg-white rounded-2xl shadow-xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-brand-border shrink-0">
                <div class="flex items-center gap-2.5">
                    <i class="ti ti-user-plus text-brand-primary text-2xl"></i>
                    <h2 class="text-lg sm:text-xl font-bold text-brand-espresso" x-text="userModalTitle"></h2>
                </div>
                <button type="button" @click="showUserModal = false"
                    class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer">
                    <i class="ti ti-x text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <!-- General Error Message (if any) -->
                <div x-cloak x-show="userFormError"
                    class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex items-center gap-2">
                    <i class="ti ti-alert-circle text-lg shrink-0"></i>
                    <span x-text="userFormError"></span>
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="userForm.name" @input="if (userErrors.name) delete userErrors.name"
                        placeholder="Contoh: Siti Rahayu, Budi Pratama"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso focus:outline-none focus:ring-2 font-medium transition"
                        :class="userErrors.name ? 'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50/20' :
                            'border-brand-border focus:ring-brand-primary focus:border-brand-primary'">
                    <p x-cloak x-show="userErrors.name"
                        class="text-xs text-red-600 mt-1.5 font-semibold flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span x-text="userErrors.name?.[0] || userErrors.name"></span>
                    </p>
                </div>

                <!-- Alamat Email -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Alamat Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="user_email_field" x-model="userForm.email" autocomplete="off"
                        @input="if (userErrors.email) delete userErrors.email" placeholder="Contoh: user@halala-food.id"
                        class="w-full px-4 py-2.5 bg-white border rounded-xl text-base text-brand-espresso focus:outline-none focus:ring-2 font-medium transition"
                        :class="userErrors.email ? 'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50/20' :
                            'border-brand-border focus:ring-brand-primary focus:border-brand-primary'">
                    <p x-cloak x-show="userErrors.email"
                        class="text-xs text-red-600 mt-1.5 font-semibold flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span x-text="userErrors.email?.[0] || userErrors.email"></span>
                    </p>
                    <p x-show="!userErrors.email" class="text-xs text-brand-warm-gray mt-1">
                        Digunakan sebagai identitas login ke panel admin Halala Food.
                    </p>
                </div>

                <!-- Nomor HP / WhatsApp (+62) -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Nomor HP / WhatsApp
                    </label>
                    <div class="relative flex items-stretch">
                        <div class="inline-flex items-center gap-1 px-3.5 bg-neutral-100 border border-r-0 border-brand-border rounded-l-xl text-sm font-bold text-brand-espresso select-none"
                            :class="userErrors.phone ? 'border-red-500' : 'border-brand-border'">
                            <i class="ti ti-brand-whatsapp text-green-600 text-base"></i>
                            <span>+62</span>
                        </div>
                        <input type="text" x-model="userForm.phone"
                            @input="userForm.phone = sanitizePhone($event.target.value); if (userErrors.phone) delete userErrors.phone"
                            placeholder="81234567890"
                            class="w-full px-4 py-2.5 bg-white border rounded-r-xl text-base text-brand-espresso focus:outline-none focus:ring-2 font-medium font-mono transition"
                            :class="userErrors.phone ? 'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50/20' :
                                'border-brand-border focus:ring-brand-primary focus:border-brand-primary'">
                    </div>
                    <p x-cloak x-show="userErrors.phone"
                        class="text-xs text-red-600 mt-1.5 font-semibold flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span x-text="userErrors.phone?.[0] || userErrors.phone"></span>
                    </p>
                    <p x-show="!userErrors.phone" class="text-xs text-brand-warm-gray mt-1">
                        Format nomor HP otomatis mendukung pengiriman pesan dan chat WhatsApp langsung (<code
                            class="font-mono text-brand-primary font-bold">wa.me/62...</code>).
                    </p>
                </div>

                <!-- Peran / Role -->
                <div>
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Peran (Role) Pengguna <span class="text-red-500">*</span>
                    </label>
                    <select x-model="userForm.role" @change="if (userErrors.role) delete userErrors.role"
                        class="select select-lg select-bordered w-full bg-white rounded-xl text-base text-brand-espresso focus:outline-none font-medium capitalize transition"
                        :class="userErrors.role ? 'border-red-500!' : ''">
                        <option value="" disabled selected>-- Pilih Peran Pengguna --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <p x-cloak x-show="userErrors.role"
                        class="text-xs text-red-600 mt-1.5 font-semibold flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span x-text="userErrors.role?.[0] || userErrors.role"></span>
                    </p>
                    <p x-show="!userErrors.role" class="text-xs text-brand-warm-gray mt-1">
                        Menentukan hak akses menu dan operasional yang dapat dijalankan oleh pengguna ini.
                    </p>
                </div>

                <!-- Kata Sandi (Password) -->
                <div x-data="{ showPassword: false }">
                    <label class="block text-sm font-bold text-brand-espresso mb-1.5">
                        Kata Sandi (Password)
                        <span x-show="!userForm.id" class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" x-model="userForm.password"
                            name="user_password_field" autocomplete="new-password"
                            @input="if (userErrors.password) delete userErrors.password"
                            :placeholder="userForm.id ? 'Kosongkan jika tidak ingin mengubah kata sandi' : 'Minimal 6 karakter'"
                            class="w-full pl-4 pr-11 py-2.5 bg-white border rounded-xl text-base text-brand-espresso focus:outline-none focus:ring-2 font-medium transition"
                            :class="userErrors.password ?
                                'border-red-500 focus:ring-red-500 focus:border-red-500 bg-red-50/20' :
                                'border-brand-border focus:ring-brand-primary focus:border-brand-primary'">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-brand-warm-gray hover:text-brand-espresso cursor-pointer p-1">
                            <i class="ti text-lg" :class="showPassword ? 'ti-eye-off' : 'ti-eye'"></i>
                        </button>
                    </div>
                    <p x-cloak x-show="userErrors.password"
                        class="text-xs text-red-600 mt-1.5 font-semibold flex items-center gap-1">
                        <i class="ti ti-alert-circle text-sm"></i>
                        <span x-text="userErrors.password?.[0] || userErrors.password"></span>
                    </p>
                    <p class="text-xs text-brand-warm-gray mt-1" x-show="!userErrors.password && userForm.id">
                        Biarkan kosong apabila tidak ingin mengganti kata sandi akun ini.
                    </p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex items-center justify-end gap-3 px-6 py-4 border-t border-brand-border bg-neutral-50/50 shrink-0">
                <button type="button" @click="showUserModal = false"
                    class="px-5 py-2.5 rounded-xl border border-brand-border text-sm font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                    Batal
                </button>
                <button type="button" @click="submitUser()" :disabled="isProcessing"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold shadow-xs transition cursor-pointer disabled:opacity-60">
                    <i x-show="isProcessing" class="ti ti-loader animate-spin text-base"></i>
                    <span x-text="isProcessing ? 'Menyimpan...' : 'Simpan Pengguna'"></span>
                </button>
            </div>

        </div>
    </div>
</div>
