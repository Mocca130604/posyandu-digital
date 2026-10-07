<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * Memastikan pengguna yang sedang masuk berstatus aktif (is_active = true).
     * Jika akun dinonaktifkan saat sedang menggunakan aplikasi, sesi akan dibatalkan
     * secara otomatis dan diarahkan kembali ke halaman login dengan pesan yang jelas.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && !$user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Akun Anda telah dinonaktifkan oleh Administrator Desa. Silakan hubungi kantor desa atau pengurus posyandu.',
            ]);
        }

        return $next($request);
    }
}
