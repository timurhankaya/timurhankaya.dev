<?php

use App\Enums\PostType;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('keeps existing primary content as a fallback for blank or absent translations', function () {
    $post = portfolioPost(['translations' => ['en' => ['title' => '', 'excerpt' => 'English summary', 'content' => '<p></p>']]]);
    expect($post->text('title', 'en'))->toBe('A thoughtful product');
    expect($post->text('excerpt', 'en'))->toBe('English summary');
    expect($post->text('content', 'en'))->toContain('Clear decisions');
    expect($post->type)->toBe(PostType::Article);
});

it('does not expose draft scheduled or deleted articles or case studies publicly', function (string $state) {
    $post = portfolioPost(['published_at' => $state === 'scheduled' ? now()->addDay() : ($state === 'draft' ? null : now())]);
    if ($state === 'deleted') {
        $post->delete();
    }
    $this->get('/writing/'.$post->slug)->assertNotFound();
    $this->get('/tr/yazilar/'.$post->slug)->assertNotFound();
    $this->get('/case-studies/'.$post->slug)->assertNotFound();
    $this->get('/sitemap.xml')->assertOk()->assertDontSee($post->slug);
})->with(['draft', 'scheduled', 'deleted']);

it('sanitizes post html in public and administrator previews', function () {
    $post = portfolioPost(['content' => '<h2>Safe title</h2><p onclick="alert(1)">Visible text</p><script>alert(2)</script><a href="javascript:alert(3)">Link</a>']);
    $html = $this->get('/writing/'.$post->slug)->assertOk()->getContent();
    expect($html)->toContain('Safe title', 'Visible text')->not->toContain('onclick=', 'javascript:alert', '<script>alert(2)');
    $admin = User::factory()->create(['is_admin' => true]);
    $preview = $this->actingAs($admin)->get('/admin/preview/posts/'.$post->id)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->getContent();
    expect($preview)->not->toContain('onclick=', 'javascript:alert', '<script>alert(2)');
});

it('does not allow title text to escape an article json ld script', function () {
    $post = portfolioPost(['title' => '</script><script>alert(1)</script>']);
    $this->get('/writing/'.$post->slug)->assertOk()->assertDontSee('</script><script>alert(1)</script>', false);
});

it('keeps published addresses immutable even after unpublishing', function () {
    $post = portfolioPost();
    $post->update(['published_at' => null]);
    expect(fn () => $post->update(['slug' => 'changed-address']))->toThrow(ValidationException::class);
    expect($post->fresh()->slug)->toBe('a-thoughtful-product');
});

it('allows incomplete case drafts but requires case metadata before publication', function () {
    $post = portfolioPost(['type' => PostType::CaseStudy, 'content' => '', 'excerpt' => null, 'published_at' => null]);
    expect($post->exists)->toBeTrue();
    expect(fn () => $post->update(['published_at' => now()]))->toThrow(ValidationException::class);
});

it('allows several case studies per project and keeps them in reading order', function () {
    caseStudy(['title' => 'Third look', 'slug' => 'third-look', 'sort_order' => 30]);
    caseStudy(['title' => 'First look', 'slug' => 'first-look', 'sort_order' => 10]);
    caseStudy(['title' => 'Second look', 'slug' => 'second-look', 'sort_order' => 20]);
    expect(Post::query()->series('monafy')->pluck('slug')->all())->toBe(['first-look', 'second-look', 'third-look']);

    $this->get('/case-studies/second-look')->assertOk()
        ->assertSee('/case-studies/first-look"', false)->assertSee('/case-studies/third-look"', false)
        ->assertSee('Part 2 of 3');
    $this->get('/tr/vaka-incelemeleri/third-look')->assertOk()->assertSee('3 / 3');
    $this->get('/case-studies/first-look')->assertOk()->assertDontSee('rel="prev"', false)->assertSee('rel="next"', false);
});

it('filters the case index by project and ignores unknown or malformed filters', function () {
    caseStudy(['title' => 'A deeper look', 'slug' => 'a-deeper-look']);
    caseStudy(['title' => 'Another project story', 'slug' => 'another-project-story', 'project_key' => 'herbalfit', 'case_meta' => ['project_name' => 'Herbalfit']]);
    expect(mainContent($this->get('/case-studies?project=monafy')->assertOk()->getContent()))->toContain('A deeper look')->not->toContain('Another project story');
    $this->get('/case-studies?project=nope')->assertOk()->assertSee('A deeper look')->assertSee('Another project story');
    $this->get('/case-studies?project[]=x')->assertOk();
});

it('restores a deleted case study without conflicting with a sibling', function () {
    $original = caseStudy(['slug' => 'original-story']);
    $sibling = caseStudy(['slug' => 'sibling-story', 'sort_order' => 10]);
    $original->delete();
    $original->restore();
    expect($original->fresh()->trashed())->toBeFalse()->and($sibling->fresh()->published_at)->not->toBeNull();
});

