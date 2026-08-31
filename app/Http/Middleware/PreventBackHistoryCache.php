<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistoryCache
{
    /**
     * Cegah browser menampilkan halaman ini dari cache lokal (bfcache) saat tombol
     * Back ditekan — supaya browser selalu minta ulang ke server, sehingga middleware
     * 'guest'/'force.password.change' selalu sempat jalan dan redirect sesuai kondisi terkini.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}