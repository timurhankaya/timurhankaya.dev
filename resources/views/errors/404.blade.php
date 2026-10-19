@php
    $seo = ['title' => __('brand.not_found_title'), 'description' => __('brand.not_found_body'), 'noindex' => true];
@endphp
@extends('layouts.app')
@section('title', __('brand.not_found_title'))
@section('content')
    <header class="brand-page-header brand-not-found">
        <p class="brand-label">404</p>
        <h1 class="brand-display">{{ __('brand.not_found_title') }}</h1>
        <p>{{ __('brand.not_found_body') }}</p>
        <nav class="brand-collection-nav" aria-label="{{ __('brand.navigation') }}">
            <a href="{{ \App\Support\Seo::route('home') }}">{{ __('portfolio.nav.home') }}</a>
            <a href="{{ \App\Support\Seo::route('case-studies.index') }}">{{ __('brand.case_type') }}</a>
            <a href="{{ \App\Support\Seo::route('blog.index') }}">{{ __('brand.blog') }}</a>
            <a href="{{ \App\Support\Seo::route('contact') }}">{{ __('portfolio.nav.contact') }}</a>
        </nav>
    </header>
@endsection
