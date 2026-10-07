<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Posyandu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Posyandu>
 */
class PosyanduFactory extends Factory
{
    protected $model = Posyandu::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $namaKembang = [
            'Mawar', 'Melati', 'Anggrek', 'Kenanga', 'Flamboyan',
            'Cempaka', 'Dahlia', 'Teratai', 'Kamboja', 'Bougenville',
            'Kemuning', 'Kasturi', 'Sedap Malam', 'Asoka', 'Tulip',
        ];

        $kelurahanList = [
            'Sukamaju', 'Mekarjaya', 'Baktijaya', 'Cisalak', 'Tugu',
            'Pasir Gunung Selatan', 'Harjamukti', 'Curug', 'Tapos', 'Cilangkap',
        ];

        return [
            'nama_posyandu' => 'Posyandu ' . $this->faker->randomElement($namaKembang) . ' ' . $this->faker->numberBetween(1, 12),
            'rt' => str_pad((string) $this->faker->numberBetween(1, 15), 3, '0', STR_PAD_LEFT),
            'rw' => str_pad((string) $this->faker->numberBetween(1, 10), 3, '0', STR_PAD_LEFT),
            'kelurahan_desa' => $this->faker->randomElement($kelurahanList),
            'kecamatan' => 'Cimanggis',
            'puskesmas_pembina' => 'Puskesmas Cimanggis',
        ];
    }
}
