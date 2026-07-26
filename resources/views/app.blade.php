<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $appName = config('app.name', 'HRM');
            $company = config('brand.company', 'PLT Solutions');
            $description = config('brand.description');
            $appUrl = rtrim((string) config('app.url'), '/');
            $ogImage = $appUrl.config('brand.og_image', '/images/welcome-hero.jpg');
            $siteLabel = $appName.' — '.$company;
        @endphp

        <meta name="description" content="{{ $description }}">
        <meta name="author" content="{{ $company }}">
        <meta name="application-name" content="{{ $appName }}">

        {{-- Open Graph (Facebook, Zalo, LinkedIn, etc.) --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $siteLabel }}">
        <meta property="og:title" content="{{ $siteLabel }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $appUrl }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

        {{-- Twitter / X card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $siteLabel }}">
        <meta name="twitter:description" content="{{ $description }}">
        <meta name="twitter:image" content="{{ $ogImage }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $appName }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
