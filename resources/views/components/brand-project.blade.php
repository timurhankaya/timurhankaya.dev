@props(['item', 'number' => 0, 'featured' => false])
@php
    $dialogId = 'project-'.substr(sha1(data_get($item, 'name').$number), 0, 12);
@endphp
<article class="brand-project {{ $featured ? 'brand-project-featured' : '' }}">
    @if ($featured)
        <div class="brand-project-art brand-project-art-{{ (($number - 1) % 3) + 1 }}" aria-hidden="true">
            <span class="brand-project-art-label">{{ data_get($item, 'badge') }} / {{ data_get($item, 'year') }}</span>
            <span class="brand-project-word">{{ data_get($item, 'name') }}</span>
            <span class="brand-project-art-bottom"><span>TKAYA / {{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</span><span aria-hidden="true"><x-arrow /></span></span>
        </div>
    @endif
    <div class="brand-project-heading"><h3>{{ data_get($item, 'name') }}</h3><span class="brand-label">{{ data_get($item, 'year') }}</span></div>
    <p class="brand-project-description">{{ data_get($item, 'description') }}</p>
    <div class="brand-project-actions">
        @if (data_get($item, 'highlights'))
            <button class="brand-contribution-button" type="button" data-project-dialog-open="{{ $dialogId }}" aria-haspopup="dialog" aria-controls="{{ $dialogId }}">{{ __('brand.details') }} <span aria-hidden="true">+</span></button>
            <dialog id="{{ $dialogId }}" class="brand-project-dialog" aria-labelledby="{{ $dialogId }}-title">
                <div class="brand-dialog-header"><p class="brand-label">{{ __('brand.details') }}</p><form method="dialog"><button class="brand-dialog-close" aria-label="{{ __('brand.close') }}" autofocus><span aria-hidden="true">×</span></button></form></div>
                <h2 id="{{ $dialogId }}-title">{{ data_get($item, 'name') }}</h2>
                <p class="brand-dialog-description">{{ data_get($item, 'description') }}</p>
                <ul>@foreach ((array) data_get($item, 'highlights', []) as $highlight)<li>{{ $highlight }}</li>@endforeach</ul>
            </dialog>
        @endif
    </div>
    @if (data_get($item, 'url') && !data_get($item, 'archived', false))
        <a class="brand-project-visit" href="{{ data_get($item, 'url') }}" target="_blank" rel="noopener noreferrer">{{ __('brand.visit') }} <span class="sr-only">— {{ data_get($item, 'name') }}</span><x-arrow /></a>
    @endif
    @foreach ((array) data_get($item, 'links', []) as $link)
        <a class="brand-project-visit" href="{{ data_get($link, 'url') }}" target="_blank" rel="noopener noreferrer">{{ data_get($link, 'label') }} <x-arrow /></a>
    @endforeach
</article>
