@php
    $homeUrl = \App\Support\Seo::route('home');
    $localeToggleUrl = \App\Support\Seo::localizedUrl(request()->fullUrl(), app()->getLocale() === 'tr' ? 'en' : 'tr');
    if (app()->getLocale() === 'tr' && request()->routeIs('tr.home')) $localeToggleUrl .= '?lang=en';
    $before = [
        [\App\Support\Anchors::home('about'), __('brand.about_label')],
        [\App\Support\Seo::route('games.index'), __('portfolio.nav.games')],
    ];
    $after = [[\App\Support\Anchors::home('work-together'), __('brand.work_together_nav')]];
    // Blog and case studies are two separate pages, grouped under one "Writing" menu.
    $writing = [
        [\App\Support\Seo::route('blog.index'), __('brand.blog'), __('brand.blog_hint'), request()->routeIs('*.blog.*')],
        [\App\Support\Seo::route('case-studies.index'), __('brand.case_type'), __('brand.case_hint'), request()->routeIs('*.case-studies.*')],
    ];
    $writingCurrent = collect($writing)->contains(fn ($item) => $item[3]);
@endphp
<header class="brand-nav">
    <div class="brand-nav-inner">
        <a href="{{ $homeUrl }}" class="brand-logo" aria-label="tkaya. — Timurhan Kaya, {{ __('portfolio.nav.home') }}">tkaya<span>.</span></a>
        <nav class="brand-nav-links" aria-label="{{ __('brand.navigation') }}">
            @foreach ($before as [$url, $label])<a href="{{ $url }}">{{ $label }}</a>@endforeach
            <div class="brand-nav-group" data-nav-menu @if ($writingCurrent) data-current @endif>
                <button class="brand-nav-trigger" type="button" aria-expanded="false" aria-controls="brand-writing-menu">{{ __('brand.writing_menu') }}<svg class="brand-nav-chevron" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4.5 6.5 8 10l3.5-3.5"/></svg></button>
                <div id="brand-writing-menu" class="brand-nav-panel">
                    @foreach ($writing as [$url, $label, $hint, $active])<a href="{{ $url }}" @if ($active) aria-current="page" @endif><span class="brand-nav-panel-title">{{ $label }} <x-arrow /></span><span class="brand-nav-panel-hint">{{ $hint }}</span></a>@endforeach
                </div>
            </div>
            @foreach ($after as [$url, $label])<a href="{{ $url }}">{{ $label }}</a>@endforeach
        </nav>
        <div class="brand-nav-actions">
            <a class="brand-language" data-locale-switch href="{{ $localeToggleUrl }}" hreflang="{{ app()->getLocale() === 'tr' ? 'en' : 'tr' }}" lang="{{ app()->getLocale() === 'tr' ? 'en' : 'tr' }}">{{ app()->getLocale() === 'tr' ? 'EN' : 'TR' }}<span class="sr-only"> — {{ __('brand.language') }}</span></a>
            <a class="brand-nav-cv" href="{{ \App\Support\Seo::route('cv') }}" target="_blank" rel="noopener">CV <x-arrow /></a>
            <button class="brand-menu-toggle" type="button" aria-controls="brand-mobile-menu" aria-expanded="false" data-mobile-nav-toggle>{{ __('brand.menu') }} <span aria-hidden="true">☰</span></button>
        </div>
    </div>
    <div class="brand-nav-backdrop hidden" data-mobile-nav-backdrop aria-hidden="true"></div>
    <div id="brand-mobile-menu" class="brand-mobile-menu hidden" data-mobile-nav>
        <nav aria-label="{{ __('brand.navigation') }}">
            @foreach ($before as [$url, $label])<a href="{{ $url }}">{{ $label }} <x-arrow /></a>@endforeach
            <p class="brand-mobile-group">{{ __('brand.writing_menu') }}</p>
            @foreach ($writing as [$url, $label, $hint, $active])<a class="brand-mobile-sub" href="{{ $url }}" @if ($active) aria-current="page" @endif>{{ $label }} <x-arrow /></a>@endforeach
            @foreach ($after as [$url, $label])<a href="{{ $url }}">{{ $label }} <x-arrow /></a>@endforeach
            <a href="{{ \App\Support\Anchors::home('experience') }}">{{ __('portfolio.nav.experience') }} <x-arrow /></a>
            <a href="{{ \App\Support\Seo::route('contact') }}">{{ __('portfolio.nav.contact') }} <x-arrow /></a>
        </nav>
    </div>
</header>
