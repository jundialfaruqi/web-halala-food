<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Admin Dashboard - Halala Food' }}</title>

        <link rel="icon" href="{{ asset('assets/logo/logo.webp') }}" type="image/webp">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="h-full bg-white text-brand-text-primary antialiased selection:bg-brand-soft-cream selection:text-brand-primary font-sans"
        x-data="{ sidebarOpen: false }">

        <div class="min-h-screen flex flex-row bg-white">

            <!-- 1. Sidebar -->
            <x-admin.sidebar />

            <!-- Main Panel Container -->
            <div class="flex-1 flex flex-col min-w-0 min-h-screen bg-white">

                <!-- 2. Header -->
                <x-admin.header />

                <!-- 3. Main Content -->
                <main class="flex-1 bg-white p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>

                <!-- 4. Footer -->
                <x-admin.footer />

            </div>

        </div>

        <!-- Global Logout Modal -->
        <x-modal.logout />

        <!-- Global Toast Notifications -->
        <x-toast />

        @livewireScripts
    </body>
</html>
