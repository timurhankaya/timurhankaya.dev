<?php

use App\Enums\VampireRoomStatus;
use App\Jobs\ResolveVampireNight;
use App\Services\Vampire\VampireRoomService;
use App\Support\Games\VampireRoomPresenter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
    Queue::fake();
    $this->travelTo(now()->startOfSecond());
});

function timedVampireRoom(bool $specialRoles = false): array
{
    $rooms = app(VampireRoomService::class);
    $created = $rooms->createRoom('Host');
    $code = $created['code'];
    $host = $created['hostPlayerId'];
    for ($i = 1; $i < ($specialRoles ? 7 : 4); $i++) {
        $rooms->joinRoom($code, 'Player '.$i);
    }
    $rooms->updateConfig($code, $host, ['vampireCount' => 1, 'villagerCount' => 3,
        'hasDoktor' => $specialRoles, 'hasDedektif' => $specialRoles, 'hasAvci' => $specialRoles]);
    $rooms->startGame($code, $host);

    return [$rooms, $code, $host];
}

it('sets a shared 45 second deadline and queues a delayed resolution for the current night', function () {
    [$rooms, $code] = timedVampireRoom();
    $room = $rooms->getRoom($code);
    expect($room['nightEndsAt'])->toBe(now()->timestamp + 45);
    Queue::assertPushed(ResolveVampireNight::class, fn ($job) => $job->roomCode === $code
        && $job->roundId === $room['roundId'] && $job->nightNumber === 1
        && $job->deadline === $room['nightEndsAt'] && $job->queue === 'vampire-timers'
        && $job->delay->timestamp === $room['nightEndsAt']);
});

it('waits for the deadline then resolves exactly once without a host action', function () {
    [$rooms, $code] = timedVampireRoom();
    $before = $rooms->getRoom($code);
    $job = new ResolveVampireNight($code, $before['roundId'], 1, $before['nightEndsAt']);
    $this->travel(44)->seconds();
    $job->handle($rooms);
    expect($rooms->getRoom($code))->toBe($before);
    $this->travel(1)->seconds();
    $job->handle($rooms);
    $after = $rooms->getRoom($code);
    expect($after['status'])->toBe('dawn')->and($after['history'])->toHaveCount(1);
    $job->handle($rooms);
    $rooms->resolveExpiredNight($code);
    expect($rooms->getRoom($code))->toBe($after);
});

it('resolves through a joined nonhost refresh if a worker is delayed', function () {
    [$rooms, $code, $host] = timedVampireRoom();
    $guest = collect(array_keys($rooms->getRoom($code)['players']))->first(fn ($id) => $id !== $host);
    session()->put('vampire.player.'.$code, $guest);
    $this->travel(45)->seconds();
    Livewire::test('games.vampire.room', ['roomCode' => $code])
        ->assertSet('status', VampireRoomStatus::Dawn)
        ->assertSet('isHost', false)->assertSet('nightEndsAt', null);
    expect($rooms->getRoom($code)['history'])->toHaveCount(1);
});

it('ignores jobs for an older night or round and schedules each new night', function () {
    [$rooms, $code, $host] = timedVampireRoom();
    $first = $rooms->getRoom($code);
    $job = new ResolveVampireNight($code, $first['roundId'], 1, $first['nightEndsAt']);
    $this->travel(45)->seconds();
    $job->handle($rooms);
    $rooms->startDay($code, $host);
    $rooms->startDayVoting($code, $host);
    $rooms->revealDayVotes($code, $host);
    $rooms->confirmDayElimination($code, $host, false);
    $second = $rooms->getRoom($code);
    expect($second['nightNumber'])->toBe(2)->and($second['nightEndsAt'])->toBe(now()->timestamp + 45);
    Queue::assertPushed(ResolveVampireNight::class, fn ($j) => $j->nightNumber === 2);
    $this->travel(45)->seconds();
    $job->handle($rooms);
    expect($rooms->getRoom($code))->toBe($second);
    $rooms->declareGameOver($code, $host, 'host_declared');
    $rooms->startNewRound($code, $host);
    $rooms->startGame($code, $host);
    $nextRound = $rooms->getRoom($code);
    $this->travel(45)->seconds();
    $job->handle($rooms);
    expect($rooms->getRoom($code))->toBe($nextRound);
});

it('rejects late role decisions even if the expiry job has not run yet', function (string $method, string $role) {
    [$rooms, $code] = timedVampireRoom(true);
    $room = $rooms->getRoom($code);
    $actor = collect($room['players'])->firstWhere('role', $role)['id'];
    $target = collect($room['players'])->firstWhere('role', 'villager')['id'];
    $this->travel(45)->seconds();
    $rooms->{$method}($code, $actor, $target);
    $after = $rooms->getRoom($code);
    expect($after['status'])->toBe('dawn')
        ->and($after['players'][$target]['alive'])->toBeTrue()
        ->and($after['detectiveInvestigationResults'])->toBe([]);
})->with(['vampire' => ['castNightVote', 'vampire'], 'doctor' => ['doctorProtect', 'doctor'], 'detective' => ['detectiveQuery', 'detective']]);

it('processes accepted votes and protection at the deadline while preserving privacy', function () {
    [$rooms, $code] = timedVampireRoom(true);
    $room = $rooms->getRoom($code);
    $vampire = collect($room['players'])->firstWhere('role', 'vampire')['id'];
    $doctor = collect($room['players'])->firstWhere('role', 'doctor')['id'];
    $target = collect($room['players'])->firstWhere('role', 'villager')['id'];
    $rooms->castNightVote($code, $vampire, $target);
    $rooms->doctorProtect($code, $doctor, $target);
    $this->travel(45)->seconds();
    $rooms->resolveExpiredNight($code);
    $after = $rooms->getRoom($code);
    expect($after['nightResult']['saved'])->toBeTrue()->and($after['players'][$target]['alive'])->toBeTrue();
    $view = app(VampireRoomPresenter::class)->present($after, $target, null);
    expect($view['nightResult']['killedId'])->toBeNull()->and($view['nightResult']['savedByName'])->toBeNull();
});

