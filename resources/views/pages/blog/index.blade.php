@php
    $caseIndex = $caseIndex ?? false;
    $project = $project ?? null;
    $projectCounts = $projectCounts ?? collect();
    $projectNames = $projectNames ?? [];
    $listingUrl = \App\Support\Seo::route($caseIndex ? 'case-studies.index' : 'blog.index');
    $seo = ['title' => $listing['title'], 'description' => $listing['description'], 'canonical' => $listing['canonical'], 'image' => 'og-blog.png'];
    $casePosts = $posts->getCollection()->filter(fn ($post) => $post->type === \App\Enums\PostType::CaseStudy);
    $articles = $posts->getCollection()->filter(fn ($post) => $post->type === \App\Enums\PostType::Article);
@endphp
@extends('layouts.app')
@section('title', $seo['title'])
@php
    $trail = [[__('brand.home_crumb'), \App\Support\Seo::route('home')], [$caseIndex ? __('brand.case_type') : __('brand.blog'), $listingUrl]];
@endphp
@push('head')
    @if ($listing['prev'])<link rel="prev" href="{{ \App\Support\Seo::localizedUrl($listing['prev']) }}">@endif
    @if ($listing['next'])<link rel="next" href="{{ \App\Support\Seo::localizedUrl($listing['next']) }}">@endif
    {!! \App\Support\StructuredData::script(
        \App\Support\StructuredData::collection($listing['canonical'], $seo['title'], $seo['description'], $posts->getCollection()->map(fn ($post) => ['name' => $post->text('title'), 'url' => $post->publicUrl()])),
        \App\Support\StructuredData::breadcrumbs($listingUrl, $trail),
        \App\Support\StructuredData::person(),
        \App\Support\StructuredData::website(),
    ) !!}
@endpush
@section('content')
    <x-breadcrumbs :trail="$trail" />
    <header class="brand-page-header brand-writing-header">
        <p class="brand-label">{{ $caseIndex ? __('brand.case_type') : __('brand.blog') }}</p>
        <h1 class="brand-display">{{ $caseIndex ? __('brand.case_collection_title') : __('brand.writing_title') }}</h1>
        <p>{{ $caseIndex ? __('brand.case_collection_intro') : __('brand.writing_intro') }}</p>
        @if ($caseIndex && $projectCounts->count() > 1)
            <nav class="brand-collection-nav brand-project-filter" aria-label="{{ __('brand.all_projects') }}">
                <a href="{{ \App\Support\Seo::route('case-studies.index') }}" @if (!$project) aria-current="page" @endif>{{ __('brand.all_projects') }} <span>{{ $projectCounts->sum() }}</span></a>
                @foreach ($projectCounts as $key => $total)<a href="{{ \App\Support\Seo::route('case-studies.index', ['project' => $key]) }}" @if ($project === $key) aria-current="page" @endif>{{ $projectNames[$key] ?? $key }} <span>{{ $total }}</span></a>@endforeach
            </nav>
        @endif
    </header>
    @if ($casePosts->isNotEmpty())
        @foreach ($casePosts->groupBy(fn ($post) => $post->project_key ?? $post->slug) as $group)
            @php
                $lead = $group->first();
                $groupName = $lead->projectName();
            @endphp
            <section class="brand-writing-section brand-case-group">
                <div class="brand-section-heading">
                    <div><p class="brand-label">{{ __('brand.case_type') }} / {{ __('brand.context_'.data_get($lead->case_meta, 'context', 'personal')) }}@if (filled(data_get($lead->case_meta, 'period'))) / {{ data_get($lead->case_meta, 'period') }}@endif</p><h2 class="brand-display">{{ $groupName }}</h2></div>
                    @if (data_get($lead->case_meta, 'url'))<a class="brand-text-link" href="{{ data_get($lead->case_meta, 'url') }}" target="_blank" rel="noopener noreferrer">{{ __('brand.visit') }} <span class="sr-only">— {{ $groupName }}</span><x-arrow /></a>@endif
                </div>
                <div class="brand-story-grid">
                    @foreach ($group as $post)
                        <a class="brand-story-card" href="{{ $post->publicUrl() }}">
                            @if ($post->coverUrl())<div class="brand-story-cover brand-story-cover-image"><img src="{{ $post->coverUrl() }}" alt="{{ $post->projectName() }}" loading="lazy"></div>
                            @else<div class="brand-story-cover brand-story-cover-{{ ($loop->parent->index + $loop->index) % 3 + 1 }}" aria-hidden="true"><span class="brand-label">{{ $post->projectName() }}@if (filled(data_get($post->case_meta, 'period'))) / {{ data_get($post->case_meta, 'period') }}@endif</span><span class="brand-story-name">{{ $group->count() > 1 ? str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) : $post->projectName() }}</span><span class="brand-story-symbol" aria-hidden="true"><x-arrow /></span></div>@endif
                            <div class="brand-story-copy"><p class="brand-label">{{ $group->count() > 1 ? __('brand.series_part', ['current' => $loop->iteration, 'total' => $group->count()]) : __('brand.case_type').' / '.__('brand.context_'.data_get($post->case_meta, 'context', 'personal')) }}</p><h3>{{ $post->text('title') }}</h3><p>{{ $post->text('excerpt') }}</p><span class="brand-story-read">{{ __('brand.read_case') }} <x-arrow /></span></div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
    @if ($articles->isNotEmpty())
        <section class="brand-writing-section">
            <div class="brand-section-heading"><h2 class="brand-label">{{ __('brand.blog') }}</h2></div>
            @foreach ($articles as $post)
                <article class="brand-post"><time class="brand-label" datetime="{{ $post->published_at->format('Y-m-d') }}">{{ $post->published_at->translatedFormat('j F Y') }}</time><div><p class="brand-label">{{ __('brand.type_article') }}</p><h2><a href="{{ $post->publicUrl() }}">{{ $post->text('title') }}</a></h2><p>{{ $post->text('excerpt') ?: \App\Support\Seo::descriptionFromHtml($post->text('content'), 150) }}</p></div><span class="brand-post-arrow" aria-hidden="true"><x-arrow /></span></article>
            @endforeach
        </section>
    @endif
    @if ($posts->isEmpty() && ! $caseIndex)
        <div class="brand-empty"><p>{{ __('brand.writing_empty') }}</p><a class="brand-text-link" href="{{ \App\Support\Seo::route('case-studies.index') }}">{{ __('brand.writing_empty_link') }} <x-arrow /></a></div>
    @elseif ($posts->isEmpty())
        <p class="brand-empty">{{ __('blog.empty') }}</p>
    @endif
    <div class="mt-12">{{ $posts->links() }}</div>
    @if (app()->isLocal())<a class="brand-example-card brand-writing-section" href="{{ \App\Support\Seo::route('preview.typography') }}"><span class="brand-label">{{ __('brand.type_example') }} / HTML</span><h3>{{ __('brand.example_title') }}</h3><p>{{ __('brand.example_intro') }}</p><span class="brand-example-arrow" aria-hidden="true"><x-arrow /></span></a>@endif
@endsection
