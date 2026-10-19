@props(['players' => [], 'label', 'hostLabel', 'emptyLabel', 'deadLabel' => null, 'canKick' => false])
{{-- Player list as a dropdown under the room header button (same pattern as the nav menu): toggle, outside click and Escape close it. --}}
<div
    class="game-players"
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }"
    x-on:focusin.window="if (open && ! $el.contains($event.target)) open = false"
>
    <button
        type="button"
        x-ref="trigger"
        x-on:click="open = ! open"
        class="game-btn touch-manipulation"
        title="{{ $label }}"
        aria-controls="game-players-panel"
        aria-expanded="false"
        x-bind:aria-expanded="open ? 'true' : 'false'"
    >
        <x-games.icon name="players" />
        {{ $label }}
        <span class="game-count">{{ count($players) }}</span>
        <x-games.icon name="chevron" class="game-players-chevron" />
    </button>

    <div id="game-players-panel" class="game-players-panel" role="group" aria-label="{{ $label }}" x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1.5"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <ul class="game-players-list">
            @forelse ($players as $player)
                @php $out = $deadLabel !== null && ! ($player['alive'] ?? true); @endphp
                <li wire:key="menu-seat-{{ $player['id'] }}" class="game-players-row {{ $player['isMe'] ? 'is-me' : '' }} {{ $out ? 'is-out' : '' }}">
                    <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" size="sm" :out="$out" />
                    <span class="game-seat-name">{{ $player['name'] }}</span>
                    <span class="game-seat-tags">
                        @if ($player['isHost'])
                            <span class="game-tag"><x-games.icon name="host" />{{ $hostLabel }}</span>
                        @endif
                        @if ($out)
                            <span class="game-tag">{{ $deadLabel }}</span>
                        @endif
                        @if ($player['isMe'])
                            <span class="game-tag game-tag-accent">{{ __('games.you') }}</span>
                        @elseif ($canKick)
                            <button
                                type="button"
                                wire:click="openKickConfirm('{{ $player['id'] }}', '{{ addslashes($player['name']) }}')"
                                x-on:click="open = false"
                                class="game-kick touch-manipulation"
                                title="{{ __('games.kick.action', ['name' => $player['name']]) }}"
                                aria-label="{{ __('games.kick.action', ['name' => $player['name']]) }}"
                            ><x-games.icon name="close" /></button>
                        @endif
                    </span>
                </li>
            @empty
                <li class="game-players-empty">{{ $emptyLabel }}</li>
            @endforelse
        </ul>
    </div>
</div>
