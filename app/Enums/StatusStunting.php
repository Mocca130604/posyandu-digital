<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusStunting: string
{
    case SEVERELY_STUNTED = 'Severely Stunted';
    case STUNTED = 'Stunted';
    case NORMAL = 'Normal';
    case TINGGI = 'Tinggi';

    public function label(): string
    {
        return $this->value;
    }

    public function isRedZone(): bool
    {
        return match ($this) {
            self::SEVERELY_STUNTED, self::STUNTED => true,
            default => false,
        };
    }
}
