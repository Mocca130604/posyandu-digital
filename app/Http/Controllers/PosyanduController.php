<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Posyandu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosyanduController extends Controller
{
    /**
     * Tampilkan daftar wilayah Posyandu beserta statistik dan pencarian.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $query = Posyandu::query()
            ->withCount(['balita', 'kader', 'bidan'])
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('nama_posyandu', 'like', "%{$search}%")
                    ->orWhere('rw', 'like', "%{$search}%")
                    ->orWhere('rt', 'like', "%{$search}%")
                    ->orWhere('kelurahan_desa', 'like', "%{$search}%")
                    ->orWhere('kecamatan', 'like', "%{$search}%")
                    ->orWhere('puskesmas_pembina', 'like', "%{$search}%");
            });
        }

        $posyandus = $query->get();

        $stats = [
            'total_posyandu' => Posyandu::count(),
            'total_balita' => \App\Models\Balita::count(),
            'total_kader' => \App\Models\User::where('peran', \App\Enums\PeranPengguna::KADER)->count(),
        ];

        return Inertia::render('Admin/Posyandu/Index', [
            'posyandus' => $posyandus,
            'stats' => $stats,
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    /**
     * Simpan data Posyandu baru ke database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_posyandu' => ['required', 'string', 'max:100'],
            'rt' => ['required', 'string', 'max:5'],
            'rw' => ['required', 'string', 'max:5'],
            'kelurahan_desa' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'puskesmas_pembina' => ['required', 'string', 'max:150'],
        ], [
            'nama_posyandu.required' => 'Nama Posyandu wajib diisi.',
            'rt.required' => 'Nomor RT wajib diisi.',
            'rw.required' => 'Nomor RW wajib diisi.',
            'kelurahan_desa.required' => 'Kelurahan / Desa wajib diisi.',
            'kecamatan.required' => 'Kecamatan wajib diisi.',
            'puskesmas_pembina.required' => 'Puskesmas Pembina wajib diisi.',
        ]);

        $posyandu = Posyandu::create($validated);

        return redirect()->back()->with('success', "Posyandu {$posyandu->nama_posyandu} berhasil ditambahkan ke sistem.");
    }

    /**
     * Perbarui data Posyandu yang sudah ada.
     */
    public function update(Request $request, Posyandu $posyandu): RedirectResponse
    {
        $validated = $request->validate([
            'nama_posyandu' => ['required', 'string', 'max:100'],
            'rt' => ['required', 'string', 'max:5'],
            'rw' => ['required', 'string', 'max:5'],
            'kelurahan_desa' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'puskesmas_pembina' => ['required', 'string', 'max:150'],
        ], [
            'nama_posyandu.required' => 'Nama Posyandu wajib diisi.',
            'rt.required' => 'Nomor RT wajib diisi.',
            'rw.required' => 'Nomor RW wajib diisi.',
            'kelurahan_desa.required' => 'Kelurahan / Desa wajib diisi.',
            'kecamatan.required' => 'Kecamatan wajib diisi.',
            'puskesmas_pembina.required' => 'Puskesmas Pembina wajib diisi.',
        ]);

        $posyandu->update($validated);

        return redirect()->back()->with('success', "Data {$posyandu->nama_posyandu} berhasil diperbarui.");
    }

    /**
     * Hapus data Posyandu (Soft Delete).
     */
    public function destroy(Posyandu $posyandu): RedirectResponse
    {
        $nama = $posyandu->nama_posyandu;
        $totalBalita = $posyandu->balita()->count();

        if ($totalBalita > 0) {
            return redirect()->back()->with('warning', "Peringatan: {$nama} memiliki {$totalBalita} data balita terdaftar. Posyandu tetap dinonaktifkan secara aman.");
        }

        $posyandu->delete();

        return redirect()->back()->with('success', "Posyandu {$nama} berhasil dihapus dari sistem.");
    }
}
