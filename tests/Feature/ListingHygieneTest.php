<?php

use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function headLinks(string $html): array
{
    preg_match_all('~<link rel="(?:canonical|alternate)"[^>]*href="([^"]+)"~', $html, $links);

    return $links[1];
}

it('keeps junk query parameters out of listing canonicals and hreflang links', function () {
    portfolioPost();
    caseStudy();
    foreach (['/writing?utm_source=x', '/writing?page=1', '/writing?type=nope', '/case-studies?project=bogus', '/tr/yazilar?utm_source=x', '/tr/vaka-incelemeleri?fbclid=1'] as $url) {
        $links = headLinks($this->get($url)->assertOk()->getContent());
        expect($links)->not->toBeEmpty();
        foreach ($links as $link) {
            expect(str_contains($link, '?'))->toBeFalse();
        }
    }
});

it('returns 404 for a listing page past the last one and points rel=prev of page 2 at the base listing', function () {
    foreach (range(1, 11) as $n) {
        portfolioPost(['title' => "Article $n", 'slug' => "article-$n", 'published_at' => now()->subDays($n)]);
    }
    $this->get('/writing?page=3')->assertNotFound();
    $this->get('/writing?page=2')->assertOk()->assertSee('<link rel="prev" href="'.url('/writing').'">', false);
    $this->get('/tr/yazilar?page=2')->assertOk()->assertSee('<link rel="prev" href="'.url('/tr/yazilar').'">', false);
});

it('gives filtered and paginated listings their own title, description and structured-data url', function () {
    foreach (range(1, 11) as $n) {
        portfolioPost(['title' => "Article $n", 'slug' => "article-$n", 'published_at' => now()->subDays($n)]);
    }
    caseStudy(['slug' => 'monafy-incident']);
    $base = pageMeta($this->get('/case-studies')->getContent());
    $filtered = pageMeta($this->get('/case-studies?project=monafy')->getContent());
    expect($filtered['title'])->not->toBe($base['title'])->toContain('Monafy')
        ->and($filtered['description'])->not->toBe($base['description'])->toContain('Monafy');
    $page2 = pageMeta($this->get('/writing?page=2')->getContent());
    expect($page2['title'])->toContain('page 2')->and($page2['description'])->not->toBe(pageMeta($this->get('/writing')->getContent())['description']);
    expect(pageMeta($this->get('/tr/yazilar?page=2')->getContent())['title'])->toContain('sayfa 2');

    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $this->get('/case-studies?project=monafy')->getContent(), $ld);
    $page = collect(json_decode($ld[1], true)['@graph'])->firstWhere('@type', 'CollectionPage');
    expect($page['url'])->toEndWith('/case-studies?project=monafy')->and($page['@id'])->toEndWith('/case-studies?project=monafy#webpage');
});

it('shortens descriptions and titles without breaking multibyte characters', function () {
    $description = Seo::metaDescription(str_repeat('Ô', 400));
    expect(mb_check_encoding($description, 'UTF-8'))->toBeTrue()->and(json_encode($description))->not->toBeFalse()->and(mb_strlen($description))->toBeLessThanOrEqual(160);
    $long = caseStudy(['slug' => 'long-title', 'title' => 'Why did the nightly synchronisation job quietly stop producing the customer report for more than one day']);
    expect(mb_strlen($long->metaTitle('en')))->toBeLessThanOrEqual(70)->and($long->metaTitle('en'))->toEndWith('…');
    expect(caseStudy(['slug' => 'short-title', 'title' => 'Short title'])->metaTitle('en'))->toBe('Short title');
});

it('keeps the Case study label in Turkish and a mobile font minimum after the series styles', function () {
    expect(__('brand.all_cases', ['count' => 5], 'tr'))->toContain('case study')->not->toContain('vaka')
        ->and(__('brand.series_label', ['project' => 'Monafy'], 'tr'))->toContain('case study')->not->toContain('vaka');
    $css = file_get_contents(resource_path('css/brand.css'));
    expect(strrpos($css, 'Mobile minimums for the series block'))->toBeGreaterThan(strpos($css, 'Several case studies per project'));
});

it('links the Case study section from the main navigation in both languages', function () {
    foreach ([['/', '/case-studies'], ['/tr', '/tr/vaka-incelemeleri']] as [$page, $target]) {
        preg_match('~<nav class="brand-nav-links".*?</nav>~s', $this->get($page)->assertOk()->getContent(), $nav);
        expect($nav[0])->toContain($target.'"');
    }
});
