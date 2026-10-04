<!-- Admin Header Component -->
<header
    class="sticky top-0 z-30 h-20 bg-white border-b border-brand-border px-4 sm:px-6 lg:px-8 flex items-center justify-between print:hidden">

    <!-- Left: Mobile Menu Toggle & Date -->
    <div class="flex items-center gap-3 sm:gap-4">
        <button type="button" @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden size-11 rounded-xl flex items-center justify-center text-brand-espresso hover:text-brand-primary hover:bg-neutral-100 transition cursor-pointer"
            aria-label="Toggle Sidebar">
            <i class="ti ti-menu-2 text-2xl"></i>
        </button>

        @php
            $now = \Carbon\Carbon::now('Asia/Jakarta')->locale('id');
        @endphp
        <div class="hidden sm:flex items-center gap-2">
            <i class="ti ti-calendar text-xl text-brand-warm-gray"></i>
            <div class="flex flex-col">
                <span class="text-xs text-brand-warm-gray font-medium leading-tight">
                    {{ $now->translatedFormat('l') }}
                </span>
                <span class="text-sm font-bold text-brand-espresso leading-tight">
                    {{ $now->translatedFormat('j F Y') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Right: Actions & Profile -->
    <div class="flex items-center gap-3">

        <!-- User Profile Dropdown -->
        <div class="dropdown dropdown-end">
            <div tabindex="0" role="button" aria-label="Menu Profil"
                class="flex items-center gap-3 p-2 sm:px-3 sm:py-2 rounded-xl hover:bg-brand-soft-cream/60 transition cursor-pointer">
                <div
                    class="size-10 rounded-full bg-brand-primary text-white flex items-center justify-center font-bold text-sm">
                    {{ auth()->user() ? auth()->user()->initials() : 'AD' }}
                </div>
                <div class="hidden md:flex flex-col text-left">
                    <span class="text-sm font-bold text-brand-espresso leading-tight">
                        {{ auth()->user()?->name ?? 'Administrator' }}
                    </span>
                    <span class="text-xs text-brand-warm-gray leading-tight">
                        {{ auth()->user()?->roles?->pluck('name')?->first() ?? 'Administrator' }}
                    </span>
                </div>
                <i class="ti ti-chevron-down text-sm text-brand-warm-gray hidden sm:block"></i>
            </div>
            <ul tabindex="0"
                class="dropdown-content z-50 menu p-2 shadow-lg bg-white border border-brand-border rounded-2xl w-64 space-y-1 mt-2">
                <li class="px-4 py-3 border-b border-brand-border/60">
                    <p class="text-sm font-bold text-brand-espresso">{{ auth()->user()?->name ?? 'Administrator' }}</p>
                    <p class="text-xs text-brand-warm-gray truncate">
                        {{ auth()->user()?->email ?? 'admin@halala-food.id' }}</p>
                </li>
                <li>
                    <a href="{{ route('home') }}" target="_blank"
                        class="flex items-center gap-2.5 text-sm font-medium text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/50 rounded-xl py-2.5">
                        <i class="ti ti-world text-base"></i>
                        <span>Lihat Toko</span>
                    </a>
                </li>
                <li class="pt-1 border-t border-brand-border/60">
                    <button type="button" @click.prevent="$dispatch('open-logout-modal')"
                        class="w-full flex items-center gap-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 rounded-xl py-2.5 cursor-pointer text-left">
                        <i class="ti ti-logout text-base"></i>
                        <span>Keluar dari Akun</span>
                    </button>
                </li>
            </ul>
        </div>

    </div>

</header>
