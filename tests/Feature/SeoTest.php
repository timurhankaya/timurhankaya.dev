<?php

use App\Models\Post;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('renders localized canonical and alternate links on the home page', function () {
    $response = $this->get('/tr');

    $response->assertOk();
    $content = $response->getContent();

    preg_match('/<link rel="canonical" href="([^"]+)">/', $content, $canonical);
    preg_match('/<link rel="alternate" hreflang="en" href="([^"]+)">/', $content, $alternateEn);
    preg_match('/<link rel="alternate" hreflang="tr" href="([^"]+)">/', $content, $alternateTr);

    expect($canonical[1] ?? null)->toEndWith('/tr');
    expect($alternateEn[1] ?? null)->toEndWith('/');
    expect($alternateTr[1] ?? null)->toEndWith('/tr');
});

it('serves robots txt with an absolute sitemap url', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toContain('/sitemap.xml');
});

it('only exposes published posts in blog routes and sitemap', function () {
    $published = Post::create([
        'title' => 'Published Post',
        'slug' => 'published-post',
        'excerpt' => 'Published excerpt',
        'content' => '<p>Published body</p>',
        'published_at' => Carbon::parse('2026-03-20 12:00:00'),
    ]);

    Post::create([
        'title' => 'Draft Post',
        'slug' => 'draft-post',
        'excerpt' => 'Draft excerpt',
        'content' => '<p>Draft body</p>',
        'published_at' => null,
    ]);

    $this->get('/writing')
        ->assertOk()
        ->assertSee('Published Post')
        ->assertDontSee('Draft Post');

    $this->get('/writing/published-post')
        ->assertOk()
        ->assertSee('Published Post');

    $this->get('/writing/draft-post')
        ->assertNotFound();

    $sitemap = $this->get('/sitemap.xml');

    $sitemap->assertOk();
    $content = $sitemap->getContent();

    expect(str_contains($content, 'writing/published-post'))->toBeTrue();
    expect(str_contains($content, 'tr/yazilar/published-post'))->toBeTrue();
    expect(str_contains($content, 'draft-post'))->toBeFalse();
});

it('renders article seo metadata on blog detail pages', function () {
    $post = Post::create([
        'title' => 'SEO Article',
        'slug' => 'seo-article',
        'cover_image' => '/images/seo-cover.png',
        'excerpt' => 'A focused article excerpt.',
        'content' => '<p>Article body</p>',
        'published_at' => Carbon::parse('2026-03-18 09:30:00'),
    ]);
    $post->forceFill([
        'updated_at' => Carbon::parse('2026-03-19 10:45:00'),
    ])->save();

    $response = $this->get('/writing/seo-article');

    $response->assertOk();
    $content = $response->getContent();

    expect(str_contains($content, 'property="og:type" content="article"'))->toBeTrue();
    preg_match('/<link rel="canonical" href="([^"]+)">/', $content, $canonical);
    expect($canonical[1] ?? null)->toEndWith('/writing/seo-article');
    expect(str_contains($content, 'property="article:published_time" content="2026-03-18T09:30:00+00:00"'))->toBeTrue();
    expect(str_contains($content, 'property="article:modified_time" content="2026-03-19T10:45:00+00:00"'))->toBeTrue();
    expect(str_contains($content, 'application/ld+json'))->toBeTrue();
});

it('publishes a sitemap with accurate lastmod, hreflang pairs and no ignored fields', function () {
    caseStudy(['slug' => 'monafy', 'title' => 'Prices vanished at the close']);
    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Cache-Control', 'max-age=3600, public')->getContent();
    expect($xml)->not->toContain('<changefreq>', '<priority>')
        ->and($xml)->toContain('hreflang="x-default"', '/tr/vaka-incelemeleri/monafy', '<lastmod>');
    $doc = simplexml_load_string($xml);
    expect($doc)->not->toBeFalse();
    foreach ($doc->url as $url) {
        expect((string) $url->loc)->not->toContain('/games/imposter/')->not->toContain('?');
        expect((string) $url->lastmod)->toMatch('/^\d{4}-\d{2}-\d{2}T/');
    }
});

it('serves a robots file that blocks private tooling and points to the absolute sitemap', function () {
    $robots = $this->get('/robots.txt')->assertOk()->getContent();
    // public/robots.txt is this route's production output (see SeoBestPracticesTest), since nginx serves it from disk.
    expect($robots)->toContain('Disallow: /admin', 'Sitemap: http')->not->toContain('Disallow: /livewire');
});

it('describes pages with linked schema.org entities that parse as json', function (string $path, array $types) {
    caseStudy(['slug' => 'monafy']);
    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $this->get($path)->getContent(), $match);
    $graph = json_decode($match[1] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
    $found = array_column($graph['@graph'], '@type');
    expect($graph['@context'])->toBe('https://schema.org')->and($found)->toContain(...$types);
    $ids = array_column($graph['@graph'], '@id');
    expect($ids)->toContain(StructuredData::personId(), StructuredData::websiteId());
    foreach ($graph['@graph'] as $node) {
        foreach ($node as $value) {
            expect($value)->not->toBe('')->not->toBe([])->not->toBeNull();
        }
    }
})->with([
    'home' => ['/', ['ProfilePage', 'Person', 'WebSite']],
    'contact' => ['/contact', ['ContactPage', 'BreadcrumbList']],
    'case study' => ['/case-studies/monafy', ['TechArticle', 'BreadcrumbList']],
    'game' => ['/games/imposter', ['VideoGame', 'BreadcrumbList']],
    'cv' => ['/tr/ozgecmis', ['ProfilePage', 'BreadcrumbList']],
]);

it('keeps game rooms and the branded 404 out of the index and shortens long meta descriptions', function () {
    $this->get('/games/imposter/ABC123')->assertOk()->assertSee('noindex,follow,noarchive', false)->assertDontSee('rel="canonical"', false);
    $this->get('/nope')->assertNotFound()->assertSee('noindex', false)->assertSee('This page isn’t here.');
    expect(mb_strlen(Seo::metaDescription(str_repeat('word ', 80))))->toBeLessThanOrEqual(160);
});
