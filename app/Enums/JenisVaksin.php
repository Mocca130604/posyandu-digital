<?php

declare(strict_types=1);

namespace App\Enums;

enum JenisVaksin: string
{
    case HB0 = 'HB0';
    case BCG = 'BCG';
    case POLIO_1 = 'POLIO_1';
    case DPT_HB_HIB_1 = 'DPT_HB_HIB_1';
    case POLIO_2 = 'POLIO_2';
    case PCV_1 = 'PCV_1';
    case ROTAVIRUS_1 = 'ROTAVIRUS_1';
    case DPT_HB_HIB_2 = 'DPT_HB_HIB_2';
    case POLIO_3 = 'POLIO_3';
    case PCV_2 = 'PCV_2';
    case ROTAVIRUS_2 = 'ROTAVIRUS_2';
    case DPT_HB_HIB_3 = 'DPT_HB_HIB_3';
    case POLIO_4 = 'POLIO_4';
    case IPV_1 = 'IPV_1';
    case ROTAVIRUS_3 = 'ROTAVIRUS_3';
    case IPV_2 = 'IPV_2';
    case CAMPAK_RUBELA = 'CAMPAK_RUBELA';
    case PCV_3 = 'PCV_3';
    case DPT_LANJUTAN = 'DPT_LANJUTAN';
    case CAMPAK_LANJUTAN = 'CAMPAK_LANJUTAN';

    public function label(): string
    {
        return match ($this) {
            self::HB0 => 'Hepatitis B (HB-0)',
            self::BCG => 'BCG',
            self::POLIO_1 => 'Polio Tetes 1 (bOPV 1)',
            self::DPT_HB_HIB_1 => 'DPT-HB-Hib 1 (Pentabio 1)',
            self::POLIO_2 => 'Polio Tetes 2 (bOPV 2)',
            self::PCV_1 => 'PCV 1 (Pneumokokus 1)',
            self::ROTAVIRUS_1 => 'Rotavirus 1',
            self::DPT_HB_HIB_2 => 'DPT-HB-Hib 2 (Pentabio 2)',
            self::POLIO_3 => 'Polio Tetes 3 (bOPV 3)',
            self::PCV_2 => 'PCV 2 (Pneumokokus 2)',
            self::ROTAVIRUS_2 => 'Rotavirus 2',
            self::DPT_HB_HIB_3 => 'DPT-HB-Hib 3 (Pentabio 3)',
            self::POLIO_4 => 'Polio Tetes 4 (bOPV 4)',
            self::IPV_1 => 'Polio Suntik 1 (IPV 1)',
            self::ROTAVIRUS_3 => 'Rotavirus 3',
            self::IPV_2 => 'Polio Suntik 2 (IPV 2)',
            self::CAMPAK_RUBELA => 'Campak-Rubela (MR 1)',
            self::PCV_3 => 'PCV 3 (Pneumokokus 3)',
            self::DPT_LANJUTAN => 'DPT-HB-Hib Lanjutan (Booster)',
            self::CAMPAK_LANJUTAN => 'Campak-Rubela Lanjutan (MR 2 / Booster)',
        };
    }

    public function usiaPemberianBulan(): int
    {
        return match ($this) {
            self::HB0 => 0,
            self::BCG, self::POLIO_1 => 1,
            self::DPT_HB_HIB_1, self::POLIO_2, self::PCV_1, self::ROTAVIRUS_1 => 2,
            self::DPT_HB_HIB_2, self::POLIO_3, self::PCV_2, self::ROTAVIRUS_2 => 3,
            self::DPT_HB_HIB_3, self::POLIO_4, self::IPV_1, self::ROTAVIRUS_3 => 4,
            self::IPV_2, self::CAMPAK_RUBELA => 9,
            self::PCV_3 => 12,
            self::DPT_LANJUTAN, self::CAMPAK_LANJUTAN => 18,
        };
    }
}
