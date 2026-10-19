@php
    $seo = ['title' => __('brand.not_found_title'), 'description' => __('brand.not_found_body'), 'noindex' => true];
    $links = [
        [__('portfolio.nav.home'), \App\Support\Seo::route('home')],
        [__('brand.blog'), \App\Support\Seo::route('blog.index')],
        [__('brand.case_type'), \App\Support\Seo::route('case-studies.index')],
        [__('portfolio.nav.games'), \App\Support\Seo::route('games.index')],
        [__('portfolio.nav.contact'), \App\Support\Seo::route('contact')],
    ];
@endphp
@extends('layouts.app')
@section('title', __('brand.not_found_title'))
@section('content')
    <section class="brand-error">
        <p class="brand-error-code" aria-hidden="true">404</p>
        <p class="brand-label">{{ __('brand.error_label') }} 404</p>
        <h1 class="brand-display">{{ __('brand.not_found_title') }}</h1>
        <p class="brand-error-body">{{ __('brand.not_found_body') }}</p>
        <nav aria-label="{{ __('brand.error_links') }}">
            <ul class="brand-error-links">
                @foreach ($links as [$label, $url])
                    <li><a href="{{ $url }}">{{ $label }} <x-arrow /></a></li>
                @endforeach
            </ul>
        </nav>
    </section>
@endsection
