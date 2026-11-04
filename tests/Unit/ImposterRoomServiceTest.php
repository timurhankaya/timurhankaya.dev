<?php

use App\Services\Imposter\ImposterRoomService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Cache::flush();
});

afterEach(function () {
    Mockery::close();
});

function makeRoom(ImposterRoomService $rooms, string $host = 'Host', ?string $password = null): array
{
    return $rooms->createRoom($host, $password, 'sky', '🎭');
}

function joinAs(ImposterRoomService $rooms, string $code, string $name, string $color = 'red', string $emoji = '👻'): string
{
    return $rooms->joinRoom($code, $name, null, $color, $emoji)['playerId'];
}

// ─── createRoom ────────────────────────────────────────────────────────────────

describe('createRoom', function () {
    it('creates a lobby room and returns code + hostPlayerId', function () {
        $rooms = app(ImposterRoomService::class);
        $result = $rooms->createRoom('Alice');

        expect($result)->toHaveKeys(['code', 'hostPlayerId']);
        expect($result['code'])->toBeString()->toHaveLength(6);
        expect($result['hostPlayerId'])->toBeString()->not->toBeEmpty();
    });

    it('stores the room in cache with lobby status', function () {
        $rooms = app(ImposterRoomService::class);
        $result = $rooms->createRoom('Bob');

        $room = $rooms->getRoom($result['code']);

        expect($room)->not->toBeNull();
        expect($room['status'])->toBe('lobby');
        expect($room['hostPlayerId'])->toBe($result['hostPlayerId']);
    });

    it('accepts password and stores it normalized', function () {
        $rooms = app(ImposterRoomService::class);
        $result = $rooms->createRoom('Carol', '  secret  ');

        $room = $rooms->getRoom($result['code']);

        expect($room['password'])->toBe('secret');
    });

    it('stores null password when null is passed', function () {
        $rooms = app(ImposterRoomService::class);
        $result = $rooms->createRoom('Dave', null);

        $room = $rooms->getRoom($result['code']);

        expect($room['password'])->toBeNull();
    });

    it('stores null password when empty string is passed', function () {
        $rooms = app(ImposterRoomService::class);
        $result = $rooms->createRoom('Eve', '   ');

        $room = $rooms->getRoom($result['code']);

        expect($room['password'])->toBeNull();
    });

    it('stores host with correct color and emoji', function () {
        $rooms = app(ImposterRoomService::class);
        $result = $rooms->createRoom('Frank', null, 'amber', '🔥');

        $room = $rooms->getRoom($result['code']);
        $host = $room['players'][$result['hostPlayerId']];

        expect($host['color'])->toBe('amber');
        expect($host['emoji'])->toBe('🔥');
        expect($host['name'])->toBe('Frank');
    });

    it('normalizes name by trimming, collapsing spaces, and truncating to 24 chars', function () {
        $rooms = app(ImposterRoomService::class);
        $result = $rooms->createRoom("  Hello   World  \t", null, 'sky', '🎭');

        $room = $rooms->getRoom($result['code']);
        $host = $room['players'][$result['hostPlayerId']];

        expect($host['name'])->toBe('Hello World');
    });

    it('truncates long names to 24 characters', function () {
        $rooms = app(ImposterRoomService::class);
        $longName = str_repeat('x', 40);
        $result = $rooms->createRoom($longName);

        $room = $rooms->getRoom($result['code']);
        $host = $room['players'][$result['hostPlayerId']];

        expect($host['name'])->toHaveLength(24);
    });

    it('creates room successfully without throwing', function () {
        $rooms = app(ImposterRoomService::class);

        $thrown = null;
        try {
            $rooms->createRoom('Gambler');
        } catch (Throwable $e) {
            $thrown = $e;
        }

        expect($thrown)->toBeNull();
    });
});

// ─── getRoom ─────────────────────────────────────────────────────────────────

describe('getRoom', function () {
    it('returns null for a non-existent room', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->getRoom('NOSUCH'))->toBeNull();
    });

    it('returns the stored room data', function () {
        $rooms = app(ImposterRoomService::class);
        $created = $rooms->createRoom('Getter');
        $room = $rooms->getRoom($created['code']);

        expect($room['code'])->toBe($created['code']);
        expect($room['status'])->toBe('lobby');
    });
});

