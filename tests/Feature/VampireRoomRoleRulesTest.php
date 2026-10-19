<?php

use App\Enums\VampireRole;
use App\Services\Vampire\VampireRoomService;
use Illuminate\Support\Facades\Cache;

afterEach(function () {
    Cache::flush();
});

it('rejects doctor protecting the same player as the previous night', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerVampireRoomWithDoctor($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $doctorId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Doctor->value);
    $koyluIds = array_keys(array_filter($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Villager->value));
    expect($koyluIds)->toHaveCount(2);
    [$v1, $v2] = $koyluIds;

    $rooms->castNightVote($ctx['code'], $vampireId, $v1);
    $rooms->doctorProtect($ctx['code'], $doctorId, $v1);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);

    $afterNight1 = $rooms->getRoom($ctx['code']);
    expect($afterNight1['players'][$v1]['alive'])->toBeTrue();
    expect($afterNight1['lastProtectedId'])->toBe($v1);

    advanceDayWithoutElimination($rooms, $ctx['code'], $ctx['hostPlayerId']);

    $beforeBlocked = $rooms->getRoom($ctx['code']);
    $rooms->doctorProtect($ctx['code'], $doctorId, $v1);
    $afterBlocked = $rooms->getRoom($ctx['code']);

    expect($afterBlocked['doctorProtects'])->toBeNull();
    expect($afterBlocked['lastProtectedId'])->toBe($v1);
    expect($afterBlocked['nightNumber'])->toBe($beforeBlocked['nightNumber']);

    $rooms->doctorProtect($ctx['code'], $doctorId, $v2);
    expect($rooms->getRoom($ctx['code'])['doctorProtects'])->toBe($v2);

    $rooms->castNightVote($ctx['code'], $vampireId, $v1);
    $resolved = $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);

    expect($resolved['players'][$v1]['alive'])->toBeFalse();
    expect($resolved['lastProtectedId'])->toBe($v2);
});

it('allows doctor to protect a previous target after a different target was protected last night', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerVampireRoomWithDoctor($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $doctorId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Doctor->value);
    $koyluIds = array_keys(array_filter($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Villager->value));
    [$v1, $v2] = $koyluIds;

    // Night 1: save v1
    $rooms->castNightVote($ctx['code'], $vampireId, $v1);
    $rooms->doctorProtect($ctx['code'], $doctorId, $v1);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);
    advanceDayWithoutElimination($rooms, $ctx['code'], $ctx['hostPlayerId']);

    // Night 2: protect v2, kill v1 (no save for v1)
    $rooms->doctorProtect($ctx['code'], $doctorId, $v2);
    $rooms->castNightVote($ctx['code'], $vampireId, $v1);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);
    expect($rooms->getRoom($ctx['code'])['players'][$v1]['alive'])->toBeFalse();
    expect($rooms->getRoom($ctx['code'])['lastProtectedId'])->toBe($v2);

    advanceDayWithoutElimination($rooms, $ctx['code'], $ctx['hostPlayerId']);

    // Night 3: v1 was protected two nights ago — allowed again (last night was v2)
    $rooms->doctorProtect($ctx['code'], $doctorId, $v1);
    expect($rooms->getRoom($ctx['code'])['doctorProtects'])->toBe($v1);
});

it('ignores doctorProtect from a non-doctor role', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerVampireRoomWithDoctor($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $koyluIds = array_keys(array_filter($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Villager->value));
    $target = $koyluIds[0];

    $before = $rooms->getRoom($ctx['code']);
    $rooms->doctorProtect($ctx['code'], $vampireId, $target);
    $after = $rooms->getRoom($ctx['code']);

    expect($after['doctorProtects'])->toBeNull();
    expect($after['nightNumber'])->toBe($before['nightNumber']);
});

