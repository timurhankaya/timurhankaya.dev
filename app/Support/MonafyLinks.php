<?php

namespace App\Support;

final class MonafyLinks
{
    public static function localized(string $url, string $locale): string
    {
        $parts = parse_url($url);
        if (! in_array(strtolower($parts['host'] ?? ''), ['monafy.com', 'www.monafy.com'], true)) {
            return $url;
        }

        $path = '/'.trim($parts['path'] ?? '', '/');
        $path = $path === '/' ? '/' : $path.'/';
        $pages = [
            ['en' => '/features/', 'tr' => '/tr/ozellikler/', 'home' => '/', 'home_tr' => '/tr/'],
            ['en' => '/pricing/', 'tr' => '/tr/fiyatlandirma/'],
            ['en' => '/methodology/', 'tr' => '/tr/metodoloji/'],
            ['en' => '/market-report/', 'tr' => '/tr/piyasa-raporu/'],
        ];
        foreach ($pages as $page) {
            if (in_array($path, $page, true)) {
                // The root negotiates language; an explicit product page does not.
                return 'https://monafy.com'.$page[$locale === 'tr' ? 'tr' : 'en']
                    .(isset($parts['query']) ? '?'.$parts['query'] : '')
                    .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
            }
        }

        return $url;
    }
}
