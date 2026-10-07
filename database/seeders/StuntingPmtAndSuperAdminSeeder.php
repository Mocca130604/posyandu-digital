<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CaraUkurTinggi;
use App\Enums\JenisKelamin;
use App\Enums\PeranPengguna;
use App\Models\Balita;
use App\Models\OrangTua;
use App\Models\PengukuranBalita;
use App\Models\Posyandu;
use App\Models\User;
use App\Services\AntropometriService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StuntingPmtAndSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        (new DatabaseSeeder())->seedWhoLmsReference();

        $posyandu = Posyandu::first();
        if (!$posyandu) {
            $posyandu = Posyandu::create([
                'nama_posyandu' => 'Posyandu Mawar 03',
                'rt' => '002',
                'rw' => '005',
                'kelurahan_desa' => 'Sukamaju',
                'kecamatan' => 'Cimanggis',
                'puskesmas_pembina' => 'Puskesmas Pembina Cimanggis',
            ]);
        }

        $kader = User::where('peran', PeranPengguna::KADER)->first() ?? User::first();

        // 1. Akun Super Admin
        User::updateOrCreate(
            ['email' => 'superadmin@desa.id'],
            [
                'posyandu_id' => null,
                'nama_lengkap' => 'Super Administrator Posyandu',
                'nomor_telepon' => '081198765432',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'peran' => PeranPengguna::SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        $antropometri = new AntropometriService();

        // 2. Data Balita Stunting & PMT
        $dataKasus = [
            [
                'parent' => [
                    'email' => 'ibu.fadhil@desa.id',
                    'nomor_telepon' => '081234567901',
                    'nama_lengkap' => 'Ratna Dewi',
                    'no_kk' => '3276011204180011',
                    'nik_ibu' => '3276015508890011',
                    'nama_ibu' => 'Ratna Dewi',
                    'nik_ayah' => '3276011503850011',
                    'nama_ayah' => 'Rahmat Hidayat',
                    'alamat' => 'RT 003 / RW 005 Kelurahan Sukamaju',
                    'golongan_darah' => 'B+',
                ],
                'anak' => [
                    'nama_anak' => 'Muhammad Fadhil Rizky',
                    'nik_anak' => '3276011501250011',
                    'jenis_kelamin' => JenisKelamin::LAKI_LAKI,
                    'tanggal_lahir' => Carbon::now()->subMonths(18)->subDays(10)->toDateString(),
                    'berat_lahir_kg' => 2.60,
                    'panjang_lahir_cm' => 46.5,
                    'lingkar_kepala_lahir_cm' => 32.5,
                ],
                // Riwayat pengukuran bulanan (tinggi badan rendah -> Stunting, BB/TB gizi kurang, BB stagnan)
                // Usia 18 bln, acuan tinggi laki2 normal ~ 82 cm. Tinggi 74.0 cm -> Stunted (Z < -2.5)
                'pengukuran' => [
                    ['bulan_lalu' => 3, 'bb' => 7.6, 'tb' => 72.8, 'catatan' => 'Pertumbuhan tinggi badan di bawah kurva hijau. Diberikan konseling gizi protein.'],
                    ['bulan_lalu' => 2, 'bb' => 7.7, 'tb' => 73.2, 'catatan' => 'Kenaikan BB tidak adekuat (T). Ibu diedukasi pemberian makanan tinggi kalori.'],
                    ['bulan_lalu' => 1, 'bb' => 7.7, 'tb' => 73.8, 'catatan' => 'BB stagnan (2T). Terindikasi stunting dan gizi kurang.'],
                    ['bulan_lalu' => 0, 'bb' => 7.8, 'tb' => 74.2, 'catatan' => 'Konfirmasi Stunting & Wasting. Memenuhi syarat PMT Pemulihan 90 hari Kemenkes.'],
                ],
            ],
            [
                'parent' => [
                    'email' => 'ibu.bilqis@desa.id',
                    'nomor_telepon' => '081234567902',
                    'nama_lengkap' => 'Maya Anggraini',
                    'no_kk' => '3276011204180012',
                    'nik_ibu' => '3276016001920012',
                    'nama_ibu' => 'Maya Anggraini',
                    'nik_ayah' => '3276012005880012',
                    'nama_ayah' => 'Dedi Prasetyo',
                    'alamat' => 'RT 001 / RW 005 Kelurahan Sukamaju',
                    'golongan_darah' => 'O+',
                ],
                'anak' => [
                    'nama_anak' => 'Bilqis Humaira Azzahra',
                    'nik_anak' => '3276015007240012',
                    'jenis_kelamin' => JenisKelamin::PEREMPUAN,
                    'tanggal_lahir' => Carbon::now()->subMonths(22)->subDays(15)->toDateString(),
                    'berat_lahir_kg' => 2.40,
                    'panjang_lahir_cm' => 45.0,
                    'lingkar_kepala_lahir_cm' => 32.0,
                ],
                // Usia 22 bulan perempuan, TB normal ~ 84 cm. Tinggi 75.0 cm -> Z-Score TB/U < -3.2 (Severely Stunted)
                'pengukuran' => [
                    ['bulan_lalu' => 3, 'bb' => 7.8, 'tb' => 73.5, 'catatan' => 'Panjang badan berada jauh di bawah garis merah KMS (Severely Stunted).'],
                    ['bulan_lalu' => 2, 'bb' => 7.9, 'tb' => 74.2, 'catatan' => 'Kondisi stunting berat dipantau ketat oleh Bidan Desa.'],
                    ['bulan_lalu' => 1, 'bb' => 8.0, 'tb' => 74.8, 'catatan' => 'Dirujuk ke Puskesmas Pembina untuk evaluasi dokter spesialis anak.'],
                    ['bulan_lalu' => 0, 'bb' => 8.1, 'tb' => 75.2, 'catatan' => 'Severely Stunted. Penerima paket PMT Pemulihan Intensif & suplemen mikronutrien.'],
                ],
            ],
            [
                'parent' => [
                    'email' => 'ibu.kenzo@desa.id',
                    'nomor_telepon' => '081234567903',
                    'nama_lengkap' => 'Rina Marlina',
                    'no_kk' => '3276011204180013',
                    'nik_ibu' => '3276014207940013',
                    'nama_ibu' => 'Rina Marlina',
                    'nik_ayah' => '3276010502900013',
                    'nama_ayah' => 'Ferry Gunawan',
                    'alamat' => 'RT 004 / RW 005 Kelurahan Sukamaju',
                    'golongan_darah' => 'A+',
                ],
                'anak' => [
                    'nama_anak' => 'Kenzo Arkananta',
                    'nik_anak' => '3276010411240013',
                    'jenis_kelamin' => JenisKelamin::LAKI_LAKI,
                    'tanggal_lahir' => Carbon::now()->subMonths(10)->subDays(8)->toDateString(),
                    'berat_lahir_kg' => 2.80,
                    'panjang_lahir_cm' => 47.0,
                    'lingkar_kepala_lahir_cm' => 33.0,
                ],
                // Usia 10 bulan laki-laki. BB 6.7 kg (normal ~ 9.2 kg), BB/TB -2.4 SD (Gizi Kurang), Kenaikan BB Tetap/Turun (2T)
                'pengukuran' => [
                    ['bulan_lalu' => 2, 'bb' => 6.8, 'tb' => 68.5, 'catatan' => 'BB awal pemantauan.'],
                    ['bulan_lalu' => 1, 'bb' => 6.7, 'tb' => 69.2, 'catatan' => 'BB turun 100 gram (T). Nafsu makan balita menurun pasca demam.'],
                    ['bulan_lalu' => 0, 'bb' => 6.7, 'tb' => 69.8, 'catatan' => 'BB tetap tidak naik 2 bulan berturut-turut (2T/Weight Faltering). Prioritas PMT.'],
                ],
            ],
        ];

        foreach ($dataKasus as $kasus) {
            $pData = $kasus['parent'];
            $userOrtu = User::updateOrCreate(
                ['email' => $pData['email']],
                [
                    'posyandu_id' => $posyandu->id,
                    'nama_lengkap' => $pData['nama_lengkap'],
                    'nomor_telepon' => $pData['nomor_telepon'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password123'),
                    'peran' => PeranPengguna::ORANG_TUA,
                    'is_active' => true,
                ]
            );

            $orangTua = OrangTua::updateOrCreate(
                ['user_id' => $userOrtu->id],
                [
                    'no_kk' => $pData['no_kk'],
                    'nik_ibu' => $pData['nik_ibu'],
                    'nama_ibu' => $pData['nama_ibu'],
                    'nik_ayah' => $pData['nik_ayah'],
                    'nama_ayah' => $pData['nama_ayah'],
                    'alamat' => $pData['alamat'],
                    'golongan_darah' => $pData['golongan_darah'],
                ]
            );

            $aData = $kasus['anak'];
            $balita = Balita::updateOrCreate(
                ['nik_anak' => $aData['nik_anak']],
                [
                    'orang_tua_id' => $orangTua->id,
                    'posyandu_id' => $posyandu->id,
                    'nama_anak' => $aData['nama_anak'],
                    'jenis_kelamin' => $aData['jenis_kelamin']->value,
                    'tanggal_lahir' => $aData['tanggal_lahir'],
                    'berat_lahir_kg' => $aData['berat_lahir_kg'],
                    'panjang_lahir_cm' => $aData['panjang_lahir_cm'],
                    'lingkar_kepala_lahir_cm' => $aData['lingkar_kepala_lahir_cm'],
                ]
            );

            // Bersihkan pengukuran lama jika ada agar tidak duplikat saat re-seed
            PengukuranBalita::where('balita_id', $balita->id)->delete();

            foreach ($kasus['pengukuran'] as $pItem) {
                $tglKunjungan = Carbon::now()->subMonths($pItem['bulan_lalu'])->startOfMonth()->addDays(8);
                $caraUkur = CaraUkurTinggi::TELENTANG;

                $analisis = $antropometri->analisis($balita, [
                    'tanggal_kunjungan' => $tglKunjungan,
                    'berat_badan_kg' => $pItem['bb'],
                    'tinggi_badan_cm' => $pItem['tb'],
                    'cara_ukur_tinggi' => $caraUkur,
                    'lingkar_lengan_atas_cm' => 12.0,
                    'lingkar_kepala_cm' => 43.0,
                ]);

                PengukuranBalita::create([
                    'balita_id' => $balita->id,
                    'kader_id' => $kader->id,
                    'tanggal_kunjungan' => $tglKunjungan->toDateString(),
                    'usia_bulan' => $analisis->usiaBulan,
                    'berat_badan_kg' => $pItem['bb'],
                    'tinggi_badan_cm' => $analisis->tinggiBadanKoreksiCm,
                    'lingkar_lengan_atas_cm' => 12.0,
                    'lingkar_kepala_cm' => 43.0,
                    'cara_ukur_tinggi' => $caraUkur->value,
                    'zscore_bbu' => $analisis->zscoreBbu,
                    'zscore_tbu' => $analisis->zscoreTbu,
                    'zscore_bbtb' => $analisis->zscoreBbtb,
                    'status_gizi' => $analisis->statusGizi->value,
                    'status_stunting' => $analisis->statusStunting->value,
                    'status_bbtb' => $analisis->statusBbtb->value,
                    'flag_data_tidak_wajar' => $analisis->flagDataTidakWajar,
                    'kenaikan_bb' => $analisis->kenaikanBb->value,
                    'vit_a_diberikan' => false,
                    'obat_cacing_diberikan' => false,
                    'catatan_kader' => $pItem['catatan'],
                ]);

                if ($pItem['bulan_lalu'] === 0) {
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
        }
    }
}
