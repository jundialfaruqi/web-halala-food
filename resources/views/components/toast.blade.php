<!-- Global Toast Notification Component -->
<div x-data class="fixed top-6 right-6 z-99999 flex flex-col gap-3 max-w-sm sm:max-w-md w-full pointer-events-none"
    style="z-index: 99999;">

    <template x-for="toast in ($store.toasts ? $store.toasts.items : [])" :key="toast.id">
        <div x-data="{ show: false }" x-init="$nextTick(() => { show = true })" x-show="show"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
            class="pointer-events-auto w-full shadow-2xl rounded-2xl p-4 flex items-start gap-3 bg-white border border-brand-border select-none ring-1 ring-black/5">

            <!-- Naked Tabler Icon based on type -->
            <i class="text-2xl shrink-0 mt-0.5"
                :class="{
                    'ti ti-circle-check text-green-600': toast.type === 'success',
                    'ti ti-alert-triangle text-red-600': toast.type === 'error',
                    'ti ti-info-circle text-blue-600': toast.type === 'info',
                    'ti ti-alert-circle text-amber-600': toast.type === 'warning'
                }"></i>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-brand-espresso truncate" x-text="toast.title"></p>
                <p class="text-xs sm:text-sm text-brand-warm-gray mt-0.5 leading-relaxed font-medium"
                    x-text="toast.message"></p>
            </div>

            <button type="button" @click="show = false; setTimeout(() => $store.toasts.remove(toast.id), 200)"
                class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer"
                aria-label="Tutup notifikasi">
                <i class="ti ti-x text-base"></i>
            </button>
        </div>
    </template>
</div>

<script>
    (function() {
        // Global Queue for early toasts
        window.__toastQueue = window.__toastQueue || [];
        window.__recentToasts = window.__recentToasts || new Map();

        // Store initializer function
        function initToastStore() {
            if (window.Alpine && !window.Alpine.store('toasts')) {
                window.Alpine.store('toasts', {
                    items: [],
                    add(item) {
                        if (!item || !item.message) return;
                        this.items.push(item);
                        setTimeout(() => {
                            this.remove(item.id);
                        }, item.duration || 4500);
                    },
                    remove(id) {
                        this.items = this.items.filter(t => t.id !== id);
                    }
                });

                // Flush queue if any
                if (window.__toastQueue && window.__toastQueue.length > 0) {
                    window.__toastQueue.forEach(item => {
                        window.Alpine.store('toasts').add(item);
                    });
                    window.__toastQueue = [];
                }
            }
        }

        // Initialize store when Alpine is ready
        if (window.Alpine) {
            initToastStore();
        } else {
            document.addEventListener('alpine:init', initToastStore);
        }

        // Global toast trigger helper with debounce/deduplication
        window.toast = function(message, type = 'success', title = null, duration = 4500) {
            let payload = message;

            // Unpack if array
            if (Array.isArray(payload)) {
                payload = payload[0] || {};
            }

            // Unpack if object with 0 index (Livewire event payload format)
            if (payload && typeof payload === 'object' && payload[0] && typeof payload[0] === 'object') {
                payload = payload[0];
            }

            // Unpack if CustomEvent detail wrapper
            if (payload && typeof payload === 'object' && payload.detail !== undefined) {
                payload = payload.detail;
                if (Array.isArray(payload)) payload = payload[0] || {};
                if (payload && payload[0] && typeof payload[0] === 'object') payload = payload[0];
            }

            // Handle string message
            if (typeof payload === 'string') {
                payload = {
                    message: payload,
                    type: type,
                    title: title,
                    duration: duration
                };
            }

            if (!payload || typeof payload !== 'object') return;

            // Extract message string
            let msg = payload.message || payload.msg || payload.text || '';
            if (Array.isArray(msg)) msg = msg[0] || '';
            if (!msg || typeof msg !== 'string') return;

            const toastType = payload.type || payload.status || type || 'success';

            // Deduplication: prevent identical toast within 1000ms
            const cacheKey = `${toastType}:${msg.trim()}`;
            const now = Date.now();
            if (window.__recentToasts.has(cacheKey) && (now - window.__recentToasts.get(cacheKey) < 1000)) {
                return; // Duplicate toast suppressed
            }
            window.__recentToasts.set(cacheKey, now);

            // Cleanup old keys
            if (window.__recentToasts.size > 20) {
                for (const [k, time] of window.__recentToasts.entries()) {
                    if (now - time > 5000) window.__recentToasts.delete(k);
                }
            }

            const defaultTitle = toastType === 'error' ? 'Peringatan' : (toastType === 'info' ? 'Informasi' : (
                toastType === 'warning' ? 'Perhatian' : 'Berhasil'));
            const toastTitle = payload.title || title || defaultTitle;
            const toastDuration = Number(payload.duration) || duration || 4500;

            const item = {
                id: Date.now() + Math.random(),
                message: msg,
                type: toastType,
                title: toastTitle,
                duration: toastDuration
            };

            if (window.Alpine && window.Alpine.store && window.Alpine.store('toasts')) {
                window.Alpine.store('toasts').add(item);
            } else {
                window.__toastQueue.push(item);
            }
        };

        // Attach browser window event listeners once
        if (!window.__toastListenersAttached) {
            window.__toastListenersAttached = true;

            const handleBrowserEvent = (e) => {
                window.toast(e.detail !== undefined ? e.detail : e);
            };

            window.addEventListener('show-toast', handleBrowserEvent);
            window.addEventListener('notify', handleBrowserEvent);
            window.addEventListener('toast', handleBrowserEvent);
            window.addEventListener('toast-message', handleBrowserEvent);
        }
    })();
</script>

<!-- Flash message from Laravel Session on page load & Livewire SPA navigations -->
@php
    $flashToast = session('toast') ?? 
        (session('success') ? ['message' => session('success'), 'type' => 'success'] : 
        (session('error') ? ['message' => session('error'), 'type' => 'error'] : 
        (session('warning') ? ['message' => session('warning'), 'type' => 'warning'] : 
        (session('message') ? ['message' => session('message'), 'type' => 'info'] : null))));
@endphp

@if ($flashToast)
    <script>
        (function() {
            const toastPayload = {!! json_encode($flashToast) !!};
            function showToast() {
                if (typeof window.toast === 'function') {
                    window.toast(toastPayload);
                } else if (window.Alpine && window.Alpine.store && window.Alpine.store('toasts')) {
                    window.Alpine.store('toasts').add({
                        id: Date.now() + Math.random(),
                        message: toastPayload.message || toastPayload,
                        type: toastPayload.type || 'success',
                        title: toastPayload.title || (toastPayload.type === 'error' ? 'Peringatan' : 'Berhasil'),
                        duration: 4500
                    });
                } else {
                    window.__toastQueue = window.__toastQueue || [];
                    window.__toastQueue.push(toastPayload);
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showToast, { once: true });
            } else {
                showToast();
            }

            document.addEventListener('livewire:navigated', showToast, { once: true });
        })();
    </script>
@endif
