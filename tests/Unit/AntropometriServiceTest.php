<?php

declare(strict_types=1);

use App\DataTransferObjects\HasilAntropometri;
use App\Enums\CaraUkurTinggi;
use App\Enums\JenisKelamin;
use App\Enums\KenaikanBb;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use App\Models\Balita;
use App\Models\OrangTua;
use App\Models\Posyandu;
use App\Models\User;
use App\Models\WhoLmsReference;
use App\Services\AntropometriService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new AntropometriService();

    // =========================================================================
    // SYNTHETIC TEST FIXTURES ONLY (Bukan nilai riil referensi WHO)
    // Digunakan khusus untuk memvalidasi presisi matematika dan logika algoritma
    // =========================================================================

    // 1. Fixture BBU sintetis (Usia 12 bulan)
    WhoLmsReference::create([
        'indikator' => 'bbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 12.0,
        'l' => 0.500000,
        'm' => 10.000000,
        's' => 0.100000,
    ]);

    // 2. Fixture BBU sintetis dengan L = 0 (Usia 24 bulan)
    WhoLmsReference::create([
        'indikator' => 'bbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 24.0,
        'l' => 0.000000,
        'm' => 12.000000,
        's' => 0.100000,
    ]);

    // 3. Fixture TBU sintetis (Usia 12 bulan)
    WhoLmsReference::create([
        'indikator' => 'tbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 12.0,
        'l' => 1.000000,
        'm' => 75.000000,
        's' => 0.040000,
    ]);

    // 4. Fixture BBTB sintetis acuan panjang_cm (< 24 bulan)
    WhoLmsReference::create([
        'indikator' => 'bbtb',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'panjang_cm',
        'nilai' => 75.0,
        'l' => -0.300000,
        'm' => 9.500000,
        's' => 0.080000,
    ]);

    // 5. Fixture BBTB sintetis acuan tinggi_cm (>= 24 bulan)
    WhoLmsReference::create([
        'indikator' => 'bbtb',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'tinggi_cm',
        'nilai' => 85.0,
        'l' => -0.350000,
        'm' => 11.800000,
        's' => 0.085000,
    ]);

    // Fixture untuk pengujian interpolasi linier (panjang 80.0 dan 81.0)
    WhoLmsReference::create([
        'indikator' => 'bbtb',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'panjang_cm',
        'nilai' => 80.0,
        'l' => 0.200000,
        'm' => 10.000000,
        's' => 0.090000,
    ]);
    WhoLmsReference::create([
        'indikator' => 'bbtb',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'panjang_cm',
        'nilai' => 81.0,
        'l' => 0.200000,
        'm' => 10.400000,
        's' => 0.090000,
    ]);
});

test('hitungZScore menghasilkan tepat 0 ketika X sama dengan Median (M)', function (): void {
    $m = 10.0;
    $l = 0.5;
    $s = 0.12;

    $z = $this->service->hitungZScore($m, $l, $m, $s);

    expect($z)->toEqualWithDelta(0.0, 0.00001);
});

test('hitungZScore menghasilkan tepat 2 ketika X dihitung dari rumus invers Z = 2', function (): void {
    $m = 10.0;
    $l = 0.5;
    $s = 0.10;

    // Invers Box-Cox untuk Z = 2: X = M * (1 + L * S * 2)^(1 / L)
    $x = $m * pow(1.0 + ($l * $s * 2.0), 1.0 / $l);

    $z = $this->service->hitungZScore($x, $l, $m, $s);

    expect($z)->toEqualWithDelta(2.0, 0.00001);
});

test('hitungZScore mengeksekusi cabang L = 0 dengan transformasi logaritma natural', function (): void {
    $m = 12.0;
    $l = 0.0;
    $s = 0.10;
    $targetZ = 1.5;

    // Untuk L = 0: X = M * exp(S * Z)
    $x = $m * exp($s * $targetZ);

    $z = $this->service->hitungZScore($x, $l, $m, $s);

    expect($z)->toEqualWithDelta(1.5, 0.00001);
});

