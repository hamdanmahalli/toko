<?php

namespace App\Http\Controllers;

use App\Enums\StatusPerangkat;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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

        return view('auth.halaman-awal', ['panel' => 'masuk']);
    }

    public function masuk(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
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

        if (! $akun || ! Auth::attempt(
            [$kolom => $login, 'password' => $kredensial['password'], 'aktif' => true],
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

    /**
     * Login "passkey sederhana": membuka kunci token yang disegel server
     * (lihat ProfilController::biometrik) setelah biometrik perangkat lolos.
     * Token hanya berlaku bila device_id-nya sama dengan saat diterbitkan.
     */
    public function masukBiometrik(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $deviceId = (string) ($data['device_id'] ?? '');

        try {
            $isi = json_decode(Crypt::decryptString($data['token']), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'login' => 'Login biometrik tidak valid. Silakan masuk dengan password.',
            ]);
        }

        $akun = User::find($isi['uid'] ?? null);

        if (! $akun instanceof User || ! $akun->aktif || (int) ($isi['exp'] ?? 0) < now()->getTimestamp()) {
            throw ValidationException::withMessages([
                'login' => 'Login biometrik sudah kedaluwarsa. Silakan masuk dengan password.',
            ]);
        }

        if ((string) ($isi['dev'] ?? '') !== $deviceId) {
            throw ValidationException::withMessages([
                'login' => 'Login biometrik hanya berlaku di perangkat aslinya.',
            ]);
        }

        $blokir = $this->perangkatDiizinkan($akun, $deviceId);

        if ($blokir !== null) {
            throw ValidationException::withMessages(['login' => $blokir]);
        }

        Auth::login($akun, true);
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
     * Selama akun masih punya perangkat aktif, perangkat lain yang belum
     * dikenal dicatat "menunggu" dan loginnya diblokir; penyelesaiannya adalah
     * pemilik HAPUS perangkat lama di menu Pengguna. Begitu tidak ada perangkat
     * aktif tersisa, perangkat berikutnya dianggap perangkat pertama dan
     * langsung diizinkan.
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
        $adaPerangkatAktif = $akun->devices()->where('status', StatusPerangkat::Disetujui)->exists();

        if ($perangkat) {
            if ($perangkat->status === StatusPerangkat::Disetujui) {
                $perangkat->update(['last_seen_at' => now()]);

                return null;
            }

            if ($perangkat->status === StatusPerangkat::Ditolak) {
                return 'Perangkat ini sudah ditolak. Minta pemilik menghapusnya di menu Pengguna untuk bisa dipakai lagi.';
            }

            // Menunggu: selama perangkat lama masih aktif, tetap diblokir.
            if ($adaPerangkatAktif) {
                return 'Perangkat ini belum dikenal. Minta pemilik atau kepala toko menghapus perangkat lama di menu Pengguna, lalu coba login lagi.';
            }

            // Perangkat lama sudah dihapus → perangkat ini jadi yang pertama.
            $perangkat->update(['status' => StatusPerangkat::Disetujui, 'last_seen_at' => now()]);

            return null;
        }

        if ($adaPerangkatAktif) {
            $this->catatPerangkatBaru($akun, $deviceId);

            return 'Perangkat ini belum dikenal. Minta pemilik atau kepala toko menghapus perangkat lama di menu Pengguna, lalu coba login lagi.';
        }

        // Tidak ada perangkat aktif = perangkat pertama, langsung diizinkan.
        $akun->devices()->create([
            'device_token' => mb_substr($deviceId, 0, 64),
            'label' => mb_substr((string) request()->userAgent(), 0, 120),
            'status' => StatusPerangkat::Disetujui,
        ]);

        return null;
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
