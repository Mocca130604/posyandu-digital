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
        Schema::create('who_lms_reference', function (Blueprint $table): void {
            $table->id();
            $table->string('indikator', 10);
            $table->char('jenis_kelamin', 1);
            $table->string('acuan', 20);
            $table->decimal('nilai', 5, 1);
            $table->decimal('l', 10, 6);
            $table->decimal('m', 10, 6);
            $table->decimal('s', 10, 6);
            $table->timestamps();

            $table->unique(
                ['indikator', 'jenis_kelamin', 'acuan', 'nilai'],
                'who_lms_indikator_jk_acuan_nilai_unique'
            );
            $table->index(['indikator', 'jenis_kelamin', 'acuan'], 'who_lms_lookup_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE who_lms_reference ADD CONSTRAINT check_who_indikator CHECK (indikator IN ('bbu', 'tbu', 'bbtb'))");
            DB::statement("ALTER TABLE who_lms_reference ADD CONSTRAINT check_who_jenis_kelamin CHECK (jenis_kelamin IN ('L', 'P'))");
            DB::statement("ALTER TABLE who_lms_reference ADD CONSTRAINT check_who_acuan CHECK (acuan IN ('usia_bulan', 'panjang_cm', 'tinggi_cm'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('who_lms_reference');
    }
};
