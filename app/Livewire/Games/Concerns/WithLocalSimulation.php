<?php

namespace App\Livewire\Games\Concerns;

use App\Livewire\Games\Imposter\Room;
use App\Services\Games\Simulation\LocalGameSimulation;
use App\Services\Imposter\ImposterRoomService;
use App\Services\Vampire\VampireRoomService;
use Livewire\Attributes\Computed;

trait WithLocalSimulation
{
    public int $simulationBotCount = 3;

    #[Computed]
    public function simulationStatus(): array
    {
        return app(LocalGameSimulation::class)->status($this->simulationGame(), $this->roomCode, $this->simulationActor());
    }

    public function addSimulationBots(): void
    {
        app(LocalGameSimulation::class)->addBots($this->simulationGame(), $this->roomCode, $this->simulationActor(), $this->simulationBotCount);
        $this->refreshSimulationRoom();
    }

    public function removeSimulationBots(): void
    {
        app(LocalGameSimulation::class)->removeBots($this->simulationGame(), $this->roomCode, $this->simulationActor());
        $this->refreshSimulationRoom();
    }

    public function playSimulationBots(): void
    {
        app(LocalGameSimulation::class)->play($this->simulationGame(), $this->roomCode, $this->simulationActor());
        $this->refreshSimulationRoom();
    }

    public function toggleSimulationAutomatic(): void
    {
        $simulation = app(LocalGameSimulation::class);
        $status = $simulation->status($this->simulationGame(), $this->roomCode, $this->simulationActor());
        if ($status['visible']) {
            $simulation->setAutomatic($this->simulationGame(), $this->roomCode, $this->simulationActor(), ! $status['automatic']);
        }
        $this->refreshSimulationRoom();
    }

    protected function runAutomaticSimulation(): void
    {
        app(LocalGameSimulation::class)->play($this->simulationGame(), $this->roomCode, $this->simulationActor(), true);
        unset($this->simulationStatus);
    }

    private function simulationActor(): ?string
    {
        $actor = session()->get($this->playerSessionKey());

        return is_string($actor) ? $actor : null;
    }

    private function simulationGame(): string
    {
        return $this instanceof Room ? 'imposter' : 'vampire';
    }

    private function refreshSimulationRoom(): void
    {
        unset($this->simulationStatus);
        $this->refreshRoom(app($this->simulationGame() === 'imposter' ? ImposterRoomService::class : VampireRoomService::class));
    }
}
