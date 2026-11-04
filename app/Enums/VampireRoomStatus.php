<?php

namespace App\Enums;

enum VampireRoomStatus: string
{
    case Loading = 'loading';

    case Missing = 'missing';

    case Lobby = 'lobby';

    case Night = 'night';

    case Dawn = 'dawn';

    case HunterLastShot = 'hunter_last_shot';

    case Day = 'day';

    case DayVoting = 'day_voting';

    case DayResults = 'day_results';

    case GameOver = 'game_over';

    public function label(): string
    {
        return match ($this) {
            self::Loading => 'Yükleniyor',
            self::Missing => 'Bulunamadı',
            self::Lobby => 'Lobi',
            self::Night => 'Gece',
            self::Dawn => 'Şafak',
            self::HunterLastShot => 'Avcı Son Atış',
            self::Day => 'Gündüz',
            self::DayVoting => 'Gündüz - Oy',
            self::DayResults => 'Gündüz - Sonuç',
            self::GameOver => 'Oyun Bitti',
        };
    }

    public function isNight(): bool
    {
        return in_array($this, [self::Night, self::Dawn, self::HunterLastShot]);
    }
}
