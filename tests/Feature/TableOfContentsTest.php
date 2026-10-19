<?php

use App\Services\Publishing\PostContent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives headings readable, unique ids and lists only the h2 sections', function () {
    $post = caseStudy(['content' => '<p>Giriş.</p><h2>Kart bilgisi yalnızca bir istek boyunca elde</h2><p>a</p><h3>Bir alt başlık</h3><p>b</p><h2>Özet</h2><p>c</p><h2>Özet</h2><p>d</p>']);

    ['articleHtml' => $html, 'headings' => $headings] = app(PostContent::class)->prepare($post);

    expect(array_column($headings, 'id'))->toBe(['kart-bilgisi-yalnizca-bir-istek-boyunca-elde', 'ozet', 'ozet-2'])
        ->and($html)->toContain('<h2 id="kart-bilgisi-yalnizca-bir-istek-boyunca-elde">')->toContain('<h3 id="bir-alt-baslik">')->toContain('<h2 id="ozet-2">');
});

it('renders a scroll-aware contents list on case studies and long articles', function () {
    $sections = collect(range(1, 10))->map(fn (int $n): string => "<h2>Bölüm $n</h2><p>Metin $n.</p>")->implode('');
    caseStudy(['slug' => 'toc-case', 'content' => '<p>Giriş.</p>'.$sections]);
    portfolioPost(['slug' => 'toc-article', 'project_key' => 'monafy', 'content' => '<p>Giriş.</p>'.$sections]);

    $case = $this->get('/tr/vaka-incelemeleri/toc-case')->assertOk()->getContent();
    $article = $this->get('/tr/yazilar/toc-article')->assertOk()->getContent();

    expect($case)->toContain('data-toc')->toContain('<a href="#bolum-10"><span>10</span>Bölüm 10</a>')->not->toContain('<span>010</span>')
        ->and($article)->toMatch('~<aside class="brand-article-aside">.*data-toc.*href="#bolum-1"~s');
});