it('ignores doctorProtect when the doctor is dead', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerVampireRoomWithDoctor($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $doctorId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Doctor->value);
    $survivorId = firstPlayerIdWhere(
        $players,
        fn ($p, $id) => $id !== $doctorId && $id !== $vampireId && ($p['alive'] ?? false),
    );

    $rooms->castNightVote($ctx['code'], $vampireId, $doctorId);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);
    expect($rooms->getRoom($ctx['code'])['players'][$doctorId]['alive'])->toBeFalse();

    advanceDayWithoutElimination($rooms, $ctx['code'], $ctx['hostPlayerId']);

    $rooms->doctorProtect($ctx['code'], $doctorId, $survivorId);
    expect($rooms->getRoom($ctx['code'])['doctorProtects'])->toBeNull();
});

it('detective query marks a vampire as vampire alignment', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFivePlayerRoom(
        $rooms,
        vampireCount: 1,
        villagerCount: 3,
        hasDoktor: false,
        hasDedektif: true,
        hasAvci: false,
    );

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $detectiveId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Detective->value);
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');

    $rooms->detectiveQuery($ctx['code'], $detectiveId, $vampireId);
    expect($rooms->getRoom($ctx['code'])['detectiveResult'])->toBe('vampire');
});

it('detective query marks a villager-aligned player as villager', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFivePlayerRoom(
        $rooms,
        vampireCount: 1,
        villagerCount: 3,
        hasDoktor: false,
        hasDedektif: true,
        hasAvci: false,
    );

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $detectiveId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Detective->value);
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $villagerId = firstPlayerIdWhere(
        $players,
        fn ($p, $id) => ($p['alignment'] ?? null) === 'villager' && $id !== $detectiveId,
    );

    expect($villagerId)->not->toBe($vampireId);

    $rooms->detectiveQuery($ctx['code'], $detectiveId, $villagerId);
    expect($rooms->getRoom($ctx['code'])['detectiveResult'])->toBe('villager');
});

it('ignores detectiveQuery from a non-detective role', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFivePlayerRoom(
        $rooms,
        vampireCount: 1,
        villagerCount: 3,
        hasDoktor: false,
        hasDedektif: true,
        hasAvci: false,
    );

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $koyluId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Villager->value);

    $rooms->detectiveQuery($ctx['code'], $koyluId, $vampireId);
    expect($rooms->getRoom($ctx['code'])['detectiveQuery'])->toBeNull();
    expect($rooms->getRoom($ctx['code'])['detectiveResult'])->toBeNull();
});

