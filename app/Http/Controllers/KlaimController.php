<?php

namespace App\Http\Controllers;

use App\Mail\KirimAkunBaru;
use App\Models\Employee;
use App\Models\User;
use App\Support\Username;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Klaim akun untuk karyawan: admin cukup membuat data karyawan dengan nomor ID
 * (NIP), lalu karyawan mengklaim akunnya sendiri memakai nomor itu dan memilih
 * username. Kalau admin sudah mengisi email, kolom email di form otomatis
 * terisi dari data karyawan. Password awal dikirim ke email tersebut, dan
 * perangkat yang dipakai untuk klaim otomatis dipercaya supaya langsung bisa
 * masuk.
 */
class KlaimController extends Controller
{
    public function form(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('beranda');
        }

        return view('auth.halaman-awal', ['panel' => 'klaim']);
    }

    /**
     * Email karyawan untuk auto-isi form klaim berdasarkan nomor ID.
     *
     * Hanya mengembalikan email karyawan yang belum punya akun dan masih aktif,
     * supaya nomor ID yang sudah diklaim atau nonaktif tidak membocorkan apa pun.
     */
    public function cari(Request $request): JsonResponse
    {
        $nip = trim((string) $request->string('nip')->value());

        if ($nip === '') {
            return response()->json(['email' => null]);
        }

        $email = Employee::query()
            ->where('nip', $nip)
            ->whereNull('user_id')
            ->where('aktif', true)
            ->value('email');

        return response()->json(['email' => $email]);
    }

    public function klaim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $karyawan = Employee::query()
            ->where('nip', $data['nip'])
            ->whereNull('user_id')
            ->where('aktif', true)
            ->first();

        if (! $karyawan) {
            return back()->with(
                'galat',
                'Data karyawan dengan nomor ID tersebut tidak ditemukan, atau akunnya sudah pernah dibuat.',
            );
        }

        $username = (new Username)->normalisasi($data['username']);

        if ((new Username)->eksis($username)) {
            return back()->with('galat', 'Username itu sudah dipakai. Pilih username lain.');
        }

        $passwordAwal = Str::random(12);

        try {
            // Semua tulis-menulis dibungkus satu transaksi. Kalau email gagal
            // dikirim, akun + perangkat + tautan ke data karyawan dibatalkan,
            // supaya nomor ID tidak terpakai sia-sia dan karyawan bisa coba lagi.
            DB::transaction(function () use ($karyawan, $data, $username, $passwordAwal, $request) {
                $akun = User::create([
                    'name' => $karyawan->nama,
                    'email' => $data['email'],
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
            });
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'galat',
                'Akun gagal dibuat karena email belum bisa dikirim. Hubungi admin, lalu coba lagi.',
            );
        }

        return redirect()
            ->route('masuk')
            ->with('sukses', 'Akun berhasil dibuat. Cek email Anda untuk password awal, lalu masuk.');
    }
}
