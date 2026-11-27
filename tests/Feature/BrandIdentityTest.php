<?php

use App\Support\StructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('presents the localized AI and cofounder identity consistently', function () {
    $this->get('/')->assertOk()->assertSee('Software &amp; AI Developer', false)->assertSee('Co-founder');
    $this->get('/tr')->assertOk()->assertSee('Yazılım ve Yapay Zekâ Geliştiricisi')->assertSee('Kurucu ortak');
    expect(StructuredData::person('en')['jobTitle'])->toBe('Software & AI Developer')
        ->and(StructuredData::person('tr')['jobTitle'])->toBe('Yazılım ve Yapay Zekâ Geliştiricisi');
});

it('describes Monafy with the approved market names in both languages', function () {
    foreach (['tr' => 'Türkiye ve ABD borsaları', 'en' => 'Turkish and US stock markets'] as $locale => $phrase) {
        $content = json_decode(file_get_contents(resource_path("content/portfolio.$locale.json")), true);
        $project = collect($content['projects']['sections'])->flatMap(fn ($s) => $s['items'])->firstWhere('key', 'monafy');
        expect($project['description'])->toContain($phrase)->not->toContain('Nasdaq');
    }
});
