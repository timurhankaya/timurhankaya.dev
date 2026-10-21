<?php

use App\Services\Games\Simulation\BotMoves;
use App\Services\Games\Simulation\LocalGameSimulation;
use App\Services\Imposter\ImposterRoomService;
use App\Services\Vampire\VampireRoomService;
use App\Support\Games\VampireRoomPresenter;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(fn () => Cache::flush());

function simulationRoom(string $game, ?string $password = null): array
{
    $rooms = app($game === 'imposter' ? ImposterRoomService::class : VampireRoomService::class);
    $created = $rooms->createRoom('Human host', $password);
    session()->put($game.'.player.'.$created['code'], $created['hostPlayerId']);

    return [$rooms, $created['code'], $created['hostPlayerId'], app(LocalGameSimulation::class)];
}

it('joins password rooms normally, caps bots at twelve and only removes its own bots', function (string $game) {
    [$rooms, $code, $host, $sim] = simulationRoom($game, 'secret');
    $human = $rooms->joinRoom($code, 'Human guest', 'secret')['playerId'];
    $sim->addBots($game, $code, $host, 99);
    $sim->addBots($game, $code, $host, 2);
    expect($rooms->getRoom($code)['players'])->toHaveCount(14);
    expect($sim->status($game, $code, $host))->toMatchArray(['count' => 12, 'automatic' => false]);
    $sim->removeBots($game, $code, $host);
    expect(array_keys($rooms->getRoom($code)['players']))->toBe([$host, $human]);
})->with(['imposter', 'vampire']);

/** A complete room from normal join/config/start operations, with controlled role allocation. */
function simulationVampireRoles(): array
{
    [$rooms, $code, $host, $sim] = simulationRoom('vampire');
    $sim->addBots('vampire', $code, $host, 6);
    $rooms->updateConfig($code, $host, ['vampireCount' => 1, 'villagerCount' => 3, 'hasDoktor' => true, 'hasDedektif' => true, 'hasAvci' => true]);
    $room = $rooms->startGame($code, $host);
    $ids = array_keys($room['players']);
    foreach (['villager', 'vampire', 'doctor', 'detective', 'hunter', 'villager', 'villager'] as $index => $role) {
        $room['players'][$ids[$index]]['role'] = $role;
        $room['players'][$ids[$index]]['alignment'] = $role === 'vampire' ? 'vampire' : 'villager';
    }
    Cache::put('vampire:room:'.$code, $room, 7200);

    return [$rooms, $code, $host, $sim, $ids];
}

it('plays night roles once, respects previous protection and investigation, and stops at resolved night', function () {
    [$rooms, $code, $host, $sim, $ids] = simulationVampireRoles();
    $room = $rooms->getRoom($code);
    $room['lastProtectedId'] = $host;
    $room['interrogatedIds'] = [$host, $ids[1], $ids[2], $ids[4], $ids[5]];
    Cache::put('vampire:room:'.$code, $room, 7200);
    $sim->play('vampire', $code, $host);
    $after = $rooms->getRoom($code);
    expect($after['nightVotes'])->toHaveCount(1)->toHaveKey($ids[1]);
    expect($after['nightVotes'][$ids[1]])->not->toBe($ids[1]);
    expect($after['doctorProtects'])->not->toBeNull()->not->toBe($host);
    expect($after['detectiveQuery'])->toBe($ids[6]);
    expect($after['nightPhase'])->toBe('vampires');
    $sim->play('vampire', $code, $host);
    expect($rooms->getRoom($code))->toBe($after);

    $rooms->advanceNightPhase($code, $host);
    $resolved = $rooms->getRoom($code);
    $sim->play('vampire', $code, $host);
    expect($rooms->getRoom($code))->toBe($resolved);
});

