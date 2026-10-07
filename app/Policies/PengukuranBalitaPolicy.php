<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Balita;
use App\Models\PengukuranBalita;
use App\Models\User;

class PengukuranBalitaPolicy
{
    /**
     * Menentukan apakah pengguna dapat melihat daftar pengukuran.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah pengguna dapat melihat data spesifik pengukuran balita.
     */
    public function view(User $user, PengukuranBalita $pengukuran): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        $balita = $pengukuran->balita;
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
     * Menentukan apakah pengguna dapat menginput pengukuran baru untuk balita tertentu.
     */
    public function create(User $user, ?Balita $balita = null): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        if ($user->isBidan() || $user->isKader()) {
            if ($balita === null) {
                return $user->posyandu_id !== null;
            }
            return $user->posyandu_id !== null && $balita->posyandu_id === $user->posyandu_id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat memperbarui data hasil pengukuran balita.
     */
    public function update(User $user, PengukuranBalita $pengukuran): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        $balita = $pengukuran->balita;
        if ($balita === null || $user->posyandu_id === null || $balita->posyandu_id !== $user->posyandu_id) {
            return false;
        }

        // Bidan dapat mengoreksi seluruh pengukuran di wilayah posyandunya
        if ($user->isBidan()) {
            return true;
        }

        // Kader hanya dapat mengoreksi pengukuran yang ia buat sendiri
        if ($user->isKader()) {
            return $pengukuran->kader_id === $user->id;
        }

        return false;
    }

    /**
     * Menentukan apakah pengguna dapat menghapus data pengukuran balita.
     */
    public function delete(User $user, PengukuranBalita $pengukuran): bool
    {
        if ($user->isAdminDesa()) {
            return true;
        }

        $balita = $pengukuran->balita;
        if ($balita === null) {
            return false;
        }

        // Bidan dapat membatalkan pengukuran yang salah di posyandu binaannya
        if ($user->isBidan()) {
            return $user->posyandu_id !== null && $balita->posyandu_id === $user->posyandu_id;
        }

        return false;
    }
}
