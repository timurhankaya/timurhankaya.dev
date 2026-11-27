<?php

use App\Services\Portfolio\ProjectCatalog;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{section: DOMElement, xpath: DOMXPath, sets: list<DOMElement>} */
function logoStrip(string $html): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8">'.$html);
    $xpath = new DOMXPath($document);
    $section = $xpath->query('//section[@class="brand-logos"]')->item(0);
    expect($section)->not->toBeNull();

    return ['section' => $section, 'xpath' => $xpath, 'sets' => iterator_to_array($xpath->query('.//ul', $section))];
}

/** @return list<array{name: string, href: ?string, target: ?string, rel: ?string, tabindex: ?string}> */
function logoTiles(DOMXPath $xpath, DOMElement $set): array
{
    $attribute = fn (DOMElement $node, string $name): ?string => $node->hasAttribute($name) ? $node->getAttribute($name) : null;

    return array_map(function (DOMElement $item) use ($xpath, $attribute): array {
        $tile = $xpath->query('./*[contains(@class, "brand-logo-tile")]', $item)->item(0);

        return [
            'name' => $xpath->query('.//img', $tile)->item(0)->getAttribute('alt'),
            'href' => $attribute($tile, 'href'),
            'target' => $attribute($tile, 'target'),
            'rel' => $attribute($tile, 'rel'),
            'tabindex' => $attribute($tile, 'tabindex'),
        ];
    }, iterator_to_array($xpath->query('./li', $set)));
}

it('titles the logo strip in plain words', function (string $path, string $title) {
    ['section' => $section, 'xpath' => $xpath] = logoStrip($this->get($path)->assertOk()->getContent());
    $heading = $xpath->query('.//h3', $section)->item(0);
    $text = trim(preg_replace('~\s+~u', ' ', $xpath->query('./span[not(@aria-hidden)]', $heading)->item(0)?->textContent ?? ''));

    expect($section->getAttribute('aria-labelledby'))->toBe($heading->getAttribute('id'))->not->toBeEmpty()
        ->and($heading->getAttribute('class'))->toContain('brand-label')
        ->and($text)->toBe($title)
        // The strip belongs to section 01, so it carries no number of its own.
        ->and($heading->textContent)->not->toMatch('/\d/');
})->with([
    ['/', 'Companies and products'],
    ['/tr', 'Şirketler ve ürünler'],
]);

it('keeps the logo order, names and destinations', function () {
    $this->seed(StorySeeder::class);
    ['xpath' => $xpath, 'sets' => $sets] = logoStrip($this->get('/')->assertOk()->getContent());
    $tiles = logoTiles($xpath, $sets[0]);

    expect(array_column($tiles, 'name'))->toBe(['Monafy', 'ADEM', 'Moneo', 'Sistem Global', 'Smart Global', 'eDanışman', 'Basefy', 'Toyka'])
        ->and(array_column($tiles, 'href'))->toBe([
            'https://monafy.com',
            app(ProjectCatalog::class)->caseStudyUrl('adem', 'en'),
            'https://moneo.com.tr',
            'https://www.sistemglobal.com.tr',
            'https://smartglobalval.co.uk',
            'https://www.edanisman.com/',
            'https://basefy.com',
            'https://toyka.com.tr',
        ])
        ->and($tiles[1]['href'])->not->toBeNull();

    foreach ($tiles as $tile) {
        $external = $tile['name'] !== 'ADEM';
        expect([$tile['target'], $tile['rel']])->toBe($external ? ['_blank', 'noopener'] : [null, null])
            ->and($tile['tabindex'])->toBeNull();
    }
});

it('prints a second copy for the loop that is hidden from assistive tech and the tab order but still clickable', function () {
    $this->seed(StorySeeder::class);
    ['xpath' => $xpath, 'sets' => $sets] = logoStrip($this->get('/')->assertOk()->getContent());

    expect($sets)->toHaveCount(2)
        ->and($sets[0]->hasAttribute('aria-hidden'))->toBeFalse()
        ->and($sets[1]->getAttribute('aria-hidden'))->toBe('true')
        // `inert` would switch off hover and clicks on whichever half of the loop is on screen.
        ->and($sets[1]->hasAttribute('inert'))->toBeFalse();

    $originals = logoTiles($xpath, $sets[0]);
    $copies = logoTiles($xpath, $sets[1]);
    foreach (['name', 'href', 'target', 'rel'] as $key) {
        expect(array_column($copies, $key))->toBe(array_column($originals, $key));
    }
    expect(array_column($copies, 'tabindex'))->toBe(array_fill(0, 8, '-1'));
});

it('sizes every logo on its own and loads it before it slides into view', function () {
    ['section' => $section, 'xpath' => $xpath] = logoStrip($this->get('/')->assertOk()->getContent());
    $images = iterator_to_array($xpath->query('.//img', $section));

    expect($images)->toHaveCount(16);
    foreach ($images as $image) {
        // The drawn width drives the CSS; the height attribute follows the file's own ratio.
        expect($image->getAttribute('style'))->toBe('--logo-width: '.$image->getAttribute('width'))
            ->and($image->getAttribute('loading'))->not->toBe('lazy');
        [$width, $height] = logoFileSize(basename($image->getAttribute('src')));
        expect(abs($image->getAttribute('width') / $image->getAttribute('height') - $width / $height) / ($width / $height))->toBeLessThan(0.03);
    }
});

/** @return array{0: float, 1: float} */
function logoFileSize(string $file): array
{
    $path = public_path('images/logos/'.$file);
    if (! str_ends_with($file, '.svg')) {
        return array_slice(getimagesize($path), 0, 2);
    }
    preg_match('~viewBox="[\d.\-]+ [\d.\-]+ ([\d.]+) ([\d.]+)"~', file_get_contents($path), $box);

    return [(float) $box[1], (float) $box[2]];
}

it('only moves for people who have not asked for reduced motion', function () {
    $css = file_get_contents(resource_path('css/brand.css'));
    preg_match('~@media \(prefers-reduced-motion: no-preference\) \{\n(?<rules>(?:    .*\n)+)\}~', $css, $motion);
    $moving = $motion['rules'] ?? '';
    $still = str_replace($motion[0] ?? '', '', $css);

    // Without the motion query the logos are a wrapping grid and the copy is not drawn.
    expect($still)->toMatch('/^\.brand-logos ul \{[^}]*display: grid;[^}]*grid-template-columns: repeat\(4,/m')
        ->toMatch('/^\.brand-logos ul\[aria-hidden="true"\] \{ display: none; \}/m')
        ->not->toContain('animation: brand-logo-marquee')
        ->and($moving)->toContain('animation: brand-logo-marquee')
        ->toContain('mask-image: linear-gradient(')
        ->toMatch('/\.brand-logos-viewport:hover \.brand-logos-track \{ animation-play-state: paused; \}/')
        // Keyboard focus rests the strip at its start so the focused logo can be scrolled into view.
        ->toMatch('/\.brand-logos-viewport:has\(:focus-visible\) \{[^}]*overflow-x: auto;[^}]*mask-image: none;/')
        ->toMatch('/\.brand-logos-viewport:has\(:focus-visible\) \.brand-logos-track \{ animation: none; \}/')
        // Only the focused logo is a snap point: Chrome leaves a half-visible focused link where it is otherwise.
        ->toMatch('/\.brand-logos-viewport:has\(:focus-visible\) \{[^}]*scroll-snap-type: x mandatory;/')
        ->toMatch('/\.brand-logos li:has\(:focus-visible\) \{ scroll-snap-align: center; \}/')
        ->not->toContain(':focus-within');
});
