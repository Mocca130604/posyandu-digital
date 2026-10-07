<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imunisasi_balita', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('balita_id')
                ->constrained('balita')
                ->cascadeOnDelete();
            $table->string('jenis_vaksin', 50);
            $table->date('tanggal_imunisasi');
            $table->string('petugas_kesehatan', 150);
            $table->string('batch_vaksin', 50)->nullable();
            $table->timestamps();

            $table->unique(['balita_id', 'jenis_vaksin']);
            $table->index(['balita_id', 'tanggal_imunisasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imunisasi_balita');
    }
};
