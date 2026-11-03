<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders case study cards without covers while retaining the detail cover', function () {
    caseStudy(['slug' => 'with-cover', 'cover_image' => '/images/example-cover.png']);
    caseStudy(['slug' => 'without-cover', 'project_key' => 'b2b-saas', 'cover_image' => null]);

    foreach (['/case-studies', '/tr/vaka-incelemeleri'] as $url) {
        $html = mainContent($this->get($url)->assertOk()->getContent());

        expect($html)->toContain('with-cover', 'without-cover', 'brand-story-copy')
            ->not->toContain('brand-story-cover', '/images/example-cover.png');
    }

    $this->get('/case-studies/with-cover')->assertOk()->assertSee('/images/example-cover.png');
});
