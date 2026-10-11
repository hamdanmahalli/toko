{{--
    Judul halaman bergaya premium.

    Permukaan melengkung dengan gradasi mint tipis, lencana ikon bergradien,
    judul tebal rapat, sub-judul halus, dan slot aksi di sisi kanan. Dipakai
    agar kepala setiap halaman tampil konsisten, bukan sekadar teks polos.

    Properti:
    - judul   : teks judul halaman.
    - sub     : sub-judul teks biasa (opsional).
    - kembali : URL tombol kembali (opsional). Bila diisi, lencana ikon
                digantikan tombol kembali.

    Slot:
    - ikon    : isi ikon (svg) untuk lencana. Diabaikan bila `kembali` diisi.
    - sub     : sub-judul berbentuk HTML kaya (opsional, menimpa properti sub).
    - aksi    : elemen di kanan (tombol/badge), opsional.
--}}
@props([
    'judul',
    'sub' => null,
    'kembali' => null,
])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-[22px] bg-gradient-to-br from-white via-white to-brand-50 p-4 shadow-sm shadow-brand-900/5 ring-1 ring-brand-100/80 sm:p-5']) }}>
    <span class="pointer-events-none absolute -right-12 -top-14 h-36 w-36 rounded-full bg-brand-200/40 blur-2xl" aria-hidden="true"></span>
    <span class="pointer-events-none absolute -bottom-16 -left-12 h-36 w-36 rounded-full bg-brand-100/50 blur-2xl" aria-hidden="true"></span>

    <div class="relative flex items-center gap-3 sm:gap-4">
        @if ($kembali)
            <a href="{{ $kembali }}" aria-label="Kembali"
               class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50 hover:text-brand-700 active:scale-95">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/>
                </svg>
            </a>
        @elseif (isset($ikon))
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/25 ring-1 ring-white/50">
                {{ $ikon }}
            </span>
        @endif

        <div class="min-w-0 flex-1">
            <h1 class="font-display text-xl font-bold leading-tight tracking-tight text-slate-900">{{ $judul }}</h1>
            @if ($sub)
                <p class="mt-1 text-xs leading-snug text-slate-500">{{ $sub }}</p>
            @endif
        </div>

        @isset($aksi)
            <div class="shrink-0">{{ $aksi }}</div>
        @endisset
    </div>
</div>
