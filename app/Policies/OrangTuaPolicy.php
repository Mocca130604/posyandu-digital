<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrangTua;
use App\Models\User;

class OrangTuaPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar orang tua.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdminDesa() || $user->isBidan() || $user->isKader();
    }

    /**
     * Menentukan apakah pengguna dapat melihat rincian data orang tua tertentu.
     */
    public function view(User $user, OrangTua $orangTua): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        if ($user->isBidan() || $user->isKader()) {
            // Kader & Bidan hanya boleh melihat jika anak terdaftar di posyandu mereka
            return $orangTua->balita()->where('posyandu_id', $user->posyandu_id)->exists();
        }

        if ($user->isOrangTua()) {
            return $orangTua->user_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat mendaftarkan profil orang tua baru.
     */
    public function create(User $user): bool
    {
        return $user->isAdminDesa() || $user->isBidan() || $user->isKader();
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui data orang tua.
     */
    public function update(User $user, OrangTua $orangTua): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        if ($user->isBidan() || $user->isKader()) {
            return $orangTua->balita()->where('posyandu_id', $user->posyandu_id)->exists();
        }

        if ($user->isOrangTua()) {
            return $orangTua->user_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data orang tua.
     */
    public function delete(User $user, OrangTua $orangTua): bool
    {
        return $user->isAdminDesa();
    }
}
