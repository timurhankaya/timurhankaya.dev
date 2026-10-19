<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

it('tells how Monafy was built and drops the hobby paragraph', function () {
    $en = $this->get('/')->assertOk()->getContent();
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect($tr)->toContain('kodu elle yazmadan yapay zekâyla geliştirdim')->not->toContain('%0,01')->not->toContain('Cloudflare')
        ->toContain('Wingie Enuygun Group’ta yüksek trafikli sigorta ürünleri üzerinde çalışıyorum.')->not->toContain('İş dışında da üretmeyi seviyorum')
        ->not->toContain('finansal veri ve yapay zekâyla çalıştım')->not->toContain('arkadaşlarımla oynamak için yaptığım oyunlar')
        ->and($en)->toContain('without writing the code by hand')->not->toContain('0.01%')
        ->toContain('At Wingie Enuygun Group, I work on high-traffic insurance products.')->not->toContain('I like making things outside work')
        ->not->toContain('Away from the screen');
    expect(preg_match('~<div class="brand-about-copy">\s*(?:<p>[^<]+</p>\s*)*<p>\s*</p>~', $tr))->toBe(0);
});

it('leaves employment markers out of the CV project list', function () {
    expect($this->get('/tr/ozgecmis')->assertOk()->getContent())->not->toContain('Maaşlı çalışma')
        ->and($this->get('/cv')->assertOk()->getContent())->not->toContain('>Employment<');
});

it('keeps Turkish badge words out of the English pages', function () {
    expect($this->get('/')->assertOk()->getContent().$this->get('/cv')->getContent())
        ->not->toContain('>Topluluk<')->not->toContain('>Kurumsal<')->not->toContain('>E-Ticaret<');
});

it('leaves recognition and adrenaline sports out of the CV and the home page', function () {
    $html = $this->get('/cv')->assertOk()->getContent().$this->get('/tr/ozgecmis')->assertOk()->getContent();

    expect($html)->not->toContain('Başarılar')->not->toContain('recognition-item')->not->toContain('Adrenalin')->not->toContain('Adrenaline')
        ->and($this->get('/tr')->getContent())->not->toContain('Adrenalin');
});

it('offers six ways to work together, ending with a partnership on a new idea', function () {
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect(substr_count($tr, '<article class="brand-service">'))->toBe(6)
        ->and($tr)->toContain('Mobil uygulamalar')->toContain('ERP &amp; CRM')->toContain('Ortaklık');
});

it('keeps the current job description modest and drops the experience intro line', function () {
    $html = $this->get('/')->getContent().$this->get('/cv')->getContent().$this->get('/tr/ozgecmis')->getContent();

    expect($html)->not->toContain('Architecting')->not->toContain('next-gen')->not->toContain('mikroservis mimarileri')
        ->not->toContain('From my first projects in 2019');
});

it('shows the games without case study links and names the prerender product Dyno Render', function () {
    caseStudy(['slug' => 'game-story', 'project_key' => 'vampir-koylu', 'case_meta' => ['project_name' => 'Vampir Köylü', 'context' => 'personal', 'period' => '2026']]);
    $home = $this->get('/')->assertOk()->getContent();

    expect($home)->not->toContain('brand-game-story')->not->toContain('eDanışman')
        ->and($home)->toContain('Dynorender')->not->toContain('Moneo Render');
});

it('shows the brand logos on the home page without a heading', function () {
    $html = $this->get('/tr')->assertOk()->getContent();

    expect($html)->toContain('class="brand-logos"')
        ->toContain('/images/logos/monafy.svg')->toContain('/images/logos/moneo.svg')->toContain('/images/logos/sistemglobal.png')->toContain('/images/logos/basefy.png')
        ->toContain('brand-logo-wordmark">Smart Global<')
        ->not->toContain('/images/logos/transiyzi.svg')->not->toContain('/images/logos/dynorender.png')
        ->not->toContain('>Birlikte çalıştığım markalar<');
    foreach (['monafy.svg', 'moneo.svg', 'sistemglobal.png', 'basefy.png'] as $file) {
        expect(public_path('images/logos/'.$file))->toBeFile();
    }
});

it('starts every breadcrumb with Home instead of the site name', function (string $path, string $home) {
    $html = $this->get($path)->assertOk()->getContent();
    preg_match('~<nav class="brand-breadcrumbs".*?</nav>~s', $html, $nav);

    expect($nav[0] ?? '')->toContain('>'.$home.'<')->not->toContain('Timurhan Kaya')
        ->and($html)->toContain('"position":1,"name":"'.$home.'"');
})->with([['/tr/vaka-incelemeleri', 'Ana sayfa'], ['/case-studies', 'Home'], ['/tr/iletisim', 'Ana sayfa'], ['/tr/oyunlar', 'Ana sayfa'], ['/writing', 'Home']]);

