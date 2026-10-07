@props(['judul' => null, 'langkah' => []])

{{-- Daftar langkah bernomor.
     Setiap langkah boleh berupa:
       - string Biasa
       - ['judul' => ..., 'detail' => ...]
       - ['Judul', 'Detail'] (posisional, supaya daftar panjang tetap enak dibaca)

     Bentuk posisional sengaja diterima karena daftar langkah di Panduan
     cukup panjang dan menulis kunci untuk tiap baris membuatnya jauh lebih
     sulit dibaca. --}}
@if ($judul)
    <p class="font-display text-[15px] text-slate-900">{{ $judul }}</p>
@endif

<ol class="mt-2 space-y-2">
    @foreach ($langkah as $nomor => $item)
        @php
            if (is_array($item)) {
                $judulLangkah = $item['judul'] ?? $item[0] ?? null;
                $detail = $item['detail'] ?? $item[1] ?? null;
            } else {
                $judulLangkah = $item;
                $detail = null;
            }
        @endphp
        <li class="flex gap-3">
            <span class="mt-px flex h-5 w-5 shrink-0 items-center justify-center rounded-full
                         bg-brand-100 text-[11px] font-semibold text-brand-800">
                {{ $nomor + 1 }}
            </span>
            <div class="min-w-0 text-[13px] leading-relaxed">
                @if ($judulLangkah)
                    <p class="font-medium text-slate-800">{!! $judulLangkah !!}</p>
                @endif
                @if ($detail)
                    <p class="text-slate-500">{!! $detail !!}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>