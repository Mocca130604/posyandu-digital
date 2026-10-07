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
        Schema::create('balita', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('orang_tua_id')
                ->constrained('orang_tua')
                ->cascadeOnDelete();
            $table->foreignId('posyandu_id')
                ->constrained('posyandu')
                ->restrictOnDelete();
            $table->char('nik_anak', 16)->nullable()->unique();
            $table->string('nama_anak', 150);
            $table->char('jenis_kelamin', 1);
            $table->date('tanggal_lahir');
            $table->decimal('berat_lahir_kg', 4, 2);
            $table->decimal('panjang_lahir_cm', 4, 1);
            $table->decimal('lingkar_kepala_lahir_cm', 4, 1);

            // Denormalized latest measurement status columns
            $table->date('tanggal_pengukuran_terakhir')->nullable();
            $table->string('status_gizi_terakhir', 50)->nullable();
            $table->string('status_stunting_terakhir', 50)->nullable();
            $table->string('status_bbtb_terakhir', 50)->nullable();
            $table->decimal('zscore_bbu_terakhir', 5, 2)->nullable();
            $table->decimal('zscore_tbu_terakhir', 5, 2)->nullable();
            $table->decimal('zscore_bbtb_terakhir', 5, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['posyandu_id', 'tanggal_lahir']);
            $table->index('orang_tua_id');
            $table->index('status_gizi_terakhir');
            $table->index('status_stunting_terakhir');
            $table->index('status_bbtb_terakhir');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE balita ADD CONSTRAINT check_jenis_kelamin_balita CHECK (jenis_kelamin IN ('L', 'P'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('balita');
    }
};
