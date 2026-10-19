<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ url('/site.webmanifest') }}">
        <link rel="alternate" type="application/rss+xml" title="{{ __('brand.feed_title') }}" href="{{ \App\Support\Seo::route('feed') }}">

        @php
            $pageTitle = trim($__env->yieldContent('title', \App\Support\Seo::siteName()));
            $seoData = is_array($seo ?? null) ? $seo : [];
            // Titles that do not already carry the brand get it appended while they still fit a results page (~65 chars).
            $documentTitle = $seoData['title'] ?? $pageTitle;
            $brand = \App\Support\Seo::siteName();
            if (! str_contains($documentTitle, $brand) && mb_strlen($documentTitle.' — '.$brand) <= 65) {
                $documentTitle .= ' — '.$brand;
            }
        @endphp
        <title>{{ $documentTitle }}</title>
        <x-seo
            :title="$documentTitle"
            :description="$seoData['description'] ?? null"
            :keywords="$seoData['keywords'] ?? null"
            :image="$seoData['image'] ?? null"
            :canonical="$seoData['canonical'] ?? null"
            :type="$seoData['type'] ?? 'website'"
            :author="$seoData['author'] ?? 'Timurhan Kaya'"
            :section="$seoData['section'] ?? null"
            :published-time="$seoData['published_time'] ?? null"
            :modified-time="$seoData['modified_time'] ?? null"
            :alternates="$seoData['alternates'] ?? null"
            :noindex="(bool) ($seoData['noindex'] ?? false)"
        />

        <link rel="preload" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/fonts/geist-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/fonts/syne-500-700-latin.woff2') }}" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('head')
    </head>
    <body class="brand-site {{ request()->routeIs('*.blog.*', '*.case-studies.*', '*.contact') ? 'brand-reading-page' : '' }} min-h-full antialiased">
        <a
            href="#content"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-white/95 focus:px-4 focus:py-3 focus:text-sm focus:font-semibold focus:text-zinc-950 focus:shadow-lg focus:ring-2 focus:ring-cyan-400/70 dark:focus:bg-zinc-950/95 dark:focus:text-white dark:focus:ring-cyan-300/40"
        >
            {{ __('portfolio.ui.skipToContent') }}
        </a>
        <div class="relative isolate min-h-screen">
            @include('partials.nav')

            <main id="content" class="brand-main">
                @yield('content')
            </main>

            @include('partials.footer')
        </div>

        @include('partials.command-palette')

    </body>
</html>
