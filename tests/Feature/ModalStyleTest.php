<?php

use App\Support\Anchors;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function paletteMarkup(string $html): string
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    $node = (new DOMXPath($dom))->query('//*[@id="command-palette"]')->item(0);

    return $node ? $dom->saveHTML($node) : '';
}

function cssRule(string $file, string $selector): string
{
    preg_match('/^'.preg_quote($selector, '/').'\s*\{([^}]*)\}/m', file_get_contents(resource_path("css/{$file}")), $match);

    return $match[1] ?? '';
}

it('renders the command palette as a brand modal with real paths', function () {
    $palette = paletteMarkup($this->get('/tr/iletisim')->assertOk()->getContent());

    expect($palette)->toContain('brand-palette')
        ->toContain('brand-palette-panel')
        ->toContain('brand-palette-item')
        ->not->toContain('cyan')
        ->not->toContain('rounded-3xl')
        ->not->toContain('zinc-')
        ->toContain('>/tr/oyunlar<')
        ->not->toContain('>/games<');
});

it('offers the same destinations as the navigation, plus every published story', function () {
    $article = portfolioPost(['title' => 'Fiyat akışındaki darboğaz', 'slug' => 'fiyat-akisi', 'published_at' => now()->subDay()]);
    $case = caseStudy(['title' => 'Mükerrer faturayı durdurmak', 'slug' => 'mukerrer-fatura', 'published_at' => now()->subDay()]);
    portfolioPost(['title' => 'Taslak yazı', 'slug' => 'taslak', 'published_at' => null]);

    $palette = paletteMarkup($this->get('/tr/iletisim')->assertOk()->getContent());
    app()->setLocale('tr');

    foreach ([Seo::route('home'), Anchors::home('about'), Seo::route('games.index'), Seo::route('blog.index'), Seo::route('case-studies.index'), Anchors::home('work-together'), Seo::route('contact'), Seo::route('cv')] as $url) {
        expect($palette)->toContain('href="'.$url.'"');
    }
    expect($palette)->toContain('Fiyat akışındaki darboğaz')->toContain('href="'.$article->publicUrl('tr').'"')
        ->toContain('Mükerrer faturayı durdurmak')->toContain('href="'.$case->publicUrl('tr').'"')
        ->not->toContain('Taslak yazı')
        ->toContain('Sonuç bulunamadı');

    // Articles sit under Blog, case studies under Case study.
    $at = fn (string $url) => strpos($palette, 'href="'.$url.'"');
    expect($at(Seo::route('blog.index')))->toBeLessThan($at($article->publicUrl('tr')))
        ->and($at($article->publicUrl('tr')))->toBeLessThan($at(Seo::route('case-studies.index')))
        ->and($at(Seo::route('case-studies.index')))->toBeLessThan($at($case->publicUrl('tr')));
});

it('keeps modal surfaces square like the rest of the site', function (string $file, string $selector) {
    expect(cssRule($file, $selector))->not->toBe('')->not->toContain('border-radius');
})->with([
    ['brand.css', '.brand-project-dialog'],
    ['brand.css', '.brand-dialog-close'],
    ['brand.css', '.brand-palette-panel'],
    ['brand.css', '.brand-palette-item'],
    ['games.css', '.game-dialog'],
    ['games.css', '.game-dialog-close'],
    ['games.css', '.vk-notice'],
    ['games.css', '.game-qr-pop'],
    ['games.css', '.game-qr-tile'],
    ['games.css', '.game-character'],
]);

it('gives every modal the same softer scrim', function (string $file, string $selector) {
    expect(cssRule($file, $selector))->toContain('var(--modal-scrim)');
})->with([
    ['brand.css', '.brand-project-dialog::backdrop'],
    ['brand.css', '.brand-palette-scrim'],
    ['games.css', '.game-dialog-scrim'],
    ['games.css', '.vk-notice-scrim'],
]);
