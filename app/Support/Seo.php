<?php

namespace App\Support;

use App\Models\Post;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class Seo
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_LOCALES = ['en', 'tr'];

    /**
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public static function fallbackLocale(): string
    {
        $locale = (string) config('app.fallback_locale', 'en');

        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'en';
    }

    public static function currentLocale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, self::SUPPORTED_LOCALES, true)
            ? $locale
            : self::fallbackLocale();
    }

    /** The brand in titles, Open Graph and schema; fixed so a server's APP_NAME (e.g. "… Dev") never leaks into results. */
    public static function siteName(): string
    {
        return 'Timurhan Kaya';
    }

    public static function defaultTitle(?string $locale = null): string
    {
        $resolvedLocale = in_array($locale, self::SUPPORTED_LOCALES, true)
            ? $locale
            : self::currentLocale();

        return $resolvedLocale === 'tr'
            ? self::siteName().' — Yazılım ve AI Mühendisi'
            : self::siteName().' — Software & AI Engineer';
    }

    public static function defaultDescription(?string $locale = null): string
    {
        $resolvedLocale = in_array($locale, self::SUPPORTED_LOCALES, true)
            ? $locale
            : self::currentLocale();

        return $resolvedLocale === 'tr'
            ? 'Timurhan Kaya, yazılım ve AI mühendisi ve Monafy’nin kurucu ortağı. Ürün geliştirme, AI ajanları, teknik yazılar ve vaka incelemeleri.'
            : 'Timurhan Kaya, software & AI engineer and Monafy co-founder in Istanbul. Product development, AI agents, engineering articles and case studies.';
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return LocalizedRoutes::route($name, $parameters, $locale ?? self::currentLocale(), $absolute);
    }

    public static function localizedUrl(?string $url = null, ?string $locale = null): string
    {
        return LocalizedRoutes::translateUrl($url ?: url()->current(), in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : self::currentLocale());
    }

    /**
     * @return array<string, string>
     */
    public static function alternateUrls(?string $url = null): array
    {
        $resolvedUrl = $url ?: url()->current();
        $alternates = [];

        foreach (self::SUPPORTED_LOCALES as $locale) {
            $alternates[$locale] = self::localizedUrl($resolvedUrl, $locale);
        }

        $alternates['x-default'] = self::localizedUrl($resolvedUrl, self::fallbackLocale());

        return $alternates;
    }

    public static function ogLocale(?string $locale = null): string
    {
        return ($locale ?? self::currentLocale()) === 'tr' ? 'tr_TR' : 'en_US';
    }

    /** A share image in the page's language: `og-games.png` becomes `og-games-tr.png` on Turkish pages when that file exists. */
    public static function imageUrl(?string $image = null, ?string $locale = null): string
    {
        if (is_string($image) && $image !== '' && Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }
        $path = ltrim((string) ($image ?: 'og-basic.png'), '/');
        $locale ??= self::currentLocale();
        if ($locale !== self::fallbackLocale()) {
            $variant = (string) preg_replace('/(\.[a-z0-9]+)$/i', '-'.$locale.'$1', $path);
            $path = is_file(public_path($variant)) ? $variant : $path;
        }

        return asset($path);
    }

    public static function normalizeDate(DateTimeInterface|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return Carbon::instance($value)->toAtomString();
            }

            return Carbon::parse($value)->toAtomString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $paths
     */
    public static function lastModifiedForPaths(array $paths): ?string
    {
        $timestamps = collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && is_file($path))
            ->map(fn (string $path): int => (int) filemtime($path))
            ->filter(fn (int $timestamp): bool => $timestamp > 0)
            ->values();

        if ($timestamps->isEmpty()) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamps->max())->toAtomString();
    }

    /**
     * Most recent content change to a page's own files or to any published post, whichever is later.
     * Lets hub pages (home, listings) report a lastmod that tracks real content changes, not deploys.
     *
     * @param  list<string>  $paths
     */
    public static function contentLastModified(array $paths = [], bool $includePosts = true): ?string
    {
        $paths = array_values(array_filter($paths, 'is_string'));
        // Keyed by project-relative paths: release directories change on every deploy.
        $key = sha1(implode('|', array_map(fn (string $path): string => str_replace(base_path(), '', $path), $paths)));
        $candidates = collect([ContentStamp::lastChanged($key, $paths)])->filter();
        if ($includePosts && ($latest = Post::published()->max('updated_at'))) {
            $candidates->push(Carbon::parse($latest));
        }

        return $candidates->max()?->toAtomString();
    }

    /** Meta descriptions beyond ~160 characters are cut by search results, so end on a word boundary. */
    public static function metaDescription(?string $text, int $limit = 160): string
    {
        return self::clip((string) $text, $limit);
    }

    /** Multibyte-safe shortening at a word boundary (a hard cut only when no space is near the end), ending in an ellipsis. */
    public static function clip(string $text, int $limit): string
    {
        $plain = trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));
        if (mb_strlen($plain) <= $limit) {
            return $plain;
        }
        $cut = mb_substr($plain, 0, $limit - 1);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $limit * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return preg_replace('/[\s,;:.\-–—]+$/u', '', $cut).'…';
    }

    /**
     * Real dimensions and MIME type for images served from this site; null when they cannot be read.
     *
     * @return array{width: int, height: int, type: string}|null
     */
    public static function imageMeta(string $imageUrl): ?array
    {
        $path = public_path(ltrim((string) parse_url($imageUrl, PHP_URL_PATH), '/'));
        $host = parse_url($imageUrl, PHP_URL_HOST);
        if (($host !== null && $host !== parse_url((string) url('/'), PHP_URL_HOST)) || ! is_file($path)) {
            return null;
        }
        $info = @getimagesize($path);

        return $info ? ['width' => $info[0], 'height' => $info[1], 'type' => $info['mime']] : null;
    }

    public static function descriptionFromHtml(?string $html, int $limit = 160): string
    {
        $plain = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $html)));

        return Str::limit($plain, $limit);
    }
}
