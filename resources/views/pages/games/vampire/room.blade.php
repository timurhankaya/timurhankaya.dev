@php
    $seo = [
        'title' => __('vampire.title').' · '.__('vampire.roomTitle', ['code' => $roomCode]),
        'description' => __('vampire.roomMetaDescription'),
        'canonical' => \App\Support\Seo::route('games.vampire.room', ['roomCode' => $roomCode]),
        'image' => 'og-vampire.png',
        'noindex' => true,
    ];
@endphp

@extends('layouts.app')

@section('title', __('vampire.title').' · '.__('vampire.roomTitle', ['code' => $roomCode]))

@section('content')
    <header class="game-room-header">
        <p class="brand-label">{{ __('vampire.roomTitle', ['code' => $roomCode]) }}</p>
        <h1>{{ __('vampire.title') }}</h1>
    </header>
    <livewire:games.vampire.room :room-code="$roomCode" />
@endsection
