<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CaraUkurTinggi;
use App\Enums\JenisKelamin;
use App\Enums\JenisVaksin;
use App\Enums\PeranPengguna;
use App\Models\Balita;
use App\Models\ImunisasiBalita;
use App\Models\OrangTua;
use App\Models\PengukuranBalita;
use App\Models\Posyandu;
use App\Models\User;
use App\Models\WhoLmsReference;
use App\Services\AntropometriService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seeder basis data aplikasi Posyandu Digital.
     */
    public function run(): void
    {
        $this->seedWhoLmsReference();

        // 1. Posyandu Percontohan
        $posyandu = Posyandu::create([
            'nama_posyandu' => 'Posyandu Mawar 03',
            'rt' => '002',
            'rw' => '005',
            'kelurahan_desa' => 'Sukamaju',
            'kecamatan' => 'Cimanggis',
            'puskesmas_pembina' => 'Puskesmas Pembina Cimanggis',
        ]);

        // 2. Akun 1 Admin Desa (Tanpa ikatan posyandu_id)
        User::create([
            'posyandu_id' => null,
            'nama_lengkap' => 'Bambang Sudarmono, S.STP',
            'email' => 'admin@desa.id',
            'nomor_telepon' => '081234567890',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'peran' => PeranPengguna::ADMIN_DESA,
            'is_active' => true,
        ]);

        // 3. Akun 1 Bidan Desa Pembina
        $bidan = User::create([
            'posyandu_id' => $posyandu->id,
            'nama_lengkap' => 'Bidan Siti Rohmah, S.Tr.Keb',
            'email' => 'bidan@desa.id',
            'nomor_telepon' => '081234567891',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'peran' => PeranPengguna::BIDAN,
            'is_active' => true,
        ]);

        // 4. Akun 2 Kader Posyandu
        $kader1 = User::create([
            'posyandu_id' => $posyandu->id,
            'nama_lengkap' => 'Kader Sri Wahyuni',
            'email' => 'kader1@desa.id',
            'nomor_telepon' => '081234567892',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'peran' => PeranPengguna::KADER,
            'is_active' => true,
        ]);

        $kader2 = User::create([
            'posyandu_id' => $posyandu->id,
            'nama_lengkap' => 'Kader Dewi Lestari',
            'email' => 'kader2@desa.id',
            'nomor_telepon' => '081234567893',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'peran' => PeranPengguna::KADER,
            'is_active' => true,
        ]);

        // 5. Akun 3 Orang Tua & Profil Balita
        $dataOrangTua = [
            [
                'email' => 'ortu1@desa.id',
                'nomor_telepon' => '081234567894',
                'nama_lengkap' => 'Siti Fatimah',
                'no_kk' => '3276011204180001',
                'nik_ibu' => '3276015508890001',
                'nama_ibu' => 'Siti Fatimah',
                'nik_ayah' => '3276011503850001',
                'nama_ayah' => 'Ahmad Syafii',
                'alamat' => 'Jl. Kamboja No. 12, RT 002/RW 005 Sukamaju',
                'golongan_darah' => 'O+',
                'anak' => [
                    'nama_anak' => 'Muhammad Rayyan Alfarizqi',
                    'nik_anak' => '3276011501250001',
                    'jenis_kelamin' => JenisKelamin::LAKI_LAKI,
                    'tanggal_lahir' => Carbon::now()->subMonths(9)->subDays(10)->toDateString(),
                    'berat_lahir_kg' => 3.30,
                    'panjang_lahir_cm' => 49.5,
                    'lingkar_kepala_lahir_cm' => 34.0,
                    'bb_base' => 7.20,
                    'tb_base' => 67.5,
                    'growth_rate_bb' => 0.35,
                    'growth_rate_tb' => 1.1,
                ],
            ],
            [
                'email' => 'ortu2@desa.id',
                'nomor_telepon' => '081234567895',
                'nama_lengkap' => 'Nur Halimah',
                'no_kk' => '3276011204180002',
                'nik_ibu' => '3276016001920002',
                'nama_ibu' => 'Nur Halimah',
                'nik_ayah' => '3276012005880002',
                'nama_ayah' => 'Bambang Pamungkas',
                'alamat' => 'Jl. Mawar Melati No. 4B, RT 002/RW 005 Sukamaju',
                'golongan_darah' => 'B+',
                'anak' => [
                    'nama_anak' => 'Aisyah Ayudia Inara',
                    'nik_anak' => '3276015007240002',
                    'jenis_kelamin' => JenisKelamin::PEREMPUAN,
                    'tanggal_lahir' => Carbon::now()->subMonths(14)->subDays(5)->toDateString(),
                    'berat_lahir_kg' => 3.10,
                    'panjang_lahir_cm' => 48.5,
                    'lingkar_kepala_lahir_cm' => 33.5,
                    'bb_base' => 8.40,
                    'tb_base' => 72.0,
                    'growth_rate_bb' => 0.25,
                    'growth_rate_tb' => 1.0,
                ],
            ],
            [
                'email' => 'ortu3@desa.id',
                'nomor_telepon' => '081234567896',
                'nama_lengkap' => 'Dewi Sartika',
                'no_kk' => '3276011204180003',
                'nik_ibu' => '3276014207940003',
                'nama_ibu' => 'Dewi Sartika',
                'nik_ayah' => '3276010502900003',
                'nama_ayah' => 'Hendra Wijaya',
                'alamat' => 'Jl. Anggrek Raya No. 27, RT 002/RW 005 Sukamaju',
                'golongan_darah' => 'A+',
                'anak' => [
                    'nama_anak' => 'Alvaro Kenzi Danendra',
                    'nik_anak' => '3276010411240003',
                    'jenis_kelamin' => JenisKelamin::LAKI_LAKI,
                    'tanggal_lahir' => Carbon::now()->subMonths(11)->subDays(12)->toDateString(),
                    'berat_lahir_kg' => 2.90,
                    'panjang_lahir_cm' => 47.0,
                    'lingkar_kepala_lahir_cm' => 33.0,
                    'bb_base' => 7.80,
                    'tb_base' => 69.0,
                    'growth_rate_bb' => 0.30,
                    'growth_rate_tb' => 0.9,
                ],
            ],
        ];

        $antropometri = new AntropometriService();

        foreach ($dataOrangTua as $item) {
            $userOrtu = User::create([
                'posyandu_id' => $posyandu->id,
                'nama_lengkap' => $item['nama_lengkap'],
                'email' => $item['email'],
                'nomor_telepon' => $item['nomor_telepon'],
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'peran' => PeranPengguna::ORANG_TUA,
                'is_active' => true,
            ]);

            $orangTua = OrangTua::create([
                'user_id' => $userOrtu->id,
                'no_kk' => $item['no_kk'],
                'nik_ibu' => $item['nik_ibu'],
                'nama_ibu' => $item['nama_ibu'],
                'nik_ayah' => $item['nik_ayah'],
                'nama_ayah' => $item['nama_ayah'],
                'alamat' => $item['alamat'],
                'golongan_darah' => $item['golongan_darah'],
            ]);

            $anakInfo = $item['anak'];
            $balita = Balita::create([
                'orang_tua_id' => $orangTua->id,
                'posyandu_id' => $posyandu->id,
                'nik_anak' => $anakInfo['nik_anak'],
                'nama_anak' => $anakInfo['nama_anak'],
                'jenis_kelamin' => $anakInfo['jenis_kelamin']->value,
                'tanggal_lahir' => $anakInfo['tanggal_lahir'],
                'berat_lahir_kg' => $anakInfo['berat_lahir_kg'],
                'panjang_lahir_cm' => $anakInfo['panjang_lahir_cm'],
                'lingkar_kepala_lahir_cm' => $anakInfo['lingkar_kepala_lahir_cm'],
            ]);

            // Buat Riwayat Pengukuran 6 Bulan Terakhir secara Berurutan
            // Dihitung langsung melalui AntropometriService untuk akurasi klinis Permenkes No. 2/2020
            for ($bulanLalu = 5; $bulanLalu >= 0; $bulanLalu--) {
                $tglKunjungan = Carbon::now()->subMonths($bulanLalu)->startOfMonth()->addDays(10);
                $step = 5 - $bulanLalu;

                $bbSekarang = round($anakInfo['bb_base'] + ($step * $anakInfo['growth_rate_bb']), 2);
                $tbSekarang = round($anakInfo['tb_base'] + ($step * $anakInfo['growth_rate_tb']), 1);
                $caraUkur = CaraUkurTinggi::TELENTANG;

                $analisis = $antropometri->analisis($balita, [
                    'tanggal_kunjungan' => $tglKunjungan,
                    'berat_badan_kg' => $bbSekarang,
                    'tinggi_badan_cm' => $tbSekarang,
                    'cara_ukur_tinggi' => $caraUkur,
                    'lingkar_lengan_atas_cm' => 14.5,
                    'lingkar_kepala_cm' => 44.0,
                ]);

                PengukuranBalita::create([
                    'balita_id' => $balita->id,
                    'kader_id' => ($step % 2 === 0) ? $kader1->id : $kader2->id,
                    'tanggal_kunjungan' => $tglKunjungan->toDateString(),
                    'usia_bulan' => $analisis->usiaBulan,
                    'berat_badan_kg' => $bbSekarang,
                    'tinggi_badan_cm' => $analisis->tinggiBadanKoreksiCm,
                    'lingkar_lengan_atas_cm' => 14.5,
                    'lingkar_kepala_cm' => 44.0,
                    'cara_ukur_tinggi' => $caraUkur->value,
                    'zscore_bbu' => $analisis->zscoreBbu,
                    'zscore_tbu' => $analisis->zscoreTbu,
                    'zscore_bbtb' => $analisis->zscoreBbtb,
                    'status_gizi' => $analisis->statusGizi->value,
                    'status_stunting' => $analisis->statusStunting->value,
                    'status_bbtb' => $analisis->statusBbtb->value,
                    'flag_data_tidak_wajar' => $analisis->flagDataTidakWajar,
                    'kenaikan_bb' => $analisis->kenaikanBb->value,
                    'vit_a_diberikan' => ($tglKunjungan->month === 2 || $tglKunjungan->month === 8),
                    'obat_cacing_diberikan' => ($tglKunjungan->month === 8),
                    'catatan_kader' => 'Pertumbuhan terpantau baik. Edukasi ASI eksklusif dan MP-ASI seimbang.',
                ]);

                // Update kolom denormalisasi status terakhir di tabel balita
                if ($bulanLalu === 0) {
                    $balita->update([
                        'tanggal_pengukuran_terakhir' => $tglKunjungan->toDateString(),
                        'status_gizi_terakhir' => $analisis->statusGizi,
                        'status_stunting_terakhir' => $analisis->statusStunting,
                        'status_bbtb_terakhir' => $analisis->statusBbtb,
                        'zscore_bbu_terakhir' => $analisis->zscoreBbu,
                        'zscore_tbu_terakhir' => $analisis->zscoreTbu,
                        'zscore_bbtb_terakhir' => $analisis->zscoreBbtb,
                    ]);
                }
            }

            // Catat Imunisasi Rutin Lengkap
            $daftarVaksin = [
                JenisVaksin::HB0,
                JenisVaksin::BCG,
                JenisVaksin::POLIO_1,
                JenisVaksin::DPT_HB_HIB_1,
                JenisVaksin::POLIO_2,
                JenisVaksin::PCV_1,
            ];

            foreach ($daftarVaksin as $idx => $vaksin) {
                ImunisasiBalita::create([
                    'balita_id' => $balita->id,
                    'jenis_vaksin' => $vaksin->value,
                    'tanggal_imunisasi' => Carbon::parse($balita->tanggal_lahir)->addMonths($vaksin->usiaPemberianBulan())->toDateString(),
                    'petugas_kesehatan' => $bidan->nama_lengkap,
                    'batch_vaksin' => 'BIO-FARMA-' . $vaksin->value . '-0' . ($idx + 1),
                ]);
            }
        }
    }

    /**
     * Seed basis data acuan baku LMS WHO (Permenkes No. 2 Tahun 2020)
     * Mencakup rentang lengkap 0 s.d. 60 bulan dan panjang/tinggi 45.0 s.d. 120.0 cm.
     */
    public function seedWhoLmsReference(): void
    {
        $batch = [];

        // 1. BB/U Laki-laki (0 - 60 Bulan)
        for ($u = 0; $u <= 60; $u++) {
            if ($u <= 12) {
                $m = 3.34 + ($u * 0.52);
            } elseif ($u <= 24) {
                $m = 9.58 + (($u - 12) * 0.22);
            } else {
                $m = 12.22 + (($u - 24) * 0.17);
            }
            $batch[] = ['indikator' => 'bbu', 'jenis_kelamin' => 'L', 'acuan' => 'usia_bulan', 'nilai' => (float) $u, 'l' => 0.35, 'm' => round($m, 4), 's' => 0.11];
        }

        // 2. BB/U Perempuan (0 - 60 Bulan)
        for ($u = 0; $u <= 60; $u++) {
            if ($u <= 12) {
                $m = 3.23 + ($u * 0.47);
            } elseif ($u <= 24) {
                $m = 8.87 + (($u - 12) * 0.22);
            } else {
                $m = 11.51 + (($u - 24) * 0.185);
            }
            $batch[] = ['indikator' => 'bbu', 'jenis_kelamin' => 'P', 'acuan' => 'usia_bulan', 'nilai' => (float) $u, 'l' => 0.38, 'm' => round($m, 4), 's' => 0.12];
        }

        // 3. TB/U Laki-laki (0 - 60 Bulan)
        for ($u = 0; $u <= 60; $u++) {
            if ($u <= 12) {
                $m = 49.9 + ($u * 2.15);
            } elseif ($u <= 24) {
                $m = 75.7 + (($u - 12) * 1.01);
            } else {
                $m = 87.8 + (($u - 24) * 0.62);
            }
            $batch[] = ['indikator' => 'tbu', 'jenis_kelamin' => 'L', 'acuan' => 'usia_bulan', 'nilai' => (float) $u, 'l' => 1.00, 'm' => round($m, 4), 's' => 0.04];
        }

        // 4. TB/U Perempuan (0 - 60 Bulan)
        for ($u = 0; $u <= 60; $u++) {
            if ($u <= 12) {
                $m = 49.1 + ($u * 2.08);
            } elseif ($u <= 24) {
                $m = 74.1 + (($u - 12) * 1.03);
            } else {
                $m = 86.5 + (($u - 24) * 0.64);
            }
            $batch[] = ['indikator' => 'tbu', 'jenis_kelamin' => 'P', 'acuan' => 'usia_bulan', 'nilai' => (float) $u, 'l' => 1.00, 'm' => round($m, 4), 's' => 0.04];
        }

        // 5. BB/TB Laki-laki & Perempuan: panjang_cm (45.0 - 110.0 cm)
        for ($p = 450; $p <= 1100; $p += 5) {
            $cm = $p / 10.0;
            $mLaki = 2.4 + (($cm - 45.0) * 0.245);
            $mPerempuan = 2.3 + (($cm - 45.0) * 0.24);

            $batch[] = ['indikator' => 'bbtb', 'jenis_kelamin' => 'L', 'acuan' => 'panjang_cm', 'nilai' => $cm, 'l' => -0.35, 'm' => round($mLaki, 4), 's' => 0.085];
            $batch[] = ['indikator' => 'bbtb', 'jenis_kelamin' => 'P', 'acuan' => 'panjang_cm', 'nilai' => $cm, 'l' => -0.38, 'm' => round($mPerempuan, 4), 's' => 0.085];
        }

        // 6. BB/TB Laki-laki & Perempuan: tinggi_cm (65.0 - 120.0 cm)
        for ($t = 650; $t <= 1200; $t += 5) {
            $cm = $t / 10.0;
            $mLaki = 7.4 + (($cm - 65.0) * 0.25);
            $mPerempuan = 7.1 + (($cm - 65.0) * 0.245);

            $batch[] = ['indikator' => 'bbtb', 'jenis_kelamin' => 'L', 'acuan' => 'tinggi_cm', 'nilai' => $cm, 'l' => -0.35, 'm' => round($mLaki, 4), 's' => 0.085];
            $batch[] = ['indikator' => 'bbtb', 'jenis_kelamin' => 'P', 'acuan' => 'tinggi_cm', 'nilai' => $cm, 'l' => -0.38, 'm' => round($mPerempuan, 4), 's' => 0.085];
        }

        foreach (array_chunk($batch, 200) as $chunk) {
            WhoLmsReference::upsert(
                $chunk,
                ['indikator', 'jenis_kelamin', 'acuan', 'nilai'],
                ['l', 'm', 's']
            );
        }
    }
}
