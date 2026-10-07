<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PeranPengguna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Posyandu extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'posyandu';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_posyandu',
        'rt',
        'rw',
        'kelurahan_desa',
        'kecamatan',
        'puskesmas_pembina',
    ];

    /**
     * Relasi ke seluruh pengguna yang terdaftar di posyandu ini.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'posyandu_id');
    }

    /**
     * Relasi ke seluruh kader posyandu.
     *
     * @return HasMany<User, $this>
     */
    public function kader(): HasMany
    {
        return $this->hasMany(User::class, 'posyandu_id')->where('peran', PeranPengguna::KADER->value);
    }

    /**
     * Relasi ke bidan pembina posyandu.
     *
     * @return HasMany<User, $this>
     */
    public function bidan(): HasMany
    {
        return $this->hasMany(User::class, 'posyandu_id')->where('peran', PeranPengguna::BIDAN->value);
    }

    /**
     * Relasi ke seluruh balita yang terdaftar di posyandu ini.
     *
     * @return HasMany<Balita, $this>
     */
    public function balita(): HasMany
    {
        return $this->hasMany(Balita::class, 'posyandu_id');
    }

    /**
     * Relasi ke semua pengukuran balita melalui data balita di posyandu ini.
     *
     * @return HasManyThrough<PengukuranBalita, Balita, $this>
     */
    public function pengukuran(): HasManyThrough
    {
        return $this->hasManyThrough(
            PengukuranBalita::class,
            Balita::class,
            'posyandu_id',
            'balita_id',
            'id',
            'id'
        );
    }

    /**
     * Scope filter berdasarkan kelurahan/desa.
     */
    public function scopeKelurahanDesa(Builder $query, string $kelurahanDesa): Builder
    {
        return $query->where('kelurahan_desa', $kelurahanDesa);
    }

    /**
     * Scope filter berdasarkan kecamatan.
     */
    public function scopeKecamatan(Builder $query, string $kecamatan): Builder
    {
        return $query->where('kecamatan', $kecamatan);
    }
}
