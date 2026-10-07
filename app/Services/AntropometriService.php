<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\HasilAntropometri;
use App\DataTransferObjects\LmsParameter;
use App\Enums\CaraUkurTinggi;
use App\Enums\JenisKelamin;
use App\Enums\KenaikanBb;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use App\Models\Balita;
use App\Models\WhoLmsReference;
use Carbon\Carbon;
use InvalidArgumentException;

class AntropometriService
{
    /**
     * Cache memori tabel LMS per request.
     * Struktur: ['indikator:jenis_kelamin:acuan' => array<int, object{nilai: float, l: float, m: float, s: float}>]
     *
     * @var array<string, array<int, object>>
     */
    private array $lmsCache = [];

    /**
     * Hitung usia dalam bulan penuh (floor/completed months).
     * Menolak nilai negatif atau > 60 bulan sesuai Permenkes No. 2 Tahun 2020.
     *
     * @throws InvalidArgumentException
     */
    public function hitungUsiaBulan(Carbon $lahir, Carbon $kunjungan): int
    {
        $usiaBulan = (int) $lahir->diffInMonths($kunjungan, false);

        if ($usiaBulan < 0 || $usiaBulan > 60) {
            throw new InvalidArgumentException(
                "Usia balita di luar batas standar pemantauan (0-60 bulan). Ditemukan: {$usiaBulan} bulan."
            );
        }

        return $usiaBulan;
    }

    /**
     * Koreksi pengukuran panjang / tinggi badan berdasarkan usia dan posisi ukur:
     * - Usia < 24 bulan diukur "berdiri" -> ditambah 0.7 cm
     * - Usia >= 24 bulan diukur "telentang" -> dikurangi 0.7 cm
     * - Kondisi lainnya -> tidak ada koreksi
     */
    public function koreksiTinggi(float $tinggi, int $usia, CaraUkurTinggi $cara): float
    {
        if ($usia < 24 && $cara === CaraUkurTinggi::BERDIRI) {
            return round($tinggi + 0.7, 1);
        }

        if ($usia >= 24 && $cara === CaraUkurTinggi::TELENTANG) {
            return round($tinggi - 0.7, 1);
        }

        return round($tinggi, 1);
    }

    /**
     * Ambil parameter L, M, S dari tabel acuan WHO dengan interpolasi linier jika diperlukan.
     * Menggunakan cache in-memory per siklus pemanggilan.
     */
    public function lookupLms(
        string $indikator,
        JenisKelamin $jenisKelamin,
        string $acuan,
        float $nilai
    ): ?LmsParameter {
        $nilaiBulat = round($nilai, 1);
        $cacheKey = "{$indikator}:{$jenisKelamin->value}:{$acuan}";

        if (!isset($this->lmsCache[$cacheKey])) {
            $rows = WhoLmsReference::query()
                ->where('indikator', $indikator)
                ->where('jenis_kelamin', $jenisKelamin->value)
                ->where('acuan', $acuan)
                ->orderBy('nilai', 'asc')
                ->get(['nilai', 'l', 'm', 's']);

            $this->lmsCache[$cacheKey] = $rows->map(fn ($r) => (object) [
                'nilai' => (float) $r->nilai,
                'l' => (float) $r->l,
                'm' => (float) $r->m,
                's' => (float) $r->s,
            ])->all();
        }

        $table = $this->lmsCache[$cacheKey];
        if (empty($table)) {
            return null;
        }

        $minNilai = $table[0]->nilai;
        $maxNilai = $table[count($table) - 1]->nilai;

        // Di luar rentang acuan tabel standar
        if ($nilaiBulat < $minNilai || $nilaiBulat > $maxNilai) {
            return null;
        }

        // Cari baris yang tepat
        foreach ($table as $row) {
            if (abs($row->nilai - $nilaiBulat) < 0.0001) {
                return new LmsParameter($row->l, $row->m, $row->s);
            }
        }

        // Interpolasi linier antara baris bawah dan atas terdekat
        $lower = null;
        $upper = null;

        foreach ($table as $row) {
            if ($row->nilai <= $nilaiBulat) {
                $lower = $row;
            }
            if ($row->nilai >= $nilaiBulat && $upper === null) {
                $upper = $row;
                break;
            }
        }

        if ($lower === null || $upper === null || abs($upper->nilai - $lower->nilai) < 0.00001) {
            return $lower !== null ? new LmsParameter($lower->l, $lower->m, $lower->s) : null;
        }

        $fraction = ($nilaiBulat - $lower->nilai) / ($upper->nilai - $lower->nilai);
        $interpL = $lower->l + $fraction * ($upper->l - $lower->l);
        $interpM = $lower->m + $fraction * ($upper->m - $lower->m);
        $interpS = $lower->s + $fraction * ($upper->s - $lower->s);

        return new LmsParameter($interpL, $interpM, $interpS);
    }

