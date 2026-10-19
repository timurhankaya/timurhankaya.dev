<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('uses the Turkish meta values on the primary locale and falls back to title and excerpt', function () {
    $post = caseStudy(['title' => 'Fiyatlar düşüyordu', 'excerpt' => 'Kısa cevap.', 'meta_title' => 'SEO başlığı', 'meta_description' => 'SEO açıklaması.']);
    expect($post->metaTitle('tr'))->toBe('SEO başlığı')->and($post->metaDescription('tr'))->toBe('SEO açıklaması.');
    $post->update(['meta_title' => null, 'meta_description' => '']);
    expect($post->fresh()->metaTitle('tr'))->toBe('Fiyatlar düşüyordu')->and($post->fresh()->metaDescription('tr'))->toBe('Kısa cevap.');
});

it('never shows Turkish meta on an English page whose own meta is blank', function () {
    $post = caseStudy([
        'title' => 'Fiyatlar düşüyordu', 'excerpt' => 'Kısa cevap.', 'meta_title' => 'SEO başlığı', 'meta_description' => 'SEO açıklaması.',
        'translations' => ['en' => ['title' => 'Prices were dropping', 'excerpt' => 'Short answer.', 'content' => '<p>Detail.</p>', 'meta_title' => '', 'meta_description' => '']],
    ]);
    expect($post->metaTitle('en'))->toBe('Prices were dropping')->and($post->metaDescription('en'))->toBe('Short answer.');
    $post->update(['translations' => ['en' => [...$post->translations['en'], 'meta_title' => 'English SEO title', 'meta_description' => 'English SEO description.']]]);
    expect($post->fresh()->metaTitle('en'))->toBe('English SEO title')->and($post->fresh()->metaDescription('en'))->toBe('English SEO description.');
});

it('shortens a long excerpt used as the description at a word boundary', function () {
    $post = caseStudy(['excerpt' => str_repeat('measured ', 40)]);
    $description = $post->metaDescription('en');
    expect(mb_strlen($description))->toBeLessThanOrEqual(160)->and($description)->toEndWith('…')->and($description)->not->toContain('measure…');
});

it('rejects meta fields longer than the search result limits in either locale', function () {
    $post = caseStudy();
    expect(fn () => $post->update(['meta_title' => str_repeat('a', 71)]))->toThrow(ValidationException::class);
    expect(fn () => $post->update(['meta_description' => str_repeat('a', 161)]))->toThrow(ValidationException::class);
    expect(fn () => $post->update(['translations' => ['en' => ['meta_description' => str_repeat('a', 161)]]]))->toThrow(ValidationException::class);
    expect($post->fresh()->meta_title)->toBeNull();
});

it('renders the meta fields in the title and description of case study and article pages', function () {
    caseStudy(['slug' => 'meta-case', 'meta_title' => 'Fiyatlar neden düştü', 'meta_description' => 'Açıklama metni.']);
    portfolioPost(['slug' => 'meta-article', 'meta_title' => 'Neden worktree', 'meta_description' => 'Makale açıklaması.']);
    $this->get('/tr/vaka-incelemeleri/meta-case')->assertOk()->assertSee('<title>Fiyatlar neden düştü — Timurhan Kaya</title>', false)->assertSee('<meta name="description" content="Açıklama metni.">', false);
    $this->get('/tr/yazilar/meta-article')->assertOk()->assertSee('<title>Neden worktree — Timurhan Kaya</title>', false)->assertSee('<meta name="description" content="Makale açıklaması.">', false);
});
