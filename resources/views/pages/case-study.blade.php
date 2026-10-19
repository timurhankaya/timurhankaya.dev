@php
    $meta = $post->caseMeta();
    $series = $series ?? collect();
    $previous = $previous ?? null;
    $next = $next ?? null;
    $writing = $writing ?? collect();
    $links = $post->caseLinks();
    $seo = ['title' => $post->metaTitle(), 'description' => $post->metaDescription(), 'canonical' => $post->publicUrl(), 'image' => $post->ogImage(), 'type' => 'article', 'published_time' => $post->published_at, 'modified_time' => $post->updated_at, 'noindex' => (bool) ($isPreview ?? false)];
@endphp
@extends('layouts.app')
@section('title', $seo['title'])
@php
    $trail = [[__('brand.home_crumb'), \App\Support\Seo::route('home')], [__('brand.case_type'), \App\Support\Seo::route('case-studies.index')], [$post->text('title'), $post->publicUrl()]];
@endphp
@push('head')
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::article($post, $post->publicUrl()),
        \App\Support\StructuredData::webPage('WebPage', $post->publicUrl(), $post->text('title'), \App\Support\Seo::metaDescription($post->text('excerpt')), ['breadcrumb' => ['@id' => $post->publicUrl().'#breadcrumb']]),
        \App\Support\StructuredData::breadcrumbs($post->publicUrl(), $trail),
        \App\Support\StructuredData::person(),
        \App\Support\StructuredData::website(),
    ) !!}
@endpush
@section('content')
    @if ($isPreview ?? false)<p class="brand-preview-notice">{{ __('brand.private_preview') }}</p>@endif
    <header class="brand-case-header">
        <x-breadcrumbs :trail="$trail" />
        <p class="brand-label brand-case-eyebrow">{{ __('brand.case_type') }} / {{ $meta['project_name'] ?? '' }}@if ($series->count() > 1 && $series->contains(fn ($story) => $story->is($post))) / {{ __('brand.series_part', ['current' => $series->search(fn ($story) => $story->is($post)) + 1, 'total' => $series->count()]) }}@endif</p>
        <h1 class="brand-display">{{ $post->text('title') }}</h1>
        <x-story-meta :post="$post" />
    </header>
    @if ($post->coverUrl())<figure class="brand-article-cover"><img src="{{ $post->coverUrl() }}" alt="{{ $meta['project_name'] ?? $post->text('title') }}"></figure>
    @else
        <div class="brand-case-cover" aria-hidden="true"><div><span class="brand-label">TKAYA / {{ __('brand.case_type') }}</span><span class="brand-case-cover-name">{{ $meta['project_name'] ?? '' }}</span><span class="brand-label">{{ $meta['period'] ?? '' }}</span></div><div class="brand-case-cover-grid">@foreach (array_slice($meta['technologies'] ?? [], 0, 3) as $technology)<span>0{{ $loop->iteration }} / {{ $technology }}</span>@endforeach<span class="brand-case-cover-mark" aria-hidden="true"><x-arrow /></span></div></div>
    @endif
    <div class="brand-case-layout">
        <aside class="brand-case-facts">
            @if ($series->count() > 1)<nav class="brand-series" aria-label="{{ __('brand.series_label', ['project' => $meta['project_name'] ?? '']) }}"><p class="brand-label">{{ __('brand.series_label', ['project' => $meta['project_name'] ?? '']) }}</p><ol>@foreach ($series as $story)<li>@if ($story->is($post))<span aria-current="page">{{ $story->text('title') }}</span>@else<a href="{{ $story->publicUrl() }}">{{ $story->text('title') }}</a>@endif</li>@endforeach</ol></nav>@endif
            <x-toc :headings="$headings" />
            <dl><div><dt>{{ __('brand.case_role') }}</dt><dd>{{ __('brand.context_'.($meta['context'] ?? 'personal')) }}</dd></div>@if (filled($meta['period'] ?? null))<div><dt>{{ __('brand.case_year') }}</dt><dd>{{ $meta['period'] }}</dd></div>@endif<div><dt>{{ __('brand.case_tools') }}</dt><dd>{{ implode(' / ', $meta['technologies'] ?? []) }}</dd></div></dl>
            @if ($meta['url'] ?? null)<a class="brand-text-link" href="{{ $meta['url'] }}" target="_blank" rel="noopener noreferrer">{{ __('brand.visit') }} <x-arrow /></a>@endif
            @if ($links)<div class="brand-case-links"><p class="brand-label">{{ __('brand.case_links') }}</p>@foreach ($links as $link)<a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"><span>{{ $link['label'] }}</span><x-arrow /></a>@endforeach</div>@endif
            @if (in_array($post->project_key, ['word-imposter', 'vampir-koylu'], true))<a class="brand-button" href="{{ \App\Support\Seo::route($post->project_key === 'word-imposter' ? 'games.imposter.index' : 'games.vampire.index') }}">{{ __('brand.play') }} <x-arrow /></a>@endif
        </aside>
        <article class="brand-prose brand-case-article">{!! $articleHtml !!}</article>
    </div>
    @if ($previous || $next)<nav class="brand-case-pager" aria-label="{{ __('brand.series_pager') }}">
        @if ($previous)<a class="brand-case-prev" href="{{ $previous->publicUrl() }}" rel="prev"><span class="brand-label"><x-arrow dir="w" /> {{ __('brand.series_prev') }}</span><span>{{ $previous->text('title') }}</span></a>@else<span></span>@endif
        @if ($next)<a class="brand-case-next" href="{{ $next->publicUrl() }}" rel="next"><span class="brand-label">{{ __('brand.series_next') }} <x-arrow dir="e" /></span><span>{{ $next->text('title') }}</span></a>@endif
    </nav>@endif
    @if ($writing->isNotEmpty())<section class="brand-case-writing"><h2 class="brand-label">{{ __('brand.project_writing', ['project' => $meta['project_name'] ?? '']) }}</h2>@foreach ($writing as $article)<a href="{{ $article->publicUrl() }}"><span>{{ $article->text('title') }}</span><x-arrow /></a>@endforeach</section>@endif
    <section class="brand-case-invite"><div><p class="brand-label">{{ __('brand.work_together_nav') }}</p><h2 class="brand-display">{{ __('brand.case_cta') }}</h2></div><a class="brand-button" href="{{ \App\Support\Seo::route('contact', ['topic' => 'project']) }}#form">{{ __('brand.project_cta') }} <x-arrow /></a></section>
    @if ($related->isNotEmpty())<section class="brand-case-related"><h2 class="brand-label">{{ __('brand.case_next') }}</h2><div>@foreach ($related as $other)<a href="{{ $other->publicUrl() }}"><span class="brand-label">{{ $other->projectName() }}</span><h3>{{ $other->text('title') }}</h3><x-arrow /></a>@endforeach</div></section>@endif
@endsection
