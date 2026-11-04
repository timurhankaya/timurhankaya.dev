<?php

namespace App\Enums;

enum VampireRole: string
{
    case Vampire = 'vampire';
    case Villager = 'villager';
    case Doctor = 'doctor';
    case Detective = 'detective';
    case Hunter = 'hunter';

    public function label(): string
    {
        return match ($this) {
            self::Vampire => 'Vampir',
            self::Villager => 'Köylü',
            self::Doctor => 'Doktor',
            self::Detective => 'Dedektif',
            self::Hunter => 'Avcı',
        };
    }

    public function waitingTextKey(): string
    {
        return match ($this) {
            self::Vampire => 'vampire.atmosphere.role_waiting.vampire',
            self::Villager => 'vampire.atmosphere.role_waiting.villager',
            self::Doctor => 'vampire.atmosphere.role_waiting.doctor',
            self::Detective => 'vampire.atmosphere.role_waiting.detective',
            self::Hunter => 'vampire.atmosphere.role_waiting.hunter',
        };
    }

    public function isNightActor(): bool
    {
        return match ($this) {
            self::Vampire, self::Doctor, self::Detective => true,
            self::Villager, self::Hunter => false,
        };
    }

    public function isVampireTeam(): bool
    {
        return $this === self::Vampire;
    }
}
