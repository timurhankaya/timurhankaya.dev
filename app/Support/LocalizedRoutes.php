<?php

namespace App\Support;

use App\Enums\PostType;
use App\Models\Post;

final class LocalizedRoutes
{
    public const PATHS = [
        'home' => ['en' => '', 'tr' => ''],
        'contact' => ['en' => 'contact', 'tr' => 'iletisim'],
        'cv' => ['en' => 'cv', 'tr' => 'ozgecmis'],
        'games.index' => ['en' => 'games', 'tr' => 'oyunlar'],
        'games.imposter.index' => ['en' => 'games/imposter', 'tr' => 'oyunlar/kelime-casusu'],
        'games.imposter.room' => ['en' => 'games/imposter/{roomCode}', 'tr' => 'oyunlar/kelime-casusu/{roomCode}'],
        'games.vampire.index' => ['en' => 'games/vampire', 'tr' => 'oyunlar/vampir-koylu'],
        'games.vampire.room' => ['en' => 'games/vampire/{roomCode}', 'tr' => 'oyunlar/vampir-koylu/{roomCode}'],
        'blog.index' => ['en' => 'writing', 'tr' => 'yazilar'],
        'blog.show' => ['en' => 'writing/{slug}', 'tr' => 'yazilar/{slug}'],
        'case-studies.index' => ['en' => 'case-studies', 'tr' => 'vaka-incelemeleri'],
        'case-studies.show' => ['en' => 'case-studies/{slug}', 'tr' => 'vaka-incelemeleri/{slug}'],
        'llms' => ['en' => 'llms.txt', 'tr' => 'llms.txt'],
        'feed' => ['en' => 'feed.xml', 'tr' => 'feed.xml'],
        'preview.typography' => ['en' => 'preview/typography', 'tr' => 'onizleme/icerik-ornekleri'],
    ];

    public static function route(string $name, array $parameters, string $locale, bool $absolute = true): string
    {
        unset($parameters['locale'], $parameters['lang']);
        if (isset($parameters['post'])) {
            $parameters['slug'] = $parameters['post'] instanceof Post
                ? $parameters['post']->publicSlug($locale) : $parameters['post'];
            unset($parameters['post']);
        }

        return route(isset(self::PATHS[$name]) ? $locale.'.'.$name : $name, $parameters, $absolute);
    }

    /** @return array{name:string, parameters:array}|null */
    public static function match(string $path): ?array
    {
        $path = trim($path, '/');
        // Compatibility with pre-redesign and intermediate preview paths.
        $aliases = [
            'blog' => 'blog.index', 'blog/{slug}' => 'blog.show',
            'preview/case-studies/{slug}' => 'case-studies.show',
        ];
        foreach (self::PATHS as $name => $paths) {
            foreach (array_unique($paths) as $template) {
                $aliases[$template] = $name;
            }
        }
        foreach ($aliases as $template => $name) {
            $keys = [];
            $parts = preg_split('/(\{\w+\})/', $template, -1, PREG_SPLIT_DELIM_CAPTURE);
            $pattern = '';
            foreach ($parts as $part) {
                if (preg_match('/^\{(\w+)\}$/', $part, $parameter)) {
                    $keys[] = $parameter[1];
                    $pattern .= '([^/]+)';
                } else {
                    $pattern .= preg_quote($part, '~');
                }
            }
            if (preg_match('~^'.$pattern.'$~u', $path, $matches)) {
                array_shift($matches);

                return ['name' => $name, 'parameters' => array_combine($keys, $matches) ?: []];
            }
        }

        return null;
    }

    public static function translateUrl(string $url, string $locale): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }
        $segments = explode('/', trim($parts['path'] ?? '', '/'));
        if (in_array($segments[0] ?? null, Seo::supportedLocales(), true)) {
            array_shift($segments);
        }
        $path = implode('/', $segments);
        $match = self::match($path);
        if ($match !== null) {
            if (isset($match['parameters']['slug'])) {
                $post = Post::published()->withPublicSlug(rawurldecode($match['parameters']['slug']))->first();
                if ($post) {
                    $match['name'] = $post->type === PostType::CaseStudy ? 'case-studies.show' : 'blog.show';
                    $match['parameters']['slug'] = $post->publicSlug($locale);
                }
            }
            $path = self::PATHS[$match['name']][$locale];
            foreach ($match['parameters'] as $key => $value) {
                $path = str_replace('{'.$key.'}', $value, $path);
            }
        }
        parse_str($parts['query'] ?? '', $query);
        unset($query['lang']);
        ksort($query);
        $origin = isset($parts['host']) ? ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '') : '';

        return $origin.($locale === 'en' ? ($path !== '' ? '/'.$path : '/') : '/tr'.($path !== '' ? '/'.$path : ''))
            .($query !== [] ? '?'.http_build_query($query) : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
