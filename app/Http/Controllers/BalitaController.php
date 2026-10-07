<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CaraUkurTinggi;
use App\Enums\JenisKelamin;
use App\Enums\JenisVaksin;
use App\Enums\KenaikanBb;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use App\Http\Requests\StoreBalitaRequest;
use App\Models\Balita;
use App\Models\OrangTua;
use App\Models\PengukuranBalita;
use App\Models\Posyandu;
use App\Models\WhoLmsReference;
use App\Services\AntropometriService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BalitaController extends Controller
{
    use AuthorizesRequests;

    /**
     * Tampilkan daftar balita dengan pencarian, filter status gizi/stunting, dan pagination.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Balita::class);

        $user = $request->user();
        $query = Balita::query()->with(['orangTua', 'posyandu']);

        // Scoping data berdasarkan peran
        if ($user->isOrangTua()) {
            $query->where('orang_tua_id', $user->orangTua?->id ?? 0);
        } elseif (!$user->isAdminDesa()) {
            $query->where('posyandu_id', $user->posyandu_id);
        } elseif ($request->filled('posyandu_id')) {
            $query->where('posyandu_id', $request->integer('posyandu_id'));
        }

        // Pencarian nama anak, NIK anak, atau nama ibu
        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            $query->where(function ($q) use ($cari): void {
                $q->where('nama_anak', 'like', "%{$cari}%")
                    ->orWhere('nik_anak', 'like', "%{$cari}%")
                    ->orWhereHas('orangTua', function ($sub) use ($cari): void {
                        $sub->where('nama_ibu', 'like', "%{$cari}%")
                            ->orWhere('nik_ibu', 'like', "%{$cari}%");
                    });
            });
        }

        // Filter status stunting (TB/U)
        if ($request->filled('status_stunting')) {
            $query->where('status_stunting_terakhir', $request->input('status_stunting'));
        }

        // Filter status gizi (BB/U)
        if ($request->filled('status_gizi')) {
            $query->where('status_gizi_terakhir', $request->input('status_gizi'));
        }

        // Filter status BB/TB
        if ($request->filled('status_bbtb')) {
            $query->where('status_bbtb_terakhir', $request->input('status_bbtb'));
        }

        // Filter khusus zona merah
        if ($request->boolean('zona_merah')) {
            $query->zonaMerah();
        }

        // Filter status domisili (aktif / pindah)
        if ($request->filled('status_domisili')) {
            $domisili = (string) $request->input('status_domisili');
            if ($domisili === 'pindah') {
                $query->pindah();
            } elseif ($domisili === 'aktif') {
                $query->aktif();
            }
        }

        $balitaList = $query->orderBy('nama_anak', 'asc')
            ->paginate(15)
            ->withQueryString();

        $daftarPosyandu = $user->isAdminDesa()
            ? Posyandu::query()->orderBy('nama_posyandu')->get(['id', 'nama_posyandu', 'kelurahan_desa'])
            : [];

        return Inertia::render('Balita/Index', [
            'balita' => $balitaList,
            'filters' => $request->only(['cari', 'posyandu_id', 'status_stunting', 'status_gizi', 'status_bbtb', 'zona_merah', 'status_domisili']),
            'posyanduList' => $daftarPosyandu,
            'statusStuntingOptions' => array_column(StatusStunting::cases(), 'value'),
            'statusGiziOptions' => array_column(StatusGizi::cases(), 'value'),
            'statusBbtbOptions' => array_column(StatusBbtb::cases(), 'value'),
        ]);
    }

    /**
     * Formulir pendaftaran balita baru.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Balita::class);

        $user = $request->user();

        $posyanduList = $user->isAdminDesa()
            ? Posyandu::query()->orderBy('nama_posyandu')->get(['id', 'nama_posyandu', 'kelurahan_desa'])
            : Posyandu::query()->where('id', $user->posyandu_id)->get(['id', 'nama_posyandu', 'kelurahan_desa']);

        $orangTuaList = OrangTua::query()
            ->orderBy('nama_ibu')
            ->get(['id', 'nama_ibu', 'nik_ibu', 'nama_ayah', 'no_kk']);

        return Inertia::render('Balita/Create', [
            'posyanduList' => $posyanduList,
            'orangTuaList' => $orangTuaList,
            'defaultPosyanduId' => $user->posyandu_id,
        ]);
    }

    /**
     * Simpan pendaftaran balita baru beserta data orang tua (jika dibuat inline) dalam database transaction.
     */
    public function store(StoreBalitaRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $balita = DB::transaction(function () use ($validated, $user): Balita {
            $orangTuaId = $validated['orang_tua_id'] ?? null;

            // Jika orang tua belum terdaftar, buat profil orang tua baru
            if ($orangTuaId === null) {
                $orangTua = OrangTua::create([
                    'no_kk' => $validated['no_kk'],
                    'nik_ibu' => $validated['nik_ibu'],
                    'nama_ibu' => $validated['nama_ibu'],
                    'nik_ayah' => $validated['nik_ayah'] ?? null,
                    'nama_ayah' => $validated['nama_ayah'] ?? null,
                    'alamat' => $validated['alamat'],
                    'golongan_darah' => $validated['golongan_darah'] ?? null,
                ]);

                $orangTuaId = $orangTua->id;
            }

            // Tentukan posyandu_id: Admin Desa boleh memilih, Kader/Bidan wajib memakai posyandu tugasnya
            $posyanduId = $user->isAdminDesa()
                ? (int) $validated['posyandu_id']
                : (int) $user->posyandu_id;

            return Balita::create([
                'orang_tua_id' => $orangTuaId,
                'posyandu_id' => $posyanduId,
                'nik_anak' => $validated['nik_anak'] ?? null,
                'nama_anak' => $validated['nama_anak'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'berat_lahir_kg' => $validated['berat_lahir_kg'],
                'panjang_lahir_cm' => $validated['panjang_lahir_cm'],
                'lingkar_kepala_lahir_cm' => $validated['lingkar_kepala_lahir_cm'],
            ]);
        });

        return redirect()->route('balita.show', $balita->id)
            ->with('success', "Data balita {$balita->nama_anak} berhasil didaftarkan ke sistem posyandu.");
    }

    /**
     * Tampilkan rekam medis dan grafik KMS balita secara komprehensif.
     */
    public function show(Balita $balita, Request $request): Response
    {
        $this->authorize('view', $balita);

        $balita->load([
            'orangTua.user',
            'posyandu',
            'pengukuran.kader',
            'imunisasi',
        ]);

        $vaksinDiterima = $balita->imunisasi->pluck('jenis_vaksin')->map(fn ($v) => $v->value)->all();
        $usiaSekarangBulan = $balita->usia_bulan;

        $rekomendasiVaksin = [];
        foreach (JenisVaksin::cases() as $vaksinCase) {
            $isSudah = in_array($vaksinCase->value, $vaksinDiterima, true);
            $usiaRekomendasi = $vaksinCase->usiaPemberianBulan();
            $statusJadwal = 'Mendatang';

            if ($isSudah) {
                $statusJadwal = 'Sudah Diberikan';
            } elseif ($usiaSekarangBulan >= $usiaRekomendasi) {
                $statusJadwal = 'Perlu Diberikan Sekarang';
            }

            $rekomendasiVaksin[] = [
                'kode' => $vaksinCase->value,
                'nama' => $vaksinCase->label(),
                'usia_target_bulan' => $usiaRekomendasi,
                'sudah_diberikan' => $isSudah,
                'status_jadwal' => $statusJadwal,
            ];
        }

        // Siapkan kurva acuan baku KMS WHO lengkap (BB/U, TB/U, BB/TB)
        $kurvaKms = [
            'bbu' => $this->ambilDataKurvaKmsSingle($balita->jenis_kelamin, 'bbu', 'usia_bulan'),
            'tbu' => $this->ambilDataKurvaKmsSingle($balita->jenis_kelamin, 'tbu', 'usia_bulan'),
            'bbtb' => $this->ambilDataKurvaKmsSingle($balita->jenis_kelamin, 'bbtb', $balita->usia_bulan < 24 ? 'panjang_cm' : 'tinggi_cm'),
        ];

        return Inertia::render('Balita/Show', [
            'balita' => $balita,
            'riwayatPengukuran' => $balita->pengukuran,
            'riwayatImunisasi' => $balita->imunisasi,
            'rekomendasiVaksin' => $rekomendasiVaksin,
            'kurvaKms' => $kurvaKms,
            'posyanduList' => Posyandu::orderBy('nama_posyandu')->get(['id', 'nama_posyandu', 'kelurahan_desa']),
        ]);
    }

    /**
     * Ambil data kurva standar KMS WHO untuk indikator tertentu.
     *
     * @return array<string, mixed>
     */
    private function ambilDataKurvaKmsSingle(JenisKelamin $jenisKelamin, string $indikator, string $acuan): array
    {
        $rows = WhoLmsReference::query()
            ->where('indikator', $indikator)
            ->where('jenis_kelamin', $jenisKelamin->value)
            ->where('acuan', $acuan)
            ->orderBy('nilai', 'asc')
            ->get();

        $labels = [];
        $sdMin3 = [];
        $sdMin2 = [];
        $sdPlus1 = [];
        $median = [];
        $sdPlus2 = [];
        $sdPlus3 = [];

        foreach ($rows as $row) {
            $labels[] = (float) $row->nilai;

            $l = (float) $row->l;
            $m = (float) $row->m;
            $s = (float) $row->s;

            $calcSd = function (float $n) use ($l, $m, $s): float {
                if (abs($l) > 0.000001) {
                    $val = 1.0 + ($l * $s * $n);
                    return round($m * pow(max($val, 0.000001), 1.0 / $l), 2);
                }
                return round($m * exp($s * $n), 2);
            };

            $sdMin3[] = $calcSd(-3.0);
            $sdMin2[] = $calcSd(-2.0);
            $median[] = round($m, 2);
            $sdPlus1[] = $calcSd(1.0);
            $sdPlus2[] = $calcSd(2.0);
            $sdPlus3[] = $calcSd(3.0);
        }

        return [
            'labels' => $labels,
            'sdMin3' => $sdMin3,
            'sdMin2' => $sdMin2,
            'sdPlus1' => $sdPlus1,
            'median' => $median,
            'sdPlus2' => $sdPlus2,
            'sdPlus3' => $sdPlus3,
        ];
    }

    /**
     * Mutasi / Pindah Posyandu untuk balita yang berpindah domisili.
     * Mendukung:
     * 1. 'posyandu_digital': Pindah ke posyandu digital lain (memilih posyandu baru).
     * 2. 'luar_wilayah': Pindah ke luar wilayah non-aplikasi (status = pindah, tanpa posyandu tujuan).
     * 3. 'aktifkan_kembali': Mengembalikan status domisili balita menjadi aktif di posyandu saat ini.
     */
    public function pindahPosyandu(Request $request, Balita $balita): RedirectResponse
    {
        $user = $request->user();

        $canTransfer = $user->isAdminDesa()
            || $user->isBidan()
            || $user->isKader()
            || ($user->isOrangTua() && $user->orangTua?->id === $balita->orang_tua_id);

        if (!$canTransfer) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah posyandu atau status domisili balita ini.');
        }

        $tipePindah = (string) $request->input('tipe_pindah', 'posyandu_digital');

        // KASUS 1: PINDAH KE LUAR WILAYAH (NON-APLIKASI)
        if ($tipePindah === 'luar_wilayah') {
            $validated = $request->validate([
                'catatan_pindah' => ['nullable', 'string', 'max:500'],
                'update_keluarga' => ['boolean'],
            ]);

            $keterangan = !empty($validated['catatan_pindah'])
                ? $validated['catatan_pindah']
                : 'Pindah ke wilayah luar yang belum terintegrasi Posyandu Digital';

            DB::transaction(function () use ($balita, $keterangan, $request): void {
                $balita->update([
                    'status_domisili' => 'pindah',
                    'keterangan_pindah' => $keterangan,
                ]);

                if ($request->boolean('update_keluarga', true) && $balita->orangTua) {
                    $balita->orangTua->balita()->update([
                        'status_domisili' => 'pindah',
                        'keterangan_pindah' => $keterangan,
                    ]);
                }
            });

            return redirect()->back()->with(
                'success',
                "Status balita {$balita->nama_anak} berhasil diperbarui menjadi PINDAH (Keluar Wilayah). Data rekam medis dan grafik KMS tetap tersimpan aman sebagai arsip historis."
            );
        }

        // KASUS 2: REAKTIVASI (KEMBALI KE POSYANDU INI)
        if ($tipePindah === 'aktifkan_kembali') {
            DB::transaction(function () use ($balita, $request): void {
                $balita->update([
                    'status_domisili' => 'aktif',
                    'keterangan_pindah' => null,
                ]);

                if ($request->boolean('update_keluarga', true) && $balita->orangTua) {
                    $balita->orangTua->balita()->update([
                        'status_domisili' => 'aktif',
                        'keterangan_pindah' => null,
                    ]);
                }
            });

            return redirect()->back()->with(
                'success',
                "Status {$balita->nama_anak} berhasil diaktifkan kembali di {$balita->posyandu?->nama_posyandu}."
            );
        }

        // KASUS 3: PINDAH KE POSYANDU DIGITAL LAIN DALAM APLIKASI
        $validated = $request->validate([
            'posyandu_id' => ['required', 'exists:posyandu,id'],
            'alamat_baru' => ['nullable', 'string', 'max:500'],
            'catatan_pindah' => ['nullable', 'string', 'max:500'],
            'update_keluarga' => ['boolean'],
        ], [
            'posyandu_id.required' => 'Silakan pilih posyandu tujuan kepindahan.',
            'posyandu_id.exists' => 'Posyandu tujuan tidak ditemukan dalam sistem.',
        ]);

        $posyanduLama = $balita->posyandu;
        $posyanduBaru = Posyandu::findOrFail($validated['posyandu_id']);

        if ($posyanduLama && $posyanduLama->id === $posyanduBaru->id && ($balita->status_domisili ?? 'aktif') === 'aktif') {
            return redirect()->back()->with('warning', "Balita {$balita->nama_anak} saat ini sudah aktif berada di {$posyanduBaru->nama_posyandu}.");
        }

        DB::transaction(function () use ($balita, $validated, $posyanduBaru, $request): void {
            // 1. Update Posyandu pada Balita & pastikan status aktif
            $balita->update([
                'posyandu_id' => $posyanduBaru->id,
                'status_domisili' => 'aktif',
                'keterangan_pindah' => null,
            ]);

            // 2. Sinkronkan domisili dan akun keluarga jika terdaftar
            if ($balita->orangTua) {
                if (!empty($validated['alamat_baru'])) {
                    $balita->orangTua->update([
                        'alamat' => $validated['alamat_baru'],
                    ]);
                }

                if ($balita->orangTua->user) {
                    $balita->orangTua->user->update([
                        'posyandu_id' => $posyanduBaru->id,
                    ]);
                }

                // Jika diminta update seluruh anak dalam keluarga yang sama
                if ($request->boolean('update_keluarga', true)) {
                    $balita->orangTua->balita()->update([
                        'posyandu_id' => $posyanduBaru->id,
                        'status_domisili' => 'aktif',
                        'keterangan_pindah' => null,
                    ]);
                }
            }
        });

        $namaLama = $posyanduLama ? $posyanduLama->nama_posyandu : 'Posyandu sebelumnya';

        return redirect()->back()->with(
            'success',
            "Balita {$balita->nama_anak} berhasil dipindahkan dari {$namaLama} ke {$posyanduBaru->nama_posyandu}. Riwayat KMS dan grafik rekam medis tetap tersimpan lengkap."
        );
    }

    /**
     * Sinkronisasi data riwayat KMS fisik (Buku KIA) untuk bulan-bulan sebelumnya yang belum tercatat di aplikasi.
     * Digunakan ketika balita baru masuk/pindah ke posyandu pada usia tertentu (misal usia 3 bulan),
     * agar data pertumbuhan dari bulan 0 (lahir) hingga bulan kedatangan lengkap terhubung ke kurva KMS digital.
     */
    public function sinkronisasiKms(
        Request $request,
        Balita $balita,
        AntropometriService $antropometriService
    ): RedirectResponse {
        $user = $request->user();

        $canSync = $user->isAdminDesa()
            || $user->isBidan()
            || $user->isKader();

        if (!$canSync) {
            abort(403, 'Hanya Kader, Bidan, atau Admin Desa yang berhak melakukan sinkronisasi riwayat buku KMS fisik.');
        }

        $validated = $request->validate([
            'riwayat' => ['required', 'array', 'min:1'],
            'riwayat.*.tanggal_kunjungan' => ['required', 'date'],
            'riwayat.*.berat_badan_kg' => ['required', 'numeric', 'min:1.0', 'max:40.0'],
            'riwayat.*.tinggi_badan_cm' => ['required', 'numeric', 'min:30.0', 'max:150.0'],
            'riwayat.*.cara_ukur_tinggi' => ['nullable', 'string', 'in:telentang,berdiri'],
            'riwayat.*.lingkar_kepala_cm' => ['nullable', 'numeric', 'min:20.0', 'max:65.0'],
            'riwayat.*.lingkar_lengan_atas_cm' => ['nullable', 'numeric', 'min:5.0', 'max:35.0'],
            'riwayat.*.catatan_kader' => ['nullable', 'string', 'max:500'],
        ], [
            'riwayat.required' => 'Tidak ada baris data KMS yang dikirimkan.',
            'riwayat.*.tanggal_kunjungan.required' => 'Tanggal penimbangan harus diisi.',
            'riwayat.*.berat_badan_kg.required' => 'Berat badan (kg) harus diisi.',
            'riwayat.*.tinggi_badan_cm.required' => 'Panjang/Tinggi badan (cm) harus diisi.',
        ]);

        DB::transaction(function () use ($balita, $validated, $user, $antropometriService): void {
            foreach ($validated['riwayat'] as $item) {
                $tanggalKunjungan = Carbon::parse($item['tanggal_kunjungan']);
                $usiaBulan = (int) $balita->tanggal_lahir->diffInMonths($tanggalKunjungan, false);
                $caraUkur = $item['cara_ukur_tinggi'] ?? ($usiaBulan < 24 ? 'telentang' : 'berdiri');

                // Analisis antropometri Z-score WHO
                $hasil = $antropometriService->analisis($balita, [
                    'tanggal_kunjungan' => $tanggalKunjungan,
                    'berat_badan_kg' => $item['berat_badan_kg'],
                    'tinggi_badan_cm' => $item['tinggi_badan_cm'],
                    'cara_ukur_tinggi' => $caraUkur,
                    'lingkar_kepala_cm' => $item['lingkar_kepala_cm'] ?? null,
                    'lingkar_lengan_atas_cm' => $item['lingkar_lengan_atas_cm'] ?? null,
                ]);

                // Update atau buat catatan pengukuran
                PengukuranBalita::updateOrCreate(
                    [
                        'balita_id' => $balita->id,
                        'tanggal_kunjungan' => $tanggalKunjungan->toDateString(),
                    ],
                    [
                        'kader_id' => $user->id,
                        'usia_bulan' => $hasil->usiaBulan,
                        'berat_badan_kg' => $item['berat_badan_kg'],
                        'tinggi_badan_cm' => $hasil->tinggiBadanKoreksiCm,
                        'lingkar_lengan_atas_cm' => $item['lingkar_lengan_atas_cm'] ?? null,
                        'lingkar_kepala_cm' => $item['lingkar_kepala_cm'] ?? null,
                        'cara_ukur_tinggi' => $caraUkur,
                        'zscore_bbu' => $hasil->zscoreBbu,
                        'zscore_tbu' => $hasil->zscoreTbu,
                        'zscore_bbtb' => $hasil->zscoreBbtb,
                        'status_gizi' => $hasil->statusGizi->value,
                        'status_stunting' => $hasil->statusStunting->value,
                        'status_bbtb' => $hasil->statusBbtb->value,
                        'flag_data_tidak_wajar' => $hasil->flagDataTidakWajar,
                        'kenaikan_bb' => $hasil->kenaikanBb->value,
                        'catatan_kader' => $item['catatan_kader'] ?? 'Sinkronisasi riwayat buku KMS fisik (Posyandu asal)',
                    ]
                );
            }

            // Rekalkulasi kenaikan BB (N / T / B) secara kronologis untuk seluruh pengukuran balita ini
            $semuaPengukuran = $balita->pengukuran()
                ->orderBy('tanggal_kunjungan', 'asc')
                ->get();

            $prevPengukuran = null;
            foreach ($semuaPengukuran as $index => $pengukuran) {
                if ($index === 0) {
                    $pengukuran->update(['kenaikan_bb' => KenaikanBb::BARU->value]);
                } else {
                    $selisihHari = $prevPengukuran->tanggal_kunjungan->diffInDays($pengukuran->tanggal_kunjungan);
                    if ($selisihHari > 65) {
                        $pengukuran->update(['kenaikan_bb' => KenaikanBb::BARU->value]);
                    } elseif ((float) $pengukuran->berat_badan_kg > (float) $prevPengukuran->berat_badan_kg) {
                        $pengukuran->update(['kenaikan_bb' => KenaikanBb::NAIK->value]);
                    } else {
                        $pengukuran->update(['kenaikan_bb' => KenaikanBb::TETAP_TURUN->value]);
                    }
                }
                $prevPengukuran = $pengukuran;
            }

            // Perbarui status denormalisasi terakhir pada balita
            $pengukuranTerakhir = $balita->pengukuran()
                ->orderBy('tanggal_kunjungan', 'desc')
                ->first();

            if ($pengukuranTerakhir !== null) {
                $balita->update([
                    'tanggal_pengukuran_terakhir' => $pengukuranTerakhir->tanggal_kunjungan,
                    'status_gizi_terakhir' => $pengukuranTerakhir->status_gizi,
                    'status_stunting_terakhir' => $pengukuranTerakhir->status_stunting,
                    'status_bbtb_terakhir' => $pengukuranTerakhir->status_bbtb,
                    'zscore_bbu_terakhir' => $pengukuranTerakhir->zscore_bbu,
                    'zscore_tbu_terakhir' => $pengukuranTerakhir->zscore_tbu,
                    'zscore_bbtb_terakhir' => $pengukuranTerakhir->zscore_bbtb,
                ]);
            }
        });

        $jumlahData = count($validated['riwayat']);
        return redirect()->back()->with(
            'success',
            "Sinkronisasi KMS berhasil! Sebanyak {$jumlahData} data riwayat penimbangan dari buku KMS fisik {$balita->nama_anak} telah tersimpan dan kurva pertumbuhan digital terhubung lengkap."
        );
    }
}
