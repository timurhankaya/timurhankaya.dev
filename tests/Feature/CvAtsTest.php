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

it('keeps every CV version accessible but out of search indexes', function (string $path) {
    $this->get($path)->assertOk()->assertSee('<meta name="robots" content="noindex,follow">', false);
})->with(['/cv', '/tr/ozgecmis', '/cv?ats=1', '/tr/ozgecmis?ats=1']);

it('leaves CV addresses out of search submissions without blocking crawlers from reading noindex', function () {
    $sitemap = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
    $addresses = array_map('strval', $sitemap->xpath('//*[local-name()="loc"]'));

    expect($addresses)->not->toContain(url('/cv'), url('/tr/ozgecmis'), url('/cv?ats=1'), url('/tr/ozgecmis?ats=1'))
        ->toContain(url('/writing'), url('/tr/yazilar'));
    expect($this->get('/robots.txt')->assertOk()->getContent())->not->toMatch('~Disallow:\\s*/(?:cv|tr/ozgecmis)(?:[/?\\s]|$)~');
});
