<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function desktopNav(string $html): string
{
    preg_match('~<nav class="brand-nav-links".*?</nav>~s', $html, $match);

    return $match[0] ?? '';
}

it('groups blog and case study under one writing menu that opens two separate pages', function () {
    $nav = desktopNav($this->get('/tr')->assertOk()->getContent());

    expect($nav)->toContain('aria-controls="brand-writing-menu"')->toContain('aria-expanded="false"')->toContain('>Yazılar')
        ->toContain('href="'.url('/tr/yazilar').'"')->toContain('href="'.url('/tr/vaka-incelemeleri').'"')
        ->toContain('Blog')->toContain('Case study');
});

it('marks the open section inside the writing menu', function () {
    $nav = desktopNav($this->get('/writing')->assertOk()->getContent());

    expect($nav)->toMatch('~<a [^>]*href="'.preg_quote(url('/writing'), '~').'"[^>]*aria-current="page"~')
        ->toContain('data-current');
});

it('drops the blog and case study switch from the listing headers', function (string $path, string $other) {
    preg_match('~<header class="brand-page-header brand-writing-header">.*?</header>~s', $this->get($path)->assertOk()->getContent(), $header);

    expect($header[0] ?? '')->not->toContain('href="'.url($other).'"');
})->with([['/writing', '/case-studies'], ['/case-studies', '/writing']]);
