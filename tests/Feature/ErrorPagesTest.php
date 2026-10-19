<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

/** The page the exception handler renders for a status; the catch-all route would answer a test URL with 404 first. */
function statusPage(int $code): Response
{
    return app(ExceptionHandler::class)->render(Request::create('/'), new HttpException($code));
}

it('shows the branded not-found page with the code, ways back and noindex', function () {
    $html = $this->get('/tr/olmayan-bir-sayfa')->assertNotFound()->getContent();

    expect($html)->toContain('class="brand-error-code"')->toContain('>404<')->toContain('Bu sayfa burada yok.')
        ->toContain('noindex')->toContain('brand-error-links')->toContain('href="'.url('/tr/yazilar').'"');
});

it('gives every other status its own branded page that works without the database', function (int $code, string $title) {
    $response = statusPage($code);
    $html = $response->getContent();

    expect($response->getStatusCode())->toBe($code);

    expect($html)->toContain('class="brand-error-code"')->toContain('>'.$code.'<')->toContain($title)
        ->toContain('<meta name="robots" content="noindex, nofollow">')->toContain('tkaya<span>.</span>')
        ->not->toContain('command-palette');
})->with([
    [403, 'This page is not public.'],
    [419, 'This page has expired.'],
    [429, 'Too many requests.'],
    [500, 'Something went wrong.'],
    [503, 'Back in a few minutes.'],
]);

it('points a visitor stopped by the form limit to email instead', function () {
    expect(statusPage(429)->getContent())->toContain('mailto:kayacekovic@gmail.com');
});
