@php
    $gameUrl = \App\Support\Seo::route('games.imposter.index');
    $seo = [
        'title' => __('imposter.metaTitle'),
        'description' => __('imposter.metaDescription'),
        'canonical' => $gameUrl,
        'image' => 'og-imposter.png',
    ];
@endphp

@extends('layouts.app')

@section('title', __('imposter.metaTitle'))

@php
    $trail = [[__('brand.home_crumb'), \App\Support\Seo::route('home')], [__('portfolio.nav.games'), \App\Support\Seo::route('games.index')], [__('imposter.title'), $gameUrl]];
@endphp
@push('head')
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::webPage('WebPage', $gameUrl, __('imposter.title'), __('imposter.subtitle'), [
            'mainEntity' => ['@id' => $gameUrl.'#game'],
            'breadcrumb' => ['@id' => $gameUrl.'#breadcrumb'],
        ]),
        \App\Support\StructuredData::game($gameUrl, __('imposter.title'), __('imposter.subtitle'), 'og-imposter.png', 3, ['Party game', 'Word game', 'Social deduction']),
        \App\Support\StructuredData::breadcrumbs($gameUrl, $trail),
        \App\Support\StructuredData::person(),
        \App\Support\StructuredData::website(),
    ) !!}
@endpush

@section('content')
    <x-games.doodles game="imposter" lite />
    <x-breadcrumbs :trail="$trail" />
    <header class="brand-page-header game-hero">
        <div class="game-hero-copy">
            <p class="brand-label">{{ __('imposter.kicker') }}</p>
            <h1 class="brand-display">{{ __('imposter.title') }}</h1>
        </div>
        <p class="game-hero-lead">{{ __('imposter.subtitle') }}</p>
        <div class="game-hero-art game-fan" aria-hidden="true">
            <span class="game-fan-card">?</span><span class="game-fan-card">?</span><span class="game-fan-card">!</span>
        </div>
    </header>

    <livewire:games.imposter.index />

    <section class="game-guide" aria-labelledby="imposter-roles">
        <h2 id="imposter-roles">{{ __('games.roles_title') }}</h2>
        <ul class="game-roles">
            <li class="is-crew"><x-games.icon name="crew" /><h3>{{ __('imposter.roleCrew') }}</h3><p>{{ __('games.imposter_roles.crew') }}</p></li>
            <li class="is-imposter"><x-games.icon name="imposter" /><h3>{{ __('imposter.roleImposter') }}</h3><p>{{ __('games.imposter_roles.imposter') }}</p></li>
        </ul>
    </section>

    <section class="game-guide" aria-labelledby="imposter-steps">
        <h2 id="imposter-steps">{{ __('games.how_it_works') }}</h2>
        <ol class="game-steps">
            @foreach (['create', 'join', 'start', 'discuss', 'voteStart', 'reveal'] as $step)
                <li><h3>{{ __('imposter.steps.'.$step.'.title') }}</h3><p>{{ __('imposter.steps.'.$step.'.body') }}</p></li>
            @endforeach
        </ol>
    </section>
@endsection
