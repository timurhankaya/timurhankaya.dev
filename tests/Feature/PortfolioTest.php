<?php

use App\Support\Anchors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('makes projects games and the localized cv accessible from the home page', function (string $locale) {
    Cache::flush();

    $response = $this->get($locale === 'tr' ? '/tr' : '/')->assertOk();
    $response->assertDontSee('brand.');
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//main//*[@id="'.Anchors::id('portfolio', $locale).'"]//article')->length)->toBeGreaterThanOrEqual(3);
    foreach (($locale === 'tr' ? ['/tr/ozgecmis', '/tr/oyunlar/kelime-casusu', '/tr/oyunlar/vampir-koylu'] : ['/cv', '/games/imposter', '/games/vampire']) as $path) {
        expect($xpath->query('//main//a[contains(@href, "'.$path.'")]')->length)->toBeGreaterThan(0);
    }
})->with(['tr', 'en']);

it('omits removed data providers from every public portfolio presentation', function (string $locale) {
    Cache::flush();

    foreach (($locale === 'tr' ? ['/tr', '/tr/ozgecmis', '/tr/iletisim'] : ['/', '/cv', '/contact']) as $path) {
        $html = strtolower($this->get($path)->assertOk()->getContent());
        expect($html)->not->toContain('yahoo', 'fmp');
    }
})->with(['tr', 'en']);
