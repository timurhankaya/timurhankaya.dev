<?php

use App\Models\Post;
use App\Services\Editorial\EditorialBundle;
use App\Services\Editorial\EditorialPublisher;
use App\Services\Editorial\RepositoryCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

function editorialPublicBundle(string $topic = 'market-report'): array
{
    $story = cleanStory(['slug' => $topic]);
    $story['case_meta'] = ['project_name' => 'Monafy', 'context' => 'founder', 'technologies' => ['Laravel'], 'url' => 'https://monafy.com'];
    foreach (['tr', 'en'] as $lang) {
        $story[$lang]['meta_title'] = $story[$lang]['title'];
        $story[$lang]['meta_description'] = $lang === 'tr' ? 'Piyasa verilerini kaynaklarıyla bir araya getiren raporun teknik kararlarını ve yayın kontrollerini anlatıyorum.' : 'The engineering decisions and publication checks behind a report that brings market data together with its sources.';
    }

    return EditorialBundle::seal($topic, $story, hash('sha256', $topic));
}

it('imports bilingual posts atomically and returns the same result after a retry', function () {
    $bundle = editorialPublicBundle();
    $publisher = app(EditorialPublisher::class);
    $first = $publisher->import($bundle);
    $again = $publisher->import($bundle);
    expect($first['post_id'])->toBe($again['post_id'])->and(Post::count())->toBe(1)
        ->and(Post::first()->text('title', 'en'))->toBe($bundle['story']['en']['title']);
});

it('does not recreate a deleted publication or overwrite an edited post', function () {
    $bundle = editorialPublicBundle();
    $publisher = app(EditorialPublisher::class);
    $result = $publisher->import($bundle);
    $post = Post::findOrFail($result['post_id']);
    $post->update(['title' => 'An editor changed this title']);
    $publisher->import($bundle);
    expect($post->fresh()->title)->toBe('An editor changed this title');
    $post->delete();
    expect($publisher->import($bundle)['status'])->toBe('suppressed')->and(Post::count())->toBe(0);
});

it('serializes and enforces the rolling publication limit including deleted rows', function () {
    $publisher = app(EditorialPublisher::class);
    $first = $publisher->import(editorialPublicBundle('first-story'));
    $publisher->import(editorialPublicBundle('second-story'));
    Post::findOrFail($first['post_id'])->delete();
    expect(fn () => $publisher->import(editorialPublicBundle('third-story')))->toThrow(ValidationException::class);
    $this->travel(8)->days();
    expect($publisher->import(editorialPublicBundle('third-story'))['status'])->toBe('published');
});

it('rejects collisions without changing existing content and rolls back the ledger', function () {
    caseStudy(['slug' => 'market-report']);
    expect(fn () => app(EditorialPublisher::class)->import(editorialPublicBundle()))->toThrow(ValidationException::class);
    expect(DB::table('editorial_publications')->count())->toBe(0)->and(Post::count())->toBe(1);
});

it('rejects tampering, unsafe HTML, private paths and the retired market label', function () {
    foreach (['<script>alert(1)</script>', '<p>/Users/example/private.txt</p>', '<p>BIST ve Nasdaq için</p>', '<p><a href="javascript:alert(1)">bad</a></p>', '<p><a href="https://10.2.3.4/admin">private</a></p>', '<p>&#47;Users&#47;example&#47;private.txt</p>'] as $extra) {
        $bundle = editorialPublicBundle();
        $bundle['story']['tr']['content'] .= $extra;
        $bundle = EditorialBundle::seal($bundle['topic_key'], $bundle['story'], $bundle['source_fingerprint']);
        expect(fn () => app(EditorialPublisher::class)->import($bundle))->toThrow(ValidationException::class);
    }
    $bundle = editorialPublicBundle();
    $bundle['story']['en']['title'] = 'Tampered';
    expect(fn () => app(EditorialPublisher::class)->import($bundle))->toThrow(ValidationException::class);
    expect(Post::count())->toBe(0);
});

it('prevents a topic from being republished using a different bundle', function () {
    $publisher = app(EditorialPublisher::class);
    $publisher->import(editorialPublicBundle());
    $bundle = editorialPublicBundle();
    $bundle['story']['slug'] = 'another-slug';
    $bundle = EditorialBundle::seal($bundle['topic_key'], $bundle['story'], hash('sha256', 'different-evidence'));
    expect(fn () => $publisher->import($bundle))->toThrow(ValidationException::class);
    expect(Post::count())->toBe(1);
});

