@php
    $publishedAt = $post->published_at ?? $post->created_at;
    $modifiedAt = $post->updated_at ?? $publishedAt;
    $postUrl = $post->publicUrl();
    $blogUrl = \App\Support\Seo::route('blog.index');
    $postDescription = $post->text('excerpt') ?: \App\Support\Seo::descriptionFromHtml($post->text('content'));
    $seo = [
        'title' => $post->metaTitle(),
        'description' => $post->metaDescription() ?: $postDescription,
        'canonical' => $postUrl,
        'alternates' => $post->alternateUrls(),
        'image' => $post->ogImage(),
        'type' => 'article',
        'noindex' => (bool) ($isPreview ?? false),
        'author' => 'Timurhan Kaya',
        'section' => 'Blog',
        'published_time' => $publishedAt,
        'modified_time' => $modifiedAt,
    ];
@endphp

@extends('layouts.app')

@section('title', $post->text('title'))

@php
    $trail = [[__('brand.home_crumb'), \App\Support\Seo::route('home')], [__('brand.blog'), $blogUrl], [$post->text('title'), $postUrl]];
@endphp
@push('head')
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::article($post, $postUrl),
        \App\Support\StructuredData::webPage('WebPage', $postUrl, $post->text('title'), \App\Support\Seo::metaDescription($postDescription), ['breadcrumb' => ['@id' => $postUrl.'#breadcrumb']]),
        \App\Support\StructuredData::breadcrumbs($postUrl, $trail),
        \App\Support\StructuredData::person(),
        \App\Support\StructuredData::website(),
    ) !!}
@endpush

@section('content')
    @if ($isPreview ?? false)<p class="brand-preview-notice">{{ __('brand.private_preview') }}</p>@endif
    <header class="brand-case-header brand-article-header">
        <x-breadcrumbs :trail="$trail" />
        <p class="brand-label brand-case-eyebrow">{{ __('brand.type_article') }}</p>
        <h1 class="brand-display">{{ $post->text('title') }}</h1>
        <x-story-meta :post="$post" :summary="$postDescription" />
    </header>
    @if ($post->cover_image)<figure class="brand-article-cover"><img src="{{ $post->coverUrl() }}" alt="{{ $post->text('title') }}"></figure>@endif
    <div class="brand-article-layout">
        <aside class="brand-article-aside"><x-toc :headings="$headings ?? []" /><p class="brand-label">Timurhan Kaya</p><p>{{ max(1, (int) ceil(str_word_count(strip_tags($post->text('content'))) / 200)) }} {{ __('brand.reading_time') }}</p>@if ($project = app(\App\Services\Portfolio\ProjectCatalog::class)->find($post->project_key))<div class="brand-article-project"><p class="brand-label">{{ __('brand.article_project') }}</p><a class="brand-text-link" href="{{ $project['url'] }}" target="_blank" rel="noopener">{{ $project['name'] }} <x-arrow /></a></div>@endif<a class="brand-text-link" href="{{ $blogUrl }}">{{ __('brand.all_writing') }} <x-arrow /></a></aside>
        <article class="brand-prose blog-content">{!! $articleHtml !!}</article>
    </div>
@endsection
