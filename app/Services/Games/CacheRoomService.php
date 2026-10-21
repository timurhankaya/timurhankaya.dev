<?php

namespace App\Services\Games;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

abstract class CacheRoomService
{
    protected const ROOM_TTL_SECONDS = 60 * 60 * 2;

    /**
     * @return array{code: string, hostPlayerId: string}
     */
    public function createRoom(string $hostName, ?string $password = null, string $color = 'sky', string $emoji = '🎭'): array
    {
        $now = CarbonImmutable::now();
        $hostPlayerId = (string) Str::uuid();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $this->generateRoomCode();
            $room = $this->initialRoomState(
                $code,
                $hostPlayerId,
                $this->makePlayerPayload($hostPlayerId, $hostName, $color, $emoji, $now),
                $this->normalizePassword($password),
                $now,
            );

            if (Cache::add($this->roomCacheKey($code), $room, static::ROOM_TTL_SECONDS)) {
                return ['code' => $code, 'hostPlayerId' => $hostPlayerId];
            }
        }

        throw new \RuntimeException('Unable to create a room. Please try again.');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRoom(string $code): ?array
    {
        /** @var array<string, mixed>|null $room */
        $room = Cache::get($this->roomCacheKey($code));

        return $room;
    }

    /**
     * @return array{playerId: string, room: array<string, mixed>}|null
     */
    public function joinRoom(string $code, string $name, ?string $password = null, string $color = 'sky', string $emoji = '🎭', ?array $expectedRoom = null): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $name, $password, $color, $emoji, $expectedRoom): ?array {
            $room = $this->getRoom($code);

            if ($expectedRoom !== null && $room !== $expectedRoom) {
                return null;
            }

            if ($room === null) {
                return null;
            }

            $this->assertRoomJoinable($room, $password);

            $playerId = (string) Str::uuid();
            $players = (array) ($room['players'] ?? []);
            $players[$playerId] = $this->makePlayerPayload(
                $playerId,
                $name,
                $color,
                $emoji,
                CarbonImmutable::now(),
            );

            $room['players'] = $players;
            $room = $this->afterPlayerJoined($room, $playerId);
            $this->putRoom($code, $room);

            return ['playerId' => $playerId, 'room' => $room];
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function leaveRoom(string $code, string $playerId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId): ?array {
            $room = $this->getRoom($code);

            if ($room === null) {
                return null;
            }

            $players = (array) ($room['players'] ?? []);
            unset($players[$playerId]);
            $room['players'] = $players;
            $room = $this->afterPlayerLeft($room, $playerId);

            if (count($players) === 0) {
                Cache::forget($this->roomCacheKey($code));

                return null;
            }

            if (($room['hostPlayerId'] ?? null) === $playerId) {
                $room['hostPlayerId'] = array_key_first($players);
            }

            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function kickPlayer(string $code, string $hostId, string $targetId): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $hostId, $targetId): ?array {
            $room = $this->getRoom($code);

            if ($room === null) {
                return null;
            }

            if (($room['hostPlayerId'] ?? null) !== $hostId) {
                return $room;
            }

            if ($hostId === $targetId || ($room['status'] ?? null) !== $this->lobbyStatus()) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            unset($players[$targetId]);
            $room['players'] = $players;
            $room = $this->afterPlayerKicked($room, $targetId);
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function updatePlayerIdentity(string $code, string $playerId, string $name, string $color, string $emoji): ?array
    {
        return $this->withRoomLock($code, function () use ($code, $playerId, $name, $color, $emoji): ?array {
            $room = $this->getRoom($code);

            if ($room === null) {
                return null;
            }

            if (($room['status'] ?? null) !== $this->lobbyStatus()) {
                return $room;
            }

            $players = (array) ($room['players'] ?? []);
            if (! isset($players[$playerId])) {
                return $room;
            }

            $players[$playerId] = array_merge($players[$playerId], [
                'name' => $this->normalizeName($name),
                'color' => $color,
                'emoji' => $emoji,
            ]);

            $room['players'] = $players;
            $this->putRoom($code, $room);

            return $room;
        });
    }

    /**
     * @param  callable(): mixed  $callback
     */
    protected function withRoomLock(string $code, callable $callback): mixed
    {
        return Cache::lock($this->roomLockKey($code), 5)->block(3, $callback);
    }

    /**
     * @return array<string, mixed>
     */
    protected function makePlayerPayload(string $playerId, string $name, string $color, string $emoji, CarbonImmutable $now): array
    {
        return [
            'id' => $playerId,
            'name' => $this->normalizeName($name),
            'color' => $color,
            'emoji' => $emoji,
            'joinedAt' => $now->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $room
     */
    protected function assertRoomJoinable(array $room, ?string $password): void
    {
        if (($room['status'] ?? null) !== $this->lobbyStatus()) {
            throw new \InvalidArgumentException('game_started');
        }

        $roomPassword = is_string($room['password'] ?? null) && $room['password'] !== '' ? (string) $room['password'] : null;
        if ($roomPassword !== null && $roomPassword !== $this->normalizePassword($password)) {
            throw new \InvalidArgumentException('wrong_password');
        }
    }

    /**
     * @param  array<string, mixed>  $room
     * @return array<string, mixed>
     */
    protected function afterPlayerJoined(array $room, string $playerId): array
    {
        return $room;
    }

    /**
     * @param  array<string, mixed>  $room
     * @return array<string, mixed>
     */
    protected function afterPlayerLeft(array $room, string $playerId): array
    {
        return $room;
    }

    /**
     * @param  array<string, mixed>  $room
     * @return array<string, mixed>
     */
    protected function afterPlayerKicked(array $room, string $playerId): array
    {
        return $this->afterPlayerLeft($room, $playerId);
    }

    /**
     * @param  array<string, mixed>  $room
     */
    protected function putRoom(string $code, array $room): void
    {
        Cache::put($this->roomCacheKey($code), $room, static::ROOM_TTL_SECONDS);
    }

    protected function roomCacheKey(string $code): string
    {
        return $this->roomNamespace().':room:'.$code;
    }

    protected function roomLockKey(string $code): string
    {
        return $this->roomNamespace().':lock:room:'.$code;
    }

    protected function generateRoomCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length = 6;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    protected function normalizeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');

        return Str::limit($name, 24, '');
    }

    protected function normalizePassword(?string $password): ?string
    {
        if ($password === null) {
            return null;
        }

        $password = trim($password);

        return $password !== '' ? Str::limit($password, 32, '') : null;
    }

    abstract protected function roomNamespace(): string;

    abstract protected function lobbyStatus(): string;

    /**
     * @param  array<string, mixed>  $hostPlayer
     * @return array<string, mixed>
     */
    abstract protected function initialRoomState(
        string $code,
        string $hostPlayerId,
        array $hostPlayer,
        ?string $password,
        CarbonImmutable $now,
    ): array;
}