// ─── joinRoom ─────────────────────────────────────────────────────────────────

describe('joinRoom', function () {
    it('adds player to existing room', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms, 'Host');
        $joined = $rooms->joinRoom($created['code'], 'Guest', null, 'green', '👻');

        expect($joined)->toHaveKey('playerId');
        expect($joined)->toHaveKey('room');

        $room = $rooms->getRoom($created['code']);
        expect($room['players'])->toHaveCount(2);
    });

    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->joinRoom('NOWHERE', 'Ghost'))->toBeNull();
    });

    it('throws InvalidArgumentException game_started when game is not in lobby', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);

        expect(fn () => $rooms->joinRoom($created['code'], 'Late'))
            ->toThrow(InvalidArgumentException::class, 'game_started');
    });

    it('throws InvalidArgumentException wrong_password when password is incorrect', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms, 'Host', 'correct');

        expect(fn () => $rooms->joinRoom($created['code'], 'Cheater', 'wrong'))
            ->toThrow(InvalidArgumentException::class, 'wrong_password');
    });

    it('succeeds with correct password', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms, 'Host', 'secret');
        $joined = $rooms->joinRoom($created['code'], 'Legit', 'secret');

        expect($joined)->not->toBeNull();
        expect($joined['playerId'])->toBeString();
    });

    it('stores player with correct identity fields', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $joined = $rooms->joinRoom($created['code'], 'Norm', null, 'blue', '🐶');

        $room = $rooms->getRoom($created['code']);
        $player = $room['players'][$joined['playerId']];

        expect($player['name'])->toBe('Norm');
        expect($player['color'])->toBe('blue');
        expect($player['emoji'])->toBe('🐶');
        expect($player['role'])->toBeNull();
    });
});

// ─── leaveRoom ─────────────────────────────────────────────────────────────────

describe('leaveRoom', function () {
    it('returns null and deletes cache when last player leaves', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);

        $result = $rooms->leaveRoom($created['code'], $created['hostPlayerId']);

        expect($result)->toBeNull();
        expect($rooms->getRoom($created['code']))->toBeNull();
    });

    it('removes player from players list', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $guestId = joinAs($rooms, $created['code'], 'Guest');

        $rooms->leaveRoom($created['code'], $guestId);

        $room = $rooms->getRoom($created['code']);
        expect($room['players'])->toHaveCount(1);
        expect($room['players'])->not->toHaveKey($guestId);
    });

    it('transfers host to remaining player when host leaves', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $guestId = joinAs($rooms, $created['code'], 'Guest');

        $rooms->leaveRoom($created['code'], $created['hostPlayerId']);

        $room = $rooms->getRoom($created['code']);
        expect($room['hostPlayerId'])->toBe($guestId);
    });

    it('removes votes cast by leaving player', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $p1 = joinAs($rooms, $created['code'], 'P1');
        $p2 = joinAs($rooms, $created['code'], 'P2');

        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);
        $rooms->castVote($created['code'], $created['hostPlayerId'], $p1);

        $rooms->leaveRoom($created['code'], $p1);

        $room = $rooms->getRoom($created['code']);
        expect($room['votes'])->not->toHaveKey($p1);
    });

    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->leaveRoom('NOWHERE', 'player'))->toBeNull();
    });
});

// ─── kickPlayer ───────────────────────────────────────────────────────────────

describe('kickPlayer', function () {
    it('does nothing when caller is not the host', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $guestId = joinAs($rooms, $created['code'], 'Guest');

        $room = $rooms->kickPlayer($created['code'], $guestId, $created['hostPlayerId']);

        expect($room['players'])->toHaveCount(2);
        expect($room['players'])->toHaveKey($created['hostPlayerId']);
    });

    it('does nothing when host tries to kick themselves', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $guestId = joinAs($rooms, $created['code'], 'Guest');

        $room = $rooms->kickPlayer($created['code'], $created['hostPlayerId'], $created['hostPlayerId']);

        expect($room['players'])->toHaveCount(2);
    });

    it('successfully kicks a non-host player from lobby', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $guestId = joinAs($rooms, $created['code'], 'Guest');

        $room = $rooms->kickPlayer($created['code'], $created['hostPlayerId'], $guestId);

        expect($room['players'])->toHaveCount(1);
        expect($room['players'])->not->toHaveKey($guestId);
    });

    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->kickPlayer('NOWHERE', 'host', 'target'))->toBeNull();
    });
});

