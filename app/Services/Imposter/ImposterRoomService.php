<?php

namespace App\Services\Imposter;

use App\Enums\ImposterRoomStatus;
use App\Services\Games\CacheRoomService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class ImposterRoomService extends CacheRoomService
{
    private const WORDS_TTL_SECONDS = 60 * 60 * 24;

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
            'status' => ImposterRoomStatus::Lobby->value,
            'hostPlayerId' => $hostPlayerId,
            'createdAt' => $now->toIso8601String(),
            'startedAt' => null,
            'votingStartedAt' => null,
            'resultsRevealedAt' => null,
            'language' => null,
            'word' => null,
            'starterId' => null,
            'imposterGuessed' => false,
            'voterQueue' => [],
            'currentVoterId' => null,
            'password' => $password,
            'votes' => [],
            'players' => [$hostPlayerId => $hostPlayer],
        ];
    }

    protected function roomNamespace(): string
    {
        return 'imposter';
    }

    protected function lobbyStatus(): string
    {
        return ImposterRoomStatus::Lobby->value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function makePlayerPayload(string $playerId, string $name, string $color, string $emoji, CarbonImmutable $now): array
    {
        return parent::makePlayerPayload($playerId, $name, $color, $emoji, $now) + [
            'role' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $room
     * @return array<string, mixed>
     */
    protected function afterPlayerLeft(array $room, string $playerId): array
    {
        $votes = (array) ($room['votes'] ?? []);
        unset($votes[$playerId]);
        $votes = array_filter(
            $votes,
            fn (mixed $target): bool => is_string($target) && $target !== $playerId,
        );

        $room['votes'] = $votes;

        return $room;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function startVoting(string $code, string $hostPlayerId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostPlayerId): ?array {
            $room = $this->getRoom($code);

            if ($room === null) {
                return null;
            }

            if (($room['hostPlayerId'] ?? null) !== $hostPlayerId) {
                return $room;
            }

            $status = ImposterRoomStatus::tryFrom((string) ($room['status'] ?? '')) ?? ImposterRoomStatus::Lobby;
            if (! in_array($status, [ImposterRoomStatus::Started, ImposterRoomStatus::Voting], true) || $status === ImposterRoomStatus::Voting) {
                return $room;
            }

            $voterQueue = array_keys((array) ($room['players'] ?? []));
            shuffle($voterQueue);

            $room['status'] = ImposterRoomStatus::Voting->value;
            $room['votingStartedAt'] = CarbonImmutable::now()->toIso8601String();
            $room['resultsRevealedAt'] = null;
            $room['votes'] = [];
            $room['voterQueue'] = $voterQueue;
            $room['currentVoterId'] = array_shift($room['voterQueue']);

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function castVote(string $code, string $playerId, string $targetPlayerId, ?array $expectedRoom = null): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId, $targetPlayerId, $expectedRoom): ?array {
            $room = $this->getRoom($code);
            // Local simulation submits against a fresh snapshot; reject concurrent host/phase changes.
            if ($expectedRoom !== null && $room !== $expectedRoom) {
                return null;
            }

            if ($room === null || ($room['status'] ?? null) !== ImposterRoomStatus::Voting->value) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            if (! isset($players[$playerId], $players[$targetPlayerId]) || $playerId === $targetPlayerId) {
                return $room;
            }

            if (($room['currentVoterId'] ?? '') !== $playerId) {
                return $room;
            }

            $votes = (array) ($room['votes'] ?? []);
            $votes[$playerId] = $targetPlayerId;
            $room['votes'] = $votes;
            $room['currentVoterId'] = ! empty($room['voterQueue']) ? array_shift($room['voterQueue']) : null;

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function triggerBongg(string $code, string $playerId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId): ?array {
            $room = $this->getRoom($code);

            if ($room === null) {
                return null;
            }

            $status = $room['status'] ?? null;
            if (! in_array($status, [ImposterRoomStatus::Voting->value, ImposterRoomStatus::Lobby->value], true)) {
                return $room;
            }

            $room['lastBonggAt'] = microtime(true);
            $room['lastBonggBy'] = $playerId;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function retractVote(string $code, string $playerId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['status'] ?? null) !== ImposterRoomStatus::Voting->value) {
                return $room;
            }

            $votes = (array) ($room['votes'] ?? []);
            unset($votes[$playerId]);
            $room['votes'] = $votes;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function revealVotes(string $code, string $hostPlayerId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostPlayerId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['hostPlayerId'] ?? null) !== $hostPlayerId || ($room['status'] ?? null) !== ImposterRoomStatus::Voting->value) {
                return $room;
            }

            $room['status'] = ImposterRoomStatus::Results->value;
            $room['resultsRevealedAt'] = CarbonImmutable::now()->toIso8601String();
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function imposterGuessedWord(string $code, string $hostPlayerId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostPlayerId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['hostPlayerId'] ?? null) !== $hostPlayerId) {
                return $room;
            }

            $status = ImposterRoomStatus::tryFrom((string) ($room['status'] ?? '')) ?? ImposterRoomStatus::Lobby;
            if (! in_array($status, [ImposterRoomStatus::Started, ImposterRoomStatus::Voting], true)) {
                return $room;
            }

            $room['status'] = ImposterRoomStatus::Results->value;
            $room['resultsRevealedAt'] = CarbonImmutable::now()->toIso8601String();
            $room['imposterGuessed'] = true;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function startNewRound(string $code, string $hostPlayerId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostPlayerId): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['hostPlayerId'] ?? null) !== $hostPlayerId || ($room['status'] ?? null) !== ImposterRoomStatus::Results->value) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            foreach ($players as $playerId => $player) {
                $players[$playerId]['role'] = null;
            }

            $room['players'] = $players;
            $room['status'] = ImposterRoomStatus::Lobby->value;
            $room['word'] = null;
            $room['starterId'] = null;
            $room['imposterGuessed'] = false;
            $room['voterQueue'] = [];
            $room['currentVoterId'] = null;
            $room['startedAt'] = null;
            $room['votingStartedAt'] = null;
            $room['resultsRevealedAt'] = null;
            $room['votes'] = [];

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function startGame(string $code, string $hostPlayerId, string $language = 'en'): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostPlayerId, $language): ?array {
            $room = $this->getRoom($code);

            if ($room === null || ($room['status'] ?? null) !== ImposterRoomStatus::Lobby->value || ($room['hostPlayerId'] ?? null) !== $hostPlayerId) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            if (count($players) < 3) {
                return $room;
            }

            $language = $this->normalizeLanguage($language);
            $word = $this->pickWord($language);

            $playerIds = array_keys($players);
            $imposterId = $playerIds[random_int(0, count($playerIds) - 1)];

            $crewIds = [];
            foreach ($players as $playerId => $player) {
                $isImposter = $playerId === $imposterId;
                $players[$playerId]['role'] = $isImposter ? 'imposter' : 'crew';
                if (! $isImposter) {
                    $crewIds[] = $playerId;
                }
            }

            $starterId = count($crewIds) > 0 ? $crewIds[random_int(0, count($crewIds) - 1)] : $imposterId;

            $room['players'] = $players;
            $room['status'] = ImposterRoomStatus::Started->value;
            $room['starterId'] = $starterId;
            $room['startedAt'] = CarbonImmutable::now()->toIso8601String();
            $room['roundId'] = (string) Str::uuid();
            $room['language'] = $language;
            $room['word'] = $word;
            $room['votingStartedAt'] = null;
            $room['resultsRevealedAt'] = null;
            $room['votes'] = [];

            $this->putRoom($code, $room);

            return $room;
        });
    }

    private function normalizeLanguage(string $language): string
    {
        $language = strtolower(trim($language));

        return in_array($language, ['tr', 'en'], true) ? $language : 'en';
    }

    private function pickWord(string $language): string
    {
        /** @var array<int, string> $words */
        $words = Cache::remember(
            'imposter:words:'.$language,
            self::WORDS_TTL_SECONDS,
            fn (): array => $this->loadWordList($language),
        );

        if ($words === []) {
            throw new \RuntimeException('No words available for language: '.$language);
        }

        return $words[random_int(0, count($words) - 1)];
    }

    /**
     * @return array<int, string>
     */
    private function loadWordList(string $language): array
    {
        $path = resource_path("content/imposter/words.{$language}.json");
        $json = file_get_contents($path);

        if (! is_string($json)) {
            throw new \RuntimeException("Unable to read word list: {$path}");
        }

        /** @var array{words?: mixed} $data */
        $data = json_decode($json, true);
        $words = is_array($data) ? ($data['words'] ?? []) : [];
        if (! is_array($words)) {
            return [];
        }

        return array_values(array_filter(array_map(function (mixed $word): ?string {
            if (! is_string($word)) {
                return null;
            }

            $word = trim($word);

            return $word !== '' ? $word : null;
        }, $words)));
    }
}
