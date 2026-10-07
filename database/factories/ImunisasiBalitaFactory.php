<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JenisVaksin;
use App\Models\Balita;
use App\Models\ImunisasiBalita;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImunisasiBalita>
 */
class ImunisasiBalitaFactory extends Factory
{
    protected $model = ImunisasiBalita::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $petugasList = [
            'Bidan Nurul Aini, S.Tr.Keb',
            'Bidan Siti Rohmah, A.Md.Keb',
            'Bidan Ratna Sari, S.Tr.Keb',
            'dr. Hendra Pratama',
            'Bidan Tri Utami, A.Md.Keb',
        ];

        $vaksin = $this->faker->randomElement(JenisVaksin::cases());
        $batch = 'VAX-' . strtoupper($vaksin->value) . '-' . $this->faker->regexify('[A-Z0-9]{5}');

        return [
            'balita_id' => Balita::factory(),
            'jenis_vaksin' => $vaksin,
            'tanggal_imunisasi' => Carbon::now()->subMonths($this->faker->numberBetween(0, 12))->toDateString(),
            'petugas_kesehatan' => $this->faker->randomElement($petugasList),
            'batch_vaksin' => $batch,
        ];
    }

    public function vaksin(JenisVaksin $vaksin): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_vaksin' => $vaksin,
            'batch_vaksin' => 'VAX-' . strtoupper($vaksin->value) . '-' . $this->faker->regexify('[A-Z0-9]{5}'),
        ]);
    }
}
