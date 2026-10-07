<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use App\Models\Balita;
use App\Models\OrangTua;
use App\Models\Posyandu;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Balita>
 */
class BalitaFactory extends Factory
{
    protected $model = Balita::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenisKelamin = $this->faker->randomElement([JenisKelamin::LAKI_LAKI, JenisKelamin::PEREMPUAN]);

        $namaLakiLaki = [
            'Muhammad Rayyan Alfarizqi', 'Alvaro Kenzi Danendra', 'Raffasya Bilal Hafizh',
            'Arsakha Virendra Shafwan', 'Muhammad El Fatih', 'Keanu Dylan Arkanza',
            'Arka Raditya Pratama', 'Dzaky Narendra Putra', 'Fathan Alghifari',
        ];

        $namaPerempuan = [
            'Aisyah Ayudia Inara', 'Khanza Aqila Humaira', 'Zalina Rayya Shaqueena',
            'Naura Zahrani Almahyra', 'Mikayla Alesha Farzana', 'Khadijah Noura Syifa',
            'Alula Farzana Ayunindya', 'Safiyya Almahyra Putri', 'Nadhira Kayla Azzahra',
        ];

        $namaAnak = $jenisKelamin === JenisKelamin::LAKI_LAKI
            ? $this->faker->randomElement($namaLakiLaki)
            : $this->faker->randomElement($namaPerempuan);

        // Usia antara 1 - 58 bulan
        $tanggalLahir = Carbon::now()->subMonths($this->faker->numberBetween(1, 58))->subDays($this->faker->numberBetween(1, 28));

        // Format NIK: 3276 + 01 + DD (+40 if P) + MM + YY + 0001
        $hari = (int) $tanggalLahir->format('d');
        if ($jenisKelamin === JenisKelamin::PEREMPUAN) {
            $hari += 40;
        }
        $nikAnak = sprintf('327601%02d%s%04d', $hari, $tanggalLahir->format('my'), $this->faker->unique()->numberBetween(1, 9999));

        return [
            'orang_tua_id' => OrangTua::factory(),
            'posyandu_id' => Posyandu::factory(),
            'nik_anak' => $nikAnak,
            'nama_anak' => $namaAnak,
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => $tanggalLahir->toDateString(),
            'berat_lahir_kg' => $this->faker->randomFloat(2, 2.50, 4.00),
            'panjang_lahir_cm' => $this->faker->randomFloat(1, 46.0, 52.5),
            'lingkar_kepala_lahir_cm' => $this->faker->randomFloat(1, 32.0, 36.5),
            'tanggal_pengukuran_terakhir' => Carbon::now()->subDays($this->faker->numberBetween(1, 25))->toDateString(),
            'status_gizi_terakhir' => StatusGizi::GIZI_BAIK,
            'status_stunting_terakhir' => StatusStunting::NORMAL,
            'status_bbtb_terakhir' => StatusBbtb::GIZI_BAIK,
            'zscore_bbu_terakhir' => $this->faker->randomFloat(2, -0.80, 0.90),
            'zscore_tbu_terakhir' => $this->faker->randomFloat(2, -0.70, 0.85),
            'zscore_bbtb_terakhir' => $this->faker->randomFloat(2, -0.50, 0.70),
        ];
    }

    public function lakiLaki(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_kelamin' => JenisKelamin::LAKI_LAKI,
        ]);
    }

    public function perempuan(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_kelamin' => JenisKelamin::PEREMPUAN,
        ]);
    }

    public function stunting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_stunting_terakhir' => StatusStunting::STUNTED,
            'zscore_tbu_terakhir' => -2.45,
        ]);
    }

    public function severelyStunted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_stunting_terakhir' => StatusStunting::SEVERELY_STUNTED,
            'zscore_tbu_terakhir' => -3.25,
        ]);
    }

    public function giziBuruk(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_gizi_terakhir' => StatusGizi::GIZI_BURUK,
            'status_bbtb_terakhir' => StatusBbtb::GIZI_BURUK,
            'zscore_bbu_terakhir' => -3.40,
            'zscore_bbtb_terakhir' => -3.35,
        ]);
    }

    public function giziKurang(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_gizi_terakhir' => StatusGizi::GIZI_KURANG,
            'status_bbtb_terakhir' => StatusBbtb::GIZI_KURANG,
            'zscore_bbu_terakhir' => -2.30,
            'zscore_bbtb_terakhir' => -2.20,
        ]);
    }
}
