<?php

use App\Services\Portfolio\ProjectCatalog;
use Database\Seeders\StorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

it('describes current work and Monafy without a hobby paragraph', function () {
    $en = $this->get('/')->assertOk()->getContent();
    $tr = $this->get('/tr')->assertOk()->getContent();

    $trAbout = Str::betweenFirst($tr, '<div class="brand-about-copy">', '<div id="skills"');
    $enAbout = Str::betweenFirst($en, '<div class="brand-about-copy">', '<div id="skills"');

    expect($trAbout)->toContain('Monafy’nin kurucu ortağı olarak Türkiye ve ABD borsalarına yönelik yapay zekâ destekli araştırma araçlarının mimarisini tasarlıyor ve geliştirme sürecini yönetiyorum.')
        ->not->toContain('%0,01')->not->toContain('Cloudflare')
        ->toContain('Wingie Enuygun Group’ta sigorta ürünleri geliştiriyorum.')->not->toContain('İş dışında da üretmeyi seviyorum')
        ->not->toContain('finansal veri ve yapay zekâyla çalıştım')->not->toContain('arkadaşlarımla oynamak için yaptığım oyunlar')
        ->and($enAbout)->toContain('As a co-founder of Monafy, I design the architecture and lead development')->not->toContain('0.01%')
        ->toContain('I build insurance products at Wingie Enuygun Group.')->not->toContain('I like making things outside work')
        ->not->toContain('Away from the screen');
    expect(substr_count($trAbout, '<p>'))->toBe(2)->and(substr_count($enAbout, '<p>'))->toBe(2);
});

it('leaves employment markers out of the CV project list', function () {
    expect($this->get('/tr/ozgecmis')->assertOk()->getContent())->not->toContain('Maaşlı çalışma')
        ->and($this->get('/cv')->assertOk()->getContent())->not->toContain('>Employment<');
});

it('keeps Turkish badge words out of the English pages', function () {
    expect($this->get('/')->assertOk()->getContent().$this->get('/cv')->getContent())
        ->not->toContain('>Topluluk<')->not->toContain('>Kurumsal<')->not->toContain('>E-Ticaret<');
});

it('keeps the awards on the CV and adrenaline sports off the CV and home page', function () {
    $html = $this->get('/cv')->assertOk()->getContent().$this->get('/tr/ozgecmis')->assertOk()->getContent();

    expect($html)->toContain('Ödüller')->not->toContain('Adrenalin')->not->toContain('Adrenaline')
        ->and($this->get('/tr')->getContent())->not->toContain('Adrenalin', 'Yılın Sigorta Girişimi');
});

it('offers six ways to work together, ending with a partnership on a new idea', function () {
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect(substr_count($tr, '<article class="brand-service">'))->toBe(6)
        ->and($tr)->toContain('Mobil uygulamalar')->toContain('ERP &amp; CRM')->toContain('Teknik ortaklık');
});

it('keeps the current job description modest and drops the experience intro line', function () {
    $home = $this->get('/')->assertOk()->getContent();
    $html = $home.$this->get('/cv')->getContent().$this->get('/tr/ozgecmis')->getContent();
    $hero = Str::betweenFirst($home, 'class="brand-hero"', '</header>');
    $experience = Str::betweenFirst($home, 'class="brand-experience-list"', '</section>');

    expect($html)->not->toContain('Architecting')->not->toContain('next-gen')->not->toContain('mikroservis mimarileri')
        ->not->toContain('From my first projects in 2019')
        ->and($hero)->toContain('View my CV')
        ->and($experience)->not->toContain('View my CV');
});

it('shows the games without case study links and names the prerender product Dyno Render', function () {
    caseStudy(['slug' => 'game-story', 'project_key' => 'vampir-koylu', 'case_meta' => ['project_name' => 'Vampir Köylü', 'context' => 'personal', 'period' => '2026']]);
    $home = $this->get('/')->assertOk()->getContent();

    expect($home)->not->toContain('brand-game-story')
        ->and($home)->toContain('Dynorender')->not->toContain('Moneo Render');
});

