<?php

use App\Models\Post;
use App\Services\Editorial\EditorialBundle;
use App\Services\Editorial\EditorialPublisher;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function localizedStory(array $attributes = []): Post
{
    return caseStudy(array_replace_recursive([
        'slug' => 'legacy-price-story', 'slug_tr' => 'kapanista-kaybolan-fiyatlar', 'slug_en' => 'prices-at-the-close',
        'title' => 'Kapanışta kaybolan fiyatlar',
        'translations' => ['en' => ['title' => 'Prices at the close', 'excerpt' => 'Prices stopped at the close.', 'content' => '<p>Prices stopped at the close.</p>']],
    ], $attributes));
}

it('serves each localized slug with its own canonical and reciprocal clean alternates', function () {
    $post = localizedStory();
    foreach (['tr', 'en'] as $locale) {
        $html = $this->withSession(['locale' => $locale === 'tr' ? 'en' : 'tr'])
            ->get($post->publicUrl($locale).'?utm_source=example')->assertOk()->getContent();
        expect($html)->toContain('<html lang="'.$locale.'"', '<link rel="canonical" href="'.$post->publicUrl($locale).'"');
        foreach (['tr', 'en'] as $alternate) {
            expect($html)->toContain('hreflang="'.$alternate.'" href="'.$post->publicUrl($alternate).'"');
        }
        expect($html)->not->toContain('hreflang="tr" href="'.$post->publicUrl('tr').'?');
    }
});

it('redirects all legacy and mixed section paths straight to the final locale URL', function () {
    $post = localizedStory();
    foreach (['/tr/vaka-incelemeleri/legacy-price-story', '/tr/writing/legacy-price-story', '/tr/yazilar/prices-at-the-close', '/tr/blog/legacy-price-story', '/tr/preview/case-studies/legacy-price-story'] as $path) {
        $this->get($path)->assertStatus(301)->assertRedirect($post->publicUrl('tr'));
    }
    $this->get('/writing/legacy-price-story')->assertStatus(301)->assertRedirect($post->publicUrl('en'));
    $this->get('/en/blog/kapanista-kaybolan-fiyatlar')->assertStatus(301)->assertRedirect($post->publicUrl('en'));
    $this->get('/tr/vaka-incelemeleri/prices-at-the-close/')->assertStatus(301)->assertRedirect($post->publicUrl('tr'));
});

it('translates post slugs and retains query fragments for language navigation', function () {
    $post = localizedStory();
    expect(Seo::localizedUrl($post->publicUrl('tr').'?utm_source=example#details', 'en'))
        ->toBe($post->publicUrl('en').'?utm_source=example#details');
    expect(Seo::route('case-studies.show', ['post' => $post], 'tr'))->toBe($post->publicUrl('tr'));
});

it('uses only localized canonical story URLs in sitemap feeds and article schema', function () {
    $post = localizedStory();
    $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
    foreach (['tr', 'en'] as $locale) {
        expect($sitemap)->toContain('<loc>'.$post->publicUrl($locale).'</loc>');
        $this->get(Seo::route('feed', [], $locale))->assertOk()->assertSee('<link>'.$post->publicUrl($locale).'</link>', false)
            ->assertSee('<guid isPermaLink="true">'.Seo::route('case-studies.show', ['slug' => $post->slug], $locale).'</guid>', false);
        $this->get($post->publicUrl($locale))->assertOk()->assertSee('"url":"'.$post->publicUrl($locale).'"', false);
    }
    expect($sitemap)->not->toContain('/legacy-price-story</loc>');
});

it('does not reveal draft scheduled or trashed localized aliases', function () {
    foreach ([null, now()->addDay(), now()->subDay()] as $index => $published) {
        $post = localizedStory(['slug' => 'private-'.$index, 'slug_tr' => 'gizli-'.$index, 'slug_en' => 'hidden-'.$index, 'published_at' => $published]);
        if ($index === 2) {
            $post->delete();
        }
        foreach (['/tr/vaka-incelemeleri/gizli-', '/case-studies/hidden-', '/tr/blog/private-'] as $prefix) {
            $this->get($prefix.$index)->assertNotFound();
        }
    }
});

