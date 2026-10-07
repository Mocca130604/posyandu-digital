<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\KenaikanBb;
use App\Enums\StatusBbtb;
use App\Enums\StatusGizi;
use App\Enums\StatusStunting;

final readonly class HasilAntropometri
{
    /**
     * @param array<int, string> $pesanPeringatan
     */
    public function __construct(
        public int $usiaBulan,
        public float $tinggiBadanKoreksiCm,
        public ?float $zscoreBbu,
        public ?float $zscoreTbu,
        public ?float $zscoreBbtb,
        public StatusGizi $statusGizi,
        public StatusStunting $statusStunting,
        public StatusBbtb $statusBbtb,
        public KenaikanBb $kenaikanBb,
        public bool $flagDataTidakWajar,
        public bool $isZonaMerah,
        public array $pesanPeringatan = [],
    ) {}

    /**
     * Konversi DTO menjadi array untuk persistensi atau respon Inertia/JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'usia_bulan' => $this->usiaBulan,
            'tinggi_badan_koreksi_cm' => $this->tinggiBadanKoreksiCm,
            'zscore_bbu' => $this->zscoreBbu,
            'zscore_tbu' => $this->zscoreTbu,
            'zscore_bbtb' => $this->zscoreBbtb,
            'status_gizi' => $this->statusGizi->value,
            'status_stunting' => $this->statusStunting->value,
            'status_bbtb' => $this->statusBbtb->value,
            'kenaikan_bb' => $this->kenaikanBb->value,
            'flag_data_tidak_wajar' => $this->flagDataTidakWajar,
            'is_zona_merah' => $this->isZonaMerah,
            'pesan_peringatan' => $this->pesanPeringatan,
        ];
    }
}
