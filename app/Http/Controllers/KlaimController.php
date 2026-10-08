<?php

namespace App\Http\Controllers;

use App\Mail\KirimAkunBaru;
use App\Models\Employee;
use App\Models\User;
use App\Support\Username;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Klaim akun untuk karyawan: admin cukup membuat data karyawan berisi NIP +
 * email, lalu karyawan mengklaim akunnya sendiri dengan memilih username.
 * Password awal dikirim lewat email, dan perangkat yang dipakai untuk klaim
 * otomatis dipercaya supaya langsung bisa masuk.
 */
class KlaimController extends Controller
{
    public function form(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('beranda');
        }

        return view('auth.klaim');
    }

    public function klaim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:120'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $karyawan = Employee::query()
            ->where('nip', $data['nip'])
            ->where('email', $data['email'])
            ->whereNull('user_id')
            ->where('aktif', true)
            ->first();

        if (! $karyawan) {
            return back()->with(
                'galat',
                'Data karyawan dengan NIP dan email tersebut tidak ditemukan, atau akunnya sudah pernah dibuat.',
            );
        }

        $username = (new Username)->normalisasi($data['username']);

        if ((new Username)->eksis($username)) {
            return back()->with('galat', 'Username itu sudah dipakai. Pilih username lain.');
        }

        $passwordAwal = Str::random(12);

        $akun = User::create([
            'name' => $karyawan->nama,
            'email' => $karyawan->email,
            'username' => $username,
            'password' => Hash::make($passwordAwal),
            'telepon' => $karyawan->telepon,
            'aktif' => true,
        ]);

        $akun->assignRole('karyawan');
        $karyawan->forceFill(['user_id' => $akun->id])->save();

        if ($deviceId = $request->string('device_id')->value()) {
            $akun->devices()->create([
                'device_token' => mb_substr($deviceId, 0, 64),
                'label' => mb_substr((string) $request->userAgent(), 0, 120),
                'status' => 'approved',
                'last_seen_at' => now(),
            ]);
        }

        Mail::to($akun->email)->send(new KirimAkunBaru($akun, $passwordAwal));

        return redirect()
            ->route('masuk')
            ->with('sukses', 'Akun berhasil dibuat. Cek email Anda untuk password awal, lalu masuk.');
    }
}
