<?php

use App\Services\Portfolio\PortfolioProjects;
use App\Support\StoryStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the project name in the page language when an English name is given', function () {
    $post = caseStudy(['slug' => 'anonymous-story', 'project_key' => 'e-commerce', 'case_meta' => ['project_name' => 'E-ticaret altyapısı', 'project_name_en' => 'E-commerce platform', 'context' => 'client', 'period' => '2021']]);

    expect($post->projectName('tr'))->toBe('E-ticaret altyapısı')->and($post->projectName('en'))->toBe('E-commerce platform');
    $this->get('/case-studies/anonymous-story')->assertOk()->assertSee('E-commerce platform')->assertDontSee('E-ticaret altyapısı');
    $this->get('/tr/vaka-incelemeleri/anonymous-story')->assertOk()->assertSee('E-ticaret altyapısı');
});

it('renders code blocks as a highlighted, numbered, copyable component', function () {
    caseStudy(['slug' => 'code-story', 'content' => '<p>Kod.</p><pre><code class="language-php">$total = $price * $qty; // net
echo "&lt;script&gt;";</code></pre>']);

    $html = $this->get('/tr/vaka-incelemeleri/code-story')->assertOk()->getContent();

    expect($html)->toContain('<figure class="brand-code" data-lang="php">')
        ->toContain('<span class="hl-variable">$total</span>')
        ->toContain('<span class="hl-gutter">2</span>')
        ->toContain('data-copy-code')
        ->toContain('&lt;script&gt;')
        ->not->toContain('echo "<script>')
        ->not->toContain('<script>";');
});

it('asks every code block in a story to name its language', function () {
    $story = cleanStory(['en' => ['content' => storyParagraphs(450, 'One evening.').'<pre><code>$x = 1;</code></pre>']]);

    expect(StoryStyle::violations($story))->toContain('en: code block without a supported language class');
    $story['en']['content'] = str_replace('<pre><code>', '<pre><code class="language-php">', $story['en']['content']);
    expect(StoryStyle::violations($story))->toBe([]);
});

it('links an article to its project on the page and in structured data', function () {
    portfolioPost(['slug' => 'monafy-article', 'project_key' => 'monafy']);

    $html = $this->get('/writing/monafy-article')->assertOk()->getContent();

    expect($html)->toMatch('~<aside class="brand-article-aside">.*href="https://monafy\.com"[^>]*>Monafy~s')
        ->not->toContain('nofollow')
        ->toContain('"about":{"@type":"Thing","name":"Monafy","url":"https://monafy.com"}');
});

it('publishes an anonymous case study without a period and shows no empty year', function () {
    caseStudy(['slug' => 'no-period', 'project_key' => 'b2b-saas', 'case_meta' => ['project_name' => 'B2B SaaS platformu', 'project_name_en' => 'B2B SaaS platform', 'context' => 'client', 'period' => '']]);

    $page = $this->get('/case-studies/no-period')->assertOk()->getContent();
    $list = $this->get('/case-studies')->assertOk()->getContent();

    expect($page)->not->toContain('<dt>'.__('brand.case_year', [], 'en').'</dt>')
        ->and($list)->toContain('B2B SaaS platform')->not->toMatch('~Client work / </p>~')->not->toMatch('~Client work /\s*</p>~');
});

it('features only the strongest projects on the home page', function () {
    $keys = collect(app(PortfolioProjects::class)->featured())->pluck('key')->all();

    expect($keys)->toBe(['monafy', 'moneo-render', 'transiyzi']);
});

it('lists Monafy first and labels anonymous work by its public name', function () {
    caseStudy(['slug' => 'game-story', 'project_key' => 'vampir-koylu', 'published_at' => now()->subDays(3), 'case_meta' => ['project_name' => 'Vampir Köylü', 'context' => 'personal', 'period' => '2026']]);
    caseStudy(['slug' => 'saas-story', 'project_key' => 'b2b-saas', 'case_meta' => ['project_name' => 'B2B SaaS platformu', 'project_name_en' => 'B2B SaaS platform', 'context' => 'client', 'period' => '']]);
    caseStudy(['slug' => 'monafy-story', 'published_at' => now()->subDay()]);

    $html = $this->get('/case-studies')->assertOk()->getContent();

    expect($html)->not->toContain('isimsiz')->toContain('B2B SaaS platform <span>1</span>')
        ->and(strpos($html, '/case-studies/monafy-story'))->toBeLessThan(strpos($html, '/case-studies/saas-story'))
        ->and(strpos($html, '/case-studies/saas-story'))->toBeLessThan(strpos($html, '/case-studies/game-story'));
});
