<?php

use App\Support\Games\ImposterRoomPresenter;

function imposterRoom(int $players): array
{
    $roster = [];
    foreach (range(1, $players) as $n) {
        $roster["p$n"] = ['id' => "p$n", 'name' => "Player$n", 'color' => 'sky', 'emoji' => '🎭', 'role' => $n === 1 ? 'imposter' : 'crew'];
    }

    return ['status' => 'started', 'hostPlayerId' => 'p2', 'word' => 'secret', 'starterId' => 'p2', 'players' => $roster, 'language' => 'en'];
}

it('does not send the starter name to the browser in a three-player game, where it would reveal the imposter', function () {
    foreach (['p1', 'p2', 'p3'] as $viewer) {
        $state = app(ImposterRoomPresenter::class)->present(imposterRoom(3), $viewer, null, 'en');
        expect($state['starterName'])->toBeNull();
    }
});

it('still names the starter in larger games', function () {
    $state = app(ImposterRoomPresenter::class)->present(imposterRoom(4), 'p3', null, 'en');
    expect($state['starterName'])->toBe('Player2');
});
