<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Models\WhoLmsReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhoLmsReference>
 */
class WhoLmsReferenceFactory extends Factory
{
    protected $model = WhoLmsReference::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'indikator' => 'bbu',
            'jenis_kelamin' => JenisKelamin::LAKI_LAKI,
            'acuan' => 'usia_bulan',
            'nilai' => 0.0,
            'l' => 0.348700,
            'm' => 3.346400,
            's' => 0.146020,
        ];
    }

    public function bbu(JenisKelamin $jk, float $usiaBulan, float $l, float $m, float $s): static
    {
        return $this->state(fn (array $attributes) => [
            'indikator' => 'bbu',
            'jenis_kelamin' => $jk,
            'acuan' => 'usia_bulan',
            'nilai' => $usiaBulan,
            'l' => $l,
            'm' => $m,
            's' => $s,
        ]);
    }

    public function tbu(JenisKelamin $jk, float $usiaBulan, float $l, float $m, float $s): static
    {
        return $this->state(fn (array $attributes) => [
            'indikator' => 'tbu',
            'jenis_kelamin' => $jk,
            'acuan' => 'usia_bulan',
            'nilai' => $usiaBulan,
            'l' => $l,
            'm' => $m,
            's' => $s,
        ]);
    }

    public function bbtbPanjang(JenisKelamin $jk, float $panjangCm, float $l, float $m, float $s): static
    {
        return $this->state(fn (array $attributes) => [
            'indikator' => 'bbtb',
            'jenis_kelamin' => $jk,
            'acuan' => 'panjang_cm',
            'nilai' => $panjangCm,
            'l' => $l,
            'm' => $m,
            's' => $s,
        ]);
    }

    public function bbtbTinggi(JenisKelamin $jk, float $tinggiCm, float $l, float $m, float $s): static
    {
        return $this->state(fn (array $attributes) => [
            'indikator' => 'bbtb',
            'jenis_kelamin' => $jk,
            'acuan' => 'tinggi_cm',
            'nilai' => $tinggiCm,
            'l' => $l,
            'm' => $m,
            's' => $s,
        ]);
    }
}
