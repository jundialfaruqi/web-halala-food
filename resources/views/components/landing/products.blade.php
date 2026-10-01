<!-- Section: Produk Kami -->
<section id="produk" class="py-16 md:py-24 bg-white border-t border-brand-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <div class="text-center max-w-2xl mx-auto space-y-3 mb-14">
            <h2 class="text-3xl sm:text-4xl font-bold text-brand-espresso tracking-tight">
                Produk Kami
            </h2>
            <p class="text-brand-warm-gray text-base sm:text-lg">
                Dua varian camilan favorit yang selalu jadi pilihan.
            </p>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10 max-w-5xl mx-auto">

            <!-- Product 1: Marie Wijen -->
            <div
                class="group bg-white rounded-3xl border border-brand-border p-4 sm:p-5 transition-all duration-300 hover:border-brand-primary/40 hover:shadow-xl">

                <!-- Product Image Box -->
                <div class="relative aspect-4/3 rounded-2xl overflow-hidden bg-brand-soft-cream/30">
                    <img src="{{ asset('assets/images/marie_wijen.jpg') }}" alt="Marie Wijen"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">

                    <!-- Badge -->
                    <div class="absolute top-3.5 left-3.5">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-honey text-brand-espresso shadow-sm">
                            Best Seller
                        </span>
                    </div>
                </div>

                <!-- Product Content -->
                <div class="pt-5 px-1 space-y-2">
                    <h3
                        class="text-xl sm:text-2xl font-bold text-brand-espresso group-hover:text-brand-primary transition">
                        Marie Wijen
                    </h3>
                    <p class="text-sm sm:text-base text-brand-warm-gray leading-relaxed">
                        Biskuit renyah dengan taburan wijen pilihan yang gurih dan lezat.
                    </p>

                    <!-- Price & CTA -->
                    <div class="pt-4 flex items-center justify-between">
                        <div>
                            <span class="text-xl sm:text-2xl font-bold text-brand-primary">
                                Rp 25.000
                            </span>
                        </div>
                        <a href="https://wa.me/6281234567890?text=Halo%20Halala%20Food,%20saya%20ingin%20pesan%20Marie%20Wijen"
                            target="_blank"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-brand-primary text-white font-medium text-sm hover:bg-brand-primary-hover shadow-sm transition">
                            <i class="ti ti-shopping-cart text-base"></i>
                            <span>Beli Sekarang</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- Product 2: Ting-Ting Susu -->
            <div
                class="group bg-white rounded-3xl border border-brand-border p-4 sm:p-5 transition-all duration-300 hover:border-brand-primary/40 hover:shadow-xl">

                <!-- Product Image Box -->
                <div class="relative aspect-4/3 rounded-2xl overflow-hidden bg-brand-soft-cream/30">
                    <img src="{{ asset('assets/images/ting_ting_susu.jpg') }}" alt="Ting-Ting Susu"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">

                    <!-- Badge -->
                    <div class="absolute top-3.5 left-3.5">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-brand-soft-cream text-brand-primary border border-brand-honey/50 shadow-sm">
                            Favorit
                        </span>
                    </div>
                </div>

                <!-- Product Content -->
                <div class="pt-5 px-1 space-y-2">
                    <h3
                        class="text-xl sm:text-2xl font-bold text-brand-espresso group-hover:text-brand-primary transition">
                        Ting-Ting Susu
                    </h3>
                    <p class="text-sm sm:text-base text-brand-warm-gray leading-relaxed">
                        Camilan manis dengan rasa susu yang lembut dan bikin ketagihan.
                    </p>

                    <!-- Price & CTA -->
                    <div class="pt-4 flex items-center justify-between">
                        <div>
                            <span class="text-xl sm:text-2xl font-bold text-brand-primary">
                                Rp 25.000
                            </span>
                        </div>
                        <a href="https://wa.me/6281234567890?text=Halo%20Halala%20Food,%20saya%20ingin%20pesan%20Ting-Ting%20Susu"
                            target="_blank"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-brand-primary text-white font-medium text-sm hover:bg-brand-primary-hover shadow-sm transition">
                            <i class="ti ti-shopping-cart text-base"></i>
                            <span>Beli Sekarang</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>
</section>
