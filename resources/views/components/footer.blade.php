<!-- Footer Component -->
<footer id="kontak" class="bg-white border-t border-brand-border pt-14 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-12 pb-12">

            <!-- Col 1: Brand Info -->
            <div class="lg:col-span-4 space-y-3">
                <a href="{{ route('home') }}" class="inline-block hover:opacity-90 transition">
                    <img src="{{ asset('assets/logo/logo.webp') }}" alt="Halala Food Logo"
                        class="h-10 w-auto object-contain">
                </a>
                <p class="text-xs sm:text-sm text-brand-warm-gray leading-relaxed">
                    Camilan Tradisional, Rasa Modern
                </p>
            </div>

            <!-- Col 2: Menu -->
            <div class="lg:col-span-2 space-y-3">
                <h5 class="font-bold text-sm text-brand-espresso">Menu</h5>
                <ul class="space-y-2 text-xs sm:text-sm">
                    <li><a href="{{ route('home') }}#beranda"
                            class="text-brand-warm-gray hover:text-brand-primary transition">Beranda</a></li>
                    <li><a href="{{ route('home') }}#produk"
                            class="text-brand-warm-gray hover:text-brand-primary transition">Produk</a></li>
                    <li><a href="{{ route('home') }}#tentang-kami"
                            class="text-brand-warm-gray hover:text-brand-primary transition">Tentang Kami</a></li>
                    <li><a href="{{ route('home') }}#kontak"
                            class="text-brand-warm-gray hover:text-brand-primary transition">Kontak</a></li>
                </ul>
            </div>

            <!-- Col 3: Produk -->
            <div class="lg:col-span-2 space-y-3">
                <h5 class="font-bold text-sm text-brand-espresso">Produk</h5>
                <ul class="space-y-2 text-xs sm:text-sm">
                    <li><a href="{{ route('home') }}#produk" class="text-brand-warm-gray hover:text-brand-primary transition">Marie
                            Wijen</a></li>
                    <li><a href="{{ route('home') }}#produk"
                            class="text-brand-warm-gray hover:text-brand-primary transition">Ting-Ting Susu</a>
                    </li>
                </ul>
            </div>

            <!-- Col 4: Kontak Kami -->
            <div class="lg:col-span-2 space-y-3">
                <h5 class="font-bold text-sm text-brand-espresso">Kontak Kami</h5>
                <ul class="space-y-2.5 text-xs sm:text-sm text-brand-warm-gray">
                    <li class="flex items-center gap-2">
                        <i class="ti ti-phone text-brand-primary text-base shrink-0"></i>
                        <span>0812 3456 7890</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ti ti-mail text-brand-primary text-base shrink-0"></i>
                        <span class="break-all">info@halala-food.id</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="ti ti-map-pin text-brand-primary text-base shrink-0"></i>
                        <span>Pekanbaru, Riau</span>
                    </li>
                </ul>
            </div>

            <!-- Col 5: Ikuti Kami -->
            <div class="lg:col-span-2 space-y-3">
                <h5 class="font-bold text-sm text-brand-espresso">Ikuti Kami</h5>
                <div class="flex items-center gap-2.5">
                    <a href="https://instagram.com" target="_blank" aria-label="Instagram"
                        class="size-9 rounded-full bg-brand-espresso text-white flex items-center justify-center hover:bg-brand-primary transition">
                        <i class="ti ti-brand-instagram text-lg"></i>
                    </a>
                    <a href="https://facebook.com" target="_blank" aria-label="Facebook"
                        class="size-9 rounded-full bg-brand-espresso text-white flex items-center justify-center hover:bg-brand-primary transition">
                        <i class="ti ti-brand-facebook text-lg"></i>
                    </a>
                    <a href="https://wa.me/6281234567890" target="_blank" aria-label="WhatsApp"
                        class="size-9 rounded-full bg-brand-espresso text-white flex items-center justify-center hover:bg-brand-primary transition">
                        <i class="ti ti-brand-whatsapp text-lg"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- Bottom Copyright -->
        <div class="pt-8 border-t border-brand-border text-center text-xs text-brand-warm-gray">
            &copy; {{ date('Y') }} Halala Food. Semua Hak Dilindungi.
        </div>
    </div>
</footer>
