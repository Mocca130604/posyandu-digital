<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PeranPengguna;
use App\Models\Posyandu;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $namaIndo = [
            'Siti Aminah', 'Dewi Lestari', 'Sri Wahyuni', 'Nurul Hidayah', 'Endang Susanti',
            'Tri Rahayu', 'Ratna Juwita', 'Budi Santoso', 'Agus Prasetyo', 'Hendra Gunawan',
            'Eko Kurniawan', 'Ahmad Fauzi', 'Rina Anggraini', 'Dian Permatasari', 'Maya Safitri',
        ];

        return [
            'posyandu_id' => Posyandu::factory(),
            'nama_lengkap' => $this->faker->randomElement($namaIndo),
            'email' => $this->faker->unique()->safeEmail(),
            'nomor_telepon' => '08' . $this->faker->numberBetween(111111111, 999999999),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password123'),
            'peran' => PeranPengguna::KADER,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function adminDesa(): static
    {
        return $this->state(fn (array $attributes) => [
            'posyandu_id' => null,
            'peran' => PeranPengguna::ADMIN_DESA,
        ]);
    }

    public function bidan(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => PeranPengguna::BIDAN,
        ]);
    }

    public function kader(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => PeranPengguna::KADER,
        ]);
    }

    public function orangTua(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => PeranPengguna::ORANG_TUA,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
