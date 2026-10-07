<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('balita', function (Blueprint $table): void {
            if (!Schema::hasColumn('balita', 'status_domisili')) {
                $table->string('status_domisili', 20)->default('aktif');
            }
            if (!Schema::hasColumn('balita', 'keterangan_pindah')) {
                $table->text('keterangan_pindah')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('balita', function (Blueprint $table): void {
            if (Schema::hasColumn('balita', 'status_domisili')) {
                $table->dropColumn('status_domisili');
            }
            if (Schema::hasColumn('balita', 'keterangan_pindah')) {
                $table->dropColumn('keterangan_pindah');
            }
        });
    }
};
