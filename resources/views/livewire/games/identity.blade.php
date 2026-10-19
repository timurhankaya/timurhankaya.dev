@php
$canCancel = (bool) session('games.identity');
// Characters are stored as emoji; the screen draws each one as a line icon.
$characterIcons = [
    '🐺' => 'wolf', '🧛' => 'vampire', '🕵' => 'detective', '🏹' => 'hunter',
    '🧙' => 'wizard', '👁' => 'eye', '🗡' => 'dagger', '🛡' => 'shield',
    '🌙' => 'moon', '☀' => 'sun', '🔮' => 'crystal', '👑' => 'crown',
    '🦇' => 'bat', '🩸' => 'drop', '⚔' => 'swords', '🎭' => 'theater',
];
$iconFor = fn (string $e): string => $characterIcons[str_replace("\u{FE0F}", '', $e)] ?? 'theater';
$selectedTone = \App\View\PlayerColors::avatar($color);
@endphp

{{-- Single root element keeps wire:id in place even when @teleport moves content --}}
<div>
@if (!$editing)
    <div class="game-identity">
        <x-games.avatar :emoji="$emoji" :color="$color" />
        <div class="game-identity-text">
            <p class="brand-label">{{ __('games.identity.playingAs') }}</p>
            <p class="game-identity-name">{{ $name }}</p>
        </div>
        <button wire:click="edit" type="button" class="game-btn touch-manipulation">
            {{ __('games.identity.change') }}
        </button>
    </div>
@else
    @teleport('body')
    <div
        class="game-dialog-backdrop"
        @if($canCancel) wire:keydown.window.escape="cancelEdit" @endif
    >
        @if($canCancel)
            <button
                type="button"
                wire:click="cancelEdit"
                class="game-dialog-scrim"
                aria-hidden="true"
                tabindex="-1"
            ></button>
        @else
            <div class="game-dialog-scrim"></div>
        @endif

        <div
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('games.identity.title') }}"
            class="game-dialog"
        >
            <div class="game-dialog-head">
                <p class="brand-label">{{ __('games.identity.title') }}</p>
                @if($canCancel)
                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="game-dialog-close touch-manipulation"
                        aria-label="{{ __('games.close') }}"
                    ><x-games.icon name="close" /></button>
                @endif
            </div>

            <div class="game-identity-preview">
                <x-games.avatar :emoji="$emoji" :color="$color" size="lg" />
                <div class="min-w-0">
                    @if ($name)
                        <p class="game-identity-name">{{ $name }}</p>
                    @endif
                    <p class="game-lead">{{ __('games.identity.subtitle') }}</p>
                </div>
            </div>

            <form wire:submit="save" class="game-identity-form">
                <label class="game-field">
                    <span class="game-field-label">{{ __('games.identity.name') }}</span>
                    <input
                        wire:model.live="name"
                        type="text"
                        name="name"
                        autocomplete="nickname"
                        maxlength="24"
                        autofocus
                        class="game-input @error('name') is-invalid @enderror"
                        placeholder="{{ __('games.identity.namePlaceholder') }}"
                    />
                    @error('name') <span class="game-field-error">{{ $message }}</span> @enderror
                </label>

                <div class="game-field">
                    <span class="game-field-label">{{ __('games.identity.color') }}</span>
                    <div class="game-swatches">
                        @foreach ($colorMap as $key => $cls)
                            <button
                                wire:click="setColor('{{ $key }}')"
                                type="button"
                                class="game-swatch touch-manipulation {{ $cls['swatch'] }} {{ $color === $key ? 'is-selected' : '' }}"
                                aria-label="{{ __('games.colors.'.$key) }}"
                                aria-pressed="{{ $color === $key ? 'true' : 'false' }}"
                            >@if ($color === $key)<x-games.icon name="check" />@endif</button>
                        @endforeach
                    </div>
                    @error('color') <span class="game-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="game-field">
                    <span class="game-field-label">{{ __('games.identity.emoji') }}</span>
                    <div class="game-characters">
                        @foreach ($emojis as $e)
                            <button
                                wire:click="setEmoji('{{ $e }}')"
                                type="button"
                                class="game-character touch-manipulation {{ $emoji === $e ? 'is-selected '.$selectedTone : '' }}"
                                aria-label="{{ __('games.avatars.'.$iconFor($e)) }}"
                                aria-pressed="{{ $emoji === $e ? 'true' : 'false' }}"
                            ><x-games.icon :name="$iconFor($e)" /></button>
                        @endforeach
                    </div>
                    @error('emoji') <span class="game-field-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="brand-button game-primary touch-manipulation">
                    <span>{{ __('games.identity.save') }}</span>
                    <x-arrow dir="e" />
                </button>
            </form>
        </div>
    </div>
    @endteleport
@endif
</div>
