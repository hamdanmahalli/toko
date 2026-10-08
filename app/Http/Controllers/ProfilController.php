<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use App\Support\Username;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Kelola akun sendiri: username, password, dan izin perangkat milik sendiri.
 * (Persetujuan perangkat karyawan lain diatur lewat halaman Pengguna.)
 */
class ProfilController extends Controller
{
    public function index(): View
    {
        return view('profil.index', [
            'akun' => auth()->user()->load(['devices']),
        ]);
    }

    public function gantiPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($data['password_lama'], $request->user()->password)) {
            return back()->with('galat', 'Password lama tidak cocok.');
        }

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('sukses', 'Password berhasil diganti.');
    }

    public function gantiUsername(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9_.-]+$/'],
        ]);

        $username = (new Username)->normalisasi($request->string('username'));

        if ($username === $request->user()->username) {
            return back()->with('pesan', 'Username kamu memang sudah itu.');
        }

        if ((new Username)->eksis($username)) {
            return back()->with('galat', 'Username itu sudah dipakai pengguna lain. Pilih yang lain.');
        }

        $request->user()->update(['username' => $username]);

        return back()->with('sukses', 'Username berhasil diganti.');
    }

    public function cabutPerangkat(Request $request, UserDevice $perangkat): RedirectResponse
    {
        abort_unless($perangkat->user_id === $request->user()->getKey(), 404);

        $perangkat->update(['status' => 'rejected']);

        return back()->with('sukses', 'Izin perangkat itu dicabut. Tidak bisa dipakai untuk login lagi sampai disetujui ulang.');
    }
}
