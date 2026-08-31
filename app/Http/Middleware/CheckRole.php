<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! in_array($user->role, $roles)) {
            // Bukan 403 statis — pantulkan otomatis ke dashboard sesuai role yang sedang aktif.
            // Ini terutama menangani kasus Back-navigation lintas akun: begitu ditekan Back
            // ke halaman milik akun lain, sistem langsung melempar balik ke dashboard akun
            // yang sekarang aktif, bukan menampilkan halaman/pesan "tidak memiliki akses".
            return redirect($user->dashboardRoute())
                ->with('warning', 'Anda dialihkan karena halaman tersebut bukan untuk akun Anda saat ini.');
        }

        return $next($request);
    }
}