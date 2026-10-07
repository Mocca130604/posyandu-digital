<?php

declare(strict_types=1);

namespace App\Enums;

enum KenaikanBb: string
{
    case NAIK = 'N';
    case TETAP_TURUN = 'T';
    case BARU = 'BARU';

    public function label(): string
    {
        return match ($this) {
            self::NAIK => 'Naik (N)',
            self::TETAP_TURUN => 'Tidak Naik / Turun / Tetap (T)',
            self::BARU => 'Penimbangan Baru (B)',
        };
    }

    public function isAlert(): bool
    {
        return $this === self::TETAP_TURUN;
    }
}
