<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Admin Dashboard - Halala Food' }}</title>

        <link rel="icon" href="{{ asset('assets/logo/logo.webp') }}" type="image/webp">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        @livewireStyles
    </head>
    <body class="h-full bg-white text-brand-text-primary antialiased selection:bg-brand-soft-cream selection:text-brand-primary font-sans"
        x-data="{ sidebarOpen: false }">

        <div class="min-h-screen flex flex-row bg-white print:block print:min-h-0">

            <!-- 1. Sidebar -->
            <div class="print:hidden">
                <x-admin.sidebar />
            </div>

            <!-- Main Panel Container -->
            <div class="flex-1 flex flex-col min-w-0 min-h-screen bg-white print:block print:min-h-0">

                <!-- 2. Header -->
                <div class="print:hidden">
                    <x-admin.header />
                </div>

                <!-- 3. Main Content -->
                <main class="flex-1 bg-white p-4 sm:p-6 lg:p-8 print:p-0 print:m-0">
                    {{ $slot }}
                </main>

                <!-- 4. Footer -->
                <div class="print:hidden">
                    <x-admin.footer />
                </div>

            </div>

        </div>

        <!-- Global Logout Modal -->
        <x-modal.logout />

        <!-- Global Toast Notifications -->
        <x-toast />

        @livewireScripts
    </body>
</html>
