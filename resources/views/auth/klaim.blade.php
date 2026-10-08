<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Klaim Akun · Toko MM</title>
    @include('layouts.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-brand-50 antialiased">
    <div class="flex min-h-full items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            <div class="mb-7 flex justify-center">
                <img src="{{ asset('logo-toko-mm.png') }}" alt="Toko MM" class="h-10 w-auto">
            </div>

            @include('layouts.pesan')

            <form method="POST" action="{{ route('klaim.proses') }}" class="card space-y-4 p-6">
                @csrf
                <input type="hidden" name="device_id">

                <div>
                    <label for="nip" class="mb-1.5 block text-[13px] font-medium text-slate-600">NIP</label>
                    <input id="nip" name="nip" type="text" required autocomplete="off"
                           value="{{ old('nip') }}"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-[13px] font-medium text-slate-600">Email di data karyawan</label>
                    <input id="email" name="email" type="email" required autocomplete="email"
                           value="{{ old('email') }}"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                </div>

                <div>
                    <label for="username" class="mb-1.5 block text-[13px] font-medium text-slate-600">Username pilihan kamu</label>
                    <input id="username" name="username" type="text" required minlength="3" maxlength="50" autocomplete="off"
                           value="{{ old('username') }}"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                    <span class="mt-1 block text-[11px] text-slate-400">Huruf kecil, angka, titik, strip, atau garis bawah (3–50 karakter).</span>
                </div>

                <button class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[0.99]">
                    Klaim akun
                </button>
            </form>

            <p class="mt-5 text-center text-[13px] text-slate-500">
                Sudah punya akun?
                <a href="{{ route('masuk') }}" class="font-medium text-brand-700 hover:text-brand-800">
                    Masuk
                </a>
            </p>

            <p class="mt-2 text-center text-[11px] text-slate-400">
                Password awal dikirim ke email. Perangkat tempat klaim langsung diizinkan untuk login.
            </p>
        </div>
    </div>
</body>
</html>