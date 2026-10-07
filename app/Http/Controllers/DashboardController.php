<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\JenisVaksin;
use App\Enums\KenaikanBb;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use App\Models\Balita;
use App\Models\ImunisasiBalita;
use App\Models\PengukuranBalita;
use App\Models\Posyandu;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Arahkan ke dashboard spesifik berdasarkan peran pengguna.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->isAdminDesa()) {
            return $this->dashboardAdminDesa($request);
        }

        if ($user->isBidan() || $user->isKader()) {
            return $this->dashboardKaderBidan($request);
        }

        return $this->dashboardOrangTua($request);
    }

    /**
     * Dashboard Administrator Desa: Cakupan penimbangan & statistik zona merah per Posyandu se-desa.
     */
    private function dashboardAdminDesa(Request $request): Response
    {
        $bulanSekarang = Carbon::now()->startOfMonth();

        $daftarPosyandu = Posyandu::query()
            ->withCount([
                'balita as total_balita',
                'balita as total_zona_merah' => function ($q): void {
                    $q->zonaMerah();
                },
                'balita as total_stunting' => function ($q): void {
                    $q->stunting();
                },
                'balita as total_gizi_buruk_kurang' => function ($q): void {
                    $q->giziKurangBuruk();
                },
                'balita as ditimbang_bulan_ini' => function ($q) use ($bulanSekarang): void {
                    $q->whereHas('pengukuran', function ($sub) use ($bulanSekarang): void {
                        $sub->where('tanggal_kunjungan', '>=', $bulanSekarang->toDateString());
                    });
                },
            ])
            ->get()
            ->map(function ($p) {
                $cakupanPersen = $p->total_balita > 0
                    ? round(($p->ditimbang_bulan_ini / $p->total_balita) * 100, 1)
                    : 0;
                $prevalensiStunting = $p->total_balita > 0
                    ? round(($p->total_stunting / $p->total_balita) * 100, 1)
                    : 0;

                return [
                    'id' => $p->id,
                    'nama_posyandu' => $p->nama_posyandu,
                    'rw' => $p->rw,
                    'rt' => $p->rt,
                    'kelurahan_desa' => $p->kelurahan_desa,
                    'total_balita' => $p->total_balita,
                    'total_zona_merah' => $p->total_zona_merah,
                    'total_stunting' => $p->total_stunting,
                    'total_gizi_buruk_kurang' => $p->total_gizi_buruk_kurang,
                    'ditimbang_bulan_ini' => $p->ditimbang_bulan_ini,
                    'cakupan_persen' => $cakupanPersen,
                    'prevalensi_stunting' => $prevalensiStunting,
                ];
            });

        $totalBalitaDesa = Balita::count();
        $totalZonaMerahDesa = Balita::zonaMerah()->count();
        $totalStuntingDesa = Balita::stunting()->count();
        $totalDitimbangBulanIni = PengukuranBalita::where('tanggal_kunjungan', '>=', $bulanSekarang->toDateString())
            ->distinct('balita_id')
            ->count('balita_id');

        $cakupanDesaPersen = $totalBalitaDesa > 0
            ? round(($totalDitimbangBulanIni / $totalBalitaDesa) * 100, 1)
            : 0;

        return Inertia::render('Dashboard/AdminDesa', [
            'summary' => [
                'total_posyandu' => $daftarPosyandu->count(),
                'total_balita' => $totalBalitaDesa,
                'total_zona_merah' => $totalZonaMerahDesa,
                'total_stunting' => $totalStuntingDesa,
                'total_ditimbang_bulan_ini' => $totalDitimbangBulanIni,
                'cakupan_desa_persen' => $cakupanDesaPersen,
            ],
            'posyanduStats' => $daftarPosyandu,
        ]);
    }

    /**
     * Dashboard Kader & Bidan: Anak butuh tindak lanjut (zona merah) & vaksin tertunda.
     */
    private function dashboardKaderBidan(Request $request): Response
    {
        $user = $request->user();
        $posyanduId = $user->posyandu_id;
        $bulanSekarang = Carbon::now()->startOfMonth();

        // 1. Anak yang butuh tindak lanjut (Zona Merah di Posyandu ini)
        $anakZonaMerah = Balita::query()
            ->diPosyandu($posyanduId)
            ->aktif()
            ->zonaMerah()
            ->with(['orangTua', 'pengukuranTerbaru'])
            ->take(15)
            ->get();

        // 2. Anak yang belum ditimbang pada bulan berjalan (khusus balita domisili aktif)
        $anakBelumDitimbang = Balita::query()
            ->diPosyandu($posyanduId)
            ->aktif()
            ->whereDoesntHave('pengukuran', function ($q) use ($bulanSekarang): void {
                $q->where('tanggal_kunjungan', '>=', $bulanSekarang->toDateString());
            })
            ->with('orangTua')
            ->orderBy('nama_anak', 'asc')
            ->take(15)
            ->get();

        // 3. Deteksi Vaksin Tertunda (Anak usia >= target vaksin, tetapi belum menerima imunisasi tersebut)
        $balitaDiPosyandu = Balita::query()
            ->diPosyandu($posyanduId)
            ->aktif()
            ->with('imunisasi')
            ->get();

        $vaksinTertunda = [];
        foreach ($balitaDiPosyandu as $balita) {
            $usiaBulan = $balita->usia_bulan;
            $sudahDiberikan = $balita->imunisasi->pluck('jenis_vaksin')->map(fn ($v) => $v->value)->all();

            foreach (JenisVaksin::cases() as $vaksinCase) {
                $targetUsia = $vaksinCase->usiaPemberianBulan();
                // Jika usia balita sudah melewati target bulan tetapi vaksin belum tercatat
                if ($usiaBulan >= $targetUsia && !in_array($vaksinCase->value, $sudahDiberikan, true)) {
                    $vaksinTertunda[] = [
                        'balita_id' => $balita->id,
                        'nama_anak' => $balita->nama_anak,
                        'usia_bulan' => $usiaBulan,
                        'jenis_kelamin' => $balita->jenis_kelamin->value,
                        'nama_ibu' => $balita->orangTua?->nama_ibu ?? '-',
                        'vaksin_kode' => $vaksinCase->value,
                        'vaksin_nama' => $vaksinCase->label(),
                        'usia_target' => $targetUsia,
                        'keterlambatan_bulan' => max(0, $usiaBulan - $targetUsia),
                    ];
                }
            }
        }

        // Urutkan vaksin tertunda berdasarkan keterlambatan terbesar
        usort($vaksinTertunda, fn ($a, $b) => $b['keterlambatan_bulan'] <=> $a['keterlambatan_bulan']);
        $vaksinTertunda = array_slice($vaksinTertunda, 0, 15);

        // Ringkasan metrik posyandu
        $totalBalita = Balita::diPosyandu($posyanduId)->aktif()->count();
        $totalDitimbang = PengukuranBalita::whereHas('balita', function ($q) use ($posyanduId): void {
            $q->where('posyandu_id', $posyanduId);
        })->where('tanggal_kunjungan', '>=', $bulanSekarang->toDateString())
        ->distinct('balita_id')
        ->count('balita_id');

        return Inertia::render('Dashboard/KaderBidan', [
            'posyandu' => $user->posyandu,
            'summary' => [
                'total_balita' => $totalBalita,
                'total_zona_merah' => $anakZonaMerah->count(),
                'total_ditimbang_bulan_ini' => $totalDitimbang,
                'total_belum_ditimbang' => max(0, $totalBalita - $totalDitimbang),
                'total_vaksin_tertunda' => count($vaksinTertunda),
            ],
            'anakZonaMerah' => $anakZonaMerah,
            'anakBelumDitimbang' => $anakBelumDitimbang,
            'vaksinTertunda' => $vaksinTertunda,
        ]);
    }

    /**
     * Portal Orang Tua: Tampilan ramah keluarga untuk memantau rekam tumbuh kembang anak-anaknya.
     */
    private function dashboardOrangTua(Request $request): Response
    {
        $user = $request->user();
        $profilOrangTua = $user->orangTua;

        $daftarAnak = $profilOrangTua
            ? $profilOrangTua->balita()
                ->with(['pengukuranTerbaru', 'posyandu', 'imunisasi'])
                ->get()
                ->map(function ($anak) {
                    $vaksinDiterima = $anak->imunisasi->pluck('jenis_vaksin')->map(fn ($v) => $v->value)->all();
                    $usiaBulan = $anak->usia_bulan;

                    $vaksinBerikutnya = null;
                    foreach (JenisVaksin::cases() as $vaksinCase) {
                        if (!in_array($vaksinCase->value, $vaksinDiterima, true)) {
                            $vaksinBerikutnya = [
                                'nama' => $vaksinCase->label(),
                                'usia_target' => $vaksinCase->usiaPemberianBulan(),
                                'status' => $usiaBulan >= $vaksinCase->usiaPemberianBulan() ? 'Perlu Segera Diberikan' : 'Jadwal Mendatang',
                            ];
                            break;
                        }
                    }

                    return [
                        'id' => $anak->id,
                        'nama_anak' => $anak->nama_anak,
                        'jenis_kelamin' => $anak->jenis_kelamin->value,
                        'tanggal_lahir' => $anak->tanggal_lahir,
                        'usia_bulan' => $usiaBulan,
                        'posyandu' => $anak->posyandu?->nama_posyandu,
                        'status_gizi_terakhir' => $anak->status_gizi_terakhir?->value,
                        'status_stunting_terakhir' => $anak->status_stunting_terakhir?->value,
                        'status_bbtb_terakhir' => $anak->status_bbtb_terakhir?->value,
                        'pengukuran_terakhir' => $anak->pengukuranTerbaru,
                        'vaksin_berikutnya' => $vaksinBerikutnya,
                        'is_zona_merah' => $anak->is_zona_merah,
                    ];
                })
            : collect();

        return Inertia::render('PortalOrangTua/Dashboard', [
            'profilOrangTua' => $profilOrangTua,
            'daftarAnak' => $daftarAnak,
            'posyanduList' => Posyandu::orderBy('nama_posyandu')->get(['id', 'nama_posyandu', 'kelurahan_desa']),
        ]);
    }
}
