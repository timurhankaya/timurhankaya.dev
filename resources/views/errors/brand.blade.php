{{-- Stand-alone error page: no navigation or palette, so it still renders when the database or the app itself is down. --}}
@php
    $home = url(app()->getLocale() === 'tr' ? '/tr' : '/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ __('brand.error_'.$code.'_title') }} — Timurhan Kaya</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        @vite(['resources/css/app.css'])
    </head>
    <body class="brand-site min-h-full antialiased">
        <div class="brand-error-shell">
            <header class="brand-error-top"><a class="brand-logo" href="{{ $home }}" aria-label="Timurhan Kaya">tkaya<span>.</span></a></header>
            <main class="brand-error">
                <p class="brand-error-code" aria-hidden="true">{{ $code }}</p>
                <p class="brand-label">{{ __('brand.error_label') }} {{ $code }}</p>
                <h1 class="brand-display">{{ __('brand.error_'.$code.'_title') }}</h1>
                <p class="brand-error-body">{{ __('brand.error_'.$code.'_body') }}</p>
                <div class="brand-error-actions">
                    <a class="brand-button" href="{{ $home }}">{{ __('brand.error_home') }} <x-arrow /></a>
                    @if ($code === 429)
                        <a class="brand-text-link" href="mailto:kayacekovic@gmail.com">kayacekovic@gmail.com <x-arrow /></a>
                    @else
                        <a class="brand-text-link" href="{{ url(app()->getLocale() === 'tr' ? '/tr/iletisim' : '/contact') }}">{{ __('brand.error_write') }} <x-arrow /></a>
                    @endif
                </div>
            </main>
            <footer class="brand-error-foot"><p>© {{ now()->year }} Timurhan Kaya</p></footer>
        </div>
    </body>
</html>
