<?php

use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('negotiates Turkish at the root and serves English without a prefix', function (string $language, string $expected) {
    $response = $this->withHeader('Accept-Language', $language)->get('/');
    if ($expected === 'tr') {
        $response->assertStatus(302)->assertRedirect('/tr')->assertHeader('Vary', 'Accept-Language, Cookie');
    } else {
        $response->assertOk()->assertSee('<html lang="en"', false);
    }
})->with([['tr-TR,tr;q=0.9,en;q=0.7', 'tr'], ['en-GB,en;q=0.9', 'en'], ['de-DE,de;q=0.9', 'en'], ['', 'en']]);

it('keeps explicit route languages accessible regardless of browser or session preference', function () {
    $this->withSession(['locale' => 'en'])->withHeader('Accept-Language', 'en-US')->get('/tr/iletisim')
        ->assertOk()->assertSee('<html lang="tr"', false)->assertSee('Bir soru, bir fikir ya da sadece tanışmak için.')->assertSessionHas('locale', 'tr');
    $this->get('/')->assertRedirect('/tr');
    $this->get('/contact')->assertOk()->assertSee('<html lang="en"', false)->assertSessionHas('locale', 'en');
});

it('redirects old and mixed language addresses preserving parameters', function () {
    $this->get('/?lang=tr')->assertStatus(301)->assertRedirect('/tr');
    $this->get('/blog?lang=en&page=2')->assertStatus(301)->assertRedirect('/writing?page=2');
    $this->get('/games/imposter/ABC123?lang=tr')->assertStatus(301)->assertRedirect('/tr/oyunlar/kelime-casusu/ABC123');
    $this->get('/tr/contact?lang=en')->assertStatus(301)->assertRedirect('/tr/iletisim');
    $this->get('/en/contact')->assertStatus(301)->assertRedirect('/contact');
    $this->get('/en')->assertStatus(301)->assertRedirect(url('/').'/');
    $this->get('/tr')->assertOk();
    $this->get('/?lang=en')->assertStatus(301)->assertRedirect('/');
    $this->get('/')->assertOk()->assertSee('<html lang="en"', false);
});

it('translates all path segments while preserving query fragments and dynamic values', function () {
    expect(Seo::localizedUrl('https://example.com/tr/yazilar?page=2&lang=tr#notes', 'en'))->toBe('https://example.com/writing?page=2#notes');
    expect(Seo::localizedUrl('https://example.com/games/vampire/ABC123', 'tr'))->toBe('https://example.com/tr/oyunlar/vampir-koylu/ABC123');
    expect(Seo::localizedUrl('https://example.com/tr/vaka-incelemeleri/monafy', 'en'))->toBe('https://example.com/case-studies/monafy');
    expect(Seo::localizedUrl('https://example.com/', 'tr'))->toBe('https://example.com/tr');
});

it('does not redirect unknown language prefixes or unknown pages', function () {
    $this->get('/de/projects')->assertNotFound();
    $this->get('/tr/unknown')->assertNotFound();
});

it('renders translated games rooms and cv paths without language queries', function () {
    foreach (['/games', '/games/imposter', '/games/vampire', '/games/imposter/ABC123', '/games/vampire/ABC123', '/cv', '/tr/oyunlar', '/tr/oyunlar/kelime-casusu', '/tr/oyunlar/vampir-koylu', '/tr/oyunlar/kelime-casusu/ABC123', '/tr/oyunlar/vampir-koylu/ABC123', '/tr/ozgecmis'] as $path) {
        $this->get($path)->assertOk()->assertDontSee('/en/', false);
    }
    $this->get('/tr/ozgecmis')->assertSee('--accent: #ff7a1a;', false);
});

it('keeps the html specimen local and out of search indexes', function () {
    $this->app->instance('env', 'local');
    $this->get('/tr/onizleme/icerik-ornekleri')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<blockquote>', false)->assertSee('<pre>', false)->assertSee('<table>', false)->assertSee('<figcaption>', false);
    $this->app->instance('env', 'production');
    $this->get('/tr/onizleme/icerik-ornekleri')->assertNotFound();
});

it('retains a game tabs explicit language across livewire requests without replacing another tabs preference', function (string $game, string $slug) {
    $response = $this->withSession(['games.identity' => ['name' => 'Preview tester', 'color' => 'orange', 'emoji' => '🎭']])
        ->get('/tr/oyunlar/'.$slug)->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $snapshot = null;
    foreach ($document->getElementsByTagName('*') as $element) {
        if (! $element->hasAttribute('wire:snapshot')) {
            continue;
        }
        $candidate = $element->getAttribute('wire:snapshot');
        if (data_get(json_decode($candidate, true), 'memo.name') === 'games.'.$game.'.index') {
            $snapshot = $candidate;
            break;
        }
    }
    expect($snapshot)->toBeString();
    $this->get('/contact')->assertOk()->assertSessionHas('locale', 'en');
    $update = $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), ['components' => [[
        'snapshot' => $snapshot, 'updates' => (object) [], 'calls' => [['path' => '', 'method' => 'createRoom', 'params' => []]],
    ]]])->assertOk()->assertSessionHas('locale', 'en');
    expect($update->json('components.0.effects.redirect'))->toStartWith(url('/tr/oyunlar/'.$slug).'/');
})->with([['imposter', 'kelime-casusu'], ['vampire', 'vampir-koylu']]);
