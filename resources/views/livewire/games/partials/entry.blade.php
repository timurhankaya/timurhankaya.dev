{{-- Create or join a room. Shared by both games; $game is "imposter" or "vampire". --}}
<div class="game-entry">
    <livewire:games.identity />

    @if ($error)
        <div class="game-error" role="alert"><x-games.icon name="alert" /><span>{{ $error }}</span></div>
    @endif

    <div class="game-entry-forms">
        <section class="game-entry-form" aria-labelledby="{{ $game }}-create-title">
            <h2 id="{{ $game }}-create-title">{{ __($game.'.createRoom') }}</h2>
            <p>{{ __($game.'.createRoomDesc') }}</p>

            <form wire:submit="createRoom">
                <label class="game-field">
                    <span class="game-field-label">{{ __($game.'.password') }} <span>{{ __($game.'.passwordOptional') }}</span></span>
                    <input
                        wire:model.live="createPassword"
                        type="password"
                        name="createPassword"
                        autocomplete="off"
                        spellcheck="false"
                        class="game-input"
                        placeholder="{{ __($game.'.placeholder.password') }}"
                    />
                </label>

                <button
                    type="submit"
                    @disabled(!$hasIdentity)
                    class="brand-button game-primary touch-manipulation"
                >
                    <span>{{ __($game.'.create') }}</span>
                    <x-arrow dir="e" />
                </button>
            </form>
        </section>

        <section class="game-entry-form" aria-labelledby="{{ $game }}-join-title">
            <h2 id="{{ $game }}-join-title">{{ __($game.'.joinRoom') }}</h2>
            <p>{{ __($game.'.joinRoomDesc') }}</p>

            <form wire:submit="joinRoom">
                <label class="game-field">
                    <span class="game-field-label">{{ __($game.'.roomCode') }}</span>
                    <input
                        wire:model.live="roomCode"
                        type="text"
                        name="roomCode"
                        inputmode="text"
                        autocomplete="off"
                        spellcheck="false"
                        autocapitalize="characters"
                        class="game-input game-input-code @error('roomCode') is-invalid @enderror"
                        placeholder="{{ __($game.'.placeholder.roomCode') }}"
                    />
                    @error('roomCode') <span class="game-field-error">{{ $message }}</span> @enderror
                </label>

                <label class="game-field">
                    <span class="game-field-label">{{ __($game.'.password') }} <span>{{ __($game.'.passwordIfRequired') }}</span></span>
                    <input
                        wire:model.live="joinPassword"
                        type="password"
                        name="joinPassword"
                        autocomplete="off"
                        spellcheck="false"
                        class="game-input"
                        placeholder="{{ __($game.'.placeholder.password') }}"
                    />
                </label>

                <button
                    type="submit"
                    @disabled(!$hasIdentity)
                    class="game-btn touch-manipulation"
                >
                    <span>{{ __($game.'.joinAction') }}</span>
                    <x-arrow dir="e" />
                </button>
            </form>
        </section>
    </div>
</div>
