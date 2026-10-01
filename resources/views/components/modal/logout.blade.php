<!-- Global Logout Confirmation Modal Component -->
<div x-data="{ open: false }"
    @open-logout-modal.window="open = true"
    @keydown.escape.window="open = false"
    x-cloak>

    <!-- Backdrop -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-brand-espresso/60 backdrop-blur-xs"
        @click="open = false">
    </div>

    <!-- Modal Box Container -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto pointer-events-none">

        <div @click.outside="open = false"
            class="pointer-events-auto relative w-full max-w-md bg-white rounded-3xl border border-brand-border p-6 sm:p-8 shadow-2xl space-y-6 text-center">

            <!-- Icon Header -->
            <div class="mx-auto size-16 rounded-full bg-red-50 text-red-600 flex items-center justify-center">
                <i class="ti ti-logout text-3xl"></i>
            </div>

            <!-- Text Details -->
            <div class="space-y-2">
                <h3 class="text-xl sm:text-2xl font-bold text-brand-espresso tracking-tight">
                    Konfirmasi Keluar
                </h3>
                <p class="text-sm sm:text-base text-brand-warm-gray leading-relaxed">
                    Apakah Anda yakin ingin keluar dari akun? Anda harus memasukkan email dan kata sandi kembali untuk masuk.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col-reverse sm:flex-row items-center justify-center gap-3 pt-2">
                <button type="button" @click="open = false"
                    class="w-full sm:w-1/2 py-3 px-5 rounded-xl border border-brand-border bg-white hover:bg-neutral-50 text-brand-espresso font-semibold text-sm sm:text-base transition cursor-pointer">
                    Batal
                </button>

                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-1/2">
                    @csrf
                    <button type="submit"
                        class="w-full py-3 px-5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm sm:text-base shadow-sm hover:shadow transition cursor-pointer">
                        Ya, Keluar
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
