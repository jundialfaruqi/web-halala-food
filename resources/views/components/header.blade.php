<!-- Header / Navbar Component -->
@php
    $navLinks = [
        ['id' => '#beranda', 'label' => 'Beranda'],
        ['id' => '#produk', 'label' => 'Produk'],
        ['id' => '#tentang-kami', 'label' => 'Tentang Kami'],
        ['id' => '#testimoni', 'label' => 'Testimoni'],
        ['id' => '#kontak', 'label' => 'Kontak'],
    ];
@endphp

<header x-data="{
    activeTab: (window.location.hash && ['#beranda', '#produk', '#tentang-kami', '#testimoni', '#kontak'].includes(window.location.hash)) ? window.location.hash : '#beranda',
    isHome: {{ request()->routeIs('home') ? 'true' : 'false' }},
    init() {
        if (!this.isHome) return;

        // Sync on manual hash change
        window.addEventListener('hashchange', () => {
            if (window.location.hash) {
                this.activeTab = window.location.hash;
            }
        });

        // Scrollspy observer for real-time section highlight
        const sectionIds = ['beranda', 'produk', 'tentang-kami', 'testimoni', 'kontak'];
        const sections = sectionIds.map(id => document.getElementById(id)).filter(Boolean);

        if (sections.length > 0) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.activeTab = '#' + entry.target.id;
                    }
                });
            }, {
                rootMargin: '-25% 0px -50% 0px',
                threshold: 0.1
            });

            sections.forEach(sec => observer.observe(sec));
        }
    },
    setActive(tab) {
        this.activeTab = tab;
    }
}" class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-brand-border transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">

            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0 transition hover:opacity-90">
                <img src="{{ asset('assets/logo/logo.webp') }}" alt="Halala Food Logo"
                    class="h-10 sm:h-12 w-auto object-contain">
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-1 lg:space-x-2 text-sm font-medium">
                @foreach($navLinks as $link)
                    <a href="{{ route('home') }}{{ $link['id'] }}"
                        @click="if (isHome) setActive('{{ $link['id'] }}')"
                        :class="(isHome && activeTab === '{{ $link['id'] }}') 
                            ? 'text-brand-primary font-semibold' 
                            : 'text-brand-warm-gray hover:text-brand-primary'"
                        class="relative px-4 py-2 transition rounded-full hover:bg-brand-soft-cream/60">
                        {{ $link['label'] }}
                        
                        <span 
                            x-show="isHome && activeTab === '{{ $link['id'] }}'"
                            x-transition.opacity.duration.200ms
                            class="absolute bottom-0 left-4 right-4 h-0.5 bg-brand-primary rounded-full">
                        </span>
                    </a>
                @endforeach
            </nav>

            <!-- Right Actions (Search, Cart & Login) -->
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('login') }}" aria-label="Masuk ke Akun"
                    class="size-10 flex items-center justify-center {{ request()->routeIs('login') ? 'text-brand-primary bg-brand-soft-cream' : 'text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/70' }} rounded-full transition"
                    title="Masuk ke Akun">
                    <i class="ti ti-user text-xl"></i>
                </a>

                <!-- Mobile Menu Button -->
                <button type="button"
                    class="md:hidden size-10 flex items-center justify-center text-brand-espresso hover:text-brand-primary hover:bg-brand-soft-cream/70 rounded-full transition cursor-pointer"
                    aria-label="Toggle menu"
                    onclick="document.getElementById('global-mobile-drawer').classList.toggle('hidden')">
                    <i class="ti ti-menu-2 text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div id="global-mobile-drawer"
        class="hidden md:hidden border-t border-brand-border bg-white px-4 pt-3 pb-6 space-y-2 shadow-xl">
        @foreach($navLinks as $link)
            <a href="{{ route('home') }}{{ $link['id'] }}"
                @click="if (isHome) { setActive('{{ $link['id'] }}'); document.getElementById('global-mobile-drawer').classList.add('hidden'); }"
                :class="(isHome && activeTab === '{{ $link['id'] }}') 
                    ? 'text-brand-primary bg-brand-soft-cream/70 font-semibold' 
                    : 'text-brand-warm-gray hover:text-brand-primary hover:bg-brand-soft-cream/40 font-medium'"
                class="block px-4 py-2.5 rounded-xl transition">
                {{ $link['label'] }}
            </a>
        @endforeach
        <a href="{{ route('login') }}"
            class="block px-4 py-2.5 rounded-xl font-semibold text-white bg-brand-primary hover:bg-brand-primary-hover transition text-center">
            Masuk ke Akun
        </a>
    </div>
</header>
