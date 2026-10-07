<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CaraUkurTinggi;
use App\Enums\JenisKelamin;
use App\Enums\KenaikanBb;
use App\Enums\PeranPengguna;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use App\Models\Balita;
use App\Models\OrangTua;
use App\Models\PengukuranBalita;
use App\Models\Posyandu;
use App\Models\User;
use App\Models\WhoLmsReference;
use Carbon\Carbon;


beforeEach(function (): void {
    $this->posyandu = Posyandu::factory()->create();
    $this->kader = User::factory()->create([
        'peran' => PeranPengguna::KADER,
        'posyandu_id' => $this->posyandu->id,
        'is_active' => true,
    ]);

    $this->orangTua = OrangTua::factory()->create();
    $this->balita = Balita::factory()->create([
        'posyandu_id' => $this->posyandu->id,
        'orang_tua_id' => $this->orangTua->id,
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI,
        'tanggal_lahir' => Carbon::now()->subMonths(12)->toDateString(),
    ]);

    // Setup WHO LMS standards for age 12 months (Laki-laki)
    WhoLmsReference::create([
        'indikator' => 'bbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 12.0,
        'l' => 0.5,
        'm' => 9.6,
        's' => 0.1, // SD ~ 0.96 kg -> -2SD ~ 7.68kg, -3SD ~ 6.72kg
    ]);
    WhoLmsReference::create([
        'indikator' => 'tbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 12.0,
        'l' => 1.0,
        'm' => 75.7,
        's' => 0.04, // SD ~ 3.03cm -> -2SD ~ 69.6cm, -3SD ~ 66.6cm
    ]);
    WhoLmsReference::create([
        'indikator' => 'bbtb',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'panjang_cm',
        'nilai' => 75.0,
        'l' => -0.3,
        'm' => 9.6,
        's' => 0.08,
    ]);
    WhoLmsReference::create([
        'indikator' => 'bbtb',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'panjang_cm',
        'nilai' => 67.0,
        'l' => -0.3,
        'm' => 7.8,
        's' => 0.08,
    ]);
});

test('balita dengan berat badan di bawah -2 SD terdeteksi masuk ke zona merah (gizi buruk / kurang)', function (): void {
    // Berat 6.5 kg di usia 12 bulan (Z-score BB/U < -3 SD -> Gizi Buruk)
    $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'berat_badan_kg' => 6.5,
        'tinggi_badan_cm' => 75.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);

    $this->balita->refresh();
    expect($this->balita->is_zona_merah)->toBeTrue()
        ->and($this->balita->status_gizi_terakhir)->toBe(StatusGizi::GIZI_BURUK);

    // Cek balita masuk ke query scopeZonaMerah
    $zonaMerahList = Balita::zonaMerah()->pluck('id')->all();
    expect($zonaMerahList)->toContain($this->balita->id);
});

test('balita dengan tinggi badan di bawah -2 SD terdeteksi masuk ke zona merah (stunting)', function (): void {
    // Tinggi 67.0 cm di usia 12 bulan (Z-score TB/U < -2 SD -> Stunted)
    $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'berat_badan_kg' => 9.6,
        'tinggi_badan_cm' => 67.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);

    $this->balita->refresh();
    expect($this->balita->is_zona_merah)->toBeTrue()
        ->and($this->balita->status_stunting_terakhir)->toBe(StatusStunting::STUNTED);

    $stuntingList = Balita::stunting()->pluck('id')->all();
    expect($stuntingList)->toContain($this->balita->id);
});

test('balita dengan dua pengukuran berturut-turut berat badan tidak naik (T-T) terdeteksi masuk zona merah', function (): void {
    // Kunjungan 1 (bulan lalu): BB 9.0 kg
    PengukuranBalita::create([
        'balita_id' => $this->balita->id,
        'kader_id' => $this->kader->id,
        'tanggal_kunjungan' => Carbon::today()->subMonth()->toDateString(),
        'usia_bulan' => 11,
        'berat_badan_kg' => 9.0,
        'tinggi_badan_cm' => 75.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG,
        'kenaikan_bb' => KenaikanBb::TETAP_TURUN,
        'zscore_bbu' => -0.5,
        'zscore_tbu' => 0.0,
        'zscore_bbtb' => -0.5,
        'status_gizi' => StatusGizi::GIZI_BAIK,
        'status_stunting' => StatusStunting::NORMAL,
        'status_bbtb' => StatusBbtb::GIZI_BAIK,
    ]);

    // Kunjungan 2 (hari ini): BB tetap 9.0 kg -> KenaikanBb::TETAP_TURUN lagi
    PengukuranBalita::create([
        'balita_id' => $this->balita->id,
        'kader_id' => $this->kader->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'usia_bulan' => 12,
        'berat_badan_kg' => 9.0,
        'tinggi_badan_cm' => 75.7,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG,
        'kenaikan_bb' => KenaikanBb::TETAP_TURUN,
        'zscore_bbu' => -0.5,
        'zscore_tbu' => 0.0,
        'zscore_bbtb' => -0.5,
        'status_gizi' => StatusGizi::GIZI_BAIK,
        'status_stunting' => StatusStunting::NORMAL,
        'status_bbtb' => StatusBbtb::GIZI_BAIK,
    ]);

    $this->balita->update([
        'status_gizi_terakhir' => StatusGizi::GIZI_BAIK,
        'status_stunting_terakhir' => StatusStunting::NORMAL,
        'status_bbtb_terakhir' => StatusBbtb::GIZI_BAIK,
    ]);

    $this->balita->refresh();
    // Walaupun status z-score individual normal, karena 2x berturut-turut T (T-T), is_zona_merah wajib TRUE
    expect($this->balita->is_zona_merah)->toBeTrue();
});
