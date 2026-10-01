<main class="w-full max-w-md mx-auto px-4 sm:px-6 py-12 sm:py-20 flex items-center justify-center"
    x-data="{ showPassword: false }">
    <div
        class="w-full bg-white rounded-3xl border border-brand-border p-6 sm:p-10 shadow-xl shadow-brand-espresso/5 space-y-6">

        <!-- Header -->
        <div class="text-center space-y-2">
            <h1 class="text-2xl sm:text-3xl font-bold text-brand-espresso tracking-tight">
                Masuk ke Akun
            </h1>
            <p class="text-xs sm:text-sm text-brand-warm-gray leading-relaxed">
                Silakan masukkan email dan kata sandi Anda untuk melanjutkan.
            </p>
        </div>

        <!-- General Error Alert -->
        @if ($errors->has('email') && $errors->first('email') === 'Email atau kata sandi yang Anda masukkan tidak sesuai.')
            <div
                class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm flex items-start gap-3">
                <i class="ti ti-alert-circle text-lg shrink-0 mt-0.5"></i>
                <span>{{ $errors->first('email') }}</span>
            </div>
        @endif

        <!-- Form -->
        <form wire:submit="login" class="space-y-4">

            <!-- Email Input -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs sm:text-sm font-semibold text-brand-espresso">
                    Alamat Email
                </label>
                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-brand-warm-gray">
                        <i class="ti ti-mail text-lg"></i>
                    </div>
                    <input type="email" id="email" wire:model.blur="email" placeholder="nama@email.com"
                        autocomplete="email"
                        @class([
                            'w-full pl-10 pr-4 py-3 rounded-xl border bg-white text-sm text-brand-espresso placeholder-brand-warm-gray/60 focus:outline-none focus:ring-4 transition',
                            'border-red-400 focus:ring-red-400' => $errors->has('email'),
                            'border-brand-border focus:border-brand-primary focus:ring-brand-primary/20' => !$errors->has('email'),
                        ])>
                </div>
                @error('email')
                    @if ($message !== 'Email atau kata sandi yang Anda masukkan tidak sesuai.')
                        <p class="text-xs text-red-600 mt-1 flex items-center gap-1">
                            <i class="ti ti-info-circle"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @endif
                @enderror
            </div>

            <!-- Password Input -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs sm:text-sm font-semibold text-brand-espresso">
                        Kata Sandi
                    </label>
                    <a href="#"
                        class="text-xs font-semibold text-brand-primary hover:text-brand-primary-hover hover:underline transition">
                        Lupa Sandi?
                    </a>
                </div>
                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-brand-warm-gray">
                        <i class="ti ti-lock text-lg"></i>
                    </div>
                    <input :type="showPassword ? 'text' : 'password'" id="password" wire:model.blur="password"
                        placeholder="••••••••" autocomplete="current-password"
                        @class([
                            'w-full pl-10 pr-11 py-3 rounded-xl border bg-white text-sm text-brand-espresso placeholder-brand-warm-gray/60 focus:outline-none focus:ring-4 transition',
                            'border-red-400 focus:ring-red-400' => $errors->has('password'),
                            'border-brand-border focus:border-brand-primary focus:ring-brand-primary/20' => !$errors->has('password'),
                        ])>

                    <!-- Show/Hide Toggle -->
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-brand-warm-gray hover:text-brand-espresso transition"
                        aria-label="Tampilkan kata sandi">
                        <i :class="showPassword ? 'ti ti-eye-off' : 'ti ti-eye'" class="text-lg"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-red-600 mt-1 flex items-center gap-1">
                        <i class="ti ti-info-circle"></i>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" wire:model="remember"
                        class="rounded border-brand-border text-brand-primary focus:ring-brand-primary/30 size-4">
                    <span class="text-xs sm:text-sm text-brand-text-primary">Ingat saya</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" wire:loading.attr="disabled"
                    class="w-full py-3.5 px-6 rounded-xl bg-brand-primary hover:bg-brand-primary-hover text-white font-semibold text-sm shadow-md shadow-brand-primary/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75">
                    <span wire:loading.remove wire:target="login">Masuk Sekarang</span>
                    <span wire:loading wire:target="login" class="flex items-center gap-2">
                        <i class="ti ti-loader-2 animate-spin text-base"></i>
                        <span>Memproses...</span>
                    </span>
                    <i wire:loading.remove wire:target="login" class="ti ti-arrow-right text-base"></i>
                </button>
            </div>

        </form>

        <!-- Divider -->
        <div class="relative flex py-2 items-center">
            <div class="grow border-t border-brand-border"></div>
            <span class="shrink-0 px-3 text-xs text-brand-warm-gray">atau</span>
            <div class="grow border-t border-brand-border"></div>
        </div>

        <!-- Register Link / Help -->
        <div class="text-center text-xs sm:text-sm text-brand-warm-gray">
            Belum punya akun?
            <a href="https://wa.me/6281234567890?text=Halo%20Halala%20Food,%20saya%20ingin%20mendaftar%20akun%20baru"
                target="_blank"
                class="font-semibold text-brand-primary hover:text-brand-primary-hover hover:underline transition ml-1">
                Hubungi Kami di WhatsApp
            </a>
        </div>

    </div>
</main>
