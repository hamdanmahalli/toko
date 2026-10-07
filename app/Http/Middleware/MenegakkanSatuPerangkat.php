<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menegakkan aturan satu akun satu perangkat.
 *
 * 1. Setelah login, session id di-regenerate. Session id baru inilah yang
 *    menjadi "pemilik" sesi aktif pengguna.
 * 2. Setiap request berikutnya dibandingkan dengan `active_session_id`.
 * 3. Bila berbeda, sesi ini bukan pemilik -> keluarkan paksa.
 *
 * Sesi yang belum punya `active_session_id` (akun lama) langsung diadopsi,
 * supaya tidak semua orang logout serentak saat fitur ini dinyalakan.
 */
class MenegakkanSatuPerangkat
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        $sessionId = $request->session()->getId();

        // finalisasi penetapan yang dibuat saat proses login
        if ($request->session()->get('sesi_aktif_menunggu') === $user->getKey()) {
            $user->forceFill(['active_session_id' => $sessionId])->save();
            $request->session()->forget('sesi_aktif_menunggu');
        }

        if ($user->active_session_id === null) {
            $user->forceFill(['active_session_id' => $sessionId])->save();
        } elseif ($user->active_session_id !== $sessionId) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('masuk')
                ->with('pesan', 'Akun Anda sedang dipakai di perangkat lain.');
        }

        return $next($request);
    }
}
