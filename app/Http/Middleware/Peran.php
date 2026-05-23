<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pemeriksa peran pengguna.
 *
 * Pemakaian pada route:
 *   Route::middleware(['auth','peran:admin,wd3'])->group(...)
 *
 * Aturan keputusan:
 *   - Belum login → diarahkan ke halaman login.
 *   - Login namun peran tidak ada di daftar → 403.
 *   - Sesuai → lanjut.
 */
class Peran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $pengguna = $request->user();

        if (! $pengguna) {
            return redirect()->guest(route('login'));
        }

        if (! in_array($pengguna->peran, $peran, true)) {
            abort(403, 'Peran Anda tidak diizinkan mengakses sumber daya ini.');
        }

        return $next($request);
    }
}