test('penyesuaian ekor terbatas menjaga kontinuitas pada batas |Z| = 3', function (): void {
    // Pada persis batas Z = 3.0, rumus Z dasar dan rumus penyesuaian tail bernilai sama persis
    $jk = JenisKelamin::LAKI_LAKI;
    $l = 0.500000;
    $m = 10.000000;
    $s = 0.100000;

    // Hitung X tepat pada Z = 3
    $xZ3 = $m * pow(1.0 + ($l * $s * 3.0), 1.0 / $l);
    $zBbuZ3 = $this->service->zScoreBbu($xZ3, 12, $jk);
    expect($zBbuZ3)->toEqualWithDelta(3.00, 0.01);

    // Hitung X sedikit di atas Z = 3 (misal Z teoretis ~ 3.5)
    $xZLebih = $m * pow(1.0 + ($l * $s * 3.5), 1.0 / $l);
    $zBbuLebih = $this->service->zScoreBbu($xZLebih, 12, $jk);
    expect($zBbuLebih)->toBeGreaterThan(3.00);

    // Kontinuitas batas negatif Z = -3
    $xZNeg3 = $m * pow(1.0 + ($l * $s * (-3.0)), 1.0 / $l);
    $zBbuNeg3 = $this->service->zScoreBbu($xZNeg3, 12, $jk);
    expect($zBbuNeg3)->toEqualWithDelta(-3.00, 0.01);

    // X di bawah Z = -3
    $xZNegLebih = $m * pow(1.0 + ($l * $s * (-3.5)), 1.0 / $l);
    $zBbuNegLebih = $this->service->zScoreBbu($xZNegLebih, 12, $jk);
    expect($zBbuNegLebih)->toBeLessThan(-3.00);
});

test('hitungUsiaBulan menerima batas usia 0 dan 60 bulan serta menolak di luar batas', function (): void {
    $lahir = Carbon::parse('2024-01-15');

    // Tepat lahir (0 bulan)
    expect($this->service->hitungUsiaBulan($lahir, Carbon::parse('2024-01-20')))->toBe(0);

    // Tepat 12 bulan
    expect($this->service->hitungUsiaBulan($lahir, Carbon::parse('2025-01-15')))->toBe(12);

    // Tepat 60 bulan
    expect($this->service->hitungUsiaBulan($lahir, Carbon::parse('2029-01-15')))->toBe(60);

    // Tanggal kunjungan mendahului tanggal lahir (< 0 bulan) -> Exception
    expect(fn () => $this->service->hitungUsiaBulan($lahir, Carbon::parse('2023-12-01')))
        ->toThrow(InvalidArgumentException::class);

    // Usia melebihi 60 bulan -> Exception
    expect(fn () => $this->service->hitungUsiaBulan($lahir, Carbon::parse('2029-02-20')))
        ->toThrow(InvalidArgumentException::class);
});

test('koreksiTinggi mengoreksi posisi ukur sesuai kaidah usia 24 bulan', function (): void {
    // Usia < 24 bulan diukur berdiri -> tambah 0.7 cm
    expect($this->service->koreksiTinggi(75.0, 18, CaraUkurTinggi::BERDIRI))->toBe(75.7);

    // Usia < 24 bulan diukur telentang -> tetap
    expect($this->service->koreksiTinggi(75.0, 18, CaraUkurTinggi::TELENTANG))->toBe(75.0);

    // Usia >= 24 bulan diukur telentang -> kurangi 0.7 cm
    expect($this->service->koreksiTinggi(85.0, 24, CaraUkurTinggi::TELENTANG))->toBe(84.3);

    // Usia >= 24 bulan diukur berdiri -> tetap
    expect($this->service->koreksiTinggi(85.0, 36, CaraUkurTinggi::BERDIRI))->toBe(85.0);
});

test('zScoreBbtb memilih tabel acuan panjang_cm atau tinggi_cm berdasarkan threshold 24 bulan', function (): void {
    $jk = JenisKelamin::LAKI_LAKI;

    // Usia 23 bulan (< 24) harus menggunakan tabel acuan panjang_cm (ada acuan panjang 75.0 cm)
    $z23Bulan = $this->service->zScoreBbtb(9.5, 75.0, 23, $jk);
    expect($z23Bulan)->not->toBeNull();

    // Usia 24 bulan (>= 24) harus menggunakan tabel acuan tinggi_cm (ada acuan tinggi 85.0 cm)
    $z24Bulan = $this->service->zScoreBbtb(11.8, 85.0, 24, $jk);
    expect($z24Bulan)->not->toBeNull();

    // Jika usia 24 bulan mencari nilai tinggi yang hanya ada di tabel panjang_cm, harus null (di luar tabel acuan)
    $zMismatched = $this->service->zScoreBbtb(9.5, 75.0, 24, $jk);
    expect($zMismatched)->toBeNull();
});

test('lookupLms melakukan interpolasi linier jika nilai berada di antara dua baris tabel acuan', function (): void {
    // Di database terdapat nilai 80.0 (M = 10.0) dan 81.0 (M = 10.4)
    // Untuk nilai 80.5, interpolasi linier M harus tepat bernilai 10.2
    $lms = $this->service->lookupLms('bbtb', JenisKelamin::LAKI_LAKI, 'panjang_cm', 80.5);

    expect($lms)->not->toBeNull();
    expect($lms->m)->toEqualWithDelta(10.200000, 0.0001);
});

