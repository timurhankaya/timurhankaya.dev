<?php

use App\Models\Post;
use App\Providers\AppServiceProvider;
use App\Support\Seo;
use App\Support\StructuredData;
use Database\Seeders\StorySeeder;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Every JSON-LD node on the page, across all ld+json blocks. */
function ldNodes(string $html): array
{
    preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $blocks);

    return collect($blocks[1])->flatMap(fn (string $json): array => json_decode($json, true)['@graph'] ?? [])->all();
}

/** Title and description exactly as a results page would show them. */
function searchSnippet(string $html): array
{
    preg_match('~<title>([^<]*)</title>~', $html, $title);
    preg_match('~<meta name="description" content="([^"]*)"~', $html, $description);

    return array_map(fn (?string $value): string => html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5), [$title[1] ?? '', $description[1] ?? '']);
}

it('sends an address with a trailing slash to the one without it', function (string $from, string $to) {
    // The test client trims trailing slashes itself, so the request goes straight to the kernel.
    $response = app(HttpKernel::class)->handle(HttpRequest::create('http://localhost'.$from));

    expect($response->getStatusCode())->toBe(301)->and($response->headers->get('Location'))->toBe('http://localhost'.$to);
})->with([['/writing/', '/writing'], ['/tr/yazilar/?page=2', '/tr/yazilar?page=2'], ['/tr/', '/tr']]);

it('ships a static robots.txt identical to what the app serves in production', function () {
    // The web server answers /robots.txt from disk, so the file must match the route's production output.
    URL::forceRootUrl('https://timurhankaya.dev');
    URL::forceScheme('https');

    expect(public_path('robots.txt'))->toBeFile()
        ->and(file_get_contents(public_path('robots.txt')))->toBe($this->get('/robots.txt')->assertOk()->getContent())
        ->toContain("\nSitemap: https://timurhankaya.dev/sitemap.xml\n");
});

it('writes robots.txt for the configured site address with one command', function () {
    $target = storage_path('framework/testing/robots.txt');
    @unlink($target);

    $this->artisan('seo:robots', ['--path' => $target, '--url' => 'https://example.test'])->assertSuccessful();
    expect(file_get_contents($target))->toContain('Sitemap: https://example.test/sitemap.xml')->toContain('Disallow: /admin');
});

it('keeps the brand name whatever APP_NAME the server sets', function () {
    config(['app.name' => 'Timurhan Kaya Dev']);
    $html = $this->get('/writing')->assertOk()->getContent();

    expect($html)->toContain('<title>Blog: notes on building software products — Timurhan Kaya</title>')
        ->toContain('<meta property="og:site_name" content="Timurhan Kaya">')
        ->not->toContain('Timurhan Kaya Dev')
        ->and($this->get('/site.webmanifest')->json('name'))->toBe('Timurhan Kaya');
});

it('builds https addresses in production even when the proxy speaks http', function () {
    app()->detectEnvironment(fn () => 'production');
    (new AppServiceProvider(app()))->boot();

    expect(url('/writing'))->toStartWith('https://')->and(Seo::route('blog.index', [], 'tr'))->toStartWith('https://');
});

it('publishes an RSS feed per language with published stories only', function () {
    $article = portfolioPost(['title' => 'Fiyat akışı', 'slug' => 'fiyat-akisi', 'published_at' => now()->subDay()]);
    $case = caseStudy(['slug' => 'feed-case', 'published_at' => now()->subDays(2)]);
    portfolioPost(['title' => 'Taslak yazı', 'slug' => 'taslak', 'published_at' => null]);

    $response = $this->get('/tr/feed.xml')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    $channel = simplexml_load_string($response->getContent())->channel;
    $links = [];
    foreach ($channel->item as $item) {
        $links[] = (string) $item->link;
    }

    expect((string) $channel->language)->toBe('tr')
        ->and($links)->toBe([$article->publicUrl('tr'), $case->publicUrl('tr')])
        ->and($response->getContent())->not->toContain('Taslak yazı')
        ->and((string) simplexml_load_string($this->get('/feed.xml')->assertOk()->getContent())->channel->language)->toBe('en');
});

it('lets readers and tools discover the feed and manifest from every page', function (string $path) {
    expect($this->get($path)->assertOk()->getContent())
        ->toMatch('~<link rel="alternate" type="application/rss\+xml" title="[^"]+" href="'.preg_quote(Seo::route('feed', [], 'tr'), '~').'">~')
        ->toContain('<link rel="manifest" href="'.url('/site.webmanifest').'">');
})->with(['/tr/iletisim', '/tr/ozgecmis']);

it('serves a web app manifest whose icons exist', function () {
    $manifest = $this->get('/site.webmanifest')->assertOk()->assertHeader('Content-Type', 'application/manifest+json')->json();

    expect($manifest['name'])->toBe('Timurhan Kaya')->and($manifest['start_url'])->toBe('/')
        ->and(collect($manifest['icons'])->pluck('sizes')->all())->toContain('192x192', '512x512');
    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
    }
    expect($this->get('/')->getContent())->toContain('<link rel="manifest" href="'.url('/site.webmanifest').'">');
});

