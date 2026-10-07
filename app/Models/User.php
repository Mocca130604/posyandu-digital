<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PeranPengguna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'posyandu_id',
        'nama_lengkap',
        'email',
        'nomor_telepon',
        'email_verified_at',
        'password',
        'peran',
        'is_active',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'peran' => PeranPengguna::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke posyandu tempat tugas pengguna.
     *
     * @return BelongsTo<Posyandu, $this>
     */
    public function posyandu(): BelongsTo
    {
        return $this->belongsTo(Posyandu::class, 'posyandu_id');
    }

    /**
     * Relasi data profil orang tua jika peran = orang_tua.
     *
     * @return HasOne<OrangTua, $this>
     */
    public function orangTua(): HasOne
    {
        return $this->hasOne(OrangTua::class, 'user_id');
    }

    /**
     * Relasi ke seluruh pengukuran yang diinput oleh kader ini.
     *
     * @return HasMany<PengukuranBalita, $this>
     */
    public function pengukuranDibuat(): HasMany
    {
        return $this->hasMany(PengukuranBalita::class, 'kader_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->peran === PeranPengguna::SUPER_ADMIN;
    }

    public function isAdminDesa(): bool
    {
        return $this->peran === PeranPengguna::ADMIN_DESA || $this->peran === PeranPengguna::SUPER_ADMIN;
    }

    public function isBidan(): bool
    {
        return $this->peran === PeranPengguna::BIDAN;
    }

    public function isKader(): bool
    {
        return $this->peran === PeranPengguna::KADER;
    }

    public function isOrangTua(): bool
    {
        return $this->peran === PeranPengguna::ORANG_TUA;
    }

    /**
     * Scope pengguna berdasarkan peran tertentu.
     */
    public function scopePeran(Builder $query, PeranPengguna $peran): Builder
    {
        return $query->where('peran', $peran->value);
    }

    /**
     * Scope pengguna yang berstatus aktif.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pengguna pada posyandu tertentu.
     */
    public function scopeDiPosyandu(Builder $query, int $posyanduId): Builder
    {
        return $query->where('posyandu_id', $posyanduId);
    }
}
