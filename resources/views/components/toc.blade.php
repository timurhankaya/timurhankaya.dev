@props(['headings' => []])
{{-- Contents list; resources/js/app.js (initToc) marks the section being read with aria-current="location". --}}
@if (count($headings) > 1)<nav class="brand-case-toc" data-toc aria-label="{{ __('brand.contents') }}"><p class="brand-label">{{ __('brand.contents') }}</p>@foreach ($headings as $heading)<a href="#{{ $heading['id'] }}"><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $heading['title'] }}</a>@endforeach</nav>@endif
