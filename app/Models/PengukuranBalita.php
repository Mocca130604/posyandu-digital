<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CaraUkurTinggi;
use App\Enums\KenaikanBb;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengukuranBalita extends Model
{
    use HasFactory;

    protected $table = 'pengukuran_balita';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'balita_id',
        'kader_id',
        'tanggal_kunjungan',
        'usia_bulan',
        'berat_badan_kg',
        'tinggi_badan_cm',
        'lingkar_lengan_atas_cm',
        'lingkar_kepala_cm',
        'cara_ukur_tinggi',
        'zscore_bbu',
        'zscore_tbu',
        'zscore_bbtb',
        'status_gizi',
        'status_stunting',
        'status_bbtb',
        'flag_data_tidak_wajar',
        'kenaikan_bb',
        'vit_a_diberikan',
        'obat_cacing_diberikan',
        'catatan_kader',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kunjungan' => 'date',
            'usia_bulan' => 'integer',
            'berat_badan_kg' => 'decimal:2',
            'tinggi_badan_cm' => 'decimal:1',
            'lingkar_lengan_atas_cm' => 'decimal:1',
            'lingkar_kepala_cm' => 'decimal:1',
            'cara_ukur_tinggi' => CaraUkurTinggi::class,
            'zscore_bbu' => 'decimal:2',
            'zscore_tbu' => 'decimal:2',
            'zscore_bbtb' => 'decimal:2',
            'status_gizi' => StatusGizi::class,
            'status_stunting' => StatusStunting::class,
            'status_bbtb' => StatusBbtb::class,
            'flag_data_tidak_wajar' => 'boolean',
            'kenaikan_bb' => KenaikanBb::class,
            'vit_a_diberikan' => 'boolean',
            'obat_cacing_diberikan' => 'boolean',
        ];
    }

    /**
     * Relasi ke data balita yang diukur.
     *
     * @return BelongsTo<Balita, $this>
     */
    public function balita(): BelongsTo
    {
        return $this->belongsTo(Balita::class, 'balita_id');
    }

    /**
     * Relasi ke kader yang melakukan pencatatan pengukuran.
     *
     * @return BelongsTo<User, $this>
     */
    public function kader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kader_id');
    }

    /**
     * Menandakan apakah hasil pengukuran ini masuk kategori Zona Merah.
     */
    public function isZonaMerah(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $giziRed = $this->status_gizi instanceof StatusGizi && $this->status_gizi->isRedZone();
                $stuntingRed = $this->status_stunting instanceof StatusStunting && $this->status_stunting->isRedZone();
                $bbtbRed = $this->status_bbtb instanceof StatusBbtb && $this->status_bbtb->isRedZone();

                return $giziRed || $stuntingRed || $bbtbRed;
            }
        );
    }

    /**
     * Scope pengukuran yang masuk Zona Merah (stunting atau gizi buruk/kurang).
     */
    public function scopeZonaMerah(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereIn('status_gizi', [
                StatusGizi::GIZI_BURUK->value,
                StatusGizi::GIZI_KURANG->value,
            ])
            ->orWhereIn('status_stunting', [
                StatusStunting::SEVERELY_STUNTED->value,
                StatusStunting::STUNTED->value,
            ])
            ->orWhereIn('status_bbtb', [
                StatusBbtb::GIZI_BURUK->value,
                StatusBbtb::GIZI_KURANG->value,
            ]);
        });
    }

    /**
     * Scope data yang diberi tanda data tidak wajar (outlier biologis).
     */
    public function scopeTidakWajar(Builder $query): Builder
    {
        return $query->where('flag_data_tidak_wajar', true);
    }

    /**
     * Scope pengukuran dalam periode tanggal tertentu.
     */
    public function scopePeriode(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('tanggal_kunjungan', [$startDate, $endDate]);
    }
}