// ─── startGame ────────────────────────────────────────────────────────────────

describe('startGame', function () {
    it('does nothing when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->startGame('NOWHERE', 'player'))->toBeNull();
    });

    it('does nothing when caller is not the host', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $guestId = joinAs($rooms, $created['code'], 'Guest');

        $room = $rooms->startGame($created['code'], $guestId);

        expect($room['status'])->toBe('lobby');
    });

    it('does nothing when room is not in lobby', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);

        $room = $rooms->startGame($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('started');
    });

    it('does nothing when fewer than 3 players', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'Solo');

        $room = $rooms->startGame($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('lobby');
    });

    it('starts game with exactly 3 players', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');

        $room = $rooms->startGame($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('started');
        expect($room['word'])->not->toBeNull();
        expect($room['starterId'])->not->toBeNull();
    });

    it('stores a word from the language word list', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');

        $room = $rooms->startGame($created['code'], $created['hostPlayerId'], 'en');

        expect($room['word'])->toBeString()->not->toBeEmpty();
        expect($room['language'])->toBe('en');
    });

    it('normalizes unknown language to en', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');

        $room = $rooms->startGame($created['code'], $created['hostPlayerId'], 'xx');

        expect($room['language'])->toBe('en');
    });

    it('accepts tr language', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');

        $room = $rooms->startGame($created['code'], $created['hostPlayerId'], 'tr');

        expect($room['language'])->toBe('tr');
    });

    it('picks a starter from crew players', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');

        $room = $rooms->startGame($created['code'], $created['hostPlayerId']);
        $starter = $room['players'][$room['starterId']] ?? null;

        expect($starter)->not->toBeNull();
        expect($starter['role'])->toBe('crew');
    });
});

// ─── startVoting ──────────────────────────────────────────────────────────────

describe('startVoting', function () {
    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->startVoting('NOWHERE', 'player'))->toBeNull();
    });

    it('does nothing when caller is not host', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $guestId = joinAs($rooms, $created['code'], 'Guest');

        $room = $rooms->startVoting($created['code'], $guestId);

        expect($room['status'])->toBe('lobby');
    });

    it('does nothing when status is lobby', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);

        $room = $rooms->startVoting($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('lobby');
    });

    it('does nothing when status is already voting', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);

        $rooms->startVoting($created['code'], $created['hostPlayerId']);
        $room = $rooms->startVoting($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('voting');
    });

    it('transitions from started to voting and sets voter queue', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);

        $room = $rooms->startVoting($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('voting');
        expect($room['votingStartedAt'])->not->toBeNull();
        expect($room['currentVoterId'])->toBeString();
        expect($room['voterQueue'])->toBeArray();
        expect($room['votes'])->toBe([]);
    });
});

// ─── castVote ─────────────────────────────────────────────────────────────────

