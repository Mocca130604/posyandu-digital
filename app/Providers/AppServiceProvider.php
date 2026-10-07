<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Balita;
use App\Models\ImunisasiBalita;
use App\Models\OrangTua;
use App\Models\PengukuranBalita;
use App\Models\Posyandu;
use App\Policies\BalitaPolicy;
use App\Policies\ImunisasiBalitaPolicy;
use App\Policies\OrangTuaPolicy;
use App\Policies\PengukuranBalitaPolicy;
use App\Policies\PosyanduPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pendaftaran eksplisit Policy otorisasi RBAC
        Gate::policy(Posyandu::class, PosyanduPolicy::class);
        Gate::policy(OrangTua::class, OrangTuaPolicy::class);
        Gate::policy(Balita::class, BalitaPolicy::class);
        Gate::policy(PengukuranBalita::class, PengukuranBalitaPolicy::class);
        Gate::policy(ImunisasiBalita::class, ImunisasiBalitaPolicy::class);
    }
}
