@props([
    'backUrl',
    'backLabel',
    'roomCodeLabel',
    'title',
    'description',
    'accent' => 'cyan',
])
{{-- A face-down card with nothing behind it: the code points to no room. --}}
<div class="game-missing">
    <div class="game-card game-card-back" aria-hidden="true"><span>?</span></div>
    <h2 class="game-title">{{ $title }}</h2>
    <p class="game-lead">{{ $description }}</p>
    <a href="{{ $backUrl }}" class="brand-button"><x-arrow dir="w" /><span>{{ $backLabel }}</span></a>
</div>
