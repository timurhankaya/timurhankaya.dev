{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>{{ __('brand.feed_title') }}</title>
        <link>{{ \App\Support\Seo::route('home', [], $locale) }}</link>
        <description>{{ __('brand.writing_intro') }}</description>
        <language>{{ $locale }}</language>
        @if ($posts->isNotEmpty())
        <lastBuildDate>{{ $posts->max(fn ($post) => $post->updated_at ?? $post->published_at)->toRssString() }}</lastBuildDate>
        @endif
        <atom:link href="{{ \App\Support\Seo::route('feed', [], $locale) }}" rel="self" type="application/rss+xml" />
@foreach ($posts as $post)
        <item>
            <title>{{ $post->text('title', $locale) }}</title>
            <link>{{ $post->publicUrl($locale) }}</link>
            <guid isPermaLink="true">{{ $post->publicUrl($locale) }}</guid>
            <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
            <dc:creator>Timurhan Kaya</dc:creator>
            <category>{{ $post->type === \App\Enums\PostType::CaseStudy ? __('brand.case_type') : __('brand.type_article') }}</category>
            <description>{{ $post->text('excerpt', $locale) }}</description>
        </item>
@endforeach
    </channel>
</rss>