it('produces no night kill when vampire votes are tied', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createSixPlayerTwoVampireRoom($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireIds = array_keys(array_filter($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire'));
    expect($vampireIds)->toHaveCount(2);

    $villagerIds = array_keys(array_filter($players, fn ($p) => ($p['alignment'] ?? null) === 'villager'));
    $a = $villagerIds[0];
    $b = $villagerIds[1];

    $rooms->castNightVote($ctx['code'], $vampireIds[0], $a);
    $rooms->castNightVote($ctx['code'], $vampireIds[1], $b);

    $result = $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);

    expect($result['nightResult']['killedId'])->toBeNull();
    foreach ($players as $id => $_) {
        expect($result['players'][$id]['alive'])->toBeTrue();
    }
});

it('allows vampires to retract a night vote by voting the same target again', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerPlainVillagers($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $target = firstPlayerIdWhere($players, fn ($p, $id) => $id !== $vampireId);

    $rooms->castNightVote($ctx['code'], $vampireId, $target);
    expect($rooms->getRoom($ctx['code'])['nightVotes'])->toHaveKey($vampireId);

    $rooms->castNightVote($ctx['code'], $vampireId, $target);
    expect($rooms->getRoom($ctx['code'])['nightVotes'])->not->toHaveKey($vampireId);
});

it('ignores night vote from a dead vampire', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createSixPlayerTwoVampireRoom($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireIds = array_keys(array_filter($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire'));
    $vampireA = $vampireIds[0];
    $vampireB = $vampireIds[1];
    $villagerIds = array_keys(array_filter($players, fn ($p) => ($p['alignment'] ?? null) === 'villager'));

    // Night 1: tied vampire votes → no kill
    $rooms->castNightVote($ctx['code'], $vampireA, $villagerIds[0]);
    $rooms->castNightVote($ctx['code'], $vampireB, $villagerIds[1]);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);

    $rooms->startDay($ctx['code'], $ctx['hostPlayerId']);
    $rooms->startDayVoting($ctx['code'], $ctx['hostPlayerId']);
    foreach ($villagerIds as $vid) {
        $rooms->castDayVote($ctx['code'], $vid, $vampireA);
    }
    $rooms->revealDayVotes($ctx['code'], $ctx['hostPlayerId']);
    $rooms->confirmDayElimination($ctx['code'], $ctx['hostPlayerId'], true);

    $prey = $villagerIds[2];
    $rooms->castNightVote($ctx['code'], $vampireA, $prey);
    $rooms->castNightVote($ctx['code'], $vampireB, $prey);

    $afterDeadVote = $rooms->getRoom($ctx['code']);
    expect($afterDeadVote['nightVotes'])->toHaveCount(1);
    expect($afterDeadVote['nightVotes'])->toHaveKey($vampireB);
    expect($afterDeadVote['nightVotes'][$vampireB] ?? null)->toBe($prey);
});

it('hunter last shot killing the last vampire ends the game for villagers', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerRoom($rooms, hasDoktor: false, hasDedektif: false, hasAvci: true);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $hunterId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Hunter->value);

    $rooms->castNightVote($ctx['code'], $vampireId, $hunterId);
    $afterNight = $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);

    expect($afterNight['status'])->toBe('hunter_last_shot');

    $final = $rooms->hunterShoot($ctx['code'], $hunterId, $vampireId);

    expect($final['status'])->toBe('game_over');
    expect($final['winner'])->toBe('villagers');
    expect($final['players'][$vampireId]['alive'])->toBeFalse();
});

it('hunter last shot killing a villager continues to dawn when parity does not end the game', function () {
    Cache::flush();

    // 1 vamp, 2 koylu, doktor, avcı — avcı bir köylüyü vurunca hâlâ 2 köylü + 1 vampir
    $rooms = app(VampireRoomService::class);
    $ctx = createFivePlayerRoom(
        $rooms,
        vampireCount: 1,
        villagerCount: 2,
        hasDoktor: true,
        hasDedektif: false,
        hasAvci: true,
    );

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $hunterId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Hunter->value);
    $villagerTarget = firstPlayerIdWhere(
        $players,
        fn ($p, $id) => $id !== $hunterId && $id !== $vampireId && ($p['role'] ?? null) === VampireRole::Villager->value,
    );

    $rooms->castNightVote($ctx['code'], $vampireId, $hunterId);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);

    $final = $rooms->hunterShoot($ctx['code'], $hunterId, $villagerTarget);

    expect($final['status'])->toBe('dawn');
    expect($final['winner'])->toBeNull();
});

it('ignores hunterShoot from a non-hunter role', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerRoom($rooms, hasDoktor: false, hasDedektif: false, hasAvci: true);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $hunterId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Hunter->value);

    $rooms->castNightVote($ctx['code'], $vampireId, $hunterId);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);

    $before = $rooms->getRoom($ctx['code']);
    $victimId = firstPlayerIdWhere($players, fn ($p, $id) => $id !== $vampireId && $id !== $hunterId);

    $rooms->hunterShoot($ctx['code'], $vampireId, $victimId);
    $after = $rooms->getRoom($ctx['code']);

    expect($after['players'][$victimId]['alive'])->toBeTrue();
    expect($after['status'])->toBe($before['status']);
});

it('clears lastProtectedId when host starts a new round after game over', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerVampireRoomWithDoctor($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $doctorId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Doctor->value);
    $v1 = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Villager->value);

    $rooms->castNightVote($ctx['code'], $vampireId, $v1);
    $rooms->doctorProtect($ctx['code'], $doctorId, $v1);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);
    expect($rooms->getRoom($ctx['code'])['lastProtectedId'])->toBe($v1);

    $rooms->declareGameOver($ctx['code'], $ctx['hostPlayerId'], 'host');
    $lobby = $rooms->startNewRound($ctx['code'], $ctx['hostPlayerId']);

    expect($lobby['lastProtectedId'])->toBeNull();
});

