<?php

namespace App\Services\Games\Simulation;

use App\Enums\ImposterRoomStatus;
use App\Enums\VampireNightPhase;
use App\Enums\VampireRole;
use App\Enums\VampireRoomStatus;

/** Chooses only from the information presented to this bot as a player. */
final class BotMoves
{
    /** @return array{method: string, target: ?string}|null */
    public function next(string $game, array $view, string $seed): ?array
    {
        $me = $view['myPlayerId'];
        if ($me === null) {
            return null;
        }

        if ($game === 'imposter') {
            if ($view['status'] !== ImposterRoomStatus::Voting || $view['currentVoterId'] !== $me || $view['myVote'] !== null) {
                return null;
            }
            $method = 'castVote';
            $candidates = array_filter($view['players'], fn (array $p) => $p['id'] !== $me);
        } else {
            $candidates = array_filter($view['players'], fn (array $p) => $p['alive']);
            $method = match (true) {
                $view['status'] === VampireRoomStatus::HunterLastShot && ! $view['myAlive'] && $view['myRole'] === VampireRole::Hunter->value => 'hunterShoot',
                $view['status'] === VampireRoomStatus::DayVoting && $view['myAlive'] && $view['myDayVote'] === null => 'castDayVote',
                $view['status'] === VampireRoomStatus::Night && $view['nightPhase'] === VampireNightPhase::Vampires->value && $view['myAlive'] => match ($view['myRole']) {
                    VampireRole::Vampire->value => $view['myNightVote'] === null ? 'castNightVote' : null,
                    VampireRole::Doctor->value => $view['myDoctorTarget'] === null ? 'doctorProtect' : null,
                    VampireRole::Detective->value => $view['myDetectiveTarget'] === null ? 'detectiveQuery' : null,
                    default => null,
                },
                default => null,
            };
            if ($method === null) {
                return null;
            }

            $candidates = array_filter($candidates, fn (array $p) => match ($method) {
                'doctorProtect' => $p['id'] !== $view['lastProtectedId'],
                'detectiveQuery' => $p['id'] !== $me && ! $p['interrogated'],
                'castNightVote' => $p['id'] !== $me && ! $p['isVampire'],
                default => $p['id'] !== $me,
            });
        }

        $ids = array_column($candidates, 'id');
        // Sorting hashes avoids depending on roster order or PHP's random state.
        usort($ids, fn (string $a, string $b) => strcmp(hash('sha256', $seed.$me.$a), hash('sha256', $seed.$me.$b)));

        return ['method' => $method, 'target' => $ids[0] ?? null];
    }
}
