<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CaraUkurTinggi;
use App\Enums\JenisKelamin;
use App\Enums\PeranPengguna;
use App\Enums\StatusGizi;
use App\Models\Balita;
use App\Models\OrangTua;
use App\Models\PengukuranBalita;
use App\Models\Posyandu;
use App\Models\User;
use App\Models\WhoLmsReference;
use App\Services\AntropometriService;
use Carbon\Carbon;
use Exception;
use Mockery;


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
        'tanggal_pengukuran_terakhir' => null,
        'status_gizi_terakhir' => null,
    ]);

    // LMS Fixtures
    WhoLmsReference::create([
        'indikator' => 'bbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 12.0,
        'l' => 0.5,
        'm' => 9.6,
        's' => 0.1,
    ]);
    WhoLmsReference::create([
        'indikator' => 'tbu',
        'jenis_kelamin' => JenisKelamin::LAKI_LAKI->value,
        'acuan' => 'usia_bulan',
        'nilai' => 12.0,
        'l' => 1.0,
        'm' => 75.7,
        's' => 0.04,
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
});

test('penyimpanan pengukuran berhasil memperbarui ringkasan status pada model balita secara atomik', function (): void {
    $visitDate = Carbon::today()->toDateString();

    $response = $this->actingAs($this->kader)->post(route('pengukuran.store'), [
        'balita_id' => $this->balita->id,
        'tanggal_kunjungan' => $visitDate,
        'berat_badan_kg' => 9.6,
        'tinggi_badan_cm' => 75.0,
        'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
        'lila_cm' => 14.5,
        'lingkar_kepala_cm' => 46.0,
        'vitamin_a' => true,
        'obat_cacing' => false,
    ]);

    $response->assertRedirect(route('balita.show', $this->balita->id));

    // Verifikasi catatan tersimpan di pengukuran_balita
    $pengukuranTersimpan = PengukuranBalita::where('balita_id', $this->balita->id)
        ->where('berat_badan_kg', 9.6)
        ->first();
    expect($pengukuranTersimpan)->not->toBeNull()
        ->and($pengukuranTersimpan->tanggal_kunjungan->toDateString())->toBe($visitDate);

    // Verifikasi cache status pada tabel balita terbarui
    $this->balita->refresh();
    expect($this->balita->tanggal_pengukuran_terakhir?->toDateString())->toBe($visitDate)
        ->and($this->balita->status_gizi_terakhir)->not->toBeNull()
        ->and($this->balita->status_stunting_terakhir)->not->toBeNull()
        ->and($this->balita->zscore_bbu_terakhir)->not->toBeNull();
});

test('transaksi database di-rollback seluruhnya jika terjadi kegagalan tak terduga saat kalkulasi atau penyimpanan', function (): void {
    $initialCount = PengukuranBalita::count();
    $visitDate = Carbon::today()->toDateString();

    // Mock AntropometriService agar melempar exception di tengah proses transaksi
    $mockService = Mockery::mock(AntropometriService::class);
    $mockService->shouldReceive('analisis')
        ->andThrow(new Exception('Simulasi kegagalan sistematis di tengah transaksi database.'));
    $this->app->instance(AntropometriService::class, $mockService);

    // Kirim request dan tangani exception jika terjadi
    try {
        $this->actingAs($this->kader)->post(route('pengukuran.store'), [
            'balita_id' => $this->balita->id,
            'tanggal_kunjungan' => $visitDate,
            'berat_badan_kg' => 9.6,
            'tinggi_badan_cm' => 75.0,
            'cara_ukur_tinggi' => CaraUkurTinggi::TELENTANG->value,
        ]);
    } catch (Exception) {
        // Exception tertangkap
    }

    // Pastikan tidak ada data pengukuran baru yang tertinggal (Rollback)
    expect(PengukuranBalita::count())->toBe($initialCount);

    // Pastikan status balita tidak berubah / tidak mengalami corrupt data
    $this->balita->refresh();
    expect($this->balita->tanggal_pengukuran_terakhir)->toBeNull()
        ->and($this->balita->status_gizi_terakhir)->toBeNull();
});
