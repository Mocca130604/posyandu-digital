<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PeranPengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'Silakan masuk terlebih dahulu.');
        }

        if (!$user->is_active) {
            abort(Response::HTTP_FORBIDDEN, 'Akun Anda dinonaktifkan.');
        }

        $userRole = $user->peran instanceof PeranPengguna ? $user->peran->value : (string) $user->peran;

        // Super Admin memiliki hak akses penuh ke seluruh fitur dan modul
        if ($userRole === 'super_admin' || in_array($userRole, $roles, true)) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');

        return $next($request);
    }
}