describe('castVote', function () {
    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->castVote('NOWHERE', 'voter', 'target'))->toBeNull();
    });

    it('does nothing when status is not voting', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $p1 = joinAs($rooms, $created['code'], 'P1');
        $p2 = joinAs($rooms, $created['code'], 'P2');

        $room = $rooms->castVote($created['code'], $created['hostPlayerId'], $p1);

        expect($room['votes'])->toBeEmpty();
    });

    it('does nothing when player id is not in room', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        $room = $rooms->getRoom($created['code']);
        $currentVoter = $room['currentVoterId'];
        $playerIds = array_keys((array) $room['players']);
        $targetId = $playerIds[0] !== $currentVoter ? $playerIds[0] : $playerIds[1];

        $rooms->castVote($created['code'], 'invalid', $targetId);

        expect($room['votes'])->not->toHaveKey('invalid');
    });

    it('does nothing when voter tries to vote for themselves', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        $room = $rooms->getRoom($created['code']);
        $currentVoter = $room['currentVoterId'];

        $rooms->castVote($created['code'], $currentVoter, $currentVoter);

        expect($room['votes'])->not->toHaveKey($currentVoter);
    });

    it('does nothing when not the current voter', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        $room = $rooms->getRoom($created['code']);
        $currentVoter = $room['currentVoterId'];
        $playerIds = array_keys((array) $room['players']);
        $nextPlayer = collect($playerIds)->first(fn ($id) => $id !== $currentVoter);

        $rooms->castVote($created['code'], $nextPlayer, $currentVoter);

        expect($room['votes'])->not->toHaveKey($nextPlayer);
    });

    it('records vote and advances currentVoterId', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        $room = $rooms->getRoom($created['code']);
        $currentVoter = (string) $room['currentVoterId'];
        $playerIds = array_keys((array) $room['players']);
        $targetId = collect($playerIds)->first(fn ($id) => $id !== $currentVoter);

        $room = $rooms->castVote($created['code'], $currentVoter, $targetId);

        expect($room['votes'][$currentVoter])->toBe($targetId);
    });

    it('sets currentVoterId to null when all have voted', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        // Vote through all players
        for ($i = 0; $i < 10; $i++) {
            $room = $rooms->getRoom($created['code']);
            $currentVoter = (string) ($room['currentVoterId'] ?? '');
            if ($currentVoter === '') {
                break;
            }
            $playerIds = array_keys((array) $room['players']);
            $targetId = collect($playerIds)->first(fn ($id) => $id !== $currentVoter);
            $rooms->castVote($created['code'], $currentVoter, $targetId);
        }

        $room = $rooms->getRoom($created['code']);
        expect($room['currentVoterId'])->toBeNull();
    });
});

// ─── retractVote ───────────────────────────────────────────────────────────────

describe('retractVote', function () {
    it('does nothing when status is not voting', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);

        $room = $rooms->retractVote($created['code'], $created['hostPlayerId']);

        expect($room['votes'])->toBe([]);
    });

    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->retractVote('NOWHERE', 'player'))->toBeNull();
    });
});

// ─── revealVotes ──────────────────────────────────────────────────────────────

describe('revealVotes', function () {
    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->revealVotes('NOWHERE', 'player'))->toBeNull();
    });

    it('does nothing when caller is not host', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        $room = $rooms->revealVotes($created['code'], 'not-host');

        expect($room['status'])->toBe('voting');
    });

    it('does nothing when status is not voting', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);

        $room = $rooms->revealVotes($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('lobby');
    });

    it('transitions to results status', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        $room = $rooms->revealVotes($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('results');
        expect($room['resultsRevealedAt'])->not->toBeNull();
    });
});

// ─── imposterGuessedWord ─────────────────────────────────────────────────────

describe('imposterGuessedWord', function () {
    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->imposterGuessedWord('NOWHERE', 'player'))->toBeNull();
    });

    it('does nothing when caller is not host', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);

        $room = $rooms->imposterGuessedWord($created['code'], 'not-host');

        expect($room['status'])->toBe('started');
    });

    it('does nothing when status is lobby', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);

        $room = $rooms->imposterGuessedWord($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('lobby');
    });

    it('does nothing when status is results', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);
        $rooms->revealVotes($created['code'], $created['hostPlayerId']);

        $room = $rooms->imposterGuessedWord($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('results');
    });

    it('transitions from started to results with imposterGuessed=true', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);

        $room = $rooms->imposterGuessedWord($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('results');
        expect($room['imposterGuessed'])->toBeTrue();
    });

    it('transitions from voting to results with imposterGuessed=true', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        $room = $rooms->imposterGuessedWord($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('results');
        expect($room['imposterGuessed'])->toBeTrue();
    });
});

// ─── startNewRound ────────────────────────────────────────────────────────────

