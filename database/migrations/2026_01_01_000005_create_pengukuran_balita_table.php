<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengukuran_balita', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('balita_id')
                ->constrained('balita')
                ->cascadeOnDelete();
            $table->foreignId('kader_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('tanggal_kunjungan');
            $table->smallInteger('usia_bulan');
            $table->decimal('berat_badan_kg', 4, 2);
            $table->decimal('tinggi_badan_cm', 4, 1);
            $table->decimal('lingkar_lengan_atas_cm', 3, 1)->nullable();
            $table->decimal('lingkar_kepala_cm', 4, 1)->nullable();
            $table->string('cara_ukur_tinggi', 20);
            $table->decimal('zscore_bbu', 5, 2)->nullable();
            $table->decimal('zscore_tbu', 5, 2)->nullable();
            $table->decimal('zscore_bbtb', 5, 2)->nullable();
            $table->string('status_gizi', 50)->nullable();
            $table->string('status_stunting', 50)->nullable();
            $table->string('status_bbtb', 50)->nullable();
            $table->boolean('flag_data_tidak_wajar')->default(false);
            $table->string('kenaikan_bb', 5)->nullable();
            $table->boolean('vit_a_diberikan')->default(false);
            $table->boolean('obat_cacing_diberikan')->default(false);
            $table->text('catatan_kader')->nullable();
            $table->timestamps();

            $table->unique(['balita_id', 'tanggal_kunjungan']);
            $table->index(['balita_id', 'tanggal_kunjungan']);
            $table->index('kader_id');
            $table->index('flag_data_tidak_wajar');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE pengukuran_balita ADD CONSTRAINT check_usia_bulan CHECK (usia_bulan >= 0 AND usia_bulan <= 60)");
            DB::statement("ALTER TABLE pengukuran_balita ADD CONSTRAINT check_cara_ukur_tinggi CHECK (cara_ukur_tinggi IN ('telentang', 'berdiri'))");
            DB::statement("ALTER TABLE pengukuran_balita ADD CONSTRAINT check_kenaikan_bb CHECK (kenaikan_bb IS NULL OR kenaikan_bb IN ('N', 'T', 'BARU'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pengukuran_balita');
    }
};