it('validates and localizes related case links', function () {
    $case = caseStudy(['slug' => 'linked-story']);
    $meta = $case->case_meta;
    $case->update(['case_meta' => [...$meta, 'links' => [['label' => ['tr' => 'Fiyatlar', 'en' => 'Pricing'], 'url' => 'https://monafy.com/pricing'], ['label' => ['tr' => 'Sadece TR'], 'url' => 'https://monafy.com']]]]);
    expect($case->fresh()->caseLinks('en'))->toBe([['label' => 'Pricing', 'url' => 'https://monafy.com/pricing'], ['label' => 'Sadece TR', 'url' => 'https://monafy.com']]);
    $this->get('/case-studies/linked-story')->assertOk()->assertSee('https://monafy.com/pricing', false)->assertSee('Pricing');
    foreach ([['label' => ['tr' => 'x'], 'url' => 'javascript:alert(1)'], ['label' => ['de' => 'x'], 'url' => 'https://monafy.com']] as $link) {
        expect(fn () => $case->fresh()->update(['case_meta' => [...$meta, 'links' => [$link]]]))->toThrow(ValidationException::class);
    }
});

it('imports stories from json once, keeps drafts private and preserves later edits', function () {
    StorySeeder::$path = base_path('tests/Fixtures/stories');
    $this->seed(StorySeeder::class);
    $story = Post::where('slug', 'fixture-incident')->firstOrFail();
    expect($story->type)->toBe(PostType::CaseStudy)->and($story->sort_order)->toBe(10)->and($story->text('title', 'en'))->toBe('Prices vanished at the close');
    expect(Post::where('slug', 'fixture-draft-article')->firstOrFail()->published_at)->toBeNull();
    $this->get('/writing/fixture-draft-article')->assertNotFound();
    $story->update(['title' => 'Edited in admin']);
    $count = Post::count();
    $this->seed(StorySeeder::class);
    expect(Post::count())->toBe($count)->and($story->fresh()->title)->toBe('Edited in admin');
    StorySeeder::$path = null;
});

it('rejects unsafe case links and unknown project associations', function () {
    $case = caseStudy(['slug' => 'unsafe-story']);
    expect(fn () => $case->update(['case_meta' => array_replace($case->case_meta, ['url' => 'javascript:alert(1)'])]))->toThrow(ValidationException::class);
    expect(fn () => $case->fresh()->update(['project_key' => 'unknown-project']))->toThrow(ValidationException::class);
});

it('serves translated canonical case studies and redirects their old article paths', function () {
    caseStudy([
        'title' => 'Fiyatlar kapanışta kayboluyordu', 'slug' => 'fiyatlar-kapanista-kayboluyordu',
        'translations' => ['en' => ['title' => 'Prices vanished at the close', 'excerpt' => 'Live prices stopped near the close.', 'content' => '<h2>Close</h2><p>Detail.</p>']],
    ]);
    $this->get('/tr/vaka-incelemeleri/fiyatlar-kapanista-kayboluyordu')->assertOk()->assertSee('Fiyatlar kapanışta kayboluyordu')->assertDontSee('brand.');
    $this->get('/case-studies/fiyatlar-kapanista-kayboluyordu')->assertOk()->assertSee('Prices vanished at the close');
    $this->get('/tr/yazilar/fiyatlar-kapanista-kayboluyordu')->assertStatus(301)->assertRedirect('/tr/vaka-incelemeleri/fiyatlar-kapanista-kayboluyordu');
    $article = portfolioPost();
    $this->get('/case-studies/'.$article->slug)->assertNotFound();
    $sitemap = $this->get('/sitemap.xml')->assertOk();
    $sitemap->assertSee('/tr/vaka-incelemeleri/fiyatlar-kapanista-kayboluyordu', false)->assertSee('/case-studies/fiyatlar-kapanista-kayboluyordu', false)->assertDontSee('/writing/fiyatlar', false);
});

it('protects draft preview from guests and ordinary users in every environment', function () {
    $draft = portfolioPost(['published_at' => null]);
    $this->get('/admin/preview/posts/'.$draft->id)->assertRedirect(route('filament.admin.auth.login'));
    $user = User::factory()->create();
    expect($user->is_admin)->toBeFalse();
    $this->actingAs($user)->get('/admin')->assertForbidden();
    $this->get('/admin/preview/posts/'.$draft->id)->assertForbidden();
    $user->is_admin = true;
    $user->save();
    $this->actingAs($user)->get('/admin/preview/posts/'.$draft->id)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('A thoughtful product');
    $this->get('/writing/'.$draft->slug)->assertNotFound();
});

it('publishes a co-founder case study and labels it in both languages', function () {
    $post = caseStudy(['slug' => 'founder-story', 'case_meta' => ['project_name' => 'Monafy', 'context' => 'founder', 'period' => '2025–']]);

    expect($post->fresh()->published_at)->not->toBeNull();
    $this->get('/case-studies/founder-story')->assertOk()->assertSee('Co-founder');
    $this->get('/tr/vaka-incelemeleri/founder-story')->assertOk()->assertSee('Kurucu ortak');
});
