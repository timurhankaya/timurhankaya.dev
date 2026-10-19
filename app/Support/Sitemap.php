<?php

namespace App\Support;

use App\Enums\PostType;
use App\Models\Post;

/**
 * Every indexable address with its lastmod and hreflang set. The sitemap renders it;
 * the IndexNow command submits the same list, so the two never disagree.
 */
final class Sitemap
{
    /** @return list<array<string, mixed>> */
    public static function entries(): array
    {
        $views = fn (string ...$names): array => array_map(fn (string $name): string => resource_path('views/'.$name.'.blade.php'), $names);
        $content = [resource_path('content/portfolio.en.json'), resource_path('content/portfolio.tr.json')];
        $lang = [resource_path('lang/en.json'), resource_path('lang/tr.json')];
        $cases = Post::published()->where('type', PostType::CaseStudy)->max('updated_at');
        $articles = Post::published()->where('type', PostType::Article)->max('updated_at');

        // lastmod only moves when the page's own content (or the posts it lists) changed.
        $pages = [
            'home' => Seo::contentLastModified([...$content, ...$views('pages/home')]),
            'case-studies.index' => Seo::normalizeDate($cases) ?? Seo::contentLastModified($views('pages/blog/index'), false),
            'blog.index' => Seo::normalizeDate($articles) ?? Seo::contentLastModified($views('pages/blog/index'), false),
            'games.index' => Seo::contentLastModified([...$lang, ...$views('pages/games/index')], false),
            'games.imposter.index' => Seo::contentLastModified([...$lang, ...$views('pages/games/imposter/index')], false),
            'games.vampire.index' => Seo::contentLastModified([...$lang, ...$views('pages/games/vampire/index')], false),
            'contact' => Seo::contentLastModified([...$content, ...$views('pages/contact')], false),
            'cv' => Seo::contentLastModified([...$content, ...$views('pages/cv')], false),
        ];

        $items = collect($pages)->flatMap(fn (?string $lastmod, string $route): array => self::perLocale(Seo::route($route), $lastmod))->all();

        $posts = Post::published()->orderByDesc('published_at')->get()->flatMap(fn (Post $post): array => self::perLocale(
            $post->publicUrl(),
            Seo::normalizeDate($post->updated_at ?? $post->published_at),
            $post->cover_image ? [$post->coverUrl()] : [],
        ))->all();

        return [...$items, ...$posts];
    }

    /** @return list<string> */
    public static function urls(): array
    {
        return array_values(array_unique(array_column(self::entries(), 'loc')));
    }

    /**
     * One entry per language, each carrying the full hreflang set (itself included).
     *
     * @param  list<string>  $images
     * @return list<array<string, mixed>>
     */
    private static function perLocale(string $url, ?string $lastmod, array $images = []): array
    {
        $alternates = Seo::alternateUrls($url);

        return collect(Seo::supportedLocales())->map(fn (string $locale): array => [
            'loc' => Seo::localizedUrl($url, $locale),
            'lastmod' => $lastmod,
            'alternates' => $alternates,
            'images' => $images,
        ])->all();
    }
}
