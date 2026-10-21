<?php

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('reports available capacity without creating a publication', function () {
    $this->travelTo(now()->startOfSecond());
    config(['editorial.weekly_limit' => 2]);

    expect(Artisan::call('editorial:status'))->toBe(0);
    $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($result['remaining'])->toBe(2)
        ->and($result['next_slot_at'])->toBeNull()
        ->and(DB::table('editorial_publications')->count())->toBe(0);
});

it('counts removed publications and waits until enough batch entries expire', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-21 09:00:00 UTC'));
    config(['editorial.weekly_limit' => 2]);
    foreach (['2026-10-18 10:00:00', '2026-10-19 10:00:00', '2026-10-20 10:00:00', '2026-10-20 11:00:00'] as $i => $created) {
        DB::table('editorial_publications')->insert([
            'topic_key' => 'batch-'.$i, 'bundle_id' => hash('sha256', 'bundle-'.$i),
            'source_fingerprint' => hash('sha256', 'source-'.$i),
            'post_id' => null, 'created_at' => $created, 'updated_at' => $created,
        ]);
    }

    expect(Artisan::call('editorial:status'))->toBe(0);
    $result = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    expect($result['recent_publications'])->toBe(4)
        ->and($result['remaining'])->toBe(0)
        ->and($result['next_slot_at'])->toBe('2026-10-27T10:00:00+00:00');

    $this->travelTo(CarbonImmutable::parse('2026-10-27 10:00:00 UTC'));
    expect(Artisan::call('editorial:status'))->toBe(0);
    expect(json_decode(Artisan::output(), true)['remaining'])->toBe(1);
});

it('refuses a remote status request with invalid connection settings', function () {
    $this->app['env'] = 'local';
    config(['editorial.ssh_host' => '-invalid']);
    $this->artisan('editorial:status', ['--remote' => true])->assertFailed();
});
