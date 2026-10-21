@if ($isJoined && $myAlive)
    <section class="game-panel vk-night-preparations" aria-label="{{ __('games.night_preparations.title') }}"
        wire:key="night-preparations-{{ $nightNumber }}-{{ $nightEndsAt }}" x-data="{ completed: [] }">
        <div class="game-panel-head"><h3 class="brand-label">{{ __('games.night_preparations.title') }}</h3></div>
        <p class="game-lead">{{ __('games.night_preparations.description') }}</p>
        <div class="vk-preparation-actions">
            @foreach (['door' => 'lock', 'curtains' => 'eye-off', 'candle' => 'moon'] as $action => $icon)
                <button type="button" class="game-btn" @click="completed.push('{{ $action }}')"
                    :disabled="completed.includes('{{ $action }}')"
                    :aria-pressed="completed.includes('{{ $action }}')"
                    :class="{ 'is-done': completed.includes('{{ $action }}') }">
                    <x-games.icon :name="$icon" />
                    <span>{{ __('games.night_preparations.'.$action) }}</span>
                    <span x-show="completed.includes('{{ $action }}')" x-cloak aria-hidden="true">✓</span>
                </button>
            @endforeach
        </div>
        <p class="game-hint vk-preparation-result" role="status" x-show="completed.length > 0" x-cloak>
            <span x-show="completed.length < 3">{{ __('games.night_preparations.progress') }}</span>
            <span x-show="completed.length === 3">{{ __('games.night_preparations.complete') }}</span>
        </p>
        <p class="game-simulation-note">{{ __('games.night_preparations.note') }}</p>
    </section>
@endif
