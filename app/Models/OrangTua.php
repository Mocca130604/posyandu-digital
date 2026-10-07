<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrangTua extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orang_tua';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'no_kk',
        'nik_ibu',
        'nama_ibu',
        'nik_ayah',
        'nama_ayah',
        'alamat',
        'golongan_darah',
    ];

    /**
     * Relasi ke akun pengguna portal orang tua.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke daftar anak/balita dari orang tua ini.
     *
     * @return HasMany<Balita, $this>
     */
    public function balita(): HasMany
    {
        return $this->hasMany(Balita::class, 'orang_tua_id');
    }

    /**
     * Scope pencarian berdasarkan NIK Ibu atau NIK Ayah atau No KK.
     */
    public function scopeCariNik(Builder $query, string $nik): Builder
    {
        return $query->where(function (Builder $q) use ($nik): void {
            $q->where('nik_ibu', $nik)
                ->orWhere('nik_ayah', $nik)
                ->orWhere('no_kk', $nik);
        });
    }

    /**
     * Scope pencarian berdasarkan Nama Ibu atau Nama Ayah.
     */
    public function scopeCariNama(Builder $query, string $nama): Builder
    {
        return $query->where(function (Builder $q) use ($nama): void {
            $q->where('nama_ibu', 'like', "%{$nama}%")
                ->orWhere('nama_ayah', 'like', "%{$nama}%");
        });
    }
}
