@php
    $pesan = match (true) {
        session('sukses') => ['sukses', session('sukses')],
        session('galat') => ['galat', session('galat')],
        session('pesan') => ['pesan', session('pesan')],
        default => null,
    };

    $judul = [
        'sukses' => 'Beres',
        'galat' => 'Ada yang gagal',
        'pesan' => 'Perhatian',
    ];
@endphp

@if ($pesan)
    <div @class([
        'masuk mb-4 flex items-start gap-3 rounded-2xl border bg-white p-3.5 shadow-sm',
        'border-brand-100' => $pesan[0] === 'sukses',
        'border-merah-100' => in_array($pesan[0], ['galat', 'pesan'], true),
    ]) role="status">
        <span @class([
            'flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
            'bg-brand-50 text-brand-700' => $pesan[0] === 'sukses',
            'bg-merah-50 text-merah-600' => in_array($pesan[0], ['galat', 'pesan'], true),
        ])>
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                @if ($pesan[0] === 'sukses')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                @elseif ($pesan[0] === 'galat')
                    <circle cx="12" cy="12" r="9"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3.5h.01"/>
                @else
                    <path stroke-linecap="round" d="M12 8v5m0 3.5v.01M10.3 3.9 2.5 17.5A2 2 0 0 0 4.2 20.5h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                @endif
            </svg>
        </span>

        <div class="min-w-0 flex-1">
            <p @class([
                'text-[13px] font-semibold',
                'text-brand-900' => $pesan[0] === 'sukses',
                'text-merah-900' => in_array($pesan[0], ['galat', 'pesan'], true),
            ])>{{ $judul[$pesan[0]] }}</p>
            <p class="mt-0.5 text-[13px] leading-relaxed text-slate-600">{{ $pesan[1] }}</p>
        </div>

        <button type="button" onclick="this.parentElement.remove()" aria-label="Tutup notifikasi"
                class="-mr-1 shrink-0 rounded-lg p-1 text-slate-300 transition hover:bg-slate-50 hover:text-slate-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="masuk mb-4 flex items-start gap-3 rounded-2xl border border-merah-100 bg-white p-3.5 shadow-sm" role="alert">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-merah-50 text-merah-600">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3.5h.01"/>
            </svg>
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-[13px] font-semibold text-merah-900">Periksa kembali</p>
            <ul class="mt-0.5 space-y-0.5 text-[13px] leading-relaxed text-slate-600">
                @foreach ($errors->all() as $isi)
                    <li>{{ $isi }}</li>
                @endforeach
            </ul>
        </div>

        <button type="button" onclick="this.parentElement.remove()" aria-label="Tutup notifikasi"
                class="-mr-1 shrink-0 rounded-lg p-1 text-slate-300 transition hover:bg-slate-50 hover:text-slate-500">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>
@endif
