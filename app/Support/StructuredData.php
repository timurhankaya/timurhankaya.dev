<?php

namespace App\Support;

use App\Enums\PostType;
use App\Models\Post;
use App\Services\Portfolio\ProjectCatalog;

/**
 * Builds schema.org JSON-LD nodes that reference each other through stable @id values,
 * so every page describes the same Person and WebSite instead of redefining them.
 */
final class StructuredData
{
    public const LINKEDIN = 'https://www.linkedin.com/in/timurhan-kaya/';

    public const GITHUB = 'https://github.com/timurhankaya';

    public const EMPLOYER = 'Wingie Enuygun Group';

    public const MONAFY = 'https://monafy.com';

    /** Origin-based ids are identical in every language. */
    private static function origin(): string
    {
        return rtrim(Seo::route('home', [], 'en'), '/');
    }

    public static function personId(): string
    {
        return self::origin().'/#person';
    }

    public static function websiteId(): string
    {
        return self::origin().'/#website';
    }

    public static function monafyId(): string
    {
        return self::origin().'/#monafy';
    }

    /**
     * A reference that still names the author when a consumer does not resolve @id links.
     *
     * @return array<string, string>
     */
    public static function personRef(?string $locale = null): array
    {
        return ['@type' => 'Person', '@id' => self::personId(), 'name' => 'Timurhan Kaya', 'url' => Seo::route('home', [], $locale ?? Seo::currentLocale())];
    }

    /** @return array<string, mixed> */
    public static function person(?string $locale = null): array
    {
        $locale ??= Seo::currentLocale();

        return [
            '@type' => 'Person',
            '@id' => self::personId(),
            'name' => 'Timurhan Kaya',
            'givenName' => 'Timurhan',
            'familyName' => 'Kaya',
            'url' => Seo::route('home', [], $locale),
            'jobTitle' => $locale === 'tr' ? 'Yazılım Geliştirici' : 'Software Developer',
            'worksFor' => ['@type' => 'Organization', 'name' => self::EMPLOYER],
            'knowsAbout' => ['Software development', 'Laravel', 'PHP', 'Next.js', 'Nuxt', 'PostgreSQL', 'Redis', 'Fintech', 'AI integrations'],
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Istanbul', 'addressCountry' => 'TR'],
            'knowsLanguage' => ['tr', 'en'],
            'sameAs' => [self::LINKEDIN, self::GITHUB],
        ];
    }

