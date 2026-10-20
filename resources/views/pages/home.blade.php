@php
    $workStartYear = 2019;
    $birthDate = \Carbon\CarbonImmutable::create(2002, 4, 12);
    $years = now()->year - $workStartYear;
    $age = $birthDate->diffInYears(\Carbon\CarbonImmutable::now());

    /** @var array<string, mixed> $content */
    $content = is_array($content ?? null) ? $content : [];
    $t = function (mixed $value) use ($years, $age): string {
        $text = is_string($value) ? $value : '';

        return str_replace([':years', ':age'], [(string) $years, (string) $age], $text);
    };
    $homeUrl = \App\Support\Seo::route('home');

    $seo = [
        'title' => $t(data_get($content, 'meta.title')),
        'description' => $t(data_get($content, 'meta.description')),
        'canonical' => $homeUrl,
        'image' => 'og-basic.png',
        'author' => 'Timurhan Kaya',
    ];
@endphp

@extends('layouts.app')

@section('title', $t(data_get($content, 'meta.title')))

@push('head')
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::webPage('ProfilePage', $homeUrl, $t(data_get($content, 'meta.title')), $t(data_get($content, 'meta.description')), [
            'mainEntity' => ['@id' => \App\Support\StructuredData::personId()],
            'dateModified' => \App\Support\Seo::contentLastModified([resource_path('content/portfolio.en.json'), resource_path('content/portfolio.tr.json'), resource_path('views/pages/home.blade.php')]),
        ]),
        \App\Support\StructuredData::person(),
        \App\Support\StructuredData::monafy(),
        \App\Support\StructuredData::website(),
    ) !!}
@endpush

