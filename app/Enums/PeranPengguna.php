<?php

declare(strict_types=1);

namespace App\Enums;

enum PeranPengguna: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN_DESA = 'admin_desa';
    case BIDAN = 'bidan';
    case KADER = 'kader';
    case ORANG_TUA = 'orang_tua';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN_DESA => 'Admin Desa',
            self::BIDAN => 'Bidan Desa',
            self::KADER => 'Kader Posyandu',
            self::ORANG_TUA => 'Orang Tua / Wali',
        };
    }
}
