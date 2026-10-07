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
use App\Models\WhoLmsReference;
use Carbon\Carbon;


beforeEach(function (): void {
    // Siapkan 2 Posyandu berbeda
    $this->posyanduA = Posyandu::factory()->create(['nama_posyandu' => 'Posyandu Mawar']);
    $this->posyanduB = Posyandu::factory()->create(['nama_posyandu' => 'Posyandu Melati']);

    // Petugas Posyandu A
    $this->kaderA = User::factory()->create([
        'peran' => PeranPengguna::KADER,
        'posyandu_id' => $this->posyanduA->id,
        'is_active' => true,
    ]);

    $this->bidanA = User::factory()->create([
        'peran' => PeranPengguna::BIDAN,
        'posyandu_id' => $this->posyanduA->id,
        'is_active' => true,
    ]);

    // Petugas Posyandu B
    $this->kaderB = User::factory()->create([
        'peran' => PeranPengguna::KADER,
        'posyandu_id' => $this->posyanduB->id,
        'is_active' => true,
    ]);

    // Administrator Desa (Global)
    $this->adminDesa = User::factory()->create([
        'peran' => PeranPengguna::ADMIN_DESA,
        'posyandu_id' => null,
        'is_active' => true,
    ]);

    // Data Orang Tua & Balita di Posyandu A
    $this->userOrangTuaA = User::factory()->create([
        'peran' => PeranPengguna::ORANG_TUA,
        'posyandu_id' => $this->posyanduA->id,
        'is_active' => true,
    ]);
    $this->orangTuaA = OrangTua::factory()->create([
        'user_id' => $this->userOrangTuaA->id,
    ]);
    $this->balitaA = Balita::factory()->create([
        'posyandu_id' => $this->posyanduA->id,
        'orang_tua_id' => $this->orangTuaA->id,
        'tanggal_lahir' => Carbon::now()->subMonths(10)->toDateString(),
    ]);

    // Data Orang Tua & Balita di Posyandu B
    $this->userOrangTuaB = User::factory()->create([
        'peran' => PeranPengguna::ORANG_TUA,
        'posyandu_id' => $this->posyanduB->id,
        'is_active' => true,
    ]);
    $this->orangTuaB = OrangTua::factory()->create([
        'user_id' => $this->userOrangTuaB->id,
    ]);
    $this->balitaB = Balita::factory()->create([
        'posyandu_id' => $this->posyanduB->id,
        'orang_tua_id' => $this->orangTuaB->id,
        'tanggal_lahir' => Carbon::now()->subMonths(12)->toDateString(),
    ]);

    // Buat data referensi LMS WHO minimal agar perhitungan antropometri valid
    WhoLmsReference::create([
        'indikator' => 'bbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 10.0,
        'l' => 0.5,
        'm' => 9.5,
        's' => 0.1,
    ]);
    WhoLmsReference::create([
        'indikator' => 'tbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 10.0,
        'l' => 1.0,
        'm' => 74.0,
        's' => 0.04,
    ]);
    WhoLmsReference::create([
        'indikator' => 'bbtb',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'panjang_cm',
        'nilai' => 74.0,
        'l' => -0.3,
        'm' => 9.5,
        's' => 0.08,
    ]);
});

test('kader posyandu A tidak dapat mencatat pengukuran untuk balita di posyandu B', function (): void {
    $response = $this->actingAs($this->kaderA)->post(route('pengukuran.store'), [
        'balita_id' => $this->balitaB->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'berat_badan_kg' => 9.2,
        'tinggi_badan_cm' => 74.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);

    // Harus ditolak dengan status 403 Forbidden (atau validasi posyandu gagal)
    expect($response->status())->toBeIn([403, 302]);
    if ($response->status() === 302) {
        $response->assertSessionHasErrors('balita_id');
    }
});

test('kader posyandu A dapat mencatat pengukuran untuk balita di posyandu A', function (): void {
    $response = $this->actingAs($this->kaderA)->post(route('pengukuran.store'), [
        'balita_id' => $this->balitaA->id,
        'tanggal_kunjungan' => Carbon::today()->toDateString(),
        'berat_badan_kg' => 9.2,
        'tinggi_badan_cm' => 74.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
    ]);

    $response->assertRedirect(route('balita.show', $this->balitaA->id));
    $this->assertDatabaseHas('pengukuran_balita', [
        'balita_id' => $this->balitaA->id,
        'berat_badan_kg' => 9.2,
    ]);
});

test('bidan posyandu A tidak dapat mencatat imunisasi untuk balita posyandu B', function (): void {
    $response = $this->actingAs($this->bidanA)->post(route('imunisasi.store'), [
        'balita_id' => $this->balitaB->id,
        'jenis_vaksin' => JenisVaksin::BCG->value,
        'tanggal_imunisasi' => Carbon::today()->toDateString(),
        'petugas_kesehatan' => 'Bidan A',
    ]);

    expect($response->status())->toBeIn([403, 302]);
    if ($response->status() === 302) {
        $response->assertSessionHasErrors('balita_id');
    }
});

test('orang tua hanya dapat melihat profil dan rekam medis anaknya sendiri', function (): void {
    // Mengakses anak sendiri -> 200 OK
    $responseSendiri = $this->actingAs($this->userOrangTuaA)->get(route('balita.show', $this->balitaA->id));
    $responseSendiri->assertOk();

    // Mengakses anak orang lain -> 403 Forbidden
    $responseAnakLain = $this->actingAs($this->userOrangTuaA)->get(route('balita.show', $this->balitaB->id));
    $responseAnakLain->assertForbidden();
});

test('admin desa memiliki kewenangan global melihat balita di seluruh posyandu', function (): void {
    $resA = $this->actingAs($this->adminDesa)->get(route('balita.show', $this->balitaA->id));
    $resA->assertOk();

    $resB = $this->actingAs($this->adminDesa)->get(route('balita.show', $this->balitaB->id));
    $resB->assertOk();
});

test('pengguna tidak aktif (is_active false) ditolak saat autentikasi login', function (): void {
    $userNonAktif = User::factory()->create([
        'email' => 'nonaktif@posyandu.id',
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    $response = $this->post(route('login.store'), [
        'login' => 'nonaktif@posyandu.id',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('login');
    $this->assertGuest();
});
