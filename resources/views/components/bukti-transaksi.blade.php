@props(['url', 'keterangan' => ''])

{{-- Thumbnail bukti transaksi. Klik/tap untuk memperbesar.
     Span (bukan tombol) agar tetap sah bila berada di dalam tautan. --}}
<span class="js-preview relative block h-11 w-11 shrink-0 cursor-zoom-in overflow-hidden rounded-lg ring-1 ring-slate-100"
      role="button" tabindex="0" aria-label="Lihat bukti transaksi"
      data-gambar="{{ $url }}" data-ket="{{ $keterangan }}">
    <img src="{{ $url }}" alt="Bukti transaksi" class="h-full w-full object-cover">
    <span class="pointer-events-none absolute inset-0 grid place-items-center bg-slate-900/0 text-white/0 transition hover:bg-slate-900/30 hover:text-white">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Zm0-9v4m-2-2h4"/>
        </svg>
    </span>
</span>

<x-modal-gambar />
