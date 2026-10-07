<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisVaksin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImunisasiBalita extends Model
{
    use HasFactory;

    protected $table = 'imunisasi_balita';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'balita_id',
        'jenis_vaksin',
        'tanggal_imunisasi',
        'petugas_kesehatan',
        'batch_vaksin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_vaksin' => JenisVaksin::class,
            'tanggal_imunisasi' => 'date',
        ];
    }

    /**
     * Relasi ke data balita yang menerima imunisasi.
     *
     * @return BelongsTo<Balita, $this>
     */
    public function balita(): BelongsTo
    {
        return $this->belongsTo(Balita::class, 'balita_id');
    }

    /**
     * Scope berdasarkan jenis vaksin tertentu.
     */
    public function scopeJenisVaksin(Builder $query, JenisVaksin $vaksin): Builder
    {
        return $query->where('jenis_vaksin', $vaksin->value);
    }

    /**
     * Scope imunisasi dalam rentang tanggal tertentu.
     */
    public function scopePeriode(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('tanggal_imunisasi', [$startDate, $endDate]);
    }
}
