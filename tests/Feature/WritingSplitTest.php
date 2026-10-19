<?php

use App\Models\Post;
use Database\Seeders\RetireLegacyContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('lists only articles on the writing page and points to case studies', function () {
    portfolioPost(['title' => 'A method note', 'slug' => 'a-method-note']);
    caseStudy();
    $main = mainContent($this->get('/writing')->assertOk()->assertSee('/case-studies', false)->getContent());
    expect($main)->toContain('A method note')->not->toContain('Prices vanished at the close');
});

it('shows a helpful empty state when no article is published yet', function () {
    caseStudy();
    $this->get('/writing')->assertOk()->assertSee('No articles are published yet.')->assertSee('/case-studies', false)->assertDontSee('brand.');
    $this->get('/tr/yazilar')->assertOk()->assertSee('Henüz yayınlanmış bir yazı yok.')->assertSee('/tr/vaka-incelemeleri', false);
});

it('redirects the old type filters to their own sections', function () {
    $this->get('/writing?type=case_study')->assertStatus(301)->assertRedirect(url('/case-studies'));
    $this->get('/writing?type=article')->assertStatus(301)->assertRedirect(url('/writing'));
    $this->get('/tr/yazilar?type=case_study')->assertStatus(301)->assertRedirect(url('/tr/vaka-incelemeleri'));
    $this->get('/writing?type[]=x')->assertOk();
});

it('keeps the project description on its card and leaves its case studies to the case study page', function () {
    caseStudy(['sort_order' => 10]);
    caseStudy(['title' => 'The report that never arrived', 'slug' => 'the-report-that-never-arrived', 'excerpt' => 'A daily report stopped without an error.', 'sort_order' => 20]);
    $grid = Str::between($this->get('/')->assertOk()->getContent(), '<div class="brand-project-grid">', '<section class="brand-logos"');

    expect($grid)->toContain('A market research platform for Borsa Istanbul and Nasdaq stocks')
        ->not->toContain('Prices vanished at the close')->not->toContain('The report that never arrived')->not->toContain('Case study · 2');
    $this->get('/case-studies')->assertSee('Prices vanished at the close')->assertSee('The report that never arrived');
});

it('retires placeholder case studies and demo articles once without touching real posts', function () {
    foreach (['monafy', 'transiyzi', 'edanisman', 'smart-global', 'herbalfit', 'word-imposter', 'vampir-koylu'] as $slug) {
        caseStudy(['title' => 'Placeholder '.$slug, 'slug' => $slug, 'project_key' => $slug]);
    }
    foreach (['monafy-service-boundaries', 'monafy-market-data-pipeline', 'monafy-realtime-prices-and-caching', 'monafy-mona-ai-assistant', 'monafy-production-verification'] as $slug) {
        caseStudy(['title' => 'Old overview '.$slug, 'slug' => $slug]);
    }
    foreach (['html-elements-demonstration', 'published-post', 'seo-article'] as $slug) {
        portfolioPost(['title' => 'Demo '.$slug, 'slug' => $slug]);
    }
    $real = caseStudy(['title' => 'A real incident', 'slug' => 'a-real-incident']);
    $article = portfolioPost(['title' => 'A real article', 'slug' => 'a-real-article']);

    $this->seed(RetireLegacyContentSeeder::class);
    $this->seed(RetireLegacyContentSeeder::class);

    expect(Post::onlyTrashed()->count())->toBe(12)
        ->and(Post::whereIn('slug', ['html-elements-demonstration', 'published-post', 'seo-article'])->whereNotNull('published_at')->count())->toBe(0)
        ->and($real->fresh()->published_at)->not->toBeNull()
        ->and($article->fresh()->published_at)->not->toBeNull()
        ->and($real->fresh()->trashed())->toBeFalse();
});
