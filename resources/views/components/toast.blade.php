<!-- Global Toast Notification Component -->
<div x-data="{
    toasts: [],
    add(toast) {
        const id = Date.now() + Math.random();
        const newToast = {
            id: id,
            message: typeof toast === 'string' ? toast : (toast.message || ''),
            title: toast.title || (toast.type === 'error' ? 'Peringatan' : (toast.type === 'info' ? 'Informasi' : 'Berhasil')),
            type: toast.type || 'success',
            duration: toast.duration || 4000,
        };
        this.toasts.push(newToast);
        setTimeout(() => {
            this.remove(id);
        }, newToast.duration);
    },
    remove(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    }
}"
@notify.window="add($event.detail)"
@show-toast.window="add($event.detail)"
@toast.window="add($event.detail)"
class="fixed top-6 right-6 z-50 flex flex-col gap-3 max-w-sm sm:max-w-md w-full pointer-events-none">

    <!-- Flash message from Laravel Session on page load -->
    @if (session()->has('success'))
        <div x-init="$nextTick(() => add({ message: @json(session('success')), type: 'success' }))"></div>
    @endif
    @if (session()->has('error'))
        <div x-init="$nextTick(() => add({ message: @json(session('error')), type: 'error' }))"></div>
    @endif
    @if (session()->has('message'))
        <div x-init="$nextTick(() => add({ message: @json(session('message')), type: 'info' }))"></div>
    @endif

    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="pointer-events-auto w-full shadow-lg rounded-xl p-4 flex items-start gap-3 bg-white border border-brand-border select-none">
            
            <!-- Naked Icon based on type -->
            <i class="text-2xl shrink-0 mt-0.5"
                :class="{
                    'ti ti-circle-check text-green-600': toast.type === 'success',
                    'ti ti-alert-triangle text-red-600': toast.type === 'error',
                    'ti ti-info-circle text-blue-600': toast.type === 'info',
                    'ti ti-alert-circle text-amber-600': toast.type === 'warning'
                }"></i>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-brand-espresso truncate" x-text="toast.title"></p>
                <p class="text-sm text-brand-warm-gray mt-0.5 leading-snug" x-text="toast.message"></p>
            </div>

            <button type="button" @click="remove(toast.id)"
                class="text-brand-warm-gray hover:text-brand-espresso p-1 rounded-lg transition cursor-pointer"
                aria-label="Tutup notifikasi">
                <i class="ti ti-x text-base"></i>
            </button>
        </div>
    </template>
</div>
