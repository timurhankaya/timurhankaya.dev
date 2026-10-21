<?php

namespace App\Services\Vampire;

use Carbon\CarbonImmutable;

final class VampireNightClock
{
    public const DURATION_SECONDS = 45;

    public static function deadline(array $room): ?int
    {
        if (is_int($room['nightEndsAt'] ?? null)) {
            return $room['nightEndsAt'];
        }

        // Older rooms retain their original night start when the timer is introduced.
        $started = $room['nightPhaseStartedAt'] ?? null;

        return is_string($started) ? CarbonImmutable::parse($started)->timestamp + self::DURATION_SECONDS : null;
    }

    public static function expired(array $room): bool
    {
        $deadline = self::deadline($room);

        return $deadline !== null && CarbonImmutable::now()->timestamp >= $deadline;
    }
}