it('adds the Yandex verification tag only when configured', function () {
    $this->get('/')->assertOk()->assertDontSee('yandex-verification');
    config(['services.yandex_webmaster.verification' => 'y123token']);
    $this->get('/')->assertOk()->assertSee('<meta name="yandex-verification" content="y123token">', false);
});

it('serves the IndexNow key file only for the configured key', function () {
    $this->get('/abcd1234efgh5678.txt')->assertNotFound();
    config(['services.indexnow.key' => 'abcd1234efgh5678']);

    $this->get('/abcd1234efgh5678.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertContent('abcd1234efgh5678');
    $this->get('/zzzz1234efgh5678.txt')->assertNotFound();
});

it('tells IndexNow about a story once it is published', function () {
    Http::fake();
    $this->withoutDefer();
    config(['services.indexnow.key' => 'abcd1234efgh5678']);
    $post = portfolioPost(['slug' => 'ping-me']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.indexnow.org/indexnow'
        && $request['key'] === 'abcd1234efgh5678' && $request['host'] === parse_url(url('/'), PHP_URL_HOST)
        && in_array($post->publicUrl('en'), $request['urlList'], true) && in_array($post->publicUrl('tr'), $request['urlList'], true));
});

it('stays quiet without a key and for drafts', function () {
    Http::fake();
    $this->withoutDefer();
    portfolioPost(['slug' => 'no-key']);
    config(['services.indexnow.key' => 'abcd1234efgh5678']);
    portfolioPost(['slug' => 'draft', 'published_at' => null]);

    Http::assertNothingSent();
});

it('submits every sitemap address with one command', function () {
    $post = caseStudy(['slug' => 'command-case']);
    Http::fake();

    $this->artisan('seo:indexnow')->assertFailed();
    config(['services.indexnow.key' => 'abcd1234efgh5678']);
    $this->artisan('seo:indexnow')->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => in_array($post->publicUrl('tr'), $request['urlList'], true)
        && in_array(Seo::localizedUrl(Seo::route('home', [], 'en'), 'en'), $request['urlList'], true) && in_array(Seo::route('games.vampire.index', [], 'tr'), $request['urlList'], true));
});

it('marks the social profiles in the footer as the same person', function () {
    $footer = Str::after($this->get('/')->assertOk()->getContent(), '<footer class="brand-footer">');

    expect($footer)->toContain('href="https://github.com/timurhankaya" target="_blank" rel="me noopener noreferrer"')
        ->toContain('href="https://www.linkedin.com/in/timurhan-kaya/" target="_blank" rel="me noopener noreferrer"');
});

it('gives every logo a width and height', function () {
    preg_match('~<section class="brand-logos".*?</section>~s', $this->get('/')->assertOk()->getContent(), $section);
    preg_match_all('~<img\b[^>]*>~', $section[0], $images);

    expect($images[0])->toHaveCount(4);
    foreach ($images[0] as $image) {
        expect($image)->toMatch('/ width="\d+"/')->toMatch('/ height="\d+"/');
    }
});

it('names the site, ties Monafy to its co-founder and credits articles by name', function () {
    portfolioPost(['slug' => 'schema-article']);
    $home = collect(ldNodes($this->get('/tr')->assertOk()->getContent()));
    $article = collect(ldNodes($this->get('/writing/schema-article')->assertOk()->getContent()))->firstWhere('@type', 'BlogPosting');

    expect($home->firstWhere('@type', 'WebSite')['alternateName'])->toContain('tkaya')
        ->and($home->firstWhere('@type', 'Organization'))->toMatchArray(['name' => 'Monafy', 'url' => 'https://monafy.com', 'founder' => ['@id' => StructuredData::personId()]])
        ->and($home->firstWhere('@type', 'ProfilePage')['name'])->toBe('Timurhan Kaya — Yazılım ve AI Mühendisi')
        ->and($article['author'])->toMatchArray(['@id' => StructuredData::personId(), 'name' => 'Timurhan Kaya']);
});

it('tells engines how many players each game needs', function (string $path, int $players) {
    $game = collect(ldNodes($this->get($path)->assertOk()->getContent()))->firstWhere('@type', 'VideoGame');

    expect($game['numberOfPlayers'])->toBe(['@type' => 'QuantitativeValue', 'minValue' => $players])->and($game['genre'])->not->toBeEmpty();
})->with([['/games/imposter', 3], ['/tr/oyunlar/vampir-koylu', 4]]);

