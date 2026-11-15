<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks the explicitly retired story addresses as gone in their original language', function (string $path, string $locale) {
    $html = $this->withHeader('Accept-Language', $locale === 'tr' ? 'en' : 'tr')
        ->get($path.'?utm_source=old-link')->assertStatus(410)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->getContent();

    expect($html)->toContain('<html lang="'.$locale.'"', '>410<', 'noindex')
        ->not->toContain('rel="canonical"', 'brand.error_410');
    $this->head($path)->assertStatus(410);
})->with([
    ['/tr/yazilar/kafka-ile-veri-akisi', 'tr'],
    ['/case-studies/designing-financial-metrics-people-can-understand', 'en'],
    ['/tr/vaka-incelemeleri/ai-piyasa-raporlarinda-veriyi-ve-yorumu-ayirmak', 'tr'],
    ['/writing/separating-prompt-examples-from-current-data', 'en'],
    ['/case-studies/grounding-ai-market-reports-in-source-data', 'en'],
    ['/tr/vaka-incelemeleri/finansal-gostergeleri-anlasilir-kilan-urun-kararlari', 'tr'],
    ['/writing/pgbouncer-transaction-pooling-session-locks', 'en'],
    ['/tr/yazilar/gecen-testler-gercekte-neyi-dogrular', 'tr'],
]);

it('keeps unknown addresses as not found and current stories available', function () {
    $this->get('/writing/not-a-retired-story')->assertNotFound();
    $this->get('/tr/yazilar/kafka-ile-veri-akisi-extra')->assertNotFound();

    $post = portfolioPost(['slug' => 'current-kafka-story']);
    $this->get($post->publicUrl('en'))->assertOk();
});