it('gives every player the same deadline without exposing hidden roles or decisions', function () {
    [$rooms, $code] = timedVampireRoom(true);
    $room = $rooms->getRoom($code);
    $presenter = app(VampireRoomPresenter::class);
    foreach ($room['players'] as $id => $player) {
        $view = $presenter->present($room, $id, null);
        expect($view['nightEndsAt'])->toBe(now()->timestamp + 45)->and($view['serverNow'])->toBe(now()->timestamp);
        expect(array_column($view['players'], 'revealedRole'))->toBe(array_fill(0, 7, null));
    }
});

it('keeps the original deadline across page refreshes', function () {
    [$rooms, $code, $host] = timedVampireRoom();
    session()->put('vampire.player.'.$code, $host);
    $deadline = $rooms->getRoom($code)['nightEndsAt'];
    $this->travel(20)->seconds();
    Livewire::test('games.vampire.room', ['roomCode' => $code])
        ->assertSet('nightEndsAt', $deadline)->call('refreshRoom')->assertSet('nightEndsAt', $deadline);
    expect($rooms->getRoom($code)['nightEndsAt'])->toBe($deadline);
});

it('shows the same optional preparations to every living role without adding a game action', function () {
    [$rooms, $code] = timedVampireRoom(true);
    $room = $rooms->getRoom($code);
    foreach ($room['players'] as $id => $player) {
        session()->put('vampire.player.'.$code, $id);
        Livewire::test('games.vampire.room', ['roomCode' => $code])
            ->assertSee(__('games.night_preparations.title'))
            ->assertSee(__('games.night_preparations.door'))
            ->assertSee(__('games.night_clock.label'));
    }
    expect($rooms->getRoom($code))->toBe($room);
});

it('uses the usual hunter last shot when the timed night kills a hunter', function () {
    [$rooms, $code] = timedVampireRoom(true);
    $room = $rooms->getRoom($code);
    $vampire = collect($room['players'])->firstWhere('role', 'vampire')['id'];
    $hunter = collect($room['players'])->firstWhere('role', 'hunter')['id'];
    $rooms->castNightVote($code, $vampire, $hunter);
    $this->travel(45)->seconds();
    $rooms->resolveExpiredNight($code);
    expect($rooms->getRoom($code)['status'])->toBe('hunter_last_shot')
        ->and($rooms->getRoom($code)['hunterLastShotSource'])->toBe('night');
});

it('ignores a delayed job after a room has disappeared', function () {
    [$rooms, $code] = timedVampireRoom();
    $room = $rooms->getRoom($code);
    $job = new ResolveVampireNight($code, $room['roundId'], 1, $room['nightEndsAt']);
    Cache::forget('vampire:room:'.$code);
    $this->travel(45)->seconds();
    $job->handle($rooms);
    expect($rooms->getRoom($code))->toBeNull();
});

it('keeps the original deadline when advancing a legacy night phase', function () {
    [$rooms, $code, $host] = timedVampireRoom();
    $legacy = $rooms->getRoom($code);
    unset($legacy['nightEndsAt']);
    Cache::put('vampire:room:'.$code, $legacy, 7200);
    $this->travel(20)->seconds();
    $rooms->advanceNightPhase($code, $host);
    $this->travel(25)->seconds();
    $rooms->resolveExpiredNight($code);
    expect($rooms->getRoom($code)['status'])->toBe('dawn');
});

it('rejects a job with the wrong deadline even when the round and night match', function () {
    [$rooms, $code] = timedVampireRoom();
    $room = $rooms->getRoom($code);
    $this->travel(45)->seconds();
    (new ResolveVampireNight($code, $room['roundId'], 1, $room['nightEndsAt'] - 1))->handle($rooms);
    expect($rooms->getRoom($code))->toBe($room);
});

it('schedules the next timed night after a hunter killed by the day vote takes a shot', function () {
    [$rooms, $code, $host] = timedVampireRoom(true);
    $room = $rooms->getRoom($code);
    $hunter = collect($room['players'])->firstWhere('role', 'hunter')['id'];
    $vampire = collect($room['players'])->firstWhere('role', 'vampire')['id'];
    $target = collect($room['players'])->firstWhere('role', 'villager')['id'];
    $rooms->resolveDawn($code, $host);
    $rooms->startDay($code, $host);
    $rooms->startDayVoting($code, $host);
    foreach (array_keys($room['players']) as $id) {
        $rooms->castDayVote($code, $id, $id === $hunter ? $vampire : $hunter);
    }
    $rooms->revealDayVotes($code, $host);
    $rooms->confirmDayElimination($code, $host, true);
    expect($rooms->getRoom($code)['status'])->toBe('hunter_last_shot');
    $this->travel(12)->seconds();
    $rooms->hunterShoot($code, $hunter, $target);
    $next = $rooms->getRoom($code);
    expect($next['status'])->toBe('night')->and($next['nightNumber'])->toBe(2)
        ->and($next['nightEndsAt'])->toBe(now()->timestamp + 45);
    Queue::assertPushed(ResolveVampireNight::class, fn ($job) => $job->nightNumber === 2 && $job->deadline === $next['nightEndsAt']);
});
