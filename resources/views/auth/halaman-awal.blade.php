@php
    $namaApp = \App\Models\Setting::ambil('umum.nama_app', 'Toko MM');
    $panel = $panel ?? 'mulai';
    // Splash hanya di halaman sambutan. Di halaman login (termasuk saat kembali
    // karena gagal login) splash tidak diputar ulang supaya tetap di form login.
    $tampilkanSplash = $panel === 'mulai';
    $judulPanel = match ($panel) {
        'masuk' => 'Masuk',
        default => 'Selamat Datang',
    };
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $judulPanel }} · {{ $namaApp }}</title>

    <!-- SPLASH SCREEN LOGO -->
    <style>
        #splash-screen {
            position: fixed;
            inset: 0;
            z-index: 99999;
            overflow: hidden;
            background: radial-gradient(1200px circle at 50% 38%, #ffffff 0%, #fdfdfc 55%, #eef2f7 100%);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.6s ease, visibility 0.6s ease;
        }

        #splash-screen.fade-out {
            opacity: 0;
            visibility: hidden;
        }

        html.splash-aktif #splash-screen {
            display: flex;
        }

        html.splash-aktif body {
            overflow: hidden;
        }

        /* Logo besar samar sebagai latar belakang */
        .splash-bg-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 125vmin;
            height: 125vmin;
            transform: translate(-50%, -50%);
            object-fit: contain;
            opacity: 0.05;
            filter: grayscale(1);
            z-index: 0;
            pointer-events: none;
            animation: bgBreath 9s ease-in-out infinite;
        }

        /* Bercak warna lembut supaya latar tidak kosong */
        .splash-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            pointer-events: none;
        }

        .splash-blob-1 {
            width: 460px;
            height: 460px;
            top: -140px;
            left: -120px;
            background: rgba(220, 38, 38, 0.18);
            animation: blobFloat 12s ease-in-out infinite;
        }

        .splash-blob-2 {
            width: 420px;
            height: 420px;
            bottom: -160px;
            right: -120px;
            background: rgba(22, 163, 74, 0.16);
            animation: blobFloat 12s ease-in-out infinite 3s;
        }

        .splash-logo-wrap {
            position: relative;
            width: 220px;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
            z-index: 2;
        }

        .splash-ring {
            position: absolute;
            inset: -22px;
            border-radius: 50%;
            border: 1px solid rgba(37, 99, 235, 0.18);
            pointer-events: none;
        }

        .splash-ring-1 {
            animation: ringExpand 1.8s ease-out infinite;
        }

        .splash-ring-2 {
            inset: -44px;
            border-color: rgba(37, 99, 235, 0.12);
            animation: ringExpand 1.8s ease-out infinite 0.4s;
        }

        .splash-logo {
            width: 180px;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            opacity: 0;
            filter: drop-shadow(0 18px 40px rgba(15, 23, 42, 0.14));
            animation: logoIn 0.9s cubic-bezier(0.2, 0.8, 0.2, 1) forwards,
                       logoMicroPulse 1.8s ease-in-out infinite 1.1s;
        }

        .splash-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .splash-dots {
            position: relative;
            z-index: 2;
            display: flex;
            gap: 8px;
            margin-top: 18px;
        }

        .splash-dots span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.45);
            animation: dotPulse 1.4s ease-in-out infinite;
        }

        .splash-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .splash-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes logoIn {
            0% {
                opacity: 0;
                transform: scale(0.92) translateY(4px);
            }
            60% {
                opacity: 1;
                transform: scale(1.02) translateY(0);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes logoMicroPulse {
            0%,
            100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.012);
            }
        }

        @keyframes ringExpand {
            0% {
                opacity: 0.25;
                transform: scale(0.9);
            }
            70% {
                opacity: 0;
                transform: scale(1.25);
            }
            100% {
                opacity: 0;
                transform: scale(1.28);
            }
        }

        @keyframes dotPulse {
            0%,
            80%,
            100% {
                transform: scale(0.5);
                opacity: 0.25;
            }
            40% {
                transform: scale(1.15);
                opacity: 0.9;
            }
        }

        @keyframes bgBreath {
            0%,
            100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.05;
            }
            50% {
                transform: translate(-50%, -50%) scale(1.06);
                opacity: 0.075;
            }
        }

        @keyframes blobFloat {
            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(24px, -28px) scale(1.08);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .splash-bg-logo,
            .splash-blob,
            .splash-ring,
            .splash-logo,
            .splash-dots span {
                animation: none !important;
            }

            .splash-logo {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>

    <!-- Panel: hanya satu yang tampak; tinggi wadah dianimasikan lewat JS
         supaya kartu tumbuh/memendek mulus dari atas tanpa bergeser posisi. -->
    <style>
        .panel-wadah {
            overflow: hidden;
            transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .panel {
            display: none;
        }

        .panel.aktif {
            display: block;
            animation: naik 0.3s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes naik {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .panel-wadah {
                transition: none;
            }

            .panel.aktif {
                animation: none;
            }
        }
    </style>

    @if ($tampilkanSplash)
        <script>
            // Splash tampil saat halaman sambutan dimuat: pasang kelas sebelum
            // konten dirender supaya overlay tampil tanpa kedipan.
            document.documentElement.classList.add('splash-aktif');
        </script>
    @endif

    @include('layouts.pwa')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-white antialiased">
    <!-- SPLASH SCREEN OVERLAY (hanya halaman sambutan) -->
    @if ($tampilkanSplash)
        <div id="splash-screen">
            <img class="splash-bg-logo" src="{{ asset('logo-toko-mm.png') }}" alt="" aria-hidden="true">
            <div class="splash-blob splash-blob-1"></div>
            <div class="splash-blob splash-blob-2"></div>

            <div class="splash-logo-wrap">
                <div class="splash-ring splash-ring-1"></div>
                <div class="splash-ring splash-ring-2"></div>
                <div class="splash-logo">
                    <img src="{{ asset('logo-toko-mm.png') }}" alt="{{ $namaApp }}">
                </div>
            </div>
            <div class="splash-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    @endif

    <div class="relative flex min-h-dvh w-full flex-col lg:flex-row lg:items-end lg:justify-end">
        <!-- Area gambar: penuh sampai tepi. Mobile = login.png, Web = website.jpg.
             flex-1 membuat gambar mengisi ruang tersisa, jadi kartu bisa memanjang ke atas. -->
        <div class="relative -mb-8 w-full flex-1 overflow-hidden lg:absolute lg:inset-0 lg:mb-0 lg:h-auto">
            <img src="{{ asset('img/login.png') }}"
                 alt="Ilustrasi {{ $namaApp }}"
                 class="absolute inset-0 h-full w-full object-cover object-center lg:hidden">
            <img src="{{ asset('img/website.jpg') }}"
                 alt="{{ $namaApp }}"
                 class="absolute inset-0 hidden h-full w-full object-cover object-center lg:block">
        </div>

        <!-- Area putih berisi panel: menumpuk gambar di mobile, melayang di kanan saat web -->
        <div class="relative flex w-full flex-col rounded-t-[2rem] bg-white px-6 pb-8 pt-7
                    lg:mb-10 lg:mr-10 lg:w-[440px] lg:shrink-0 lg:rounded-[2rem] lg:px-10 lg:py-10 lg:shadow-2xl">
            @include('layouts.pesan')

            <div id="panel-wadah" class="panel-wadah">
            {{-- PANEL: GET STARTED --}}
            <section id="panel-mulai" class="panel {{ $panel === 'mulai' ? 'aktif' : '' }}">
                <div class="text-center">
                    <img src="{{ asset('logo-toko-mm.png') }}" alt="{{ $namaApp }}" class="mx-auto h-12 w-auto">
                    <h1 class="mt-5 text-[26px] font-extrabold leading-tight tracking-tight text-slate-800">
                        Let&rsquo;s Get You Set Up<br>for Success
                    </h1>
                    <p class="mx-auto mt-3 max-w-xs text-[13px] leading-relaxed text-slate-500">
                        Toko MM adalah toko milik Pondok Pesantren Maqna&rsquo;ul Ulum,
                        yang menyediakan segala jenis sembako dan kebutuhan pokok.
                    </p>
                </div>
                <button type="button" data-ke="masuk"
                        class="mt-7 w-full rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[0.99]">
                    Get Started
                </button>
            </section>

            {{-- PANEL: LOGIN --}}
            <section id="panel-masuk" class="panel {{ $panel === 'masuk' ? 'aktif' : '' }}">
                <h2 class="text-center text-[26px] font-extrabold tracking-tight text-slate-800">Login</h2>

                <form method="POST" action="{{ route('masuk') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="device_id">

                    <div id="baris-login">
                        <label for="login" class="sr-only">Email atau username</label>
                        <div class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 transition focus-within:border-brand-500 focus-within:bg-white focus-within:ring-4 focus-within:ring-brand-100">
                            <svg class="h-[18px] w-[18px] shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0v.15H4.5V20.1Z"/>
                            </svg>
                            <input id="login" name="login" type="text" required autocomplete="username"
                                   value="{{ old('login') }}" placeholder="email/username"
                                   class="w-full bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <div class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 transition focus-within:border-brand-500 focus-within:bg-white focus-within:ring-4 focus-within:ring-brand-100">
                            <svg class="h-[18px] w-[18px] shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.5a4.5 4.5 0 0 0-9 0v3M6 10.5h12a1.5 1.5 0 0 1 1.5 1.5v6A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18v-6A1.5 1.5 0 0 1 6 10.5Z"/>
                            </svg>
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                   placeholder="Enter password"
                                   class="w-full bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('panduan') }}"
                           class="text-[13px] font-medium text-brand-700 transition hover:text-brand-800">
                            Baca Panduan
                        </a>
                    </div>

                    <div class="flex items-center gap-3">
                        <button class="flex h-12 flex-1 items-center justify-center rounded-xl bg-brand-600 px-4 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[0.99]">
                            Login
                        </button>

                        {{-- Tombol biometrik: ikon di kanan tombol login, muncul
                             hanya kalau perangkat ini menyimpan kredensial
                             (ditampilkan oleh app.js). --}}
                        <button type="button" id="bio-masuk" hidden
                                title="Masuk dengan biometrik" aria-label="Masuk dengan biometrik"
                                class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-600 text-white shadow-md shadow-brand-600/25 transition hover:bg-brand-700 active:scale-[0.98]">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25a4.5 4.5 0 1 1 9 0M5.25 8.25a6.75 6.75 0 1 1 13.5 0M12 12v3.75m-3.75 3.75c1.5-1.5 2.25-3 2.25-5.25m7.5 0c0 2.25-.75 3.75-2.25 5.25"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <form id="bio-masuk-form" method="POST" action="{{ route('masuk.biometrik') }}">
                    @csrf
                    <input type="hidden" name="token">
                    <input type="hidden" name="device_id">
                </form>
            </section>

        </div>
    </div>

    <script>
        // Pindah antar panel (Get Started / Login) tanpa muat ulang.
        // Tinggi wadah dianimasikan supaya kartu tumbuh/memendek mulus dari atas.
        document.addEventListener('click', function (e) {
            var tombol = e.target.closest('[data-ke]');
            if (!tombol) return;

            var tujuan = tombol.getAttribute('data-ke');
            var wadah = document.getElementById('panel-wadah');
            var sekarang = wadah.querySelector('.panel.aktif');
            var berikut = document.getElementById('panel-' + tujuan);
            if (!berikut || berikut === sekarang) return;

            wadah.style.height = wadah.getBoundingClientRect().height + 'px';
            void wadah.offsetHeight;

            if (sekarang) sekarang.classList.remove('aktif');
            berikut.classList.add('aktif');

            wadah.style.height = berikut.getBoundingClientRect().height + 'px';

            clearTimeout(wadah._timer);
            wadah._timer = setTimeout(function () {
                wadah.style.height = '';
            }, 330);
        });
    </script>
    <script>
        // Isi device_id di form login dari modul identitas.
        document.addEventListener('DOMContentLoaded', function () {
            var id = null;
            try { id = window.localStorage.getItem('tokom_dev'); } catch (e) { id = null; }
            if (!id) return;
            document.querySelectorAll('input[name="device_id"]').forEach(function (kolom) {
                if (!kolom.value) kolom.value = id;
            });
        });
    </script>
    @if ($tampilkanSplash)
        <script>
            // Splash logo: tampil segera (kelas splash-aktif dari <head>), lalu fade-out
            // setelah 2,5 detik.
            function tutupSplash() {
                var splash = document.getElementById('splash-screen');
                if (!splash) return;
                if (!document.documentElement.classList.contains('splash-aktif')) return;

                setTimeout(function () {
                    splash.classList.add('fade-out');
                    document.documentElement.classList.remove('splash-aktif');
                    document.body.style.overflow = '';
                    setTimeout(function () { splash.remove(); }, 600);
                }, 2500);
            }

            // `load` untuk kunjungan normal, `pageshow` untuk kembali dari cache
            // (bfcache) yang tidak memicu `load` lagi.
            window.addEventListener('load', tutupSplash);
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) tutupSplash();
            });
        </script>
    @endif
</body>
</html>
