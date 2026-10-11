{{--
    Modal pratinjau gambar sekali pakai per halaman.

    Seluruh modal "dititipkan" ke stack kaki lewat @once supaya berada di akhir
    <body>, di luar tautan/baris apa pun. Ini mencegah klik tombol tutup ikut
    menggelembung ke tautan induk (mis. baris transaksi yang menuju edit) atau
    mengirim form.

    Pemakaian: beri elemen apa pun class "js-preview" (opsional data-gambar &
    data-ket). Bila data-gambar kosong, sumber diambil dari <img> di dalamnya.
--}}
@once
    @push('kaki')
    <div id="modal-gambar" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/85 p-4" role="dialog" aria-modal="true" aria-label="Pratinjau gambar">
        <button type="button" id="modal-gambar-tutup" class="absolute inset-0 cursor-zoom-out" aria-label="Tutup pratinjau"></button>
        <figure class="relative z-10 flex max-h-full w-full max-w-lg flex-col">
            <img id="modal-gambar-img" src="" alt="Pratinjau gambar"
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

    <script>
    (() => {
        const modal = document.getElementById('modal-gambar');
        if (!modal) return;

        const gambar = document.getElementById('modal-gambar-img');
        const keterangan = document.getElementById('modal-gambar-ket');

        const sumberDari = (el) => el.dataset.gambar
            || el.querySelector('img')?.getAttribute('src')
            || el.getAttribute('src')
            || '';

        const buka = (el) => {
            const sumber = sumberDari(el);
            if (!sumber) return;

            gambar.src = sumber;
            keterangan.textContent = el.dataset.ket || '';
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
                buka(el);
            });
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.stopPropagation();
                    buka(el);
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
