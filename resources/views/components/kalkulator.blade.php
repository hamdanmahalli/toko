{{-- Kalkulator mengambang. Tombol mana pun dengan atribut data-kalkulator-buka
     berisi id input tujuan; menekan tombol membuka dialog ini dan hasilnya
     dituang kembali ke input tersebut. Tanpa JavaScript, input tetap bisa
     diketik manual, jadi halaman tidak kehilangan fungsi. --}}
<dialog id="kalkulator"
        class="m-auto w-[calc(100%-2rem)] max-w-xs rounded-[24px] bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-900/40 backdrop:backdrop-blur-sm">
    <div class="p-5">
        <div class="mb-3 flex items-start justify-between gap-3">
            <h2 class="font-display text-base text-slate-900">Kalkulator</h2>
            <button type="button" data-kalkulator-tutup aria-label="Tutup"
                    class="-mr-1 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="mb-3 overflow-x-auto rounded-2xl bg-slate-50 px-4 py-3 text-right ring-1 ring-slate-100">
            <p id="kalkulator-hasil" class="tabular whitespace-nowrap text-2xl font-bold text-slate-900">0</p>
            <p id="kalkulator-ekspresi" class="tabular mt-0.5 h-4 whitespace-nowrap text-xs text-slate-400"></p>
        </div>

        <div class="grid grid-cols-4 gap-2" style="grid-template-rows: repeat(5, minmax(0, 1fr));">
            <button type="button" data-kalkulator="clear" class="col-span-2 rounded-xl bg-merah-50 py-3 text-sm font-semibold text-merah-600 transition hover:bg-merah-100">C</button>
            <button type="button" data-kalkulator="back" class="rounded-xl bg-slate-100 py-3 text-slate-600 transition hover:bg-slate-200">
                <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h11a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H9L3 12l6-7Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9l6 6m0-6l-6 6"/>
                </svg>
            </button>
            <button type="button" data-kalkulator="op" data-nilai="/" class="rounded-xl bg-brand-50 py-3 text-base font-semibold text-brand-700 transition hover:bg-brand-100">÷</button>

            @foreach ([7, 8, 9] as $n)
                <button type="button" data-kalkulator="num" data-nilai="{{ $n }}" class="rounded-xl bg-slate-50 py-3 text-base font-semibold text-slate-800 transition hover:bg-slate-100">{{ $n }}</button>
            @endforeach
            <button type="button" data-kalkulator="op" data-nilai="*" class="rounded-xl bg-brand-50 py-3 text-base font-semibold text-brand-700 transition hover:bg-brand-100">×</button>

            @foreach ([4, 5, 6] as $n)
                <button type="button" data-kalkulator="num" data-nilai="{{ $n }}" class="rounded-xl bg-slate-50 py-3 text-base font-semibold text-slate-800 transition hover:bg-slate-100">{{ $n }}</button>
            @endforeach
            <button type="button" data-kalkulator="op" data-nilai="-" class="rounded-xl bg-brand-50 py-3 text-base font-semibold text-brand-700 transition hover:bg-brand-100">−</button>

            @foreach ([1, 2, 3] as $n)
                <button type="button" data-kalkulator="num" data-nilai="{{ $n }}" class="rounded-xl bg-slate-50 py-3 text-base font-semibold text-slate-800 transition hover:bg-slate-100">{{ $n }}</button>
            @endforeach
            <button type="button" data-kalkulator="op" data-nilai="+" class="row-span-2 rounded-xl bg-brand-50 py-3 text-base font-semibold text-brand-700 transition hover:bg-brand-100">+</button>

            <button type="button" data-kalkulator="num" data-nilai="0" class="col-span-2 rounded-xl bg-slate-50 py-3 text-base font-semibold text-slate-800 transition hover:bg-slate-100">0</button>
            <button type="button" data-kalkulator="dot" class="rounded-xl bg-slate-50 py-3 text-base font-semibold text-slate-800 transition hover:bg-slate-100">.</button>
        </div>

        <button type="button" id="kalkulator-pakai"
                class="mt-3 w-full rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-3 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
            Pakai angka ini
        </button>
    </div>
</dialog>

@once
    @push('kaki')
    <script>
    (() => {
        const dialog = document.getElementById('kalkulator');
        if (!dialog) return;

        const hasil = document.getElementById('kalkulator-hasil');
        const ekspresi = document.getElementById('kalkulator-ekspresi');

        let target = null;
        let expr = '';

        const rapikan = (teks) => teks.replace(/\d+(?:\.\d+)?/g, (n) => {
            const [bulat, desimal] = n.split('.');
            const ribu = Number(bulat).toLocaleString('id-ID');
            return desimal !== undefined ? ribu + ',' + desimal : ribu;
        });

        function hitung(teks) {
            const bagian = teks.match(/(\d+\.?\d*|\.\d+|[+\-*/])/g);
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
            if (expr === '') {
                hasil.textContent = '0';
                ekspresi.textContent = '';
                return;
            }
            const akhir = expr.at(-1);
            if ('+-*/'.includes(akhir)) {
                hasil.textContent = rapikan(expr.slice(0, -1)) + ' ' + akhir;
                ekspresi.textContent = '';
            } else {
                hasil.textContent = rapikan(expr);
                ekspresi.textContent = '';
            }
        }

        const opRegex = /[+\-*/]/;

        function tekan(tombol) {
            const aksi = tombol.dataset.kalkulator;

            if (aksi === 'clear') {
                expr = '';
            } else if (aksi === 'back') {
                expr = expr.slice(0, -1);
            } else if (aksi === 'num') {
                expr += tombol.dataset.nilai;
            } else if (aksi === 'dot') {
                const terakhir = expr.split(opRegex).pop();
                if (!terakhir.includes('.')) expr += terakhir === '' ? '0.' : '.';
            } else if (aksi === 'op') {
                if (expr === '') return;
                if (opRegex.test(expr.at(-1))) expr = expr.slice(0, -1);
                expr += tombol.dataset.nilai;
            }

            tampil();
        }

        dialog.querySelectorAll('[data-kalkulator]').forEach((tombol) => {
            tombol.addEventListener('click', () => tekan(tombol));
        });

        document.querySelectorAll('[data-kalkulator-buka]').forEach((pemicu) => {
            pemicu.addEventListener('click', () => {
                target = document.getElementById(pemicu.getAttribute('data-kalkulator-buka'));
                if (!target) return;
                expr = (target.value || '').replace(/[^\d+\-*/]/g, '');
                tampil();
                if (typeof dialog.showModal === 'function') dialog.showModal();
            });
        });

        document.getElementById('kalkulator-pakai')?.addEventListener('click', () => {
            if (target) {
                const nilai = hitung(expr);
                const bulat = Math.round(Number.isFinite(nilai) ? nilai : 0);
                target.value = bulat.toLocaleString('id-ID');
            }
            dialog.close();
        });

        dialog.querySelector('[data-kalkulator-tutup]')?.addEventListener('click', () => dialog.close());

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    })();
    </script>
    @endpush
@endonce
