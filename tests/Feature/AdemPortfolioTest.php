<?php

use App\Models\Post;
use App\Services\Portfolio\PortfolioProjects;
use App\Services\Portfolio\ProjectCatalog;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

it('leads the featured work and the case studies with ADEM', function () {
    $keys = array_column(app(PortfolioProjects::class)->featured(), 'key');

    expect($keys)->toBe(['adem', 'monafy', 'moneo-render', 'transiyzi', 'smart-global', 'edanisman', 'word-imposter'])
        ->and(ProjectCatalog::STORY_ORDER[0])->toBe('adem');
});

it('links the ADEM card to its case study on this site once the story is published', function (string $locale, string $home) {
    $this->get($home)->assertOk()->assertDontSee('vaka-incelemeleri/onayinizi-bekleyen', false)->assertDontSee('case-studies/digital-teammate', false);

    $this->seed(StorySeeder::class);
    $story = Post::published()->where('project_key', 'adem')->firstOrFail();
    $url = $story->publicUrl($locale);
    $html = $this->get($home)->assertOk()->getContent();

    expect($html)->toContain('<a class="brand-project-link" href="'.$url.'" aria-label="'.e(__('brand.read_case', [], $locale)).' — ADEM">ADEM</a>');
    $this->get($url)->assertOk()->assertSee('ADEM');
    expect(app(ProjectCatalog::class)->caseStudyUrl('adem', $locale))->toBe($url)
        ->and(app(ProjectCatalog::class)->caseStudyUrl('unknown', $locale))->toBeNull();
})->with(['English' => ['en', '/'], 'Turkish' => ['tr', '/tr']]);

it('shows the ADEM hero as a silent loop that only plays without a reduced-motion preference', function () {
    $html = $this->get('/')->assertOk()->getContent();
    preg_match('~<video[^>]*data-motion-video[^>]*>~', $html, $video);

    expect($video[0] ?? '')->toContain('poster="'.asset('images/projects/adem-hero.webp').'"')
        ->toContain(' muted')->toContain(' loop')->toContain(' playsinline')->toContain('preload="none"')
        ->not->toContain('autoplay');
    expect($html)->toContain('<source src="'.asset('images/projects/adem-hero.webm').'" type="video/webm">')
        ->toContain('<source src="'.asset('images/projects/adem-hero.mp4').'" type="video/mp4">');
    foreach (['webm', 'mp4', 'webp'] as $extension) {
        expect(public_path('images/projects/adem-hero.'.$extension))->toBeFile();
    }
    expect(file_get_contents(resource_path('js/app.js')))->toContain("matchMedia('(prefers-reduced-motion: no-preference)')");
});

it('never names the private ADEM address or an outside ADEM site', function () {
    $files = [...glob(resource_path('content/*.json')), ...glob(resource_path('content/stories/*.json')), ...glob(resource_path('views/**/*.blade.php')), ...glob(resource_path('lang/*/*.php'))];
    foreach ($files as $file) {
        expect(file_get_contents($file))->not->toContain('adem.monafy');
    }
    foreach (['en', 'tr'] as $locale) {
        $content = json_decode(file_get_contents(resource_path("content/portfolio.{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
        $adem = collect($content['projects']['sections'])->flatMap(fn (array $section): array => $section['items'])->firstWhere('key', 'adem');
        expect($adem['url'])->toBe('')->and($adem['case_study'])->toBeTrue();
    }
    $story = collect(json_decode(file_get_contents(resource_path('content/stories/adem.json')), true))->first();
    expect($story['case_meta']['url'])->toBeNull();
});