it('day voting tie leaves no eliminated player on reveal', function () {
    Cache::flush();

    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerPlainVillagers($rooms);

    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $villagerIds = array_keys(array_filter($players, fn ($p) => ($p['alignment'] ?? null) === 'villager'));
    expect($villagerIds)->toHaveCount(3);

    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);
    $rooms->startDay($ctx['code'], $ctx['hostPlayerId']);
    $rooms->startDayVoting($ctx['code'], $ctx['hostPlayerId']);

    // Two villagers vote for different targets → top tie
    $rooms->castDayVote($ctx['code'], $villagerIds[0], $villagerIds[1]);
    $rooms->castDayVote($ctx['code'], $villagerIds[1], $villagerIds[2]);

    $revealed = $rooms->revealDayVotes($ctx['code'], $ctx['hostPlayerId']);

    expect($revealed['dayResult']['eliminatedId'])->toBeNull();
});

/**
 * @return array{code: string, hostPlayerId: string}
 */
function createFourPlayerVampireRoomWithDoctor(VampireRoomService $rooms): array
{
    $created = $rooms->createRoom('Host', null, 'red', '🧛');
    $rooms->joinRoom($created['code'], 'P2', null, 'sky', '🐺');
    $rooms->joinRoom($created['code'], 'P3', null, 'purple', '🕵️');
    $rooms->joinRoom($created['code'], 'P4', null, 'green', '👑');

    $rooms->updateConfig($created['code'], $created['hostPlayerId'], [
        'vampireCount' => 1,
        'villagerCount' => 2,
        'hasDoktor' => true,
        'hasDedektif' => false,
        'hasAvci' => false,
    ]);

    $rooms->startGame($created['code'], $created['hostPlayerId']);

    return ['code' => $created['code'], 'hostPlayerId' => $created['hostPlayerId']];
}

/**
 * @return array{code: string, hostPlayerId: string}
 */
function createFourPlayerPlainVillagers(VampireRoomService $rooms): array
{
    return createFourPlayerRoom($rooms, hasDoktor: false, hasDedektif: false, hasAvci: false);
}

/**
 * @return array{code: string, hostPlayerId: string}
 */
function createFourPlayerRoom(VampireRoomService $rooms, bool $hasDoktor, bool $hasDedektif, bool $hasAvci): array
{
    $created = $rooms->createRoom('Host', null, 'red', '🧛');
    $rooms->joinRoom($created['code'], 'P2', null, 'sky', '🐺');
    $rooms->joinRoom($created['code'], 'P3', null, 'purple', '🕵️');
    $rooms->joinRoom($created['code'], 'P4', null, 'green', '👑');

    $villagerCount = 3 - (($hasDoktor ? 1 : 0) + ($hasDedektif ? 1 : 0) + ($hasAvci ? 1 : 0));
    expect($villagerCount)->toBeGreaterThan(0);

    $rooms->updateConfig($created['code'], $created['hostPlayerId'], [
        'vampireCount' => 1,
        'villagerCount' => $villagerCount,
        'hasDoktor' => $hasDoktor,
        'hasDedektif' => $hasDedektif,
        'hasAvci' => $hasAvci,
    ]);

    $rooms->startGame($created['code'], $created['hostPlayerId']);

    return ['code' => $created['code'], 'hostPlayerId' => $created['hostPlayerId']];
}

/**
 * @return array{code: string, hostPlayerId: string}
 */
