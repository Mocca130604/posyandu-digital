<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BalitaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImunisasiController;
use App\Http\Controllers\PengukuranController;
use App\Http\Controllers\PosyanduController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - Aplikasi Posyandu Digital
|--------------------------------------------------------------------------
| Seluruh rute dilindungi dengan autentikasi, verifikasi is_active,
| dan RoleMiddleware ('peran') sesuai matriks kewenangan.
*/

// Rute Publik & Tamu
Route::middleware('guest')->group(function (): void {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

// Rute Terotentikasi
Route::middleware(['auth', 'aktif'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard Utama Terpadu (Diarahkan oleh DashboardController berdasarkan peran)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // =========================================================================
    // MODUL BALITA (Pencarian, Profil, Rekam Medis & Kurva KMS)
    // =========================================================================
    Route::get('/balita', [BalitaController::class, 'index'])->name('balita.index');
    Route::get('/balita/{balita}', [BalitaController::class, 'show'])->name('balita.show');
    Route::put('/balita/{balita}/pindah-posyandu', [BalitaController::class, 'pindahPosyandu'])->name('balita.pindah-posyandu');

    // Pendaftaran Balita & Sinkronisasi KMS (Super Admin, Admin Desa, Bidan, Kader)
    Route::middleware(['peran:super_admin,admin_desa,bidan,kader'])->group(function (): void {
        Route::get('/balita-baru/create', [BalitaController::class, 'create'])->name('balita.create');
        Route::post('/balita', [BalitaController::class, 'store'])->name('balita.store');
        Route::post('/balita/{balita}/sinkronisasi-kms', [BalitaController::class, 'sinkronisasiKms'])->name('balita.sinkronisasi-kms');
    });

    // =========================================================================
    // MODUL PENGUKURAN ANTROPOMETRI (Kader, Bidan, Admin Desa, Super Admin)
    // =========================================================================
    Route::middleware(['peran:super_admin,admin_desa,bidan,kader'])->group(function (): void {
        Route::get('/pengukuran/create', [PengukuranController::class, 'create'])->name('pengukuran.create');
        Route::post('/pengukuran/preview', [PengukuranController::class, 'preview'])->name('pengukuran.preview');
        Route::post('/pengukuran', [PengukuranController::class, 'store'])->name('pengukuran.store');
    });

    // =========================================================================
    // MODUL IMUNISASI BALITA
    // =========================================================================
    // Tinjauan & Pelacakan Imunisasi (Kader, Bidan, Admin Desa, Super Admin)
    Route::middleware(['peran:super_admin,admin_desa,bidan,kader'])->group(function (): void {
        Route::get('/imunisasi', [ImunisasiController::class, 'index'])->name('imunisasi.index');
    });

    // Pencatatan & Penghapusan Imunisasi Medis (Khusus Bidan, Admin Desa, Super Admin)
    Route::middleware(['peran:super_admin,admin_desa,bidan'])->group(function (): void {
        Route::post('/imunisasi', [ImunisasiController::class, 'store'])->name('imunisasi.store');
        Route::delete('/imunisasi/{imunisasi}', [ImunisasiController::class, 'destroy'])->name('imunisasi.destroy');
    });

    // =========================================================================
    // PORTAL ORANG TUA (Khusus Peran Orang Tua)
    // =========================================================================
    Route::middleware(['peran:orang_tua'])->prefix('orang-tua')->name('orang-tua.')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // =========================================================================
    // PORTAL ADMINISTRATOR & SUPER ADMIN (Manajemen Pengguna & Posyandu)
    // =========================================================================
    Route::middleware(['peran:super_admin,admin_desa'])->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/posyandu', [PosyanduController::class, 'index'])->name('posyandu.index');
        Route::post('/posyandu', [PosyanduController::class, 'store'])->name('posyandu.store');
        Route::put('/posyandu/{posyandu}', [PosyanduController::class, 'update'])->name('posyandu.update');
        Route::delete('/posyandu/{posyandu}', [PosyanduController::class, 'destroy'])->name('posyandu.destroy');

        Route::get('/pengguna', [UserController::class, 'index'])->name('pengguna.index');
        Route::post('/pengguna', [UserController::class, 'store'])->name('pengguna.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('pengguna.update');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('pengguna.destroy');
    });
});
