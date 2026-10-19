@props(['trail'])
<nav class="brand-breadcrumbs" aria-label="{{ __('brand.breadcrumb') }}">
    <ol>
        @foreach ($trail as [$name, $url])
            <li>@if ($loop->last)<span aria-current="page">{{ $name }}</span>@else<a href="{{ $url }}">{{ $name }}</a>@endif</li>
        @endforeach
    </ol>
</nav>
