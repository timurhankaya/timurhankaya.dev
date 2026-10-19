<?php

namespace App\Support;

/**
 * In-page anchor ids in the page's language (/tr#hakkimda, /#about). Each anchored element also carries
 * data-anchor-alt with the other language's id, so the language switch can keep the reader in the same section.
 */
final class Anchors
{
    private const IDS = [
        'about' => ['en' => 'about', 'tr' => 'hakkimda'],
        'portfolio' => ['en' => 'portfolio', 'tr' => 'calismalar'],
        'experience' => ['en' => 'experience', 'tr' => 'deneyim'],
        'games' => ['en' => 'games', 'tr' => 'oyunlar'],
        'work-together' => ['en' => 'work-together', 'tr' => 'birlikte-calisalim'],
        'contact' => ['en' => 'contact', 'tr' => 'iletisim'],
        // Project archive sections (the "anchor" key of each section in resources/content/portfolio.*.json).
        'featured' => ['en' => 'featured', 'tr' => 'one-cikanlar'],
        'platforms' => ['en' => 'saas-platforms', 'tr' => 'saas-platformlar'],
        'commerce' => ['en' => 'fintech-ecommerce', 'tr' => 'fintech-e-ticaret'],
        'other' => ['en' => 'other-work', 'tr' => 'diger-calismalar'],
    ];

    public static function id(string $key, ?string $locale = null): string
    {
        $locale = ($locale ?? app()->getLocale()) === 'tr' ? 'tr' : 'en';

        return self::IDS[$key][$locale] ?? $key;
    }

    /** `id` plus `data-anchor-alt` for an anchored element. */
    public static function attributes(string $key, ?string $locale = null): string
    {
        $locale = ($locale ?? app()->getLocale()) === 'tr' ? 'tr' : 'en';

        return sprintf('id="%s" data-anchor-alt="%s"', e(self::id($key, $locale)), e(self::id($key, $locale === 'tr' ? 'en' : 'tr')));
    }

    /** Absolute link to a section of the home page in the current language. */
    public static function home(string $key): string
    {
        return Seo::route('home').'#'.self::id($key);
    }
}