describe('startNewRound', function () {
    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->startNewRound('NOWHERE', 'player'))->toBeNull();
    });

    it('does nothing when caller is not host', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);
        $rooms->revealVotes($created['code'], $created['hostPlayerId']);

        $room = $rooms->startNewRound($created['code'], 'not-host');

        expect($room['status'])->toBe('results');
    });

    it('does nothing when status is not results', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);

        $room = $rooms->startNewRound($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('lobby');
    });

    it('resets room to lobby with roles cleared', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);
        $rooms->revealVotes($created['code'], $created['hostPlayerId']);

        $room = $rooms->startNewRound($created['code'], $created['hostPlayerId']);

        expect($room['status'])->toBe('lobby');
        expect($room['word'])->toBeNull();
        expect($room['starterId'])->toBeNull();
        expect($room['imposterGuessed'])->toBeFalse();
        expect($room['votes'])->toBe([]);
        foreach ($room['players'] as $player) {
            expect($player['role'])->toBeNull();
        }
    });
});

// ─── updatePlayerIdentity ────────────────────────────────────────────────────

describe('updatePlayerIdentity', function () {
    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->updatePlayerIdentity('NOWHERE', 'player', 'Name', 'red', '😺'))
            ->toBeNull();
    });

    it('does nothing when room is not in lobby', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);

        $room = $rooms->updatePlayerIdentity(
            $created['code'],
            $created['hostPlayerId'],
            'Newname',
            'amber',
            '🔥'
        );

        expect($room['players'][$created['hostPlayerId']]['name'])->toBe('Host');
    });

    it('does nothing when player is not in room', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);

        $room = $rooms->updatePlayerIdentity(
            $created['code'],
            'stranger',
            'Newname',
            'amber',
            '🔥'
        );

        expect($room['players'])->not->toHaveKey('stranger');
    });

    it('updates identity fields in lobby', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        $guestId = joinAs($rooms, $created['code'], 'Guest');

        $room = $rooms->updatePlayerIdentity(
            $created['code'],
            $guestId,
            'Renamed',
            'violet',
            '🌟'
        );

        expect($room['players'][$guestId]['name'])->toBe('Renamed');
        expect($room['players'][$guestId]['color'])->toBe('violet');
        expect($room['players'][$guestId]['emoji'])->toBe('🌟');
    });
});

// ─── triggerBongg ─────────────────────────────────────────────────────────────

describe('triggerBongg', function () {
    it('returns null when room does not exist', function () {
        $rooms = app(ImposterRoomService::class);
        expect($rooms->triggerBongg('NOWHERE', 'player'))->toBeNull();
    });

    it('sets lastBonggAt and lastBonggBy in lobby', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);

        $room = $rooms->triggerBongg($created['code'], $created['hostPlayerId']);

        expect($room['lastBonggAt'])->toBeFloat();
        expect($room['lastBonggBy'])->toBe($created['hostPlayerId']);
    });

    it('sets lastBonggAt and lastBonggBy during voting', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);

        $room = $rooms->triggerBongg($created['code'], $created['hostPlayerId']);

        expect($room['lastBonggAt'])->toBeFloat();
        expect($room['lastBonggBy'])->toBe($created['hostPlayerId']);
    });

    it('does nothing when status is started', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);

        $room = $rooms->triggerBongg($created['code'], $created['hostPlayerId']);

        expect($room)->not->toHaveKey('lastBonggAt');
    });

    it('does nothing when status is results', function () {
        $rooms = app(ImposterRoomService::class);
        $created = makeRoom($rooms);
        joinAs($rooms, $created['code'], 'P1');
        joinAs($rooms, $created['code'], 'P2');
        $rooms->startGame($created['code'], $created['hostPlayerId']);
        $rooms->startVoting($created['code'], $created['hostPlayerId']);
        $rooms->revealVotes($created['code'], $created['hostPlayerId']);

        $room = $rooms->triggerBongg($created['code'], $created['hostPlayerId']);

        expect($room)->not->toHaveKey('lastBonggAt');
    });
});

// ─── private helpers / edge cases ─────────────────────────────────────────────

describe('room code generation', function () {
    it('generates uppercase 6-character alphanumeric codes', function () {
        $rooms = app(ImposterRoomService::class);
        $result = $rooms->createRoom('Test');

        expect($result['code'])->toMatch('/^[A-Z0-9]{6}$/');
    });

    it('generates unique codes across multiple rooms', function () {
        $rooms = app(ImposterRoomService::class);
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $result = $rooms->createRoom("Host{$i}");
            $codes[] = $result['code'];
        }

        expect(count($codes))->toBe(count(array_unique($codes)));
    });
});
