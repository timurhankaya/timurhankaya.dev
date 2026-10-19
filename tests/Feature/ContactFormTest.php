<?php

use App\Filament\Resources\ContactRequests\Pages\ListContactRequests;
use App\Filament\Resources\ContactRequests\Pages\ViewContactRequest;
use App\Models\ContactRequest;
use App\Models\User;
use App\Support\Seo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/** A valid submission as the form sends it, started long enough ago to pass the timing check. */
function contactPayload(array $overrides = []): array
{
    return array_replace([
        'topic' => 'project', 'name' => 'Ayşe Yılmaz', 'email' => 'ayse@example.com', 'company' => 'Örnek A.Ş.',
        'services' => ['saas'], 'budget' => '200.000 TL', 'timeline' => '1-3-months',
        'message' => 'Bayilerimiz için sipariş ve fatura süreçlerini toplayan bir panel istiyoruz.',
        'locale' => 'tr', 'website' => '', 'started' => encrypt(now()->subSeconds(30)->timestamp),
    ], $overrides);
}

it('shows the form on the contact page with the project topic chosen from the call to action', function () {
    $html = $this->get('/tr/iletisim?topic=project')->assertOk()->getContent();

    expect($html)->toContain('<form id="form"')->toContain('action="'.route('contact.store').'"')
        ->toMatch('/<input type="radio" name="topic" value="project" checked/')
        ->toContain('name="website"')->toContain('name="started"')->toContain('name="message"')
        ->toContain('Bilgilerini yalnızca bu mesaja dönmek için kullanırım');
});

it('stores a request and thanks the visitor in their language', function () {
    $this->post(route('contact.store'), contactPayload())
        ->assertRedirect(Seo::route('contact', [], 'tr').'#form')->assertSessionHas('contact_sent');
    $request = ContactRequest::sole();

    expect($request->only(['topic', 'name', 'email', 'company', 'budget', 'timeline', 'locale']))
        ->toBe(['topic' => 'project', 'name' => 'Ayşe Yılmaz', 'email' => 'ayse@example.com', 'company' => 'Örnek A.Ş.', 'budget' => '200.000 TL', 'timeline' => '1-3-months', 'locale' => 'tr'])
        ->and($request->services)->toBe(['saas'])->and($request->read_at)->toBeNull()
        ->and($request->ip_hash)->toHaveLength(64)->not->toContain('127.0.0.1')
        ->and($this->get('/tr/iletisim')->getContent())->toContain('Mesajın bana ulaştı.');
});

it('explains what is missing in the visitor’s language and keeps what they typed', function (string $locale, string $page, string $nameError) {
    $this->from($page)->post(route('contact.store'), contactPayload(['locale' => $locale, 'name' => '', 'email' => 'not-an-email', 'message' => 'kısa']))
        ->assertRedirect($page)->assertSessionHasErrors(['name', 'email', 'message']);

    expect($this->get($page)->getContent())->toContain($nameError)->toContain('value="Örnek A.Ş."');
    expect(ContactRequest::count())->toBe(0);
})->with([['tr', '/tr/iletisim', 'Adını yaz.'], ['en', '/contact', 'Tell me your name.']]);

it('drops bot submissions quietly without storing them', function (array $overrides) {
    $this->post(route('contact.store'), contactPayload($overrides))->assertRedirect()->assertSessionHas('contact_sent');

    expect(ContactRequest::count())->toBe(0);
})->with([
    // Closures, because encrypt() needs the booted application.
    'honeypot filled' => [fn () => ['website' => 'https://spam.example']],
    'sent within seconds' => [fn () => ['started' => encrypt(now()->timestamp)]],
    'forged timer' => [fn () => ['started' => 'not-encrypted']],
]);

it('limits how often one visitor can send, by the address Cloudflare reports', function () {
    foreach (range(1, 5) as $i) {
        $this->withHeader('CF-Connecting-IP', '203.0.113.7')->post(route('contact.store'), contactPayload(['email' => "a{$i}@example.com"]))->assertRedirect();
    }
    $this->withHeader('CF-Connecting-IP', '203.0.113.7')->post(route('contact.store'), contactPayload())->assertStatus(429);
    $this->withHeader('CF-Connecting-IP', '198.51.100.4')->post(route('contact.store'), contactPayload())->assertRedirect();

    expect(ContactRequest::count())->toBe(6);
});

it('sends the contact and project calls to action to the form', function () {
    $case = caseStudy(['slug' => 'cta-case']);
    $home = $this->get('/tr')->assertOk()->getContent();
    $caseHtml = $this->get($case->publicUrl('en'))->assertOk()->getContent();

    expect($home)->toContain('href="'.Seo::route('contact', [], 'tr').'#form"')
        ->toContain('href="'.Seo::route('contact', ['topic' => 'project'], 'tr').'#form"')
        ->not->toContain('mailto:kayacekovic@gmail.com?subject=')
        ->and($caseHtml)->toContain('href="'.Seo::route('contact', ['topic' => 'project'], 'en').'#form"');
});

it('lists requests for the administrator, newest first, and marks one read when opened', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $older = ContactRequest::create(['topic' => 'other', 'name' => 'Eski', 'email' => 'old@example.com', 'message' => 'Daha önce gönderilmiş bir mesaj.', 'locale' => 'en']);
    $older->forceFill(['created_at' => now()->subDay()])->save();
    $newer = ContactRequest::create(['topic' => 'project', 'name' => 'Yeni', 'email' => 'new@example.com', 'message' => 'Yeni gelen bir proje talebi.', 'locale' => 'tr']);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(ListContactRequests::class)->assertCanSeeTableRecords([$newer, $older], inOrder: true);
    Livewire::test(ViewContactRequest::class, ['record' => $newer->id])->assertOk()->assertSee('Yeni gelen bir proje talebi.');
    expect($newer->fresh()->read_at)->not->toBeNull()->and($older->fresh()->read_at)->toBeNull();
});

it('keeps contact requests away from anyone who is not an administrator', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());

    Livewire::test(ListContactRequests::class)->assertForbidden();
});