    /**
     * Hitung nilai Z-Score dasar menggunakan transformasi Box-Cox LMS:
     * - Jika L != 0 : Z = ((X / M)^L - 1) / (L * S)
     * - Jika L == 0 : Z = ln(X / M) / S
     */
    public function hitungZScore(float $x, float $l, float $m, float $s): float
    {
        if (abs($l) > 0.000001) {
            return (pow($x / $m, $l) - 1.0) / ($l * $s);
        }

        return log($x / $m) / $s;
    }

    /**
     * Hitung Z-score BB/U dengan penyesuaian tail adjustment WHO (|Z| > 3).
     */
    public function zScoreBbu(float $beratKg, int $usiaBulan, JenisKelamin $jk): ?float
    {
        $lms = $this->lookupLms('bbu', $jk, 'usia_bulan', (float) $usiaBulan);
        if ($lms === null) {
            return null;
        }

        $z = $this->hitungZScore($beratKg, $lms->l, $lms->m, $lms->s);
        $zDisesuaikan = $this->sesuaikanEkorTerbatas($beratKg, $z, $lms->l, $lms->m, $lms->s);

        return round($zDisesuaikan, 2);
    }

    /**
     * Hitung Z-score TB/U tanpa penyesuaian tail (WHO standard: TB/U tidak disesuaikan).
     */
    public function zScoreTbu(float $tinggiKoreksiCm, int $usiaBulan, JenisKelamin $jk): ?float
    {
        $lms = $this->lookupLms('tbu', $jk, 'usia_bulan', (float) $usiaBulan);
        if ($lms === null) {
            return null;
        }

        $z = $this->hitungZScore($tinggiKoreksiCm, $lms->l, $lms->m, $lms->s);

        return round($z, 2);
    }

    /**
     * Hitung Z-score BB/TB dengan pemilihan tabel:
     * - Usia < 24 bulan: tabel acuan panjang_cm (45.0 - 110.0 cm)
     * - Usia >= 24 bulan: tabel acuan tinggi_cm (65.0 - 120.0 cm)
     * Disertai penyesuaian tail WHO jika |Z| > 3.
     */
    public function zScoreBbtb(
        float $beratKg,
        float $tinggiKoreksiCm,
        int $usiaBulan,
        JenisKelamin $jk
    ): ?float {
        $acuan = $usiaBulan < 24 ? 'panjang_cm' : 'tinggi_cm';
        $lms = $this->lookupLms('bbtb', $jk, $acuan, $tinggiKoreksiCm);

        if ($lms === null) {
            return null;
        }

        $z = $this->hitungZScore($beratKg, $lms->l, $lms->m, $lms->s);
        $zDisesuaikan = $this->sesuaikanEkorTerbatas($beratKg, $z, $lms->l, $lms->m, $lms->s);

        return round($zDisesuaikan, 2);
    }

    /**
     * WHO Restricted Tail Adjustment untuk |Z| > 3 pada BB/U dan BB/TB.
     * Menjamin kontinuitas kurva dan mencegah distorsi asimetri ekstrem.
     */
    private function sesuaikanEkorTerbatas(
        float $x,
        float $z,
        float $l,
        float $m,
        float $s
    ): float {
        if ($z > 3.0) {
            $sd2pos = $this->hitungSdCutoff($m, $l, $s, 2.0);
            $sd3pos = $this->hitungSdCutoff($m, $l, $s, 3.0);
            $delta = $sd3pos - $sd2pos;

            if ($delta > 0.000001) {
                return 3.0 + (($x - $sd3pos) / $delta);
            }
        } elseif ($z < -3.0) {
            $sd2neg = $this->hitungSdCutoff($m, $l, $s, -2.0);
            $sd3neg = $this->hitungSdCutoff($m, $l, $s, -3.0);
            $delta = $sd2neg - $sd3neg;

            if ($delta > 0.000001) {
                return -3.0 + (($x - $sd3neg) / $delta);
            }
        }

        return $z;
    }

    /**
     * Hitung nilai cut-off SD tertentu:
     * - Jika L != 0 : SD_n = M * (1 + L * S * n)^(1 / L)
     * - Jika L == 0 : SD_n = M * exp(S * n)
     */
    private function hitungSdCutoff(float $m, float $l, float $s, float $n): float
    {
        if (abs($l) > 0.000001) {
            $arg = 1.0 + ($l * $s * $n);
            return $m * pow(max($arg, 0.000001), 1.0 / $l);
        }

        return $m * exp($s * n);
    }

