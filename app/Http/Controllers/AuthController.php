<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Batas percobaan login sebelum dikunci sementara. */
    private const BATAS_PERCOBAAN = 5;

    private const KUNCI_SELAMA_DETIK = 60;

    public function formMasuk(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('beranda');
        }

        return view('auth.masuk');
    }

    public function masuk(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'ingat' => ['nullable', 'boolean'],
        ]);

        $kunci = Str::transliterate(Str::lower($kredensial['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($kunci, self::BATAS_PERCOBAAN)) {
            $sisa = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan. Coba lagi dalam {$sisa} detik.",
            ]);
        }

        $ingat = (bool) ($kredensial['ingat'] ?? false);

        if (! Auth::attempt(
            ['email' => $kredensial['email'], 'password' => $kredensial['password'], 'aktif' => true],
            $ingat,
        )) {
            RateLimiter::hit($kunci, self::KUNCI_SELAMA_DETIK);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($kunci);

        // id sesi baru hasil regenerasi inilah yang menjadi pemilik sesi aktif
        $request->session()->regenerate();
        $request->session()->put('sesi_aktif_menunggu', $request->user()->getKey());

        return redirect()
            ->intended(route('beranda'))
            ->with('sukses', 'Selamat datang, '.$request->user()->name.'.');
    }

    public function keluar(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('masuk')
            ->with('sukses', 'Anda sudah keluar.');
    }
}
