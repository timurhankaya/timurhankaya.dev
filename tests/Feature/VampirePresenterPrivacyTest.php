<?php

use App\Support\Games\VampireRoomPresenter;

function privacyRoom(): array
{
    $player = fn (string $id, string $role, string $alignment): array => [
        'id' => $id, 'name' => ucfirst($id), 'color' => 'sky', 'emoji' => '🎭', 'role' => $role, 'alignment' => $alignment, 'alive' => true,
    ];

    return [
        'status' => 'night', 'hostPlayerId' => 'host', 'nightPhase' => 'vampires', 'nightNumber' => 2, 'config' => [], 'history' => [], 'nightVotes' => [],
        'players' => [
            'host' => $player('host', 'villager', 'village'), 'vamp' => $player('vamp', 'vampire', 'vampire'),
            'doc' => $player('doc', 'doctor', 'village'), 'det' => $player('det', 'detective', 'village'),
        ],
        'lastProtectedId' => 'host', 'detectiveInvestigationResults' => ['vamp' => 'vampire'], 'interrogatedIds' => ['vamp'],
    ];
}

it('sends the doctor\'s last protection only to the doctor', function (?string $viewer, ?string $expected) {
    $state = app(VampireRoomPresenter::class)->present(privacyRoom(), $viewer, null);
    expect($state['lastProtectedId'])->toBe($expected);
})->with([['doc', 'host'], ['host', null], ['vamp', null], ['det', null], [null, null]]);

it('sends detective findings and the interrogated marks only to the detective', function (?string $viewer, bool $isDetective) {
    $state = app(VampireRoomPresenter::class)->present(privacyRoom(), $viewer, null);
    $marked = array_values(array_filter($state['players'], fn (array $player): bool => $player['interrogated']));
    expect($state['detectiveInvestigationResults'])->toBe($isDetective ? ['vamp' => 'vampire'] : [])
        ->and(count($marked))->toBe($isDetective ? 1 : 0);
})->with([['det', true], ['doc', false], ['host', false], ['vamp', false], [null, false]]);

it('keeps those secrets out of the serialized payload a browser receives', function (?string $viewer) {
    $json = json_encode(app(VampireRoomPresenter::class)->present(privacyRoom(), $viewer, null));
    expect($json)->not->toContain('"lastProtectedId":"host"')->not->toContain('"vamp":"vampire"')->not->toContain('"interrogated":true');
})->with(['host', 'vamp', null]);

it('hides who was saved and who saved them from every browser when the doctor saves someone', function (?string $viewer) {
    $room = privacyRoom();
    $room['nightResult'] = ['saved' => true, 'killedId' => 'host', 'killedName' => 'Host', 'savedById' => 'host', 'savedByName' => 'Doc'];
    $result = app(VampireRoomPresenter::class)->present($room, $viewer, null)['nightResult'];
    expect($result['saved'])->toBeTrue()
        ->and([$result['killedId'], $result['killedName'], $result['savedById'], $result['savedByName']])->toBe([null, null, null, null]);
})->with(['doc', 'host', 'vamp', null]);

it('reveals every role to everyone only once the game is over', function (string $status, bool $revealed) {
    $room = privacyRoom();
    $room['status'] = $status;
    $state = app(VampireRoomPresenter::class)->present($room, 'host', null);
    $roles = array_column($state['players'], 'revealedRole', 'id');

    expect($roles)->toBe($revealed ? ['host' => 'villager', 'vamp' => 'vampire', 'doc' => 'doctor', 'det' => 'detective'] : ['host' => null, 'vamp' => null, 'doc' => null, 'det' => null]);
})->with([['game_over', true], ['night', false], ['day', false], ['day_voting', false]]);
