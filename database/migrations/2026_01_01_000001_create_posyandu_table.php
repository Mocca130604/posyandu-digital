<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posyandu', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_posyandu', 100);
            $table->string('rt', 5);
            $table->string('rw', 5);
            $table->string('kelurahan_desa', 100);
            $table->string('kecamatan', 100);
            $table->string('puskesmas_pembina', 150);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kecamatan', 'kelurahan_desa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posyandu');
    }
};
