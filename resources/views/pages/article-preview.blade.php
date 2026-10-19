@php
    $seo = ['title' => __('brand.example_title'), 'description' => __('brand.example_intro'), 'noindex' => true];
    $tr = app()->getLocale() === 'tr';
@endphp
@extends('layouts.app')
@section('title', $seo['title'])
@section('content')
    <header class="brand-case-header brand-article-header">
        <div class="brand-case-breadcrumb"><a class="brand-text-link" href="{{ \App\Support\Seo::route('blog.index') }}"><x-arrow dir="w" /> {{ __('brand.all_writing') }}</a><span class="brand-label">{{ __('brand.case_preview') }}</span></div>
        <p class="brand-label brand-case-eyebrow">{{ __('brand.type_example') }} / HTML</p>
        <h1 class="brand-display">{{ __('brand.example_title') }}</h1>
        <p class="brand-case-summary">{{ __('brand.example_intro') }}</p>
    </header>
    <div class="brand-article-layout">
        <aside class="brand-article-aside">
            <p class="brand-label">{{ __('brand.contents') }}</p>
            <nav class="brand-case-toc" aria-label="{{ __('brand.contents') }}">
                @foreach (($tr ? ['Metin & başlıklar', 'Alıntılar & listeler', 'Kod & tablolar', 'Görseller', 'Detaylar & veriler', 'Form elemanları'] : ['Text & headings', 'Quotes & lists', 'Code & tables', 'Images', 'Details & data', 'Form elements']) as $label)<a href="#sample-{{ $loop->iteration }}"><span>0{{ $loop->iteration }}</span>{{ $label }}</a>@endforeach
            </nav>
        </aside>
        <article class="brand-prose">
            <section id="sample-1">
                <p class="brand-prose-lead">{{ $tr ? 'Bir ürünü ilk kez açtığında seni karşılayan şey yalnızca arayüz değildir. Kararların ne kadar düşünülmüş olduğu, akışın nasıl kurulduğu ve ayrıntılara verilen emek de görünür olur.' : 'The first time you open a product, you meet more than its interface. You notice how carefully decisions were made, how the flow works, and the attention given to small details.' }}</p>
                <h2>{{ $tr ? 'Önce doğru soruyu sormak' : 'Start with the right question' }}</h2>
                <p>{{ $tr ? 'İyi bir çözüm, problemi anlamakla başlar.' : 'A good solution starts with understanding the problem.' }} <strong>{{ $tr ? 'Derin düşünme' : 'Deep thinking' }}</strong>, <em>{{ $tr ? 'merak' : 'curiosity' }}</em> {{ $tr ? 've' : 'and' }} <a href="{{ \App\Support\Anchors::home('about') }}">{{ $tr ? 'ince işçilik' : 'careful craft' }}</a> {{ $tr ? 'bu sürecin parçalarıdır.' : 'all play a part.' }}</p>
                <p><mark>{{ $tr ? 'Küçük ayrıntılar güven oluşturur.' : 'Small details build trust.' }}</mark> <del>{{ $tr ? 'Yalnızca çalışması yeterli.' : 'It just needs to work.' }}</del> <ins>{{ $tr ? 'Anlaşılır ve sürdürülebilir olması da gerekir.' : 'It also needs to be clear and maintainable.' }}</ins></p>
                <h3>{{ $tr ? 'Bir kararın arkasındaki gerekçe' : 'The reason behind a decision' }}</h3><p>{{ $tr ? 'Teknoloji seçimini, işin ihtiyacına ve bakım maliyetine göre yaparım. Yapay zekâ denemeleri hızlandırır; değerlendirme ve doğrulama sürecin bir parçası olmaya devam eder.' : 'I choose technology around the need and the cost of maintaining it. AI speeds up exploration; evaluation and verification stay part of the process.' }}</p>
                <h4>{{ $tr ? 'Daha küçük bir alt başlık' : 'A smaller subheading' }}</h4><p>{{ $tr ? 'Dördüncü düzey başlıklar, uzun bölümlerde konuları ayrıştırır.' : 'Level-four headings help organise longer sections.' }}</p>
                <h5>{{ $tr ? 'Uygulama notu' : 'Implementation note' }}</h5><p><abbr title="Application Programming Interface">API</abbr> · <kbd>⌘</kbd> + <kbd>K</kbd> · H<sub>2</sub>O · x<sup>2</sup> · <small>{{ $tr ? 'Kısa bir dipnot örneği.' : 'An example of a short footnote.' }}</small></p>
                <h6>{{ $tr ? 'Son bir ayrıntı' : 'One final detail' }}</h6><p>{{ $tr ? 'Başlık hiyerarşisi hem okumayı hem de sayfada gezinmeyi kolaylaştırır.' : 'A clear heading hierarchy makes reading and navigation easier.' }}</p>
            </section>
            <section id="sample-2">
                <h2>{{ $tr ? 'Düşünceyi görünür kılmak' : 'Make the thinking visible' }}</h2>
                <blockquote><p>{{ $tr ? 'İyi işçilik, kullanıcının fark etmek zorunda kalmadığı ayrıntılarda saklıdır.' : 'Careful craft lives in the details a user never has to think about.' }}</p><cite>Timurhan Kaya · {{ $tr ? 'Çalışma notu' : 'Working note' }}</cite></blockquote>
                <h3>{{ $tr ? 'Bir projeye başlarken' : 'At the start of a project' }}</h3>
                <ul><li>{{ $tr ? 'Kullanıcının yapmak istediği işi anla.' : 'Understand what the user wants to do.' }}<ul><li>{{ $tr ? 'Akıştaki sürtünmeyi bul.' : 'Find friction in the flow.' }}</li><li>{{ $tr ? 'Kısıtları görünür kıl.' : 'Make the constraints visible.' }}</li></ul></li><li>{{ $tr ? 'En küçük anlamlı çözümü dene.' : 'Try the smallest useful solution.' }}</li><li>{{ $tr ? 'Gerçek davranış üzerinden iyileştir.' : 'Improve it based on real behaviour.' }}</li></ul>
                <ol><li>{{ $tr ? 'Problemi tanımla.' : 'Define the problem.' }}</li><li>{{ $tr ? 'Alternatifleri karşılaştır.' : 'Compare alternatives.' }}</li><li>{{ $tr ? 'Uygula, ölç ve öğren.' : 'Build, measure, and learn.' }}</li></ol>
                <dl><dt>{{ $tr ? 'Derin düşünme' : 'Deep thinking' }}</dt><dd>{{ $tr ? 'Çözümden önce ihtiyacı ve seçenekleri değerlendirmek.' : 'Considering the need and alternatives before choosing a solution.' }}</dd><dt>{{ $tr ? 'İnce işçilik' : 'Careful craft' }}</dt><dd>{{ $tr ? 'Ayrıntılara, tutarlılığa ve bakım kalitesine özen göstermek.' : 'Taking care over details, consistency, and maintainability.' }}</dd></dl>
            </section>
            <section id="sample-3">
                <h2>{{ $tr ? 'Kod ve kararlar' : 'Code and decisions' }}</h2>
                <p>{{ $tr ? 'Satır içi kod için' : 'Inline code uses' }} <code>strict_types=1</code>; {{ $tr ? 'uzun örnekler için ayrı bir kod bloğu.' : 'longer examples have a dedicated code block.' }}</p>
                <pre><code>&lt;?php

