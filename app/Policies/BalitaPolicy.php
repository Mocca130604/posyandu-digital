<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Balita;
use App\Models\User;

class BalitaPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar balita.
     */
    public function viewAny(User $user): bool
    {
        return true; // Query akan discoping berdasarkan peran di Controller
    }

    /**
     * Menentukan apakah pengguna dapat melihat rincian balita tertentu.
     */
    public function view(User $user, Balita $balita): bool
    {
        if ($user->isAdminDesa()) {
            return true;
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
     * Menentukan apakah pengguna dapat mendaftarkan data balita baru.
     */
    public function create(User $user): bool
    {
        return $user->isAdminDesa() || $user->isBidan() || $user->isKader();
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui data balita.
     */
    public function update(User $user, Balita $balita): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        if ($user->isBidan() || $user->isKader()) {
            return $user->posyandu_id !== null && $balita->posyandu_id === $user->posyandu_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data balita.
     */
    public function delete(User $user, Balita $balita): bool
    {
        return $user->isAdminDesa();
    }
}