it('does not play dead bots, protects only living targets and reports exhausted detective targets', function () {
    [$rooms, $code, $host, $sim, $ids] = simulationVampireRoles();
    $room = $rooms->getRoom($code);
    $room['players'][$ids[1]]['alive'] = false;
    $room['interrogatedIds'] = array_values(array_diff($ids, [$ids[3]]));
    Cache::put('vampire:room:'.$code, $room, 7200);
    $sim->play('vampire', $code, $host);
    $after = $rooms->getRoom($code);
    expect($after['nightVotes'])->toBe([]);
    expect($after['detectiveQuery'])->toBeNull();
    expect($after['players'][$after['doctorProtects']]['alive'])->toBeTrue();
    expect($sim->status('vampire', $code, $host)['reason'])->toBe('no_targets');
});

it('plays living day voters only and never toggles votes off on a repeated poll', function () {
    [$rooms, $code, $host, $sim, $ids] = simulationVampireRoles();
    $room = $rooms->getRoom($code);
    $room['players'][$ids[5]]['alive'] = false;
    $room['status'] = 'day';
    Cache::put('vampire:room:'.$code, $room, 7200);
    $rooms->startDayVoting($code, $host);
    $sim->play('vampire', $code, $host);
    $after = $rooms->getRoom($code);
    expect($after['dayVotes'])->toHaveCount(5)->not->toHaveKey($host)->not->toHaveKey($ids[5]);
    foreach ($after['dayVotes'] as $bot => $target) {
        expect($target)->not->toBe($bot);
        expect($after['players'][$target]['alive'])->toBeTrue();
    }
    $sim->play('vampire', $code, $host);
    expect($rooms->getRoom($code))->toBe($after);
});

it('allows a dead hunter its final shot through the normal game service', function () {
    [$rooms, $code, $host, $sim, $ids] = simulationVampireRoles();
    $rooms->castNightVote($code, $ids[1], $ids[4]);
    $rooms->resolveDawn($code, $host);
    expect($rooms->getRoom($code)['status'])->toBe('hunter_last_shot');
    $sim->play('vampire', $code, $host);
    $after = $rooms->getRoom($code);
    expect(array_filter($after['players'], fn ($p) => ! $p['alive']))->toHaveCount(2);
    expect($after['status'])->not->toBe('hunter_last_shot');
    $sim->play('vampire', $code, $host);
    expect($rooms->getRoom($code))->toBe($after);
});

it('runs automatic moves from refresh only while enabled and leaves phase controls manual', function (string $game) {
    [$rooms, $code, $host, $sim] = simulationRoom($game);
    $sim->addBots($game, $code, $host, 3);
    if ($game === 'vampire') {
        $rooms->updateConfig($code, $host, ['vampireCount' => 1, 'villagerCount' => 3]);
    }
    $sim->setAutomatic($game, $code, $host, true);
    Livewire::test('games.'.$game.'.room', ['roomCode' => $code])->call('refreshRoom');
    expect($rooms->getRoom($code)['status'])->toBe('lobby');
    $rooms->startGame($code, $host);
    if ($game === 'imposter') {
        $rooms->startVoting($code, $host);
        $room = $rooms->getRoom($code);
        $queue = array_values(array_diff(array_keys($room['players']), [$host]));
        $room['currentVoterId'] = array_shift($queue);
        $room['voterQueue'] = [...$queue, $host];
        Cache::put('imposter:room:'.$code, $room, 7200);
    } else {
        // Choose a bot vampire so the automatic night move is observable regardless of random roles.
        $room = $rooms->getRoom($code);
        foreach ($room['players'] as $id => &$player) {
            $player['role'] = $id === array_keys($room['players'])[1] ? 'vampire' : 'villager';
            $player['alignment'] = $player['role'] === 'vampire' ? 'vampire' : 'villager';
        }
        unset($player);
        Cache::put('vampire:room:'.$code, $room, 7200);
    }
    $sim->setAutomatic($game, $code, $host, false);
    $before = $rooms->getRoom($code);
    Livewire::test('games.'.$game.'.room', ['roomCode' => $code])->call('refreshRoom');
    expect($rooms->getRoom($code))->toBe($before);
    $sim->setAutomatic($game, $code, $host, true);
    Livewire::test('games.'.$game.'.room', ['roomCode' => $code])->call('refreshRoom');
    $after = $rooms->getRoom($code);
    expect($after[$game === 'imposter' ? 'votes' : 'nightVotes'])->not->toBe([]);
    expect($after['status'])->toBe($game === 'imposter' ? 'voting' : 'night');
})->with(['imposter', 'vampire']);

