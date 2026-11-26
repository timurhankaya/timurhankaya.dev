@php
    // Companies and products I have worked on. A logo without a file shows its name as a wordmark.
    // Width and height are the drawn size, tuned so every mark carries about the same optical weight (not the
    // same height). `thin` brightens hairline logos that would otherwise fade next to the heavy ones.
    $logos = [
        ['name' => 'Monafy', 'src' => 'images/logos/monafy.svg', 'url' => 'https://monafy.com', 'size' => [128, 27]],
        // ADEM has no public site of its own here; its tile opens the case study once that is published.
        ['name' => 'ADEM', 'src' => 'images/logos/adem.png', 'url' => app(\App\Services\Portfolio\ProjectCatalog::class)->caseStudyUrl('adem'), 'internal' => true, 'size' => [114, 31]],
        ['name' => 'Moneo', 'src' => 'images/logos/moneo.svg', 'url' => 'https://moneo.com.tr', 'size' => [110, 18]],
        ['name' => 'Sistem Global', 'src' => 'images/logos/sistemglobal.png', 'url' => 'https://www.sistemglobal.com.tr', 'size' => [70, 56]],
        ['name' => 'Smart Global', 'src' => 'images/logos/smart-global.svg', 'url' => 'https://smartglobalval.co.uk', 'size' => [140, 27]],
        // The eDanışman file has its own margin around the mark, so its box is larger than what you see.
        ['name' => 'eDanışman', 'src' => 'images/logos/edanisman.png', 'url' => 'https://www.edanisman.com/', 'size' => [150, 39], 'thin' => true],
        ['name' => 'Basefy', 'src' => 'images/logos/basefy.png', 'url' => 'https://basefy.com', 'size' => [112, 33], 'thin' => true],
        ['name' => 'Toyka', 'src' => 'images/logos/toyka.png', 'url' => 'https://toyka.com.tr', 'size' => [94, 44]],
    ];
@endphp
<section class="brand-logos" aria-labelledby="brand-logos-title">
    <h3 id="brand-logos-title" class="brand-logos-title brand-label"><span class="brand-section-number" aria-hidden="true">/</span><span>{{ __('brand.logos_label') }}</span></h3>
    <div class="brand-logos-viewport">
        <div class="brand-logos-track">
            {{-- The second set only closes the loop: hidden from screen readers and the tab order, still clickable. --}}
            @foreach ([false, true] as $duplicate)
                <ul @if ($duplicate) aria-hidden="true" @endif>
                    @foreach ($logos as $logo)
                        <li>@if ($logo['url'])<a class="brand-logo-tile" href="{{ $logo['url'] }}"{!! ($logo['internal'] ?? false) ? '' : ' target="_blank" rel="noopener"' !!} title="{{ $logo['name'] }}"{!! $duplicate ? ' tabindex="-1"' : '' !!}>@else<span class="brand-logo-tile" title="{{ $logo['name'] }}">@endif@if ($logo['src'])<img @class(['is-thin' => $logo['thin'] ?? false]) src="{{ asset($logo['src']) }}" alt="{{ $logo['name'] }}" width="{{ $logo['size'][0] }}" height="{{ $logo['size'][1] }}" style="--logo-width: {{ $logo['size'][0] }}" decoding="async">@else<span class="brand-logo-wordmark">{{ $logo['name'] }}</span>@endif{!! $logo['url'] ? '</a>' : '</span>' !!}</li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
</section>
