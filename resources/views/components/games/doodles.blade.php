@props(['game' => 'imposter', 'lite' => false, 'phase' => null])
{{-- Background doodles for the game pages: thin line drawings in the site's icon style (round caps, no fill), very faint,
     fixed behind the content and out of the way of the text. They only fade in once; the vampire set follows the phase. --}}
@php
    // 64×64 line drawings. Strokes are drawn by CSS (1.5px, round caps) so every size keeps the same thin line.
    $shapes = [
        'card' => '<rect x="15" y="5" width="34" height="54" rx="3"/><path d="M25.5 25c0-3.8 2.9-6.2 6.5-6.2s6.5 2.3 6.5 5.7c0 3.2-2.6 4.3-4.8 5.8-1.1.8-1.7 1.7-1.7 3.2"/><circle cx="32" cy="43" r="1"/><path d="M21 11l2.4 3-2.4 3-2.4-3ZM43 47l2.4 3-2.4 3-2.4-3Z"/>',
        'card-back' => '<rect x="15" y="5" width="34" height="54" rx="3"/><rect x="20" y="10" width="24" height="44" rx="1.5"/><path d="M32 19l8 13-8 13-8-13ZM24 32h16"/>',
        'qmark' => '<path d="M18 22c0-8.6 6-13.5 14-13.5s14 4.7 14 12c0 6.5-4.6 8.8-8.8 11.6-2.7 1.8-3.9 3.9-3.9 8.4"/><circle cx="33.3" cy="54" r="2.2"/>',
        'bang' => '<path d="M27.5 6h9l-1.8 33h-5.4Z"/><circle cx="32" cy="51" r="4"/>',
        'bubble' => '<path d="M9 12h46a3 3 0 0 1 3 3v24a3 3 0 0 1-3 3H34l-11 11V42H9a3 3 0 0 1-3-3V15a3 3 0 0 1 3-3Z"/><path d="M16 22h32M16 30h18"/>',
        'bubble-dots' => '<path d="M9 12h46a3 3 0 0 1 3 3v24a3 3 0 0 1-3 3H34l-11 11V42H9a3 3 0 0 1-3-3V15a3 3 0 0 1 3-3Z"/><circle cx="21" cy="27" r="1.6"/><circle cx="32" cy="27" r="1.6"/><circle cx="43" cy="27" r="1.6"/>',
        'bubble-q' => '<path d="M32 8C17 8 6 16 6 27c0 5 2.4 9.4 6.4 12.6L10 54l13-7c2.9.7 6 1 9 1 15 0 26-8 26-18S47 8 32 8Z"/><path d="M26.5 24c0-2.8 2.3-4.6 5.3-4.6s5.2 1.8 5.2 4.4c0 2.6-2 3.4-3.6 4.4-.9.6-1.4 1.4-1.4 2.8"/><circle cx="32" cy="36.5" r="1"/>',
        'magnifier' => '<circle cx="26" cy="26" r="16"/><path d="M38 38l17 17M50.5 58.5l8-8"/><path d="M16 24a10.5 10.5 0 0 1 8.5-8.5"/>',
        'sparkle' => '<path d="M32 5c1.8 14.5 5.5 22 21 27-15.5 5-19.2 12.5-21 27-1.8-14.5-5.5-22-21-27 15.5-5 19.2-12.5 21-27Z"/>',
        'sparkles' => '<path d="M24 22c1.2 8.6 3.4 13 13 16.4-9.6 3.4-11.8 7.8-13 16.4-1.2-8.6-3.4-13-13-16.4 9.6-3.4 11.8-7.8 13-16.4Z"/><path d="M47 6c.6 4 1.6 5.8 5.2 7-3.6 1.2-4.6 3-5.2 7-.6-4-1.6-5.8-5.2-7 3.6-1.2 4.6-3 5.2-7Z"/><path d="M50 40v9M45.5 44.5h9"/>',
        'mask' => '<g transform="translate(2 12) scale(3.75)"><path d="M1.5 5.5c2.2-1.4 4.4-1.4 6.5 0 2.1-1.4 4.3-1.4 6.5 0 0 3.2-1.4 5-3.3 5-1.4 0-2.4-.9-3.2-2.5-.8 1.6-1.8 2.5-3.2 2.5-1.9 0-3.3-1.8-3.3-5ZM4.3 7.2h1.6M10.1 7.2h1.6"/></g>',
        'eye' => '<path d="M2 32s11-18 30-18 30 18 30 18-11 18-30 18S2 32 2 32Z"/><circle cx="32" cy="32" r="8"/><circle cx="32" cy="32" r="1.5"/>',
        'spade' => '<path d="M32 6C20 18 10 25 10 35a11 11 0 0 0 19 7.5C28.6 48 26.8 52 23 56h18c-3.8-4-5.6-8-6-13.5A11 11 0 0 0 54 35C54 25 44 18 32 6Z"/>',
        'diamond' => '<path d="M32 6 52 32 32 58 12 32Z"/><path d="M32 18 42 32 32 46 22 32Z"/>',
        'moon' => '<path d="M43 7a26 26 0 1 0 14 41A21 21 0 0 1 43 7Z"/><circle cx="21" cy="33" r="2.6"/><circle cx="30" cy="47" r="1.8"/>',
        'stars' => '<path d="M6 46l14-14 12 8 14-20 14 6"/><circle cx="6" cy="46" r="1.8"/><circle cx="20" cy="32" r="1.8"/><circle cx="32" cy="40" r="1.8"/><circle cx="46" cy="20" r="1.8"/><circle cx="60" cy="26" r="1.8"/><path d="M46 9c.4 3 1 4 3.6 4.8-2.6.8-3.2 1.8-3.6 4.8-.4-3-1-4-3.6-4.8C45 13 45.6 12 46 9Z"/>',
        'star' => '<path d="M32 8c1.2 10.5 3.4 14.2 14 15.5-10.6 1.3-12.8 5-14 15.5-1.2-10.5-3.4-14.2-14-15.5C28.6 22.2 30.8 18.5 32 8Z"/>',
        'bat' => '<path d="M28 25l-.5-8 3.2 3.2h2.6L36.5 17 36 25"/><path d="M29 25c-.6 5-.2 9 3 13 3.2-4 3.6-8 3-13"/><path d="M29 27C23 21.5 13 20 3 24c3 .6 5 2.6 5.5 6 2.3-2.2 5-2.6 7.5-1 1.4-2.6 4-3.6 7-2.6 1-1.8 3-3 5-3.4"/><path d="M35 27c6-5.5 16-7 26-3-3 .6-5 2.6-5.5 6-2.3-2.2-5-2.6-7.5-1-1.4-2.6-4-3.6-7-2.6-1-1.8-3-3-5-3.4"/>',
        'pine' => '<path d="M32 4 18 24h8L14 42h9L8 58h48L41 42h9L38 24h8Z"/><path d="M32 58v5"/>',
        'bare-tree' => '<path d="M32 62V28M32 48 20 38M32 42l12-10M32 34 24 22M32 28l8-12M20 38l-7-2M44 32l7-3M24 22l-6-1M40 16l5-5M25 14l-3-5"/>',
        'lantern' => '<path d="M32 3v4M24 14a8 8 0 0 1 16 0"/><path d="M21 22h22l-3-8H24Z"/><path d="M23 22l2 28h14l2-28"/><path d="M32 29c-3.4 3.4-3.4 8.6 0 12 3.4-3.4 3.4-8.6 0-12Z"/><path d="M20 50h24v5H20Z"/>',
        'candle' => '<path d="M20 58h24M26 58V32h12v26M32 32v-4"/><path d="M32 26c-3.2-3.4-3.2-8 0-13 3.2 5 3.2 9.6 0 13Z"/><path d="M26 38c-3 0-3 5 0 5"/>',
        'sun' => '<circle cx="32" cy="32" r="9"/><path d="M32 8v8M32 48v8M8 32h8M48 32h8M15 15l5.6 5.6M43.4 43.4 49 49M15 49l5.6-5.6M43.4 20.6 49 15"/>',
        'cloud' => '<path d="M16 46a10 10 0 0 1 2-19.8A14 14 0 0 1 45 29a8.5 8.5 0 0 1 1 17Z"/>',
        'birds' => '<path d="M4 24c4-7 9-7 13 0 4-7 9-7 13 0M34 42c3-5 6.5-5 9.5 0 3-5 6.5-5 9.5 0"/>',
        'dawn' => '<path d="M2 50h60M14 50a18 18 0 0 1 36 0M32 18v6M10 28l4 4M54 28l-4 4"/>',
    ];

    // Skyline of the village: roofs, a church spire, chimneys and a few lit windows. One long line along the bottom edge.
    $skyline = '<path d="M0 66h240M2 66V42l16-14 16 14v24M26 35v-9h5v13M40 66V46l11-10 11 10v20M68 66V34l16-16 16 16v32M106 66V30h18v36M104 30l11-22 11 22M115 8V3M113 5.5h4M134 66V40l14-12 14 12v26M168 66V46l12-11 12 11v20M198 66V36l18-16 18 16v30M224 29v-8h5v13"/><path d="M10 44h6v6h-6ZM80 38h8v8h-8ZM112 38h6v8h-6ZM144 46h6v6h-6ZM210 40h8v8h-8Z"/><path d="M86 66V56h6v10M222 66V56h6v10M10 66V58h6v8"/>';

    // x, y: centre in % of the screen; z: size in px; r: tilt; t: tone; m: [x, y, size] on phones (or false to hide); f: phases it shows in; l: kept in the lighter landing version.
    $sets = [
        'imposter' => [
            ['s' => 'card', 'x' => 4.5, 'y' => 15, 'z' => 112, 'r' => -14, 't' => 'cool', 'm' => [-2, 62, 64], 'l' => true],
            ['s' => 'qmark', 'x' => 3.8, 'y' => 40, 'z' => 88, 'r' => 10, 't' => 'accent', 'm' => false],
            ['s' => 'magnifier', 'x' => 5, 'y' => 66, 'z' => 98, 'r' => -8, 'm' => [3, 86, 60], 'l' => true],
            ['s' => 'sparkles', 'x' => 4, 'y' => 89, 'z' => 72, 'r' => 0, 't' => 'accent', 'm' => false],
            ['s' => 'bubble-dots', 'x' => 95, 'y' => 13, 'z' => 108, 'r' => 8, 'm' => [86, 14, 66], 'l' => true],
            ['s' => 'bang', 'x' => 96, 'y' => 38, 'z' => 88, 'r' => -10, 't' => 'accent', 'm' => [95, 33, 48], 'l' => true],
            ['s' => 'spade', 'x' => 95, 'y' => 62, 'z' => 78, 'r' => 12, 'm' => false],
            ['s' => 'eye', 'x' => 94.5, 'y' => 86, 'z' => 100, 'r' => 0, 't' => 'cool', 'm' => [92, 88, 64]],
            ['s' => 'bubble-q', 'x' => 64, 'y' => 15, 'z' => 84, 'r' => -6, 'm' => false],
            ['s' => 'card-back', 'x' => 79, 'y' => 20, 'z' => 78, 'r' => 16, 't' => 'accent', 'm' => false],
            ['s' => 'mask', 'x' => 51, 'y' => 13, 'z' => 74, 'r' => -6, 'm' => false],
            ['s' => 'diamond', 'x' => 88, 'y' => 76, 'z' => 58, 'r' => -10, 'm' => false],
            ['s' => 'sparkle', 'x' => 18, 'y' => 8, 'z' => 40, 'r' => 0, 't' => 'accent', 'm' => [10, 8, 28]],
        ],
        'vampire' => [
            ['s' => 'moon', 'x' => 5.5, 'y' => 15, 'z' => 104, 'r' => -8, 'f' => 'night lobby', 'm' => [10, 80, 56], 'l' => true],
            ['s' => 'stars', 'x' => 66, 'y' => 11, 'z' => 130, 'r' => 0, 'f' => 'night lobby', 'm' => false, 'l' => true],
            ['s' => 'star', 'x' => 22, 'y' => 7, 'z' => 36, 'r' => 0, 'f' => 'night lobby dawn', 'm' => [16, 8, 26]],
            ['s' => 'star', 'x' => 90, 'y' => 46, 'z' => 28, 'r' => 0, 'f' => 'night lobby', 'm' => false],
            ['s' => 'bat', 'x' => 79, 'y' => 24, 'z' => 76, 'r' => -10, 't' => 'accent', 'f' => 'night lobby', 'm' => [84, 14, 52], 'l' => true],
            ['s' => 'bat', 'x' => 12, 'y' => 50, 'z' => 52, 'r' => 12, 't' => 'accent', 'f' => 'night lobby dawn', 'm' => false],
            ['s' => 'bat', 'x' => 90, 'y' => 58, 'z' => 60, 'r' => -6, 'f' => 'night lobby', 'm' => [10, 40, 40]],
            ['s' => 'dawn', 'x' => 6, 'y' => 18, 'z' => 100, 'r' => 0, 't' => 'accent', 'f' => 'dawn', 'm' => [12, 12, 62]],
            ['s' => 'sun', 'x' => 6, 'y' => 15, 'z' => 96, 'r' => 0, 'f' => 'day verdict', 'm' => [12, 12, 56]],
            ['s' => 'cloud', 'x' => 72, 'y' => 12, 'z' => 108, 'r' => 0, 'f' => 'day verdict', 'm' => [76, 10, 66]],
            ['s' => 'cloud', 'x' => 14, 'y' => 46, 'z' => 78, 'r' => 0, 'f' => 'day', 'm' => false],
            ['s' => 'birds', 'x' => 86, 'y' => 34, 'z' => 84, 'r' => -6, 'f' => 'day dawn verdict', 'm' => [82, 24, 52]],
            ['s' => 'pine', 'x' => 3.5, 'y' => 74, 'z' => 124, 'r' => 0, 'm' => [-1, 76, 78], 'l' => true],
            ['s' => 'pine', 'x' => 9, 'y' => 82, 'z' => 84, 'r' => 0, 'm' => false],
            ['s' => 'bare-tree', 'x' => 96, 'y' => 72, 'z' => 130, 'r' => 0, 'm' => [100, 74, 86], 'l' => true],
            ['s' => 'lantern', 'x' => 4.5, 'y' => 40, 'z' => 78, 'r' => 0, 't' => 'accent', 'm' => false],
            ['s' => 'candle', 'x' => 95.5, 'y' => 44, 'z' => 74, 'r' => 0, 't' => 'accent', 'm' => [92, 52, 46]],
        ],
    ];
    $items = array_filter($sets[$game] ?? $sets['imposter'], fn ($d) => ! $lite || ! empty($d['l']));
@endphp
<div {{ $attributes->class(['game-doodles', 'game-doodles-'.$game, 'is-lite' => $lite]) }} @if ($phase) data-phase="{{ $phase }}" @endif aria-hidden="true">
    @if ($game === 'vampire')
        <div class="game-skyline">
            @for ($i = 0; $i < 7; $i++)<svg viewBox="0 0 240 70" focusable="false">{!! $skyline !!}</svg>@endfor
        </div>
    @endif
    @foreach ($items as $d)
        @php $m = $d['m'] ?? null; @endphp
        <svg
            class="game-doodle {{ isset($d['t']) ? 'is-'.$d['t'] : '' }} {{ $m === false ? 'is-desktop' : '' }}"
            viewBox="0 0 64 64" focusable="false"
            @isset($d['f']) data-for="{{ $d['f'] }}" @endisset
            style="--x: {{ $d['x'] }}%; --y: {{ $d['y'] }}%; --s: {{ $d['z'] }}px; --r: {{ $d['r'] ?? 0 }}deg;@if (is_array($m)) --px: {{ $m[0] }}%; --py: {{ $m[1] }}%; --ps: {{ $m[2] }}px;@endif"
        >{!! $shapes[$d['s']] !!}</svg>
    @endforeach
</div>