it('exposes only safe simulation status and leaves role privacy to the normal presenters', function () {
    [$rooms, $code, $host, $sim, $ids] = simulationVampireRoles();
    $status = $sim->status('vampire', $code, $host);
    expect(array_keys($status))->toBe(['visible', 'count', 'automatic', 'reason', 'lobby', 'playerCount', 'roleCount']);
    foreach (array_slice($ids, 1) as $id) {
        expect(json_encode($status))->not->toContain($id);
    }
    $component = Livewire::test('games.vampire.room', ['roomCode' => $code]);
    foreach ($component->get('players') as $player) {
        expect($player['revealedRole'])->toBeNull()->and($player['isVampire'])->toBeFalse();
        expect($player)->not->toHaveKey('role');
    }
    expect($component->get('myRole'))->toBe('villager');
    expect($component->get('myDoctorTarget'))->toBeNull();
});

it('chooses the same target when only hidden roles or roster order change', function () {
    [$rooms, $code, $host, $sim, $ids] = simulationVampireRoles();
    $room = $rooms->getRoom($code);
    $policy = app(BotMoves::class);
    $presenter = app(VampireRoomPresenter::class);
    $before = $policy->next('vampire', $presenter->present($room, $ids[2], null), 'fixed-stage');
    [$room['players'][$ids[1]]['role'], $room['players'][$ids[6]]['role']] = [$room['players'][$ids[6]]['role'], $room['players'][$ids[1]]['role']];
    [$room['players'][$ids[1]]['alignment'], $room['players'][$ids[6]]['alignment']] = [$room['players'][$ids[6]]['alignment'], $room['players'][$ids[1]]['alignment']];
    $room['players'] = array_reverse($room['players'], true);
    expect($policy->next('vampire', $presenter->present($room, $ids[2], null), 'fixed-stage'))->toBe($before);
});

it('rejects a bot join if the host changed after the simulation read', function (string $game) {
    [$rooms, $code, $host] = simulationRoom($game);
    $guest = $rooms->joinRoom($code, 'Next host')['playerId'];
    $stale = $rooms->getRoom($code);
    $rooms->leaveRoom($code, $host);
    expect($rooms->joinRoom($code, 'Bot', expectedRoom: $stale))->toBeNull();
    expect(array_keys($rooms->getRoom($code)['players']))->toBe([$guest]);
})->with(['imposter', 'vampire']);

it('rejects a stale vampire move under the room lock', function (string $method, int $actorIndex) {
    [$rooms, $code, $host, $sim, $ids] = simulationVampireRoles();
    if ($method === 'castDayVote') {
        $room = $rooms->getRoom($code);
        $room['status'] = 'day';
        Cache::put('vampire:room:'.$code, $room, 7200);
        $rooms->startDayVoting($code, $host);
    } elseif ($method === 'hunterShoot') {
        $rooms->castNightVote($code, $ids[1], $ids[4]);
        $rooms->resolveDawn($code, $host);
    }
    $stale = $rooms->getRoom($code);
    $rooms->declareGameOver($code, $host, 'host_declared');
    $before = $rooms->getRoom($code);
    expect($rooms->{$method}($code, $ids[$actorIndex], $host, expectedRoom: $stale))->toBeNull();
    expect($rooms->getRoom($code))->toBe($before);
})->with(['vampire' => ['castNightVote', 1], 'doctor' => ['doctorProtect', 2], 'detective' => ['detectiveQuery', 3], 'hunter' => ['hunterShoot', 4], 'day voter' => ['castDayVote', 5]]);

