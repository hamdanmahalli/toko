<div class="px-3 pb-2 pt-3">
    <label class="relative block">
        <span class="sr-only">Cari panduan</span>
        <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
             fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M21 21l-4.3-4.3M17 11a6 6 0 1 1-12 0 6 6 0 1 1 12 0Z"/>
        </svg>
        <input type="search" data-panduan-cari placeholder="Cari panduan"
               class="w-full rounded-lg border border-slate-200 py-1.5 pl-8 pr-2 text-xs
                      outline-none focus:border-brand-500">
    </label>
</div>

{{-- Fragmen URL seperti #admin tidak pernah dikirim ke server, jadi tautan aktif
     ditentukan di sisi peramban oleh partial pencarian. --}}
<nav data-panduan-daftar class="space-y-0.5 px-2 pb-3">
    @foreach ($bagian as $m)
        <a href="#{{ $m['id'] }}"
           data-panduan-item="{{ $m['judul'] }} {{ $m['ringkas'] }}"
           data-panduan-tautan="{{ $m['id'] }}"
           class="panduan-tautan block rounded-xl px-3 py-2 text-[13px] leading-snug transition
                  text-slate-600 hover:bg-brand-50 hover:text-brand-800">
            <span class="block font-medium">{{ $m['judul'] }}</span>
            <span class="mt-0.5 block text-[11px] leading-snug opacity-70">{{ $m['ringkas'] }}</span>
        </a>
    @endforeach

    <p data-panduan-kosong class="hidden px-3 py-3 text-center text-[11px] text-slate-400">
        Tidak ada bagian yang cocok.
    </p>
</nav>