test('klasifikasiStunting tepat pada nilai batas batas -3, -2, dan +3 SD', function (): void {
    expect($this->service->klasifikasiStunting(-3.01))->toBe(StatusStunting::SEVERELY_STUNTED);
    expect($this->service->klasifikasiStunting(-3.00))->toBe(StatusStunting::STUNTED);
    expect($this->service->klasifikasiStunting(-2.01))->toBe(StatusStunting::STUNTED);
    expect($this->service->klasifikasiStunting(-2.00))->toBe(StatusStunting::NORMAL);
    expect($this->service->klasifikasiStunting(0.00))->toBe(StatusStunting::NORMAL);
    expect($this->service->klasifikasiStunting(3.00))->toBe(StatusStunting::NORMAL);
    expect($this->service->klasifikasiStunting(3.01))->toBe(StatusStunting::TINGGI);
});

test('klasifikasiGizi tepat pada nilai batas batas -3, -2, dan +1 SD', function (): void {
    expect($this->service->klasifikasiGizi(-3.01))->toBe(StatusGizi::GIZI_BURUK);
    expect($this->service->klasifikasiGizi(-3.00))->toBe(StatusGizi::GIZI_KURANG);
    expect($this->service->klasifikasiGizi(-2.01))->toBe(StatusGizi::GIZI_KURANG);
    expect($this->service->klasifikasiGizi(-2.00))->toBe(StatusGizi::GIZI_BAIK);
    expect($this->service->klasifikasiGizi(1.00))->toBe(StatusGizi::GIZI_BAIK);
    expect($this->service->klasifikasiGizi(1.01))->toBe(StatusGizi::RISIKO_GIZI_LEBIH);
});

test('klasifikasiBbtb tepat pada seluruh nilai batas -3, -2, +1, +2, dan +3 SD', function (): void {
    expect($this->service->klasifikasiBbtb(null))->toBe(StatusBbtb::DI_LUAR_RENTANG);
    expect($this->service->klasifikasiBbtb(-3.01))->toBe(StatusBbtb::GIZI_BURUK);
    expect($this->service->klasifikasiBbtb(-3.00))->toBe(StatusBbtb::GIZI_KURANG);
    expect($this->service->klasifikasiBbtb(-2.01))->toBe(StatusBbtb::GIZI_KURANG);
    expect($this->service->klasifikasiBbtb(-2.00))->toBe(StatusBbtb::GIZI_BAIK);
    expect($this->service->klasifikasiBbtb(1.00))->toBe(StatusBbtb::GIZI_BAIK);
    expect($this->service->klasifikasiBbtb(1.01))->toBe(StatusBbtb::BERISIKO_GIZI_LEBIH);
    expect($this->service->klasifikasiBbtb(2.00))->toBe(StatusBbtb::BERISIKO_GIZI_LEBIH);
    expect($this->service->klasifikasiBbtb(2.01))->toBe(StatusBbtb::GIZI_LEBIH);
    expect($this->service->klasifikasiBbtb(3.00))->toBe(StatusBbtb::GIZI_LEBIH);
    expect($this->service->klasifikasiBbtb(3.01))->toBe(StatusBbtb::OBESITAS);
});

test('analisis menghasilkan DTO lengkap dengan deteksi zona merah dan plausibility flag', function (): void {
    $posyandu = Posyandu::factory()->create();
    $kader = User::factory()->kader()->create(['posyandu_id' => $posyandu->id]);
    $orangTua = OrangTua::factory()->create();

    $balita = Balita::create([
        'orang_tua_id' => $orangTua->id,
        'posyandu_id' => $posyandu->id,
        'nama_anak' => 'Anak Uji Klinis',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'tanggal_lahir' => Carbon::now()->subMonths(12)->toDateString(),
        'berat_lahir_kg' => 3.20,
        'panjang_lahir_cm' => 50.0,
        'lingkar_kepala_lahir_cm' => 34.0,
    ]);

    $hasil = $this->service->analisis($balita, [
        'tanggal_kunjungan' => Carbon::now()->toDateString(),
        'berat_badan_kg' => 10.0,
        'tinggi_badan_cm' => 74.3,
        'cara_ukur_tinggi' => CaraUkurTinggi::BERDIRI->value, // Usia 12 bulan berdiri -> koreksi +0.7 -> 75.0 cm
    ]);

    expect($hasil)->toBeInstanceOf(HasilAntropometri::class);
    expect($hasil->usiaBulan)->toBe(12);
    expect($hasil->tinggiBadanKoreksiCm)->toBe(75.0);
    expect($hasil->zscoreBbu)->toEqualWithDelta(0.00, 0.05);
    expect($hasil->zscoreTbu)->toEqualWithDelta(0.00, 0.05);
    expect($hasil->statusGizi)->toBe(StatusGizi::GIZI_BAIK);
    expect($hasil->statusStunting)->toBe(StatusStunting::NORMAL);
    expect($hasil->kenaikanBb)->toBe(KenaikanBb::BARU);
    expect($hasil->flagDataTidakWajar)->toBeFalse();
    expect($hasil->isZonaMerah)->toBeFalse();
});