    /**
     * The company the person co-founded, linked back to them so engines join the two entities.
     *
     * @return array<string, mixed>
     */
    public static function monafy(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => self::monafyId(),
            'name' => 'Monafy',
            'url' => self::MONAFY,
            'founder' => ['@id' => self::personId()],
        ];
    }

    /** @return array<string, mixed> */
    public static function website(?string $locale = null): array
    {
        $locale ??= Seo::currentLocale();

        return [
            '@type' => 'WebSite',
            '@id' => self::websiteId(),
            'url' => Seo::route('home', [], $locale),
            'name' => Seo::siteName(),
            // The wordmark people see in the header, so results can show it as the site name.
            'alternateName' => ['tkaya'],
            'description' => Seo::defaultDescription($locale),
            'inLanguage' => $locale,
            'author' => ['@id' => self::personId()],
            'publisher' => ['@id' => self::personId()],
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function webPage(string $type, string $url, string $name, string $description, array $extra = []): array
    {
        return [
            '@type' => $type,
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $name,
            'description' => $description,
            'inLanguage' => Seo::currentLocale(),
            'isPartOf' => ['@id' => self::websiteId()],
            ...$extra,
        ];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $trail  [name, url] pairs, the current page last
     * @return array<string, mixed>
     */
    public static function breadcrumbs(string $pageUrl, array $trail): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $pageUrl.'#breadcrumb',
            'itemListElement' => collect($trail)->values()->map(fn (array $crumb, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ])->all(),
        ];
    }

    /**
     * @param  iterable<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function collection(string $url, string $name, string $description, iterable $items): array
    {
        return self::webPage('CollectionPage', $url, $name, $description, [
            'breadcrumb' => ['@id' => $url.'#breadcrumb'],
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => collect($items)->values()->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'url' => $item['url'],
                ])->all(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    public static function article(Post $post, string $url, ?string $locale = null): array
    {
        $locale ??= Seo::currentLocale();
        $case = $post->type === PostType::CaseStudy;
        $meta = (array) $post->case_meta;
        $words = preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'’-]*/u', (string) preg_replace('/<[^>]+>/', ' ', $post->text('content', $locale)));
        $node = [
            '@type' => $case ? 'TechArticle' : 'BlogPosting',
            '@id' => $url.'#article',
            'headline' => mb_substr($post->text('title', $locale), 0, 110),
            'description' => $post->metaDescription($locale),
            'abstract' => $post->text('excerpt', $locale),
            'url' => $url,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url.'#webpage'],
            'image' => [Seo::imageUrl($post->ogImage($locale), $locale)],
            'datePublished' => Seo::normalizeDate($post->published_at),
            'dateModified' => Seo::normalizeDate($post->updated_at ?? $post->published_at),
            'author' => self::personRef($locale),
            'publisher' => self::personRef($locale),
            'inLanguage' => $locale,
            'wordCount' => $words ?: null,
            'articleSection' => $case ? 'Case study' : 'Article',
            'keywords' => $case ? array_values((array) ($meta['technologies'] ?? [])) : null,
        ];
        // An article names the own project it is about, so engines tie the write-up to the product's site.
        if (! $case && ($project = app(ProjectCatalog::class)->find($post->project_key))) {
            $node['about'] = ['@type' => 'Thing', 'name' => $project['name'], 'url' => $project['url']];
        }
        if ($case && $post->projectName($locale) !== '') {
            $node['about'] = array_filter(['@type' => 'Thing', 'name' => $post->projectName($locale), 'url' => $meta['url'] ?? null]);
            $series = $post->storySeries();
            if ($series->count() > 1 && ($position = $series->search(fn (Post $story): bool => $story->is($post))) !== false) {
                $node['position'] = $position + 1;
                $node['isPartOf'] = [
                    '@type' => 'CreativeWorkSeries',
                    'name' => __('brand.series_label', ['project' => $post->projectName($locale)]),
                    'url' => Seo::route('case-studies.index', ['project' => $post->project_key], $locale),
                ];
            }
        }

        return $node;
    }

    /**
     * @param  list<string>  $genre
     * @return array<string, mixed>
     */
    public static function game(string $url, string $name, string $description, string $image, int $minPlayers, array $genre): array
    {
        return [
            '@type' => 'VideoGame',
            '@id' => $url.'#game',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'image' => Seo::imageUrl($image),
            'inLanguage' => Seo::currentLocale(),
            'gamePlatform' => 'Web browser',
            'applicationCategory' => 'Game',
            'operatingSystem' => 'Any',
            'playMode' => 'MultiPlayer',
            'numberOfPlayers' => ['@type' => 'QuantitativeValue', 'minValue' => $minPlayers],
            'genre' => $genre,
            'isAccessibleForFree' => true,
            'author' => ['@id' => self::personId()],
            'publisher' => ['@id' => self::personId()],
        ];
    }

    /** One `<script type="application/ld+json">` tag holding a linked @graph. */
    public static function script(array ...$nodes): string
    {
        $graph = array_values(array_filter(array_map(self::clean(...), $nodes)));

        return '<script type="application/ld+json">'.json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
        ).'</script>';
    }

    /** Drops null, empty-string and empty-array values so no property is emitted blank. */
    private static function clean(array $node): array
    {
        $out = [];
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $value = self::clean($value);
            }
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $out[$key] = $value;
        }

        return array_is_list($node) ? array_values($out) : $out;
    }
}
