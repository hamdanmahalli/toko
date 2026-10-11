{{--
    Keypad nominal (kalkulator) sebagai panel bawah untuk halaman catat/ubah
    transaksi.

    - Hanya muncul saat kolom nominal (#jumlah) disentuh/difokus.
    - Panel menutupi bottom nav (z-40 di atas nav z-20) dan bisa ditutup lewat
      tombol silang, klik latar, tombol Escape, atau tombol "Simpan" keypad.
    - Setiap ketukan langsung memperbarui kolom #jumlah.
    - Tombol "Simpan" pada keypad = konfirmasi (merapikan rumus jadi satu
      angka). Submit form tetap lewat tombol "Simpan" halaman.
    Nominal selalu bilangan bulat rupiah, jadi tombol koma tidak aktif.
--}}
@props([
    'jenis' => 'masuk',
    'nilai' => '',
])

@php
    $awal = (int) preg_replace('/\D/', '', (string) $nilai);
    $keluar = $jenis === 'keluar';
@endphp

<div id="pad-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/40"></div>

<div id="pad-sheet" class="fixed inset-x-0 bottom-0 z-40 translate-y-full transition-transform duration-300 ease-out">
    <div class="safe-bottom relative mx-auto max-w-lg rounded-t-3xl bg-white px-4 pb-4 pt-3 shadow-2xl ring-1 ring-slate-100">
        <div class="mb-2 flex items-center justify-between gap-2">
            <span class="mx-auto h-1.5 w-10 rounded-full bg-slate-200"></span>
            <button type="button" id="pad-tutup" aria-label="Tutup keypad"
                    class="absolute right-4 top-3 flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                </svg>
            </button>
        </div>

        <div id="pad-display" class="mb-3 rounded-2xl bg-slate-50 px-4 py-3 transition">
            <div class="flex items-center justify-between gap-2">
                <p id="pad-label" class="text-xs font-medium text-slate-400">{{ $keluar ? 'Total Pengeluaran' : 'Total Pemasukan' }}</p>
                <p id="pad-rumus" class="tabular truncate text-xs text-slate-400"></p>
            </div>
            <div id="pad-angka" class="mt-1 flex items-end gap-1 {{ $keluar ? 'text-merah-600' : 'text-brand-700' }}">
                <span id="pad-nilai" class="tabular text-3xl font-bold leading-none">Rp{{ number_format($awal, 0, ',', '.') }}</span>
                <span class="kursor mb-[3px] inline-block h-6 w-[3px] rounded-full bg-current"></span>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-2" style="grid-auto-rows: 3.25rem;">
            <button type="button" data-pad="clear" class="rounded-xl bg-merah-50 text-sm font-semibold text-merah-600 transition hover:bg-merah-100 active:scale-95">C</button>
            <button type="button" data-pad="op" data-nilai="/" class="rounded-xl bg-brand-50 text-lg font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-95">÷</button>
            <button type="button" data-pad="op" data-nilai="*" class="rounded-xl bg-brand-50 text-lg font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-95">×</button>
            <button type="button" data-pad="back" aria-label="Hapus satu" class="rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 active:scale-95">
                <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h11a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H9L3 12 9 5Zm2.5 4.5 5 5m0-5-5 5"/>
                </svg>
            </button>

            <button type="button" data-pad="num" data-nilai="1" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">1</button>
            <button type="button" data-pad="num" data-nilai="2" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">2</button>
            <button type="button" data-pad="num" data-nilai="3" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">3</button>
            <button type="button" data-pad="op" data-nilai="-" class="rounded-xl bg-brand-50 text-lg font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-95">−</button>

            <button type="button" data-pad="num" data-nilai="4" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">4</button>
            <button type="button" data-pad="num" data-nilai="5" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">5</button>
            <button type="button" data-pad="num" data-nilai="6" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">6</button>
            <button type="button" data-pad="op" data-nilai="+" class="rounded-xl bg-brand-50 text-lg font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-95">+</button>

            <button type="button" data-pad="num" data-nilai="7" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">7</button>
            <button type="button" data-pad="num" data-nilai="8" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">8</button>
            <button type="button" data-pad="num" data-nilai="9" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">9</button>
            <button type="button" data-pad="konfirmasi" class="row-span-2 rounded-xl bg-yellow-400 text-sm font-bold uppercase tracking-wide text-black transition hover:bg-yellow-300 active:scale-95">Simpan</button>

            <button type="button" data-pad="num" data-nilai="0" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">0</button>
            <button type="button" data-pad="num" data-nilai="000" class="rounded-xl bg-slate-50 text-lg font-semibold text-slate-700 transition hover:bg-slate-100 active:scale-95">000</button>
            <button type="button" disabled aria-label="Desimal tidak tersedia"
                    class="cursor-not-allowed rounded-xl bg-slate-50 text-lg font-semibold text-slate-300">,</button>
        </div>
    </div>
</div>

@once
    @push('kaki')
    <script>
    (() => {
        const jumlah = document.getElementById('jumlah');
        const sheet = document.getElementById('pad-sheet');
        const overlay = document.getElementById('pad-overlay');
        if (!jumlah || !sheet) return;

        const display = document.getElementById('pad-display');
        const angka = document.getElementById('pad-angka');
        const nilai = document.getElementById('pad-nilai');
        const rumus = document.getElementById('pad-rumus');
        const label = document.getElementById('pad-label');

        let expr = jumlah.value.replace(/\D/g, '');

        const format = (n) => n.toLocaleString('id-ID');

        const hitung = (teks) => {
            if (!teks) return 0;
            let total = 0;
            let operator = '+';
            const bagian = teks.match(/\d+|[+\-*/]/g) || [];
            for (const token of bagian) {
                if (/[+\-*/]/.test(token)) {
                    operator = token;
                    continue;
                }
                const n = parseInt(token, 10);
                if (operator === '+') total += n;
                else if (operator === '-') total -= n;
                else if (operator === '*') total *= n;
                else if (operator === '/') total = n === 0 ? total : Math.floor(total / n);
            }
            return total;
        };

        const tampil = () => {
            const n = Math.max(0, Math.round(hitung(expr)));
            rumus.textContent = expr;
            nilai.textContent = 'Rp' + format(n);
            jumlah.value = n > 0 ? format(n) : '';
        };

        const getar = () => {
            display.classList.add('getar', 'ring-merah-300');
            window.setTimeout(() => display.classList.remove('getar', 'ring-merah-300'), 400);
        };

        const warnai = () => {
            const keluar = document.querySelector('input[name="jenis"]:checked')?.value === 'keluar';
            angka.classList.toggle('text-merah-600', keluar);
            angka.classList.toggle('text-brand-700', !keluar);
            label.textContent = keluar ? 'Total Pengeluaran' : 'Total Pemasukan';
        };

        const buka = () => {
            document.getElementById('pad-overlay').classList.remove('hidden');
            document.getElementById('pad-sheet').classList.remove('translate-y-full');
        };

        const tutup = () => {
            document.getElementById('pad-overlay').classList.add('hidden');
            document.getElementById('pad-sheet').classList.add('translate-y-full');
        };

        const tekan = (tombol) => {
            const aksi = tombol.dataset.pad;

            if (aksi === 'clear') {
                expr = '';
            } else if (aksi === 'back') {
                expr = expr.slice(0, -1);
            } else if (aksi === 'num') {
                expr += tombol.dataset.nilai;
            } else if (aksi === 'op') {
                if (expr === '') return;
                if (/[+\-*/]$/.test(expr)) expr = expr.slice(0, -1);
                expr += tombol.dataset.nilai;
            } else if (aksi === 'konfirmasi') {
                const n = Math.round(hitung(expr));
                if (!expr || n < 1) return getar();
                expr = String(n);
                tampil();
                jumlah.blur();
                return tutup();
            }

            tampil();
        };

        document.querySelectorAll('[data-pad]').forEach((tombol) => {
            tombol.addEventListener('click', () => tekan(tombol));
        });

        document.querySelectorAll('input[name="jenis"]').forEach((radio) => {
            radio.addEventListener('change', warnai);
        });

        jumlah.addEventListener('focus', buka);
        jumlah.addEventListener('click', buka);
        document.getElementById('pad-tutup').addEventListener('click', tutup);
        document.getElementById('pad-overlay').addEventListener('click', tutup);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') tutup();
        });

        warnai();
        tampil();
    })();
    </script>
    @endpush
@endonce
