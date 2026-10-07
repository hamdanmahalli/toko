@extends('layouts.presensi', ['judul' => 'Login Presensi'])

@section('konten')
    <div class="card space-y-4 p-5">
        <div>
            <h1 class="font-display text-lg leading-tight text-slate-900">Login presensi</h1>
            <p class="mt-1 text-[13px] leading-relaxed text-slate-500">
                Perangkat presensi
                <span class="font-medium text-slate-700">{{ $shop->nama }}</span> dipakai
                bersama. User dan password-nya sama untuk semua karyawan di toko ini, jadi tidak
                perlu akun pribadi. Setelah login, pindai kartu untuk absen.
            </p>
        </div>

        @include('layouts.pesan')

        <form method="POST" action="{{ route('presensi.masuk', $shop->kode) }}" class="space-y-4">
            @csrf

            <div>
                <label for="user" class="mb-1.5 block text-[13px] font-medium text-slate-600">User presensi</label>
                <input id="user" name="user" type="text" required autofocus autocomplete="username"
                       autocapitalize="off" autocorrect="off" spellcheck="false"
                       value="{{ old('user') }}" placeholder="User presensi"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3 text-base outline-none transition
                              placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-[13px] font-medium text-slate-600">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       placeholder="Password presensi"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3 text-base outline-none transition
                              placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                Masuk
            </button>
        </form>

        <p class="text-center text-[12px] leading-relaxed text-slate-400">
            User dan password presensi diberikan pemilik toko.
        </p>
    </div>
@endsection