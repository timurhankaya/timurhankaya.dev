<?php

use App\Support\Anchors;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives home sections readable anchors in the page language and points to the other language', function () {
    $en = $this->get('/')->assertOk()->getContent();
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect($tr)->toContain('id="hakkimda" data-anchor-alt="about"')
        ->toContain('id="birlikte-calisalim" data-anchor-alt="work-together"')
        ->toContain('/tr#hakkimda')
        ->not->toContain('id="about"');
    expect($en)->toContain('id="about" data-anchor-alt="hakkimda"');
});

it('keeps hidden projects in the content file but off every page', function () {
    $html = $this->get('/')->getContent().$this->get('/tr')->getContent();

    foreach (['Sera Ambalaj', 'Eagle Global Plastik', 'Vispera', 'Vietnamees Foodies', 'Moneo Aircraft', 'Cashflow NFT'] as $name) {
        expect($html)->not->toContain($name);
        expect(file_get_contents(resource_path('content/portfolio.tr.json')))->toContain($name);
    }
});

it('resolves an anchor id per locale', function () {
    expect(Anchors::id('about', 'tr'))->toBe('hakkimda')
        ->and(Anchors::id('about', 'en'))->toBe('about')
        ->and(Anchors::attributes('contact', 'tr'))->toBe('id="iletisim" data-anchor-alt="contact"');
});
