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
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('posyandu_id')
                ->nullable()
                ->constrained('posyandu')
                ->nullOnDelete();
            $table->string('nama_lengkap', 150);
            $table->string('email')->unique();
            $table->string('nomor_telepon', 20)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('peran', 30);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['posyandu_id', 'peran']);
            $table->index('is_active');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT check_peran_pengguna CHECK (peran IN ('admin_desa', 'bidan', 'kader', 'orang_tua'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
