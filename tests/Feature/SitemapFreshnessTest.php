<?php

use App\Support\ContentStamp;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(fn () => Carbon::setTestNow());

it('dates a page by when its content changed, not by when a deploy rewrote the file', function () {
    $file = tempnam(sys_get_temp_dir(), 'page');
    file_put_contents($file, 'first version');

    Carbon::setTestNow('2026-10-01 10:00:00');
    expect(ContentStamp::lastChanged('page', [$file])->toAtomString())->toBe('2026-10-01T10:00:00+00:00');

    Carbon::setTestNow('2026-10-05 10:00:00');
    touch($file, time() + 3600);
    expect(ContentStamp::lastChanged('page', [$file])->toAtomString())->toBe('2026-10-01T10:00:00+00:00');

    file_put_contents($file, 'second version');
    expect(ContentStamp::lastChanged('page', [$file])->toAtomString())->toBe('2026-10-05T10:00:00+00:00')
        ->and(Seo::contentLastModified([$file], false))->toBe('2026-10-05T10:00:00+00:00');
});

it('lists a post as soon as it is published and drops it when unpublished or trashed', function () {
    $post = caseStudy(['slug' => 'fresh-story']);
    expect($this->get('/sitemap.xml')->getContent())->toContain('/case-studies/fresh-story', '/tr/vaka-incelemeleri/fresh-story');

    $post->update(['published_at' => now()->addDay()]);
    expect($this->get('/sitemap.xml')->getContent())->not->toContain('fresh-story');

    $post->update(['published_at' => now()->subHour()]);
    $post->delete();
    expect($this->get('/sitemap.xml')->getContent())->not->toContain('fresh-story');
});

it('answers a conditional request with 304 when nothing changed since', function () {
    caseStudy(['slug' => 'steady-story']);
    $first = $this->get('/sitemap.xml')->assertOk();
    $lastModified = $first->headers->get('Last-Modified');

    expect($lastModified)->not->toBeNull();
    $this->get('/sitemap.xml', ['If-Modified-Since' => $lastModified])->assertStatus(304);
});
