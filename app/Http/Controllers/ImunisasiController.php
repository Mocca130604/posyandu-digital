<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\JenisVaksin;
use App\Http\Requests\StoreImunisasiRequest;
use App\Models\Balita;
use App\Models\ImunisasiBalita;
use App\Models\Posyandu;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImunisasiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Tampilkan daftar rekam imunisasi dan deteksi vaksin tertunda.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = ImunisasiBalita::query()->with(['balita.posyandu', 'balita.orangTua']);

        // Scope posyandu berdasarkan wewenang pengguna
        if (!$user->isAdminDesa()) {
            $query->whereHas('balita', function ($q) use ($user): void {
                $q->where('posyandu_id', $user->posyandu_id);
            });
        } elseif ($request->filled('posyandu_id')) {
            $query->whereHas('balita', function ($q) use ($request): void {
                $q->where('posyandu_id', (int) $request->input('posyandu_id'));
            });
        }

        // Pencarian berdasarkan nama balita atau NIK
        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));
            $query->whereHas('balita', function ($q) use ($keyword): void {
                $q->where('nama_anak', 'like', "%{$keyword}%")
                    ->orWhere('nik_anak', 'like', "%{$keyword}%");
            });
        }

        // Filter jenis vaksin
        if ($request->filled('jenis_vaksin')) {
            $query->where('jenis_vaksin', $request->input('jenis_vaksin'));
        }

        $imunisasiList = $query->orderBy('tanggal_imunisasi', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => $item->id,
                'balita_id' => $item->balita_id,
                'nama_anak' => $item->balita?->nama_anak ?? '-',
                'nik_anak' => $item->balita?->nik_anak,
                'jenis_kelamin' => $item->balita?->jenis_kelamin?->value ?? 'L',
                'posyandu' => $item->balita?->posyandu?->nama_posyandu ?? '-',
                'nama_ibu' => $item->balita?->orangTua?->nama_ibu ?? '-',
                'jenis_vaksin' => $item->jenis_vaksin->value,
                'jenis_vaksin_label' => $item->jenis_vaksin->label(),
                'tanggal_imunisasi' => $item->tanggal_imunisasi->toDateString(),
                'petugas_kesehatan' => $item->petugas_kesehatan,
                'batch_vaksin' => $item->batch_vaksin,
            ]);

        // Daftar semua enum vaksin untuk referensi & filter
        $daftarVaksin = collect(JenisVaksin::cases())->map(fn ($v) => [
            'kode' => $v->value,
            'nama' => $v->label(),
            'usia_target_bulan' => $v->usiaPemberianBulan(),
        ])->values();

        // Ringkasan metrik bulan ini
        $bulanIni = Carbon::now()->startOfMonth()->toDateString();
        $imunisasiBulanIniQuery = ImunisasiBalita::where('tanggal_imunisasi', '>=', $bulanIni);
        if (!$user->isAdminDesa()) {
            $imunisasiBulanIniQuery->whereHas('balita', fn ($q) => $q->where('posyandu_id', $user->posyandu_id));
        } elseif ($request->filled('posyandu_id')) {
            $imunisasiBulanIniQuery->whereHas('balita', fn ($q) => $q->where('posyandu_id', (int) $request->input('posyandu_id')));
        }
        $totalBulanIni = $imunisasiBulanIniQuery->count();

        // Deteksi balita dengan vaksin tertunda untuk pelacakan / sweeping
        $balitaScope = Balita::query();
        if (!$user->isAdminDesa()) {
            $balitaScope->where('posyandu_id', $user->posyandu_id);
        } elseif ($request->filled('posyandu_id')) {
            $balitaScope->where('posyandu_id', (int) $request->input('posyandu_id'));
        }
        $balitaList = $balitaScope->with(['imunisasi', 'orangTua', 'posyandu'])->get();

        $vaksinTertunda = [];
        foreach ($balitaList as $b) {
            $usiaBulan = $b->usia_bulan;
            $diberikan = $b->imunisasi->pluck('jenis_vaksin')->map(fn ($v) => $v->value)->all();

            foreach (JenisVaksin::cases() as $vaksinCase) {
                $target = $vaksinCase->usiaPemberianBulan();
                if ($usiaBulan >= $target && !in_array($vaksinCase->value, $diberikan, true)) {
                    $vaksinTertunda[] = [
                        'balita_id' => $b->id,
                        'nama_anak' => $b->nama_anak,
                        'nik_anak' => $b->nik_anak,
                        'posyandu' => $b->posyandu?->nama_posyandu ?? '-',
                        'nama_ibu' => $b->orangTua?->nama_ibu ?? '-',
                        'usia_bulan' => $usiaBulan,
                        'vaksin_kode' => $vaksinCase->value,
                        'vaksin_nama' => $vaksinCase->label(),
                        'usia_target' => $target,
                        'keterlambatan_bulan' => max(0, $usiaBulan - $target),
                    ];
                }
            }
        }

        usort($vaksinTertunda, fn ($a, $b) => $b['keterlambatan_bulan'] <=> $a['keterlambatan_bulan']);

        return Inertia::render('Imunisasi/Index', [
            'imunisasi' => $imunisasiList,
            'daftarVaksin' => $daftarVaksin,
            'daftarPosyandu' => $user->isAdminDesa() ? Posyandu::all(['id', 'nama_posyandu']) : [],
            'vaksinTertunda' => array_slice($vaksinTertunda, 0, 25),
            'totalTertunda' => count($vaksinTertunda),
            'totalBulanIni' => $totalBulanIni,
            'filters' => [
                'q' => $request->input('q', ''),
                'jenis_vaksin' => $request->input('jenis_vaksin', ''),
                'posyandu_id' => $request->input('posyandu_id', ''),
            ],
        ]);
    }

    /**
     * Catat pemberian imunisasi baru untuk balita (Khusus Bidan Desa & Admin).
     */
    public function store(StoreImunisasiRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $balita = Balita::findOrFail($validated['balita_id']);

        $imunisasi = ImunisasiBalita::create([
            'balita_id' => $balita->id,
            'jenis_vaksin' => $validated['jenis_vaksin'],
            'tanggal_imunisasi' => $validated['tanggal_imunisasi'],
            'petugas_kesehatan' => $validated['petugas_kesehatan'],
            'batch_vaksin' => $validated['batch_vaksin'] ?? null,
        ]);

        return redirect()->back()
            ->with('success', "Catatan imunisasi {$imunisasi->jenis_vaksin->label()} untuk {$balita->nama_anak} berhasil disimpan.");
    }

    /**
     * Hapus catatan imunisasi balita jika terjadi kekeliruan pencatatan.
     */
    public function destroy(ImunisasiBalita $imunisasi, Request $request): RedirectResponse
    {
        $this->authorize('delete', $imunisasi);

        $namaVaksin = $imunisasi->jenis_vaksin->label();
        $imunisasi->delete();

        return redirect()->back()
            ->with('success', "Catatan imunisasi {$namaVaksin} berhasil dihapus.");
    }
}
