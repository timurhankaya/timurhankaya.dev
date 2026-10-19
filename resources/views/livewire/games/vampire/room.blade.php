@php
    $backUrl = \App\Support\Seo::route('games.vampire.index');
    $roomUrl = \App\Support\Seo::route('games.vampire.room', ['roomCode' => $roomCode]);
    // The sky and every panel follow the phase: cold night, warm dawn and day, a red edge while the village votes.
    $phase = match ($status) {
        \App\Enums\VampireRoomStatus::Night => 'night',
        \App\Enums\VampireRoomStatus::Dawn, \App\Enums\VampireRoomStatus::HunterLastShot => 'dawn',
        \App\Enums\VampireRoomStatus::Day => 'day',
        \App\Enums\VampireRoomStatus::DayVoting, \App\Enums\VampireRoomStatus::DayResults => 'verdict',
        \App\Enums\VampireRoomStatus::GameOver => $winner === 'vampires' ? 'night' : ($winner === 'villagers' ? 'day' : 'dawn'),
        default => 'lobby',
    };
    $roleIcons = [
        \App\Enums\VampireRole::Vampire->value => 'vampire',
        \App\Enums\VampireRole::Villager->value => 'villager',
        \App\Enums\VampireRole::Doctor->value => 'doctor',
        \App\Enums\VampireRole::Detective->value => 'detective',
        \App\Enums\VampireRole::Hunter->value => 'hunter',
    ];
    $hostStatuses = [\App\Enums\VampireRoomStatus::Lobby, \App\Enums\VampireRoomStatus::Night, \App\Enums\VampireRoomStatus::Dawn, \App\Enums\VampireRoomStatus::Day, \App\Enums\VampireRoomStatus::DayVoting, \App\Enums\VampireRoomStatus::DayResults, \App\Enums\VampireRoomStatus::GameOver];
    $hostActions = $isHost && in_array($status, $hostStatuses, true);
    $hasRole = $isJoined && $myRole !== null;
@endphp

<div class="game-room vk-room" data-phase="{{ $phase }}"
    x-data="{
        roleVisible: true,
        phaseCountdown: 45,
        phaseCountdownStarted: null,
        phaseCountdownInterval: null,
        playBongg() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') ctx.resume();
                const gain = ctx.createGain();
                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(1.0, ctx.currentTime + 0.05);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8);
                gain.connect(ctx.destination);
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
        },
        initPhaseCountdown(startedAt) {
            if (!startedAt) return;
            if (this.phaseCountdownInterval) clearInterval(this.phaseCountdownInterval);
            this.phaseCountdownStarted = new Date(startedAt + 'Z').getTime();
            this.phaseCountdown = 45;
            this.phaseCountdownInterval = setInterval(() => {
                const now = Date.now();
                const elapsed = Math.floor((now - this.phaseCountdownStarted) / 1000);
                this.phaseCountdown = Math.max(0, 45 - elapsed);
                if (this.phaseCountdown <= 0) {
                    clearInterval(this.phaseCountdownInterval);
                }
            }, 250);
        }
    }"
    x-on:play-bongg.window="playBongg()"
    x-on:show-notification.window="
        $nextTick(() => {
            setTimeout(() => { $wire.clearNotification() }, 8000);
        });
    "
