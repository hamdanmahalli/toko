{{--
    Keypad nominal terintegrasi untuk halaman catat/ubah transaksi.

    Urutan render: display (atas) - slot (tengah, isi field) - keypad (bawah).
    Slot dipakai supaya field lain berada di antara display dan keypad tanpa
    memecah komponen.

    - Display: label + nominal besar + kursor berkedip + sub-teks rumus.
    - Keypad 4x5: C ÷ × ⌫ / 1 2 3 − / 4 5 6 + / 7 8 9 SIMPAN / 0 000 ,
    - SIMPAN mengevaluasi ekspresi lalu mengisi input jumlah dan submit form.
    Nominal selalu bilangan bulat rupiah, jadi tombol koma hanya tampil dan
    tidak aktif.
--}}
@props([
    'jenis' => 'masuk',
    'nilai' => '',
])

@php
    $awal = preg_replace('/\D/', '', (string) $nilai);
    $keluar = $jenis === 'keluar';
@endphp

<div class="flex min-h-0 flex-1 flex-col">
    <input type="hidden" name="jumlah" id="pad-jumlah" value="{{ $awal }}">

    <div id="pad-display" class="mb-3 shrink-0 rounded-2xl bg-white p-4 ring-1 ring-slate-100 transition">
        <p id="pad-label" class="text-xs font-medium text-slate-400">{{ $keluar ? 'Total Pengeluaran' : 'Total Pemasukan' }}</p>
        <div id="pad-angka" class="mt-1.5 flex items-end gap-1 {{ $keluar ? 'text-merah-600' : 'text-brand-700' }}">
            <span id="pad-nilai" class="tabular text-[32px] font-bold leading-none">Rp0</span>
            <span class="kursor mb-[3px] inline-block h-7 w-[3px] rounded-full bg-current"></span>
        </div>
        <p id="pad-rumus" class="tabular mt-2 h-4 truncate text-xs text-slate-400"></p>
    </div>

    {{ $slot }}

    <div class="grid shrink-0 grid-cols-4 gap-2 pb-2 safe-bottom" style="grid-auto-rows: 3.25rem;">
        <button type="button" data-pad="clear" class="rounded-xl bg-merah-50 text-sm font-semibold text-merah-600 transition hover:bg-merah-100 active:scale-95">C</button>
        <button type="button" data-pad="op" data-nilai="/" class="rounded-xl bg-brand-50 text-lg font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-95">÷</button>
        <button type="button" data-pad="op" data-nilai="*" class="rounded-xl bg-brand-50 text-lg font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-95">×</button>
        <button type="button" data-pad="back" aria-label="Hapus satu" class="rounded-xl bg-slate-100 text-slate-600 transition hover:bg-slate-200 active:scale-95">
            <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h11a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H9L3 12l6-7Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9l6 6m0-6l-6 6"/>
            </svg>
        </button>

        @foreach ([1, 2, 3] as $n)
            <button type="button" data-pad="num" data-nilai="{{ $n }}" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-800 transition hover:bg-slate-100 active:scale-95">{{ $n }}</button>
        @endforeach
        <button type="button" data-pad="op" data-nilai="-" class="rounded-xl bg-brand-50 text-lg font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-95">−</button>

        @foreach ([4, 5, 6] as $n)
            <button type="button" data-pad="num" data-nilai="{{ $n }}" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-800 transition hover:bg-slate-100 active:scale-95">{{ $n }}</button>
        @endforeach
        <button type="button" data-pad="op" data-nilai="+" class="rounded-xl bg-brand-50 text-lg font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-95">+</button>

        @foreach ([7, 8, 9] as $n)
            <button type="button" data-pad="num" data-nilai="{{ $n }}" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-800 transition hover:bg-slate-100 active:scale-95">{{ $n }}</button>
        @endforeach
        <button type="submit" class="row-span-2 rounded-xl bg-yellow-400 text-sm font-bold uppercase tracking-wide text-black transition hover:bg-yellow-300 active:scale-95">Simpan</button>

        <button type="button" data-pad="num" data-nilai="0" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-800 transition hover:bg-slate-100 active:scale-95">0</button>
        <button type="button" data-pad="num" data-nilai="000" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-800 transition hover:bg-slate-100 active:scale-95">000</button>
        <button type="button" disabled aria-label="Desimal tidak tersedia"
                class="cursor-not-allowed rounded-xl bg-slate-50 text-lg font-semibold text-slate-300">,</button>
    </div>
</div>

@once
    @push('kaki')
    <script>
    (() => {
        const jumlah = document.getElementById('pad-jumlah');
        if (!jumlah) return;

        const form = jumlah.closest('form');
        const display = document.getElementById('pad-display');
        const angka = document.getElementById('pad-angka');
        const nilai = document.getElementById('pad-nilai');
        const rumus = document.getElementById('pad-rumus');
        const label = document.getElementById('pad-label');

        const opRegex = /[+\-*/]/;
        let expr = (jumlah.value || '').replace(/[^\d+\-*/]/g, '');

        const format = (n) => Math.round(n).toLocaleString('id-ID');

        function hitung(teks) {
            const bagian = teks.match(/(\d+|[+\-*/])/g);
            if (!bagian) return 0;

            let total = parseFloat(bagian[0]) || 0;
            for (let i = 1; i < bagian.length - 1; i += 2) {
                const op = bagian[i];
                const n = parseFloat(bagian[i + 1]);
                if (isNaN(n)) break;
                if (op === '+') total += n;
                else if (op === '-') total -= n;
                else if (op === '*') total *= n;
                else if (op === '/') total = n === 0 ? total : total / n;
            }
            return total;
        }

        function tampil() {
            rumus.textContent = expr;
            if (expr === '' || /^[+\-*/]+$/.test(expr)) {
                nilai.textContent = 'Rp0';
            } else {
                nilai.textContent = 'Rp' + format(hitung(expr));
            }
        }

        function warnai() {
            const keluar = document.querySelector('input[name="jenis"]:checked')?.value === 'keluar';
            angka.classList.toggle('text-merah-600', keluar);
            angka.classList.toggle('text-brand-700', !keluar);
            label.textContent = keluar ? 'Total Pengeluaran' : 'Total Pemasukan';
        }

        function tekan(tombol) {
            const aksi = tombol.dataset.pad;

            if (aksi === 'clear') {
                expr = '';
            } else if (aksi === 'back') {
                expr = expr.slice(0, -1);
            } else if (aksi === 'num') {
                expr += tombol.dataset.nilai;
            } else if (aksi === 'op') {
                if (expr === '') return;
                if (opRegex.test(expr.at(-1))) expr = expr.slice(0, -1);
                expr += tombol.dataset.nilai;
            }

            tampil();
        }

        document.querySelectorAll('[data-pad]').forEach((tombol) => {
            tombol.addEventListener('click', () => tekan(tombol));
        });

        document.querySelectorAll('input[name="jenis"]').forEach((radio) => {
            radio.addEventListener('change', warnai);
        });

        form?.addEventListener('submit', (event) => {
            const nilaiAkhir = Math.round(hitung(expr));

            if (!expr || !Number.isFinite(nilaiAkhir) || nilaiAkhir < 1) {
                event.preventDefault();
                display.classList.add('getar', 'ring-merah-300');
                window.setTimeout(() => display.classList.remove('getar', 'ring-merah-300'), 400);
                return;
            }

            jumlah.value = String(nilaiAkhir);
        });

        warnai();
        tampil();
    })();
    </script>
    @endpush
@endonce
