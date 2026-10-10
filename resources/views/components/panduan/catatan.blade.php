@props(['tipe' => 'info', 'judul' => null])

@php
    // Satu komponen untuk semua kotak peringatan supaya warna, ikon, dan
    // jarak antarbaris selalu sama di seluruh halaman panduan.
    $gaya = [
        'info' => ['kelas' => 'bg-brand-50 text-brand-900 ring-brand-100', 'ikon' => 'M12 16v-5m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        'penting' => ['kelas' => 'bg-merah-50 text-merah-900 ring-merah-100', 'ikon' => 'M12 9v4m0 4h.01M10.3 3.9 2.5 17.5A2 2 0 0 0 4.2 20.5h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z'],
        'larangan' => ['kelas' => 'bg-merah-50 text-merah-900 ring-merah-100', 'ikon' => 'M18.4 18.4A9 9 0 0 0 5.6 5.6m12.8 12.8A9 9 0 1 1 5.6 5.6m12.8 12.8L5.6 5.6'],
    ][$tipe] ?? null;
@endphp

<div @class(['flex gap-2.5 rounded-xl px-4 py-3 text-[13px] leading-relaxed ring-1', $gaya['kelas']])>
    <svg class="mt-px h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $gaya['ikon'] }}"/>
    </svg>
    <div class="min-w-0">
        @if ($judul)
            <p class="font-semibold">{{ $judul }}</p>
        @endif
        <div class="{{ $judul ? 'mt-0.5' : '' }}">{{ $slot }}</div>
    </div>
</div>