<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PeranPengguna;
use App\Models\OrangTua;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrangTua>
 */
class OrangTuaFactory extends Factory
{
    protected $model = OrangTua::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $namaIbuList = [
            'Siti Fatimah', 'Nur Halimah', 'Dewi Sartika', 'Rini Wulandari', 'Sri Astuti',
            'Mega Utami', 'Tri Lestari', 'Ayu Wandira', 'Fitri Handayani', 'Lilis Suryani',
        ];

        $namaAyahList = [
            'Agus Setiawan', 'Bambang Pamungkas', 'Joko Widodo', 'Ahmad Syafii', 'Dedi Mulyadi',
            'Rudi Hartono', 'Surya Saputra', 'Hendra Wijaya', 'Fajar Pratama', 'Doni Kusuma',
        ];

        $golonganDarahList = ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'];

        // Format NIK: 3276 (Depok) + 01 (Kecamatan) + Tgl/Bln/Thn + 4 digit nomor urut
        $tglLahirIbu = $this->faker->dateTimeBetween('-40 years', '-20 years');
        $hariIbu = (int) $tglLahirIbu->format('d') + 40; // NIK wanita: tanggal + 40
        $nikIbu = sprintf('327601%02d%s%04d', $hariIbu, $tglLahirIbu->format('my'), $this->faker->unique()->numberBetween(1, 9999));

        $tglLahirAyah = $this->faker->dateTimeBetween('-45 years', '-22 years');
        $nikAyah = sprintf('327601%02d%s%04d', (int) $tglLahirAyah->format('d'), $tglLahirAyah->format('my'), $this->faker->unique()->numberBetween(1, 9999));

        $noKk = sprintf('327601%02d%02d%02d%04d', $this->faker->numberBetween(1, 28), $this->faker->numberBetween(1, 12), $this->faker->numberBetween(10, 24), $this->faker->numberBetween(1, 9999));

        return [
            'user_id' => User::factory()->state(['peran' => PeranPengguna::ORANG_TUA]),
            'no_kk' => $noKk,
            'nik_ibu' => $nikIbu,
            'nama_ibu' => $this->faker->randomElement($namaIbuList),
            'nik_ayah' => $nikAyah,
            'nama_ayah' => $this->faker->randomElement($namaAyahList),
            'alamat' => 'Jl. ' . $this->faker->streetName() . ' No. ' . $this->faker->buildingNumber() . ', RT ' . str_pad((string) $this->faker->numberBetween(1, 10), 3, '0', STR_PAD_LEFT) . '/RW ' . str_pad((string) $this->faker->numberBetween(1, 10), 3, '0', STR_PAD_LEFT),
            'golongan_darah' => $this->faker->randomElement($golonganDarahList),
        ];
    }
}
