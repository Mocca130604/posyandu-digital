<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisKelamin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhoLmsReference extends Model
{
    use HasFactory;

    protected $table = 'who_lms_reference';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'indikator',
        'jenis_kelamin',
        'acuan',
        'nilai',
        'l',
        'm',
        's',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_kelamin' => JenisKelamin::class,
            'nilai' => 'decimal:1',
            'l' => 'decimal:6',
            'm' => 'decimal:6',
            's' => 'decimal:6',
        ];
    }

    /**
     * Scope pencarian parameter LMS untuk indikator, jenis kelamin, dan acuan tertentu.
     */
    public function scopeCariAcuan(
        Builder $query,
        string $indikator,
        JenisKelamin $jenisKelamin,
        string $acuan,
        float $nilai
    ): Builder {
        return $query->where('indikator', $indikator)
            ->where('jenis_kelamin', $jenisKelamin->value)
            ->where('acuan', $acuan)
            ->where('nilai', $nilai);
    }

    /**
     * Scope pengurutan tabel acuan untuk keperluan interpolasi linier.
     */
    public function scopeTabel(
        Builder $query,
        string $indikator,
        JenisKelamin $jenisKelamin,
        string $acuan
    ): Builder {
        return $query->where('indikator', $indikator)
            ->where('jenis_kelamin', $jenisKelamin->value)
            ->where('acuan', $acuan)
            ->orderBy('nilai', 'asc');
    }
}