@section('content')
    <header id="home" class="brand-hero">
        <div class="brand-hero-top brand-label">
            <span>{{ __('brand.personal') }}</span>
            <span>{{ __('brand.based') }} <x-arrow /></span>
        </div>
        <div class="brand-hero-grid">
            <h1 class="brand-name">Timurhan<br><span>Kaya<span class="brand-period">.</span></span></h1>
            <div class="brand-hero-note">
                <p class="brand-hero-intro">{{ __('brand.intro') }}</p>
                <a class="brand-text-link" href="#{{ \App\Support\Anchors::id('portfolio') }}">{{ __('brand.explore') }} <x-arrow dir="se" /></a>
            </div>
        </div>
        <div class="brand-hero-bottom">
            <p><span class="brand-status" aria-hidden="true"></span><span>{{ __('brand.currently') }} <strong>Wingie Enuygun Group</strong><br><span class="brand-role">{{ __('brand.employment_role') }}</span></span></p>
            <a class="brand-button" href="{{ \App\Support\Seo::route('cv') }}" target="_blank" rel="noopener">{{ __('brand.cv') }} <x-arrow /></a>
        </div>
    </header>

    <section {!! \App\Support\Anchors::attributes('portfolio') !!} class="brand-section">
        <div class="brand-section-heading">
            <h2 class="brand-label"><span class="brand-section-number">01 /</span> {{ __('brand.work') }}</h2>
        </div>
        <div class="brand-project-grid">
            @foreach ($featuredProjects as $project)
                <x-brand-project :item="$project" :number="$loop->iteration" featured />
            @endforeach
        </div>
        <x-brand-logos />
    </section>

    <section {!! \App\Support\Anchors::attributes('about') !!} class="brand-section brand-about">
        <div>
            <p class="brand-label"><span class="brand-section-number">02 /</span> {{ __('brand.about_label') }}</p>
            <h2 class="brand-display">{{ __('brand.about') }}</h2>
            <p class="brand-about-caption">{{ __('brand.about_intro') }}</p>
            <div class="brand-hobbies">
                @foreach ((array) data_get($content, 'about.hobbies', []) as $hobby)
                    <span>{{ $hobby }}</span>
                @endforeach
            </div>
            <div class="brand-about-focus"><p class="brand-label">{{ __('brand.focus_label') }}</p><p>{{ __('brand.focus_body') }}</p><a class="brand-text-link" href="#{{ \App\Support\Anchors::id('experience') }}">{{ __('brand.experience_link') }} <x-arrow dir="s" /></a></div>
        </div>
        <div class="brand-about-copy">
            @foreach (['p1', 'p2', 'p3'] as $paragraph)
                @if (filled($t(data_get($content, 'about.'.$paragraph))))<p>{{ $t(data_get($content, 'about.'.$paragraph)) }}</p>@endif
            @endforeach
            <div id="skills" class="brand-tools">
                <div class="brand-approach-heading"><div class="brand-emblem" aria-hidden="true">
                    <svg viewBox="0 0 120 120" fill="none"><path d="M32 42 14 60l18 18m56-36 18 18-18 18M70 25 50 95" stroke-width="2"/><path d="M14 14h16M14 14v16m92-16H90m16 0v16M14 106h16m-16 0V90m92 16H90m16 0V90" opacity=".35"/></svg>
                </div><h3 class="brand-label">{{ __('brand.tools') }}</h3></div>
                <ol class="brand-approach-list">
                    @foreach (__('brand.approach_items') as $item)
                        <li>
                            <span class="brand-approach-number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <div><h4>{{ $item['title'] }}</h4><p>{{ $item['body'] }}</p></div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    <section {!! \App\Support\Anchors::attributes('experience') !!} class="brand-section">
        <div class="brand-section-heading">
            <div>
                <p class="brand-label"><span class="brand-section-number">03 /</span> {{ $t(data_get($content, 'experience.title')) }}</p>
                <h2 class="brand-display">{{ __('brand.experience') }}</h2>
            </div>
        </div>
        <div class="brand-experience-list">
            @foreach ((array) data_get($content, 'experience.items', []) as $job)
                <article class="brand-job">
                    <p class="brand-job-period">{{ $t(data_get($job, 'period')) }}</p>
                    <div>
                        <h3>{{ $t(data_get($job, 'company')) }}</h3>
                        <p class="brand-job-role">{{ $t(data_get($job, 'role')) }}</p>
                        <p class="brand-job-meta">{{ $t(data_get($job, 'meta')) }}</p>
                        <ul>
                            @foreach ((array) data_get($job, 'bullets', []) as $bullet)
                                <li>{{ $t($bullet) }}</li>
                            @endforeach
                        </ul>
                    </div>
                </article>
            @endforeach
        </div>
        <a class="brand-text-link brand-cv-link" href="{{ \App\Support\Seo::route('cv') }}" target="_blank" rel="noopener">{{ __('brand.cv') }} <x-arrow /></a>
    </section>

    <section {!! \App\Support\Anchors::attributes('games') !!} class="brand-section">
        <div class="brand-section-heading">
            <div>
                <p class="brand-label"><span class="brand-section-number">04 /</span> {{ __('portfolio.nav.games') }}</p>
                <h2 class="brand-display">{{ __('brand.games') }}</h2>
            </div>
            <p>{{ __('brand.games_intro') }}</p>
        </div>
        @include('partials.brand-games')
    </section>

    <section {!! \App\Support\Anchors::attributes('work-together') !!} class="brand-section brand-services">
        <div class="brand-section-heading">
            <div><p class="brand-label"><span class="brand-section-number">05 /</span> {{ __('brand.services_label') }}</p><h2 class="brand-display">{{ __('brand.work_together') }}</h2></div>
            <p>{{ __('brand.services_intro') }}</p>
        </div>
        <div class="brand-services-grid">
            @foreach (__('brand.services') as $service)
                <article class="brand-service"><span class="brand-label">0{{ $loop->iteration }} /</span><h3>{{ $service['title'] }}</h3><p>{{ $service['body'] }}</p></article>
            @endforeach
        </div>
        <div class="brand-services-bottom"><p>{{ __('brand.project_invite') }}</p><a class="brand-button" href="{{ \App\Support\Seo::route('contact', ['topic' => 'project']) }}#form">{{ __('brand.project_cta') }} <x-arrow /></a></div>
    </section>

    <section {!! \App\Support\Anchors::attributes('contact') !!} class="brand-section brand-contact">
        <p class="brand-label"><span class="brand-section-number">06 /</span> {{ __('portfolio.nav.contact') }}</p>
        <div class="brand-contact-heading"><h2 class="brand-display">{{ __('brand.contact') }}</h2><a class="brand-button" href="{{ \App\Support\Seo::route('contact') }}#form">{{ __('brand.contact_cta') }} <x-arrow /></a></div>
        <div class="brand-contact-bottom">
            <p>{{ __('brand.contact_intro') }}</p>
        </div>
    </section>
@endsection
