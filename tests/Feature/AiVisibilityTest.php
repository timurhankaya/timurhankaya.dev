<?php

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists published writing and case studies in llms.txt, in the right language, without drafts or trashed posts', function () {
    portfolioPost(['title' => 'Neden worktree kullanıyorum', 'slug' => 'neden-worktree', 'excerpt' => 'Her iş için ayrı bir klasör açıyorum.', 'translations' => ['en' => ['title' => 'Why I use worktrees', 'excerpt' => 'I open a separate folder per task.', 'content' => '<p>x</p>']]]);
    caseStudy(['title' => 'Fiyatlar kapanışta düşüyordu', 'slug' => 'fiyatlar-kapanista', 'excerpt' => 'Canlı fiyatlar kapanışta durdu.', 'translations' => ['en' => ['title' => 'Prices dropped at the close', 'excerpt' => 'Live prices stopped at the close.', 'content' => '<p>x</p>']]]);
    portfolioPost(['title' => 'A hidden draft', 'slug' => 'hidden-draft', 'published_at' => null]);
    portfolioPost(['title' => 'A trashed post', 'slug' => 'trashed-post'])->delete();

    $en = $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();
    expect($en)->toStartWith('# Timurhan Kaya')
        ->toContain("## Writing\n- [Why I use worktrees](", '/writing/neden-worktree): I open a separate folder per task.')
        ->toContain("## Case study\n- [Prices dropped at the close](", '/case-studies/fiyatlar-kapanista): Live prices stopped at the close.')
        ->toContain('## Projects', '## Contact')
        ->not->toContain('A hidden draft', 'A trashed post');

    $tr = $this->get('/tr/llms.txt')->assertOk()->getContent();
    expect($tr)->toContain("## Yazılar\n- [Neden worktree kullanıyorum](", '/tr/yazilar/neden-worktree', '## Projeler', '## İletişim');
});

it('puts an answer-first summary, a byline and dates at the top of every story', function () {
    $post = caseStudy(['slug' => 'summary-case', 'excerpt' => 'The short answer to the title.', 'published_at' => '2026-10-01 09:00:00']);
    $html = $this->get('/case-studies/summary-case')->assertOk()->getContent();
    expect($html)->toContain('<p class="brand-summary', 'The short answer to the title.', 'Short answer')
        ->and($html)->toContain('href="'.url('/cv').'"')
        ->and(substr_count($html, '<time datetime="2026-10-01'))->toBe(1);

    Post::whereKey($post->id)->update(['updated_at' => '2026-10-10 09:00:00']);
    $this->get('/case-studies/summary-case')->assertSee('<time datetime="2026-10-10', false);

    portfolioPost(['slug' => 'summary-article', 'excerpt' => 'Article answer.']);
    $this->get('/tr/yazilar/summary-article')->assertOk()->assertSee('Kısa cevap')->assertSee('Article answer.');
});

it('marks case studies as TechArticle with an abstract and keeps articles as BlogPosting', function () {
    caseStudy(['slug' => 'schema-case', 'excerpt' => 'Abstract text for the case.']);
    portfolioPost(['slug' => 'schema-article']);
    $graphOf = function (string $path): array {
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $this->get($path)->getContent(), $m);

        return json_decode($m[1], true)['@graph'];
    };
    $case = collect($graphOf('/case-studies/schema-case'))->firstWhere('@id', url('/case-studies/schema-case').'#article');
    expect($case['@type'])->toBe('TechArticle')->and($case['abstract'])->toBe('Abstract text for the case.');
    $article = collect($graphOf('/writing/schema-article'))->firstWhere('@id', url('/writing/schema-article').'#article');
    expect($article['@type'])->toBe('BlogPosting');
});

it('names every AI crawler in robots.txt and repeats the private-path rules inside that group', function () {
    $robots = $this->get('/robots.txt')->assertOk()->getContent();
    $groups = array_values(array_filter(explode("\n\n", trim($robots)), fn (string $block): bool => str_contains($block, 'User-agent:')));
    expect($groups)->toHaveCount(2);
    [$named, $any] = $groups;
    $rules = fn (string $group): array => array_values(array_filter(explode("\n", $group), fn (string $line): bool => str_starts_with($line, 'Allow:') || str_starts_with($line, 'Disallow:')));
    $agents = fn (string $group): array => array_map(fn (string $line): string => trim(substr($line, strlen('User-agent:'))), array_values(array_filter(explode("\n", $group), fn (string $line): bool => str_starts_with($line, 'User-agent:'))));

    expect($agents($any))->toBe(['*'])
        ->and($agents($named))->toContain('Googlebot', 'bingbot', 'OAI-SearchBot', 'ChatGPT-User', 'GPTBot', 'Claude-SearchBot', 'Claude-User', 'ClaudeBot', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'CCBot')
        ->and($rules($named))->toBe($rules($any))
        ->and($rules($named))->toContain('Allow: /', 'Disallow: /admin', 'Disallow: /preview/', 'Disallow: /tr/onizleme/')->not->toContain('Disallow: /livewire');
    expect($robots)->toContain('Sitemap: http')->and(trim($robots))->toEndWith('/sitemap.xml');
});

it('always serves the root llms.txt in English and the Turkish one only with Turkish addresses', function () {
    portfolioPost(['title' => 'Neden worktree', 'slug' => 'neden-worktree', 'translations' => ['en' => ['title' => 'Why worktrees', 'excerpt' => 'Short.', 'content' => '<p>x</p>']]]);
    $root = $this->withSession(['locale' => 'tr'])->withHeader('Accept-Language', 'tr-TR,tr;q=0.9')->get('/llms.txt')->assertOk()->getContent();
    expect($root)->toContain('## Writing', '/writing/neden-worktree', 'Why worktrees', '/tr/llms.txt')->not->toContain('/tr/yazilar');
    $tr = $this->get('/tr/llms.txt')->assertOk()->getContent();
    expect($tr)->toContain('## Yazılar', '/tr/yazilar/neden-worktree')->not->toContain('(http://localhost/writing/')
        ->and($tr)->not->toMatch('~\]\(https?://[^/]+/(writing|case-studies|projects|contact|cv|games)~');
});

it('keeps snippet directives open on indexable pages and adds the Bing verification tag only when configured', function () {
    portfolioPost(['slug' => 'open-snippets']);
    foreach (['/', '/writing/open-snippets', '/tr/yazilar/open-snippets'] as $path) {
        $html = $this->get($path)->assertOk()->getContent();
        preg_match('~<meta name="robots" content="([^"]*)"~', $html, $robots);
        expect($robots[1])->toContain('index', 'max-snippet:-1')->not->toContain('nosnippet', 'noarchive', 'nocache');
    }
    $this->get('/contact')->assertDontSee('msvalidate.01');
    config(['services.bing_webmaster.verification' => 'bing-token-1']);
    $this->get('/contact')->assertSee('<meta name="msvalidate.01" content="bing-token-1">', false);
});

it('shows the updated date whenever it differs from the published day so it matches dateModified', function () {
    $post = caseStudy(['slug' => 'dated-case', 'published_at' => '2026-10-01 23:00:00']);
    Post::whereKey($post->id)->update(['updated_at' => '2026-10-02 01:00:00']);
    $this->get('/case-studies/dated-case')->assertSee('<time datetime="2026-10-02', false);
});
