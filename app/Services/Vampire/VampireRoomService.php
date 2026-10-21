<?php

namespace App\Services\Vampire;

use App\Enums\VampireNightPhase;
use App\Enums\VampireRole;
use App\Enums\VampireRoomStatus;
use App\Services\Games\CacheRoomService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class VampireRoomService extends CacheRoomService
{
    public function __construct(
        private readonly VampireRoleDistributor $roleDistributor,
        private readonly VampireNightResolver $nightResolver,
        private readonly VampireWinnerResolver $winnerResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $hostPlayer
     * @return array<string, mixed>
     */
    protected function initialRoomState(
        string $code,
        string $hostPlayerId,
        array $hostPlayer,
        ?string $password,
        CarbonImmutable $now,
    ): array {
        return [
            'code' => $code,
            'status' => VampireRoomStatus::Lobby->value,
            'hostPlayerId' => $hostPlayerId,
            'createdAt' => $now->toIso8601String(),
            'startedAt' => null,
            'password' => $password,
            'config' => $this->normalizeConfig([]),
            'players' => [$hostPlayerId => $hostPlayer],
            'nightPhase' => null,
            'nightPhaseStartedAt' => null,
            'nightNumber' => 0,
            'nightVotes' => [],
            'doctorProtects' => null,
            'lastProtectedId' => null,
            'detectiveQuery' => null,
            'detectiveResult' => null,
            'detectiveInvestigationResults' => [],
            'interrogatedIds' => [],
            'nightResult' => null,
            'dayVotes' => [],
            'dayVotingStartedAt' => null,
            'dayResult' => null,
            'winner' => null,
            'gameOverAt' => null,
            'history' => [],
            'hunterLastShotSource' => null,
        ];
    }

    protected function roomNamespace(): string
    {
        return 'vampire';
    }

    protected function lobbyStatus(): string
    {
        return VampireRoomStatus::Lobby->value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function makePlayerPayload(string $playerId, string $name, string $color, string $emoji, CarbonImmutable $now): array
    {
        return parent::makePlayerPayload($playerId, $name, $color, $emoji, $now) + [
            'role' => null,
            'alignment' => null,
            'alive' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    public function updateConfig(string $code, string $hostId, array $config): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId, $config): ?array {
            $room = $this->getRoom($code);

            if ($room === null) {
                return null;
            }

            if (($room['hostPlayerId'] ?? null) !== $hostId || ($room['status'] ?? null) !== VampireRoomStatus::Lobby->value) {
                return $room;
            }

            $room['config'] = $this->normalizeConfig($config);
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function startGame(string $code, string $hostId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId): ?array {
            $room = $this->getRoom($code);

            if ($room === null) {
                return null;
            }

            if (($room['status'] ?? null) !== VampireRoomStatus::Lobby->value || ($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            $config = $this->normalizeConfig((array) ($room['config'] ?? []));

            if (! $this->canStartGame($players, $config)) {
                return $room;
            }

            $room['config'] = $config;
            $room['players'] = $this->assignRolesToPlayers($players, $config);
            $room['status'] = VampireRoomStatus::Night->value;
            $room['startedAt'] = CarbonImmutable::now()->toIso8601String();
            $room['roundId'] = (string) Str::uuid();
            $room['winner'] = null;
            $room['gameOverAt'] = null;
            $room['history'] = [];
            $room['hunterLastShotSource'] = null;

            $room = $this->prepareNightState($room, 1, true);

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function castNightVote(string $code, string $playerId, string $targetId, ?array $expectedRoom = null): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId, $targetId, $expectedRoom): ?array {
            $room = $this->getRoom($code);
            // Local simulation submits against a fresh snapshot; reject concurrent host/phase changes.
            if ($expectedRoom !== null && $room !== $expectedRoom) {
                return null;
            }

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::Night->value) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            if (! isset($players[$playerId], $players[$targetId])) {
                return $room;
            }

            if (($players[$playerId]['alignment'] ?? null) !== 'vampire' || ! ($players[$playerId]['alive'] ?? false) || $playerId === $targetId) {
                return $room;
            }

            $nightVotes = (array) ($room['nightVotes'] ?? []);
            if (($nightVotes[$playerId] ?? null) === $targetId) {
                unset($nightVotes[$playerId]);
            } else {
                $nightVotes[$playerId] = $targetId;
            }

            $room['nightVotes'] = $nightVotes;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function advanceNightPhase(string $code, string $hostId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::Night->value || ($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            $room['nightPhase'] = VampireNightPhase::Resolved->value;
            $room['nightPhaseStartedAt'] = CarbonImmutable::now()->toIso8601String();
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveDawn(string $code, string $hostId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::Night->value || ($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            $room = $this->nightResolver->resolve($room);
            if (($room['status'] ?? null) === VampireRoomStatus::HunterLastShot->value) {
                $room['hunterLastShotSource'] = 'night';
            }

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function hunterShoot(string $code, string $playerId, string $targetId, ?array $expectedRoom = null): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId, $targetId, $expectedRoom): ?array {
            $room = $this->getRoom($code);
            // Local simulation submits against a fresh snapshot; reject concurrent host/phase changes.
            if ($expectedRoom !== null && $room !== $expectedRoom) {
                return null;
            }

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::HunterLastShot->value) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            if (! isset($players[$playerId], $players[$targetId])) {
                return $room;
            }

            if (VampireRole::tryFrom((string) ($players[$playerId]['role'] ?? '')) !== VampireRole::Hunter) {
                return $room;
            }

            if (! ($players[$targetId]['alive'] ?? false)) {
                return $room;
            }

            $players[$targetId]['alive'] = false;
            $room['players'] = $players;
            $room['history'][] = [
                'icon' => '🎯',
                'type' => 'death',
                'key' => 'vampire.log.hunter_kill',
                'params' => ['name' => $players[$targetId]['name'] ?? 'Unknown'],
                'night' => $room['nightNumber'],
            ];

            $winner = $this->winnerResolver->resolve($players);
            if ($winner !== null) {
                $room = $this->markGameOver($room, $winner);
            } elseif (($room['hunterLastShotSource'] ?? null) === 'day') {
                $room = $this->prepareNightState($room, ((int) ($room['nightNumber'] ?? 0)) + 1);
                $room['status'] = VampireRoomStatus::Night->value;
                $room['history'][] = [
                    'icon' => '🌙',
                    'type' => 'narrative',
                    'key' => 'vampire.log.night_start',
                    'params' => ['number' => $room['nightNumber']],
                    'night' => $room['nightNumber'],
                ];
            } else {
                $room['status'] = VampireRoomStatus::Dawn->value;
            }

            $room['hunterLastShotSource'] = null;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function doctorProtect(string $code, string $playerId, string $targetId, ?array $expectedRoom = null): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId, $targetId, $expectedRoom): ?array {
            $room = $this->getRoom($code);
            // Local simulation submits against a fresh snapshot; reject concurrent host/phase changes.
            if ($expectedRoom !== null && $room !== $expectedRoom) {
                return null;
            }

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::Night->value) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            if (! isset($players[$playerId], $players[$targetId])) {
                return $room;
            }

            if (VampireRole::tryFrom((string) ($players[$playerId]['role'] ?? '')) !== VampireRole::Doctor || ! ($players[$playerId]['alive'] ?? false)) {
                return $room;
            }

            if ($targetId === ($room['lastProtectedId'] ?? null)) {
                return $room;
            }

            $room['doctorProtects'] = $targetId;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detectiveQuery(string $code, string $playerId, string $targetId, ?array $expectedRoom = null): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId, $targetId, $expectedRoom): ?array {
            $room = $this->getRoom($code);
            // Local simulation submits against a fresh snapshot; reject concurrent host/phase changes.
            if ($expectedRoom !== null && $room !== $expectedRoom) {
                return null;
            }

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::Night->value) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            if (! isset($players[$playerId], $players[$targetId])) {
                return $room;
            }

            if (VampireRole::tryFrom((string) ($players[$playerId]['role'] ?? '')) !== VampireRole::Detective || ! ($players[$playerId]['alive'] ?? false)) {
                return $room;
            }

            $interrogatedIds = array_values(array_map('strval', (array) ($room['interrogatedIds'] ?? [])));
            if (in_array($targetId, $interrogatedIds, true)) {
                return $room;
            }

            $room['detectiveQuery'] = $targetId;
            $room['detectiveResult'] = ($players[$targetId]['alignment'] ?? null) === 'vampire' ? 'vampire' : 'villager';
            $room['interrogatedIds'] = [...$interrogatedIds, $targetId];
            $detectiveInvestigationResults = (array) ($room['detectiveInvestigationResults'] ?? []);
            $detectiveInvestigationResults[$targetId] = $room['detectiveResult'];
            $room['detectiveInvestigationResults'] = $detectiveInvestigationResults;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function startDay(string $code, string $hostId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::Dawn->value || ($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            $room['status'] = VampireRoomStatus::Day->value;
            $room['detectiveResult'] = null;
            $room['nightResult'] = null;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function startDayVoting(string $code, string $hostId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::Day->value || ($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            $room['status'] = VampireRoomStatus::DayVoting->value;
            $room['dayVotingStartedAt'] = CarbonImmutable::now()->toIso8601String();
            $room['dayVotes'] = [];
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function castDayVote(string $code, string $playerId, string $targetId, ?array $expectedRoom = null): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId, $targetId, $expectedRoom): ?array {
            $room = $this->getRoom($code);
            // Local simulation submits against a fresh snapshot; reject concurrent host/phase changes.
            if ($expectedRoom !== null && $room !== $expectedRoom) {
                return null;
            }

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::DayVoting->value) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            if (! isset($players[$playerId], $players[$targetId])) {
                return $room;
            }

            if (! ($players[$playerId]['alive'] ?? false) || $playerId === $targetId) {
                return $room;
            }

            $dayVotes = (array) ($room['dayVotes'] ?? []);
            if (($dayVotes[$playerId] ?? null) === $targetId) {
                unset($dayVotes[$playerId]);
            } else {
                $dayVotes[$playerId] = $targetId;
            }

            $room['dayVotes'] = $dayVotes;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function revealDayVotes(string $code, string $hostId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::DayVoting->value || ($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            $dayVotes = (array) ($room['dayVotes'] ?? []);
            $voteCounts = [];
            foreach ($dayVotes as $targetId) {
                if (! is_string($targetId) || $targetId === '') {
                    continue;
                }

                $voteCounts[$targetId] = ($voteCounts[$targetId] ?? 0) + 1;
            }

            $eliminatedId = null;
            $eliminatedName = null;
            if ($voteCounts !== []) {
                $maxVotes = max($voteCounts);
                $topTargets = array_keys(array_filter($voteCounts, fn (int $count): bool => $count === $maxVotes));
                if (count($topTargets) === 1) {
                    $eliminatedId = $topTargets[0];
                    $eliminatedName = (string) (($room['players'][$eliminatedId]['name'] ?? ''));
                }
            }

            $room['status'] = VampireRoomStatus::DayResults->value;
            $room['dayResult'] = [
                'eliminatedId' => $eliminatedId,
                'eliminatedName' => $eliminatedName,
            ];

            if ($eliminatedId === null) {
                $room['history'][] = [
                    'icon' => '↳',
                    'type' => 'sub-narrative',
                    'key' => 'vampire.log.no_death_vote',
                    'params' => [],
                    'night' => $room['nightNumber'],
                ];
            }

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function confirmDayElimination(string $code, string $hostId, bool $confirm): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId, $confirm): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['status'] ?? null) !== VampireRoomStatus::DayResults->value || ($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            $dayResult = (array) ($room['dayResult'] ?? []);
            $eliminatedId = is_string($dayResult['eliminatedId'] ?? null) ? (string) $dayResult['eliminatedId'] : null;

            if ($confirm && $eliminatedId !== null && isset($players[$eliminatedId])) {
                $players[$eliminatedId]['alive'] = false;
                $room['players'] = $players;
                $room['history'][] = [
                    'icon' => '🗳',
                    'type' => 'death',
                    'key' => 'vampire.log.death_vote',
                    'params' => ['name' => $players[$eliminatedId]['name'] ?? 'Unknown'],
                    'night' => $room['nightNumber'],
                ];
            }

            $winner = $this->winnerResolver->resolve($players);
            if ($winner !== null) {
                $room = $this->markGameOver($room, $winner);
            } else {
                $hunterTriggered = $confirm
                    && $eliminatedId !== null
                    && isset($players[$eliminatedId])
                    && VampireRole::tryFrom((string) ($players[$eliminatedId]['role'] ?? '')) === VampireRole::Hunter;

                if ($hunterTriggered) {
                    $room['status'] = VampireRoomStatus::HunterLastShot->value;
                    $room['hunterLastShotSource'] = 'day';
                } else {
                    $room = $this->prepareNightState($room, ((int) ($room['nightNumber'] ?? 0)) + 1);
                    $room['status'] = VampireRoomStatus::Night->value;
                    $room['history'][] = [
                        'icon' => '🌙',
                        'type' => 'narrative',
                        'key' => 'vampire.log.night_start',
                        'params' => ['number' => $room['nightNumber']],
                        'night' => $room['nightNumber'],
                    ];
                }
            }

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function declareGameOver(string $code, string $hostId, string $winner): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            $room['status'] = VampireRoomStatus::GameOver->value;
            $room['winner'] = 'host_declared';
            $room['gameOverAt'] = CarbonImmutable::now()->toIso8601String();
            $room['history'][] = [
                'icon' => '🏁',
                'type' => 'gameover',
                'key' => 'vampire.log.game_over',
                'params' => [],
                'night' => $room['nightNumber'],
            ];
            $room['hunterLastShotSource'] = null;

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function startNewRound(string $code, string $hostId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['hostPlayerId'] ?? null) !== $hostId || ($room['status'] ?? null) !== VampireRoomStatus::GameOver->value) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            foreach ($players as $playerId => $player) {
                $players[$playerId]['role'] = null;
                $players[$playerId]['alignment'] = null;
                $players[$playerId]['alive'] = true;
            }

            $room['players'] = $players;
            $room['status'] = VampireRoomStatus::Lobby->value;
            $room['startedAt'] = null;
            $room['nightPhase'] = null;
            $room['nightPhaseStartedAt'] = null;
            $room['nightNumber'] = 0;
            $room['nightVotes'] = [];
            $room['doctorProtects'] = null;
            $room['lastProtectedId'] = null;
            $room['detectiveQuery'] = null;
            $room['detectiveResult'] = null;
            $room['detectiveInvestigationResults'] = [];
            $room['interrogatedIds'] = [];
            $room['nightResult'] = null;
            $room['dayVotes'] = [];
            $room['dayVotingStartedAt'] = null;
            $room['dayResult'] = null;
            $room['winner'] = null;
            $room['gameOverAt'] = null;
            $room['hunterLastShotSource'] = null;

            $this->putRoom($code, $room);

            return $room;
        });
    }

    public function triggerBongg(string $code, string $playerId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId): ?array {
            $room = $this->getRoom($code);

            if ($room === null) {
                return null;
            }

            $room['lastBonggAt'] = microtime(true);
            $room['lastBonggBy'] = $playerId;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $players
     * @param  array<string, mixed>  $config
     */
    private function canStartGame(array $players, array $config): bool
    {
        if (count($players) < 4) {
            return false;
        }

        $specialCount = ((bool) ($config['hasDoktor'] ?? false) ? 1 : 0)
            + ((bool) ($config['hasDedektif'] ?? false) ? 1 : 0)
            + ((bool) ($config['hasAvci'] ?? false) ? 1 : 0);

        $totalRequired = (int) ($config['vampireCount'] ?? 1) + (int) ($config['villagerCount'] ?? 2) + $specialCount;

        return (int) ($config['vampireCount'] ?? 1) >= 1
            && (int) ($config['villagerCount'] ?? 2) >= 1
            && $totalRequired === count($players);
    }

    /**
     * @param  array<string, array<string, mixed>>  $players
     * @param  array<string, mixed>  $config
     * @return array<string, array<string, mixed>>
     */
    private function assignRolesToPlayers(array $players, array $config): array
    {
        $rolePool = $this->roleDistributor->buildRolePool($config);
        $playerIds = array_keys($players);

        foreach ($playerIds as $index => $playerId) {
            $players[$playerId]['role'] = $rolePool[$index]['role'];
            $players[$playerId]['alignment'] = $rolePool[$index]['alignment'];
            $players[$playerId]['alive'] = true;
        }

        return $players;
    }

    /**
     * @param  array<string, mixed>  $room
     * @return array<string, mixed>
     */
    private function prepareNightState(array $room, int $nightNumber, bool $resetProtection = false): array
    {
        $room['status'] = VampireRoomStatus::Night->value;
        $room['nightPhase'] = VampireNightPhase::Vampires->value;
        $room['nightPhaseStartedAt'] = CarbonImmutable::now()->toIso8601String();
        $room['nightNumber'] = $nightNumber;
        $room['nightVotes'] = [];
        $room['doctorProtects'] = null;
        $room['detectiveQuery'] = null;
        $room['detectiveResult'] = null;
        $room['nightResult'] = null;
        $room['dayVotes'] = [];
        $room['dayVotingStartedAt'] = null;
        $room['dayResult'] = null;
        $room['hunterLastShotSource'] = null;

        if ($resetProtection) {
            $room['lastProtectedId'] = null;
        }

        return $room;
    }

    /**
     * @param  array<string, mixed>  $room
     * @return array<string, mixed>
     */
    private function markGameOver(array $room, string $winner): array
    {
        $room['status'] = VampireRoomStatus::GameOver->value;
        $room['winner'] = $winner;
        $room['gameOverAt'] = CarbonImmutable::now()->toIso8601String();
        $room['hunterLastShotSource'] = null;

        $room['history'][] = [
            'icon' => $winner === 'vampires' ? '🧛' : '🎉',
            'type' => 'gameover',
            'key' => $winner === 'vampires' ? 'vampire.log.vampires_win' : 'vampire.log.villagers_win',
            'params' => [],
            'night' => $room['nightNumber'],
        ];

        return $room;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $config): array
    {
        return [
            'vampireCount' => max(1, (int) ($config['vampireCount'] ?? 1)),
            'villagerCount' => max(1, (int) ($config['villagerCount'] ?? 2)),
            'hasDoktor' => (bool) ($config['hasDoktor'] ?? false),
            'hasDedektif' => (bool) ($config['hasDedektif'] ?? false),
            'hasAvci' => (bool) ($config['hasAvci'] ?? false),
        ];
    }
}
