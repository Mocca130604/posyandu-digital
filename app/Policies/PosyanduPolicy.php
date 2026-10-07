<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Posyandu;
use App\Models\User;

class PosyanduPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar posyandu.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdminDesa() || $user->isBidan() || $user->isKader();
    }

    /**
     * Menentukan apakah pengguna dapat melihat data rinci posyandu tertentu.
     */
    public function view(User $user, Posyandu $posyandu): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        if ($user->isBidan() || $user->isKader()) {
            return $user->posyandu_id === $posyandu->id;
        }

        if ($user->isOrangTua()) {
            return $user->orangTua !== null &&
                $user->orangTua->balita()->where('posyandu_id', $posyandu->id)->exists();
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat membuat posyandu baru.
     */
    public function create(User $user): bool
    {
        return $user->isAdminDesa();
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui data posyandu.
     */
    public function update(User $user, Posyandu $posyandu): bool
    {
        return $user->isAdminDesa();
    }

    /**
     * Menentukan apakah pengguna dapat menghapus posyandu.
     */
    public function delete(User $user, Posyandu $posyandu): bool
    {
        return $user->isAdminDesa();
    }
}
