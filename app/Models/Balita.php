<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\KenaikanBb;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Balita extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'balita';

    /**
     * @var array<int, string>
     */
    protected $appends = [
        'usia_bulan',
        'usia_saat_ini',
        'is_zona_merah',
        'kriteria_pmt',
        'is_pindah',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'orang_tua_id',
        'posyandu_id',
        'nik_anak',
        'nama_anak',
        'jenis_kelamin',
        'tanggal_lahir',
        'berat_lahir_kg',
        'panjang_lahir_cm',
        'lingkar_kepala_lahir_cm',
        'tanggal_pengukuran_terakhir',
        'status_gizi_terakhir',
        'status_stunting_terakhir',
        'status_bbtb_terakhir',
        'zscore_bbu_terakhir',
        'zscore_tbu_terakhir',
        'zscore_bbtb_terakhir',
        'status_domisili',
        'keterangan_pindah',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_kelamin' => JenisKelamin::class,
            'tanggal_lahir' => 'date',
            'berat_lahir_kg' => 'decimal:2',
            'panjang_lahir_cm' => 'decimal:1',
            'lingkar_kepala_lahir_cm' => 'decimal:1',
            'tanggal_pengukuran_terakhir' => 'date',
            'status_gizi_terakhir' => StatusGizi::class,
            'status_stunting_terakhir' => StatusStunting::class,
            'status_bbtb_terakhir' => StatusBbtb::class,
            'zscore_bbu_terakhir' => 'decimal:2',
            'zscore_tbu_terakhir' => 'decimal:2',
            'zscore_bbtb_terakhir' => 'decimal:2',
        ];
    }

    /**
     * Relasi ke data orang tua.
     *
     * @return BelongsTo<OrangTua, $this>
     */
    public function orangTua(): BelongsTo
    {
        return $this->belongsTo(OrangTua::class, 'orang_tua_id');
    }

    /**
     * Relasi ke posyandu domisili balita.
     *
     * @return BelongsTo<Posyandu, $this>
     */
    public function posyandu(): BelongsTo
    {
        return $this->belongsTo(Posyandu::class, 'posyandu_id');
    }

    /**
     * Relasi ke seluruh riwayat pengukuran balita.
     *
     * @return HasMany<PengukuranBalita, $this>
     */
    public function pengukuran(): HasMany
    {
        return $this->hasMany(PengukuranBalita::class, 'balita_id')->orderBy('tanggal_kunjungan', 'desc');
    }

    /**
     * Relasi ke data pengukuran terbaru balita.
     *
     * @return HasOne<PengukuranBalita, $this>
     */
    public function pengukuranTerbaru(): HasOne
    {
        return $this->hasOne(PengukuranBalita::class, 'balita_id')->latestOfMany('tanggal_kunjungan');
    }

    /**
     * Relasi ke catatan imunisasi balita.
     *
     * @return HasMany<ImunisasiBalita, $this>
     */
    public function imunisasi(): HasMany
    {
        return $this->hasMany(ImunisasiBalita::class, 'balita_id')->orderBy('tanggal_imunisasi', 'asc');
    }

    /**
     * Hitung usia dalam bulan penuh (completed months) saat ini.
     */
    public function usiaBulan(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->tanggal_lahir === null) {
                    return 0;
                }
                return (int) $this->tanggal_lahir->diffInMonths(Carbon::now());
            }
        );
    }

    /**
     * Deskripsi usia manusiawi (misal: "1 tahun 3 bulan" atau "8 bulan").
     */
    public function usiaSaatIni(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->tanggal_lahir === null) {
                    return '-';
                }

                $diff = $this->tanggal_lahir->diff(Carbon::now());
                $tahun = $diff->y;
                $bulan = $diff->m;

                if ($tahun > 0) {
                    return $bulan > 0 ? "{$tahun} tahun {$bulan} bulan" : "{$tahun} tahun";
                }

                return "{$bulan} bulan";
            }
        );
    }

    /**
     * Menandakan apakah balita saat ini masuk ke Zona Merah kewaspadaan gizi/stunting.
     */
    public function isZonaMerah(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $giziRed = $this->status_gizi_terakhir instanceof StatusGizi && $this->status_gizi_terakhir->isRedZone();
                $stuntingRed = $this->status_stunting_terakhir instanceof StatusStunting && $this->status_stunting_terakhir->isRedZone();
                $bbtbRed = $this->status_bbtb_terakhir instanceof StatusBbtb && $this->status_bbtb_terakhir->isRedZone();

                if ($giziRed || $stuntingRed || $bbtbRed) {
                    return true;
                }

                // Cek riwayat 2 pengukuran terakhir apakah "T" berturut-turut
                $duaPengukuranTerakhir = $this->pengukuran()
                    ->take(2)
                    ->get();

                if ($duaPengukuranTerakhir->count() === 2) {
                    $p1 = $duaPengukuranTerakhir->get(0);
                    $p2 = $duaPengukuranTerakhir->get(1);
                    if ($p1?->kenaikan_bb === KenaikanBb::TETAP_TURUN && $p2?->kenaikan_bb === KenaikanBb::TETAP_TURUN) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    /**
     * Menentukan kriteria Pemberian Makanan Tambahan (PMT) Pemulihan Kemenkes RI:
     * - Stunting (TB/U < -2 SD atau < -3 SD)
     * - Gizi Kurang / Buruk (BB/TB atau BB/U < -2 SD)
     * - Weight Faltering (Kenaikan BB tidak adekuat / T / 2T)
     */
    public function kriteriaPmt(): Attribute
    {
        return Attribute::make(
            get: function (): ?array {
                $isStunting = in_array($this->status_stunting_terakhir?->value, [
                    StatusStunting::STUNTED->value,
                    StatusStunting::SEVERELY_STUNTED->value,
                ], true);

                $isGiziKurang = in_array($this->status_gizi_terakhir?->value, [
                    StatusGizi::GIZI_KURANG->value,
                    StatusGizi::GIZI_BURUK->value,
                ], true) || in_array($this->status_bbtb_terakhir?->value, [
                    StatusBbtb::GIZI_KURANG->value,
                    StatusBbtb::GIZI_BURUK->value,
                ], true);

                // Cek 2 pengukuran terakhir untuk weight faltering (2T)
                $duaPengukuran = $this->pengukuran()->take(2)->get();
                $is2T = false;
                $isT = false;
                if ($duaPengukuran->count() >= 1) {
                    $p1 = $duaPengukuran->get(0);
                    $val1 = $p1?->kenaikan_bb instanceof KenaikanBb ? $p1->kenaikan_bb->value : (string) $p1?->kenaikan_bb;
                    $isT = in_array($val1, ['T', 'tidak_naik', 'tetap_turun'], true);
                }
                if ($duaPengukuran->count() === 2) {
                    $p1 = $duaPengukuran->get(0);
                    $p2 = $duaPengukuran->get(1);
                    $val1 = $p1?->kenaikan_bb instanceof KenaikanBb ? $p1->kenaikan_bb->value : (string) $p1?->kenaikan_bb;
                    $val2 = $p2?->kenaikan_bb instanceof KenaikanBb ? $p2->kenaikan_bb->value : (string) $p2?->kenaikan_bb;
                    if (in_array($val1, ['T', 'tidak_naik', 'tetap_turun'], true) && in_array($val2, ['T', 'tidak_naik', 'tetap_turun'], true)) {
                        $is2T = true;
                    }
                }

                if ($isStunting || $isGiziKurang || $is2T || $isT) {
                    $alasan = [];
                    if ($isStunting) {
                        $alasan[] = $this->status_stunting_terakhir === StatusStunting::SEVERELY_STUNTED ? 'Stunting Berat (Sangat Pendek / TB-U < -3 SD)' : 'Terindikasi Stunting (Pendek / TB-U < -2 SD)';
                    }
                    if ($isGiziKurang) {
                        $alasan[] = 'Status Gizi Kurang / Wasting (BB-TB < -2 SD)';
                    }
                    if ($is2T) {
                        $alasan[] = 'Berat Badan Tidak Naik 2 Bulan Berturut-turut (2T)';
                    } elseif ($isT) {
                        $alasan[] = 'Kenaikan Berat Badan Tidak Adekuat (T)';
                    }

                    return [
                        'layak_pmt' => true,
                        'tipe' => $isStunting ? 'PMT Pemulihan Stunting' : 'PMT Pemulihan Gizi & Tumbuh Kembang',
                        'durasi' => $isStunting ? '90 Hari (3 Bulan)' : '56 - 90 Hari',
                        'alasan' => $alasan,
                        'menu_rekomendasi' => 'Pangan lokal tinggi protein hewani (telur ayam, ikan, ayam, daging sapi, hati ayam, dan susu pertumbuhan).',
                        'tindakan' => 'Daftarkan balita ke program PMT Posyandu & Puskesmas Pembina, serta lakukan evaluasi kenaikan BB mingguan.',
                    ];
                }

                return null;
            }
        );
    }

    /**
     * Cek apakah status domisili balita adalah 'pindah'.
     */
     protected function isPindah(): Attribute
     {
         return Attribute::make(
             get: fn () => ($this->status_domisili ?? 'aktif') === 'pindah'
         );
     }

    /**
     * Scope balita dengan status domisili aktif (bukan pindah).
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('status_domisili')
                ->orWhere('status_domisili', '!=', 'pindah');
        });
    }

    /**
     * Scope balita dengan status domisili pindah ke luar wilayah.
     */
    public function scopePindah(Builder $query): Builder
    {
        return $query->where('status_domisili', 'pindah');
    }

    /**
     * Scope balita berdasarkan posyandu tertentu.
     */
    public function scopeDiPosyandu(Builder $query, int $posyanduId): Builder
    {
        return $query->where('posyandu_id', $posyanduId);
    }

    /**
     * Scope balita dalam zona merah (stunting atau gizi buruk/kurang).
     */
    public function scopeZonaMerah(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereIn('status_gizi_terakhir', [
                StatusGizi::GIZI_BURUK->value,
                StatusGizi::GIZI_KURANG->value,
            ])
            ->orWhereIn('status_stunting_terakhir', [
                StatusStunting::SEVERELY_STUNTED->value,
                StatusStunting::STUNTED->value,
            ])
            ->orWhereIn('status_bbtb_terakhir', [
                StatusBbtb::GIZI_BURUK->value,
                StatusBbtb::GIZI_KURANG->value,
            ]);
        });
    }

    /**
     * Scope balita yang terindikasi stunting (Severely Stunted / Stunted).
     */
    public function scopeStunting(Builder $query): Builder
    {
        return $query->whereIn('status_stunting_terakhir', [
            StatusStunting::SEVERELY_STUNTED->value,
            StatusStunting::STUNTED->value,
        ]);
    }

    /**
     * Scope balita dengan status gizi buruk atau gizi kurang (BB/U).
     */
    public function scopeGiziKurangBuruk(Builder $query): Builder
    {
        return $query->whereIn('status_gizi_terakhir', [
            StatusGizi::GIZI_BURUK->value,
            StatusGizi::GIZI_KURANG->value,
        ]);
    }
}
