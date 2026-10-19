@php
    $gamesUrl = \App\Support\Seo::route('games.index');
    $imposterUrl = \App\Support\Seo::route('games.imposter.index');
    $vampireUrl = \App\Support\Seo::route('games.vampire.index');
    $seo = [
        'title' => __('games.metaTitle'),
        'description' => __('games.metaDescription'),
        'canonical' => $gamesUrl,
        'image' => 'og-games.png',
    ];
@endphp

@extends('layouts.app')

@section('title', __('games.metaTitle'))

@php
    $trail = [[__('brand.home_crumb'), \App\Support\Seo::route('home')], [__('portfolio.nav.games'), $gamesUrl]];
@endphp
@push('head')
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::collection($gamesUrl, __('games.metaTitle'), __('games.metaDescription'), [
            ['name' => __('imposter.title'), 'url' => $imposterUrl],
            ['name' => __('vampire.title'), 'url' => $vampireUrl],
        ]),
        \App\Support\StructuredData::breadcrumbs($gamesUrl, $trail),
        \App\Support\StructuredData::person(),
        \App\Support\StructuredData::website(),
    ) !!}
@endpush

@section('content')
    <x-breadcrumbs :trail="$trail" />
    <header class="brand-page-header brand-games-header">
        <p class="brand-label">Timurhan Kaya / {{ __('portfolio.nav.games') }}</p>
        <h1 class="brand-display">{{ __('brand.games') }}</h1>
        <p>{{ __('brand.games_intro') }}</p>
        <div class="brand-game-note brand-label"><span class="brand-status" aria-hidden="true"></span>{{ __('brand.game_note') }}</div>
    </header>
    @include('partials.brand-games')
    <section class="brand-game-guide"><h2 class="brand-display">{{ __('brand.game_how') }}</h2><ol>@foreach (__('brand.game_steps') as $step)<li><span class="brand-label">0{{ $loop->iteration }} /</span><p>{{ $step }}</p></li>@endforeach</ol></section>
@endsection
