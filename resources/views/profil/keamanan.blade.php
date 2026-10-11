@extends('layouts.app')

@section('judul', 'Keamanan Akun')

@section('konten')
    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('profil.index') }}" aria-label="Kembali ke profil"
               class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 active:scale-95">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/>
                </svg>
            </a>
            <div>
                <h1 class="font-display text-xl text-slate-900">Keamanan akun</h1>
                <p class="mt-0.5 text-xs text-slate-500">Kelola cara masuk ke akun ini.</p>
            </div>
        </div>

        {{-- Kelompok 1: kredensial login (username + password). --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 p-4">
                <p class="text-sm font-bold text-slate-900">Kredensial login</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Perbarui username dan password akun.</p>
            </div>

            {{-- Ganti username. --}}
            <form method="POST" action="{{ route('profil.username') }}" class="border-b border-slate-100 p-4">
                @csrf
                <p class="mb-2 text-[13px] font-medium text-slate-600">Ganti username</p>

                <div class="flex gap-2">
                    <input type="text" name="username" value="{{ old('username', $akun->username) }}"
                           minlength="3" maxlength="50" required autocomplete="off"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                    <button class="shrink-0 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                        Simpan
                    </button>
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">Login memakai username atau email.</p>
            </form>

            {{-- Ganti password. --}}
            <form method="POST" action="{{ route('profil.password') }}" class="p-4">
                @csrf
                <p class="mb-2 text-[13px] font-medium text-slate-600">Ganti password</p>

                <div class="space-y-2">
                    <input type="password" name="password_lama" placeholder="Password lama" required autocomplete="current-password"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                    <input type="password" name="password" placeholder="Password baru (min. 8 karakter)" required autocomplete="new-password"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                    <input type="password" name="password_confirmation" placeholder="Ulangi password baru" required autocomplete="new-password"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                </div>

                <button class="mt-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
                    Ganti password
                </button>
            </form>
        </div>

        {{-- Kelompok 2: pintasan masuk di perangkat ini. --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 p-4">
                <p class="text-sm font-bold text-slate-900">Masuk cepat</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Pilihan yang hanya berlaku di perangkat ini.</p>
            </div>

            {{-- Simpan nama user (hanya di perangkat ini). --}}
            <label class="flex cursor-pointer items-center justify-between gap-4 border-b border-slate-100 p-4">
                <span class="min-w-0">
                    <span class="block text-[13px] font-medium text-slate-700">Simpan nama user</span>
                    <span class="block text-[11px] text-slate-400">Sembunyikan kolom username di halaman login perangkat ini; cukup isi sandi.</span>
                </span>
                <input type="checkbox" id="ingat-toggle" class="peer sr-only">
                <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-200 transition-all peer-checked:bg-brand-600
                             after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all
                             peer-checked:after:translate-x-5"></span>
            </label>

            {{-- Login dengan biometrik (passkey sederhana di perangkat ini). --}}
            <label class="flex cursor-pointer items-center justify-between gap-4 p-4">
                <span class="min-w-0">
                    <span class="block text-[13px] font-medium text-slate-700">Login dengan biometrik</span>
                    <span id="bio-ket" class="block text-[11px] text-slate-400">Masuk cepat pakai sidik jari atau wajah di perangkat ini.</span>
                </span>
                <input type="checkbox" id="bio-toggle" class="peer sr-only"
                       data-token-url="{{ route('profil.biometrik') }}"
                       data-user="{{ $akun->username ?: $akun->email }}">
                <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-200 transition-all peer-checked:bg-brand-600
                             after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all
                             peer-checked:after:translate-x-5"></span>
            </label>
        </div>
    </div>
@endsection