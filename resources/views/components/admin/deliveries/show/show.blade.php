<div class="space-y-6 max-w-5xl">

    <!-- Header Section with Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1 print:hidden">
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Distribusi</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <a href="{{ route('admin.deliveries') }}" wire:navigate class="hover:text-brand-primary transition">Pengantaran</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary font-mono">{{ $delivery->delivery_number }}</span>
            </nav>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight font-mono">
                    {{ $delivery->delivery_number }}
                </h1>

                <!-- Status Indicator (Text Only, NO BADGE) -->
                <div class="flex items-center gap-2 px-3 py-1 bg-neutral-100 rounded-lg text-xs font-semibold uppercase tracking-wider">
                    <span class="size-2 rounded-full {{ $delivery->status === 'diproses' ? 'bg-amber-500' : ($delivery->status === 'dikirim' ? 'bg-blue-600' : ($delivery->status === 'selesai' ? 'bg-emerald-600' : 'bg-stone-400')) }}"></span>
                    <span class="{{ $delivery->status === 'diproses' ? 'text-amber-800' : ($delivery->status === 'dikirim' ? 'text-blue-800' : ($delivery->status === 'selesai' ? 'text-emerald-800' : 'text-stone-500 line-through')) }}">
                        {{ $delivery->status_label }}
                    </span>
                </div>
            </div>
            <p class="text-xs text-brand-warm-gray mt-1">
                Dibuat tanggal {{ $delivery->delivery_date?->translatedFormat('d F Y') ?? '-' }}
                @if ($delivery->creator)
                    oleh <strong class="text-brand-espresso">{{ $delivery->creator->name }}</strong>
                @endif
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-1.5 px-4 py-2 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                <i class="ti ti-printer text-base"></i>
                <span>Cetak Surat Jalan</span>
            </button>

            @can('pengantaran-edit')
                @if ($delivery->canBeEdited())
                    <a href="{{ route('admin.deliveries.edit', $delivery) }}" wire:navigate
                        class="inline-flex items-center gap-1.5 px-4 py-2 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-50 transition">
                        <i class="ti ti-edit text-base"></i>
                        <span>Edit</span>
                    </a>
                @endif

                @if ($delivery->status === 'diproses')
                    <button type="button" wire:click="startDelivery" wire:confirm="Mulai pengantaran surat jalan ini sekarang?"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer">
                        <i class="ti ti-truck-delivery text-base"></i>
                        <span>Mulai Pengantaran</span>
                    </button>
                @endif
            @endcan
        </div>
    </div>

    <!-- Handover Action Box (Active when on_delivery / dikirim) -->
    @if ($delivery->status === 'dikirim')
        <div class="bg-blue-50/50 border border-blue-200/80 rounded-2xl p-6 shadow-xs space-y-4 print:hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Konfirmasi Serah Terima di Toko</h2>
                    <p class="text-xs text-brand-warm-gray mt-0.5">
                        Barang sedang dalam perjalanan. Saat kurir tiba di lokasi dan barang diterima staf toko, isi data serah terima di bawah ini.
                    </p>
                </div>
                <div class="text-xs font-mono text-brand-warm-gray">
                    Berangkat: {{ $delivery->dispatched_at?->translatedFormat('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <form wire:submit="completeDelivery" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div>
                    <label for="recipient_name" class="block text-xs font-semibold text-brand-espresso mb-1">
                        Nama Penerima di Toko <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="recipient_name" wire:model="recipient_name"
                        placeholder="Contoh: Ibu Hj. Aminah / Kasir"
                        class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                    @error('recipient_name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="recipient_phone" class="block text-xs font-semibold text-brand-espresso mb-1">
                        No. HP Penerima (Opsional)
                    </label>
                    <input type="text" id="recipient_phone" wire:model="recipient_phone"
                        placeholder="Contoh: 081234567890"
                        class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary font-mono">
                    @error('recipient_phone')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="handover_notes" class="block text-xs font-semibold text-brand-espresso mb-1">
                        Catatan Serah Terima (Opsional)
                    </label>
                    <input type="text" id="handover_notes" wire:model="handover_notes"
                        placeholder="Contoh: Diterima lengkap 25 pouch di rak kaca depan"
                        class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                </div>

                <div class="md:col-span-3 flex items-center justify-end pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-800 hover:bg-emerald-900 text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                        <i class="ti ti-circle-check text-base"></i>
                        <span wire:loading.remove>Konfirmasi Barang Tiba &amp; Selesaikan</span>
                        <span wire:loading>Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Information Section (Printable Document Card) -->
    <div class="bg-white border border-brand-border rounded-2xl p-6 sm:p-8 shadow-xs space-y-8 print:border-none print:shadow-none print:p-0">

        <!-- Printable Document Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-brand-border">
            <div>
                <div class="text-xl font-extrabold text-brand-espresso">{{ $businessSetting->company_name }}</div>
                @if ($businessSetting->tagline)
                    <div class="text-xs text-brand-warm-gray mt-0.5">{{ $businessSetting->tagline }}</div>
                @endif
                <div class="text-xs text-brand-warm-gray">
                    {{ $businessSetting->address ?: 'Malang, Jawa Timur' }}
                    @if ($businessSetting->phone)
                        &bull; WhatsApp: {{ $businessSetting->phone }}
                    @endif
                </div>
            </div>

            <div class="text-left sm:text-right">
                <div class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray">Dokumen Resmi</div>
                <div class="text-lg font-bold font-mono text-brand-espresso mt-0.5">SURAT JALAN PENGANTARAN</div>
                <div class="text-xs text-brand-warm-gray mt-1">No: <strong class="font-mono text-brand-espresso">{{ $delivery->delivery_number }}</strong></div>
                <div class="text-xs text-brand-warm-gray">Tanggal: <strong class="text-brand-espresso">{{ $delivery->delivery_date?->translatedFormat('d F Y') ?? '-' }}</strong></div>
            </div>
        </div>

        <!-- 2 Column Details: Toko & Kurir -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 text-sm">
            <!-- Toko Mitra Tujuan -->
            <div class="space-y-2">
                <h3 class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray pb-1 border-b border-brand-border/60">
                    Toko Mitra Tujuan
                </h3>
                @if ($delivery->store)
                    <div class="text-base font-bold text-brand-espresso">{{ $delivery->store->name }}</div>
                    <div class="text-xs text-brand-warm-gray">{{ $delivery->store->address ?? 'Alamat belum diatur' }}</div>

                    <div class="pt-2 space-y-1 text-xs text-brand-warm-gray">
                        @if ($delivery->store->route)
                            <div>Rute Wilayah: <strong class="text-brand-espresso">{{ $delivery->store->route }}</strong></div>
                        @endif
                        @if ($delivery->store->owner_name)
                            <div>Nama Kontak / Pemilik: <strong class="text-brand-espresso">{{ $delivery->store->owner_name }}</strong></div>
                        @endif
                        @if ($delivery->store->phone)
                            <div>Telepon / WhatsApp: <strong class="text-brand-espresso font-mono">{{ $delivery->store->phone }}</strong></div>
                        @endif
                    </div>

                    <!-- Direct Google Maps Link -->
                    @if ($delivery->store->latitude && $delivery->store->longitude)
                        <div class="pt-2 print:hidden">
                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $delivery->store->latitude }},{{ $delivery->store->longitude }}"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-primary hover:underline">
                                <i class="ti ti-map-pin text-sm"></i>
                                <span>Buka Petunjuk Arah Google Maps ({{ $delivery->store->latitude }}, {{ $delivery->store->longitude }})</span>
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-xs text-brand-warm-gray italic">Data toko mitra tidak ditemukan</div>
                @endif
            </div>

            <!-- Kurir & Logistik -->
            <div class="space-y-2">
                <h3 class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray pb-1 border-b border-brand-border/60">
                    Petugas Kurir &amp; Status
                </h3>
                @if ($delivery->courier)
                    <div class="text-base font-bold text-brand-espresso">{{ $delivery->courier->name }}</div>
                    <div class="text-xs text-brand-warm-gray font-mono">{{ $delivery->courier->phone ?? $delivery->courier->email }}</div>
                @else
                    <div class="text-xs text-brand-warm-gray italic">Kurir belum ditugaskan</div>
                @endif

                <div class="pt-2 space-y-1 text-xs text-brand-warm-gray">
                    <div>Status Saat Ini: <strong class="text-brand-espresso">{{ $delivery->status_label }}</strong></div>
                    @if ($delivery->dispatched_at)
                        <div>Waktu Berangkat: <strong class="text-brand-espresso font-mono">{{ $delivery->dispatched_at->translatedFormat('d M Y H:i') }}</strong></div>
                    @endif
                    @if ($delivery->delivered_at)
                        <div>Waktu Selesai Serah Terima: <strong class="text-brand-espresso font-mono">{{ $delivery->delivered_at->translatedFormat('d M Y H:i') }}</strong></div>
                    @endif
                    @if ($delivery->recipient_name)
                        <div>Penerima di Toko: <strong class="text-brand-espresso">{{ $delivery->recipient_name }}</strong> {{ $delivery->recipient_phone ? "({$delivery->recipient_phone})" : '' }}</div>
                    @endif
                </div>

                @if ($delivery->notes)
                    <div class="pt-2">
                        <div class="text-xs font-semibold text-brand-espresso">Catatan Dokumen:</div>
                        <div class="text-xs text-brand-warm-gray mt-0.5 whitespace-pre-line bg-neutral-50 p-2.5 rounded-lg border border-brand-border/60">
                            {{ $delivery->notes }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tabel Item Muatan Surat Jalan -->
        <div class="space-y-3">
            <h3 class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray pb-1 border-b border-brand-border/60">
                Rincian Barang yang Diserahkan
            </h3>

            <div class="border border-brand-border rounded-xl overflow-hidden">
                <table class="w-full text-left text-sm text-brand-espresso">
                    <thead class="bg-neutral-50 border-b border-brand-border text-xs uppercase tracking-wider font-semibold text-brand-warm-gray">
                        <tr>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-12 text-center">No</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">Nama Produk Kemasan</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-32 text-center">Jumlah Kirim</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-40 text-right">Harga Setor</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-44 text-right">Subtotal Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border/60">
                        @foreach ($delivery->items as $idx => $item)
                            <tr>
                                <td class="px-4 py-3 text-center text-xs text-brand-warm-gray">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-brand-espresso">{{ $item->product?->name ?? 'Produk' }}</div>
                                    <div class="text-xs text-brand-warm-gray">
                                        Satuan: {{ $item->product?->unit ?? 'pcs' }}
                                        @if ($item->notes)
                                            &bull; {{ $item->notes }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-brand-espresso font-mono whitespace-nowrap">
                                    {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product?->unit ?? 'kemasan' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-brand-espresso whitespace-nowrap">
                                    Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-brand-espresso whitespace-nowrap">
                                    Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-neutral-50 border-t border-brand-border font-bold text-brand-espresso">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-xs uppercase tracking-wider">
                                Total Keseluruhan
                            </td>
                            <td class="px-4 py-3 text-center font-mono whitespace-nowrap">
                                {{ number_format($delivery->total_items, 0, ',', '.') }} kemasan
                            </td>
                            <td></td>
                            <td class="px-4 py-3 text-right font-mono text-base whitespace-nowrap">
                                Rp {{ number_format($delivery->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Kolom Tanda Tangan Serah Terima (Print Friendly) -->
        <div class="pt-8 border-t border-brand-border/60 grid grid-cols-3 gap-6 text-center text-xs">
            <div>
                <p class="font-semibold text-brand-espresso">Dibuat / Disiapkan</p>
                <div class="h-20 flex items-end justify-center">
                    <p class="border-b border-brand-espresso/50 w-36 pb-1 font-medium">
                        {{ $delivery->creator?->name ?? '( Staf Gudang )' }}
                    </p>
                </div>
            </div>

            <div>
                <p class="font-semibold text-brand-espresso">Petugas Kurir</p>
                <div class="h-20 flex items-end justify-center">
                    <p class="border-b border-brand-espresso/50 w-36 pb-1 font-medium">
                        {{ $delivery->courier?->name ?? '( Kurir )' }}
                    </p>
                </div>
            </div>

            <div>
                <p class="font-semibold text-brand-espresso">Penerima Toko Mitra</p>
                <div class="h-20 flex items-end justify-center">
                    <p class="border-b border-brand-espresso/50 w-36 pb-1 font-medium">
                        {{ $delivery->recipient_name ?: '( Staf / Pemilik Toko )' }}
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Bottom Cancel Option (If not completed) -->
    @can('pengantaran-edit')
        @if ($delivery->status !== 'selesai' && $delivery->status !== 'dibatalkan')
            <div class="flex items-center justify-between p-4 bg-neutral-50 rounded-2xl border border-brand-border text-xs print:hidden">
                <span class="text-brand-warm-gray">Perlu membatalkan surat jalan dan mengembalikan stok ke gudang?</span>
                <button type="button" wire:click="cancelDelivery" wire:confirm="Batalkan surat jalan ini? Stok produk akan otomatis dikembalikan ke gudang."
                    class="px-3.5 py-1.5 text-xs font-semibold text-amber-800 hover:text-amber-900 hover:bg-amber-100 rounded-lg transition cursor-pointer">
                    Batalkan Surat Jalan
                </button>
            </div>
        @endif
    @endcan

</div>
