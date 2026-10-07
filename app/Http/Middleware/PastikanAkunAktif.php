<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak akun yang dinonaktifkan padahal masih memegang sesi valid.
 */
class PastikanAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // `aktif` tidak nullable di DB, tapi model baru bisa belum punya atribut ini.
        // Hanya `false` yang diartikan sebagai akun dinonaktifkan.
        if ($user !== null && $user->aktif === false) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('masuk')
                ->with('pesan', 'Akun Anda sudah dinonaktifkan. Hubungi pemilik aplikasi.');
        }

        return $next($request);
    }
}
