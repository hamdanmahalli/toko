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

@once
    <div id="modal-gambar" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/85 p-4" role="dialog" aria-modal="true" aria-label="Pratinjau bukti transaksi">
        <button type="button" id="modal-gambar-tutup" class="absolute inset-0 cursor-zoom-out" aria-label="Tutup pratinjau"></button>
        <figure class="relative z-10 flex max-h-full w-full max-w-lg flex-col">
            <img id="modal-gambar-img" src="" alt="Bukti transaksi"
                 class="mx-auto max-h-[78vh] w-auto rounded-2xl object-contain shadow-2xl ring-1 ring-white/10">
            <figcaption class="mt-2 flex items-center justify-center gap-2">
                <span id="modal-gambar-ket" class="text-center text-xs text-white/80"></span>
            </figcaption>
            <button type="button" id="modal-gambar-tombol" aria-label="Tutup"
                    class="absolute -top-3 -right-3 grid h-9 w-9 place-items-center rounded-full bg-white text-slate-700 shadow-lg transition hover:bg-slate-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </figure>
    </div>

    @push('kaki')
    <script>
    (() => {
        const modal = document.getElementById('modal-gambar');
        if (!modal) return;

        const gambar = document.getElementById('modal-gambar-img');
        const keterangan = document.getElementById('modal-gambar-ket');

        const buka = (sumber, teks) => {
            gambar.src = sumber;
            keterangan.textContent = teks || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        };

        const tutup = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            gambar.src = '';
            document.body.style.overflow = '';
        };

        document.querySelectorAll('.js-preview').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                buka(el.dataset.gambar, el.dataset.ket);
            });
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.stopPropagation();
                    buka(el.dataset.gambar, el.dataset.ket);
                }
            });
        });

        document.getElementById('modal-gambar-tutup')?.addEventListener('click', tutup);
        document.getElementById('modal-gambar-tombol')?.addEventListener('click', tutup);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') tutup(); });
    })();
    </script>
    @endpush
@endonce
