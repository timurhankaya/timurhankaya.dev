<?php

use App\Services\Portfolio\ProjectCatalog;
use App\Support\Seo;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

it('presents the developer role throughout the localized CV and its metadata', function (string $locale, string $path) {
    $content = json_decode(file_get_contents(resource_path("content/portfolio.{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
    $html = $this->get($path)->assertOk()->getContent();
    $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);

    expect($decoded)->toContain('<p class="title-role">'.$content['cv']['role'].'</p>')
        ->toContain($content['cv']['profile'])
        ->toContain('<div class="section-title">'.$content['cv']['projects_title'].'</div>')
        ->toContain('<span class="project-name">ADEM</span>')
        ->toContain($content['cv']['awards']['items'][0]['body'])
        ->not->toContain('class="project-stack"');
    foreach ($content['cv']['project_descriptions'] as $description) {
        expect($decoded)->toContain('<p class="project-desc">'.$description.'</p>');
    }
    foreach ($content['cv']['technologies']['groups'] as $group) {
        expect($decoded)->toContain('<p class="skill-label">'.implode(', ', $group['items']).'</p>');
    }

    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $ld);
    $graph = json_decode($ld[1], true, flags: JSON_THROW_ON_ERROR)['@graph'];
    $person = collect($graph)->firstWhere('@type', 'Person');

    expect($person['jobTitle'])->toBe($content['cv']['role'])
        ->and($person['description'])->toBe($content['cv']['profile'])
        ->and($person['worksFor']['name'])->toBe('Wingie Enuygun Group')
        ->and($person['knowsAbout'])->toContain('PHP', 'Go', 'TypeScript', 'Apache Kafka', 'TimescaleDB');

    preg_match_all('~<span class="exp-role">([^<]+)</span>~', $html, $roles);
    preg_match_all('~<div class="exp-company">([^<]+)</div>~', $html, $companies);
    expect($roles[1])->toBe(array_column($content['experience']['items'], 'role'))
        ->and($companies[1])->toBe(array_column($content['experience']['items'], 'company'));

    $image = Seo::imageUrl('og-cv.png', $locale);
    expect($html)->toContain('<meta property="og:image" content="'.$image.'">')
        ->toContain('<meta name="twitter:image" content="'.$image.'">');
})->with(['English' => ['en', '/cv'], 'Turkish' => ['tr', '/tr/ozgecmis']]);

it('keeps the portfolio biography and CV skills summary distinct', function () {
    foreach (['en' => '/', 'tr' => '/tr'] as $locale => $path) {
        $content = json_decode(file_get_contents(resource_path("content/portfolio.{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
        $profile = $content['cv']['profile'];
        expect($this->get($path)->assertOk()->getContent())->toContain(e($content['about']['p2']));
        expect($content['stats']['role_value'])->toContain($locale === 'en' ? 'Co-founder' : 'Kurucu ortak');
        expect($profile)->toContain('AI-DLC', $locale === 'en' ? 'product architecture' : 'Ürün mimarisi')
            ->toContain($locale === 'en' ? 'microservice-based systems' : 'mikroservis tabanlı sistemler')
            ->not->toContain('Wingie Enuygun Group', 'Monafy', 'ADEM');
    }
});

it('orders the CV experience and links ADEM to its case study', function (string $locale, string $path) {
    $content = json_decode(file_get_contents(resource_path("content/portfolio.{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
    expect(array_column($content['experience']['items'], 'company'))->toBe(['Wingie Enuygun Group', 'Monafy', 'MONEO YAZILIM A.Ş.', 'MONEO YAZILIM A.Ş.'])
        ->and(array_column($content['skills']['groups'], 'title'))->toHaveCount(3);

    $this->seed(StorySeeder::class);
    $url = app(ProjectCatalog::class)->caseStudyUrl('adem', $locale);
    $html = $this->get($path)->assertOk()->getContent();
    preg_match_all('~<span class="project-name">(.*?)</span>~s', $html, $names);
    expect($names[1][0] ?? '')->toBe('<a href="'.$url.'">ADEM</a>');
    expect($this->get($path.'?ats=1')->assertOk()->getContent())->toContain('<a href="'.$url.'">'.$url.'</a>');
})->with(['English' => ['en', '/cv'], 'Turkish' => ['tr', '/tr/ozgecmis']]);

it('lists technologies as plain grouped names in both CV views', function (string $locale, string $path) {
    $expected = [
        'PHP, TypeScript, JavaScript, Go, Python',
        'Laravel, Symfony, Node.js, REST APIs',
        'React, Next.js, React Native, Vue, Nuxt, Tailwind CSS',
        'Apache Kafka, RabbitMQ',
        'PostgreSQL, TimescaleDB, MySQL, MongoDB, Redis, Elasticsearch',
        'Docker, AWS, GCP',
    ];
    foreach (['', '?ats=1'] as $view) {
        $html = html_entity_decode($this->get($path.$view)->assertOk()->getContent(), ENT_QUOTES | ENT_HTML5);
        preg_match('~<section[^>]*aria-labelledby="ats-technologies".*?</section>|<div class="section cv-technologies">.*?(?=<div class="section")~s', $html, $block);
        $text = preg_replace('/\s+/', ' ', strip_tags($block[0] ?? ''));
        expect($text)->toContain($locale === 'en' ? 'Technologies' : 'Teknolojiler', ...$expected)->not->toContain('%');
        expect($html)->not->toContain('Inertia');
    }
})->with(['English' => ['en', '/cv'], 'Turkish' => ['tr', '/tr/ozgecmis']]);

it('gives only the CV card its localized developer role without the founder title', function () {
    $spec = json_decode(file_get_contents(resource_path('og/cards.json')), true, flags: JSON_THROW_ON_ERROR);
    $card = collect($spec['cards'])->firstWhere('file', 'og-cv');

    foreach (['en', 'tr'] as $locale) {
        $content = json_decode(file_get_contents(resource_path("content/portfolio.{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
        expect($card['role'][$locale])->toBe($content['cv']['role']);
        expect(mb_strtolower(json_encode($card, JSON_UNESCAPED_UNICODE)))->not->toContain('co-founder')->not->toContain('kurucu');
        expect($spec['role'][$locale])->toContain($locale === 'en' ? 'Co-founder' : 'Kurucu ortak');
    }
});

it('versions localized CV images by content while preserving other image URL rules', function () {
    $originalPublicPath = public_path();
    $directory = sys_get_temp_dir().'/cv-images-'.bin2hex(random_bytes(8));
    File::makeDirectory($directory.'/og/stories', 0755, true);
    app()->usePublicPath($directory);

    try {
        foreach (['og-cv.png', 'og-cv-tr.png', 'og-basic.png', 'og/stories/example-en.png'] as $file) {
            file_put_contents(public_path($file), 'original '.$file);
        }

        foreach (['en' => 'og-cv.png', 'tr' => 'og-cv-tr.png'] as $locale => $file) {
            $original = Seo::imageUrl('og-cv.png', $locale);
            expect($original)->toBe(asset($file).'?v='.substr(hash_file('sha256', public_path($file)), 0, 12));
            file_put_contents(public_path($file), 'changed '.$file);
            expect(Seo::imageUrl('og-cv.png', $locale))->not->toBe($original);
        }

        expect(Seo::imageUrl('og-basic.png', 'en'))->toBe(asset('og-basic.png'))
            ->and(Seo::imageUrl('og/stories/example-en.png', 'en'))->toContain('?v=')
            ->and(Seo::imageUrl('https://example.com/og-cv.png', 'tr'))->toBe('https://example.com/og-cv.png');

        unlink(public_path('og-cv-tr.png'));
        expect(Seo::imageUrl('og-cv.png', 'tr'))->toBe(Seo::imageUrl('og-cv.png', 'en'));
        unlink(public_path('og-cv.png'));
        expect(Seo::imageUrl('og-cv.png', 'en'))->toBe(asset('og-cv.png'));
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($directory);
    }
});

it('loads the new CV schema even when portfolio data from the previous release is cached', function (string $locale, string $path) {
    $content = json_decode(file_get_contents(resource_path("content/portfolio.{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
    $old = $content;
    unset($old['cv']);
    Cache::put('portfolio:content:v3:'.$locale, $old, 1800);

    $html = html_entity_decode($this->get($path)->assertOk()->getContent(), ENT_QUOTES | ENT_HTML5);
    expect($html)->toContain('<p class="title-role">'.$content['cv']['role'].'</p>')->toContain($content['cv']['profile']);
})->with(['en' => ['en', '/cv'], 'tr' => ['tr', '/tr/ozgecmis']]);
