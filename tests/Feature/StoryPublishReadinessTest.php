<?php

use App\Models\Post;
use App\Support\StoryStyle;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('asks for a search title when the headline would be cut in results', function () {
    $long = 'Prices kept arriving late because the consumer committed too early';

    expect(StoryStyle::violations(cleanStory(['en' => ['title' => $long]])))->toContain('en: title is longer than 60 characters and needs a meta_title')
        ->and(StoryStyle::violations(cleanStory(['en' => ['title' => $long, 'meta_title' => 'Prices arrived late after an early commit']])))->not->toContain('en: title is longer than 60 characters and needs a meta_title')
        ->and(StoryStyle::violations(cleanStory(['en' => ['meta_title' => $long.' again']])))->toContain('en: meta_title is longer than 60 characters')
        ->and(StoryStyle::violations(cleanStory(['tr' => ['meta_title' => 'Fiyatlar: geç geliyordu']])))->toContain('tr: meta_title has a colon');
});

it('imports each language’s search title with the story', function () {
    StorySeeder::$path = base_path('tests/Fixtures/stories');
    $this->seed(StorySeeder::class);
    StorySeeder::$path = null;
    $story = Post::where('slug', 'fixture-incident')->firstOrFail();

    expect($story->metaTitle('tr'))->toBe('Kapanışta kaybolan fiyatlar')->and($story->metaTitle('en'))->toBe('Prices that vanished at the close');
});

it('publishes every shipped story with a search title that fits the results page', function () {
    $this->seed(StorySeeder::class);
    $stories = collect(glob(resource_path('content/stories/*.json')))->flatMap(fn (string $file): array => json_decode(file_get_contents($file), true));

    expect(Post::published()->count())->toBe($stories->count());
    foreach (Post::published()->get() as $post) {
        foreach (['tr', 'en'] as $locale) {
            expect(mb_strlen($post->metaTitle($locale)))->toBeLessThanOrEqual(60, "{$post->slug} {$locale}");
            $this->get($post->publicUrl($locale))->assertOk();
        }
    }
});