    /**
     * Klasifikasi Status TB/U (Stunting):
     * - Z < -3.00: Severely Stunted
     * - -3.00 <= Z < -2.00: Stunted
     * - -2.00 <= Z <= +3.00: Normal
     * - Z > +3.00: Tinggi
     */
    public function klasifikasiStunting(?float $z): StatusStunting
    {
        if ($z === null) {
            return StatusStunting::NORMAL;
        }

        if ($z < -3.0) {
            return StatusStunting::SEVERELY_STUNTED;
        }

        if ($z < -2.0) {
            return StatusStunting::STUNTED;
        }

        if ($z <= 3.0) {
            return StatusStunting::NORMAL;
        }

        return StatusStunting::TINGGI;
    }

    /**
     * Klasifikasi Status Gizi BB/U:
     * - Z < -3.00: Gizi Buruk
     * - -3.00 <= Z < -2.00: Gizi Kurang
     * - -2.00 <= Z <= +1.00: Gizi Baik
     * - Z > +1.00: Risiko Gizi Lebih
     */
    public function klasifikasiGizi(?float $z): StatusGizi
    {
        if ($z === null) {
            return StatusGizi::GIZI_BAIK;
        }

        if ($z < -3.0) {
            return StatusGizi::GIZI_BURUK;
        }

        if ($z < -2.0) {
            return StatusGizi::GIZI_KURANG;
        }

        if ($z <= 1.0) {
            return StatusGizi::GIZI_BAIK;
        }

        return StatusGizi::RISIKO_GIZI_LEBIH;
    }

    /**
     * Klasifikasi Status BB/TB:
     * - null: Di luar rentang
     * - Z < -3.00: Gizi Buruk
     * - -3.00 <= Z < -2.00: Gizi Kurang
     * - -2.00 <= Z <= +1.00: Gizi Baik
     * - +1.00 < Z <= +2.00: Berisiko Gizi Lebih
     * - +2.00 < Z <= +3.00: Gizi Lebih
     * - Z > +3.00: Obesitas
     */
    public function klasifikasiBbtb(?float $z): StatusBbtb
    {
        if ($z === null) {
            return StatusBbtb::DI_LUAR_RENTANG;
        }

        if ($z < -3.0) {
            return StatusBbtb::GIZI_BURUK;
        }

        if ($z < -2.0) {
            return StatusBbtb::GIZI_KURANG;
        }

        if ($z <= 1.0) {
            return StatusBbtb::GIZI_BAIK;
        }

        if ($z <= 2.0) {
            return StatusBbtb::BERISIKO_GIZI_LEBIH;
        }

        if ($z <= 3.0) {
            return StatusBbtb::GIZI_LEBIH;
        }

        return StatusBbtb::OBESITAS;
    }

    /**
     * Evaluasi kenaikan berat badan KMS:
     * - "BARU" jika kunjungan pertama atau jeda kunjungan > 2 bulan (65 hari)
     * - "N" jika berat naik dibanding kunjungan terakhir
     * - "T" jika berat tetap atau turun
     */
    public function hitungKenaikanBb(
        Balita $balita,
        float $beratSekarang,
        Carbon $tanggalSekarang
    ): KenaikanBb {
        $pengukuranTerakhir = $balita->pengukuran()
            ->where('tanggal_kunjungan', '<', $tanggalSekarang->toDateString())
            ->orderBy('tanggal_kunjungan', 'desc')
            ->first();

        if ($pengukuranTerakhir === null) {
            return KenaikanBb::BARU;
        }

        $jarakHari = $pengukuranTerakhir->tanggal_kunjungan->diffInDays($tanggalSekarang);
        if ($jarakHari > 65) {
            return KenaikanBb::BARU;
        }

        $beratLalu = (float) $pengukuranTerakhir->berat_badan_kg;

        if ($beratSekarang > $beratLalu) {
            return KenaikanBb::NAIK;
        }

        return KenaikanBb::TETAP_TURUN;
    }

