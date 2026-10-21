@php
    $backUrl = \App\Support\Seo::route('games.imposter.index');
    $roomUrl = \App\Support\Seo::route('games.imposter.room', ['roomCode' => $roomCode]);
    $inRound = in_array($status, [\App\Enums\ImposterRoomStatus::Started, \App\Enums\ImposterRoomStatus::Voting, \App\Enums\ImposterRoomStatus::Results], true);
    $hostActions = $isHost && in_array($status, [\App\Enums\ImposterRoomStatus::Lobby, \App\Enums\ImposterRoomStatus::Started, \App\Enums\ImposterRoomStatus::Voting, \App\Enums\ImposterRoomStatus::Results], true);
    // The secret word is never broken inside a word, so the type size follows the longest word (and the whole phrase) instead.
    $wordSize = function (?string $word): string {
        $word = trim((string) $word);
        $longest = collect(preg_split('/\s+/u', $word) ?: [])->map(fn ($w) => mb_strlen($w))->max() ?? 0;
        $tier = $longest <= 8 ? 0 : ($longest <= 12 ? 1 : ($longest <= 16 ? 2 : 3));
        $tier = max($tier, mb_strlen($word) > 28 ? 2 : (mb_strlen($word) > 18 ? 1 : 0));

        return ['is-lg', 'is-md', 'is-sm', 'is-xs'][$tier];
    };
@endphp

<div class="game-room imp-room"
    x-data="{
        roleVisible: true,
        playBongg() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') ctx.resume();

                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(1.0, ctx.currentTime + 0.05); // Vurucu başlangıç
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8); // Uzun rahatsız edici azalış
                gain.connect(ctx.destination);

                // Dissonant (uyumsuz) ve rahatsız edici bir akor yaratıyoruz
                const freqs = [350, 380, 410, 800];
                freqs.forEach(f => {
                    const osc = ctx.createOscillator();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(f * 2, ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(f / 3, ctx.currentTime + 0.8);
                    osc.connect(gain);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.8);
                });
            } catch (e) {
                console.error('Audio failed:', e);
            }
        }
    }"
    x-on:play-bongg.window="playBongg()"
