<?php

namespace App\Services\Games\Simulation;

use App\Services\Games\CacheRoomService;
use App\Services\Imposter\ImposterRoomService;
use App\Services\Vampire\VampireRoomService;
use App\Support\Games\ImposterRoomPresenter;
use App\Support\Games\VampireRoomPresenter;
use Illuminate\Support\Facades\Cache;

final class LocalGameSimulation
{
    private const TTL = 7200;

    public function __construct(private readonly BotMoves $moves) {}

    public function status(string $game, string $code, ?string $actor): array
    {
        return $this->withSimulation($game, $code, $actor, function ($rooms, $room, &$state) use ($game) {
            $config = $room['config'] ?? [];
            $reason = $state['reason'];
            if ($reason !== 'host_changed' && $state['stage'] !== $this->stage($room)) {
                $reason = $room['status'] === 'lobby' || ! in_array($room['status'], ['voting', 'night', 'day_voting', 'hunter_last_shot'], true)
                    || ($room['status'] === 'night' && ($room['nightPhase'] ?? null) === 'resolved') ? 'host_action' : 'ready';
            }

            return [
                'visible' => true,
                'count' => count($state['bots']),
                'automatic' => $state['automatic'],
                'reason' => $reason,
                'lobby' => $room['status'] === 'lobby',
                'playerCount' => count($room['players']),
                'roleCount' => $game === 'vampire' ? (int) $config['vampireCount'] + (int) $config['villagerCount']
                    + (int) $config['hasDoktor'] + (int) $config['hasDedektif'] + (int) $config['hasAvci'] : null,
            ];
        }) ?? ['visible' => false];
    }

    public function addBots(string $game, string $code, ?string $actor, int $count): void
    {
        $this->withSimulation($game, $code, $actor, function ($rooms, $room, &$state) use ($game, $code, $actor, $count) {
            if ($room['status'] !== 'lobby') {
                return;
            }
            $remaining = min(max(0, $count), 12 - count($state['bots']));
            for ($i = 0; $i < $remaining; $i++) {
                $room = $rooms->getRoom($code);
                if (! $this->authorized($game, $code, $actor, $room) || $room['status'] !== 'lobby') {
                    break;
                }
                try {
                    $joined = $rooms->joinRoom($code, 'Bot '.(++$state['serial']), $room['password'] ?? null, 'sky', '🤖', expectedRoom: $room);
                } catch (\InvalidArgumentException) {
                    break; // A concurrent host action may have closed the lobby.
                }
                if ($joined === null) {
                    break;
                }
                $state['bots'][] = $joined['playerId'];
                $this->save($game, $code, $state);
            }
            $state['reason'] = 'host_action';
        });
    }

    public function removeBots(string $game, string $code, ?string $actor): void
    {
        $this->withSimulation($game, $code, $actor, function ($rooms, $room, &$state) use ($game, $code, $actor) {
            foreach ($state['bots'] as $bot) {
                $room = $rooms->getRoom($code);
                if (! $this->authorized($game, $code, $actor, $room) || $room['status'] !== 'lobby') {
                    break;
                }
                // kickPlayer rechecks host and lobby under the game's own room lock.
                $rooms->kickPlayer($code, $actor, $bot);
            }
            $room = $rooms->getRoom($code);
            $state['bots'] = array_values(array_intersect($state['bots'], array_keys($room['players'] ?? [])));
            $state['reason'] = 'host_action';
        });
    }

    public function setAutomatic(string $game, string $code, ?string $actor, bool $enabled): void
    {
        $this->withSimulation($game, $code, $actor, function ($rooms, $room, &$state) use ($enabled) {
            $state['automatic'] = $enabled;
            $state['reason'] = $enabled ? 'ready' : 'paused';
        });
    }

