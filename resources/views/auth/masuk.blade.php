<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Masuk · Toko MM</title>
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

            <form method="POST" action="{{ route('masuk') }}" class="card space-y-4 p-6">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-[13px] font-medium text-slate-600">Email</label>
                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                           value="{{ old('email') }}"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-[13px] font-medium text-slate-600">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                                  focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                </div>

                <div class="flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-[13px] text-slate-500">
                        <input type="checkbox" name="ingat" value="1"
                               class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                        Ingat saya
                    </label>
                    <a href="{{ route('panduan') }}"
                       class="text-[13px] font-medium text-brand-700 transition hover:text-brand-800">
                        Butuh bantuan? Baca panduan
                    </a>
                </div>

                <button class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[0.99]">
                    Masuk
                </button>
            </form>

            <p class="mt-5 text-center text-[11px] text-slate-400">
                Satu akun hanya bisa dipakai di satu perangkat.
            </p>
        </div>
    </div>
</body>
</html>