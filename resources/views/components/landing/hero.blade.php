<!-- Hero Section -->
<section id="beranda" class="relative overflow-hidden pt-8 pb-16 md:pt-14 md:pb-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">

            <!-- Left Content -->
            <div class="lg:col-span-6 space-y-6">

                <!-- Badge Subheading -->
                <div class="inline-flex items-center gap-2">
                    <span class="text-brand-primary font-medium tracking-wide text-sm md:text-base italic">
                        — Camilan Tradisional
                    </span>
                </div>

                <!-- Main Heading -->
                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-bold text-brand-espresso tracking-tight leading-[1.15]">
                    Rasa Sederhana,<br>
                    <span class="text-brand-espresso">Selalu Istimewa</span>
                </h1>

                <!-- Description -->
                <p class="text-brand-warm-gray text-base sm:text-lg leading-relaxed max-w-xl">
                    Marie Wijen dan Ting-Ting Susu, camilan tradisional dengan cita rasa autentik yang selalu
                    bikin rindu.
                </p>

                <!-- CTA Action Buttons -->
                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="#produk"
                        class="inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-full bg-brand-primary text-white font-semibold text-sm sm:text-base shadow-sm hover:bg-brand-primary-hover hover:shadow-md transition-all">
                        <i class="ti ti-cookie text-lg"></i>
                        <span>Lihat Produk Kami</span>
                    </a>
                    <a href="#tentang-kami"
                        class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-full border border-brand-border bg-white text-brand-espresso font-semibold text-sm sm:text-base hover:border-brand-primary hover:text-brand-primary transition-all">
                        <span>Tentang Kami</span>
                        <i class="ti ti-chevron-right text-base"></i>
                    </a>
                </div>

                <!-- Value Badges -->
                <div class="pt-8 border-t border-brand-border/80 grid grid-cols-1 sm:grid-cols-3 gap-4">

                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-full bg-brand-soft-cream flex items-center justify-center text-brand-primary shrink-0 border border-brand-honey/40">
                            <i class="ti ti-leaf text-xl"></i>
                        </div>
                        <div class="text-xs sm:text-sm font-semibold text-brand-espresso leading-snug">
                            Bahan Pilihan<br><span class="text-brand-warm-gray font-normal">Berkualitas</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-full bg-brand-soft-cream flex items-center justify-center text-brand-primary shrink-0 border border-brand-honey/40">
                            <i class="ti ti-shield-check text-xl"></i>
                        </div>
                        <div class="text-xs sm:text-sm font-semibold text-brand-espresso leading-snug">
                            Tanpa Pengawet<br><span class="text-brand-warm-gray font-normal">Buatan</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div
                            class="size-10 rounded-full bg-brand-soft-cream flex items-center justify-center text-brand-primary shrink-0 border border-brand-honey/40">
                            <i class="ti ti-mood-smile text-xl"></i>
                        </div>
                        <div class="text-xs sm:text-sm font-semibold text-brand-espresso leading-snug">
                            Rasa Lezat<br><span class="text-brand-warm-gray font-normal">dan Renyah</span>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Right Visual Image with Playful Annotations -->
            <div class="lg:col-span-6 relative">
                <div
                    class="relative mx-auto max-w-lg lg:max-w-none rounded-3xl overflow-hidden border border-brand-border/80 bg-white shadow-xl shadow-brand-espresso/5 group">

                    <!-- Main Product Composition Image -->
                    <img src="{{ asset('assets/images/hero_products.webp') }}"
                        alt="Marie Wijen dan Ting-Ting Susu Halala Food"
                        class="w-full h-auto object-cover transform group-hover:scale-102 transition duration-700">

                    <!-- Handwritten Annotation: Marie Wijen -->
                    <div class="absolute top-4 left-6 sm:top-7 sm:left-10 z-20 pointer-events-none select-none">
                        <div class="flex flex-col items-center">
                            <span
                                class="text-brand-espresso font-bold text-base sm:text-xl font-serif tracking-tight leading-tight drop-shadow-xs">
                                Marie<br>Wijen
                            </span>
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-brand-primary -mt-0.5 sm:-mt-1 transform rotate-12"
                                viewBox="0 0 50 50" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round">
                                <path d="M15 10 Q 25 30 35 40 M28 38 L35 40 L36 32" />
                            </svg>
                        </div>
                    </div>

                    <!-- Handwritten Annotation: Ting-Ting Susu -->
                    <div
                        class="absolute top-8 sm:top-25 right-6 sm:right-10 z-20 pointer-events-none select-none">
                        <div class="flex flex-col items-center">
                            <span
                                class="text-brand-espresso font-bold text-base sm:text-xl font-serif tracking-tight leading-tight drop-shadow-xs">
                                Ting-Ting<br>Susu
                            </span>
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-brand-primary -mt-0.5 sm:-mt-1 transform -rotate-12"
                                viewBox="0 0 50 50" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round">
                                <path d="M35 10 Q 25 30 15 40 M22 38 L15 40 L14 32" />
                            </svg>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>
