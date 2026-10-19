<?php

namespace App\Livewire\Games\Imposter;

use App\Services\Imposter\ImposterRoomService;
use App\Support\GameIdentity;
use App\Support\Seo;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    public string $roomCode = '';

    public string $createPassword = '';

    public string $joinPassword = '';

    public ?string $error = null;

    public function createRoom(ImposterRoomService $rooms): mixed
    {
        $this->error = null;

        if (! GameIdentity::exists()) {
            $this->error = (string) __('imposter.identityRequired');

            return null;
        }

        $validated = $this->validate([
            'createPassword' => ['nullable', 'string', 'max:32'],
        ]);

        $created = $rooms->createRoom(
            GameIdentity::name(),
            $validated['createPassword'] ?: null,
            GameIdentity::color(),
            GameIdentity::emoji(),
        );

        session()->put($this->playerSessionKey($created['code']), $created['hostPlayerId']);

        return redirect(Seo::route('games.imposter.room', ['roomCode' => $created['code']]));
    }

    public function joinRoom(ImposterRoomService $rooms): mixed
    {
        $this->error = null;

        if (! GameIdentity::exists()) {
            $this->error = (string) __('imposter.identityRequired');

            return null;
        }

        $validated = $this->validate([
            'roomCode' => ['required', 'string', 'min:4', 'max:10'],
            'joinPassword' => ['nullable', 'string', 'max:32'],
        ]);

        $code = strtoupper(trim($validated['roomCode']));

        $joined = $rooms->joinRoom(
            $code,
            GameIdentity::name(),
            $validated['joinPassword'] ?: null,
            GameIdentity::color(),
            GameIdentity::emoji(),
        );

        if ($joined === null) {
            $this->error = (string) __('imposter.roomNotFoundDesc');

            return null;
        }

        session()->put($this->playerSessionKey($code), $joined['playerId']);

        return redirect(Seo::route('games.imposter.room', ['roomCode' => $code]));
    }

    #[On('identity-saved')]
    public function onIdentitySaved(): void {}

    private function playerSessionKey(string $code): string
    {
        return 'imposter.player.'.$code;
    }

    public function render(): View
    {
        return view('livewire.games.imposter.index', [
            'hasIdentity' => GameIdentity::exists(),
        ]);
    }
}
