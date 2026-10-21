<?php

namespace App\Livewire\Games\Imposter;

use App\Enums\ImposterRoomStatus;
use App\Livewire\Games\Concerns\WithLocalSimulation;
use App\Services\Imposter\ImposterRoomService;
use App\Support\GameIdentity;
use App\Support\Games\ImposterRoomPresenter;
use App\Support\Seo;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class Room extends Component
{
    use WithLocalSimulation;

    #[Locked]
    public string $roomCode;

    public ImposterRoomStatus $status = ImposterRoomStatus::Loading;

    public string $language = 'en';

    /**
     * @var array<int, array{id: string, name: string, isHost: bool, isMe: bool}>
     */
    public array $players = [];

    public bool $isHost = false;

    public bool $isJoined = false;

    public ?string $myPlayerId = null;

    public ?string $myRole = null;

    public ?string $myWord = null;

    public ?string $myVote = null;

    /**
     * @var array<string, int>
     */
    public array $voteCounts = [];

    /**
     * @var array<string, list<string>> targetPlayerId => [voterName, ...]
     */
    public array $voteMap = [];

    public ?string $imposterName = null;

    public ?string $revealedWord = null;

    public ?string $starterName = null;

    public ?string $currentVoterId = null;

    public ?string $currentVoterName = null;

    public array $voterQueueNames = [];

    public ?float $lastBonggAt = null;

    public bool $hasImposterGuessed = false;

    public ?string $confirmKickPlayerId = null;

    public ?string $confirmKickPlayerName = null;

    /**
     * 'crew' | 'imposter' | null
     */
    public ?string $winner = null;

    public ?string $joinPassword = null;

    public bool $isPasswordProtected = false;

    public bool $confirmImposterGuessed = false;

    public ?string $error = null;

    public ?string $roomPassword = null;

    public bool $roomMissing = false;

    public bool $showPlayers = false;

    public bool $roleVisible = true;

    public bool $hasIdentity = false;

    public function mount(string $roomCode, ImposterRoomService $rooms): void
    {
        $this->roomCode = strtoupper($roomCode);
        $this->language = in_array(app()->getLocale(), ['tr', 'en'], true) ? app()->getLocale() : 'en';
        $this->hasIdentity = GameIdentity::exists();
        $this->refreshRoom($rooms);
    }

    #[On('identity-saved')]
    public function updateIdentity(ImposterRoomService $rooms): void
    {
        $this->hasIdentity = GameIdentity::exists();

        $playerId = session()->get($this->playerSessionKey());
        if (is_string($playerId) && $playerId !== '' && $this->status === ImposterRoomStatus::Lobby) {
            $rooms->updatePlayerIdentity(
                $this->roomCode,
                $playerId,
                GameIdentity::name(),
                GameIdentity::color(),
                GameIdentity::emoji(),
            );
            $this->refreshRoom($rooms);
        }
    }

    public function refreshRoom(ImposterRoomService $rooms): void
    {
        $this->runAutomaticSimulation();
        $playerId = session()->get($this->playerSessionKey());
        $snapshot = app(ImposterRoomPresenter::class)->present(
            $rooms->getRoom($this->roomCode),
            is_string($playerId) ? $playerId : null,
            $this->lastBonggAt,
            $this->language,
        );

        $this->applySnapshot($snapshot);

        if (($snapshot['shouldPlayBongg'] ?? false) === true) {
            $this->dispatch('play-bongg');
        }
    }

    public function join(ImposterRoomService $rooms): void
    {
        $this->error = null;
        $this->resetErrorBag('joinPassword');

        if (! GameIdentity::exists()) {
            $this->error = (string) __('imposter.joinError');

            return;
        }

        if ($this->isPasswordProtected && empty($this->joinPassword)) {
            $this->addError('joinPassword', (string) __('imposter.errors.password_required'));

            return;
        }

        try {
            $joined = $rooms->joinRoom(
                $this->roomCode,
                GameIdentity::name(),
                $this->joinPassword,
                GameIdentity::color(),
                GameIdentity::emoji(),
            );
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() === 'wrong_password') {
                $this->addError('joinPassword', (string) __('imposter.errors.wrong_password'));

                return;
            }
            if ($e->getMessage() === 'game_started') {
                $this->error = (string) __('imposter.errors.game_started');

                return;
            }
            throw $e;
        }

        if ($joined === null) {
            $this->error = (string) __('imposter.joinError');

            return;
        }

        session()->put($this->playerSessionKey(), $joined['playerId']);
        $this->refreshRoom($rooms);
    }

    public function start(ImposterRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        if (count($this->players) < 3) {
            $this->error = (string) __('imposter.errorMinPlayers');

            return;
        }

        $rooms->startGame($this->roomCode, $playerId, $this->language);
        $this->error = null;
        $this->refreshRoom($rooms);
    }

    public function startVoting(ImposterRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->startVoting($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function vote(ImposterRoomService $rooms, string $targetPlayerId): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        if ($this->myVote === $targetPlayerId) {
            $rooms->retractVote($this->roomCode, $playerId);
        } else {
            $rooms->castVote($this->roomCode, $playerId, $targetPlayerId);
        }

        $this->refreshRoom($rooms);
    }

    public function bongg(ImposterRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->triggerBongg($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function toggleRole(): void
    {
        $this->roleVisible = ! $this->roleVisible;
    }

    public function imposterGuessed(ImposterRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->imposterGuessedWord($this->roomCode, $playerId);
        $this->confirmImposterGuessed = false;
        $this->refreshRoom($rooms);
    }

    public function revealVotes(ImposterRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->revealVotes($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function newRound(ImposterRoomService $rooms): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '') {
            return;
        }

        $rooms->startNewRound($this->roomCode, $playerId);
        $this->refreshRoom($rooms);
    }

    public function leave(ImposterRoomService $rooms): mixed
    {
        $playerId = session()->get($this->playerSessionKey());
        if (is_string($playerId) && $playerId !== '') {
            $rooms->leaveRoom($this->roomCode, $playerId);
        }

        session()->forget($this->playerSessionKey());

        return redirect(Seo::route('games.imposter.index'));
    }

    public function openKickConfirm(string $id, string $name): void
    {
        $this->confirmKickPlayerId = $id;
        $this->confirmKickPlayerName = $name;
    }

    public function closeKickConfirm(): void
    {
        $this->confirmKickPlayerId = null;
        $this->confirmKickPlayerName = null;
    }

    public function kickPlayer(ImposterRoomService $rooms, string $targetId): void
    {
        $playerId = session()->get($this->playerSessionKey());
        if (! is_string($playerId) || $playerId === '' || ! $this->isHost) {
            return;
        }

        $rooms->kickPlayer($this->roomCode, $playerId, $targetId);
        $this->closeKickConfirm();
        $this->refreshRoom($rooms);
    }

    public function openPlayers(): void
    {
        $this->showPlayers = true;
    }

    public function closePlayers(): void
    {
        $this->showPlayers = false;
    }

    public function render(): View
    {
        return view('livewire.games.imposter.room');
    }

    private function playerSessionKey(): string
    {
        return 'imposter.player.'.$this->roomCode;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function applySnapshot(array $snapshot): void
    {
        foreach ($snapshot as $property => $value) {
            if ($property === 'shouldPlayBongg' || $property === 'showPlayers') {
                continue;
            }

            $this->{$property} = $value;
        }

        if ($this->roomMissing) {
            $this->showPlayers = false;
        }
    }
}
