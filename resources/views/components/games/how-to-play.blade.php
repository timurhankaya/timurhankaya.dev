@props(['title', 'rules' => [], 'tip' => null])
{{-- The same rules panel the lobby showed: numbered rules and a tip, kept below the arena in every phase. --}}
<section {{ $attributes->class(['game-panel', 'game-howto']) }} aria-label="{{ $title }}">
    <p class="brand-label">{{ $title }}</p>
    <ol class="game-rules">
        @foreach ($rules as $rule)
            <li>{{ $rule }}</li>
        @endforeach
    </ol>
    @if ($tip)
        <p class="game-tip">{{ $tip }}</p>
    @endif
</section>