>
    <x-games.doodles game="imposter" wire:ignore />

    @if ($roomMissing)
        <x-games.room-missing-state
            :back-url="$backUrl"
            :back-label="__('imposter.back')"
            :room-code-label="__('imposter.roomCode')"
            :title="__('imposter.roomNotFound')"
            :description="__('imposter.roomNotFoundDesc')"
            accent="cyan"
        />
    @else
        <div class="game-roombar">
            <button type="button" wire:click="leave" class="game-btn touch-manipulation">
                <x-arrow dir="w" />
                {{ $isJoined ? __('imposter.leave') : __('imposter.back') }}
            </button>
            <x-games.players-menu
                :players="$players"
                :label="__('imposter.players')"
                :host-label="__('imposter.host')"
                :empty-label="__('imposter.noOneYet')"
                :can-kick="$isHost && $status === \App\Enums\ImposterRoomStatus::Lobby"
            />
        </div>

        <div
            class="game-arena imposter-arena"
            data-imposter-arena
            @if ($status === \App\Enums\ImposterRoomStatus::Lobby)
                wire:poll.2s="refreshRoom"
            @else
                wire:poll.5s="refreshRoom"
            @endif
        >
            @include('livewire.games.partials.simulation')

            @if ($status === \App\Enums\ImposterRoomStatus::Lobby)
                {{-- Room code and the ways to share it --}}
                <div class="game-code-block">
                    <div class="game-code-head">
                        <p class="brand-label">{{ __('imposter.roomCode') }}</p>
                        @if ($roomPassword !== null)
                            <div x-data="{ showPassword: false }" class="game-secret">
                                <x-games.icon name="lock" />
                                <span x-text="showPassword ? '{{ $roomPassword }}' : '••••••••'"></span>
                                <button type="button" x-on:click="showPassword = !showPassword" title="{{ __('games.password_toggle') }}" aria-label="{{ __('games.password_toggle') }}">
                                    <x-games.icon name="eye" x-show="!showPassword" />
                                    <x-games.icon name="eye-off" x-cloak x-show="showPassword" />
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="game-code" role="img" aria-label="{{ __('imposter.roomCode') }}: {{ $roomCode }}">
                        @foreach (mb_str_split($roomCode) as $char)<span aria-hidden="true">{{ $char }}</span>@endforeach
                    </div>
                    <div class="game-share">
                        <button
                            type="button"
                            class="game-btn game-copy touch-manipulation"
                            data-copy-text="{{ $roomCode }}"
                            data-copy-label="{{ __('imposter.copyCode') }}"
                            data-copy-label-success="{{ __('portfolio.ui.copied') }}"
                        >{{ __('imposter.copyCode') }}</button>
                        <button
                            type="button"
                            class="game-btn game-copy game-copy-link touch-manipulation"
                            data-copy-text="{{ $roomUrl }}"
                            data-copy-label="{{ __('imposter.copyLink') }}"
                            data-copy-label-success="{{ __('portfolio.ui.copied') }}"
                        >{{ __('imposter.copyLink') }}</button>
                        <div x-data="{ openQr: false }" class="game-qr">
                            <button
                                type="button"
                                @click="openQr = !openQr"
                                class="game-btn game-btn-square touch-manipulation"
                                title="{{ __('games.qr') }}"
                                aria-label="{{ __('games.qr') }}"
                                :aria-expanded="openQr ? 'true' : 'false'"
                            ><x-games.icon name="qr" /></button>
                            <div
                                x-show="openQr"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                @click.away="openQr = false"
                                x-cloak
                                class="game-qr-pop"
                            >
                                <p class="game-qr-label">{{ __('games.qr_scan') }}</p><div class="game-qr-tile" role="img" aria-label="{{ __('games.qr_alt') }}">{!! \App\Support\Games\RoomQr::svg($roomUrl) !!}</div><p class="game-qr-room">{{ $roomCode }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="game-arena-body">
                @if ($error)
                    <div class="game-error" role="alert"><x-games.icon name="alert" /><span>{{ $error }}</span></div>
                @endif

                <div class="{{ $inRound && $isJoined ? 'game-round' : 'game-stage-only' }}">
                @if ($inRound && $isJoined)
                    {{-- Role card: a face-down card that flips over when the round starts --}}
                    <section class="game-role {{ $status !== \App\Enums\ImposterRoomStatus::Started ? 'is-compact' : '' }}" role="region" aria-label="{{ __('imposter.yourRole') }}">
                        <button
                            type="button"
                            x-on:click.stop.prevent="roleVisible = !roleVisible"
                            class="game-role-toggle touch-manipulation"
                            :aria-expanded="roleVisible ? 'true' : 'false'"
                            aria-controls="role-card-body"
                            id="role-card-toggle"
                        >
                            <span>
                                <strong>{{ __('imposter.yourRole') }}</strong>
                                <small>{{ __('imposter.privateRole') }}</small>
                            </span>
                            <span>
                                <x-games.icon name="eye-off" x-show="roleVisible" />
                                <x-games.icon name="eye" x-show="!roleVisible" x-cloak />
                                <span x-show="roleVisible">{{ __('imposter.roleHide') }}</span>
                                <span x-show="!roleVisible" x-cloak>{{ __('imposter.roleShow') }}</span>
                            </span>
                        </button>

                        <div id="role-card-body" x-show="roleVisible" role="region" aria-labelledby="role-card-toggle">
                            <div class="game-flip">
                                <div class="game-flip-inner">
                                    <div class="game-flip-face">
                                        @if ($myRole === 'imposter')
                                            <div class="game-card game-role-card is-imposter">
                                                <span class="game-role-ghost" aria-hidden="true">?</span>
                                                <p class="game-role-badge"><x-games.icon name="imposter" /><span>{{ __('imposter.roleImposter') }}</span></p>
                                                <p class="game-role-line">{{ __('games.imposter_card.imposter') }}</p>
                                            </div>
                                        @elseif ($myRole === 'crew')
                                            <div class="game-card game-role-card is-crew">
                                                <p class="game-role-badge"><x-games.icon name="crew" /><span>{{ __('imposter.roleCrew') }}</span></p>
                                                <div class="game-role-wordbox">
                                                    <p class="brand-label">{{ __('imposter.yourWord') }}</p>
                                                    <p class="game-role-word {{ $wordSize($myWord) }}">{{ $myWord ?: '...' }}</p>
                                                </div>
                                                <p class="game-role-line">{{ __('games.imposter_card.crew') }}</p>
                                            </div>
                                        @else
                                            <div class="game-card game-card-back"><span aria-hidden="true">?</span><small>{{ __('imposter.waitingForRole') }}</small></div>
                                        @endif
                                    </div>
                                    <div class="game-flip-face game-flip-back" aria-hidden="true">
                                        <div class="game-card game-card-back"><span>?</span></div>
                                    </div>
                                </div>
                            </div>
                            @if ($myRole === 'crew' && ! $myWord)
                                <p class="game-hint mt-3"><x-games.icon name="eye-off" />{{ __('imposter.waitingForWord') }}</p>
                            @endif
                        </div>
                        <div x-show="!roleVisible" x-cloak class="game-card game-card-back game-card-hidden"><span aria-hidden="true">?</span><small>{{ __('imposter.privateRole') }}</small></div>
                    </section>
                @endif

                <div class="game-stage">
                @if ($status === \App\Enums\ImposterRoomStatus::Lobby)
                    <div class="game-arena-split {{ $isJoined ? 'is-joined' : '' }}">
                        <div class="game-side">
                            @if (! $isJoined)
                                <div class="game-panel">
                                    <h2 class="game-title">{{ __('imposter.joinThisRoom') }}</h2>
                                    <p class="game-lead">{{ __('imposter.joinThisRoomDesc') }}</p>
                                    <livewire:games.identity />

                                    @if ($isPasswordProtected)
                                        <label class="game-field">
                                            <span class="game-field-label">{{ __('imposter.password') }}</span>
                                            <input
                                                wire:model.live="joinPassword"
                                                type="password"
                                                autocomplete="off"
                                                spellcheck="false"
                                                class="game-input @error('joinPassword') is-invalid @enderror"
                                                placeholder="{{ __('imposter.placeholder.password') }}"
                                            />
                                            @error('joinPassword')
                                                <span class="game-field-error">{{ $message }}</span>
                                            @enderror
                                        </label>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="join"
                                        @disabled(!$hasIdentity)
                                        class="brand-button game-primary touch-manipulation"
                                    >
                                        <span>{{ __('imposter.join') }}</span>
                                        <x-arrow dir="e" />
                                    </button>
                                </div>
                            @else
                                <div class="game-panel">
                                    <div class="game-panel-head">
                                        <p class="brand-label"><span class="game-live" aria-hidden="true"></span>{{ __('imposter.waitingForStart') }}</p>
                                    </div>
                                    <p class="game-lead">{{ __('imposter.waitingForStartDesc') }}</p>
                                </div>
                                <livewire:games.identity />
                            @endif
                        </div>

                        <div class="game-main">
                            <div class="game-panel">
                                <div class="game-panel-head">
                                    <p class="brand-label"><span class="game-live" aria-hidden="true"></span>{{ __('imposter.lobby') }} / {{ trans_choice('games.players_count', count($players)) }}</p>
                                    @if ($isJoined)
                                        <button wire:click="bongg" type="button" class="game-btn touch-manipulation" title="{{ __('games.nudge_all') }}">
                                            <x-games.icon name="bell" />
                                            Bongg!
                                        </button>
                                    @endif
                                </div>
                                @if ($isJoined)<p class="game-lead">{{ __('imposter.roomLead') }}</p>@endif

                                @if (count($players) > 0)
                                    <div class="game-hand" aria-hidden="true">
                                        @foreach ($players as $player)
                                            <span wire:key="hand-{{ $player['id'] }}" class="game-hand-card {{ $player['isMe'] ? 'is-me' : '' }}" style="--i: {{ $loop->index }}; --n: {{ count($players) }}">?</span>
                                        @endforeach
                                    </div>
                                    <ul class="game-seats">
                                        @foreach ($players as $player)
                                            <li wire:key="seat-{{ $player['id'] }}" class="game-seat {{ $player['isMe'] ? 'is-me' : '' }}">
                                                <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                                <span class="game-seat-name">{{ $player['name'] }}</span>
                                                <span class="game-seat-tags">
                                                    @if ($player['isHost'])
                                                        <span class="game-tag"><x-games.icon name="host" />{{ __('imposter.host') }}</span>
                                                    @endif
                                                    @if ($player['isMe'])
                                                        <span class="game-tag game-tag-accent">{{ __('games.you') }}</span>
                                                    @elseif ($isHost && $status === \App\Enums\ImposterRoomStatus::Lobby)
                                                        <button
                                                            type="button"
                                                            wire:click="openKickConfirm('{{ $player['id'] }}', '{{ addslashes($player['name']) }}')"
                                                            class="game-kick touch-manipulation"
                                                            title="{{ __('games.kick.action', ['name' => $player['name']]) }}"
                                                            aria-label="{{ __('games.kick.action', ['name' => $player['name']]) }}"
                                                        ><x-games.icon name="close" /></button>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif (! $isJoined && $inRound)
                    {{-- Visitor arrived after game started --}}
                    <div class="game-panel">
                        <div class="vk-statement">
                            <x-games.icon name="clock" />
                            <div>
                                <h2 class="game-title">{{ __('imposter.gameAlreadyStarted') }}</h2>
                                <p class="game-lead mt-2">{{ __('imposter.gameAlreadyStartedDesc') }}</p>
                            </div>
                        </div>
                    </div>
                @elseif ($status === \App\Enums\ImposterRoomStatus::Started && $isJoined)
                    <div class="game-panel">
                        <div class="game-panel-head">
                            <p class="brand-label"><span class="game-live" aria-hidden="true"></span>{{ __('imposter.gameStarted') }}</p>
                        </div>
                        <p class="game-lead">{{ __('imposter.gameStartedDesc') }}</p>

                        @if ($starterName && count($players) !== 3)
                            <div class="game-starter">
                                <p class="brand-label">{{ __('imposter.starterPlayer') }}</p>
                                <p class="game-title game-title-xl">{{ $starterName }}</p>
                                <p class="game-lead">{!! __('imposter.starterPlayerDesc', ['name' => '<strong>'.e($starterName).'</strong>']) !!}</p>
                            </div>
                        @endif

                        <ul class="game-chips" aria-label="{{ __('imposter.players') }}">
                            @foreach ($players as $player)
                                <li wire:key="table-{{ $player['id'] }}" class="game-chip {{ $starterName && count($players) !== 3 && $player['name'] === $starterName ? 'is-starter' : '' }}">
                                    <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" size="sm" />{{ $player['name'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @elseif ($status === \App\Enums\ImposterRoomStatus::Voting && $isJoined)
                    <div class="game-panel">
                        @if ($currentVoterId === $myPlayerId)
                            <p class="game-turn is-mine" role="status"><x-games.icon name="target" />{{ __('imposter.voting.yourTurn') }}</p>
                        @elseif (count($voterQueueNames) > 0)
                            <div class="game-turn">
                                <div class="min-w-0 flex-1">
                                    <p class="brand-label">{{ __('games.vote_order') }}</p>
                                    <p class="game-queue">
                                        @foreach ($voterQueueNames as $queuedName)
                                            @if (! $loop->first)<x-arrow dir="e" />@endif<span>{{ $queuedName }}</span>
                                        @endforeach
                                    </p>
                                </div>
                                <button wire:click="bongg" type="button" class="game-btn touch-manipulation" title="{{ __('games.nudge_one') }}">
                                    <x-games.icon name="bell" />
                                    Bongg!
                                </button>
                            </div>
                        @else
                            <p class="game-turn"><x-games.icon name="check" />{{ __('imposter.voting.allVoted') }}</p>
                        @endif

                        <div class="game-panel-head mt-2">
                            <h2 class="game-title">{{ __('imposter.voting.title') }}</h2>
                            @if ($myVote)
                                <span class="game-tag game-tag-ok"><x-games.icon name="check" />{{ __('imposter.voting.voted') }}</span>
                            @endif
                        </div>
                        <p class="game-lead">{{ __('imposter.voting.lead') }}</p>

                        @if ($myPlayerId !== null)
                            @php
                                $votesReceived = (int) ($voteCounts[$myPlayerId] ?? 0);
                                $votersOfMe = $voteMap[$myPlayerId] ?? [];
                            @endphp
                            @if ($votesReceived > 0)
                                <div class="game-onme">
                                    <p class="brand-label">{{ __('imposter.voting.votesReceived') }}</p>
                                    <p>{{ __('imposter.voting.votesReceivedCount', ['count' => $votesReceived]) }}</p>
                                    @if (count($votersOfMe) > 0)
                                        <div class="game-tally-voters">
                                            @foreach ($votersOfMe as $voterName)
                                                <span>{{ $voterName }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif

                        <div class="game-picks">
                            @foreach ($players as $player)
                                @if (! $player['isMe'])
                                    @php
                                        $playerVotes = (int) ($voteCounts[$player['id']] ?? 0);
                                        $canVote = ($currentVoterId === $myPlayerId);
                                    @endphp
                                    <button
                                        type="button"
                                        wire:key="vote-{{ $player['id'] }}"
                                        wire:click="vote('{{ $player['id'] }}')"
                                        @if(!$canVote) disabled @endif
                                        class="game-pick touch-manipulation {{ $myVote === $player['id'] ? 'is-picked' : '' }}"
                                    >
                                        <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                        <span class="game-pick-name"><span>{{ $player['name'] }}</span></span>
                                        <span class="game-pick-votes {{ $playerVotes > 0 ? 'has-votes' : '' }}" aria-label="{{ __('imposter.voting.voteCountLabel', ['count' => $playerVotes]) }}">{{ $playerVotes }}</span>
                                        <span class="game-pick-action">@if ($myVote === $player['id'])<x-games.icon name="target" />@endif{{ $myVote === $player['id'] ? __('imposter.voting.selected') : __('imposter.voting.select') }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @elseif ($status === \App\Enums\ImposterRoomStatus::Results && $isJoined)
                    @php $maxVotes = max(1, max(array_values($voteCounts) ?: [0])); @endphp

                    @if ($winner === 'crew')
                        <div class="game-verdict game-verdict-ink">
                            <x-games.icon name="crew" class="game-icon-lg" />
                            <p class="brand-label">{{ __('imposter.results.winner') }}</p>
                            <p class="game-title game-title-xl">{{ __('imposter.results.crewWins') }}</p>
                            <p>{{ __('imposter.results.crewWinsDesc', ['name' => $imposterName ?? '?']) }}</p>
                        </div>
                    @elseif ($winner === 'imposter')
                        <div class="game-verdict game-verdict-accent">
                            <x-games.icon name="imposter" class="game-icon-lg" />
                            <p class="brand-label">{{ __('imposter.results.winner') }}</p>
                            <p class="game-title game-title-xl">{{ __('imposter.results.imposterWins') }}</p>
                            <p>
                                @if ($hasImposterGuessed)
                                    {{ __('imposter.results.imposterWinsGuessedDesc') }}
                                @else
                                    {{ __('imposter.results.imposterWinsDesc', ['name' => $imposterName ?? '?']) }}
                                @endif
                            </p>
                        </div>
                    @endif

                    <div class="game-reveals">
                        @if ($imposterName)
                            <div class="game-card game-card-secret">
                                <span class="game-card-mark" aria-hidden="true">!</span>
                                <p class="brand-label">{{ __('imposter.voting.imposterWas') }}</p>
                                <p class="game-card-role">{{ $imposterName }}</p>
                            </div>
                        @endif
                        @if (is_string($revealedWord) && $revealedWord !== '')
                            <div class="game-card game-card-face">
                                <p class="brand-label">{{ __('imposter.voting.wordWas') }}</p>
                                <p class="game-role-word {{ $wordSize($revealedWord) }}">{{ $revealedWord }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="game-panel">
                        <h2 class="game-title">{{ __('imposter.voting.resultsTitle') }}</h2>
                        <ul class="game-tally">
                            @foreach ($players as $player)
                                @php
                                    $votes = (int) ($voteCounts[$player['id']] ?? 0);
                                    $voters = $voteMap[$player['id']] ?? [];
                                @endphp
                                <li wire:key="tally-{{ $player['id'] }}" class="game-tally-row {{ $votes > 0 && $votes === $maxVotes ? 'is-top' : '' }}">
                                    @if ($votes > 0)
                                        <span class="game-tally-bar" style="--share: {{ round(($votes / $maxVotes) * 100) }}%; --i: {{ $loop->index }}" aria-hidden="true"></span>
                                    @endif
                                    <span class="game-tally-main">
                                        <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                        <span class="game-seat-name">{{ $player['name'] }}</span>
                                        <span class="game-tally-count" aria-label="{{ __('imposter.voting.voteCountLabel', ['count' => $votes]) }}">{{ $votes }}</span>
                                    </span>
                                    @if (count($voters) > 0)
                                        <span class="game-tally-voters">
                                            @foreach ($voters as $voterName)
                                                <span>{{ $voterName }}</span>
                                            @endforeach
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="game-panel">
                        <h2 class="game-title">{{ __('imposter.joinThisRoom') }}</h2>
                        <p class="game-lead">{{ __('imposter.roomLead') }}</p>
                        <livewire:games.identity />

                        @if ($isPasswordProtected)
                            <label class="game-field">
                                <span class="game-field-label">{{ __('imposter.password') }}</span>
                                <input
                                    wire:model.live="joinPassword"
                                    type="password"
                                    autocomplete="off"
                                    spellcheck="false"
                                    class="game-input @error('joinPassword') is-invalid @enderror"
                                    placeholder="{{ __('imposter.placeholder.password') }}"
                                />
                                @error('joinPassword')
                                    <span class="game-field-error">{{ $message }}</span>
                                @enderror
                            </label>
                        @endif

                        <button
                            type="button"
                            wire:click="join"
                            @disabled(!\App\Support\GameIdentity::exists())
                            class="brand-button game-primary touch-manipulation"
                        >
                            <span>{{ __('imposter.join') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    </div>
                @endif
                </div>
                </div>
            </div>

            @if ($hostActions)
                {{-- Host controls stay under the thumb --}}
                <div class="game-host-bar" role="group" aria-label="{{ __('imposter.host') }}">
                    @if ($status === \App\Enums\ImposterRoomStatus::Lobby)
                        @if (count($players) < 3)
                            <p class="brand-label"><x-games.icon name="players" />{{ __('imposter.errorMinPlayers') }}</p>
                        @endif
                        <button type="button" wire:click="start" class="brand-button touch-manipulation">
                            <span>{{ __('imposter.start') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @elseif ($status === \App\Enums\ImposterRoomStatus::Started)
                        <button type="button" wire:click="$set('confirmImposterGuessed', true)" class="game-btn game-btn-danger touch-manipulation">
                            {{ __('imposter.host.imposterGuessed') }}
                        </button>
                        <button type="button" wire:click="startVoting" class="brand-button touch-manipulation">
                            <span>{{ __('imposter.voting.start') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @elseif ($status === \App\Enums\ImposterRoomStatus::Voting)
                        <button type="button" wire:click="$set('confirmImposterGuessed', true)" class="game-btn game-btn-danger touch-manipulation">
                            {{ __('imposter.host.imposterGuessed') }}
                        </button>
                        <button type="button" wire:click="revealVotes" class="brand-button touch-manipulation">
                            <span>{{ __('imposter.voting.reveal') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @elseif ($status === \App\Enums\ImposterRoomStatus::Results)
                        <button type="button" wire:click="newRound" class="brand-button touch-manipulation">
                            <span>{{ __('imposter.voting.newRound') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Reference stays in view in every phase --}}
        <x-games.how-to-play
            :title="__('imposter.howToPlay')"
            :rules="[__('imposter.rulesLine1'), __('imposter.rulesLine2'), __('imposter.rulesLine3')]"
            :tip="__('imposter.rulesTip')"
        />

    @endif

    {{-- Custom Modal for Imposter Guessed --}}
    @if ($confirmImposterGuessed)
        <div class="game-dialog-backdrop">
            <div class="game-dialog-scrim"></div>
            <div class="game-dialog game-dialog-sm" role="dialog" aria-modal="true" aria-labelledby="confirm-guessed-title">
                <x-games.icon name="imposter" class="game-icon-lg game-dialog-mark" />
                <h3 id="confirm-guessed-title">{{ __('imposter.modal.confirmGuessed.title') }}</h3>
                <p class="mt-3">{{ __('imposter.modal.confirmGuessed.desc') }}</p>
                <div class="game-dialog-actions" wire:loading.class="pointer-events-none opacity-50 block cursor-progress" wire:target="imposterGuessed">
                    <button type="button" wire:click="$set('confirmImposterGuessed', false)" class="game-btn touch-manipulation">
                        {{ __('imposter.modal.confirmGuessed.cancel') }}
                    </button>
                    <button type="button" wire:click="imposterGuessed" class="brand-button touch-manipulation">
                        <span>{{ __('imposter.modal.confirmGuessed.confirm') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
    {{-- Custom Modal for Kick Confirmation --}}
    @if ($confirmKickPlayerId)
        <div class="game-dialog-backdrop">
            <div class="game-dialog-scrim"></div>
            <div class="game-dialog game-dialog-sm" role="dialog" aria-modal="true" aria-labelledby="kick-title" @click.away="$wire.closeKickConfirm()">
                <h3 id="kick-title">{{ __('games.kick.title') }}</h3>
                <p class="mt-3">{!! __('games.kick.desc', ['name' => '<strong class="text-[var(--ink)]">'.e($confirmKickPlayerName).'</strong>']) !!}</p>
                <div class="game-dialog-actions">
                    <button type="button" wire:click="closeKickConfirm" class="game-btn touch-manipulation">
                        {{ __('games.kick.cancel') }}
                    </button>
                    <button type="button" wire:click="kickPlayer('{{ $confirmKickPlayerId }}')" class="brand-button touch-manipulation">
                        <span>{{ __('games.kick.confirm') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
