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
</script>
