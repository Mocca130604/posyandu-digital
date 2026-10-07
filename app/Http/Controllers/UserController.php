<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PeranPengguna;
use App\Models\OrangTua;
use App\Models\Posyandu;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh akun pengguna dengan filter & pencarian.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $peran = $request->query('peran');
        $posyanduId = $request->query('posyandu_id');

        $usersQuery = User::query()
            ->with(['posyandu:id,nama_posyandu,kelurahan_desa', 'orangTua:id,user_id,nama_ibu,nama_ayah,nik_ibu'])
            ->latest('id');

        if ($search) {
            $usersQuery->where(function ($q) use ($search): void {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nomor_telepon', 'like', "%{$search}%");
            });
        }

        if ($peran && $peran !== 'all') {
            $usersQuery->where('peran', $peran);
        }

        if ($posyanduId && $posyanduId !== 'all') {
            $usersQuery->where('posyandu_id', $posyanduId);
        }

        $users = $usersQuery->get();
        $posyandus = Posyandu::orderBy('nama_posyandu')->get(['id', 'nama_posyandu', 'kelurahan_desa']);

        $stats = [
            'total' => User::count(),
            'super_admin' => User::where('peran', PeranPengguna::SUPER_ADMIN)->count(),
            'admin_desa' => User::where('peran', PeranPengguna::ADMIN_DESA)->count(),
            'bidan' => User::where('peran', PeranPengguna::BIDAN)->count(),
            'kader' => User::where('peran', PeranPengguna::KADER)->count(),
            'orang_tua' => User::where('peran', PeranPengguna::ORANG_TUA)->count(),
        ];

        return Inertia::render('Admin/Pengguna/Index', [
            'users' => $users,
            'posyandus' => $posyandus,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'peran' => $peran ?? 'all',
                'posyandu_id' => $posyanduId ?? 'all',
            ],
        ]);
    }

    /**
     * Simpan akun pengguna baru oleh administrator.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'nomor_telepon' => ['required', 'string', 'min:9', 'max:20'],
            'peran' => ['required', 'string', Rule::enum(PeranPengguna::class)],
            'posyandu_id' => ['nullable', 'exists:posyandu,id'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'nomor_telepon.required' => 'Nomor kontak telepon/WhatsApp wajib diisi.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
        ]);

        $user = User::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'nomor_telepon' => $validated['nomor_telepon'],
            'peran' => $validated['peran'],
            'posyandu_id' => $validated['posyandu_id'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        if ($user->peran === PeranPengguna::ORANG_TUA) {
            OrangTua::create([
                'user_id' => $user->id,
                'no_kk' => '0000000000000000',
                'nik_ibu' => '000000' . str_pad((string) $user->id, 10, '0', STR_PAD_LEFT),
                'nama_ibu' => $user->nama_lengkap,
                'alamat' => 'Alamat belum diatur',
            ]);
        }

        return redirect()->back()->with('success', "Akun {$user->nama_lengkap} ({$user->peran->label()}) berhasil dibuat.");
    }

    /**
     * Perbarui data akun pengguna (ganti nomor Kader, nomor Ibu, peran, posyandu, status, atau password).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nomor_telepon' => ['required', 'string', 'min:9', 'max:20'],
            'peran' => ['required', 'string', Rule::enum(PeranPengguna::class)],
            'posyandu_id' => ['nullable', 'exists:posyandu,id'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'nomor_telepon.required' => 'Nomor kontak telepon/WhatsApp wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
        ]);

        $updateData = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'nomor_telepon' => $validated['nomor_telepon'],
            'peran' => $validated['peran'],
            'posyandu_id' => $validated['posyandu_id'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Jika akun memiliki profil orang tua, sinkronkan nama ibu
        if ($user->orangTua) {
            $user->orangTua->update([
                'nama_ibu' => $user->nama_lengkap,
            ]);
        }

        return redirect()->back()->with('success', "Data akun {$user->nama_lengkap} (Nomor HP: {$user->nomor_telepon}) berhasil diperbarui.");
    }

    /**
     * Hapus akun pengguna dari sistem.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang masuk.');
        }

        $nama = $user->nama_lengkap;
        $user->delete();

        return redirect()->back()->with('success', "Akun {$nama} berhasil dihapus dari sistem.");
    }
}
