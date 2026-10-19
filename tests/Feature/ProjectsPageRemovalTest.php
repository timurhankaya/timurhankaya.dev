<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('no longer serves a projects page in any language', function (string $path) {
    $this->get($path)->assertNotFound();
})->with(['/projects', '/tr/projeler', '/tr/projects', '/en/projects']);

it('drops every link to the projects page', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();

    expect($html)->not->toMatch('~href="[^"]*/(projects|projeler)["#?]~')
        ->not->toContain('brand-all-work');
})->with(['/', '/tr', '/tr/iletisim', '/writing', '/tr/oyunlar']);

it('leaves Work out of the navigation and the command palette', function () {
    $html = $this->get('/tr')->assertOk()->getContent();
    preg_match('~<nav class="brand-nav-links".*?</nav>~s', $html, $nav);

    expect($nav[0] ?? '')->toContain('Hakkımda')->not->toContain('Çalışmalar')
        ->and($html)->not->toContain('data-command-label="Çalışmalar"');
});

it('moves the brand logos to the home page, right below the featured work', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();

    expect($html)->toContain('class="brand-logos"')->toContain('/images/logos/monafy.svg')
        ->and(strpos($html, 'class="brand-logos"'))->toBeGreaterThan(strpos($html, 'brand-project-grid'))
        ->and(strpos($html, 'class="brand-logos"'))->toBeLessThan(strpos($html, 'brand-about'));
})->with(['/', '/tr']);

it('keeps the projects page out of the sitemap and llms.txt', function () {
    $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
    $llms = $this->get('/llms.txt')->getContent().$this->get('/tr/llms.txt')->getContent();

    expect($sitemap)->not->toContain('/projects<')->not->toContain('/projeler<')
        ->and($llms)->not->toContain('/projects')->not->toContain('/projeler')
        ->toContain('#portfolio)')->toContain('/tr#calismalar)');
});

it('keeps case study links off the home project cards', function () {
    $story = caseStudy(['slug' => 'card-story']);
    $html = $this->get('/')->assertOk()->getContent();
    $grid = Str::between($html, '<div class="brand-project-grid">', '<section class="brand-logos"');

    expect($grid)->toContain('Monafy')->not->toContain('brand-case-series')
        ->not->toContain($story->publicUrl('en'))->not->toContain('/case-studies');
});

it('lines up the card rules whatever the description length', function () {
    $css = file_get_contents(resource_path('css/brand.css'));

    expect($css)->toMatch('/^\.brand-project \{[^}]*display: flex;[^}]*flex-direction: column;/m')
        ->toMatch('/^\.brand-project-description \{[^}]*flex-grow: 1;/m');
});

it('spaces the logo strip like the gap before the next section', function () {
    $css = file_get_contents(resource_path('css/brand.css'));

    expect($css)->toMatch('/^\.brand-logos \{[^}]*margin-top: var\(--section-space\)/m')
        ->toMatch('/^\.brand-section \{[^}]*padding-top: var\(--section-space\)/m')
        ->toMatch('/^\.brand-site \{[^}]*--section-space: 100px/m')
        ->not->toContain('.brand-section { padding-top: 65px; }');
});