function createFivePlayerRoom(
    VampireRoomService $rooms,
    int $vampireCount,
    int $villagerCount,
    bool $hasDoktor,
    bool $hasDedektif,
    bool $hasAvci,
): array {
    $created = $rooms->createRoom('Host', null, 'red', '🧛');
    for ($i = 2; $i <= 5; $i++) {
        $rooms->joinRoom($created['code'], 'P'.$i, null, 'sky', '🐺');
    }

    $rooms->updateConfig($created['code'], $created['hostPlayerId'], [
        'vampireCount' => $vampireCount,
        'villagerCount' => $villagerCount,
        'hasDoktor' => $hasDoktor,
        'hasDedektif' => $hasDedektif,
        'hasAvci' => $hasAvci,
    ]);

    $rooms->startGame($created['code'], $created['hostPlayerId']);

    return ['code' => $created['code'], 'hostPlayerId' => $created['hostPlayerId']];
}

/**
 * @return array{code: string, hostPlayerId: string}
 */
function createSixPlayerTwoVampireRoom(VampireRoomService $rooms): array
{
    $created = $rooms->createRoom('Host', null, 'red', '🧛');
    for ($i = 2; $i <= 6; $i++) {
        $rooms->joinRoom($created['code'], 'P'.$i, null, 'sky', '🐺');
    }

    $rooms->updateConfig($created['code'], $created['hostPlayerId'], [
        'vampireCount' => 2,
        'villagerCount' => 4,
        'hasDoktor' => false,
        'hasDedektif' => false,
        'hasAvci' => false,
    ]);

    $rooms->startGame($created['code'], $created['hostPlayerId']);

    return ['code' => $created['code'], 'hostPlayerId' => $created['hostPlayerId']];
}

function advanceDayWithoutElimination(VampireRoomService $rooms, string $code, string $hostId): void
{
    $rooms->startDay($code, $hostId);
    $rooms->startDayVoting($code, $hostId);
    $rooms->revealDayVotes($code, $hostId);
    $rooms->confirmDayElimination($code, $hostId, false);
}

/**
 * @param  array<string, array<string, mixed>>  $players
 */
function firstPlayerIdWhere(array $players, callable $predicate): string
{
    foreach ($players as $id => $player) {
        if ($predicate($player, $id)) {
            return (string) $id;
        }
    }

    throw new RuntimeException('No player matched the predicate.');
}

it('tells the doctor why last night\'s patient cannot be picked again', function () {
    Cache::flush();
    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerVampireRoomWithDoctor($rooms);
    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    $doctorId = firstPlayerIdWhere($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Doctor->value);
    [$v1] = array_keys(array_filter($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Villager->value));

    $rooms->castNightVote($ctx['code'], $vampireId, $v1);
    $rooms->doctorProtect($ctx['code'], $doctorId, $v1);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);
    advanceDayWithoutElimination($rooms, $ctx['code'], $ctx['hostPlayerId']);

    session()->put('vampire.player.'.$ctx['code'], $doctorId);
    $component = Livewire::test('games.vampire.room', ['roomCode' => $ctx['code']])->call('doctorProtect', $v1);

    expect($component->get('error'))->toBe(__('vampire.errors.doctor_same_target'))
        ->and($rooms->getRoom($ctx['code'])['doctorProtects'])->toBeNull();
});

it('announces at dawn who died, from the same data every player receives', function () {
    Cache::flush();
    $rooms = app(VampireRoomService::class);
    $ctx = createFourPlayerVampireRoomWithDoctor($rooms);
    $players = (array) $rooms->getRoom($ctx['code'])['players'];
    $vampireId = firstPlayerIdWhere($players, fn ($p) => ($p['alignment'] ?? null) === 'vampire');
    [$v1] = array_keys(array_filter($players, fn ($p) => ($p['role'] ?? null) === VampireRole::Villager->value));

    session()->put('vampire.player.'.$ctx['code'], $ctx['hostPlayerId']);
    $component = Livewire::test('games.vampire.room', ['roomCode' => $ctx['code']])->call('refreshRoom');
    $rooms->castNightVote($ctx['code'], $vampireId, $v1);
    $rooms->resolveDawn($ctx['code'], $ctx['hostPlayerId']);
    $component->call('refreshRoom');

    expect($component->get('notification.message'))->toBe(__('vampire.log.death_night', ['name' => $players[$v1]['name']]));
});
