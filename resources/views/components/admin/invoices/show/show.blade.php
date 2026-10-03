<div class="space-y-6 max-w-5xl" x-data="{
    // Toast State
    toastMessage: '',
    toastType: 'success',
    showToast: false,

    triggerToast(message, type = 'success') {
        this.toastMessage = message;
        this.toastType = type;
        this.showToast = true;
        setTimeout(() => { this.showToast = false; }, 3500);
    },

    init() {
        @if ($flashToast = session('toast') ?? (session('success') ? ['message' => session('success'), 'type' => 'success'] : null))
            const toastData = {{ \Illuminate\Support\Js::from($flashToast) }};
            this.$nextTick(() => {
                if (typeof toastData === 'object' && toastData.message) {
                    this.triggerToast(toastData.message, toastData.type || 'success');
                } else {
                    this.triggerToast(toastData, 'success');
                }
            });
        @endif
    }
}">

    <!-- Toast Notification (Minimalist) -->
    <div x-show="showToast" x-transition.opacity.duration.200ms
        class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 bg-brand-espresso text-white rounded-xl shadow-lg border border-brand-warm-gray/20 text-sm font-medium print:hidden"
        style="display: none;">
        <span x-text="toastMessage"></span>
        <button type="button" @click="showToast = false" class="text-white/60 hover:text-white ml-2 text-base">
            <i class="ti ti-x"></i>
        </button>
    </div>

    <!-- Header Section with Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1 print:hidden">
        <div>
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-warm-gray mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-brand-primary transition">Admin</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span>Keuangan</span>
                <i class="ti ti-chevron-right text-xs"></i>
                <a href="{{ route('admin.invoices') }}" wire:navigate class="hover:text-brand-primary transition">Faktur Tagihan</a>
                <i class="ti ti-chevron-right text-xs"></i>
                <span class="text-brand-primary font-mono">{{ $invoice->invoice_number }}</span>
            </nav>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-espresso tracking-tight font-mono">
                    {{ $invoice->invoice_number }}
                </h1>

                <!-- Status Indicator (Text Only, NO BADGE) -->
                <div class="flex items-center gap-2 px-3 py-1 bg-neutral-100 rounded-lg text-xs font-semibold uppercase tracking-wider">
                    <span class="size-2 rounded-full {{ $invoice->status === 'lunas' ? 'bg-emerald-600' : ($invoice->status === 'sebagian' ? 'bg-blue-600' : ($invoice->is_overdue ? 'bg-red-600' : ($invoice->status === 'belum_dibayar' ? 'bg-amber-500' : 'bg-stone-400'))) }}"></span>
                    <span class="{{ $invoice->status === 'lunas' ? 'text-emerald-800' : ($invoice->status === 'sebagian' ? 'text-blue-800' : ($invoice->is_overdue ? 'text-red-700 font-bold' : ($invoice->status === 'belum_dibayar' ? 'text-amber-800' : 'text-stone-500 line-through'))) }}">
                        {{ $invoice->is_overdue && $invoice->status !== 'lunas' ? 'Jatuh Tempo' : $invoice->status_label }}
                    </span>
                </div>
            </div>
            <p class="text-xs text-brand-warm-gray mt-1">
                Diterbitkan tanggal {{ $invoice->invoice_date?->translatedFormat('d F Y') ?? '-' }}
                &bull; Jatuh tempo {{ $invoice->due_date?->translatedFormat('d F Y') ?? '-' }}
                @if ($invoice->creator)
                    &bull; Dibuat oleh <strong class="text-brand-espresso">{{ $invoice->creator->name }}</strong>
                @endif
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-1.5 px-4 py-2 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-50 transition cursor-pointer">
                <i class="ti ti-printer text-base"></i>
                <span>Cetak Faktur</span>
            </button>

            @can('faktur-edit')
                @if ($invoice->status !== 'lunas' && $invoice->status !== 'dibatalkan')
                    <a href="{{ route('admin.invoices.edit', $invoice) }}" wire:navigate
                        class="inline-flex items-center gap-1.5 px-4 py-2 border border-brand-border rounded-xl text-sm font-semibold text-brand-espresso hover:bg-neutral-50 transition">
                        <i class="ti ti-edit text-base"></i>
                        <span>Edit Faktur</span>
                    </a>
                @endif
            @endcan
        </div>
    </div>

    <!-- Summary Numbers Bar (Clean, Minimalist Text) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 print:hidden">
        <div class="p-4 bg-white border border-brand-border rounded-2xl">
            <div class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Total Tagihan</div>
            <div class="text-xl font-extrabold text-brand-espresso font-mono mt-1">
                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
            </div>
            <div class="text-xs text-brand-warm-gray mt-0.5">
                {{ $invoice->items->count() }} item produk
            </div>
        </div>

        <div class="p-4 bg-white border border-brand-border rounded-2xl">
            <div class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Sudah Dibayar</div>
            <div class="text-xl font-extrabold text-emerald-800 font-mono mt-1">
                Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
            </div>
            <div class="text-xs text-brand-warm-gray mt-0.5">
                {{ $invoice->payments->count() }} transaksi pembayaran
            </div>
        </div>

        <div class="p-4 bg-white border border-brand-border rounded-2xl">
            <div class="text-xs font-semibold text-brand-warm-gray uppercase tracking-wider">Sisa Piutang</div>
            <div class="text-xl font-extrabold {{ $invoice->remaining_balance > 0 ? ($invoice->is_overdue ? 'text-red-700' : 'text-amber-700') : 'text-stone-400' }} font-mono mt-1">
                Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
            </div>
            <div class="text-xs {{ $invoice->is_overdue ? 'text-red-600 font-medium' : 'text-brand-warm-gray' }} mt-0.5">
                {{ $invoice->remaining_balance <= 0 ? 'Telah lunas terbayar' : ($invoice->is_overdue ? 'Lewat jatuh tempo' : 'Belum lunas') }}
            </div>
        </div>
    </div>

    <!-- Quick Payment Recording Form (Visible if remaining balance > 0 and not cancelled) -->
    @can('faktur-edit')
        @if ($invoice->status !== 'dibatalkan' && $invoice->remaining_balance > 0)
            <div class="bg-neutral-50/70 border border-brand-border rounded-2xl p-6 shadow-xs space-y-4 print:hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-brand-espresso">Catat Pembayaran Masuk</h2>
                        <p class="text-xs text-brand-warm-gray mt-0.5">
                            Rekam setoran tunai, transfer, atau QRIS dari toko mitra untuk faktur ini.
                        </p>
                    </div>
                    <div>
                        <button type="button" wire:click="fillFullPayment"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-brand-border bg-white rounded-lg text-xs font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer">
                            <i class="ti ti-check text-xs"></i>
                            <span>Isi Pelunasan Penuh (Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }})</span>
                        </button>
                    </div>
                </div>

                <form wire:submit="recordPayment" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                    <div>
                        <label for="payment_amount" class="block text-xs font-semibold text-brand-espresso mb-1">
                            Nominal Bayar (Rp) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="payment_amount" wire:model="payment_amount" step="1000" min="1" max="{{ $invoice->remaining_balance }}"
                            placeholder="Contoh: 150000"
                            class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                        @error('payment_amount')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_date" class="block text-xs font-semibold text-brand-espresso mb-1">
                            Tanggal Pembayaran <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="payment_date" wire:model="payment_date"
                            class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                        @error('payment_date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_method" class="block text-xs font-semibold text-brand-espresso mb-1">
                            Metode Pembayaran <span class="text-red-500">*</span>
                        </label>
                        <select id="payment_method" wire:model="payment_method"
                            class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                            <option value="tunai">Tunai (Cash / Staf)</option>
                            <option value="transfer_bank">Transfer Bank</option>
                            <option value="qris">QRIS Toko / QRIS Halala</option>
                        </select>
                        @error('payment_method')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference_number" class="block text-xs font-semibold text-brand-espresso mb-1">
                            No. Referensi / Bukti (Opsional)
                        </label>
                        <input type="text" id="reference_number" wire:model="reference_number"
                            placeholder="Contoh: TF-BCA-10294"
                            class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso font-mono focus:outline-none focus:border-brand-primary">
                        @error('reference_number')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 md:col-span-3">
                        <label for="payment_notes" class="block text-xs font-semibold text-brand-espresso mb-1">
                            Catatan Pembayaran (Opsional)
                        </label>
                        <input type="text" id="payment_notes" wire:model="payment_notes"
                            placeholder="Contoh: Setoran dititipkan lewat kurir Budi"
                            class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-none focus:border-brand-primary">
                    </div>

                    <div class="flex items-end justify-end">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                            <i class="ti ti-wallet text-base"></i>
                            <span wire:loading.remove>Simpan Pembayaran</span>
                            <span wire:loading>Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    @endcan

    <!-- Printable Invoice Document Card -->
    <div class="bg-white border border-brand-border rounded-2xl p-6 sm:p-8 shadow-xs space-y-8 print:border-none print:shadow-none print:p-0">

        <!-- Document Header -->
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
                <div class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray">Dokumen Penagihan Resmi</div>
                <div class="text-lg font-bold font-mono text-brand-espresso mt-0.5">FAKTUR PENAGIHAN KONSINYASI</div>
                <div class="text-xs text-brand-warm-gray mt-1">No Faktur: <strong class="font-mono text-brand-espresso">{{ $invoice->invoice_number }}</strong></div>
                <div class="text-xs text-brand-warm-gray">Tanggal Terbit: <strong class="text-brand-espresso">{{ $invoice->invoice_date?->translatedFormat('d F Y') ?? '-' }}</strong></div>
                <div class="text-xs text-brand-warm-gray">Jatuh Tempo: <strong class="text-brand-espresso">{{ $invoice->due_date?->translatedFormat('d F Y') ?? '-' }}</strong></div>
            </div>
        </div>

        <!-- 2 Column Details: Toko & Ketentuan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 text-sm">
            <!-- Toko Mitra Tujuan -->
            <div class="space-y-2">
                <h3 class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray pb-1 border-b border-brand-border/60">
                    Ditagihkan Kepada
                </h3>
                @if ($invoice->store)
                    <div class="text-base font-bold text-brand-espresso">{{ $invoice->store->name }}</div>
                    <div class="text-xs text-brand-warm-gray">{{ $invoice->store->address ?? 'Alamat belum diatur' }}</div>

                    <div class="pt-2 space-y-1 text-xs text-brand-warm-gray">
                        @if ($invoice->store->route)
                            <div>Rute Wilayah: <strong class="text-brand-espresso">{{ $invoice->store->route }}</strong></div>
                        @endif
                        @if ($invoice->store->owner_name)
                            <div>Pemilik / Kontak: <strong class="text-brand-espresso">{{ $invoice->store->owner_name }}</strong></div>
                        @endif
                        @if ($invoice->store->phone)
                            <div>Telepon / WhatsApp: <strong class="text-brand-espresso font-mono">{{ $invoice->store->phone }}</strong></div>
                        @endif
                    </div>
                @else
                    <div class="text-xs text-brand-warm-gray italic">Data toko mitra tidak ditemukan</div>
                @endif
            </div>

            <!-- Ketentuan & Info Pembayaran -->
            <div class="space-y-2">
                <h3 class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray pb-1 border-b border-brand-border/60">
                    Informasi &amp; Rekening Pembayaran
                </h3>

                <div class="space-y-1 text-xs text-brand-warm-gray">
                    <div>Status Pembayaran: <strong class="text-brand-espresso">{{ $invoice->status_label }}</strong></div>
                    @if ($invoice->delivery)
                        <div>Dasar Pengantaran:
                            <a href="{{ route('admin.deliveries.show', $invoice->delivery) }}" class="text-brand-primary hover:underline font-mono">
                                {{ $invoice->delivery->delivery_number }}
                            </a>
                            ({{ $invoice->delivery->delivery_date?->translatedFormat('d M Y') }})
                        </div>
                    @endif
                    @if (! empty($businessSetting->bank_accounts))
                        <div class="pt-2">
                            <div class="font-semibold text-brand-espresso">Rekening Resmi Pembayaran:</div>
                            @foreach ($businessSetting->bank_accounts as $bank)
                                <div class="font-mono text-brand-espresso font-bold mt-0.5">
                                    {{ $bank['bank_name'] ?? 'Bank' }}: {{ $bank['account_number'] ?? '-' }} {{ !empty($bank['account_name']) ? "a.n {$bank['account_name']}" : '' }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($invoice->notes)
                    <div class="pt-2">
                        <div class="text-xs font-semibold text-brand-espresso">Catatan Faktur:</div>
                        <div class="text-xs text-brand-warm-gray mt-0.5 whitespace-pre-line bg-neutral-50 p-2.5 rounded-lg border border-brand-border/60">
                            {{ $invoice->notes }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tabel Item Rincian Tagihan -->
        <div class="space-y-3">
            <h3 class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray pb-1 border-b border-brand-border/60">
                Rincian Barang yang Ditagihkan
            </h3>

            <div class="border border-brand-border rounded-xl overflow-hidden">
                <table class="w-full text-left text-sm text-brand-espresso">
                    <thead class="bg-neutral-50 border-b border-brand-border text-xs uppercase tracking-wider font-semibold text-brand-warm-gray">
                        <tr>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-12 text-center">No</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap">Nama Produk</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-32 text-center">Jumlah</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-40 text-right">Harga Satuan</th>
                            <th scope="col" class="px-4 py-3 whitespace-nowrap w-44 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-border/60">
                        @foreach ($invoice->items as $idx => $item)
                            <tr>
                                <td class="px-4 py-3 text-center text-xs text-brand-warm-gray">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-brand-espresso">{{ $item->product?->name ?? 'Produk' }}</div>
                                    <div class="text-xs text-brand-warm-gray">
                                        Satuan: {{ $item->product?->unit ?? 'kemasan' }}
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
                    <tfoot class="border-t border-brand-border divide-y divide-brand-border/50 text-brand-espresso text-sm">
                        <tr>
                            <td colspan="4" class="px-4 py-2.5 text-right font-semibold text-brand-warm-gray text-xs uppercase tracking-wider">
                                Subtotal Nilai Barang
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold">
                                Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                        @if ($invoice->discount > 0)
                            <tr>
                                <td colspan="4" class="px-4 py-2.5 text-right font-semibold text-red-600 text-xs uppercase tracking-wider">
                                    Potongan / Diskon Toko
                                </td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold text-red-600">
                                    - Rp {{ number_format($invoice->discount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        <tr class="bg-neutral-50 font-bold">
                            <td colspan="4" class="px-4 py-3 text-right text-xs uppercase tracking-wider">
                                Total Tagihan Akhir
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-base">
                                Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-2.5 text-right font-semibold text-emerald-800 text-xs uppercase tracking-wider">
                                Total Sudah Dibayar
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-emerald-800">
                                Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-neutral-50 font-bold">
                            <td colspan="4" class="px-4 py-3 text-right text-xs uppercase tracking-wider">
                                Sisa Piutang yang Harus Dibayar
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-base {{ $invoice->remaining_balance > 0 ? 'text-amber-800' : 'text-stone-400' }}">
                                Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Riwayat Pembayaran (Payment History) -->
        <div class="space-y-3">
            <h3 class="text-xs uppercase font-bold tracking-wider text-brand-warm-gray pb-1 border-b border-brand-border/60">
                Riwayat Pembayaran &amp; Setoran
            </h3>

            @if ($invoice->payments->isEmpty())
                <div class="p-4 bg-neutral-50 rounded-xl border border-brand-border/60 text-xs text-brand-warm-gray text-center">
                    Belum ada riwayat pembayaran tercatat untuk faktur ini.
                </div>
            @else
                <div class="border border-brand-border rounded-xl overflow-hidden">
                    <table class="w-full text-left text-sm text-brand-espresso">
                        <thead class="bg-neutral-50 border-b border-brand-border text-xs uppercase tracking-wider font-semibold text-brand-warm-gray">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 whitespace-nowrap">No. Kwitansi</th>
                                <th scope="col" class="px-4 py-2.5 whitespace-nowrap">Tanggal</th>
                                <th scope="col" class="px-4 py-2.5 whitespace-nowrap">Metode</th>
                                <th scope="col" class="px-4 py-2.5 whitespace-nowrap">No. Referensi</th>
                                <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">Nominal</th>
                                <th scope="col" class="px-4 py-2.5 whitespace-nowrap">Petugas</th>
                                <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right print:hidden">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border/60 text-xs">
                            @foreach ($invoice->payments as $pay)
                                <tr>
                                    <td class="px-4 py-3 font-mono font-bold text-brand-espresso">
                                        {{ $pay->payment_number }}
                                    </td>
                                    <td class="px-4 py-3 text-brand-warm-gray whitespace-nowrap">
                                        {{ $pay->payment_date?->translatedFormat('d M Y') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-brand-espresso whitespace-nowrap">
                                        {{ $pay->method_label }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-brand-warm-gray whitespace-nowrap">
                                        {{ $pay->reference_number ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-emerald-800 whitespace-nowrap">
                                        Rp {{ number_format($pay->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-brand-warm-gray whitespace-nowrap">
                                        {{ $pay->user?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap print:hidden">
                                        @can('faktur-edit')
                                            <button type="button" wire:click="deletePayment({{ $pay->id }})" wire:confirm="Hapus catatan pembayaran ini? Saldo faktur akan dihitung ulang."
                                                class="text-xs text-red-600 hover:text-red-800 hover:underline cursor-pointer">
                                                Hapus
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Kolom Tanda Tangan Penagihan (Print Friendly) -->
        <div class="pt-8 border-t border-brand-border/60 grid grid-cols-2 gap-12 text-center text-xs">
            <div>
                <p class="font-semibold text-brand-espresso">Petugas Penagihan / Halala Food</p>
                <div class="h-20 flex items-end justify-center">
                    <p class="border-b border-brand-espresso/50 w-44 pb-1 font-medium">
                        {{ $invoice->creator?->name ?? '( Bagian Keuangan )' }}
                    </p>
                </div>
            </div>

            <div>
                <p class="font-semibold text-brand-espresso">Penerima / Toko Mitra</p>
                <div class="h-20 flex items-end justify-center">
                    <p class="border-b border-brand-espresso/50 w-44 pb-1 font-medium">
                        {{ $invoice->store?->owner_name ?: '( Cap & Staf Toko )' }}
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Bottom Cancel Option (If no payments made) -->
    @can('faktur-edit')
        @if ($invoice->paid_amount == 0 && $invoice->status !== 'dibatalkan')
            <div class="flex items-center justify-between p-4 bg-neutral-50 rounded-2xl border border-brand-border text-xs print:hidden">
                <span class="text-brand-warm-gray">Faktur salah buat atau dibatalkan oleh pihak toko?</span>
                <button type="button" wire:click="cancelInvoice" wire:confirm="Batalkan faktur tagihan ini?"
                    class="px-3.5 py-1.5 text-xs font-semibold text-amber-800 hover:text-amber-900 hover:bg-amber-100 rounded-lg transition cursor-pointer">
                    Batalkan Faktur Tagihan
                </button>
            </div>
        @endif
    @endcan

</div>
