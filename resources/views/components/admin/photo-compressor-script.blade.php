<script>
    if (!window.compressStorePhoto) {
        window.compressStorePhoto = function (file, onProgress) {
            return new Promise((resolve, reject) => {
                if (!file) {
                    return reject(new Error('Silakan pilih file foto terlebih dahulu.'));
                }

                // 1. Validasi ekstensi & MIME (hanya jpg, jpeg, png, webp)
                const validExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                const fileName = file.name || '';
                const ext = fileName.includes('.') ? fileName.split('.').pop().toLowerCase() : '';
                const validMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                const fileMime = (file.type || '').toLowerCase();

                const isMimeValid = validMimes.includes(fileMime);
                const isExtValid = validExtensions.includes(ext);

                if (!isMimeValid && !isExtValid) {
                    return reject(new Error('Format file tidak didukung. Hanya file JPG, JPEG, PNG, atau WEBP yang diperbolehkan.'));
                }

                // 2. Validasi batas ukuran file: Maksimal 10MB
                const maxUploadBytes = 10 * 1024 * 1024; // 10MB
                if (file.size > maxUploadBytes) {
                    const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
                    return reject(new Error(`Ukuran file terlalu besar (${sizeInMb} MB). Maksimal ukuran file yang diizinkan adalah 10MB.`));
                }

                // 3. Deteksi Safari & Perangkat iPhone/iPad/iOS
                const ua = navigator.userAgent;
                const isIos = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                const isSafari = /^((?!chrome|android).)*safari/i.test(ua);
                const targetMime = (isIos || isSafari) ? 'image/jpeg' : 'image/webp';
                const targetFormatName = (isIos || isSafari) ? 'JPEG' : 'WebP';

                const updateProgress = (pct, text) => {
                    if (typeof onProgress === 'function') {
                        onProgress(pct, text);
                    }
                };

                updateProgress(15, 'Membaca data file foto...');

                const reader = new FileReader();
                reader.onerror = () => reject(new Error('Gagal membaca file foto dari perangkat.'));
                reader.onload = (e) => {
                    updateProgress(35, 'Memuat gambar ke engine pengolah...');

                    const img = new Image();
                    img.onerror = () => reject(new Error('Format file gambar rusak atau tidak dapat diproses.'));
                    img.onload = async () => {
                        try {
                            updateProgress(50, `Mengoptimasi resolusi & format (${targetFormatName})...`);

                            const MAX_TARGET_BYTES = 50 * 1024; // 50KB (51,200 bytes)
                            let width = img.width;
                            let height = img.height;

                            const maxInitialDim = 1200;
                            if (width > maxInitialDim || height > maxInitialDim) {
                                if (width > height) {
                                    height = Math.round((height * maxInitialDim) / width);
                                    width = maxInitialDim;
                                } else {
                                    width = Math.round((width * maxInitialDim) / height);
                                    height = maxInitialDim;
                                }
                            }

                            const canvas = document.createElement('canvas');
                            canvas.width = width;
                            canvas.height = height;
                            const ctx = canvas.getContext('2d');

                            const drawToCanvas = () => {
                                ctx.clearRect(0, 0, width, height);
                                if (targetMime === 'image/jpeg') {
                                    ctx.fillStyle = '#FFFFFF';
                                    ctx.fillRect(0, 0, width, height);
                                }
                                ctx.drawImage(img, 0, 0, width, height);
                            };

                            drawToCanvas();

                            let quality = 0.85;
                            let iteration = 0;
                            const maxIterations = 12;
                            let currentBlob = null;

                            while (iteration < maxIterations) {
                                iteration++;
                                const currentPct = Math.min(92, 50 + Math.round((iteration / maxIterations) * 42));
                                updateProgress(currentPct, `Mengompresi (${targetFormatName} &le; 50KB)...`);

                                await new Promise((r) => setTimeout(r, 50));

                                currentBlob = await new Promise((res) => canvas.toBlob(res, targetMime, quality));

                                if (!currentBlob) {
                                    throw new Error('Gagal melakukan kompresi canvas pada browser.');
                                }

                                if (currentBlob.size <= MAX_TARGET_BYTES) {
                                    break;
                                }

                                if (quality > 0.35) {
                                    quality -= 0.15;
                                } else {
                                    width = Math.max(120, Math.round(width * 0.8));
                                    height = Math.max(120, Math.round(height * 0.8));
                                    canvas.width = width;
                                    canvas.height = height;
                                    drawToCanvas();
                                    quality = 0.65;
                                }
                            }

                            updateProgress(96, 'Menyusun pratinjau hasil konversi...');

                            const outReader = new FileReader();
                            outReader.onerror = () => reject(new Error('Gagal menghasilkan data gambar hasil konversi.'));
                            outReader.onloadend = () => {
                                updateProgress(100, `Selesai! Berhasil dikonversi ke ${targetFormatName} (${(currentBlob.size / 1024).toFixed(1)} KB)`);

                                const formatBytes = (bytes) => {
                                    if (bytes >= 1024 * 1024) {
                                        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
                                    }
                                    return (bytes / 1024).toFixed(1) + ' KB';
                                };

                                resolve({
                                    dataUrl: outReader.result,
                                    size: currentBlob.size,
                                    sizeFormatted: formatBytes(currentBlob.size),
                                    originalSize: file.size,
                                    originalSizeFormatted: formatBytes(file.size),
                                    format: targetFormatName,
                                    mime: targetMime,
                                    width: width,
                                    height: height,
                                });
                            };
                            outReader.readAsDataURL(currentBlob);
                        } catch (err) {
                            reject(err);
                        }
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        };
    }

    if (!window.openDeviceCamera) {
        window.openDeviceCamera = function (opts = {}) {
            const onProgress = opts.onProgress || function() {};
            const onCapture = opts.onCapture || function() {};
            const onError = opts.onError || function(e) { console.error(e); };
            const fallbackInput = opts.fallbackInput;

            const modal = document.getElementById('halala-camera-modal');
            const video = document.getElementById('halala-camera-video');
            const loading = document.getElementById('halala-camera-loading');
            const switchBtn = document.getElementById('halala-camera-switch-btn');
            const snapBtn = document.getElementById('halala-camera-snap-btn');
            const closeBtn = document.getElementById('halala-camera-close-btn');

            if (!modal || !video) {
                if (fallbackInput) fallbackInput.click();
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                if (fallbackInput) {
                    fallbackInput.click();
                    return;
                }
                return onError(new Error('Browser ini tidak mendukung akses kamera langsung (WebRTC).'));
            }

            let currentStream = null;
            let currentFacingMode = 'environment';
            let isMirrored = false;

            const stopStream = () => {
                if (currentStream) {
                    currentStream.getTracks().forEach(t => t.stop());
                    currentStream = null;
                }
            };

            const closeModal = () => {
                stopStream();
                video.style.transform = 'none';
                modal.style.display = 'none';
                document.body.classList.remove('overflow-hidden');
            };

            closeBtn.onclick = closeModal;

            const startCamera = async (facingMode) => {
                stopStream();
                loading.style.display = 'flex';
                try {
                    const constraints = {
                        video: {
                            facingMode: facingMode,
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    };
                    const stream = await navigator.mediaDevices.getUserMedia(constraints);
                    currentStream = stream;
                    video.srcObject = stream;
                    await video.play();

                    const track = stream.getVideoTracks()[0];
                    const settings = (track && track.getSettings) ? track.getSettings() : {};
                    isMirrored = (settings.facingMode === 'user') || (facingMode === 'user');
                    video.style.transform = isMirrored ? 'scaleX(-1)' : 'none';

                    loading.style.display = 'none';
                } catch (err) {
                    // Fallback to any available video device (e.g. desktop webcam)
                    try {
                        const fallbackStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                        currentStream = fallbackStream;
                        video.srcObject = fallbackStream;
                        await video.play();

                        const track = fallbackStream.getVideoTracks()[0];
                        const settings = (track && track.getSettings) ? track.getSettings() : {};
                        isMirrored = (settings.facingMode === 'user') || (!settings.facingMode);
                        video.style.transform = isMirrored ? 'scaleX(-1)' : 'none';

                        loading.style.display = 'none';
                    } catch (e2) {
                        closeModal();
                        if (fallbackInput) {
                            fallbackInput.click();
                            return;
                        }
                        onError(new Error('Tidak dapat mengakses kamera: ' + (err.message || 'Izin kamera ditolak.')));
                    }
                }
            };

            // Show modal and start stream
            document.body.classList.add('overflow-hidden');
            modal.style.display = 'flex';
            startCamera(currentFacingMode);

            // Switch camera button
            switchBtn.onclick = () => {
                currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
                startCamera(currentFacingMode);
            };

            // Check if multiple cameras exist to show switch button
            if (navigator.mediaDevices.enumerateDevices) {
                navigator.mediaDevices.enumerateDevices().then(devices => {
                    const videoDevices = devices.filter(d => d.kind === 'videoinput');
                    if (videoDevices.length > 1) {
                        switchBtn.style.display = 'inline-flex';
                    } else {
                        switchBtn.style.display = 'none';
                    }
                }).catch(() => {});
            }

            // Snap Button
            snapBtn.onclick = async () => {
                if (!video.videoWidth || !video.videoHeight) return;

                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');

                // Mirror photo if front-facing camera
                if (isMirrored) {
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                }

                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                closeModal();

                canvas.toBlob(async (blob) => {
                    if (!blob) {
                        return onError(new Error('Gagal mengambil tangkapan frame dari kamera.'));
                    }
                    const file = new File([blob], 'camera_capture.jpg', { type: 'image/jpeg' });
                    try {
                        const res = await window.compressStorePhoto(file, onProgress);
                        onCapture(res);
                    } catch (err) {
                        onError(err);
                    }
                }, 'image/jpeg', 0.92);
            };
        };
    }
</script>

<!-- Global Halala WebRTC Camera Modal -->
<div id="halala-camera-modal" class="fixed inset-0 z-100 bg-brand-espresso/80 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl flex flex-col border border-brand-border">
        <!-- Header -->
        <div class="p-4 border-b border-brand-border flex items-center justify-between bg-white">
            <h3 class="text-sm font-bold text-brand-espresso flex items-center gap-2">
                <i class="ti ti-camera text-brand-primary text-lg"></i>
                <span>Kamera Perangkat Langsung</span>
            </h3>
            <button type="button" id="halala-camera-close-btn"
                class="size-8 rounded-lg flex items-center justify-center text-brand-warm-gray hover:text-brand-espresso hover:bg-neutral-100 transition cursor-pointer"
                title="Tutup Kamera">
                <i class="ti ti-x text-lg"></i>
            </button>
        </div>

        <!-- Video Viewport -->
        <div class="relative bg-neutral-900 flex items-center justify-center aspect-4/3 overflow-hidden">
            <video id="halala-camera-video" playsinline autoplay muted class="w-full h-full object-cover"></video>
            <div id="halala-camera-loading" class="absolute inset-0 flex flex-col items-center justify-center text-white bg-black/60 gap-2" style="display: none;">
                <i class="ti ti-loader-2 animate-spin text-3xl text-brand-primary"></i>
                <span class="text-xs font-medium">Menghubungkan ke kamera perangkat...</span>
            </div>
        </div>

        <!-- Controls Footer -->
        <div class="p-4 bg-neutral-50 flex items-center justify-between gap-3 border-t border-brand-border">
            <button type="button" id="halala-camera-switch-btn"
                class="px-3.5 py-2 bg-white border border-brand-border rounded-xl text-xs font-semibold text-brand-espresso hover:bg-neutral-100 transition cursor-pointer"
                style="display: none;">
                <i class="ti ti-switch-horizontal text-sm mr-1"></i>
                <span>Ganti Kamera</span>
            </button>
            <div class="flex-1"></div>
            <button type="button" id="halala-camera-snap-btn"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary hover:bg-brand-primary-hover text-white text-sm font-bold rounded-xl shadow-xs transition cursor-pointer">
                <i class="ti ti-aperture text-base"></i>
                <span>Jepret Foto</span>
            </button>
        </div>
    </div>
</div>
