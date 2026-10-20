@props(['post', 'summary' => null])
@php
    $summary ??= $post->text('excerpt');
    $published = $post->published_at ?? $post->created_at;
    $updated = $post->updated_at && $published && $post->updated_at->toDateString() !== $published->toDateString() ? $post->updated_at : null;
@endphp
@if (filled($summary))
    <p class="brand-summary brand-case-summary"><span class="brand-label">{{ __('brand.short_answer') }}</span>{{ $summary }}</p>
@endif
<p class="brand-byline">
    <span>{{ __('brand.by') }} <a href="{{ \App\Support\Seo::route('cv') }}">Timurhan Kaya</a></span>
    @if ($published)<time datetime="{{ $published->toDateString() }}">{{ $published->translatedFormat('j F Y') }}</time>@endif
    @if ($updated)<span>{{ __('brand.updated') }} <time datetime="{{ $updated->toDateString() }}">{{ $updated->translatedFormat('j F Y') }}</time></span>@endif
</p>

@if ($post->isAutomated())<p class="brand-editorial-note">{{ __('brand.ai_publication_note') }}</p>@endif
