<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name', 'Halala Food') }}</title>

        <link rel="icon" href="{{ asset('assets/logo/logo.webp') }}" type="image/webp">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-white text-brand-text-primary antialiased selection:bg-brand-soft-cream selection:text-brand-primary min-h-screen flex flex-col justify-between">
        <x-header />

        <div class="flex-1">
            {{ $slot }}
        </div>

        <x-footer />

        @livewireScripts
    </body>
</html>