it('rejects a stale imposter vote after a concurrent roster change', function () {
    [$rooms, $code, $host, $sim] = simulationRoom('imposter');
    $sim->addBots('imposter', $code, $host, 3);
    $rooms->startGame($code, $host);
    $stale = $rooms->startVoting($code, $host);
    $target = collect(array_keys($stale['players']))->first(fn ($id) => $id !== $stale['currentVoterId']);
    $rooms->leaveRoom($code, $target);
    $before = $rooms->getRoom($code);
    expect($rooms->castVote($code, $stale['currentVoterId'], $target, expectedRoom: $stale))->toBeNull();
    expect($rooms->getRoom($code))->toBe($before);
});

it('rejects every production and nonlocal action even when debug is enabled', function (string $environment, string $game) {
    [$rooms, $code, $host, $sim] = simulationRoom($game);
    $sim->addBots($game, $code, $host, 3);
    $before = $rooms->getRoom($code);
    app()->instance('env', $environment);
    config(['app.debug' => true]);
    try {
        $sim->addBots($game, $code, $host, 2);
        $sim->removeBots($game, $code, $host);
        $sim->setAutomatic($game, $code, $host, true);
        $sim->play($game, $code, $host);
        expect($sim->status($game, $code, $host)['visible'])->toBeFalse();
        expect($rooms->getRoom($code))->toBe($before);
    } finally {
        app()->instance('env', 'testing');
    }
})->with(['production', 'staging'])->with(['imposter', 'vampire']);

it('requires the real session host and isolates rooms and games', function (string $game) {
    [$rooms, $code, $host, $sim] = simulationRoom($game);
    $guest = $rooms->joinRoom($code, 'Guest')['playerId'];
    session()->put($game.'.player.'.$code, $guest);
    $before = $rooms->getRoom($code);
    foreach ([$guest, $host, null] as $actor) {
        $sim->addBots($game, $code, $actor, 4);
        $sim->removeBots($game, $code, $actor);
        $sim->setAutomatic($game, $code, $actor, true);
        $sim->play($game, $code, $actor);
    }
    expect($rooms->getRoom($code))->toBe($before);
    session()->put($game.'.player.'.$code, $host);
    $sim->addBots($game, $code, $host, 2);
    $other = $rooms->createRoom('Other host');
    $sim->removeBots($game, $other['code'], $host);
    expect($rooms->getRoom($other['code'])['players'])->toHaveCount(1);
    $otherGame = $game === 'imposter' ? 'vampire' : 'imposter';
    expect($sim->status($otherGame, $code, $host)['visible'])->toBeFalse();
})->with(['imposter', 'vampire']);

it('only adds and removes in the lobby and keeps vampire role counts explicit', function () {
    [$rooms, $code, $host, $sim] = simulationRoom('vampire');
    $config = $rooms->getRoom($code)['config'];
    $sim->addBots('vampire', $code, $host, 3);
    expect($rooms->getRoom($code)['config'])->toBe($config);
    expect($sim->status('vampire', $code, $host))->toMatchArray(['playerCount' => 4, 'roleCount' => 3]);
    expect($rooms->startGame($code, $host)['status'])->toBe('lobby');
    $rooms->updateConfig($code, $host, ['vampireCount' => 1, 'villagerCount' => 3]);
    $rooms->startGame($code, $host);
    $before = $rooms->getRoom($code)['players'];
    $sim->addBots('vampire', $code, $host, 1);
    $sim->removeBots('vampire', $code, $host);
    expect($rooms->getRoom($code)['players'])->toBe($before);
});

