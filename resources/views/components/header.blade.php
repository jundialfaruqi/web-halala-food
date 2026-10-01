<!-- Header / Navbar Component -->
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-brand-border transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">

            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0 transition hover:opacity-90">
                <img src="{{ asset('assets/logo/logo.webp') }}" alt="Halala Food Logo"
                    class="h-10 sm:h-12 w-auto object-contain">
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-1 lg:space-x-2 text-sm font-medium">
                <a href="{{ route('home') }}#beranda"
                    class="relative px-4 py-2 {{ request()->routeIs('home') ? 'text-brand-primary font-semibold' : 'text-brand-warm-gray hover:text-brand-primary' }} transition rounded-full hover:bg-brand-soft-cream/60">
                    Beranda
                    @if(request()->routeIs('home'))
                        <span class="absolute bottom-0 left-4 right-4 h-0.5 bg-brand-primary rounded-full"></span>
                    @endif
                </a>
                <a href="{{ route('home') }}#produk"
                    class="px-4 py-2 text-brand-warm-gray hover:text-brand-primary transition rounded-full hover:bg-brand-soft-cream/60">
                    Produk
                </a>
                <a href="{{ route('home') }}#tentang-kami"
                    class="px-4 py-2 text-brand-warm-gray hover:text-brand-primary transition rounded-full hover:bg-brand-soft-cream/60">
                    Tentang Kami
                </a>
                <a href="{{ route('home') }}#testimoni"
                    class="px-4 py-2 text-brand-warm-gray hover:text-brand-primary transition rounded-full hover:bg-brand-soft-cream/60">
                    Testimoni
                </a>
                <a href="{{ route('home') }}#kontak"
                    class="px-4 py-2 text-brand-warm-gray hover:text-brand-primary transition rounded-full hover:bg-brand-soft-cream/60">
                    Kontak
                </a>
            </nav>

            <!-- Right Actions (Search, Cart & Login) -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button type="button" aria-label="Cari Produk"
                    class="p-2.5 text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/70 rounded-full transition">
                    <i class="ti ti-search text-xl"></i>
                </button>

                <a href="{{ route('home') }}#produk" aria-label="Keranjang Belanja"
                    class="relative p-2.5 text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/70 rounded-full transition">
                    <i class="ti ti-shopping-bag text-xl"></i>
                    <span
                        class="absolute top-1.5 right-1.5 size-4 bg-brand-primary text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                        0
                    </span>
                </a>

                <a href="{{ route('login') }}" aria-label="Masuk ke Akun"
                    class="p-2.5 {{ request()->routeIs('login') ? 'text-brand-primary bg-brand-soft-cream' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/70' }} rounded-full transition"
                    title="Masuk ke Akun">
                    <i class="ti ti-user text-xl"></i>
                </a>

                <!-- Mobile Menu Button -->
                <button type="button"
                    class="md:hidden p-2 text-brand-espresso hover:text-brand-primary rounded-lg transition"
                    onclick="document.getElementById('global-mobile-drawer').classList.toggle('hidden')">
                    <i class="ti ti-menu-2 text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div id="global-mobile-drawer"
        class="hidden md:hidden border-t border-brand-border bg-white px-4 pt-3 pb-6 space-y-2 shadow-xl">
        <a href="{{ route('home') }}#beranda"
            class="block px-4 py-2.5 rounded-xl font-semibold {{ request()->routeIs('home') ? 'text-brand-primary bg-brand-soft-cream/70' : 'text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/40' }}">Beranda</a>
        <a href="{{ route('home') }}#produk"
            class="block px-4 py-2.5 rounded-xl font-medium text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/40">Produk</a>
        <a href="{{ route('home') }}#tentang-kami"
            class="block px-4 py-2.5 rounded-xl font-medium text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/40">Tentang Kami</a>
        <a href="{{ route('home') }}#testimoni"
            class="block px-4 py-2.5 rounded-xl font-medium text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/40">Testimoni</a>
        <a href="{{ route('home') }}#kontak"
            class="block px-4 py-2.5 rounded-xl font-medium text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/40">Kontak</a>
        <a href="{{ route('login') }}"
            class="block px-4 py-2.5 rounded-xl font-semibold text-white bg-brand-primary hover:bg-brand-primary-hover transition text-center">Masuk ke Akun</a>
    </div>
</header>
