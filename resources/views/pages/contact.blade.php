@php
    $years = now()->year - 2019;

    /** @var array<string, mixed> $content */
    $content = is_array($content ?? null) ? $content : [];
    $t = function (mixed $value) use ($years): string {
        $text = is_string($value) ? $value : '';

        return str_replace([':years'], [(string) $years], $text);
    };

    $contactUrl = \App\Support\Seo::route('contact');
    $homeUrl = \App\Support\Seo::route('home');

    $seo = [
        'title' => $t(data_get($content, 'contact.title')).' — '.$t(data_get($content, 'meta.title')),
        'description' => $t(data_get($content, 'contact.meta_description') ?: data_get($content, 'contact.description')),
        'canonical' => $contactUrl,
        'image' => 'og-contact.png',
        'author' => 'Timurhan Kaya',
    ];
@endphp

@extends('layouts.app')

@section('title', $t(data_get($content, 'contact.title')).' — '.$t(data_get($content, 'meta.title')))

@php
    $trail = [[__('brand.home_crumb'), $homeUrl], [$t(data_get($content, 'contact.title')), $contactUrl]];
@endphp
@push('head')
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::webPage('ContactPage', $contactUrl, $t(data_get($content, 'contact.title')), $t(data_get($content, 'contact.description')), [
            'mainEntity' => ['@id' => \App\Support\StructuredData::personId()],
            'breadcrumb' => ['@id' => $contactUrl.'#breadcrumb'],
        ]),
        \App\Support\StructuredData::breadcrumbs($contactUrl, $trail),
        [...\App\Support\StructuredData::person(), 'email' => 'kayacekovic@gmail.com'],
        \App\Support\StructuredData::website(),
    ) !!}
@endpush

@section('content')
    <x-breadcrumbs :trail="$trail" />
    <header class="brand-page-header">
        <p class="brand-label">{{ __('brand.contact_page') }} / Timurhan Kaya</p>
        <h1 class="brand-display">{{ __('brand.contact') }}</h1>
        <p>{{ __('brand.contact_intro') }}</p>
    </header>
    <div class="brand-contact-page">
        <div class="brand-contact-links">
            <div class="brand-contact-row">
                <div><p class="brand-label">{{ __('portfolio.ui.email') }}</p><a href="mailto:kayacekovic@gmail.com">kayacekovic@gmail.com <x-arrow /></a></div>
                <button type="button" data-copy-text="kayacekovic@gmail.com" data-copy-label="{{ __('brand.copy_email') }}" data-copy-label-success="{{ __('brand.copied') }}">{{ __('brand.copy_email') }}</button>
            </div>
            <div class="brand-contact-row"><div><p class="brand-label">LinkedIn</p><a href="https://www.linkedin.com/in/timurhan-kaya/" target="_blank" rel="noopener noreferrer">Timurhan Kaya <x-arrow /></a></div></div>
            <div class="brand-contact-row"><div><p class="brand-label">GitHub</p><a href="https://github.com/timurhankaya" target="_blank" rel="noopener noreferrer">@timurhankaya <x-arrow /></a></div></div>
        </div>
        <aside class="brand-cv-aside"><h2>{{ __('brand.cv') }}</h2><p>{{ __('brand.cv_note') }}</p><a class="brand-button" href="{{ \App\Support\Seo::route('cv') }}" target="_blank" rel="noopener">{{ __('brand.cv') }} <x-arrow /></a></aside>
    </div>
@endsection
