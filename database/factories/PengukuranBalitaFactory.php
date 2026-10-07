<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CaraUkurTinggi;
use App\Enums\KenaikanBb;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use App\Models\Balita;
use App\Models\PengukuranBalita;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengukuranBalita>
 */
class PengukuranBalitaFactory extends Factory
{
    protected $model = PengukuranBalita::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $usiaBulan = $this->faker->numberBetween(1, 59);
        $caraUkur = $usiaBulan < 24 ? CaraUkurTinggi::TELENTANG : CaraUkurTinggi::BERDIRI;

        // Perkiraan berat dan tinggi sesuai usia
        $beratEstimasi = 3.5 + ($usiaBulan * 0.28);
        $tinggiEstimasi = 50.0 + ($usiaBulan * 1.05);

        return [
            'balita_id' => Balita::factory(),
            'kader_id' => User::factory()->kader(),
            'tanggal_kunjungan' => Carbon::now()->subDays($this->faker->numberBetween(0, 30))->toDateString(),
            'usia_bulan' => $usiaBulan,
            'berat_badan_kg' => round($beratEstimasi + $this->faker->randomFloat(2, -0.4, 0.4), 2),
            'tinggi_badan_cm' => round($tinggiEstimasi + $this->faker->randomFloat(1, -1.0, 1.0), 1),
            'lingkar_lengan_atas_cm' => round(13.5 + $this->faker->randomFloat(1, -1.5, 2.0), 1),
            'lingkar_kepala_cm' => round(42.0 + ($usiaBulan * 0.15) + $this->faker->randomFloat(1, -0.8, 0.8), 1),
            'cara_ukur_tinggi' => $caraUkur,
            'zscore_bbu' => $this->faker->randomFloat(2, -1.00, 1.00),
            'zscore_tbu' => $this->faker->randomFloat(2, -1.00, 1.00),
            'zscore_bbtb' => $this->faker->randomFloat(2, -1.00, 1.00),
            'status_gizi' => StatusGizi::GIZI_BAIK,
            'status_stunting' => StatusStunting::NORMAL,
            'status_bbtb' => StatusBbtb::GIZI_BAIK,
            'flag_data_tidak_wajar' => false,
            'kenaikan_bb' => KenaikanBb::NAIK,
            'vit_a_diberikan' => $this->faker->boolean(40),
            'obat_cacing_diberikan' => $this->faker->boolean(30),
            'catatan_kader' => 'Pertumbuhan terpantau baik, nafsu makan normal.',
        ];
    }

    public function baru(): static
    {
        return $this->state(fn (array $attributes) => [
            'kenaikan_bb' => KenaikanBb::BARU,
            'catatan_kader' => 'Kunjungan pertama balita di posyandu.',
        ]);
    }

    public function naik(): static
    {
        return $this->state(fn (array $attributes) => [
            'kenaikan_bb' => KenaikanBb::NAIK,
        ]);
    }

    public function tidakNaik(): static
    {
        return $this->state(fn (array $attributes) => [
            'kenaikan_bb' => KenaikanBb::TETAP_TURUN,
            'catatan_kader' => 'Berat badan tetap dibandingkan bulan lalu. Edukasi PMT diberikan.',
        ]);
    }

    public function zonaMerah(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_gizi' => StatusGizi::GIZI_BURUK,
            'status_stunting' => StatusStunting::SEVERELY_STUNTED,
            'status_bbtb' => StatusBbtb::GIZI_BURUK,
            'zscore_bbu' => -3.45,
            'zscore_tbu' => -3.20,
            'zscore_bbtb' => -3.15,
            'kenaikan_bb' => KenaikanBb::TETAP_TURUN,
            'catatan_kader' => 'Perlu rujukan puskesmas segera untuk evaluasi dokter dan ahli gizi.',
        ]);
    }

    public function tidakWajar(): static
    {
        return $this->state(fn (array $attributes) => [
            'flag_data_tidak_wajar' => true,
            'zscore_bbu' => -6.50,
            'catatan_kader' => 'Peringatan: z-score di luar batas biologis wajar. Pengukuran ulang dianjurkan.',
        ]);
    }
}
