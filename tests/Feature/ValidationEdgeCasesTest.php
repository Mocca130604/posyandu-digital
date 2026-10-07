<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CaraUkurTinggi;
use App\Enums\JenisKelamin;
use App\Enums\JenisVaksin;
use App\Enums\PeranPengguna;
use App\Models\Balita;
use App\Models\OrangTua;
use App\Models\Posyandu;
use App\Models\User;
use Carbon\Carbon;


beforeEach(function (): void {
    $this->posyandu = Posyandu::factory()->create();
    $this->kader = User::factory()->create([
        'peran' => PeranPengguna::KADER,
        'posyandu_id' => $this->posyandu->id,
        'is_active' => true,
    ]);
    $this->bidan = User::factory()->create([
        'peran' => PeranPengguna::BIDAN,
        'posyandu_id' => $this->posyandu->id,
        'is_active' => true,
    ]);

    $this->orangTua = OrangTua::factory()->create();
    $this->balita = Balita::factory()->create([
        'posyandu_id' => $this->posyandu->id,
        'orang_tua_id' => $this->orangTua->id,
        'tanggal_lahir' => Carbon::now()->subMonths(10)->toDateString(),
    ]);
});

test('pendaftaran balita menolak NIK yang tidak tepat 16 digit angka', function (): void {
    // 1. NIK kurang dari 16 digit (15 digit)
    $res1 = $this->actingAs($this->kader)->post(route('balita.store'), [
        'orang_tua_id' => $this->orangTua->id,
        'nik_anak' => '320101123456789', // 15 digit
        'nama_anak' => 'Anak Uji',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'tanggal_lahir' => Carbon::today()->subMonths(5)->toDateString(),
    ]);
    $res1->assertSessionHasErrors('nik_anak');

    // 2. NIK lebih dari 16 digit (17 digit)
    $res2 = $this->actingAs($this->kader)->post(route('balita.store'), [
        'orang_tua_id' => $this->orangTua->id,
        'nik_anak' => '32010112345678901', // 17 digit
        'nama_anak' => 'Anak Uji',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'tanggal_lahir' => Carbon::today()->subMonths(5)->toDateString(),
    ]);
    $res2->assertSessionHasErrors('nik_anak');

    // 3. NIK mengandung huruf
    $res3 = $this->actingAs($this->kader)->post(route('balita.store'), [
        'orang_tua_id' => $this->orangTua->id,
        'nik_anak' => '320101123456789A',
        'nama_anak' => 'Anak Uji',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'tanggal_lahir' => Carbon::today()->subMonths(5)->toDateString(),
    ]);
    $res3->assertSessionHasErrors('nik_anak');
});

test('pengukuran menolak berat badan di luar rentang klinis 0.5 hingga 30.0 kg', function (): void {
    // Di bawah batas bawah (0.4 kg)
    $resBawah = $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'berat_badan_kg' => 0.4,
        'tinggi_badan_cm' => 70.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);
    $resBawah->assertSessionHasErrors('berat_badan_kg');

    // Di atas batas atas (30.1 kg)
    $resAtas = $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'berat_badan_kg' => 30.1,
        'tinggi_badan_cm' => 70.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);
    $resAtas->assertSessionHasErrors('berat_badan_kg');
});

test('pengukuran menolak tinggi badan di luar rentang klinis 35.0 hingga 130.0 cm', function (): void {
    // Di bawah batas bawah (34.9 cm)
    $resBawah = $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'berat_badan_kg' => 8.0,
        'tinggi_badan_cm' => 34.9,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);
    $resBawah->assertSessionHasErrors('tinggi_badan_cm');

    // Di atas batas atas (130.1 cm)
    $resAtas = $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'berat_badan_kg' => 8.0,
        'tinggi_badan_cm' => 130.1,
        'cara_ukur_tinggi' => CaraUkurTinggi::BERDIRI->value,
    ]);
    $resAtas->assertSessionHasErrors('tinggi_badan_cm');
});

test('pengukuran menolak tanggal kunjungan di masa depan atau sebelum tanggal lahir', function (): void {
    // Tanggal kunjungan di masa depan (besok)
    $resMasaDepan = $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => Carbon::tomorrow()->toDateString(),
        'berat_badan_kg' => 8.5,
        'tinggi_badan_cm' => 72.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);
    $resMasaDepan->assertSessionHasErrors('tanggal_kunjungan');

    // Tanggal kunjungan sebelum anak lahir
    $sebelumLahir = Carbon::parse($this->balita->tanggal_lahir)->subDays(5)->toDateString();
    $resSebelumLahir = $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => $sebelumLahir,
        'berat_badan_kg' => 8.5,
        'tinggi_badan_cm' => 72.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);
    $resSebelumLahir->assertSessionHasErrors('tanggal_kunjungan');
});

test('pengukuran menolak duplikasi tanggal kunjungan pada balita yang sama di hari yang sama', function (): void {
    $visitDate = Carbon::today()->toDateString();

    // Buat pengukuran pertama langsung di database
    $this->balita->pengukuran()->create([
        'kader_id' => $this->kader->id,
        'tanggal_kunjungan' => $visitDate,
        'usia_bulan' => 10,
        'berat_badan_kg' => 8.5,
        'tinggi_badan_cm' => 72.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG,
    ]);

    // Percobaan input pengukuran kedua pada tanggal yang sama
    $resDuplicate = $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => $visitDate,
        'berat_badan_kg' => 8.6,
        'tinggi_badan_cm' => 72.2,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);
    $resDuplicate->assertSessionHasErrors('tanggal_kunjungan');
});

test('imunisasi menolak pencatatan jenis vaksin yang sama dua kali pada satu balita', function (): void {
    // Catatan imunisasi pertama
    $this->actingAs($this->bidan)->post(route('imunisasi.store'), [
        'balita_id' => $this->balita->id,
        'jenis_vaksin' => JenisVaksin::BCG->value,
        'tanggal_imunisasi' => Carbon::today()->toDateString(),
        'petugas_kesehatan' => 'Bidan Desa',
    ]);

    // Percobaan pencatatan ulang jenis vaksin yang sama (BCG)
    $resDuplikat = $this->actingAs($this->bidan)->post(route('imunisasi.store'), [
        'balita_id' => $this->balita->id,
        'jenis_vaksin' => JenisVaksin::BCG->value,
        'tanggal_imunisasi' => Carbon::today()->toDateString(),
        'petugas_kesehatan' => 'Bidan Desa',
    ]);

    $resDuplikat->assertSessionHasErrors('jenis_vaksin');
});