it('labels the brand logos with a short heading on the home page', function () {
    $html = $this->get('/tr')->assertOk()->getContent();

    expect($html)->toContain('class="brand-logos" aria-labelledby="brand-logos-title"')
        ->toContain('<span>Şirketler ve ürünler</span>')
        ->toContain('/images/logos/monafy.svg')->toContain('/images/logos/moneo.svg')->toContain('/images/logos/sistemglobal.png')->toContain('/images/logos/basefy.png')
        ->toContain('/images/logos/smart-global.svg')->toContain('/images/logos/edanisman.png')
        ->not->toContain('/images/logos/transiyzi.svg')->not->toContain('/images/logos/dynorender.png')
        // The old wording read like a client list.
        ->not->toContain('Birlikte çalıştığım markalar');
    foreach (['monafy.svg', 'moneo.svg', 'sistemglobal.png', 'smart-global.svg', 'edanisman.png', 'basefy.png'] as $file) {
        expect(public_path('images/logos/'.$file))->toBeFile();
    }
});

it('adds ADEM and Toyka to the logo strip with transparent logos', function () {
    $tile = fn (string $html, string $file): string => preg_match('~<li>(?:(?!</li>).)*?/images/logos/'.preg_quote($file, '~').'(?:(?!</li>).)*</li>~s', $html, $match) ? $match[0] : '';

    // Before the case study is published the ADEM tile shows its logo without a link.
    $html = $this->get('/')->assertOk()->getContent();
    expect($tile($html, 'adem.png'))->toContain('<span class="brand-logo-tile" title="ADEM">')->not->toContain('href=')
        ->and($tile($html, 'toyka.png'))->toContain('href="https://toyka.com.tr" target="_blank" rel="noopener"')->toContain('alt="Toyka"');

    $this->seed(StorySeeder::class);
    foreach (['en' => '/', 'tr' => '/tr'] as $locale => $path) {
        $url = app(ProjectCatalog::class)->caseStudyUrl('adem', $locale);
        $adem = $tile($this->get($path)->assertOk()->getContent(), 'adem.png');
        expect($url)->not->toBeNull()
            ->and($adem)->toContain('href="'.$url.'"')->not->toContain('target="_blank"')->toContain('alt="ADEM"');
    }

    foreach (['adem.png', 'toyka.png'] as $file) {
        $image = imagecreatefrompng(public_path('images/logos/'.$file));
        // A transparent corner keeps the white silhouette filter from drawing a box around the logo.
        expect((imagecolorat($image, 0, 0) >> 24) & 0x7F)->toBe(127);
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

    expect(Str::betweenFirst($tr, '<p class="brand-hero-intro">', '</p>'))->toBe('Merhaba, ben Timurhan. Yazılım ürünleri ve yapay zekâ sistemleri geliştiriyorum.')
        ->and(Str::betweenFirst($en, '<p class="brand-hero-intro">', '</p>'))->toBe('Hi, I’m Timurhan. I build software products and AI systems.');

    expect($tr)->toContain('Bir fikri ürüne dönüştürmek.')
        ->toContain('Monafy’nin kurucu ortağı olarak')
        ->toContain('Web teknolojilerindeki gelişmeleri ve yapay zekânın ürün geliştirmeyi nasıl değiştirdiğini takip ediyorum.')
        ->toContain('Bir ürünün günlük kullanımını ve zaman içinde nasıl geliştirileceğini birlikte düşünürüm.')
        ->toContain('Derin düşünme')->toContain('İnce işçilik')
        ->not->toContain('Özenli mühendislik, kalıcı ürünler.')
        ->not->toContain('Boş zamanlarımda arkadaşlarla')->not->toContain('Kendi ürünlerim, müşteri işleri')
        ->not->toContain('brand-craft-line')->not->toContain('Finans ve yeni iş fikirleriyle')->not->toContain('İnce işçilik, güzel ürünler.')
        ->not->toContain('Ürünü bir bütün olarak düşünürüm.')->not->toContain('sahada, su altında')
        ->not->toContain('Kod · Ürün · Merak')->not->toContain('beni en çok heyecanlandıran')
        ->not->toContain('arkasındaki case study')
        ->and($en)->toContain('Turning an idea into a product.')->toContain('As a co-founder of Monafy,')
        ->toContain('I follow developments in web technology and how AI is changing the way products are built.')
        ->toContain('Deep thinking')->toContain('Careful craft')
        ->not->toContain('Careful engineering, lasting products.')
        ->not->toContain('small games to play with friends')->not->toContain('My own products, client work')
        ->not->toContain('Fine craft, beautiful products.')
        ->not->toContain('I think about the whole product.')->not->toContain('Code · Products · Curiosity')
        ->not->toContain('what I enjoy most')->not->toContain('links the case studies behind it')
        ->and($this->get('/tr/llms.txt')->assertOk()->getContent())->not->toContain('brand.work_intro')->not->toContain('Kendi ürünlerim');
});

it('describes the AI-assisted development approach and review before release', function () {
    $en = $this->get('/')->assertOk()->getContent();
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect($tr)->toContain('Hızlı üretim')->toContain('Değişiklikleri yayına almadan önce inceler ve test ederim.')
        ->not->toContain('Yapay zekâyla hız')->not->toContain('fikirleri hızlı denemek')
        ->and($en)->toContain('Fast delivery')->toContain('I review and test changes before release.')
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

it('presents AI work and experience in both languages', function () {
    $en = $this->get('/')->assertOk()->getContent().$this->get('/cv')->assertOk()->getContent();
    $tr = $this->get('/tr')->assertOk()->getContent().$this->get('/tr/ozgecmis')->assertOk()->getContent();

    expect($tr)->toContain('<li>Sigorta alanında yapay zekâ uygulamaları geliştiriyorum</li>')
        ->toContain('<li>Şirket içi AI-DLC çalışmalarına katkıda bulunuyorum</li>')
        ->toContain('Genç geliştiricilere mentorluk yaptım')
        ->not->toContain('Kommunity, Transiyzi ve Dynorender gibi SaaS ürünlerinde')
        ->and($en)->toContain('<li>Develop AI applications for insurance</li>')
        ->toContain('<li>Contribute to the company’s AI-DLC initiatives</li>')
        ->toContain('Mentored junior developers')
        ->not->toContain('SaaS products such as Kommunity, Transiyzi and Dynorender');
});

it('places services before games in both languages', function (string $path, string $services, string $games) {
    $html = $this->get($path)->assertOk()->getContent();
    expect(strpos($html, $services))->not->toBeFalse()
        ->and(strpos($html, $games))->not->toBeFalse()
        ->and(strpos($html, $services))->toBeLessThan(strpos($html, $games));
})->with([['/', 'Let’s work together.', 'Made for game night.'], ['/tr', 'Birlikte çalışalım.', 'Oyun geceleri için.']]);

it('keeps the games section short and descriptive', function () {
    $tr = $this->get('/tr')->assertOk()->getContent();

    expect($tr)->toContain('Oyun geceleri için.')
        ->toContain('Aynı odadayken telefonlarınızdan oynayabileceğiniz oyunlar.')
        ->toContain('Kelimeyi açık etmeden ipuçları verin.')
        ->not->toContain('Bir kelime, masada bir Imposter.')
        ->toContain('Geceyi oyun yönetir, moderatör gerekmez.')
        ->not->toContain('kelimeyi bilmeyeni oylayın')->not->toContain('Boş zamanlarımda yaptığım')
        ->not->toContain('Herkes aynı kelimeyi bilir, biri hariç.')->not->toContain('gündüz köy oylamayla şüpheliyi seçer')
        ->not->toContain('İşin bir de oyun tarafı var.')->not->toContain('bulabilir misin?')
        ->not->toContain('karar vermelisin');
});
