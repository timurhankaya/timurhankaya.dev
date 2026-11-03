<?php

use App\Services\Editorial\EditorialBundle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

function evidenceTestStory(): array
{
    $story = cleanStory(['slug' => 'evidence-test']);
    $story['case_meta'] = ['project_name' => 'Monafy', 'context' => 'founder', 'technologies' => ['Laravel']];
    foreach (['tr', 'en'] as $locale) {
        $story[$locale]['meta_title'] = $story[$locale]['title'];
        $story[$locale]['meta_description'] = 'A sufficiently detailed description of the engineering decisions and the documented source material.';
    }

    return $story;
}

it('validates an anonymous article with evidence from an explicitly configured repository', function () {
    $root = sys_get_temp_dir().'/editorial-external-'.bin2hex(random_bytes(6));
    mkdir($root.'/sources', 0700, true);
    mkdir($root.'/adem', 0700, true);
    $git = fn (array $args) => (new Process(['git', '-C', $root.'/adem', ...$args]))->mustRun()->getOutput();
    try {
        $git(['init', '-b', 'main']);
        $git(['config', 'user.email', 'test@example.test']);
        $git(['config', 'user.name', 'Test']);
        file_put_contents($root.'/adem/README.md', 'Verified source');
        $git(['add', 'README.md']);
        $git(['commit', '-m', 'Initial source']);
        $sha = trim($git(['rev-parse', 'HEAD']));
        $git(['update-ref', 'refs/remotes/origin/main', $sha]);
        config(['editorial.source_root' => $root.'/sources', 'editorial.evidence_repositories' => ['adem' => $root.'/adem', 'adem-alias' => $root.'/adem']]);
        $story = evidenceTestStory();
        $story['type'] = 'article';
        $story['project_key'] = null;
        unset($story['case_meta']);
        $private = ['version' => 1, 'topic_key' => 'adem-intro', 'story' => $story, 'evidence' => [
            ['claim' => 'The source documents the agent workflow.', 'sources' => [['repository' => 'adem', 'commit' => $sha, 'path' => 'README.md']]],
        ]];
        $first = app(EditorialBundle::class)->prepare($private);
        expect($first['story']['project_key'])->toBeNull();
        $private['evidence'][0]['sources'][0]['repository'] = 'adem-alias';
        expect(app(EditorialBundle::class)->prepare($private)['source_fingerprint'])->toBe($first['source_fingerprint']);
        $private['evidence'][0]['sources'][0]['repository'] = '../adem';
        expect(fn () => app(EditorialBundle::class)->prepare($private))->toThrow(ValidationException::class);
        $private['evidence'][0]['sources'][0]['repository'] = 'adem';
        $private['evidence'][0]['sources'][0]['path'] = '.env';
        expect(fn () => app(EditorialBundle::class)->prepare($private))->toThrow(ValidationException::class);
        $private['evidence'][0]['sources'][0]['path'] = 'README.md';
        $private['evidence'][0]['sources'][0]['commit'] = str_repeat('0', 40);
        expect(fn () => app(EditorialBundle::class)->prepare($private))->toThrow(ValidationException::class);
    } finally {
        (new Process(['rm', '-rf', $root]))->mustRun();
    }
});

it('does not accept an unrecognised project or an anonymous case study', function () {
    $story = evidenceTestStory();
    $story['project_key'] = 'unknown';
    $bundle = EditorialBundle::seal('unknown-project', $story, hash('sha256', 'source'));
    expect(fn () => app(EditorialBundle::class)->validatePublic($bundle))->toThrow(ValidationException::class);
    $story['project_key'] = null;
    $bundle = EditorialBundle::seal('anonymous-case', $story, hash('sha256', 'source'));
    expect(fn () => app(EditorialBundle::class)->validatePublic($bundle))->toThrow(ValidationException::class);
});
