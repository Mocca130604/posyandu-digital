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
        Schema::create('orang_tua', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->char('no_kk', 16);
            $table->char('nik_ibu', 16)->unique();
            $table->string('nama_ibu', 150);
            $table->char('nik_ayah', 16)->nullable();
            $table->string('nama_ayah', 150)->nullable();
            $table->text('alamat');
            $table->string('golongan_darah', 4)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('no_kk');
            $table->index('nik_ayah');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE orang_tua ADD CONSTRAINT check_golongan_darah CHECK (golongan_darah IS NULL OR golongan_darah IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'A', 'B', 'AB', 'O'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orang_tua');
    }
};