    public function play(string $game, string $code, ?string $actor, bool $automaticOnly = false): void
    {
        $this->withSimulation($game, $code, $actor, function ($rooms, $room, &$state) use ($game, $code, $actor, $automaticOnly) {
            if ($automaticOnly && ! $state['automatic']) {
                return;
            }
            $stage = $this->stage($room);
            if ($state['stage'] !== $stage) {
                $state['stage'] = $stage;
                $state['completed'] = [];
            }
            $state['reason'] = 'host_action';
            if ($state['bots'] === []) {
                $state['reason'] = 'no_bots';

                return;
            }

            // Imposter can have several consecutive bot turns; Vampire has one action per bot.
            for ($pass = 0; $pass < 12; $pass++) {
                $progress = false;
                $noTargets = false;
                foreach ($state['bots'] as $bot) {
                    $room = $rooms->getRoom($code);
                    if (! $this->authorized($game, $code, $actor, $room) || $this->stage($room) !== $stage) {
                        return;
                    }
                    $view = $game === 'imposter'
                        ? app(ImposterRoomPresenter::class)->present($room, $bot, null, 'en')
                        : app(VampireRoomPresenter::class)->present($room, $bot, null);
                    $move = $this->moves->next($game, $view, $code.$stage);
                    if ($move === null || isset($state['completed'][$bot.':'.$move['method']])) {
                        continue;
                    }
                    if ($move['target'] === null) {
                        $noTargets = true;

                        continue;
                    }
                    $after = $rooms->{$move['method']}($code, $bot, $move['target'], expectedRoom: $room);
                    if ($after !== null && $after !== $room) {
                        $state['completed'][$bot.':'.$move['method']] = true;
                        $this->save($game, $code, $state);
                        $progress = true;
                    }
                }
                $room = $rooms->getRoom($code);
                if ($room === null) {
                    return;
                }
                $state['reason'] = match (true) {
                    $game === 'imposter' && $room['status'] === 'voting' && $room['currentVoterId'] !== null
                        && ! in_array($room['currentVoterId'], $state['bots'], true) => 'human_turn',
                    $noTargets => 'no_targets',
                    in_array($room['status'], ['voting', 'day_voting', 'night', 'hunter_last_shot'], true) => 'complete',
                    default => 'host_action',
                };
                if (! $progress || $game !== 'imposter') {
                    break;
                }
            }
        });
    }

    private function withSimulation(string $game, string $code, ?string $actor, callable $operation): mixed
    {
        if (! app()->environment(['local', 'testing']) || ! in_array($game, ['imposter', 'vampire'], true)) {
            return null;
        }

        return Cache::lock('simulation:lock:'.$game.':'.$code, 10)->block(3, function () use ($game, $code, $actor, $operation) {
            $rooms = $this->rooms($game);
            $room = $rooms->getRoom($code);
            if ($room === null) {
                Cache::forget('simulation:'.$game.':'.$code);

                return null;
            }
            $state = Cache::get('simulation:'.$game.':'.$code);
            if ($state === null || $state['createdAt'] !== $room['createdAt']) {
                $state = ['createdAt' => $room['createdAt'], 'host' => $room['hostPlayerId'], 'bots' => [], 'serial' => 0,
                    'automatic' => false, 'stage' => null, 'completed' => [], 'reason' => 'host_action'];
            }
            if ($state['host'] !== $room['hostPlayerId']) {
                $state['host'] = $room['hostPlayerId'];
                $state['automatic'] = false;
                $state['reason'] = 'host_changed';
            }
            $state['bots'] = array_values(array_intersect($state['bots'], array_keys($room['players'])));
            if (! $this->authorized($game, $code, $actor, $room)) {
                // Persist ownership changes even if an old host's poll noticed them first.
                if (Cache::has('simulation:'.$game.':'.$code)) {
                    $this->save($game, $code, $state);
                }

                return null;
            }
            $result = $operation($rooms, $room, $state);
            if ($rooms->getRoom($code) === null) {
                Cache::forget('simulation:'.$game.':'.$code);
            } else {
                $this->save($game, $code, $state);
            }

            return $result;
        });
    }

    private function authorized(string $game, string $code, ?string $actor, ?array $room): bool
    {
        return app()->environment(['local', 'testing']) && $actor !== null && $actor !== ''
            && session()->get($game.'.player.'.$code) === $actor
            && $room !== null && $room['hostPlayerId'] === $actor && isset($room['players'][$actor]);
    }

    private function rooms(string $game): CacheRoomService
    {
        return app($game === 'imposter' ? ImposterRoomService::class : VampireRoomService::class);
    }

    private function save(string $game, string $code, array $state): void
    {
        Cache::put('simulation:'.$game.':'.$code, $state, self::TTL);
    }

    private function stage(array $room): string
    {
        return hash('sha256', json_encode([
            $room['roundId'] ?? $room['startedAt'], $room['status'], $room['nightNumber'] ?? 0,
            $room['nightPhase'] ?? null, $room['votingStartedAt'] ?? $room['dayVotingStartedAt'] ?? null,
        ], JSON_THROW_ON_ERROR));
    }
}