declare(strict_types=1);

final class ProductDecision
{
    public function __construct(
        public readonly string $problem,
        public readonly array $constraints,
    ) {}
}</code></pre>
                <div class="brand-table-scroll" role="region" aria-label="{{ $tr ? 'Karar tablosu' : 'Decision table' }}" tabindex="0"><table><caption>{{ $tr ? 'Bir ürün kararını değerlendirirken' : 'When evaluating a product decision' }}</caption><thead><tr><th scope="col">{{ $tr ? 'Kriter' : 'Criterion' }}</th><th scope="col">{{ $tr ? 'Sorulacak soru' : 'Question to ask' }}</th><th scope="col">{{ $tr ? 'Çıktı' : 'Outcome' }}</th></tr></thead><tbody><tr><th scope="row">{{ $tr ? 'İhtiyaç' : 'Need' }}</th><td>{{ $tr ? 'Hangi işi kolaylaştırıyor?' : 'What does it make easier?' }}</td><td>{{ $tr ? 'Net bir problem tanımı' : 'A clear problem statement' }}</td></tr><tr><th scope="row">{{ $tr ? 'Bakım' : 'Maintenance' }}</th><td>{{ $tr ? 'Bir yıl sonra anlaşılacak mı?' : 'Will it make sense a year from now?' }}</td><td>{{ $tr ? 'Sürdürülebilir bir yapı' : 'A maintainable structure' }}</td></tr><tr><th scope="row">{{ $tr ? 'Deneyim' : 'Experience' }}</th><td>{{ $tr ? 'Hata olduğunda ne olacak?' : 'What happens when something goes wrong?' }}</td><td>{{ $tr ? 'Öngörülebilir bir akış' : 'A predictable flow' }}</td></tr></tbody><tfoot><tr><td colspan="3">{{ $tr ? 'Örnek değerlendirme tablosu; proje metriği içermez.' : 'An illustrative decision table, without project metrics.' }}</td></tr></tfoot></table></div>
            </section>
            <section id="sample-4">
                <h2>{{ $tr ? 'Bir fikri görselleştirmek' : 'Give an idea a visual form' }}</h2>
                <figure><img src="{{ asset('images/craft-diagram.svg') }}" alt="{{ $tr ? 'İhtiyaç, karar ve ürün adımlarını birbirine bağlayan şema' : 'A diagram linking the need, a decision, and the product' }}" width="1200" height="600" loading="lazy"><figcaption>{{ $tr ? 'İhtiyaç → karar → ürün. Aradaki bağ, düşünce ve işçilikle kurulur.' : 'Need → decision → product. Thinking and craft connect the steps.' }}</figcaption></figure>
                <aside class="brand-prose-callout"><strong>{{ $tr ? 'Çalışma notu' : 'Working note' }}</strong><p>{{ $tr ? 'Bir görselin görevi süslemek kadar açıklamaktır. Altyazı ve alternatif metin de bu anlatımın parçasıdır.' : 'Images explain as well as decorate. Captions and alternative text are part of that explanation.' }}</p></aside>
            </section>
            <section id="sample-5">
                <h2>{{ $tr ? 'Gerektiğinde daha fazla ayrıntı' : 'More detail when it helps' }}</h2>
                <details><summary>{{ $tr ? 'Bir case study’de neler anlatılır?' : 'What goes into a case study?' }}</summary><p>{{ $tr ? 'İhtiyaç, çalışma kapsamı, alınan kararlar ve uygulamanın sonuçları. Her bölüm okuyucunun projeyi daha iyi anlamasını sağlamalı.' : 'The need, scope of work, decisions, and results. Every section should help the reader understand the project.' }}</p></details>
                <p>{{ $tr ? 'Örnek ilerleme göstergesi' : 'Example progress indicator' }} <progress max="100" value="72">72%</progress></p>
                <p>{{ $tr ? 'Örnek ölçüm göstergesi' : 'Example measurement indicator' }} <meter min="0" max="100" low="25" high="80" optimum="90" value="84">84/100</meter></p>
                <hr><p><small>{{ $tr ? 'Bu sayfadaki içerik, yazı biçimlerini göstermek için hazırlanmış bir örnektir.' : 'This page contains sample content to demonstrate article formatting.' }}</small></p>
            </section>
            <section id="sample-6">
                <h2>{{ $tr ? 'Form elemanları' : 'Form elements' }}</h2>
                <p>{{ $tr ? 'Aşağıdaki form yalnızca görsel örnektir; veri göndermez.' : 'The form below is a visual example and does not send data.' }}</p>
                <form><fieldset disabled><legend>{{ $tr ? 'Örnek proje notu' : 'Sample project note' }}</legend><label for="example-name">{{ $tr ? 'Adın' : 'Your name' }}</label><input id="example-name" type="text" placeholder="{{ $tr ? 'Ad Soyad' : 'Full name' }}"><label for="example-type">{{ $tr ? 'Proje türü' : 'Project type' }}</label><select id="example-type"><option>SaaS / {{ $tr ? 'Kurumsal ürün' : 'Corporate product' }}</option><option>{{ $tr ? 'Web deneyimi' : 'Web experience' }}</option></select><label for="example-note">{{ $tr ? 'Kısa bir not' : 'A short note' }}</label><textarea id="example-note" rows="3" placeholder="{{ $tr ? 'Ne yapmak istiyorsun?' : 'What would you like to build?' }}"></textarea><label class="brand-example-check"><input type="checkbox" checked> {{ $tr ? 'Örnek seçim' : 'Example selection' }}</label><button class="brand-button" type="button">{{ $tr ? 'Örnek buton' : 'Sample button' }} <x-arrow /></button></fieldset></form>
            </section>
        </article>
    </div>
@endsection