it('writes titles for what people search and descriptions that fill the snippet', function () {
    // The English home comes first: once a Turkish page is seen, `/` redirects to the preferred language.
    $paths = ['/', '/writing', '/case-studies', '/games', '/games/imposter', '/games/vampire', '/tr', '/tr/yazilar', '/tr/vaka-incelemeleri', '/tr/oyunlar', '/tr/oyunlar/kelime-casusu', '/tr/oyunlar/vampir-koylu'];
    $snippets = collect($paths)->mapWithKeys(fn (string $path): array => [$path => searchSnippet($this->get($path)->assertOk()->getContent())]);

    expect($snippets['/'][0])->toBe('Timurhan Kaya — Software & AI Engineer')
        ->and($snippets['/tr'][0])->toBe('Timurhan Kaya — Yazılım ve AI Mühendisi')
        ->and($snippets['/tr/yazilar'][0])->toBe('Blog: yazılım ve ürün geliştirme notları — Timurhan Kaya')
        ->and($snippets['/case-studies'][0])->toBe('Case studies: problems I solved on real projects — Timurhan Kaya')
        ->and($snippets['/tr/oyunlar/vampir-koylu'][0])->toBe('Vampir Köylü: telefondan, moderatörsüz oyna — Timurhan Kaya')
        ->and($snippets['/tr/oyunlar/kelime-casusu'][0])->toBe('Word Imposter: Türkçe imposter kelime oyunu — Timurhan Kaya')
        ->and($snippets['/games/vampire'][0])->toBe('Vampire Village: a Werewolf-style game, no moderator')
        ->and($snippets['/tr/oyunlar'][0])->toContain('Vampir Köylü')->toContain('Word Imposter');
    foreach ($snippets as $path => [$title, $description]) {
        expect(mb_strlen($description))->toBeGreaterThanOrEqual(110, $path)->toBeLessThanOrEqual(160, $path);
    }
    expect($this->get('/contact')->getContent())->not->toContain('name="keywords"');
});

it('keeps code comments and line numbers readable against the code background', function () {
    $css = file_get_contents(resource_path('css/brand.css'));
    $color = function (string $selector, string $property) use ($css): string {
        preg_match('/^'.preg_quote($selector, '/').'\s*\{[^}]*\b'.$property.':\s*(#[0-9a-f]{6})/mi', $css, $match);

        return $match[1] ?? '#000000';
    };
    $luminance = function (string $hex): float {
        $channel = fn (int $value): float => ($c = $value / 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    };
    $background = $luminance($color('.brand-prose .brand-code', 'background'));

    foreach (['.brand-code .hl-comment', '.brand-code .hl-gutter'] as $selector) {
        expect(($luminance($color($selector, 'color')) + 0.05) / ($background + 0.05))->toBeGreaterThanOrEqual(4.5, $selector);
    }
});

it('links the renamed GitHub profile everywhere', function (string $path) {
    expect($this->get($path)->assertOk()->getContent())->not->toContain('github.com/kayacekovic')->not->toContain('@kayacekovic')->toContain('github.com/timurhankaya');
})->with(['/', '/tr/iletisim', '/cv']);

it('uses the Turkish share image on Turkish pages when one exists', function () {
    $og = function (string $path): string {
        preg_match('~<meta property="og:image" content="([^"]+)"~', $this->get($path)->assertOk()->getContent(), $match);

        return $match[1] ?? '';
    };

    expect($og('/games'))->toEndWith('/og-games.png')->and($og('/tr/oyunlar'))->toEndWith('/og-games-tr.png')
        ->and(public_path('og-games-tr.png'))->toBeFile();
});

it('gives each story a share card with its own title, and a generic one otherwise', function () {
    $this->seed(StorySeeder::class);
    $story = Post::where('slug', 'what-is-monafy')->firstOrFail();
    $unknown = portfolioPost(['slug' => 'no-card-yet']);

    expect($story->ogImage('tr'))->toBe('og/stories/what-is-monafy-tr.png')->and(public_path($story->ogImage('en')))->toBeFile()
        ->and($unknown->ogImage('en'))->toBe('og-blog-post.png');
    $html = $this->get($story->publicUrl('en'))->assertOk()->getContent();
    $imageUrl = Seo::imageUrl($story->ogImage('en'), 'en');
    expect($html)->toContain('<meta property="og:image" content="'.$imageUrl.'">')
        ->and(collect(ldNodes($html))->firstWhere('@type', 'BlogPosting')['image'])->toBe([$imageUrl]);
});

it('changes a story share image URL only when its contents change', function () {
    $card = 'og/stories/cache-version-check-en.png';
    $file = public_path($card);
    $contents = file_get_contents(public_path('og/stories/what-is-monafy-en.png'));
    try {
        file_put_contents($file, $contents);
        $original = Seo::imageUrl($card, 'en');
        expect($original)->toBe(asset($card).'?v='.substr(hash('sha256', $contents), 0, 12))
            ->and(Seo::imageMeta($original))->toMatchArray(['width' => 1200, 'height' => 630, 'type' => 'image/png']);

        touch($file, time() - 60);
        expect(Seo::imageUrl($card, 'en'))->toBe($original);

        file_put_contents($file, $contents."\n");
        expect(Seo::imageUrl($card, 'en'))->not->toBe($original)
            ->and(Seo::imageUrl('og-blog.png', 'en'))->toBe(asset('og-blog.png'));
    } finally {
        @unlink($file);
    }
});
