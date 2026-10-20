<?php

use App\Models\Post;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(fn () => StorySeeder::$path = null);

function storyEntry(array $overrides = []): array
{
    return array_replace_recursive([
        'type' => 'article', 'slug' => 'new-story', 'project_key' => null, 'sort_order' => 100, 'published_at' => '2026-10-01', 'draft' => true,
        'tr' => ['title' => 'Yeni yazı', 'excerpt' => 'Kısa cevap.', 'content' => '<p>İçerik.</p>'],
        'en' => ['title' => 'New story', 'excerpt' => 'Short answer.', 'content' => '<p>Content.</p>'],
    ], $overrides);
}

function storiesDir(array $stories): string
{
    $dir = sys_get_temp_dir().'/stories-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/stories.json', json_encode($stories));
    StorySeeder::$path = $dir;

    return $dir;
}

it('soft-deletes the row a new story replaces, once, and ignores a missing replaces slug', function () {
    $old = caseStudy(['slug' => 'old-case', 'title' => 'An old template title']);
    storiesDir([storyEntry(['slug' => 'new-article', 'replaces' => 'old-case']), storyEntry(['slug' => 'other-article', 'replaces' => 'does-not-exist'])]);

    $this->seed(StorySeeder::class);
    $this->seed(StorySeeder::class);

    expect(Post::withTrashed()->find($old->id)->trashed())->toBeTrue()
        ->and(Post::where('slug', 'new-article')->count())->toBe(1)
        ->and(Post::where('slug', 'other-article')->count())->toBe(1);
});

it('soft-deletes every row in a replaces list when one deep story supersedes several', function () {
    $first = caseStudy(['slug' => 'short-one']);
    $second = caseStudy(['slug' => 'short-two']);
    storiesDir([storyEntry(['slug' => 'deep-story', 'replaces' => ['short-one', 'short-two', 'missing']])]);

    $this->seed(StorySeeder::class);

    expect(Post::withTrashed()->find($first->id)->trashed())->toBeTrue()
        ->and(Post::withTrashed()->find($second->id)->trashed())->toBeTrue()
        ->and(Post::where('slug', 'deep-story')->count())->toBe(1);
});

it('creates every story as an unpublished draft when marked draft', function () {
    storiesDir([storyEntry(['slug' => 'draft-one']), storyEntry(['slug' => 'draft-two'])]);
    $this->seed(StorySeeder::class);
    expect(Post::whereIn('slug', ['draft-one', 'draft-two'])->whereNotNull('published_at')->count())->toBe(0);
});

it('skips a slug that already exists, even a trashed one, and says so instead of failing', function () {
    $trashed = portfolioPost(['slug' => 'taken-slug']);
    $trashed->delete();
    storiesDir([storyEntry(['slug' => 'taken-slug', 'replaces' => 'someone']), storyEntry(['slug' => 'free-slug'])]);

    $this->artisan('db:seed', ['--class' => StorySeeder::class])->expectsOutputToContain('taken-slug')->assertExitCode(0);

    expect(Post::withTrashed()->where('slug', 'taken-slug')->count())->toBe(1)->and(Post::where('slug', 'free-slug')->count())->toBe(1);
});

it('syncs the text of an already published story from its json without touching its address or date', function () {
    $dir = storiesDir([storyEntry(['slug' => 'live-story', 'draft' => false])]);
    $this->seed(StorySeeder::class);
    $published = Post::where('slug', 'live-story')->firstOrFail()->published_at;
    file_put_contents($dir.'/stories.json', json_encode([storyEntry(['slug' => 'live-story', 'draft' => false,
        'tr' => ['title' => 'Yeni başlık', 'meta_title' => 'Arama başlığı', 'content' => '<p>Yeni içerik.</p>'],
        'en' => ['title' => 'New title', 'meta_title' => 'Search title'],
    ]), storyEntry(['slug' => 'untouched'])]));

    $this->artisan('stories:sync', ['slug' => ['live-story']])->expectsOutputToContain('synced: live-story')->assertSuccessful();
    $post = Post::where('slug', 'live-story')->firstOrFail();

    expect($post->title)->toBe('Yeni başlık')->and($post->metaTitle('tr'))->toBe('Arama başlığı')->and($post->content)->toBe('<p>Yeni içerik.</p>')
        ->and($post->text('title', 'en'))->toBe('New title')->and($post->metaTitle('en'))->toBe('Search title')
        ->and($post->published_at->equalTo($published))->toBeTrue()
        ->and(Post::where('slug', 'untouched')->exists())->toBeFalse();
    $this->artisan('stories:sync', ['slug' => ['missing-story']])->expectsOutputToContain('not found: missing-story')->assertFailed();
});

it('imports and synchronizes the primary language SEO description', function () {
    $dir = storiesDir([storyEntry(['slug' => 'description-story', 'tr' => ['meta_description' => 'İlk SEO açıklaması.']])]);
    $this->seed(StorySeeder::class);
    expect(Post::where('slug', 'description-story')->firstOrFail()->meta_description)->toBe('İlk SEO açıklaması.');
    file_put_contents($dir.'/stories.json', json_encode([storyEntry(['slug' => 'description-story', 'tr' => ['meta_description' => 'Güncel SEO açıklaması.']])]));
    $this->artisan('stories:sync', ['slug' => ['description-story']])->assertSuccessful();
    expect(Post::where('slug', 'description-story')->firstOrFail()->meta_description)->toBe('Güncel SEO açıklaması.');
});
