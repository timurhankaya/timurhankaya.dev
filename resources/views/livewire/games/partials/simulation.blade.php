@php($simulation = $this->simulationStatus)
@if ($simulation['visible'])
    <section class="game-simulation game-panel" aria-label="{{ __('games.simulation.title') }}">
        <div class="game-simulation-head">
            <h2 class="brand-label">{{ __('games.simulation.title') }}</h2>
            <span class="game-tag">{{ __('games.simulation.bots', ['count' => $simulation['count']]) }} / 12</span>
        </div>
        <p class="game-simulation-note">{{ __('games.simulation.description') }}</p>
        <div class="game-simulation-controls">
            <label class="game-simulation-count">
                <span>{{ __('games.simulation.count') }}</span>
                <input type="number" min="1" max="12" wire:model="simulationBotCount" @disabled(! $simulation['lobby'])>
            </label>
            <button type="button" class="game-btn" wire:click="addSimulationBots" wire:loading.attr="disabled" @disabled(! $simulation['lobby'] || $simulation['count'] >= 12)>{{ __('games.simulation.add') }}</button>
            <button type="button" class="game-btn" wire:click="removeSimulationBots" wire:loading.attr="disabled" @disabled(! $simulation['lobby'] || $simulation['count'] === 0)>{{ __('games.simulation.remove') }}</button>
            <button type="button" class="game-btn" wire:click="playSimulationBots" wire:loading.attr="disabled" @disabled($simulation['lobby'] || $simulation['count'] === 0)>{{ __('games.simulation.play') }}</button>
            <button type="button" class="game-btn game-simulation-toggle" wire:click="toggleSimulationAutomatic" wire:loading.attr="disabled" aria-pressed="{{ $simulation['automatic'] ? 'true' : 'false' }}">
                <span class="game-simulation-light" aria-hidden="true"></span>
                {{ __('games.simulation.automatic') }}: {{ $simulation['automatic'] ? __('games.simulation.on') : __('games.simulation.off') }}
            </button>
        </div>
        <p class="game-simulation-status" role="status">{{ __('games.simulation.reasons.'.$simulation['reason']) }}</p>
        @if ($simulation['roleCount'] !== null)
            <p class="game-simulation-note {{ $simulation['roleCount'] !== $simulation['playerCount'] ? 'is-mismatch' : '' }}">
                {{ __('games.simulation.roles', ['players' => $simulation['playerCount'], 'roles' => $simulation['roleCount']]) }}
                @if ($simulation['roleCount'] !== $simulation['playerCount'])
                    {{ __('games.simulation.role_mismatch') }}
                @endif
            </p>
        @endif
    </section>
@endif
