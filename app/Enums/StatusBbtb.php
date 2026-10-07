<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusBbtb: string
{
    case GIZI_BURUK = 'Gizi Buruk';
    case GIZI_KURANG = 'Gizi Kurang';
    case GIZI_BAIK = 'Gizi Baik';
    case BERISIKO_GIZI_LEBIH = 'Berisiko Gizi Lebih';
    case GIZI_LEBIH = 'Gizi Lebih';
    case OBESITAS = 'Obesitas';
    case DI_LUAR_RENTANG = 'Di luar rentang';

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
