<?php

namespace App\Http\Controllers;

use App\Enums\StatusPerangkat;
use App\Models\User;
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
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'ingat' => ['nullable', 'boolean'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $login = mb_strtolower(trim($kredensial['login']));
        $kunci = Str::transliterate($login.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($kunci, self::BATAS_PERCOBAAN)) {
            $sisa = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan. Coba lagi dalam {$sisa} detik.",
            ]);
        }

        // Kolom tunggal "Email / Username": ada `@` berarti email, sisanya username.
        $kolom = str_contains($login, '@') ? 'email' : 'username';
        $akun = User::where($kolom, $login)->first();

        $ingat = (bool) ($kredensial['ingat'] ?? false);

        if (! $akun || ! Auth::attempt(
            [$kolom => $login, 'password' => $kredensial['password'], 'aktif' => true],
            $ingat,
        )) {
            RateLimiter::hit($kunci, self::KUNCI_SELAMA_DETIK);

            throw ValidationException::withMessages([
                'login' => 'Email/username atau password salah.',
            ]);
        }

        $blokir = $this->perangkatDiizinkan($akun, (string) ($kredensial['device_id'] ?? ''));

        if ($blokir !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages(['login' => $blokir]);
        }

        RateLimiter::clear($kunci);

        // id sesi baru hasil regenerasi inilah yang menjadi pemilik sesi aktif
        $request->session()->regenerate();
        $request->session()->put('sesi_aktif_menunggu', $akun->getKey());

        return redirect()
            ->intended(route('beranda'))
            ->with('sukses', 'Selamat datang, '.$akun->name.'.');
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

    /**
     * Cek penjagaan perangkat untuk akun karyawan.
     *
     * Mengembalikan null ketika diizinkan, atau pesan penolakan ketika tidak.
     * Perangkat pertama yang belum dikenal otomatis dicatat sebagai "menunggu
     * persetujuan" supaya pemilik/kepala toko tinggal menyetujui di Pengguna.
     */
    private function perangkatDiizinkan(User $akun, string $deviceId): ?string
    {
        if (! $akun->wajibPerangkat()) {
            return null;
        }

        if ($deviceId === '') {
            return 'Tidak bisa mengenali perangkat browser ini. Pakai aplikasi PWA atau browser dengan penyimpanan lokal, lalu coba lagi.';
        }

        $perangkat = $akun->devices()->where('device_token', $deviceId)->first();

        if ($perangkat) {
            if ($perangkat->status === StatusPerangkat::Disetujui) {
                $perangkat->update(['last_seen_at' => now()]);

                return null;
            }

            return $perangkat->status === StatusPerangkat::Ditolak
                ? 'Perangkat ini sudah ditolak. Minta pemilik menentukan ulang di menu Pengguna.'
                : 'Perangkat ini belum disetujui. Minta pemilik atau kepala toko mengizinkannya di menu Pengguna.';
        }

        $this->catatPerangkatBaru($akun, $deviceId);

        return 'Perangkat ini belum didaftarkan dan sekarang menunggu persetujuan pemilik atau kepala toko di menu Pengguna.';
    }

    private function catatPerangkatBaru(User $akun, string $deviceId): void
    {
        $akun->devices()->updateOrCreate(
            ['device_token' => mb_substr($deviceId, 0, 64)],
            [
                'label' => mb_substr((string) request()->userAgent(), 0, 120),
                'status' => StatusPerangkat::Pending,
            ],
        );
    }
}
