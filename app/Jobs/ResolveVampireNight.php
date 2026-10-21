<?php

namespace App\Jobs;

use App\Services\Vampire\VampireRoomService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ResolveVampireNight implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $roomCode,
        public string $roundId,
        public int $nightNumber,
        public int $deadline,
    ) {}

    public function handle(VampireRoomService $rooms): void
    {
        // Sync queues cannot delay a job. Joined-player polling remains a fallback.
        if (now()->timestamp < $this->deadline) {
            return;
        }

        $rooms->resolveExpiredNight($this->roomCode, $this->roundId, $this->nightNumber, $this->deadline);
    }
}
