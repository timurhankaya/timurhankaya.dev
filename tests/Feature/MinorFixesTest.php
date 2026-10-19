<?php

use App\Support\StoryStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('flags Turkish from-to titles but not ordinary questions', function () {
    $story = fn (string $title): array => cleanStory(['tr' => ['title' => $title]]);
    expect(StoryStyle::violations($story('Anahtar kelimelerden araç döngüsüne')))->toContain('tr: title uses a from-to pattern');
    expect(StoryStyle::violations($story('Fiyatlar ABD kapanışında neden düşüyordu?')))->not->toContain('tr: title uses a from-to pattern');
});

it('shows the publish date once on an article page', function () {
    portfolioPost(['slug' => 'dated-article']);
    expect(substr_count($this->get('/writing/dated-article')->assertOk()->getContent(), '<time '))->toBe(1);
});

it('labels the series list and the previous/next pager as two different navigation landmarks', function () {
    caseStudy(['slug' => 'series-one', 'sort_order' => 10]);
    caseStudy(['slug' => 'series-two', 'sort_order' => 20, 'title' => 'Second']);
    $html = $this->get('/case-studies/series-one')->assertOk()->getContent();
    preg_match('~<nav class="brand-series" aria-label="([^"]+)"~', $html, $series);
    preg_match('~<nav class="brand-case-pager" aria-label="([^"]+)"~', $html, $pager);
    expect($series[1] ?? null)->not->toBeNull()->and($pager[1] ?? null)->not->toBeNull()->and($series[1])->not->toBe($pager[1]);
});

it('names the section switcher as sections, not as a table of contents', function () {
    expect(__('brand.index', [], 'tr'))->toBe('Bölümler')->and(__('brand.index', [], 'en'))->toBe('Sections');
});