it('reserves legacy and localized slugs including soft deleted stories and freezes published addresses', function () {
    $post = localizedStory();
    expect(fn () => $post->update(['slug_tr' => 'new-address']))->toThrow(ValidationException::class);
    $post->delete();
    expect(fn () => portfolioPost(['slug' => 'another-story', 'slug_en' => 'kapanista-kaybolan-fiyatlar']))->toThrow(ValidationException::class);
    expect(fn () => portfolioPost(['slug' => 'prices-at-the-close']))->toThrow(ValidationException::class);
});

it('backfills final editorial slugs and resolves title collisions without rewriting timestamps', function () {
    $migration = require database_path('migrations/2026_10_21_120000_add_localized_post_slugs.php');
    $migration->down();
    $timestamp = '2026-10-01 09:00:00';
    $base = ['content' => '<p>Detail.</p>', 'created_at' => $timestamp, 'updated_at' => $timestamp, 'published_at' => $timestamp, 'first_published_at' => $timestamp];
    DB::table('posts')->insert([
        ['slug' => 'reserved-address', 'title' => 'Ayrı başlık', ...$base],
        ['slug' => 'old-first', 'title' => 'Reserved address', ...$base],
        ['slug' => 'old-second', 'title' => 'Reserved address', ...$base],
    ]);
    $launch = json_decode(file_get_contents(resource_path('editorial/launch-story.json')), true, flags: JSON_THROW_ON_ERROR);
    DB::table('posts')->insert(['slug' => $launch['slug'], 'title' => 'Eski Türkçe başlık', ...$base]);
    $migration->up();
    expect(DB::table('posts')->where('slug', 'old-first')->value('slug_tr'))->toBe('reserved-address-2')
        ->and(DB::table('posts')->where('slug', 'old-second')->value('slug_tr'))->toBe('reserved-address-3')
        ->and(DB::table('posts')->where('slug', 'old-first')->value('slug_en'))->toBe('old-first')
        ->and(DB::table('posts')->where('slug', 'old-first')->value('updated_at'))->toBe($timestamp)
        ->and(DB::table('posts')->where('slug', $launch['slug'])->value('slug_tr'))->toBe($launch['tr']['slug'] ?? 'eski-turkce-baslik');
});

it('imports nested editorial slugs without replacing the stable key or polluting translation fields', function () {
    $story = cleanStory(['slug' => 'stable-bundle-key', 'tr' => ['slug' => 'turkce-baglanti'], 'en' => ['slug' => 'english-link']]);
    $story['case_meta'] = ['project_name' => 'Monafy', 'context' => 'founder', 'technologies' => ['Laravel']];
    foreach (['tr', 'en'] as $locale) {
        $story[$locale]['meta_title'] = $story[$locale]['title'];
        $story[$locale]['meta_description'] = 'This story explains the engineering decisions and publication checks behind reliable market data.';
    }
    $result = app(EditorialPublisher::class)->import(EditorialBundle::seal('localized-bundle', $story, hash('sha256', 'localized-bundle')));
    $post = Post::findOrFail($result['post_id']);
    expect($post->slug)->toBe('stable-bundle-key')->and($post->slug_tr)->toBe('turkce-baglanti')->and($post->slug_en)->toBe('english-link')
        ->and($post->translations['en'])->not->toHaveKey('slug')
        ->and($result['urls'])->toBe(['tr' => $post->publicUrl('tr'), 'en' => $post->publicUrl('en')]);
});

it('requires explicit launch sync and preserves published URLs and publication dates', function () {
    $launch = json_decode(file_get_contents(resource_path('editorial/launch-story.json')), true, flags: JSON_THROW_ON_ERROR);
    $post = localizedStory(['slug' => $launch['slug']]);
    $published = $post->published_at->toAtomString();
    $this->artisan('stories:sync', ['slug' => [$launch['slug']]])->assertFailed();
    $this->artisan('stories:sync', ['slug' => [$launch['slug']], '--include-launch' => true])->assertSuccessful();
    $post->refresh();
    expect($post->title)->toBe($launch['tr']['title'])->and($post->slug_tr)->toBe('kapanista-kaybolan-fiyatlar')
        ->and($post->slug_en)->toBe('prices-at-the-close')->and($post->published_at->toAtomString())->toBe($published);
});