>
    {{-- The sky behind the whole screen. Layers cross-fade and the moon sets as the phase changes. --}}
    <div class="vk-sky" data-sky="{{ $phase }}" aria-hidden="true">
        <span class="vk-sky-layer vk-sky-lobby"></span>
        <span class="vk-sky-layer vk-sky-night"></span>
        <span class="vk-sky-layer vk-sky-dawn"></span>
        <span class="vk-sky-layer vk-sky-day"></span>
        <span class="vk-sky-layer vk-sky-verdict"></span>
        <span class="vk-orb vk-moon"></span>
        <span class="vk-orb vk-sun"></span>
    </div>

    {{-- Line drawings on top of the sky: moon, stars and bats at night, sun and birds by day; the village roofline stays. --}}
    <x-games.doodles game="vampire" wire:ignore />

    {{-- Notification Modal --}}
    @if ($notification)
        @php
            // The notice titles are the phase titles, so match them to pick the phase icon.
            $noticeIcon = match ($notification['title']) {
                (string) __('vampire.dawn.title') => 'dawn',
                (string) __('vampire.day.title') => 'sun',
                (string) __('vampire.voting.title') => 'vote',
                default => 'bell',
            };
        @endphp
        <div
            x-data="{
                show: true,
                progress: 100,
                startCountdown() {
                    this.progress = 100;
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            this.progress = 0;
                        });
                    });
                }
            }"
            x-init="startCountdown()"
            x-show="show"
            class="vk-notice-wrap"
            role="dialog"
            aria-modal="true"
            aria-labelledby="vk-notice-title"
        >
            {{-- Backdrop --}}
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="vk-notice-scrim"
                aria-hidden="true"
            ></div>

            {{-- Modal Content --}}
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="translate-y-12 opacity-0 scale-95"
                x-transition:enter-end="translate-y-0 opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="translate-y-0 opacity-100 scale-100"
                x-transition:leave-end="translate-y-12 opacity-0 scale-95"
                class="vk-notice is-{{ $noticeIcon }}"
            >
                {{-- Sidebar Progress Bar --}}
                <div class="vk-notice-bar">
                    <div
                        :style="`height: ${progress}%; transition-duration: 8000ms;`"
                    ></div>
                </div>

                <div>
                    <x-games.icon :name="$noticeIcon" class="game-icon-lg" />
                    <h3 id="vk-notice-title">{{ $notification['title'] }}</h3>
                    <p>{{ $notification['message'] }}</p>
                </div>
                <button
                    @click="show = false; $wire.clearNotification()"
                    class="game-dialog-close touch-manipulation"
                    aria-label="{{ __('games.close') }}"
                >
                    <x-games.icon name="close" />
                </button>

                {{-- Action Suggestion (Atmospheric) --}}
                <p class="vk-notice-foot">{{ __('vampire.log.continue_game') }}</p>
            </div>
        </div>
    @endif

    @if ($roomMissing)
        <x-games.room-missing-state
            :back-url="$backUrl"
            :back-label="__('vampire.back')"
            :room-code-label="__('vampire.roomCode')"
            :title="__('vampire.roomNotFound')"
            :description="__('vampire.roomNotFoundDesc')"
            accent="red"
        />
    @else
        <div class="game-roombar">
            <button type="button" wire:click="leave" class="game-btn touch-manipulation">
                <x-arrow dir="w" />
                {{ $isJoined ? __('vampire.leave') : __('vampire.back') }}
            </button>
            <x-games.players-menu
                :players="$players"
                :label="__('vampire.players')"
                :host-label="__('vampire.host')"
                :empty-label="__('vampire.noOneYet')"
                :dead-label="__('vampire.dead')"
                :can-kick="$isHost && $status === \App\Enums\VampireRoomStatus::Lobby"
            />
        </div>

        <div
            class="game-arena"
            @if ($status === \App\Enums\VampireRoomStatus::Lobby)
                wire:poll.2s="refreshRoom"
            @else
                wire:poll.5s="refreshRoom"
            @endif
        >
            @if ($status === \App\Enums\VampireRoomStatus::Lobby)
                {{-- Room code and the ways to share it --}}
                <div class="game-code-block">
                    <div class="game-code-head">
                        <p class="brand-label">{{ __('vampire.roomCode') }}</p>
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
                    <div class="game-code" role="img" aria-label="{{ __('vampire.roomCode') }}: {{ $roomCode }}">
                        @foreach (mb_str_split($roomCode) as $char)<span aria-hidden="true">{{ $char }}</span>@endforeach
                    </div>
                    <div class="game-share">
                        <button
                            type="button"
                            class="game-btn game-copy touch-manipulation"
                            data-copy-text="{{ $roomCode }}"
                            data-copy-label="{{ __('vampire.copyCode') }}"
                            data-copy-label-success="{{ __('portfolio.ui.copied') }}"
                        >{{ __('vampire.copyCode') }}</button>
                        <button
                            type="button"
                            class="game-btn game-copy game-copy-link touch-manipulation"
                            data-copy-text="{{ $roomUrl }}"
                            data-copy-label="{{ __('vampire.copyLink') }}"
                            data-copy-label-success="{{ __('portfolio.ui.copied') }}"
                        >{{ __('vampire.copyLink') }}</button>
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

            {{-- Phase Banner --}}
            @if (in_array($status, [\App\Enums\VampireRoomStatus::Night, \App\Enums\VampireRoomStatus::Day, \App\Enums\VampireRoomStatus::Dawn, \App\Enums\VampireRoomStatus::DayVoting, \App\Enums\VampireRoomStatus::HunterLastShot, \App\Enums\VampireRoomStatus::GameOver], true))
                @php
                    $phaseIcon = match($status) {
                        \App\Enums\VampireRoomStatus::Night        => 'moon',
                        \App\Enums\VampireRoomStatus::Dawn         => 'dawn',
                        \App\Enums\VampireRoomStatus::Day          => 'sun',
                        \App\Enums\VampireRoomStatus::DayVoting    => 'vote',
                        \App\Enums\VampireRoomStatus::HunterLastShot => 'target',
                        \App\Enums\VampireRoomStatus::GameOver     => 'flag',
                        default => 'moon',
                    };
                    $phaseName = match($status) {
                        \App\Enums\VampireRoomStatus::Night        => __('vampire.night.title', ['number' => $nightNumber]),
                        \App\Enums\VampireRoomStatus::Dawn         => __('vampire.dawn.title'),
                        \App\Enums\VampireRoomStatus::Day          => __('vampire.day.title'),
                        \App\Enums\VampireRoomStatus::DayVoting    => __('vampire.voting.title'),
                        \App\Enums\VampireRoomStatus::HunterLastShot => __('vampire.hunter.title'),
                        \App\Enums\VampireRoomStatus::GameOver     => __('vampire.gameover.title'),
                        default => '',
                    };
                @endphp
                <div class="vk-phase" wire:key="phase-{{ $status->value }}-{{ $nightNumber }}">
                    <x-games.icon :name="$phaseIcon" />
                    <h2>{{ $phaseName }}</h2>
                    @if ($status === \App\Enums\VampireRoomStatus::Night && $nightPhase && \Illuminate\Support\Facades\Lang::has('vampire.night.phase.'.$nightPhase))
                        <p class="brand-label">{{ __('vampire.night.phase.'.$nightPhase) }}</p>
                    @endif
                </div>
            @endif

            <div class="game-arena-body">
                @php $doctorPanel = $status === \App\Enums\VampireRoomStatus::Night && $myAlive && $myRole === \App\Enums\VampireRole::Doctor->value; @endphp
                @if ($error && ! $doctorPanel)
                    <div class="game-error" role="alert"><x-games.icon name="alert" /><span>{{ $error }}</span></div>
                @endif

                <div class="{{ $hasRole ? 'game-round vk-round' : 'game-stage-only' }}">
                @if ($hasRole)
                    @php
                        $roleNameMap = [
                            \App\Enums\VampireRole::Vampire->value   => __('vampire.roles.vampir'),
                            \App\Enums\VampireRole::Villager->value  => __('vampire.roles.koylu'),
                            \App\Enums\VampireRole::Doctor->value    => __('vampire.roles.doktor'),
                            \App\Enums\VampireRole::Detective->value => __('vampire.roles.dedektif'),
                            \App\Enums\VampireRole::Hunter->value    => __('vampire.roles.avci'),
                        ];
                    @endphp
                    <section class="game-role vk-role {{ $status !== \App\Enums\VampireRoomStatus::Night ? 'is-compact' : '' }}" role="region" aria-label="{{ __('vampire.yourRole') }}">
                        <button
                            type="button"
                            x-on:click.stop.prevent="roleVisible = !roleVisible"
                            class="game-role-toggle touch-manipulation"
                            :aria-expanded="roleVisible ? 'true' : 'false'"
                            aria-controls="vampire-role-card-body"
                            id="vampire-role-card-toggle"
                        >
                            <span>
                                <strong>{{ __('vampire.yourRole') }}</strong>
                                <small>{{ __('vampire.privateRole') }}</small>
                            </span>
                            <span>
                                <x-games.icon name="eye-off" x-show="roleVisible" />
                                <x-games.icon name="eye" x-show="!roleVisible" x-cloak />
                                <span x-show="roleVisible">{{ __('vampire.roleHide') }}</span>
                                <span x-show="!roleVisible" x-cloak>{{ __('vampire.roleShow') }}</span>
                            </span>
                        </button>

                        <div id="vampire-role-card-body" x-show="roleVisible" role="region" aria-labelledby="vampire-role-card-toggle">
                            <div class="game-flip">
                                <div class="game-flip-inner">
                                    <div class="game-flip-face">
                                        <div class="game-card {{ $myAlignment === 'vampire' ? 'is-vampire' : '' }} {{ $myAlive ? '' : 'is-out' }}">
                                            <div class="game-card-split">
                                                <div class="grid gap-2">
                                                    <p class="brand-label">{{ __('vampire.youAre') }}</p>
                                                    <p class="game-card-role {{ mb_strlen($roleNameMap[$myRole] ?? $myRole) > 7 ? 'is-long' : '' }}">{{ $roleNameMap[$myRole] ?? $myRole }}</p>
                                                </div>
                                                <x-games.icon :name="$roleIcons[$myRole] ?? 'villager'" class="game-icon-lg" />
                                            </div>
                                            @if (! $myAlive)
                                                <span class="game-tag vk-team"><x-games.icon name="eliminated" />{{ __('vampire.eliminated') }}</span>
                                            @elseif ($myAlignment === 'vampire')
                                                <span class="game-tag game-tag-blood vk-team"><x-games.icon name="vampire" />{{ __('vampire.roles.vampirTeam') }}</span>
                                            @else
                                                <span class="game-tag game-tag-ok vk-team"><x-games.icon name="villager" />{{ __('vampire.roles.villagerTeam') }}</span>
                                            @endif

                                            {{-- Detective findings: every player this detective has checked, newest one marked --}}
                                            @if ($myRole === \App\Enums\VampireRole::Detective->value)
                                                @php
                                                    $findings = $detectiveInvestigationResults;
                                                    if ($detectiveResult !== null && $myDetectiveTarget !== null && ! isset($findings[$myDetectiveTarget])) {
                                                        $findings[$myDetectiveTarget] = $detectiveResult;
                                                    }
                                                @endphp
                                                @if (count($findings) > 0)
                                                    <div class="vk-findings">
                                                        <p class="brand-label">{{ __('games.findings') }}</p>
                                                        <ul>
                                                            @foreach ($findings as $suspectId => $finding)
                                                                @php
                                                                    $isVampire = $finding === 'vampire';
                                                                    $suspectName = collect($players)->firstWhere('id', (string) $suspectId)['name'] ?? '?';
                                                                @endphp
                                                                <li wire:key="finding-{{ $suspectId }}" class="vk-finding {{ $isVampire ? 'is-blood' : 'is-safe' }} {{ (string) $suspectId === $myDetectiveTarget ? 'is-latest' : '' }}">
                                                                    <x-games.icon :name="$isVampire ? 'vampire' : 'check'" />
                                                                    <span>{{ __('vampire.detective.result', ['name' => $suspectName, 'result' => $isVampire ? __('vampire.detective.isVampire') : __('vampire.detective.isVillager')]) }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div class="game-flip-face game-flip-back" aria-hidden="true">
                                        <div class="game-card game-card-back"><x-games.icon name="moon" class="game-icon-lg" /></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div x-show="!roleVisible" x-cloak class="game-card game-card-back game-card-hidden"><x-games.icon name="moon" class="game-icon-lg" /><small>{{ __('vampire.privateRole') }}</small></div>
                    </section>
                @endif

                <div class="game-stage">
                {{-- Lobby --}}
                @if ($status === \App\Enums\VampireRoomStatus::Lobby)
                    <div class="game-arena-split {{ $isJoined && ! $isHost ? 'is-joined' : '' }}">
                        <div class="game-side">
                            @if ($isHost)
                                {{-- Config panel --}}
                                @php
                                    $specialCount = ($configHasDoktor ? 1 : 0) + ($configHasDedektif ? 1 : 0) + ($configHasAvci ? 1 : 0);
                                    $totalRequired = $configVampireCount + $configVillagerCount + $specialCount;
                                    $totalPlayers = count($players);
                                    $configMatch = $totalRequired === $totalPlayers;
                                @endphp
                                <div class="game-panel">
                                    <p class="brand-label">{{ __('vampire.config.title') }}</p>
                                    <p class="game-lead">{{ __('vampire.config.hint') }}</p>

                                    <div class="vk-config">
                                        <div class="vk-config-row is-vampire">
                                            <x-games.icon name="vampire" />
                                            <label for="vk-config-vampires">{{ __('vampire.roles.vampir') }}</label>
                                            <input
                                                id="vk-config-vampires"
                                                wire:model.live="configVampireCount"
                                                wire:change="updateConfig"
                                                type="number"
                                                min="1"
                                                max="8"
                                                inputmode="numeric"
                                            />
                                        </div>
                                        <div class="vk-config-row">
                                            <x-games.icon name="villager" />
                                            <label for="vk-config-villagers">{{ __('vampire.roles.koylu') }}</label>
                                            <input
                                                id="vk-config-villagers"
                                                wire:model.live="configVillagerCount"
                                                wire:change="updateConfig"
                                                type="number"
                                                min="1"
                                                max="12"
                                                inputmode="numeric"
                                            />
                                        </div>

                                        <p class="brand-label mt-2">{{ __('vampire.config.specialRoles') }}</p>
                                        @foreach ([
                                            ['field' => 'configHasDoktor', 'label' => __('vampire.roles.doktor'), 'icon' => 'doctor'],
                                            ['field' => 'configHasDedektif', 'label' => __('vampire.roles.dedektif'), 'icon' => 'detective'],
                                            ['field' => 'configHasAvci', 'label' => __('vampire.roles.avci'), 'icon' => 'hunter'],
                                        ] as $toggle)
                                            <label class="vk-config-row vk-config-toggle">
                                                <x-games.icon :name="$toggle['icon']" />
                                                <span>{{ $toggle['label'] }}</span>
                                                <input
                                                    wire:model.live="{{ $toggle['field'] }}"
                                                    wire:change="updateConfig"
                                                    type="checkbox"
                                                />
                                                <span class="vk-switch" aria-hidden="true"></span>
                                            </label>
                                        @endforeach

                                        <p class="vk-config-total {{ $configMatch ? 'is-ok' : 'is-off' }}" role="status">
                                            <x-games.icon :name="$configMatch ? 'check' : 'alert'" />
                                            <span>
                                                @if ($configMatch)
                                                    {{ __('games.role_setup.ready') }}
                                                @elseif ($totalRequired > $totalPlayers)
                                                    {{ __('games.role_setup.too_many', ['required' => $totalRequired, 'players' => $totalPlayers]) }}
                                                @else
                                                    {{ __('games.role_setup.too_few', ['required' => $totalRequired, 'players' => $totalPlayers]) }}
                                                @endif
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <livewire:games.identity />
                            @elseif (! $isJoined)
                                <div class="game-panel">
                                    @if ($status !== \App\Enums\VampireRoomStatus::Lobby)
                                        <div class="vk-statement">
                                            <x-games.icon name="clock" />
                                            <div>
                                                <h3 class="game-title">{{ __('vampire.gameAlreadyStarted') }}</h3>
                                                <p class="game-lead mt-2">{{ __('vampire.gameAlreadyStartedDesc') }}</p>
                                            </div>
                                        </div>
                                    @else
                                        <h2 class="game-title">{{ __('vampire.joinThisRoom') }}</h2>
                                        <p class="game-lead">{{ __('vampire.joinThisRoomDesc') }}</p>
                                        <livewire:games.identity />

                                        @if ($isPasswordProtected)
                                            <label class="game-field">
                                                <span class="game-field-label">{{ __('vampire.password') }}</span>
                                                <input
                                                    wire:model.live="joinPassword"
                                                    type="password"
                                                    autocomplete="off"
                                                    spellcheck="false"
                                                    class="game-input @error('joinPassword') is-invalid @enderror"
                                                    placeholder="{{ __('vampire.placeholder.password') }}"
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
                                            <span>{{ __('vampire.join') }}</span>
                                            <x-arrow dir="e" />
                                        </button>
                                    @endif
                                </div>
                            @else
                                <div class="game-panel">
                                    <div class="game-panel-head">
                                        <p class="brand-label"><span class="game-live" aria-hidden="true"></span>{{ __('vampire.waitingForStart') }}</p>
                                    </div>
                                    <p class="game-lead">{{ __('vampire.waitingForStartDesc') }}</p>
                                </div>
                                <livewire:games.identity />
                            @endif
                        </div>

                        <div class="game-main">
                            <div class="game-panel">
                                <div class="game-panel-head">
                                    <p class="brand-label"><span class="game-live" aria-hidden="true"></span>{{ __('vampire.lobby') }} / {{ trans_choice('games.players_count', count($players)) }}</p>
                                    @if ($isJoined)
                                        <button wire:click="bongg" type="button" class="game-btn touch-manipulation" title="{{ __('games.nudge_all') }}">
                                            <x-games.icon name="bell" />
                                            Bongg!
                                        </button>
                                    @endif
                                </div>
                                @if ($isJoined)<p class="game-lead">{{ __('vampire.roomLead') }}</p>@endif

                                @if (count($players) > 0)
                                    <ul class="game-seats">
                                        @foreach ($players as $player)
                                            <li wire:key="seat-{{ $player['id'] }}" class="game-seat {{ $player['isMe'] ? 'is-me' : '' }}">
                                                <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                                <span class="game-seat-name">{{ $player['name'] }}</span>
                                                <span class="game-seat-tags">
                                                    @if ($player['isHost'])
                                                        <span class="game-tag"><x-games.icon name="host" />{{ __('vampire.host') }}</span>
                                                    @endif
                                                    @if ($player['isMe'])
                                                        <span class="game-tag game-tag-accent">{{ __('games.you') }}</span>
                                                    @elseif ($isHost && $status === \App\Enums\VampireRoomStatus::Lobby)
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

                {{-- Night Phase --}}
                @elseif ($status === \App\Enums\VampireRoomStatus::Night)
                    @php $nightActor = $myAlive && ($myAlignment === 'vampire' || in_array($myRole, [\App\Enums\VampireRole::Doctor->value, \App\Enums\VampireRole::Detective->value], true)); @endphp
                    @if (! $nightActor)
                        <div class="game-panel vk-closed">
                            <div class="vk-statement">
                                <x-games.icon name="eye-off" />
                                <div>
                                    <h3 class="game-title">{{ __('vampire.night.waiting') }}</h3>
                                    <p class="game-lead mt-2">{{ $isJoined ? __('vampire.night.waitingDesc') : __('vampire.night.desc') }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="game-hint"><x-games.icon name="moon" />{{ __('vampire.night.desc') }}</p>
                    @endif

                    {{-- Action Panels --}}
                    @if ($myAlive)
                        {{-- Vampire voting panel --}}
                        @if ($myAlignment === 'vampire')
                            <div class="game-panel vk-act is-vampire">
                                <div class="game-panel-head"><p class="brand-label"><x-games.icon name="vampire" />{{ __('vampire.night.vampireVote') }}</p></div>
                                <p class="game-lead">{{ __('vampire.night.vampireVoteDesc') }}</p>

                                <div class="game-picks">
                                    @foreach ($players as $player)
                                        @if (! $player['isMe'] && $player['alive'])
                                            <button
                                                wire:key="night-action-{{ $player['id'] }}"
                                                type="button"
                                                wire:click="castNightVote('{{ $player['id'] }}')"
                                                class="game-pick touch-manipulation {{ $myNightVote === $player['id'] ? 'is-picked' : '' }}"
                                            >
                                                <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                                <span class="game-pick-name">
                                                    <span>{{ $player['name'] }}</span>
                                                    @if ($player['isVampire'])
                                                        <small class="is-blood">{{ __('games.teammate') }}</small>
                                                    @endif
                                                    @if ($player['interrogated'] && isset($detectiveInvestigationResults[$player['id']]))
                                                        @php $investigationResult = $detectiveInvestigationResults[$player['id']]; @endphp
                                                        <small class="{{ $investigationResult === 'vampire' ? 'is-blood' : 'is-safe' }}">
                                                            {{ $investigationResult === 'vampire' ? __('vampire.detective.result.vampire') : __('vampire.detective.result.villager') }}
                                                        </small>
                                                    @endif
                                                </span>
                                                @if (isset($nightVoteCounts[$player['id']]) && $nightVoteCounts[$player['id']] > 0)
                                                    <span class="game-pick-votes has-votes">{{ $nightVoteCounts[$player['id']] }}</span>
                                                @endif
                                                <span class="game-pick-action">@if ($myNightVote === $player['id'])<x-games.icon name="vampire" />@endif{{ $myNightVote === $player['id'] ? __('vampire.voting.selected') : __('vampire.voting.select') }}</span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Doctor protection panel --}}
                        @if ($myRole === \App\Enums\VampireRole::Doctor->value)
                            <div class="game-panel vk-act">
                                <div class="game-panel-head"><p class="brand-label"><x-games.icon name="doctor" />{{ __('vampire.night.doctorProtect') }}</p></div>
                                <p class="game-lead">{{ __('vampire.night.doctorProtectDesc') }}</p>

                                @if ($error)
                                    <div class="game-error" role="alert"><x-games.icon name="alert" /><span>{{ $error }}</span></div>
                                @endif

                                <div class="game-picks">
                                    @foreach ($players as $player)
                                        @if ($player['alive'])
                                            @php $protectedLastNight = $lastProtectedId === $player['id']; @endphp
                                            <button
                                                type="button"
                                                wire:key="doctor-{{ $player['id'] }}"
                                                wire:click="doctorProtect('{{ $player['id'] }}')"
                                                @if ($protectedLastNight) aria-disabled="true" @endif
                                                class="game-pick touch-manipulation {{ $myDoctorTarget === $player['id'] ? 'is-picked' : '' }} {{ $protectedLastNight ? 'is-locked' : '' }}"
                                            >
                                                <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                                <span class="game-pick-name">
                                                    <span>{{ $player['name'] }}@if ($player['isMe']) <span class="text-[var(--muted)]">({{ __('games.you') }})</span>@endif</span>
                                                    @if ($protectedLastNight)
                                                        <small>{{ __('games.protected_last_night') }}</small>
                                                    @endif
                                                </span>
                                                @if ($protectedLastNight)
                                                    <span class="game-pick-action"><x-games.icon name="lock" /></span>
                                                @else
                                                    <span class="game-pick-action">@if ($myDoctorTarget === $player['id'])<x-games.icon name="protected" />@endif{{ $myDoctorTarget === $player['id'] ? __('vampire.voting.selected') : __('vampire.voting.select') }}</span>
                                                @endif
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Detective query panel --}}
                        @if ($myRole === \App\Enums\VampireRole::Detective->value)
                            <div class="game-panel vk-act">
                                <div class="game-panel-head"><p class="brand-label"><x-games.icon name="detective" />{{ __('vampire.night.detectiveQuery') }}</p></div>
                                <p class="game-lead">{{ __('vampire.night.detectiveQueryDesc') }}</p>

                                <div class="game-picks">
                                    @foreach ($players as $player)
                                        @if (! $player['isMe'] && $player['alive'] && ! $player['interrogated'])
                                            <button
                                                type="button"
                                                wire:key="detective-{{ $player['id'] }}"
                                                wire:click="detectiveQuery('{{ $player['id'] }}')"
                                                class="game-pick touch-manipulation {{ $myDetectiveTarget === $player['id'] ? 'is-picked' : '' }}"
                                            >
                                                <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                                <span class="game-pick-name"><span>{{ $player['name'] }}</span></span>
                                                <span class="game-pick-action">@if ($myDetectiveTarget === $player['id'])<x-games.icon name="detective" />@endif{{ $myDetectiveTarget === $player['id'] ? __('vampire.voting.selected') : __('vampire.voting.select') }}</span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                {{-- Dawn --}}
                @elseif ($status === \App\Enums\VampireRoomStatus::Dawn)
                    @php
                        $dawnKind = 'quiet';
                        if ($nightResult !== null) {
                            if (($nightResult['killedId'] ?? null) !== null && ($nightResult['savedByName'] ?? null) === null) {
                                $dawnKind = 'death';
                            } elseif (($nightResult['saved'] ?? false) === true) {
                                $dawnKind = 'save';
                            }
                        }
                    @endphp
                    <div class="game-panel">
                        <div class="vk-statement is-{{ $dawnKind }}">
                            <x-games.icon :name="match ($dawnKind) { 'death' => 'eliminated', 'save' => 'protected', default => 'dawn' }" />
                            <div>
                                <p class="brand-label">{{ __('vampire.dawn.title') }}</p>
                                @if ($nightResult !== null)
                                    @if (($nightResult['killedId'] ?? null) !== null && ($nightResult['savedByName'] ?? null) === null)
                                        <p class="game-title game-title-xl mt-2">
                                            {{ __('vampire.dawn.killed', ['name' => $nightResult['killedName'] ?? '?']) }}
                                        </p>
                                    @elseif (($nightResult['saved'] ?? false) === true)
                                        <p class="game-title game-title-xl mt-2">
                                            {{ __('vampire.dawn.savedBy') }}
                                        </p>
                                    @else
                                        <p class="game-title game-title-xl mt-2">{{ __('vampire.dawn.noKill') }}</p>
                                    @endif
                                @else
                                    <p class="game-lead mt-2">{{ __('vampire.dawn.desc') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Living players summary --}}
                    <div class="game-panel">
                        <p class="brand-label">{{ __('vampire.dawn.survivors') }}</p>
                        <ul class="game-chips">
                            @foreach ($players as $player)
                                <li wire:key="dawn-{{ $player['id'] }}" class="game-chip {{ $player['alive'] ? '' : 'is-out' }}">
                                    <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" size="sm" :out="! $player['alive']" />{{ $player['name'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                {{-- Hunter Last Shot --}}
                @elseif ($status === \App\Enums\VampireRoomStatus::HunterLastShot)
                    <div class="game-panel">
                        <div class="vk-statement is-death">
                            <x-games.icon name="hunter" />
                            <div>
                                <p class="game-title">{{ __('vampire.hunter.subtitle') }}</p>
                                <p class="game-lead mt-2">{{ __('vampire.hunter.desc') }}</p>
                            </div>
                        </div>
                    </div>

                    @if ($myRole === \App\Enums\VampireRole::Hunter->value && $myAlive === false)
                        <div class="game-panel vk-act">
                            <div class="game-panel-head"><p class="brand-label"><x-games.icon name="target" />{{ __('vampire.hunter.chooseTarget') }}</p></div>
                            <div class="game-picks">
                                @foreach ($players as $player)
                                    @if (! $player['isMe'] && $player['alive'])
                                        <button
                                            wire:key="hunter-shot-{{ $player['id'] }}"
                                            type="button"
                                            wire:click="hunterShoot('{{ $player['id'] }}')"
                                            class="game-pick touch-manipulation"
                                        >
                                            <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                            <span class="game-pick-name"><span>{{ $player['name'] }}</span></span>
                                            <span class="game-pick-action"><x-games.icon name="target" />{{ __('vampire.hunter.shoot') }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @elseif ($isJoined)
                        <p class="game-turn"><span class="game-live" aria-hidden="true"></span>{{ __('vampire.hunter.waiting') }}</p>
                    @endif

                {{-- Day --}}
                @elseif ($status === \App\Enums\VampireRoomStatus::Day)
                    <div class="game-panel">
                        <div class="vk-statement">
                            <x-games.icon name="players" />
                            <p class="game-lead">{{ __('vampire.day.desc') }}</p>
                        </div>
                    </div>

                    <div class="game-panel">
                        <p class="brand-label">{{ __('vampire.day.alivePlayers') }}</p>
                        <ul class="game-chips">
                            @foreach ($players as $player)
                                <li wire:key="day-alive-badge-{{ $player['id'] }}" class="game-chip {{ $player['alive'] ? '' : 'is-out' }}">
                                    <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" size="sm" :out="! $player['alive']" />{{ $player['name'] }}
                                    @if ($player['isHost'])
                                        <x-games.icon name="host" class="text-[var(--muted)]" />
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                {{-- Day Voting --}}
                @elseif ($status === \App\Enums\VampireRoomStatus::DayVoting)
                    <div class="game-panel">
                        <div class="game-panel-head">
                            <p class="game-lead">{{ __('vampire.voting.lead') }}</p>
                            @if ($myDayVote)
                                <span class="game-tag game-tag-ok"><x-games.icon name="check" />{{ __('vampire.voting.voted') }}</span>
                            @endif
                        </div>

                        @if ($isJoined && $myAlive)
                            <div class="game-picks">
                                @foreach ($players as $player)
                                    @if (! $player['isMe'] && $player['alive'])
                                        @php $playerVotes = (int) ($dayVoteCounts[$player['id']] ?? 0); @endphp
                                        <button
                                            wire:key="day-vote-btn-{{ $player['id'] }}"
                                            type="button"
                                            wire:click="castDayVote('{{ $player['id'] }}')"
                                            class="game-pick touch-manipulation {{ $myDayVote === $player['id'] ? 'is-picked' : '' }}"
                                        >
                                            <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" />
                                            <span class="game-pick-name"><span>{{ $player['name'] }}</span></span>
                                            <span class="game-pick-votes {{ $playerVotes > 0 ? 'has-votes' : '' }}">{{ $playerVotes }}</span>
                                            <span class="game-pick-action">@if ($myDayVote === $player['id'])<x-games.icon name="vote" />@endif{{ $myDayVote === $player['id'] ? __('vampire.voting.selected') : __('vampire.voting.select') }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                            <button wire:click="bongg" type="button" class="game-btn game-btn-quiet touch-manipulation justify-self-start" title="{{ __('games.nudge_all') }}">
                                <x-games.icon name="bell" />
                                Bongg!
                            </button>
                        @elseif ($isJoined && ! $myAlive)
                            <p class="game-hint"><x-games.icon name="eliminated" />{{ __('vampire.voting.eliminatedCannotVote') }}</p>
                        @endif
                    </div>

                {{-- Day Results --}}
                @elseif ($status === \App\Enums\VampireRoomStatus::DayResults)
                    @php $maxVotes = max(1, max(array_values($dayVoteCounts) ?: [0])); @endphp
                    @if ($dayResult !== null && ($dayResult['eliminatedId'] ?? null) !== null)
                        <div class="game-verdict vk-verdict">
                            <x-games.icon name="vote" class="game-icon-lg" />
                            <p class="brand-label">{{ __('vampire.day.eliminationResult') }}</p>
                            <p class="game-title game-title-xl">{{ $dayResult['eliminatedName'] ?? '' }}</p>
                        </div>
                    @endif

                    {{-- Vote tally --}}
                    <div class="game-panel">
                        <h3 class="game-title">{{ __('vampire.voting.resultsTitle') }}</h3>
                        <ul class="game-tally">
                            @foreach ($players as $player)
                                @php
                                    $pVotes = (int) ($dayVoteCounts[$player['id']] ?? 0);
                                    $pVoters = $dayVoteMap[$player['id']] ?? [];
                                @endphp
                                <li wire:key="day-tally-{{ $player['id'] }}" class="game-tally-row {{ $pVotes > 0 && $pVotes === $maxVotes ? 'is-top' : '' }} {{ $player['alive'] ? '' : 'is-out' }}">
                                    @if ($pVotes > 0)
                                        <span class="game-tally-bar" style="--share: {{ round(($pVotes / $maxVotes) * 100) }}%; --i: {{ $loop->index }}" aria-hidden="true"></span>
                                    @endif
                                    <span class="game-tally-main">
                                        <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" :out="! $player['alive']" />
                                        <span class="game-seat-name">{{ $player['name'] }}</span>
                                        <span class="game-tally-count">{{ $pVotes }}</span>
                                    </span>
                                    @if (count($pVoters) > 0)
                                        <span class="game-tally-voters">
                                            @foreach ($pVoters as $voterName)
                                                <span>{{ $voterName }}</span>
                                            @endforeach
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                {{-- Game Over --}}
                @elseif ($status === \App\Enums\VampireRoomStatus::GameOver)
                    @php
                        $vampiresWon = $winner === 'vampires';
                        $villageWon = $winner === 'villagers';
                        $finalRoleNames = [
                            \App\Enums\VampireRole::Vampire->value   => __('vampire.roles.vampir'),
                            \App\Enums\VampireRole::Villager->value  => __('vampire.roles.koylu'),
                            \App\Enums\VampireRole::Doctor->value    => __('vampire.roles.doktor'),
                            \App\Enums\VampireRole::Detective->value => __('vampire.roles.dedektif'),
                            \App\Enums\VampireRole::Hunter->value    => __('vampire.roles.avci'),
                        ];
                    @endphp
                    <div class="game-verdict {{ $vampiresWon ? 'vk-verdict-blood' : ($villageWon ? 'vk-verdict-day' : '') }}">
                        <x-games.icon :name="$vampiresWon ? 'vampire' : ($villageWon ? 'villager' : 'flag')" class="game-icon-lg" />
                        <p class="brand-label">{{ __('vampire.gameover.title') }}</p>
                        <p class="game-title game-title-xl">
                            @if ($vampiresWon)
                                {{ __('vampire.gameover.vampiresWin') }}
                            @elseif ($villageWon)
                                {{ __('vampire.gameover.villagersWin') }}
                            @else
                                {{ __('vampire.gameover.declared') }}
                            @endif
                        </p>
                    </div>

                    {{-- Reveal all roles --}}
                    <div class="game-panel">
                        <p class="brand-label">{{ __('vampire.gameover.roles') }}</p>
                        <p class="game-lead">{{ __('vampire.gameover.rolesHint') }}</p>
                        <ul class="game-seats">
                            @foreach ($players as $player)
                                @php $revealed = $player['revealedRole'] ?? null; @endphp
                                <li wire:key="final-{{ $player['id'] }}" class="game-seat vk-final {{ $player['alive'] ? '' : 'is-out' }}" style="--i: {{ $loop->index }}">
                                    <x-games.avatar :emoji="$player['emoji']" :color="$player['color']" :out="! $player['alive']" />
                                    <span class="game-seat-name"><span>{{ $player['name'] }}</span><small>{{ $player['alive'] ? __('vampire.alive') : __('vampire.dead') }}</small></span>
                                    @if ($revealed)
                                        <span class="game-tag vk-final-role {{ $revealed === \App\Enums\VampireRole::Vampire->value ? 'game-tag-blood' : '' }}"><x-games.icon :name="$roleIcons[$revealed] ?? 'villager'" />{{ $finalRoleNames[$revealed] ?? $revealed }}</span>
                                    @else
                                        <span class="game-tag {{ $player['alive'] ? 'game-tag-ok' : '' }}">{{ $player['alive'] ? __('vampire.alive') : __('vampire.dead') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                @else
                    {{-- Fallback / joined but game started state --}}
                    @if (! $isJoined)
                        <div class="game-panel">
                            <div class="vk-statement">
                                <x-games.icon name="clock" />
                                <div>
                                    <h3 class="game-title">{{ __('vampire.gameAlreadyStarted') }}</h3>
                                    <p class="game-lead mt-2">{{ __('vampire.gameAlreadyStartedDesc') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
                </div>

                {{-- Event Log --}}
                @if ($isJoined && count($eventLog) > 0 && count($history) > 0 && ! in_array($status, [\App\Enums\VampireRoomStatus::Lobby, \App\Enums\VampireRoomStatus::Loading, \App\Enums\VampireRoomStatus::Missing]))
                    @php
                        $alivePlayers = count(array_filter($players, fn($p) => $p['alive']));
                        $deadPlayers  = count($players) - $alivePlayers;
                        $groupedHistory = collect($history)->groupBy('night');
                        $logIcons = ['✝' => 'eliminated', '💚' => 'protected', '😮' => 'sun', '🎯' => 'target', '🌙' => 'moon', '🗳' => 'vote', '🏁' => 'flag', '🧛' => 'vampire', '🎉' => 'villager', '⚠️' => 'alert'];
                    @endphp
                    <div class="game-panel vk-log-panel">
                        <div class="game-panel-head">
                            <p class="brand-label">{{ __('vampire.eventLog') }}</p>
                            <span class="brand-label text-[var(--muted)]">{{ count($history) }} {{ __('vampire.eventLogCount') }}</span>
                        </div>

                        <div class="vk-log">
                            @forelse ($groupedHistory as $night => $events)
                                <div class="vk-log-night">
                                    <span class="brand-label">
                                        @if ($night == 0)
                                            {{ __('vampire.log.pre_game') }}
                                        @else
                                            {{ __('vampire.night.title', ['number' => $night]) }}
                                        @endif
                                    </span>
                                    <ol>
                                        @foreach ($events as $h)
                                            @php
                                                $logClass = match ($h['type']) {
                                                    'death' => 'is-death', 'save' => 'is-save', 'warning' => 'is-warning',
                                                    'gameover' => 'is-gameover', 'action' => 'is-action', 'sub-narrative' => 'is-sub', default => '',
                                                };
                                            @endphp
                                            <li class="{{ $logClass }}">
                                                @if ($h['type'] === 'sub-narrative')
                                                    <span aria-hidden="true"></span>
                                                @else
                                                    <x-games.icon :name="$logIcons[$h['icon']] ?? 'moon'" />
                                                @endif
                                                <span>{{ __($h['key'], $h['params'] ?? []) }}</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                </div>
                            @empty
                                <p class="game-lead">{{ __('vampire.log.empty') }}</p>
                            @endforelse
                        </div>

                        {{-- Player count footer --}}
                        @if ($deadPlayers > 0)
                            <div class="vk-log-foot">
                                <span><x-games.icon name="crew" />{{ $alivePlayers }} {{ __('vampire.alive') }}</span>
                                <span><x-games.icon name="eliminated" />{{ $deadPlayers }} {{ __('vampire.dead') }}</span>
                            </div>
                        @endif
                    </div>
                @endif
                </div>
            </div>

            {{-- Host action buttons --}}
            @if ($hostActions)
                <div class="game-host-bar" role="group" aria-label="{{ __('vampire.host') }}">
                    @if ($status === \App\Enums\VampireRoomStatus::Lobby)
                        <button type="button" wire:click="startGame" class="brand-button touch-manipulation">
                            <span>{{ __('vampire.start') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @elseif ($status === \App\Enums\VampireRoomStatus::Night)
                        <button type="button" wire:click="resolveDawn" class="brand-button touch-manipulation">
                            <span>{{ __('vampire.night.resolveDawn') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @elseif ($status === \App\Enums\VampireRoomStatus::Dawn)
                        <button type="button" wire:click="startDay" class="brand-button touch-manipulation">
                            <span>{{ __('vampire.dawn.startDay') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @elseif ($status === \App\Enums\VampireRoomStatus::Day)
                        <button type="button" wire:click="startDayVoting" class="brand-button touch-manipulation">
                            <span>{{ __('vampire.day.startVoting') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @elseif ($status === \App\Enums\VampireRoomStatus::DayVoting)
                        <button type="button" wire:click="revealDayVotes" class="brand-button touch-manipulation">
                            <span>{{ __('vampire.voting.reveal') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @elseif ($status === \App\Enums\VampireRoomStatus::DayResults)
                        @if ($dayResult !== null && ($dayResult['eliminatedId'] ?? null) !== null)
                            <button type="button" wire:click="confirmDayElimination(true)" class="brand-button touch-manipulation">
                                <span>{{ __('vampire.day.confirmElimination') }}</span>
                                <x-arrow dir="e" />
                            </button>
                            <button type="button" wire:click="confirmDayElimination(false)" class="game-btn touch-manipulation">
                                {{ __('vampire.day.skipElimination') }}
                            </button>
                        @else
                            <button type="button" wire:click="confirmDayElimination(false)" class="brand-button touch-manipulation">
                                <span>{{ __('vampire.night.title', ['number' => $nightNumber + 1]) }}</span>
                                <x-arrow dir="e" />
                            </button>
                        @endif
                    @elseif ($status === \App\Enums\VampireRoomStatus::GameOver)
                        <button type="button" wire:click="newRound" class="brand-button touch-manipulation">
                            <span>{{ __('vampire.gameover.newRound') }}</span>
                            <x-arrow dir="e" />
                        </button>
                    @endif

                    @if (in_array($status, [\App\Enums\VampireRoomStatus::Night, \App\Enums\VampireRoomStatus::Day, \App\Enums\VampireRoomStatus::Dawn, \App\Enums\VampireRoomStatus::DayVoting, \App\Enums\VampireRoomStatus::DayResults], true))
                        <button type="button" wire:click="declareGameOver" class="game-btn game-btn-quiet touch-manipulation">
                            <x-games.icon name="flag" />
                            {{ __('vampire.gameover.declare') }}
                        </button>
                    @endif
                </div>
            @endif
        </div>

        {{-- Reference stays in view in every phase --}}
        <x-games.how-to-play
            :title="__('vampire.howToPlay')"
            :rules="[__('vampire.rulesLine1'), __('vampire.rulesLine2'), __('vampire.rulesLine3'), __('vampire.rulesLine4')]"
        />
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
