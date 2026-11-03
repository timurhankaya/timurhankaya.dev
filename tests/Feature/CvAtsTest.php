<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

it('serves an ATS CV with the complete experience in document reading order', function (string $locale, string $path) {
    $content = json_decode(file_get_contents(resource_path("content/portfolio.{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
    $html = $this->get($path.'?ats=1')->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($document);
    $main = $xpath->query('//main[@id="ats-cv"]')->item(0);
    expect($main)->not->toBeNull();
    $text = $main->textContent;
    expect($text)->toContain('Timurhan Kaya', 'kayacekovic@gmail.com', 'https://linkedin.com/in/timurhan-kaya', 'Laravel', 'PHP');
    foreach ($content['experience']['items'] as $experience) {
        expect($text)->toContain($experience['role'], $experience['company'], $experience['period']);
        foreach ($experience['bullets'] as $bullet) {
            expect($text)->toContain($bullet);
        }
    }
    expect($xpath->query('.//table|.//img|.//canvas', $main)->length)->toBe(0)
        ->and($xpath->query('//h2')->length)->toBe(5);
    $positions = [];
    foreach (['ats-summary', 'ats-experience', 'ats-skills', 'ats-projects', 'ats-education'] as $id) {
        $positions[] = strpos($html, 'id="'.$id.'"');
    }
    $ordered = $positions;
    sort($ordered);
    expect($positions)->toBe($ordered)
        ->and($html)->toContain('noindex,follow')
        ->not->toContain('height: 297mm', 'overflow: hidden', 'fonts/typography.css');
})->with(['English' => ['en', '/cv'], 'Turkish' => ['tr', '/tr/ozgecmis']]);

it('keeps the normal CV as the default and offers the ATS variant', function () {
    foreach (['/cv', '/cv?ats=0', '/cv?ats=true'] as $path) {
        $this->get($path)->assertOk()->assertDontSee('id="ats-cv"', false)->assertSee('?ats=1', false);
    }
});
