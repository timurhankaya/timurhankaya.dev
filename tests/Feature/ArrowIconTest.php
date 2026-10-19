<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('draws every arrow on the site pages as one joined svg instead of a font glyph', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();

    expect($html)->toContain('class="brand-arrow"')
        ->and(preg_match('/[↗↘→←↓↑]/u', strip_tags($html)))->toBe(0);
})->with(['/', '/tr', '/contact', '/writing', '/case-studies', '/games']);

it('renders the arrow as a single path so head and shaft meet', function () {
    $svg = $this->blade('<x-arrow />')->__toString();

    expect($svg)->toContain('<svg')->toContain('aria-hidden="true"')
        ->and(substr_count($svg, '<path'))->toBe(1)
        ->and($svg)->toContain('data-dir="ne"');
    expect($this->blade('<x-arrow dir="s" />')->__toString())->toContain('data-dir="s"');
});
