<div class="space-y-6 max-w-5xl print:max-w-none print:w-full print:space-y-4">

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
        <div class="bg-white border border-brand-border rounded-2xl p-6 shadow-xs space-y-4 print:hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-brand-espresso">Konfirmasi Serah Terima di Toko</h2>
                    <p class="text-xs text-brand-warm-gray mt-0.5">
                        Barang sedang dalam perjalanan. Saat kurir tiba di lokasi dan barang diterima staf toko, lengkapi bukti serah terima dan tanda tangan di bawah ini.
                    </p>
                </div>
                <div class="text-xs font-mono text-brand-warm-gray">
                    Berangkat: {{ $delivery->dispatched_at?->translatedFormat('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <form wire:submit="completeDelivery" class="space-y-4 pt-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="recipient_name" class="block text-xs font-semibold text-brand-espresso mb-1">
                            Nama Penerima di Toko <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="recipient_name" wire:model="recipient_name"
                            placeholder="Contoh: Ibu Hj. Aminah"
                            class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary">
                        @error('recipient_name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="recipient_role" class="block text-xs font-semibold text-brand-espresso mb-1">
                            Jabatan / Peran di Toko
                        </label>
                        <input type="text" id="recipient_role" wire:model="recipient_role"
                            placeholder="Contoh: Pemilik Toko / Kasir / Karyawan"
                            class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary">
                        @error('recipient_role')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="recipient_phone" class="block text-xs font-semibold text-brand-espresso mb-1">
                            No. HP Penerima (Opsional)
                        </label>
                        <input type="text" id="recipient_phone" wire:model="recipient_phone"
                            placeholder="Contoh: 081234567890"
                            class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary font-mono">
                        @error('recipient_phone')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Foto Bukti Serah Terima (Sistem Kompresi Seperti Foto Toko) -->
                    <div x-data="{
                        photoPreview: @js($delivery->proof_image ? asset('storage/' . $delivery->proof_image) : null),
                        originalSize: '',
                        compressedSize: '',
                        photoFormat: '',
                        progress: 0,
                        statusText: '',
                        isConverting: false,
                        errorMessage: '',

                        async handleFile(e) {
                            const file = e.target.files ? e.target.files[0] : null;
                            if (!file) return;

                            this.errorMessage = '';
                            this.photoPreview = null;
                            this.isConverting = true;
                            this.progress = 0;
                            this.statusText = 'Mempersiapkan gambar...';

                            try {
                                const res = await window.compressStorePhoto(file, (pct, status) => {
                                    this.progress = pct;
                                    this.statusText = status;
                                });

                                this.photoPreview = res.dataUrl;
                                this.originalSize = res.originalSizeFormatted;
                                this.compressedSize = res.sizeFormatted;
                                this.photoFormat = res.format;
                                $wire.set('photo_data', res.dataUrl);
                            } catch (err) {
                                this.errorMessage = err.message || 'Gagal memproses file foto.';
                                $wire.set('photo_data', null);
                            } finally {
                                this.isConverting = false;
                                e.target.value = '';
                            }
                        },

                        removePhoto() {
                            this.photoPreview = null;
                            this.originalSize = '';
                            this.compressedSize = '';
                            this.photoFormat = '';
                            this.errorMessage = '';
                            $wire.set('photo_data', null);
                        },

                        openCamera() {
                            const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                            if (isMobile && this.$refs.proofCameraInput) {
                                this.$refs.proofCameraInput.click();
                            } else if (window.openDeviceCamera) {
                                window.openDeviceCamera({
                                    onProgress: (pct, status) => {
                                        this.isConverting = true;
                                        this.progress = pct;
                                        this.statusText = status;
                                    },
                                    onCapture: (res) => {
                                        this.photoPreview = res.dataUrl;
                                        this.originalSize = res.originalSizeFormatted;
                                        this.compressedSize = res.sizeFormatted;
                                        this.photoFormat = res.format;
                                        this.isConverting = false;
                                        $wire.set('photo_data', res.dataUrl);
                                    },
                                    onError: (err) => {
                                        this.isConverting = false;
                                        if (this.$refs.proofCameraInput) {
                                            this.$refs.proofCameraInput.click();
                                        } else {
                                            this.errorMessage = err.message || 'Kamera tidak dapat diakses.';
                                        }
                                    },
                                    fallbackInput: this.$refs.proofCameraInput
                                });
                            } else if (this.$refs.proofCameraInput) {
                                this.$refs.proofCameraInput.click();
                            }
                        }
                    }">
                        <label class="block text-xs font-semibold text-brand-espresso mb-1">
                            Foto Bukti Serah Terima <span class="text-brand-warm-gray font-normal">(Kamera / File)</span>
                        </label>
                        <p class="text-[11px] text-brand-warm-gray mb-2">Foto kemasan di etalase/rak toko mitra atau bersama staf penerima toko.</p>

                        <!-- Error Alert -->
                        <template x-if="errorMessage">
                            <div class="mb-2 p-2.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2">
                                <i class="ti ti-alert-triangle text-base shrink-0 mt-0.5"></i>
                                <span x-text="errorMessage"></span>
                            </div>
                        </template>

                        @error('photo_data')
                            <div class="mb-2 p-2 rounded-lg bg-red-50 text-red-600 text-xs font-medium">{{ $message }}</div>
                        @enderror

                        <!-- Photo Container -->
                        <div class="flex flex-col sm:flex-row items-center gap-4 p-3 rounded-xl border border-brand-border bg-neutral-50/60">
                            <!-- Preview Box -->
                            <div class="relative w-24 h-24 rounded-xl border border-brand-border bg-white flex items-center justify-center shrink-0 overflow-hidden shadow-xs">
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" alt="Pratinjau Bukti Serah Terima" class="w-full h-full object-contain p-1">
                                </template>
                                <template x-if="!photoPreview">
                                    <div class="text-center p-2 text-brand-warm-gray">
                                        <i class="ti ti-camera text-2xl block mb-0.5"></i>
                                        <span class="text-[10px] block">Belum ada foto</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Actions & Controls -->
                            <div class="flex-1 w-full space-y-2">
                                <!-- Progress Bar -->
                                <div x-show="isConverting" class="space-y-1">
                                    <div class="flex items-center justify-between text-xs font-semibold text-brand-espresso">
                                        <span x-text="statusText"></span>
                                        <span x-text="progress + '%'"></span>
                                    </div>
                                    <div class="w-full bg-neutral-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-brand-primary h-1.5 rounded-full transition-all duration-150" :style="'width: ' + progress + '%'"></div>
                                    </div>
                                </div>

                                <!-- Success Conversion Details -->
                                <template x-if="photoPreview && compressedSize">
                                    <div class="space-y-1.5">
                                        <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-espresso bg-white px-2.5 py-1 rounded-lg border border-brand-border">
                                            <i class="ti ti-check text-emerald-600"></i>
                                            <span>Format: <strong x-text="photoFormat"></strong></span>
                                            <span>&bull;</span>
                                            <span x-text="compressedSize"></span>
                                            <span class="text-brand-warm-gray font-normal" x-text="'(dari ' + originalSize + ')'"></span>
                                        </div>
                                        <div>
                                            <button type="button" @click="removePhoto()"
                                                class="text-xs text-red-600 hover:text-red-700 font-semibold inline-flex items-center gap-1 cursor-pointer">
                                                <i class="ti ti-trash"></i>
                                                <span>Hapus Foto</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <!-- Hidden Inputs -->
                                <input type="file" x-ref="proofCameraInput" @change="handleFile($event)"
                                    accept="image/*" capture="environment" class="hidden">
                                <input type="file" x-ref="proofFileInput" @change="handleFile($event)"
                                    accept="image/jpeg,image/png,image/webp,image/jpg" class="hidden">

                                <!-- Upload Buttons -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" @click="openCamera()" :disabled="isConverting"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-brand-border hover:bg-neutral-50 rounded-xl text-xs font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                                        <i class="ti ti-camera text-sm text-brand-primary"></i>
                                        <span>Ambil dari Kamera</span>
                                    </button>
                                    <button type="button" @click="$refs.proofFileInput.click()" :disabled="isConverting"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-brand-border hover:bg-neutral-50 rounded-xl text-xs font-semibold text-brand-espresso transition cursor-pointer disabled:opacity-50">
                                        <i class="ti ti-photo text-sm text-brand-espresso"></i>
                                        <span>Pilih dari File</span>
                                    </button>
                                </div>

                                <p class="text-[10px] text-brand-warm-gray">
                                    Format: <strong>JPG, PNG, WEBP</strong> (Maks 10MB). Otomatis dikompresi di HP &le; 50KB.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Serah Terima -->
                    <div class="flex flex-col justify-between">
                        <div>
                            <label for="handover_notes" class="block text-xs font-semibold text-brand-espresso mb-1">
                                Catatan Serah Terima (Opsional)
                            </label>
                            <textarea id="handover_notes" wire:model="handover_notes" rows="4"
                                placeholder="Contoh: Diterima lengkap 25 pouch di etalase depan, barang dalam kondisi baik..."
                                class="w-full px-3.5 py-2 bg-white border border-brand-border rounded-xl text-sm text-brand-espresso focus:outline-hidden focus:border-brand-primary resize-none"></textarea>
                            @error('handover_notes')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Digital Signature Pad (Alpine.js HTML5 Canvas) -->
                <div class="pt-2" x-data="{
                    isDrawing: false,
                    hasSignature: false,
                    ctx: null,
                    canvas: null,

                    init() {
                        this.canvas = this.$refs.sigCanvas;
                        if (!this.canvas) return;
                        this.ctx = this.canvas.getContext('2d');
                        this.ctx.lineWidth = 2.5;
                        this.ctx.lineCap = 'round';
                        this.ctx.lineJoin = 'round';
                        this.ctx.strokeStyle = '#2B1E16';
                    },

                    getPos(e) {
                        const rect = this.canvas.getBoundingClientRect();
                        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                        return {
                            x: (clientX - rect.left) * (this.canvas.width / rect.width),
                            y: (clientY - rect.top) * (this.canvas.height / rect.height)
                        };
                    },

                    startDrawing(e) {
                        this.isDrawing = true;
                        const pos = this.getPos(e);
                        this.ctx.beginPath();
                        this.ctx.moveTo(pos.x, pos.y);
                    },

                    draw(e) {
                        if (!this.isDrawing) return;
                        e.preventDefault();
                        const pos = this.getPos(e);
                        this.ctx.lineTo(pos.x, pos.y);
                        this.ctx.stroke();
                        this.hasSignature = true;
                    },

                    stopDrawing() {
                        if (!this.isDrawing) return;
                        this.isDrawing = false;
                        if (this.hasSignature) {
                            $wire.set('signature_data', this.canvas.toDataURL('image/png'));
                        }
                    },

                    clearCanvas() {
                        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                        this.hasSignature = false;
                        $wire.set('signature_data', '');
                    }
                }">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-brand-espresso">
                            Tanda Tangan Digital Penerima Toko (Coret di Layar HP / Mouse)
                        </label>
                        <button type="button" @click="clearCanvas()"
                            class="text-xs font-semibold text-brand-warm-gray hover:text-brand-espresso cursor-pointer">
                            Hapus Tanda Tangan
                        </button>
                    </div>
                    <div class="border border-brand-border rounded-xl bg-neutral-50/50 p-2 overflow-hidden flex justify-center">
                        <canvas x-ref="sigCanvas" width="400" height="150"
                            class="touch-none bg-white rounded-lg border border-brand-border/60 cursor-crosshair w-full max-w-md h-36"
                            @mousedown="startDrawing($event)"
                            @mousemove="draw($event)"
                            @mouseup="stopDrawing()"
                            @mouseleave="stopDrawing()"
                            @touchstart="startDrawing($event)"
                            @touchmove="draw($event)"
                            @touchend="stopDrawing()"></canvas>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-2">
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary/90 text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50">
                        <i class="ti ti-check text-base"></i>
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
                        <div>Penerima di Toko: <strong class="text-brand-espresso">{{ $delivery->recipient_name }}</strong> {{ $delivery->recipient_role ? "({$delivery->recipient_role})" : '' }} {{ $delivery->recipient_phone ? " - {$delivery->recipient_phone}" : '' }}</div>
                    @endif
                </div>

                @if ($delivery->proof_image || $delivery->signature_data)
                    <div class="pt-3 border-t border-brand-border/60 grid grid-cols-1 sm:grid-cols-2 gap-4 print:hidden">
                        @if ($delivery->proof_image)
                            <div>
                                <span class="text-xs font-semibold text-brand-espresso block mb-1">Foto Bukti Serah Terima:</span>
                                <a href="{{ asset('storage/' . $delivery->proof_image) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $delivery->proof_image) }}" alt="Bukti Serah Terima"
                                        class="h-28 w-auto object-cover rounded-xl border border-brand-border hover:opacity-90 transition">
                                </a>
                            </div>
                        @endif

                        @if ($delivery->signature_data)
                            <div>
                                <span class="text-xs font-semibold text-brand-espresso block mb-1">Tanda Tangan Digital Penerima:</span>
                                <div class="p-2 bg-white rounded-xl border border-brand-border inline-block">
                                    <img src="{{ $delivery->signature_data }}" alt="Tanda Tangan Penerima" class="h-20 w-auto object-contain">
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

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
                <div class="h-20 flex flex-col items-center justify-end">
                    @if ($delivery->signature_data)
                        <img src="{{ $delivery->signature_data }}" alt="Tanda Tangan" class="max-h-12 w-auto object-contain mb-1">
                    @endif
                    <p class="border-b border-brand-espresso/50 w-36 pb-1 font-medium text-center">
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

    <!-- Client-side Photo Compression Script -->
    <x-admin.photo-compressor-script />

</div>
