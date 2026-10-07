<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusGizi: string
{
    case GIZI_BURUK = 'Gizi Buruk';
    case GIZI_KURANG = 'Gizi Kurang';
    case GIZI_BAIK = 'Gizi Baik';
    case RISIKO_GIZI_LEBIH = 'Risiko Gizi Lebih';

    public function label(): string
    {
        return $this->value;
    }

    public function isRedZone(): bool
    {
        return match ($this) {
            self::GIZI_BURUK, self::GIZI_KURANG => true,
            default => false,
        };
    }
}
