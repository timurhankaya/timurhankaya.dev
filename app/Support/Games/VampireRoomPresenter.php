<?php

namespace App\Support\Games;

use App\Enums\VampireRole;
use App\Enums\VampireRoomStatus;
use App\Services\Vampire\VampireNightClock;

final class VampireRoomPresenter
{
    /**
     * @param  array<string, mixed>|null  $room
     * @return array<string, mixed>
     */
    public function present(?array $room, ?string $sessionPlayerId, ?float $previousBonggAt): array
    {
        if ($room === null) {
            return $this->missingState();
        }

        $status = VampireRoomStatus::tryFrom((string) ($room['status'] ?? '')) ?? VampireRoomStatus::Lobby;
        $roomPlayers = (array) ($room['players'] ?? []);
        $hostPlayerId = (string) ($room['hostPlayerId'] ?? '');
        $myPlayerId = is_string($sessionPlayerId) && $sessionPlayerId !== '' && isset($roomPlayers[$sessionPlayerId]) ? $sessionPlayerId : null;
        $isJoined = $myPlayerId !== null;
        $myPlayer = $myPlayerId !== null ? (array) ($roomPlayers[$myPlayerId] ?? []) : [];
        $myRole = is_string($myPlayer['role'] ?? null) ? (string) $myPlayer['role'] : null;
        $myAlignment = is_string($myPlayer['alignment'] ?? null) ? (string) $myPlayer['alignment'] : null;

        // What the detective has learned and what the doctor protected last are secrets of those roles:
        // everything in this payload is serialized to the browser, so other roles must never receive it.
        $myRoleEnum = VampireRole::tryFrom($myRole ?? '');
        $isDetective = $myRoleEnum === VampireRole::Detective;
        $isDoctor = $myRoleEnum === VampireRole::Doctor;
        $interrogatedIds = array_values(array_map('strval', (array) ($room['interrogatedIds'] ?? [])));
        // Roles become public only when the game is over (the final roles panel).
        $gameOver = ($room['status'] ?? null) === VampireRoomStatus::GameOver->value;
        $players = [];
        foreach ($roomPlayers as $player) {
            $players[] = [
                'id' => (string) ($player['id'] ?? ''),
                'name' => (string) ($player['name'] ?? ''),
                'color' => (string) ($player['color'] ?? 'sky'),
                'emoji' => (string) ($player['emoji'] ?? '🎭'),
                'isHost' => (string) ($player['id'] ?? '') === $hostPlayerId,
                'isMe' => $myPlayerId !== null && (string) ($player['id'] ?? '') === $myPlayerId,
                'alive' => (bool) ($player['alive'] ?? true),
                'isVampire' => $myPlayerId !== null
                    && VampireRole::tryFrom((string) ($roomPlayers[$myPlayerId]['role'] ?? ''))?->isVampireTeam() === true
                    && VampireRole::tryFrom((string) ($player['role'] ?? ''))?->isVampireTeam() === true,
                'interrogated' => $isDetective && in_array((string) ($player['id'] ?? ''), $interrogatedIds, true),
                'revealedRole' => $gameOver && is_string($player['role'] ?? null) ? (string) $player['role'] : null,
            ];
        }

        $nightVotes = (array) ($room['nightVotes'] ?? []);
        $nightVoteCounts = [];
        foreach ($nightVotes as $targetId) {
            if (! is_string($targetId) || $targetId === '') {
                continue;
            }

            $nightVoteCounts[$targetId] = ($nightVoteCounts[$targetId] ?? 0) + 1;
        }

        $dayVotes = (array) ($room['dayVotes'] ?? []);
        $dayVoteCounts = [];
        foreach ($dayVotes as $targetId) {
            if (! is_string($targetId) || $targetId === '') {
                continue;
            }

            $dayVoteCounts[$targetId] = ($dayVoteCounts[$targetId] ?? 0) + 1;
        }

        $dayVoteMap = [];
        if (in_array($status, [VampireRoomStatus::DayVoting, VampireRoomStatus::DayResults], true)) {
            foreach ($dayVotes as $voterId => $targetId) {
                if (! is_string($voterId) || ! is_string($targetId) || $targetId === '') {
                    continue;
                }

                $voterName = (string) ($roomPlayers[$voterId]['name'] ?? '');
                if ($voterName !== '') {
                    $dayVoteMap[$targetId][] = $voterName;
                }
            }
        }

        $incomingBongg = (float) ($room['lastBonggAt'] ?? 0);
        $detectiveInvestigationResults = [];
        foreach ((array) ($room['detectiveInvestigationResults'] ?? []) as $playerId => $result) {
            $detectiveInvestigationResults[(string) $playerId] = (string) $result;
        }

        $nightResult = is_array($room['nightResult'] ?? null) ? (array) $room['nightResult'] : null;
        if (is_array($nightResult) && (bool) ($nightResult['saved'] ?? false)) {
            // Keep doctor protection target private on client payload.
            $nightResult['killedId'] = null;
            $nightResult['killedName'] = null;
            $nightResult['savedById'] = null;
            $nightResult['savedByName'] = null;
        }

        return [
            'roomMissing' => false,
            'status' => $status,
            'nightPhase' => is_string($room['nightPhase'] ?? null) ? (string) $room['nightPhase'] : null,
            'nightPhaseStartedAt' => is_string($room['nightPhaseStartedAt'] ?? null) ? (string) $room['nightPhaseStartedAt'] : null,
            'nightEndsAt' => $status === VampireRoomStatus::Night ? VampireNightClock::deadline($room) : null,
            'serverNow' => now()->timestamp,
            'nightNumber' => (int) ($room['nightNumber'] ?? 0),
            'isHost' => $myPlayerId !== null && $myPlayerId === $hostPlayerId,
            'isJoined' => $isJoined,
            'myPlayerId' => $myPlayerId,
            'myRole' => $myRole,
            'myAlignment' => $myAlignment,
            'myAlive' => $isJoined ? (bool) ($myPlayer['alive'] ?? true) : true,
            'players' => $players,
            'myNightVote' => $myAlignment === 'vampire' && $myPlayerId !== null ? ((string) ($nightVotes[$myPlayerId] ?? '') ?: null) : null,
            'myDoctorTarget' => VampireRole::tryFrom($myRole ?? '') === VampireRole::Doctor && is_string($room['doctorProtects'] ?? null)
                ? (string) $room['doctorProtects']
                : null,
            'myDetectiveTarget' => VampireRole::tryFrom($myRole ?? '') === VampireRole::Detective && is_string($room['detectiveQuery'] ?? null)
                ? (string) $room['detectiveQuery']
                : null,
            'detectiveResult' => VampireRole::tryFrom($myRole ?? '') === VampireRole::Detective && is_string($room['detectiveResult'] ?? null)
                ? (string) $room['detectiveResult']
                : null,
            'detectiveInvestigationResults' => $isDetective ? $detectiveInvestigationResults : [],
            'lastProtectedId' => $isDoctor && is_string($room['lastProtectedId'] ?? null) ? (string) $room['lastProtectedId'] : null,
            'nightVoteCounts' => $myAlignment === 'vampire' ? $nightVoteCounts : [],
            'nightResult' => $nightResult,
            'dayVoteCounts' => $dayVoteCounts,
            'dayVoteMap' => $dayVoteMap,
            'myDayVote' => $myPlayerId !== null && isset($dayVotes[$myPlayerId]) ? (string) $dayVotes[$myPlayerId] : null,
            'dayResult' => is_array($room['dayResult'] ?? null) ? (array) $room['dayResult'] : null,
            'configVampireCount' => (int) (($room['config']['vampireCount'] ?? 1)),
            'configVillagerCount' => (int) (($room['config']['villagerCount'] ?? 2)),
            'configHasDoktor' => (bool) (($room['config']['hasDoktor'] ?? false)),
            'configHasDedektif' => (bool) (($room['config']['hasDedektif'] ?? false)),
            'configHasAvci' => (bool) (($room['config']['hasAvci'] ?? false)),
            'winner' => is_string($room['winner'] ?? null) ? (string) $room['winner'] : null,
            'roomPassword' => $myPlayerId !== null && $myPlayerId === $hostPlayerId && is_string($room['password'] ?? null) && $room['password'] !== ''
                ? (string) $room['password']
                : null,
            'isPasswordProtected' => is_string($room['password'] ?? null) && $room['password'] !== '',
            'history' => is_array($room['history'] ?? null) ? (array) $room['history'] : [],
            'shouldPlayBongg' => $incomingBongg > 0 && $previousBonggAt !== null && $previousBonggAt !== $incomingBongg,
            'lastBonggAt' => $incomingBongg,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function missingState(): array
    {
        return [
            'roomMissing' => true,
            'status' => VampireRoomStatus::Missing,
            'nightPhase' => null,
            'nightPhaseStartedAt' => null,
            'nightEndsAt' => null,
            'serverNow' => now()->timestamp,
            'nightNumber' => 0,
            'isHost' => false,
            'isJoined' => false,
            'myPlayerId' => null,
            'myRole' => null,
            'myAlignment' => null,
            'myAlive' => true,
            'players' => [],
            'myNightVote' => null,
            'myDoctorTarget' => null,
            'myDetectiveTarget' => null,
            'detectiveResult' => null,
            'detectiveInvestigationResults' => [],
            'lastProtectedId' => null,
            'nightVoteCounts' => [],
            'nightResult' => null,
            'dayVoteCounts' => [],
            'dayVoteMap' => [],
            'myDayVote' => null,
            'dayResult' => null,
            'configVampireCount' => 1,
            'configVillagerCount' => 2,
            'configHasDoktor' => false,
            'configHasDedektif' => false,
            'configHasAvci' => false,
            'winner' => null,
            'roomPassword' => null,
            'isPasswordProtected' => false,
            'history' => [],
            'shouldPlayBongg' => false,
            'lastBonggAt' => null,
        ];
    }
}
