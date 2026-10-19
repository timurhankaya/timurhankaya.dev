@props(['emoji' => '🎭', 'color' => 'sky', 'size' => 'md', 'out' => false])
{{-- A player's chosen character, drawn as a line icon in their colour. The stored value stays the emoji. --}}
@php
    $icon = \Illuminate\Support\Arr::get([
        '🐺' => 'wolf', '🧛' => 'vampire', '🕵' => 'detective', '🏹' => 'hunter',
        '🧙' => 'wizard', '👁' => 'eye', '🗡' => 'dagger', '🛡' => 'shield',
        '🌙' => 'moon', '☀' => 'sun', '🔮' => 'crystal', '👑' => 'crown',
        '🦇' => 'bat', '🩸' => 'drop', '⚔' => 'swords', '🎭' => 'theater',
    ], str_replace("\u{FE0F}", '', (string) $emoji), 'theater');
@endphp
<span {{ $attributes->class(['game-avatar', 'game-avatar-'.$size, 'is-out' => $out, \App\View\PlayerColors::avatar($color)]) }} aria-hidden="true"><x-games.icon :name="$out ? 'eliminated' : $icon" /></span>
