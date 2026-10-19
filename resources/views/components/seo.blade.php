@props([
    'title' => null,
    'description' => null,
    'keywords' => null,
    'image' => null,
    'canonical' => null,
    'type' => 'website',
    'author' => null,
    'section' => null,
    'publishedTime' => null,
    'modifiedTime' => null,
    'alternates' => null,
    'noindex' => false,
])

@php
    $siteName = \App\Support\Seo::siteName();
    $canonicalSource = is_string($canonical) && $canonical !== '' ? $canonical : url()->current();
    $resolvedTitle = is_string($title) && $title !== '' ? $title : \App\Support\Seo::defaultTitle();
    $resolvedDescription = \App\Support\Seo::metaDescription(is_string($description) && $description !== '' ? $description : \App\Support\Seo::defaultDescription());
    $resolvedKeywords = is_string($keywords) && $keywords !== '' ? $keywords : null;
    $resolvedCanonical = \App\Support\Seo::localizedUrl($canonicalSource);
    $resolvedImage = \App\Support\Seo::imageUrl(is_string($image) ? $image : null);
    $resolvedType = is_string($type) && $type !== '' ? $type : 'website';
    $resolvedAuthor = is_string($author) && $author !== '' ? $author : 'Timurhan Kaya';
    $resolvedSection = is_string($section) && $section !== '' ? $section : null;
    $resolvedPublishedTime = \App\Support\Seo::normalizeDate($publishedTime);
    $resolvedModifiedTime = \App\Support\Seo::normalizeDate($modifiedTime);
    $resolvedAlternates = is_array($alternates) && $alternates !== [] ? $alternates : \App\Support\Seo::alternateUrls($canonicalSource);
    $ogLocale = \App\Support\Seo::ogLocale();
    $imageMeta = \App\Support\Seo::imageMeta($resolvedImage);
    $ogLocaleAlternates = collect(\App\Support\Seo::supportedLocales())
        ->reject(fn (string $locale): bool => $locale === app()->getLocale())
        ->map(fn (string $locale): string => \App\Support\Seo::ogLocale($locale))
        ->values();
    $robots = $noindex
        ? 'noindex,follow,noarchive'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
@endphp

<meta name="description" content="{{ $resolvedDescription }}">
<meta name="author" content="{{ $resolvedAuthor }}">
<meta name="application-name" content="{{ $siteName }}">
<meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
<meta name="theme-color" content="#09090b">
<meta name="format-detection" content="telephone=no,address=no,email=no">
<meta name="referrer" content="strict-origin-when-cross-origin">
@if ($resolvedKeywords)
    <meta name="keywords" content="{{ $resolvedKeywords }}">
@endif
@unless ($noindex)
    <link rel="canonical" href="{{ $resolvedCanonical }}">
    @foreach ($resolvedAlternates as $hreflang => $href)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
    @endforeach
@endunless

<meta name="robots" content="{{ $robots }}">
@if (filled(config('services.bing_webmaster.verification')))
    <meta name="msvalidate.01" content="{{ config('services.bing_webmaster.verification') }}">
@endif
@if (filled(config('services.search_console.verification')))
    <meta name="google-site-verification" content="{{ config('services.search_console.verification') }}">
@endif
@if (filled(config('services.yandex_webmaster.verification')))
    <meta name="yandex-verification" content="{{ config('services.yandex_webmaster.verification') }}">
@endif

<meta property="og:type" content="{{ $resolvedType }}">
<meta property="og:title" content="{{ $resolvedTitle }}">
<meta property="og:description" content="{{ $resolvedDescription }}">
<meta property="og:url" content="{{ $resolvedCanonical }}">
<meta property="og:image" content="{{ $resolvedImage }}">
<meta property="og:image:alt" content="{{ $resolvedTitle }}">
@if ($imageMeta)
    <meta property="og:image:type" content="{{ $imageMeta['type'] }}">
    <meta property="og:image:width" content="{{ $imageMeta['width'] }}">
    <meta property="og:image:height" content="{{ $imageMeta['height'] }}">
@endif
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $ogLocale }}">
@foreach ($ogLocaleAlternates as $alternateLocale)
    <meta property="og:locale:alternate" content="{{ $alternateLocale }}">
@endforeach
@if ($resolvedType === 'article')
    @if ($resolvedPublishedTime)
        <meta property="article:published_time" content="{{ $resolvedPublishedTime }}">
    @endif
    @if ($resolvedModifiedTime)
        <meta property="article:modified_time" content="{{ $resolvedModifiedTime }}">
    @endif
    @if ($resolvedSection)
        <meta property="article:section" content="{{ $resolvedSection }}">
    @endif
    <meta property="article:author" content="{{ \App\Support\Seo::route('home') }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $resolvedTitle }}">
<meta name="twitter:description" content="{{ $resolvedDescription }}">
<meta name="twitter:image" content="{{ $resolvedImage }}">
<meta name="twitter:image:alt" content="{{ $resolvedTitle }}">
<meta name="twitter:url" content="{{ $resolvedCanonical }}">
