<?php

use App\Services\Editorial\EditorialBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('uses an explicit localized product destination in article sidebars', function () {
    $post = portfolioPost(['project_key' => 'monafy']);
    foreach (['tr' => 'https://monafy.com/tr/ozellikler/', 'en' => 'https://monafy.com/features/'] as $locale => $url) {
        $this->get($post->publicUrl($locale))->assertOk()->assertSee('href="'.$url.'"', false);
    }
});

it('renders case project and related links in the page language', function () {
    $post = caseStudy(['case_meta' => [
        'url' => 'https://monafy.com',
        'links' => [
            ['url' => 'https://monafy.com/methodology/', 'label' => ['tr' => 'Yöntem', 'en' => 'Method']],
            ['url' => 'https://monafy.com/tr/piyasa-raporu/', 'label' => ['tr' => 'Raporlar', 'en' => 'Reports']],
            ['url' => 'https://example.com/reference', 'label' => ['tr' => 'Kaynak', 'en' => 'Source']],
        ],
    ]]);
    foreach (['tr' => ['ozellikler', 'metodoloji', 'piyasa-raporu'], 'en' => ['features', 'methodology', 'market-report']] as $locale => [$product, $method, $report]) {
        $base = 'https://monafy.com/'.($locale === 'tr' ? 'tr/' : '');
        expect($post->caseMeta($locale)['url'])->toBe($base.$product.'/')
            ->and(array_column($post->caseLinks($locale), 'url'))->toBe([$base.$method.'/', $base.$report.'/', 'https://example.com/reference']);
        $this->get($post->publicUrl($locale))->assertOk()
            ->assertSee('href="'.$base.$product.'/"', false)
            ->assertSee('href="'.$base.$method.'/"', false)
            ->assertSee('href="'.$base.$report.'/"', false);
    }
});

it('rejects wrong-language and language-negotiating Monafy body links before publication', function () {
    $base = cleanStory();
    foreach (['tr', 'en'] as $locale) {
        $base[$locale]['meta_title'] = $base[$locale]['title'];
        $base[$locale]['meta_description'] = 'The product decisions behind market data, reader preferences and reliable publication checks.';
    }
    foreach ([['tr', 'https://monafy.com/'], ['tr', 'https://monafy.com/methodology/'], ['en', 'https://monafy.com/tr/piyasa-raporu/'], ['en', 'https://monafy.com/']] as [$locale, $url]) {
        $story = $base;
        $story[$locale]['content'] .= '<p><a href="'.$url.'">Read more</a></p>';
        $bundle = EditorialBundle::seal('link-language-check', $story, hash('sha256', 'link-language-check'));
        expect(fn () => app(EditorialBundle::class)->validatePublic($bundle))->toThrow(ValidationException::class, 'Monafy links must use a direct destination');
    }
    $story = $base;
    $story['tr']['content'] .= '<p><a href="https://monafy.com/tr/ozellikler/">Özellikler</a></p>';
    $story['en']['content'] .= '<p><a href="https://monafy.com/features/">Features</a></p>';
    expect(app(EditorialBundle::class)->validatePublic(EditorialBundle::seal('link-language-check', $story, hash('sha256', 'link-language-check'))))
        ->toBe($story);
});
