<?php

use App\Models\Post;
use App\Models\User;
use App\Services\Publishing\StoryDrafts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('local'));

function previewStory(Post $post): array
{
    return [
        'type' => $post->type->value, 'slug' => $post->slug, 'project_key' => $post->project_key,
        'tr' => ['title' => 'Belge tekrar inceleniyor', 'excerpt' => 'Yeni özet.', 'content' => '<p>Yalnız taslakta bulunan açıklama.</p>'],
        'en' => ['title' => 'Reviewing a document again', 'excerpt' => 'New summary.', 'content' => '<p>Explanation available only in the draft.</p>'],
    ];
}

it('requires admin login for query previews in both story sections', function (string $path) {
    $post = $path === 'writing' ? portfolioPost(['published_at' => null]) : caseStudy(['published_at' => null]);
    $url = '/'.$path.'/'.$post->slug.'?draft=1';
    $this->get($url)->assertRedirect(route('filament.admin.auth.login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    expect(session('url.intended'))->toBe(url($url));
    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
})->with(['writing', 'case-studies']);

it('previews an unpublished database post only for admins', function () {
    $post = portfolioPost(['published_at' => null]);
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $response = $this->get('/writing/'.$post->slug.'?draft=1')->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('A thoughtful product');
    expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
    $this->get('/writing/'.$post->slug)->assertNotFound();
    expect($post->fresh()->published_at)->toBeNull();
});

it('renders staged bilingual text without changing public pages or the database', function () {
    $post = caseStudy();
    app(StoryDrafts::class)->stage($post, previewStory($post), 'review-01');
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get('/tr/vaka-incelemeleri/'.$post->slug.'?draft=1')->assertOk()->assertSee('Yalnız taslakta bulunan açıklama.')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('content="noindex,follow,noarchive"', false);
    $this->get('/case-studies/'.$post->slug.'?draft=1')->assertOk()->assertSee('Explanation available only in the draft.');
    $this->get('/admin/preview/posts/'.$post->id.'?locale=en')->assertOk()->assertSee('Explanation available only in the draft.');
    $this->get('/tr/vaka-incelemeleri/'.$post->slug)->assertOk()->assertDontSee('Yalnız taslakta bulunan açıklama.');
    expect($post->fresh()->title)->toBe('Prices vanished at the close');
    $this->get('/sitemap.xml')->assertOk()->assertDontSee('draft=1');
});

it('keeps draft mode when redirecting old article paths to a localized case URL', function () {
    $post = caseStudy(['slug_tr' => 'belge-akisi', 'slug_en' => 'document-flow']);
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get('/tr/yazilar/'.$post->slug.'?draft=1')->assertRedirect(url('/tr/vaka-incelemeleri/belge-akisi?draft=1'));
    $this->get('/tr/vaka-incelemeleri/belge-akisi?draft=1')->assertOk()
        ->assertSee('/case-studies/document-flow?draft=1', false);
});

it('sanitizes staged html and keeps canonical URLs free of the draft parameter', function () {
    $post = portfolioPost();
    $story = previewStory($post);
    $story['en']['content'] = '<p onclick="alert(1)">Draft content</p><script>alert(2)</script>';
    app(StoryDrafts::class)->stage($post, $story, 'review-01');
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get('/writing/'.$post->slug.'?draft=1')->assertOk()->assertSee('Draft content')
        ->assertDontSee('onclick=', false)->assertDontSee('<script>alert(2)', false)
        ->assertDontSee('rel="canonical"', false)
        ->assertSee('property="og:url" content="'.url('/writing/'.$post->slug).'"', false);
});

it('rejects stale previews after a live edit rather than showing the old revision', function () {
    config(['app.debug' => false]);
    $post = portfolioPost();
    app(StoryDrafts::class)->stage($post, previewStory($post), 'review-01');
    $post->update(['content' => '<p>A new live edit.</p>']);
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get('/writing/'.$post->slug.'?draft=1')->assertStatus(409)->assertDontSee('Yalnız taslakta bulunan açıklama.');
    $this->get('/writing/'.$post->slug)->assertOk()->assertSee('A new live edit.');
});

it('never reopens a deleted story through the public query preview', function () {
    $post = portfolioPost();
    $post->delete();
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get('/writing/'.$post->slug.'?draft=1')->assertNotFound();
});

it('stages exactly one matching story and rejects a mismatched identity', function () {
    $post = portfolioPost();
    $file = tempnam(sys_get_temp_dir(), 'story-preview-');
    try {
        file_put_contents($file, json_encode([previewStory($post)], JSON_THROW_ON_ERROR));
        $this->artisan('stories:stage', ['file' => $file, '--revision' => 'review-01'])->assertSuccessful();
        Storage::disk('local')->assertExists('editorial/previews/'.$post->id.'.json');
        $story = previewStory($post);
        $story['type'] = 'case_study';
        file_put_contents($file, json_encode([$story], JSON_THROW_ON_ERROR));
        $this->artisan('stories:stage', ['file' => $file, '--revision' => 'review-02'])->assertFailed();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/writing/'.$post->slug.'?draft=1')->assertOk()->assertSee('Explanation available only in the draft.');
    } finally {
        unlink($file);
    }
});

it('rejects malformed staged files without displaying private data', function () {
    config(['app.debug' => false]);
    $post = portfolioPost();
    Storage::disk('local')->put('editorial/previews/'.$post->id.'.json', '{invalid json');
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get('/writing/'.$post->slug.'?draft=1')->assertStatus(409);
});

it('rejects multiple stories and non-local remote staging without publishing anything', function () {
    $post = portfolioPost(['published_at' => null]);
    $file = tempnam(sys_get_temp_dir(), 'stage-invalid-');
    try {
        file_put_contents($file, json_encode([previewStory($post), previewStory($post)], JSON_THROW_ON_ERROR));
        $this->artisan('stories:stage', ['file' => $file, '--revision' => 'review-01'])->assertFailed();
        file_put_contents($file, json_encode([previewStory($post)], JSON_THROW_ON_ERROR));
        $this->artisan('stories:stage', ['file' => $file, '--revision' => 'review-01', '--remote' => true])->assertFailed();
        Storage::disk('local')->assertMissing('editorial/previews/'.$post->id.'.json');
        expect($post->fresh()->published_at)->toBeNull();
    } finally {
        unlink($file);
    }
});
