<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\PeranPengguna;
use App\Http\Controllers\Controller;
use App\Models\OrangTua;
use App\Models\Posyandu;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran akun baru khusus Orang Tua / Kader.
     */
    public function create(): Response
    {
        $posyandus = Posyandu::orderBy('nama_posyandu')->get(['id', 'nama_posyandu', 'kelurahan_desa']);

        return Inertia::render('Auth/Register', [
            'posyandus' => $posyandus,
        ]);
    }

    /**
     * Simpan pendaftaran akun orang tua dengan data profil keluarga lengkap
     * serta fitur auto-linking jika data balita sudah pernah diinput oleh kader posyandu.
     * Catatan: Akun kader, bidan, dan staf didaftarkan secara terpusat oleh Admin Desa / Super Admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'nomor_telepon' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'posyandu_id' => ['nullable', 'exists:posyandu,id'],
            'nik_ibu' => ['required', 'string', 'digits:16'],
            'no_kk' => ['required', 'string', 'digits:16'],
            'nama_ayah' => ['nullable', 'string', 'max:150'],
            'nik_ayah' => ['nullable', 'string', 'digits:16'],
            'alamat' => ['required', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        $messages = [
            'nama_lengkap.required' => 'Nama lengkap ibu / pemohon wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan gunakan menu Masuk.',
            'nomor_telepon.required' => 'Nomor WhatsApp / telepon wajib diisi untuk notifikasi jadwal posyandu.',
            'nomor_telepon.regex' => 'Format nomor WhatsApp hanya boleh berupa angka.',
            'nik_ibu.required' => 'NIK Ibu (16 digit) wajib diisi untuk verifikasi data balita.',
            'nik_ibu.digits' => 'NIK Ibu harus tepat 16 digit angka.',
            'no_kk.required' => 'Nomor Kartu Keluarga (KK) wajib diisi.',
            'no_kk.digits' => 'Nomor KK harus tepat 16 digit angka.',
            'nik_ayah.digits' => 'NIK Ayah harus tepat 16 digit angka jika diisi.',
            'alamat.required' => 'Alamat domisili lengkap wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ];

        $validated = $request->validate($rules, $messages);

        // Periksa apakah NIK Ibu sudah memiliki akun yang terhubung
        $existingOrangTua = OrangTua::where('nik_ibu', $validated['nik_ibu'])->first();
        if ($existingOrangTua && $existingOrangTua->user_id !== null) {
            throw ValidationException::withMessages([
                'nik_ibu' => 'NIK Ibu ini sudah terhubung dengan akun Posyandu Digital. Silakan masuk menggunakan email Anda atau hubungi kader posyandu.',
            ]);
        }

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'posyandu_id' => $validated['posyandu_id'] ?? null,
                'nama_lengkap' => $validated['nama_lengkap'],
                'email' => $validated['email'],
                'nomor_telepon' => $validated['nomor_telepon'],
                'password' => Hash::make($validated['password']),
                'peran' => PeranPengguna::ORANG_TUA,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $existingOrangTua = OrangTua::where('nik_ibu', $validated['nik_ibu'])->first();

                if ($existingOrangTua) {
                    // Sinkronisasi data yang sudah dibuat oleh kader sebelumnya
                    $existingOrangTua->update([
                        'user_id' => $user->id,
                        'nama_ibu' => $validated['nama_lengkap'],
                        'no_kk' => $validated['no_kk'],
                        'nama_ayah' => $validated['nama_ayah'] ?? $existingOrangTua->nama_ayah,
                        'nik_ayah' => $validated['nik_ayah'] ?? $existingOrangTua->nik_ayah,
                        'alamat' => $validated['alamat'],
                    ]);
                } else {
                    // Buat profil orang tua baru
                    OrangTua::create([
                        'user_id' => $user->id,
                        'no_kk' => $validated['no_kk'],
                        'nik_ibu' => $validated['nik_ibu'],
                        'nama_ibu' => $validated['nama_lengkap'],
                        'nama_ayah' => $validated['nama_ayah'] ?? null,
                        'nik_ayah' => $validated['nik_ayah'] ?? null,
                        'alamat' => $validated['alamat'],
                    ]);
                }

                return $user;
            });

        event(new Registered($user));

        Auth::login($user);

        if ($user->peran === PeranPengguna::ORANG_TUA) {
            return redirect()->route('orang-tua.dashboard');
        }

        return redirect()->route('dashboard');
    }
}

