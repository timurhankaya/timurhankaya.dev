@php
    // Brands I have built for. A missing file shows the name as a wordmark until the logo arrives.
    // Width and height are each file's drawn size at the tile's max height, so the strip never reflows.
    $logos = [
        ['name' => 'Monafy', 'src' => 'images/logos/monafy.svg', 'url' => 'https://monafy.com', 'size' => [160, 34]],
        ['name' => 'Moneo', 'src' => 'images/logos/moneo.svg', 'url' => 'https://moneo.com.tr', 'size' => [122, 20]],
        ['name' => 'Sistem Global', 'src' => 'images/logos/sistemglobal.png', 'url' => 'https://www.sistemglobal.com.tr', 'tall' => true, 'size' => [68, 54]],
        ['name' => 'Smart Global', 'src' => null, 'url' => 'https://smartglobalval.co.uk'],
        ['name' => 'Basefy', 'src' => 'images/logos/basefy.png', 'url' => 'https://basefy.com', 'size' => [114, 34]],
    ];
@endphp
<section class="brand-logos" aria-label="{{ __('brand.logos_label') }}">
    <ul>
        @foreach ($logos as $logo)
            <li><a class="brand-logo-tile" href="{{ $logo['url'] }}" target="_blank" rel="noopener" title="{{ $logo['name'] }}">@if ($logo['src'])<img @class(['is-tall' => $logo['tall'] ?? false]) src="{{ asset($logo['src']) }}" alt="{{ $logo['name'] }}" width="{{ $logo['size'][0] }}" height="{{ $logo['size'][1] }}" loading="lazy" decoding="async">@else<span class="brand-logo-wordmark">{{ $logo['name'] }}</span>@endif</a></li>
        @endforeach
    </ul>
</section>
