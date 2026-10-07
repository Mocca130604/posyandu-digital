<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTransferObjects\HasilAntropometri;
use App\Enums\CaraUkurTinggi;
use App\Http\Requests\PreviewPengukuranRequest;
use App\Http\Requests\StorePengukuranRequest;
use App\Models\Balita;
use App\Models\PengukuranBalita;
use App\Services\AntropometriService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PengukuranController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected AntropometriService $antropometriService
    ) {}

    /**
     * Tampilkan formulir pencatatan pengukuran balita.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', PengukuranBalita::class);

        $user = $request->user();
        $balitaId = $request->integer('balita_id');

        $balitaQuery = Balita::query()->with('orangTua');

        if ($user->isOrangTua()) {
            $balitaQuery->where('orang_tua_id', $user->orangTua?->id ?? 0);
        } elseif (!$user->isAdminDesa()) {
            $balitaQuery->where('posyandu_id', $user->posyandu_id);
        }

        $balitaList = $balitaQuery->orderBy('nama_anak', 'asc')
            ->get(['id', 'nama_anak', 'nik_anak', 'tanggal_lahir', 'jenis_kelamin', 'orang_tua_id']);

        $balitaTerpilih = null;
        $pengukuranTerakhir = null;

        if ($balitaId > 0) {
            $balitaTerpilih = Balita::query()
                ->with(['orangTua', 'pengukuranTerbaru'])
                ->find($balitaId);

            if ($balitaTerpilih !== null) {
                $this->authorize('view', $balitaTerpilih);
                $pengukuranTerakhir = $balitaTerpilih->pengukuranTerbaru;
            }
        }

        return Inertia::render('Pengukuran/Create', [
            'balitaList' => $balitaList,
            'balitaTerpilih' => $balitaTerpilih,
            'pengukuranTerakhir' => $pengukuranTerakhir,
            'caraUkurOptions' => array_map(fn (CaraUkurTinggi $c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ], CaraUkurTinggi::cases()),
            'defaultTanggalKunjungan' => Carbon::now()->toDateString(),
        ]);
    }

    /**
     * Endpoint API JSON untuk pratinjau kalkulasi Z-Score real-time tanpa menyimpan ke database.
     */
    public function preview(PreviewPengukuranRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $balita = Balita::findOrFail($validated['balita_id']);

        $hasil = $this->antropometriService->analisis($balita, $validated);

        return response()->json([
            'success' => true,
            'data' => $hasil->toArray(),
        ]);
    }

    /**
     * Simpan hasil pengukuran balita dengan transaksi database, lock concurrency,
     * kalkulasi Z-score, dan pembaruan kolom denormalisasi balita.
     */
    public function store(StorePengukuranRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        /** @var array{pengukuran: PengukuranBalita, hasil: HasilAntropometri, balita: Balita} $result */
        $result = DB::transaction(function () use ($validated, $user): array {
            // Lock baris balita untuk mencegah race-condition pengukuran ganda
            /** @var Balita $balita */
            $balita = Balita::where('id', $validated['balita_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Jalankan analisis antropometri lengkap
            $hasil = $this->antropometriService->analisis($balita, $validated);

            // Simpan catatan pengukuran
            $pengukuran = PengukuranBalita::create([
                'balita_id' => $balita->id,
                'kader_id' => $user->id,
                'tanggal_kunjungan' => $validated['tanggal_kunjungan'],
                'usia_bulan' => $hasil->usiaBulan,
                'berat_badan_kg' => $validated['berat_badan_kg'],
                'tinggi_badan_cm' => $hasil->tinggiBadanKoreksiCm,
                'lingkar_lengan_atas_cm' => $validated['lingkar_lengan_atas_cm'] ?? null,
                'lingkar_kepala_cm' => $validated['lingkar_kepala_cm'] ?? null,
                'cara_ukur_tinggi' => $validated['cara_ukur_tinggi'],
                'zscore_bbu' => $hasil->zscoreBbu,
                'zscore_tbu' => $hasil->zscoreTbu,
                'zscore_bbtb' => $hasil->zscoreBbtb,
                'status_gizi' => $hasil->statusGizi->value,
                'status_stunting' => $hasil->statusStunting->value,
                'status_bbtb' => $hasil->statusBbtb->value,
                'flag_data_tidak_wajar' => $hasil->flagDataTidakWajar,
                'kenaikan_bb' => $hasil->kenaikanBb->value,
                'vit_a_diberikan' => (bool) ($validated['vit_a_diberikan'] ?? false),
                'obat_cacing_diberikan' => (bool) ($validated['obat_cacing_diberikan'] ?? false),
                'catatan_kader' => $validated['catatan_kader'] ?? null,
            ]);

            // Perbarui kolom denormalisasi status terakhir pada tabel balita
            $balita->update([
                'tanggal_pengukuran_terakhir' => $validated['tanggal_kunjungan'],
                'status_gizi_terakhir' => $hasil->statusGizi,
                'status_stunting_terakhir' => $hasil->statusStunting,
                'status_bbtb_terakhir' => $hasil->statusBbtb,
                'zscore_bbu_terakhir' => $hasil->zscoreBbu,
                'zscore_tbu_terakhir' => $hasil->zscoreTbu,
                'zscore_bbtb_terakhir' => $hasil->zscoreBbtb,
            ]);

            return [
                'pengukuran' => $pengukuran,
                'hasil' => $hasil,
                'balita' => $balita,
            ];
        });

        $hasil = $result['hasil'];
        $balita = $result['balita'];

        // Siapkan pesan umpan balik interaktif
        if ($hasil->isZonaMerah) {
            $pesanAlert = "Pengukuran tersimpan. PERINGATAN: {$balita->nama_anak} terdeteksi masuk ke ZONA MERAH (Status Gizi: {$hasil->statusGizi->value}, Stunting: {$hasil->statusStunting->value}). Harap segera lakukan rujukan dan konseling gizi!";
            return redirect()->route('balita.show', $balita->id)
                ->with('warning', $pesanAlert);
        }

        if ($hasil->flagDataTidakWajar) {
            return redirect()->route('balita.show', $balita->id)
                ->with('warning', "Pengukuran tersimpan dengan catatan: nilai Z-Score berada di luar rentang biologis normal. Pastikan alat ukur dan data telah dikonfirmasi.");
        }

        return redirect()->route('balita.show', $balita->id)
            ->with('success', "Pengukuran untuk {$balita->nama_anak} berhasil dicatat. Status: {$hasil->statusGizi->value}, {$hasil->statusStunting->value}.");
    }
}
