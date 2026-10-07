<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Balita;
use App\Models\ImunisasiBalita;
use App\Models\User;

class ImunisasiBalitaPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar imunisasi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat catatan imunisasi tertentu.
     */
    public function view(User $user, ImunisasiBalita $imunisasi): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        $balita = $imunisasi->balita;
        if ($balita === null) {
            return false;
        }

        if ($user->isBidan() || $user->isKader()) {
            return $user->posyandu_id !== null && $balita->posyandu_id === $user->posyandu_id;
        }

        if ($user->isOrangTua()) {
            return $user->orangTua !== null && $balita->orang_tua_id === $user->orangTua->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat mencatat imunisasi baru (Hanya Bidan & Admin Desa).
     */
    public function create(User $user, ?Balita $balita = null): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        // Bidan mencatat imunisasi untuk balita di posyandu binaannya
        if ($user->isBidan()) {
            if ($balita === null) {
                return $user->posyandu_id !== null;
            }
            return $user->posyandu_id !== null && $balita->posyandu_id === $user->posyandu_id;
        }

        // Kader dan Orang Tua tidak berwenang mencatat imunisasi medis
        return false;
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui catatan imunisasi balita.
     */
    public function update(User $user, ImunisasiBalita $imunisasi): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        $balita = $imunisasi->balita;
        if ($balita === null) {
            return false;
        }

        if ($user->isBidan()) {
            return $user->posyandu_id !== null && $balita->posyandu_id === $user->posyandu_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus catatan imunisasi balita.
     */
    public function delete(User $user, ImunisasiBalita $imunisasi): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        $balita = $imunisasi->balita;
        if ($balita === null) {
            return false;
        }

        if ($user->isBidan()) {
            return $user->posyandu_id !== null && $balita->posyandu_id === $user->posyandu_id;
        }

        return false;
    }
}
