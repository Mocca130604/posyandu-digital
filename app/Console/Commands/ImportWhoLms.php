<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\JenisKelamin;
use App\Models\WhoLmsReference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SplFileObject;

class ImportWhoLms extends Command
{
    /**
     * Signature command:
     * who:import {indikator} {jenis_kelamin} {acuan} {path}
     *
     * Contoh:
     * php artisan who:import bbu L usia_bulan storage/data/who_bbu_boys.csv
     */
    protected $signature = 'who:import 
                            {indikator : Indikator antropometri (bbu|tbu|bbtb)} 
                            {jenis_kelamin : Jenis kelamin (L|P)} 
                            {acuan : Parameter acuan (usia_bulan|panjang_cm|tinggi_cm)} 
                            {path : Path file CSV referensi WHO}';

    /**
     * Deskripsi dan dokumentasi format CSV.
     */
    protected $description = 'Mengimpor data referensi WHO Child Growth Standards (LMS) dari file CSV.
Format CSV wajib memiliki baris header persis: "nilai,l,m,s"
- nilai: numerik rentang (misal usia bulan 0..60 atau tinggi/panjang cm 45.0..120.0)
- l: parameter Box-Cox Lambda (presisi desimal hingga 6 angka di belakang koma)
- m: parameter Median (presisi desimal hingga 6 angka di belakang koma)
- s: parameter Koefisien Variasi (presisi desimal hingga 6 angka di belakang koma)
Contoh baris data CSV:
nilai,l,m,s
0,0.3487,3.3464,0.14602
1,0.2561,4.4709,0.13395';

    public function handle(): int
    {
        $indikator = strtolower((string) $this->argument('indikator'));
        $jkRaw = strtoupper((string) $this->argument('jenis_kelamin'));
        $acuan = strtolower((string) $this->argument('acuan'));
        $path = (string) $this->argument('path');

        // Validasi argumen indikator
        if (!in_array($indikator, ['bbu', 'tbu', 'bbtb'], true)) {
            $this->error("Indikator '{$indikator}' tidak valid! Pilihan: bbu, tbu, bbtb.");
            return self::FAILURE;
        }

        // Validasi jenis kelamin
        $jenisKelamin = JenisKelamin::tryFrom($jkRaw);
        if ($jenisKelamin === null) {
            $this->error("Jenis kelamin '{$jkRaw}' tidak valid! Pilihan: L (Laki-laki), P (Perempuan).");
            return self::FAILURE;
        }

        // Validasi parameter acuan
        if (!in_array($acuan, ['usia_bulan', 'panjang_cm', 'tinggi_cm'], true)) {
            $this->error("Acuan '{$acuan}' tidak valid! Pilihan: usia_bulan, panjang_cm, tinggi_cm.");
            return self::FAILURE;
        }

        // Validasi keberadaan file
        if (!file_exists($path) || !is_readable($path)) {
            $this->error("File CSV pada path '{$path}' tidak ditemukan atau tidak dapat dibaca!");
            return self::FAILURE;
        }

        $this->info("Memulai validasi dan impor file: {$path}");
        $this->info("Konfigurasi: Indikator={$indikator}, Jenis Kelamin={$jenisKelamin->value}, Acuan={$acuan}");

        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $header = $file->fgetcsv();
        if ($header === false || empty($header)) {
            $this->error('File CSV kosong!');
            return self::FAILURE;
        }

        // Normalisasi header (trim dan lowercase)
        $cleanHeader = array_map(fn ($col) => strtolower(trim((string) $col)), $header);
        $expectedHeader = ['nilai', 'l', 'm', 's'];

        if ($cleanHeader !== $expectedHeader) {
            $this->error(
                'Header CSV tidak sesuai! Diharapkan: ' . implode(',', $expectedHeader) .
                ' | Ditemukan: ' . implode(',', $cleanHeader)
            );
            return self::FAILURE;
        }

        $barisData = [];
        $nomorBaris = 1;
        $totalError = 0;

        while (!$file->eof()) {
            $row = $file->fgetcsv();
            $nomorBaris++;

            if ($row === false || $row === [null] || empty($row)) {
                continue;
            }

            if (count($row) < 4) {
                $this->warn("Baris {$nomorBaris}: Jumlah kolom kurang dari 4. Dilewati.");
                $totalError++;
                continue;
            }

            $nilai = trim((string) $row[0]);
            $l = trim((string) $row[1]);
            $m = trim((string) $row[2]);
            $s = trim((string) $row[3]);

            if (!is_numeric($nilai) || !is_numeric($l) || !is_numeric($m) || !is_numeric($s)) {
                $this->warn("Baris {$nomorBaris}: Nilai non-numerik terdeteksi [nilai={$nilai}, l={$l}, m={$m}, s={$s}]. Dilewati.");
                $totalError++;
                continue;
            }

            $mFloat = (float) $m;
            $sFloat = (float) $s;

            if ($mFloat <= 0) {
                $this->warn("Baris {$nomorBaris}: Median (M) harus positif (> 0). Ditemukan: {$mFloat}. Dilewati.");
                $totalError++;
                continue;
            }

            if ($sFloat <= 0) {
                $this->warn("Baris {$nomorBaris}: Koefisien variasi (S) harus positif (> 0). Ditemukan: {$sFloat}. Dilewati.");
                $totalError++;
                continue;
            }

            $barisData[] = [
                'indikator' => $indikator,
                'jenis_kelamin' => $jenisKelamin->value,
                'acuan' => $acuan,
                'nilai' => round((float) $nilai, 1),
                'l' => round((float) $l, 6),
                'm' => round($mFloat, 6),
                's' => round($sFloat, 6),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($barisData)) {
            $this->error('Tidak ada data valid yang dapat diimpor dari file CSV.');
            return self::FAILURE;
        }

        $this->info('Memproses penyimpanan ke database dengan transaksi...');

        // Eksekusi upsert dalam database transaction
        DB::transaction(function () use ($barisData): void {
            // Batch upsert per 100 baris untuk efisiensi
            $chunks = array_chunk($barisData, 100);
            foreach ($chunks as $chunk) {
                WhoLmsReference::upsert(
                    $chunk,
                    ['indikator', 'jenis_kelamin', 'acuan', 'nilai'],
                    ['l', 'm', 's', 'updated_at']
                );
            }
        });

        $totalSukses = count($barisData);
        $this->newLine();
        $this->info('====================================================');
        $this->info("RINGKASAN IMPOR WHO LMS SELESAI:");
        $this->info(" - Indikator        : {$indikator}");
        $this->info(" - Jenis Kelamin    : {$jenisKelamin->label()} ({$jenisKelamin->value})");
        $this->info(" - Parameter Acuan  : {$acuan}");
        $this->info(" - Baris Valid      : {$totalSukses} baris tersimpan/diperbarui");
        $this->info(" - Baris Bermasalah : {$totalError} baris");
        $this->info('====================================================');

        return self::SUCCESS;
    }
}