it('advances consecutive imposter bots once, waits for humans and never reveals results', function () {
    [$rooms, $code, $host, $sim] = simulationRoom('imposter');
    $sim->addBots('imposter', $code, $host, 4);
    $rooms->startGame($code, $host);
    $rooms->startVoting($code, $host);
    $sim->play('imposter', $code, $host);
    $room = $rooms->getRoom($code);
    expect($room['currentVoterId'])->toBe($host);
    expect($sim->status('imposter', $code, $host)['reason'])->toBe('human_turn');
    $before = $room['votes'];
    $sim->play('imposter', $code, $host);
    expect($rooms->getRoom($code)['votes'])->toBe($before);
    $target = collect(array_keys($room['players']))->first(fn ($id) => $id !== $host);
    $rooms->castVote($code, $host, $target);
    $sim->play('imposter', $code, $host);
    $room = $rooms->getRoom($code);
    expect($room['votes'])->toHaveCount(5)->and($room['status'])->toBe('voting');
    foreach ($room['votes'] as $voter => $target) {
        expect($target)->not->toBe($voter);
        expect($room['players'])->toHaveKey($target);
    }
    $sim->play('imposter', $code, $host);
    expect($rooms->getRoom($code))->toBe($room);
    $rooms->revealVotes($code, $host);
    $rooms->startNewRound($code, $host);
    $rooms->startGame($code, $host);
    $rooms->startVoting($code, $host);
    $sim->play('imposter', $code, $host);
    $room = $rooms->getRoom($code);
    expect($room['currentVoterId'])->toBe($host);
});

it('stops automation on host change and cleans up missing rooms', function (string $game) {
    [$rooms, $code, $host, $sim] = simulationRoom($game);
    $guest = $rooms->joinRoom($code, 'Next human host')['playerId'];
    $sim->addBots($game, $code, $host, 2);
    $sim->setAutomatic($game, $code, $host, true);
    $rooms->leaveRoom($code, $host);
    $sim->play($game, $code, $host, true);
    session()->put($game.'.player.'.$code, $guest);
    expect($sim->status($game, $code, $guest))->toMatchArray(['automatic' => false, 'count' => 2, 'reason' => 'host_changed']);
    Cache::forget($game.':room:'.$code);
    expect($sim->status($game, $code, $guest)['visible'])->toBeFalse();
    expect(Cache::has('simulation:'.$game.':'.$code))->toBeFalse();
})->with(['imposter', 'vampire']);

it('gates Livewire actions by session and hides the panel from production and guests', function (string $game) {
    [$rooms, $code, $host] = simulationRoom($game);
    $guest = $rooms->joinRoom($code, 'Guest')['playerId'];
    session()->put($game.'.player.'.$code, $guest);
    Livewire::test('games.'.$game.'.room', ['roomCode' => $code])
        ->set('isHost', true)->set('myPlayerId', $host)->set('simulationBotCount', 3)
        ->call('addSimulationBots')->call('removeSimulationBots')->call('playSimulationBots')->call('toggleSimulationAutomatic')
        ->assertDontSee(__('games.simulation.title'));
    expect($rooms->getRoom($code)['players'])->toHaveCount(2);
    session()->put($game.'.player.'.$code, $host);
    Livewire::test('games.'.$game.'.room', ['roomCode' => $code])
        ->assertSee(__('games.simulation.title'))->set('simulationBotCount', 2)->call('addSimulationBots');
    expect($rooms->getRoom($code)['players'])->toHaveCount(4);
    app()->instance('env', 'production');
    try {
        Livewire::test('games.'.$game.'.room', ['roomCode' => $code])
            ->call('addSimulationBots')->call('removeSimulationBots')->call('toggleSimulationAutomatic')->call('playSimulationBots')
            ->assertDontSee(__('games.simulation.title'));
        expect($rooms->getRoom($code)['players'])->toHaveCount(4);
    } finally {
        app()->instance('env', 'testing');
    }
})->with(['imposter', 'vampire']);
