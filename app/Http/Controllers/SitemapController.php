<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use App\Support\Sitemap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function robots(): Response
    {
        // Private tooling and transient pages that should never rank; repeated in every group.
        $rules = ['Allow: /', 'Disallow: /admin', 'Disallow: /preview/', 'Disallow: /tr/onizleme/'];
        $named = [];
        foreach (['search' => 'Search and answer indexing', 'fetch' => 'User-initiated fetches', 'training' => 'Model training and data collection'] as $key => $label) {
            $named[] = '# '.$label;
            array_push($named, ...array_map(fn (string $agent): string => 'User-agent: '.$agent, config('ai_crawlers.'.$key, [])));
        }
        $content = implode('
', [...$named, ...$rules, '', 'User-agent: *', ...$rules, '', 'Sitemap: '.route('sitemap')]).'
';

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }

    /** Name, colours and icons browsers and search results use for the site. */
    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => Seo::siteName(),
            'short_name' => 'tkaya',
            'description' => Seo::defaultDescription('en'),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'browser',
            'background_color' => '#09090b',
            'theme_color' => '#09090b',
            'icons' => [
                ['src' => '/favicon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml'],
                ['src' => '/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=86400'], JSON_UNESCAPED_SLASHES);
    }

    public function sitemap(Request $request): Response
    {
        $items = Sitemap::entries();
        $response = response()->view('sitemap.xml', ['items' => $items], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
        // Crawlers re-fetch sitemaps often; an unchanged one is answered with 304.
        $newest = collect($items)->pluck('lastmod')->filter()->max();
        if ($newest) {
            $response->setLastModified(Carbon::parse($newest));
            $response->isNotModified($request);
        }

        return $response;
    }
}