it('collects from the frozen merged ref rather than a stale checkout and ignores worktrees', function () {
    $root = sys_get_temp_dir().'/editorial-'.bin2hex(random_bytes(6));
    mkdir($root.'/service', 0700, true);
    $git = function (array $args) use ($root): string {
        return (new Process(['git', '-C', $root.'/service', ...$args]))->mustRun()->getOutput();
    };
    try {
        $git(['init', '-b', 'main']);
        $git(['config', 'user.email', 'editorial@example.test']);
        $git(['config', 'user.name', 'Editorial test']);
        file_put_contents($root.'/service/feature.php', '<?php // feature');
        file_put_contents($root.'/service/.env', 'SECRET=must-not-be-collected');
        $git(['add', '.']);
        $git(['commit', '-m', 'Add the report workflow']);
        $first = trim($git(['rev-parse', 'HEAD']));
        file_put_contents($root.'/service/feature.php', '<?php // updated');
        $git(['add', '.']);
        $git(['commit', '-m', 'Validate the report sources']);
        $latest = trim($git(['rev-parse', 'HEAD']));
        $git(['update-ref', 'refs/remotes/origin/main', $latest]);
        $git(['checkout', '--detach', $first]);
        mkdir($root.'/.worktrees');
        $git(['worktree', 'add', '--detach', $root.'/.worktrees/copy', $latest]);
        $collector = app(RepositoryCollector::class);
        $scan = $collector->collect($root, [], false);
        expect($scan['repositories'])->toHaveCount(1)
            ->and($scan['repositories'][0]['head'])->toBe($latest)
            ->and(json_encode($scan))->not->toContain('SECRET')->not->toContain('".env"');
        $historical = $collector->collect($root, [$scan['repositories'][0]['identity'] => $latest], false, '-1 day');
        expect($historical['repositories'][0]['commits'])->toHaveCount(2);
        $next = $collector->collect($root, [$scan['repositories'][0]['identity'] => $latest], false);
        expect($next['repositories'][0]['commits'])->toBe([]);
    } finally {
        (new Process(['rm', '-rf', $root]))->mustRun();
    }
});

it('identifies automatic publications without implying human editorial approval', function () {
    $bundle = editorialPublicBundle();
    $result = app(EditorialPublisher::class)->import($bundle);
    expect(Post::findOrFail($result['post_id'])->isAutomated())->toBeTrue();
    $this->get('/tr/vaka-incelemeleri/market-report')->assertOk()->assertSee('AI destekli yayın akışıyla hazırlandı');
});

it('rejects infrastructure addresses hidden in public metadata', function () {
    foreach (['https://[fd00::1]/admin', 'https://api.private.internal/path', 'https://apigw.monafy.com/private'] as $url) {
        $bundle = editorialPublicBundle();
        $bundle['story']['case_meta']['url'] = $url;
        $bundle = EditorialBundle::seal($bundle['topic_key'], $bundle['story'], $bundle['source_fingerprint']);
        expect(fn () => app(EditorialPublisher::class)->import($bundle))->toThrow(ValidationException::class);
    }
});

it('fingerprints the commit set independently of cited files and repository spelling', function () {
    $root = sys_get_temp_dir().'/editorial-proof-'.bin2hex(random_bytes(6));
    mkdir($root.'/service', 0700, true);
    $git = fn (array $args) => (new Process(['git', '-C', $root.'/service', ...$args]))->mustRun()->getOutput();
    try {
        $git(['init', '-b', 'main']);
        $git(['config', 'user.email', 'editorial@example.test']);
        $git(['config', 'user.name', 'Editorial test']);
        file_put_contents($root.'/service/feature.php', '<?php // one feature');
        file_put_contents($root.'/service/README.md', 'The same feature');
        $git(['add', '.']);
        $git(['commit', '-m', 'Add one feature with documentation']);
        $sha = trim($git(['rev-parse', 'HEAD']));
        $git(['update-ref', 'refs/remotes/origin/main', $sha]);
        config(['editorial.source_root' => $root]);
        $private = ['version' => 1, 'topic_key' => 'same-commit', 'story' => editorialPublicBundle()['story'], 'evidence' => [
            ['claim' => 'A documented feature in the same source commit.', 'sources' => [['repository' => 'service', 'commit' => $sha, 'path' => 'feature.php']]],
        ]];
        $first = app(EditorialBundle::class)->prepare($private);
        $private['evidence'][0]['sources'][0]['repository'] = './service';
        $private['evidence'][0]['sources'][0]['path'] = 'README.md';
        $second = app(EditorialBundle::class)->prepare($private);
        expect($second['source_fingerprint'])->toBe($first['source_fingerprint']);
    } finally {
        (new Process(['rm', '-rf', $root]))->mustRun();
    }
});

it('blocks publication before reading a bundle or contacting production until owner approval', function () {
    $this->app['env'] = 'local';
    config(['editorial.publish_enabled' => false]);

    $this->artisan('editorial:publish', ['file' => '/missing/unapproved-bundle.json'])
        ->expectsOutputToContain('Publication is disabled pending owner approval')
        ->assertFailed();

    expect(DB::table('editorial_publications')->count())->toBe(0);
});
