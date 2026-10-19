<?php

namespace App\Support\Games;

use App\Enums\ImposterRoomStatus;

final class ImposterRoomPresenter
{
    /**
     * @param  array<string, mixed>|null  $room
     * @return array<string, mixed>
     */
    public function present(?array $room, ?string $sessionPlayerId, ?float $previousBonggAt, string $fallbackLanguage): array
    {
        if ($room === null) {
            return $this->missingState();
        }

        $status = ImposterRoomStatus::tryFrom((string) ($room['status'] ?? '')) ?? ImposterRoomStatus::Lobby;
        $roomPlayers = (array) ($room['players'] ?? []);
        $hostPlayerId = (string) ($room['hostPlayerId'] ?? '');

        $currentVoterId = (string) ($room['currentVoterId'] ?? '');
        $currentVoterName = $currentVoterId !== '' ? (string) ($roomPlayers[$currentVoterId]['name'] ?? '') : '';
        $voterQueueNames = $currentVoterName !== '' ? [$currentVoterName] : [];
        foreach ((array) ($room['voterQueue'] ?? []) as $queuedId) {
            $queuedName = (string) ($roomPlayers[$queuedId]['name'] ?? '');
            if ($queuedName !== '') {
                $voterQueueNames[] = $queuedName;
            }
        }

        $myPlayerId = is_string($sessionPlayerId) && $sessionPlayerId !== '' && isset($roomPlayers[$sessionPlayerId]) ? $sessionPlayerId : null;
        $isJoined = $myPlayerId !== null;

        $players = [];
        foreach ($roomPlayers as $player) {
            $players[] = [
                'id' => (string) ($player['id'] ?? ''),
                'name' => (string) ($player['name'] ?? ''),
                'color' => (string) ($player['color'] ?? 'sky'),
                'emoji' => (string) ($player['emoji'] ?? '🎭'),
                'isHost' => (string) ($player['id'] ?? '') === $hostPlayerId,
                'isMe' => $myPlayerId !== null && (string) ($player['id'] ?? '') === $myPlayerId,
            ];
        }

        $votes = (array) ($room['votes'] ?? []);
        $voteCounts = [];
        foreach ($votes as $targetId) {
            if (! is_string($targetId) || $targetId === '') {
                continue;
            }

            $voteCounts[$targetId] = ($voteCounts[$targetId] ?? 0) + 1;
        }

        $voteMap = [];
        if (in_array($status, [ImposterRoomStatus::Voting, ImposterRoomStatus::Results], true)) {
            foreach ($votes as $voterId => $targetId) {
                if (! is_string($voterId) || ! is_string($targetId) || $targetId === '') {
                    continue;
                }

                $voterName = (string) ($roomPlayers[$voterId]['name'] ?? '');
                if ($voterName !== '') {
                    $voteMap[$targetId][] = $voterName;
                }
            }
        }

        $myRole = null;
        $myWord = null;
        $starterName = null;
        if ($isJoined && in_array($status, [ImposterRoomStatus::Started, ImposterRoomStatus::Voting, ImposterRoomStatus::Results], true)) {
            $myRole = (string) ($roomPlayers[$myPlayerId]['role'] ?? '') ?: null;
            $word = (string) ($room['word'] ?? '');
            $myWord = $myRole === 'crew' ? $word : null;

            $starterId = (string) ($room['starterId'] ?? '');
            // The starter is always crew. With three players the other crew member would learn who the
            // imposter is, so the name is not sent at all (the page also hides it, but the payload is public).
            $starterName = $starterId !== '' && count($roomPlayers) !== 3 ? ((string) ($roomPlayers[$starterId]['name'] ?? '') ?: null) : null;
        }

        $revealedWord = null;
        $imposterName = null;
        $winner = null;
        if ($status === ImposterRoomStatus::Results && $isJoined) {
            $revealedWord = (string) ($room['word'] ?? '') ?: null;
            $imposter = null;
            foreach ($roomPlayers as $player) {
                if (($player['role'] ?? null) === 'imposter') {
                    $imposter = $player;
                    break;
                }
            }

            $imposterName = is_array($imposter) ? ((string) ($imposter['name'] ?? '') ?: null) : null;
            $imposterId = is_array($imposter) ? (string) ($imposter['id'] ?? '') : '';
            $hasImposterGuessed = (bool) ($room['imposterGuessed'] ?? false);

            if ($hasImposterGuessed) {
                $winner = 'imposter';
            } elseif ($imposterId !== '' && $voteCounts !== []) {
                $maxVotes = max($voteCounts);
                $topVoted = array_keys(array_filter($voteCounts, fn (int $count): bool => $count === $maxVotes));
                $winner = count($topVoted) === 1 && $topVoted[0] === $imposterId ? 'crew' : 'imposter';
            } else {
                $winner = 'imposter';
            }
        }

        $incomingBongg = (float) ($room['lastBonggAt'] ?? 0);

        return [
            'roomMissing' => false,
            'status' => $status,
            'language' => is_string($room['language'] ?? null) ? (string) $room['language'] : $fallbackLanguage,
            'players' => $players,
            'isHost' => $myPlayerId !== null && $myPlayerId === $hostPlayerId,
            'isJoined' => $isJoined,
            'myPlayerId' => $myPlayerId,
            'myRole' => $myRole,
            'myWord' => $myWord,
            'myVote' => $myPlayerId !== null ? ((string) ($votes[$myPlayerId] ?? '') ?: null) : null,
            'voteCounts' => $voteCounts,
            'voteMap' => $voteMap,
            'imposterName' => $imposterName,
            'revealedWord' => $revealedWord,
            'starterName' => $starterName,
            'currentVoterId' => $currentVoterId !== '' ? $currentVoterId : null,
            'currentVoterName' => $currentVoterName !== '' ? $currentVoterName : null,
            'voterQueueNames' => $voterQueueNames,
            'hasImposterGuessed' => (bool) ($room['imposterGuessed'] ?? false),
            'winner' => $winner,
            'isPasswordProtected' => is_string($room['password'] ?? null) && $room['password'] !== '',
            'roomPassword' => $myPlayerId !== null && $myPlayerId === $hostPlayerId && is_string($room['password'] ?? null) && $room['password'] !== ''
                ? (string) $room['password']
                : null,
            'showPlayers' => false,
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
            'status' => ImposterRoomStatus::Missing,
            'language' => 'en',
            'players' => [],
            'isHost' => false,
            'isJoined' => false,
            'myPlayerId' => null,
            'myRole' => null,
            'myWord' => null,
            'myVote' => null,
            'voteCounts' => [],
            'voteMap' => [],
            'imposterName' => null,
            'revealedWord' => null,
            'starterName' => null,
            'currentVoterId' => null,
            'currentVoterName' => null,
            'voterQueueNames' => [],
            'hasImposterGuessed' => false,
            'winner' => null,
            'isPasswordProtected' => false,
            'roomPassword' => null,
            'showPlayers' => false,
            'shouldPlayBongg' => false,
            'lastBonggAt' => null,
        ];
    }
}