    /**
     * Verifikasi kriteria Zona Merah (Waspada Posyandu):
     * - Status BB/U atau BB/TB = Gizi Buruk / Gizi Kurang
     * - ATAU Status TB/U = Severely Stunted / Stunted
     * - ATAU Terjadi 2 kali "T" (Tidak Naik) berturut-turut
     */
    public function isZonaMerah(
        StatusGizi $gizi,
        StatusStunting $stunting,
        StatusBbtb $bbtb,
        KenaikanBb $kenaikanBb,
        ?Balita $balita = null
    ): bool {
        if ($gizi->isRedZone() || $stunting->isRedZone() || $bbtb->isRedZone()) {
            return true;
        }

        if ($kenaikanBb === KenaikanBb::TETAP_TURUN && $balita !== null) {
            $pengukuranLalu = $balita->pengukuran()
                ->orderBy('tanggal_kunjungan', 'desc')
                ->first();

            if ($pengukuranLalu !== null && $pengukuranLalu->kenaikan_bb === KenaikanBb::TETAP_TURUN) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lakukan analisis antropometri lengkap untuk balita berdasarkan kunjungan baru.
     * Mengembalikan HasilAntropometri DTO yang immutable.
     *
     * @param array{
     *     tanggal_kunjungan: string|Carbon,
     *     berat_badan_kg: float|numeric,
     *     tinggi_badan_cm: float|numeric,
     *     cara_ukur_tinggi: CaraUkurTinggi|string,
     *     lingkar_lengan_atas_cm?: float|numeric|null,
     *     lingkar_kepala_cm?: float|numeric|null,
     * } $input
     */
    public function analisis(Balita $balita, array $input): HasilAntropometri
    {
        $tanggalKunjungan = $input['tanggal_kunjungan'] instanceof Carbon
            ? $input['tanggal_kunjungan']
            : Carbon::parse((string) $input['tanggal_kunjungan']);

        $caraUkur = $input['cara_ukur_tinggi'] instanceof CaraUkurTinggi
            ? $input['cara_ukur_tinggi']
            : CaraUkurTinggi::from((string) $input['cara_ukur_tinggi']);

        $beratBadan = (float) $input['berat_badan_kg'];
        $tinggiMentah = (float) $input['tinggi_badan_cm'];

        $usiaBulan = $this->hitungUsiaBulan($balita->tanggal_lahir, $tanggalKunjungan);
        $tinggiKoreksi = $this->koreksiTinggi($tinggiMentah, $usiaBulan, $caraUkur);

        $zBbu = $this->zScoreBbu($beratBadan, $usiaBulan, $balita->jenis_kelamin);
        $zTbu = $this->zScoreTbu($tinggiKoreksi, $usiaBulan, $balita->jenis_kelamin);
        $zBbtb = $this->zScoreBbtb($beratBadan, $tinggiKoreksi, $usiaBulan, $balita->jenis_kelamin);

        $statusGizi = $this->klasifikasiGizi($zBbu);
        $statusStunting = $this->klasifikasiStunting($zTbu);
        $statusBbtb = $this->klasifikasiBbtb($zBbtb);

        $kenaikanBb = $this->hitungKenaikanBb($balita, $beratBadan, $tanggalKunjungan);
        $isRedZone = $this->isZonaMerah($statusGizi, $statusStunting, $statusBbtb, $kenaikanBb, $balita);

        // Evaluasi batas kewajaran biologis (Plausibility flags WHO)
        $pesanPeringatan = [];
        $tidakWajar = false;

        if ($zBbu !== null && ($zBbu < -6.0 || $zBbu > 5.0)) {
            $tidakWajar = true;
            $pesanPeringatan[] = "Z-Score BB/U ({$zBbu}) berada di luar batas kewajaran biologis (-6 s.d. +5 SD).";
        }

        if ($zTbu !== null && ($zTbu < -6.0 || $zTbu > 6.0)) {
            $tidakWajar = true;
            $pesanPeringatan[] = "Z-Score TB/U ({$zTbu}) berada di luar batas kewajaran biologis (-6 s.d. +6 SD).";
        }

        if ($zBbtb !== null && ($zBbtb < -5.0 || $zBbtb > 5.0)) {
            $tidakWajar = true;
            $pesanPeringatan[] = "Z-Score BB/TB ({$zBbtb}) berada di luar batas kewajaran biologis (-5 s.d. +5 SD).";
        }

        if ($zBbtb === null) {
            $pesanPeringatan[] = "Tinggi badan ({$tinggiKoreksi} cm) berada di luar batas acuan tabel BB/TB WHO.";
        }

        if ($isRedZone) {
            $pesanPeringatan[] = 'Perhatian: Balita masuk kategori Zona Merah kewaspadaan gizi/stunting. Perlu konseling PMT atau rujukan Puskesmas.';
        }

        return new HasilAntropometri(
            usiaBulan: $usiaBulan,
            tinggiBadanKoreksiCm: $tinggiKoreksi,
            zscoreBbu: $zBbu,
            zscoreTbu: $zTbu,
            zscoreBbtb: $zBbtb,
            statusGizi: $statusGizi,
            statusStunting: $statusStunting,
            statusBbtb: $statusBbtb,
            kenaikanBb: $kenaikanBb,
            flagDataTidakWajar: $tidakWajar,
            isZonaMerah: $isRedZone,
            pesanPeringatan: $pesanPeringatan,
        );
    }

    /**
     * Bersihkan cache memori acuan LMS (berguna untuk testing).
     */
    public function clearCache(): void
    {
        $this->lmsCache = [];
    }
}
