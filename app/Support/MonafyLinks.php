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
            ['en' => '/', 'tr' => '/tr/', 'features' => '/features/', 'features_tr' => '/tr/ozellikler/'],
            ['en' => '/pricing/', 'tr' => '/tr/fiyatlandirma/'],
            ['en' => '/methodology/', 'tr' => '/tr/metodoloji/'],
            ['en' => '/market-report/', 'tr' => '/tr/piyasa-raporu/'],
        ];
        foreach ($pages as $page) {
            if (in_array($path, $page, true)) {
                // General Monafy links use the homepage; feature subpages stay specific.
                return 'https://monafy.com'.$page[$locale === 'tr' ? 'tr' : 'en']
                    .(isset($parts['query']) ? '?'.$parts['query'] : '')
                    .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
            }
        }

        return $url;
    }
}
