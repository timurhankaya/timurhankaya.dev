<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pageMeta(string $html): array
{
    $decode = fn (?string $value): string => html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5);
    preg_match('~<title>([^<]*)</title>~', $html, $title);
    preg_match('~<meta name="description" content="([^"]*)"~', $html, $description);
    preg_match('~<link rel="canonical" href="([^"]+)"~', $html, $canonical);
    preg_match_all('~<link rel="alternate" hreflang="([^"]+)"~', $html, $hreflang);

    return [
        'title' => $decode($title[1] ?? ''), 'description' => $decode($description[1] ?? ''), 'canonical' => $canonical[1] ?? null,
        'hreflang' => $hreflang[1], 'h1' => preg_match_all('~<h1\b~', $html),
    ];
}

it('gives every public page one h1, a short title, a description within limits, a canonical and hreflang pairs', function () {
    caseStudy(['slug' => 'meta-case', 'excerpt' => 'A short answer that is long enough to work as a search result description for this page.']);
    portfolioPost(['slug' => 'meta-article', 'excerpt' => 'A short answer that is long enough to work as a search result description for this page.']);
    $paths = ['/', '/tr', '/contact', '/tr/iletisim', '/cv', '/tr/ozgecmis', '/games', '/tr/oyunlar',
        '/games/imposter', '/tr/oyunlar/kelime-casusu', '/games/vampire', '/tr/oyunlar/vampir-koylu', '/writing', '/tr/yazilar',
        '/writing/meta-article', '/tr/yazilar/meta-article', '/case-studies', '/tr/vaka-incelemeleri', '/case-studies/meta-case', '/tr/vaka-incelemeleri/meta-case'];
    $problems = [];
    foreach ($paths as $path) {
        $meta = pageMeta($this->get($path)->assertOk()->getContent());
        $meta['h1'] === 1 || $problems[] = "$path: {$meta['h1']} h1";
        mb_strlen($meta['title']) <= 70 || $problems[] = "$path: title too long ({$meta['title']})";
        (mb_strlen($meta['description']) >= 40 && mb_strlen($meta['description']) <= 160) || $problems[] = "$path: description is ".mb_strlen($meta['description']).' characters';
        $meta['canonical'] !== null || $problems[] = "$path: no canonical";
        array_diff(['en', 'tr', 'x-default'], $meta['hreflang']) === [] || $problems[] = "$path: hreflang set incomplete";
    }
    expect($problems)->toBe([]);
});

it('keeps paginated and project-filtered listings self-canonical', function () {
    foreach (range(1, 11) as $n) {
        portfolioPost(['title' => "Article $n", 'slug' => "article-$n", 'published_at' => now()->subDays($n)]);
    }
    caseStudy(['slug' => 'filtered-case']);
    $page2 = $this->get('/writing?page=2')->assertOk()->getContent();
    expect(pageMeta($page2)['canonical'])->toEndWith('/writing?page=2')->and($page2)->toContain('rel="prev"');
    $filtered = $this->get('/case-studies?project=monafy')->assertOk()->getContent();
    expect(pageMeta($filtered)['canonical'])->toEndWith('/case-studies?project=monafy');
});

it('adds the Search Console verification tag only when configured', function () {
    $this->get('/')->assertOk()->assertDontSee('google-site-verification');
    config(['services.search_console.verification' => 'abc123token']);
    $this->get('/')->assertOk()->assertSee('<meta name="google-site-verification" content="abc123token">', false);
});

it('shows a visible breadcrumb that matches the structured data trail', function () {
    caseStudy(['slug' => 'crumb-case']);
    portfolioPost(['slug' => 'crumb-article']);
    $paths = ['/case-studies/crumb-case', '/writing/crumb-article', '/writing', '/case-studies', '/contact', '/games', '/games/imposter', '/tr/vaka-incelemeleri/crumb-case'];
    foreach ($paths as $path) {
        $html = $this->get($path)->assertOk()->getContent();
        preg_match('~<nav class="brand-breadcrumbs"[^>]*>(.*?)</nav>~s', $html, $nav);
        expect($nav)->not->toBeEmpty("$path has a breadcrumb nav");
        preg_match_all('~<li>(?:<a href="[^"]*">)?(?:<span aria-current="page">)?([^<]+)~', $nav[1], $visible);
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $ld);
        $graph = json_decode($ld[1], true)['@graph'];
        $crumbs = collect($graph)->firstWhere('@type', 'BreadcrumbList');
        $schemaNames = array_map(fn ($item) => html_entity_decode($item['name'], ENT_QUOTES | ENT_HTML5), $crumbs['itemListElement']);
        expect(array_map(fn ($name) => html_entity_decode($name, ENT_QUOTES | ENT_HTML5), $visible[1]))->toBe($schemaNames, $path);
        expect($nav[1])->toContain('aria-current="page"');
    }
});
