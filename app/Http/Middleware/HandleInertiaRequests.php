<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PeranPengguna;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'nama_lengkap' => $user->nama_lengkap,
                    'email' => $user->email,
                    'nomor_telepon' => $user->nomor_telepon,
                    'peran' => [
                        'value' => $user->peran instanceof PeranPengguna ? $user->peran->value : $user->peran,
                        'label' => $user->peran instanceof PeranPengguna ? $user->peran->label() : (string) $user->peran,
                    ],
                    'posyandu_id' => $user->posyandu_id,
                    'posyandu' => $user->posyandu ? [
                        'id' => $user->posyandu->id,
                        'nama_posyandu' => $user->posyandu->nama_posyandu,
                        'kelurahan_desa' => $user->posyandu->kelurahan_desa,
                        'puskesmas_pembina' => $user->posyandu->puskesmas_pembina,
                    ] : null,
                    'is_active' => (bool) $user->is_active,
                ] : null,
                'peran' => $user ? ($user->peran instanceof PeranPengguna ? $user->peran->value : (string) $user->peran) : null,
                'permissions' => $user ? [
                    'can_manage_users' => $user->isAdminDesa(),
                    'can_manage_posyandu' => $user->isAdminDesa(),
                    'can_create_balita' => $user->isAdminDesa() || $user->isBidan() || $user->isKader(),
                    'can_create_pengukuran' => $user->isAdminDesa() || $user->isBidan() || $user->isKader(),
                    'can_manage_imunisasi' => $user->isAdminDesa() || $user->isBidan(),
                    'is_super_admin' => $user->isSuperAdmin(),
                    'is_kader' => $user->isKader(),
                    'is_bidan' => $user->isBidan(),
                    'is_admin_desa' => $user->isAdminDesa(),
                    'is_orang_tua' => $user->isOrangTua(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
