<?php

declare(strict_types=1);

namespace App\Enums;

enum CaraUkurTinggi: string
{
    case TELENTANG = 'telentang';
    case BERDIRI = 'berdiri';

    public function label(): string
    {
        return match ($this) {
            self::TELENTANG => 'Telentang (Panjang Badan)',
            self::BERDIRI => 'Berdiri (Tinggi Badan)',
        };
    }
}
