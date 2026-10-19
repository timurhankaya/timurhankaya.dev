@php
    $gameUrl = \App\Support\Seo::route('games.vampire.index');
    $seo = [
        'title' => __('vampire.metaTitle'),
        'description' => __('vampire.metaDescription'),
        'canonical' => $gameUrl,
        'image' => 'og-vampire.png',
    ];
@endphp

@extends('layouts.app')

@section('title', __('vampire.metaTitle'))

@php
    $trail = [[__('brand.home_crumb'), \App\Support\Seo::route('home')], [__('portfolio.nav.games'), \App\Support\Seo::route('games.index')], [__('vampire.title'), $gameUrl]];
@endphp
@push('head')
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::webPage('WebPage', $gameUrl, __('vampire.title'), __('vampire.subtitle'), [
            'mainEntity' => ['@id' => $gameUrl.'#game'],
            'breadcrumb' => ['@id' => $gameUrl.'#breadcrumb'],
        ]),
        \App\Support\StructuredData::game($gameUrl, __('vampire.title'), __('vampire.subtitle'), 'og-vampire.png', 4, ['Party game', 'Social deduction', 'Hidden role']),
        \App\Support\StructuredData::breadcrumbs($gameUrl, $trail),
        \App\Support\StructuredData::person(),
        \App\Support\StructuredData::website(),
    ) !!}
@endpush

@section('content')
    <div class="vk-sky" data-sky="lobby" aria-hidden="true"><span class="vk-sky-layer vk-sky-lobby"></span></div>
    <x-games.doodles game="vampire" lite phase="lobby" />
    <x-breadcrumbs :trail="$trail" />
    <header class="brand-page-header game-hero">
        <div class="game-hero-copy">
            <p class="brand-label">{{ __('vampire.kicker') }}</p>
            <h1 class="brand-display">{{ __('vampire.title') }}</h1>
        </div>
        <p class="game-hero-lead">{{ __('vampire.subtitle') }}</p>
        <div class="game-hero-art game-nightfall" aria-hidden="true">
            <span class="game-nightfall-moon"></span>
            <span class="game-nightfall-star" style="top: 30px; left: 44%">✦</span>
            <span class="game-nightfall-star" style="top: 96px; left: 62%; font-size: 9px">✦</span>
            <span class="game-nightfall-star" style="top: 132px; left: 22%; font-size: 8px">✦</span>
            <x-games.icon name="bat" />
            <span class="game-nightfall-village"><svg viewBox="0 0 320 64" preserveAspectRatio="none"><path fill="currentColor" d="M0 64V44h22V30l14-12 14 12v14h18V36l10-8 10 8v8h26V22l18-16 18 16v22h20V34l12-10 12 10v10h22V28l16-14 16 14v16h24V38l9-7 9 7v6h20v20Z"/></svg></span>
        </div>
    </header>

    <livewire:games.vampire.index />

    <section class="game-guide" aria-labelledby="vampire-roles">
        <h2 id="vampire-roles">{{ __('games.roles_title') }}</h2>
        <ul class="game-roles">
            <li class="is-vampire"><x-games.icon name="vampire" /><h3>{{ __('vampire.roles.vampir') }}</h3><p>{{ __('games.vampire_roles.vampire') }}</p></li>
            <li><x-games.icon name="villager" /><h3>{{ __('vampire.roles.koylu') }}</h3><p>{{ __('games.vampire_roles.villager') }}</p></li>
            <li><x-games.icon name="doctor" /><h3>{{ __('vampire.roles.doktor') }}</h3><p>{{ __('games.vampire_roles.doctor') }}</p></li>
            <li><x-games.icon name="detective" /><h3>{{ __('vampire.roles.dedektif') }}</h3><p>{{ __('games.vampire_roles.detective') }}</p></li>
            <li><x-games.icon name="hunter" /><h3>{{ __('vampire.roles.avci') }}</h3><p>{{ __('games.vampire_roles.hunter') }}</p></li>
        </ul>
    </section>

    <section class="game-guide" aria-labelledby="vampire-steps">
        <h2 id="vampire-steps">{{ __('vampire.howToPlay') }}</h2>
        <ol class="game-steps game-steps-text">
            @foreach ([__('vampire.rulesLine1'), __('vampire.rulesLine2'), __('vampire.rulesLine3'), __('vampire.rulesLine4')] as $rule)
                <li><p>{{ $rule }}</p></li>
            @endforeach
        </ol>
    </section>
@endsection
