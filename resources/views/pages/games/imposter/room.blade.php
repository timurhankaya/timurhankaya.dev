@php
    $seo = [
        'title' => __('imposter.title').' · '.__('imposter.roomTitle', ['code' => $roomCode]),
        'description' => __('imposter.roomMetaDescription'),
        'canonical' => \App\Support\Seo::route('games.imposter.room', ['roomCode' => $roomCode]),
        'image' => 'og-imposter.png',
        'noindex' => true,
    ];
@endphp

@extends('layouts.app')

@section('title', __('imposter.title').' · '.__('imposter.roomTitle', ['code' => $roomCode]))

@section('content')
    <header class="game-room-header">
        <p class="brand-label">{{ __('imposter.roomTitle', ['code' => $roomCode]) }}</p>
        <h1>{{ __('imposter.title') }}</h1>
    </header>
    <livewire:games.imposter.room :room-code="$roomCode" />
@endsection