it('keeps the site copy short and plain', function () {
    $en = $this->get('/')->assertOk()->getContent();
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect($tr)->toContain('Ürün geliştiriyorum.')
        ->toContain('Aynı zamanda Monafy’nin kurucu ortağıyım.')
        ->toContain('İstanbul’da yaşıyorum. Yazılımın yanı sıra finans piyasalarını ve girişimciliği takip ediyorum.')
        ->toContain('İnce işçilikle yapılmış, güzel ve kullanması kolay ürünler geliştiririm.')
        ->toContain('Özenli mühendislik, kalıcı ürünler.')
        ->not->toContain('Boş zamanlarımda arkadaşlarla')->not->toContain('Kendi ürünlerim, müşteri işleri')
        ->not->toContain('brand-craft-line')->not->toContain('Finans ve yeni iş fikirleriyle')->not->toContain('İnce işçilik, güzel ürünler.')
        ->not->toContain('Ürünü bir bütün olarak düşünürüm.')->not->toContain('sahada, su altında')
        ->not->toContain('Kod · Ürün · Merak')->not->toContain('Derin düşünme')->not->toContain('beni en çok heyecanlandıran')
        ->not->toContain('arkasındaki case study')
        ->and($en)->toContain('I build products.')->toContain('I also co-founded Monafy.')
        ->toContain('Based in Istanbul. Beyond software, I follow financial markets and startups.')
        ->toContain('Careful engineering, lasting products.')
        ->not->toContain('small games to play with friends')->not->toContain('My own products, client work')
        ->not->toContain('Fine craft, beautiful products.')
        ->not->toContain('I think about the whole product.')->not->toContain('Code · Products · Curiosity')
        ->not->toContain('what I enjoy most')->not->toContain('links the case studies behind it')
        ->and($this->get('/tr/llms.txt')->assertOk()->getContent())->not->toContain('brand.work_intro')->not->toContain('Kendi ürünlerim');
});

it('describes building with AI agents the way I actually work', function () {
    $en = $this->get('/')->assertOk()->getContent();
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect($tr)->toContain('Yapay zekâyla geliştirme')->toContain('Kodu yapay zekâ ajanlarıyla yazıyorum.')
        ->not->toContain('Yapay zekâyla hız')->not->toContain('fikirleri hızlı denemek')
        ->and($en)->toContain('Building with AI')->toContain('I write code with AI agents.')
        ->not->toContain('Faster with AI')->not->toContain('to try ideas quickly');
});

it('leaves the social links to the footer instead of repeating them in the contact section', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $contact = Str::between($html, 'class="brand-section brand-contact"', '</section>');

    expect($contact)->toContain('brand-contact-bottom')->not->toContain('brand-socials')
        ->not->toContain('linkedin.com')->not->toContain('github.com')->not->toContain('mailto:')
        ->and(Str::after($html, '<footer class="brand-footer">'))->toContain('linkedin.com/in/timurhan-kaya')->toContain('github.com/timurhankaya');
});

it('sets the about column text and chips at a readable size', function () {
    $css = file_get_contents(resource_path('css/brand.css'));
    $size = function (string $selector) use ($css): int {
        preg_match('/^'.preg_quote($selector, '/').'\s*\{[^}]*font-size:\s*(\d+)px/m', $css, $match);

        return (int) ($match[1] ?? 0);
    };

    expect($size('.brand-about-caption'))->toBeGreaterThanOrEqual(15)
        ->and($size('.brand-hobbies span'))->toBeGreaterThanOrEqual(13)
        ->and($size('.brand-about-focus > p:not(.brand-label)'))->toBeGreaterThanOrEqual(15)
        ->and($size('.brand-about-focus .brand-text-link'))->toBeGreaterThanOrEqual(14);
});

it('words the experience bullets plainly, without claiming to have led', function () {
    $tr = $this->get('/tr')->assertOk()->getContent().$this->get('/tr/ozgecmis')->assertOk()->getContent();
    $en = $this->get('/cv')->assertOk()->getContent();

    expect($tr)->toContain('Binlerce aktif kullanıcısı olan ödüllü sigorta platformlarını geliştirdim')
        ->toContain('Kıdemli geliştiricilerin yanında canlı projelere katkı verdim')
        ->not->toContain('öncülük ettim')->not->toContain('yaşam döngüsünü yönettim')->not->toContain('gerçek dünya projeleriyle')
        ->not->toContain('best practice')->not->toContain('öğrenerek hızlandım')
        ->and($en)->toContain('Built award-winning insurance platforms with thousands of active users')
        ->not->toContain('Led the development')->not->toContain('Managed the lifecycle')->not->toContain('real-world projects');
});

it('keeps the games section short and descriptive', function () {
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect($tr)->toContain('Oyun geceleri için.')
        ->toContain('Aynı odadaki arkadaşlarınla telefondan oynayacağın çok oyunculu oyunlar.')
        ->toContain('Herkes gizli kelimeyi görür, Imposter görmez.')
        ->toContain('Oyunu sistem yönettiği için moderatöre gerek kalmaz.')
        ->not->toContain('kelimeyi bilmeyeni oylayın')->not->toContain('Boş zamanlarımda yaptığım')
        ->not->toContain('Herkes aynı kelimeyi bilir, biri hariç.')->not->toContain('gündüz köy oylamayla şüpheliyi seçer')
        ->not->toContain('İşin bir de oyun tarafı var.')->not->toContain('bulabilir misin?')
        ->not->toContain('karar vermelisin');
});
