@props([
    'href',
    'label',
    'accent' => 'cyan',
])
<a href="{{ $href }}" {{ $attributes->class(['game-btn']) }}><x-arrow dir="w" /> {{ $label }}</a>
