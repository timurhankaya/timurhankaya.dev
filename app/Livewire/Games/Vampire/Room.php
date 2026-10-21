<?php

namespace App\Livewire\Games\Vampire;

use App\Enums\VampireRole;
use App\Enums\VampireRoomStatus;
use App\Livewire\Games\Concerns\WithLocalSimulation;
use App\Services\Vampire\VampireRoomService;
use App\Support\GameIdentity;
use App\Support\Games\VampireRoomPresenter;
use App\Support\Seo;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class Room extends Component
{
    use WithLocalSimulation;

    #[Locked]
    public string $roomCode;

    public VampireRoomStatus $status = VampireRoomStatus::Loading;

    public ?string $nightPhase = null;

    public ?string $nightPhaseStartedAt = null;

    #[Locked]
    public ?int $nightEndsAt = null;

    #[Locked]
    public int $serverNow = 0;

    public int $nightNumber = 0;

    public bool $isHost = false;

    public bool $isJoined = false;

    public ?string $myPlayerId = null;

    public ?string $myRole = null;

    public ?string $myAlignment = null;

    public bool $myAlive = true;

    /**
     * @var array<int, array{id: string, name: string, isHost: bool, isMe: bool, alive: bool}>
     */
    public array $players = [];

    // Night action state (filtered to own role)
    public ?string $myNightVote = null;

    public ?string $myDoctorTarget = null;

    public ?string $myDetectiveTarget = null;

    public ?string $detectiveResult = null;

    /** @var array<string, string> */
    public array $detectiveInvestigationResults = [];

    public ?string $lastProtectedId = null;

    public array $nightVoteCounts = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $nightResult = null;

    // Day voting
    /**
     * @var array<string, int>
     */
    public array $dayVoteCounts = [];

    /**
     * @var array<string, list<string>>
     */
    public array $dayVoteMap = [];

    public ?string $myDayVote = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $dayResult = null;

    // Config (host editable in lobby)
    public int $configVampireCount = 1;

    public int $configVillagerCount = 2;

    public bool $configHasDoktor = false;

    public bool $configHasDedektif = false;

    public bool $configHasAvci = false;

    // Game over
    public ?string $winner = null;

    public ?string $roomPassword = null;

    public ?string $joinPassword = null;

    public bool $isPasswordProtected = false;

    public bool $roomMissing = false;

    public bool $showPlayers = false;

    public bool $roleVisible = true;

    public ?string $confirmKickPlayerId = null;

    public ?string $confirmKickPlayerName = null;

    /** @var list<array{icon: string, type: string, text: string}> */
    public array $eventLog = [];

    /** @var list<array{icon: string, type: string, key: string, params: array<string, mixed>}> */
    public array $history = [];

    public ?string $error = null;

    /** @var array{title: string, message: string}|null */
    public ?array $notification = null;

    public ?VampireRoomStatus $lastStatus = null;

    public ?float $lastBonggAt = null;

    public function mount(string $roomCode, VampireRoomService $rooms): void
    {
        $this->roomCode = strtoupper($roomCode);
        $this->refreshRoom($rooms);
    }

    public function refreshRoom(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        $room = $rooms->getRoom($this->roomCode);
        if (is_string($playerId) && isset($room['players'][$playerId]) && ($room['status'] ?? null) === VampireRoomStatus::Night->value) {
            $rooms->resolveExpiredNight($this->roomCode);
        }
        $this->runAutomaticSimulation();
        $room = $rooms->getRoom($this->roomCode);
        $snapshot = app(VampireRoomPresenter::class)->present(
            $room,
            is_string($playerId) ? $playerId : null,
            $this->lastBonggAt,
        );

        $this->applySnapshot($snapshot);

        if (($snapshot['shouldPlayBongg'] ?? false) === true) {
            $this->dispatch('play-bongg');
        }

        // Check for phase transitions to trigger notifications
        // Built from the presented snapshot, the same data every player receives, never from the raw room.
        if ($room !== null && $this->lastStatus !== null && $this->lastStatus !== $this->status) {
            $this->handleStatusChange($this->lastStatus, $this->status, (array) ($snapshot['nightResult'] ?? []));
        }
        $this->lastStatus = $this->status;

        // Event log (computed from current state)
        $this->eventLog = $this->computeEventLog();
    }

    private function handleStatusChange(VampireRoomStatus $old, VampireRoomStatus $new, array $nightResult): void
    {
        if ($new === VampireRoomStatus::Dawn) {
            $this->notification = [
                'title' => (string) __('vampire.dawn.title'),
                'message' => $this->getDawnMessage($nightResult),
            ];
            $this->dispatch('show-notification');
        } elseif ($new === VampireRoomStatus::Day) {
            $this->notification = [
                'title' => (string) __('vampire.day.title'),
                'message' => (string) __('vampire.log.day_start'),
            ];
            $this->dispatch('show-notification');
        } elseif ($new === VampireRoomStatus::DayVoting) {
            $this->notification = [
                'title' => (string) __('vampire.voting.title'),
                'message' => (string) __('vampire.log.voting_start'),
            ];
            $this->dispatch('show-notification');
        }
    }

    /** @param  array<string, mixed>  $nightResult  the presented night result, without protection details */
    private function getDawnMessage(array $nightResult): string
    {
        $killedId = is_string($nightResult['killedId'] ?? null) ? (string) $nightResult['killedId'] : null;
        $saved = (bool) ($nightResult['saved'] ?? false);

        if ($killedId && ! $saved) {
            $name = (string) ($nightResult['killedName'] ?? 'Unknown');

            return (string) __('vampire.log.death_night', ['name' => $name]);
        }

        return (string) __('vampire.log.no_kill_night');
    }

    public function clearNotification(): void
    {
        $this->notification = null;
    }

    #[On('identity-saved')]
    public function onIdentitySaved(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '' || $this->status !== VampireRoomStatus::Lobby) {
            return;
        }

        $rooms->updatePlayerIdentity(
            $this->roomCode,
            $playerId,
            GameIdentity::name(),
            GameIdentity::color(),
            GameIdentity::emoji(),
        );
        $this->refreshRoom($rooms);
    }

    public function bongg(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->triggerBongg($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function join(VampireRoomService $rooms): void
    {
        $this->error = null;
        $this->resetErrorBag('joinPassword');

        if (! GameIdentity::exists()) {
            $this->error = (string) __('vampire.joinError');

            return;
        }

        if ($this->isPasswordProtected && empty($this->joinPassword)) {
            $this->addError('joinPassword', (string) __('vampire.errors.password_required'));

            return;
        }

        try {
            $joined = $rooms->joinRoom(
                $this->roomCode,
                GameIdentity::name(),
                $this->joinPassword,
                GameIdentity::color(),
                GameIdentity::emoji(),
            );
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() === 'wrong_password') {
                $this->addError('joinPassword', (string) __('vampire.errors.wrong_password'));

                return;
            }
            if ($e->getMessage() === 'game_started') {
                $this->error = (string) __('vampire.errors.game_started');

                return;
            }
            throw $e;
        }

        if ($joined === null) {
            $this->error = (string) __('vampire.joinError');

            return;
        }

        session()->put($this->playerSessionKey(), $joined['playerId']);
        $this->refreshRoom($rooms);
    }

    public function updateConfig(VampireRoomService $rooms): void
    {
        $this->error = null;
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->updateConfig($this->roomCode, $playerId, [
            'vampireCount' => $this->configVampireCount,
            'villagerCount' => $this->configVillagerCount,
            'hasDoktor' => $this->configHasDoktor,
            'hasDedektif' => $this->configHasDedektif,
            'hasAvci' => $this->configHasAvci,
        ]);

        $this->refreshRoom($rooms);
    }

    public function startGame(VampireRoomService $rooms): void
    {
        $this->error = null;
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $result = $rooms->startGame($this->roomCode, $playerId);
        $this->refreshRoom($rooms);

        // Detect validation failure: status didn't change
        if ($result !== null && ($result['status'] ?? null) === 'lobby') {
            $players = (array) ($result['players'] ?? []);
            $config = (array) ($result['config'] ?? []);
            $vampireCount = (int) ($config['vampireCount'] ?? 1);
            $villagerCount = (int) ($config['villagerCount'] ?? 2);
            $specialCount = ((bool) ($config['hasDoktor'] ?? false) ? 1 : 0)
                + ((bool) ($config['hasDedektif'] ?? false) ? 1 : 0)
                + ((bool) ($config['hasAvci'] ?? false) ? 1 : 0);
            $totalRequired = $vampireCount + $villagerCount + $specialCount;
            $totalPlayers = count($players);

            if ($totalPlayers < 4) {
                $this->error = (string) __('vampire.config.errorMinPlayers');
            } elseif ($totalRequired !== $totalPlayers) {
                $this->error = (string) __('vampire.config.errorMismatch', [
                    'required' => $totalRequired,
                    'players' => $totalPlayers,
                ]);
            }
        }
    }

    public function castNightVote(VampireRoomService $rooms, string $targetId): void
    {
        $this->error = null;
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->castNightVote($this->roomCode, $playerId, $targetId);
        $this->refreshRoom($rooms);
    }

    public function advanceNightPhase(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->advanceNightPhase($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function doctorProtect(VampireRoomService $rooms, string $targetId): void
    {
        $this->error = null;
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        // The rule (no protecting the same player two nights running) lives in the service; say why the pick did nothing.
        if ($this->lastProtectedId !== null && $targetId === $this->lastProtectedId) {
            $this->error = (string) __('vampire.errors.doctor_same_target');

            return;
        }

        $rooms->doctorProtect($this->roomCode, $playerId, $targetId);
        $this->refreshRoom($rooms);
    }

    public function detectiveQuery(VampireRoomService $rooms, string $targetId): void
    {
        $this->error = null;
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->detectiveQuery($this->roomCode, $playerId, $targetId);
        $this->refreshRoom($rooms);
    }

    public function resolveDawn(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->resolveDawn($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function hunterShoot(VampireRoomService $rooms, string $targetId): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->hunterShoot($this->roomCode, $playerId, $targetId);
        $this->refreshRoom($rooms);
    }

    public function startDay(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->startDay($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function startDayVoting(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->startDayVoting($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function castDayVote(VampireRoomService $rooms, string $targetId): void
    {
        $this->error = null;
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->castDayVote($this->roomCode, $playerId, $targetId);
        $this->refreshRoom($rooms);
    }

    public function revealDayVotes(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->revealDayVotes($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function confirmDayElimination(VampireRoomService $rooms, bool $confirm): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->confirmDayElimination($this->roomCode, $playerId, $confirm);
        $this->refreshRoom($rooms);
    }

    public function declareGameOver(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->declareGameOver($this->roomCode, $playerId, 'host_declared');
        $this->refreshRoom($rooms);
    }

    public function newRound(VampireRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->startNewRound($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function leave(VampireRoomService $rooms): mixed
    {
        $playerId = session()->get($this->playerSessionKey());
        if (is_string($playerId) && $playerId !== '') {
            $rooms->leaveRoom($this->roomCode, $playerId);
        }

        session()->forget($this->playerSessionKey());

        return redirect(Seo::route('games.vampire.index'));
    }

    public function openKickConfirm(string $id, string $name): void
    {
        $this->confirmKickPlayerId = $id;
        $this->confirmKickPlayerName = $name;
    }

    public function closeKickConfirm(): void
    {
        $this->confirmKickPlayerId = null;
        $this->confirmKickPlayerName = null;
    }

    public function kickPlayer(VampireRoomService $rooms, string $targetId): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '' || ! $this->isHost) {
            return;
        }

        $rooms->kickPlayer($this->roomCode, $playerId, $targetId);
        $this->closeKickConfirm();
        $this->refreshRoom($rooms);
    }

    public function toggleRole(): void
    {
        $this->roleVisible = ! $this->roleVisible;
    }

    public function openPlayers(): void
    {
        $this->showPlayers = true;
    }

    public function closePlayers(): void
    {
        $this->showPlayers = false;
    }

    public function render(): View
    {
        return view('livewire.games.vampire.room');
    }

    /** @return list<array{icon: string, type: string, text: string}> */
    private function computeEventLog(): array
    {
        $events = [];

        // 1. Permanent history from service
        foreach ($this->history as $h) {
            $events[] = [
                'icon' => $h['icon'],
                'type' => $h['type'],
                'text' => (string) __($h['key'], $h['params'] ?? []),
            ];
        }

        // 2. Current phase/action messages (Temporary - not stored in history)
        match ($this->status) {
            VampireRoomStatus::Night => (function () use (&$events): void {
                $events[] = ['icon' => '🌙', 'type' => 'narrative', 'text' => __('vampire.log.night_start', ['number' => $this->nightNumber])];

                $isVampireTurn = $this->myAlignment === 'vampire';
                $isDoctorTurn = VampireRole::tryFrom($this->myRole ?? '') === VampireRole::Doctor;
                $isDetectiveTurn = VampireRole::tryFrom($this->myRole ?? '') === VampireRole::Detective;

                if ($this->myAlive) {
                    if ($isVampireTurn) {
                        $events[] = ['icon' => '🧛', 'type' => 'action', 'text' => __('vampire.log.vampire_wake')];
                    }
                    if ($isDoctorTurn) {
                        $events[] = ['icon' => '💉', 'type' => 'action', 'text' => __('vampire.log.doctor_wake')];
                    }
                    if ($isDetectiveTurn) {
                        $events[] = ['icon' => '🔍', 'type' => 'action', 'text' => __('vampire.log.detective_wake')];
                    }

                    if (! $isVampireTurn && ! $isDoctorTurn && ! $isDetectiveTurn) {
                        // Role-specific waiting text
                        $waitingTextKey = VampireRole::tryFrom($this->myRole ?? '')?->waitingTextKey();
                        if ($waitingTextKey) {
                            $events[] = ['icon' => '✨', 'type' => 'narrative', 'text' => __($waitingTextKey)];
                        } else {
                            // Fallback to atmospheric messages
                            $seed = crc32($this->roomCode.$this->nightNumber);
                            $msgIndex = ($seed % 8) + 1;
                            $events[] = ['icon' => '✨', 'type' => 'narrative', 'text' => __('vampire.atmosphere.waiting.'.$msgIndex)];
                        }
                        $events[] = ['icon' => '💤', 'type' => 'narrative', 'text' => __('vampire.log.waiting_night')];
                    }
                } else {
                    $events[] = ['icon' => '💤', 'type' => 'narrative', 'text' => __('vampire.log.waiting_night')];
                }
            })(),
            VampireRoomStatus::Dawn => $events[] = ['icon' => '🌅', 'type' => 'narrative', 'text' => __('vampire.log.dawn_start')],
            VampireRoomStatus::HunterLastShot => (function () use (&$events): void {
                VampireRole::tryFrom($this->myRole ?? '') === VampireRole::Hunter
                    ? $events[] = ['icon' => '🎯', 'type' => 'action', 'text' => __('vampire.log.hunter_last_shot_self')]
                    : $events[] = ['icon' => '⚡', 'type' => 'warning', 'text' => __('vampire.log.hunter_last_shot_all')];
            })(),
            VampireRoomStatus::Day => $events[] = ['icon' => '☀️', 'type' => 'narrative', 'text' => __('vampire.log.day_start')],
            VampireRoomStatus::DayVoting => (function () use (&$events): void {
                $events[] = ['icon' => '☀️', 'type' => 'narrative', 'text' => __('vampire.log.day_start')];
                $events[] = ['icon' => '🗳', 'type' => 'narrative', 'text' => __('vampire.log.voting_start')];
            })(),
            VampireRoomStatus::DayResults => (function () use (&$events): void {
                $events[] = ['icon' => '☀️', 'type' => 'narrative', 'text' => __('vampire.log.day_start')];
                $events[] = ['icon' => '🗳', 'type' => 'narrative', 'text' => __('vampire.log.voting_start')];
                $events[] = ['icon' => '🔄', 'type' => 'narrative', 'text' => __('vampire.log.continue_game')];
            })(),
            default => null,
        };

        // 3. Few-players warning (Temporary)
        $alive = count(array_filter($this->players, fn (array $p) => $p['alive']));
        if ($alive <= 3 && in_array($this->status, [
            VampireRoomStatus::Dawn,
            VampireRoomStatus::Day,
            VampireRoomStatus::DayVoting,
            VampireRoomStatus::DayResults,
            VampireRoomStatus::HunterLastShot,
        ], true)) {
            $events[] = ['icon' => '⚠️', 'type' => 'warning', 'text' => __('vampire.log.few_players_warning', ['count' => $alive])];
        }

        return $events;
    }

    private function playerSessionKey(): string
    {
        return 'vampire.player.'.$this->roomCode;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function applySnapshot(array $snapshot): void
    {
        foreach ($snapshot as $property => $value) {
            if ($property === 'shouldPlayBongg') {
                continue;
            }

            $this->{$property} = $value;
        }
    }
}
