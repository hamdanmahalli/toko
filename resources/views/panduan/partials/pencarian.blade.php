{{-- Pendamping daftar isi. Sengaja vanilla JS supaya tidak menambah dependensi.
     Kotak pencarian dipakai di dua tempat (HP dan layar lebar), jadi ditandai
     lewat atribut data, bukan id. --}}
<script>
(() => {
    const kotak = Array.from(document.querySelectorAll('[data-panduan-cari]'));
    const tautan = Array.from(document.querySelectorAll('[data-panduan-tautan]'));

    const normalize = (teks) => teks
        .toLocaleLowerCase('id')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    const terapkan = (nilai) => {
        const cari = normalize(nilai.trim());
        let adaYangCocok = false;

        document.querySelectorAll('[data-panduan-item]').forEach((item) => {
            const cocok = cari === '' || normalize(item.dataset.panduanItem).includes(cari);

            item.classList.toggle('hidden', !cocok);
            if (cocok) adaYangCocok = true;
        });

        document.querySelectorAll('[data-panduan-daftar]').forEach((daftar) => {
            daftar.querySelector('[data-panduan-kosong]')?.classList.toggle('hidden', adaYangCocok);

            // Hasil filter di HP tersembunyi di balik details yang tertutup.
            if (cari !== '') daftar.closest('details')?.setAttribute('open', '');
        });
    };

    if (kotak.length > 0) {
        kotak.forEach((sumber) => {
            sumber.addEventListener('input', () => {
                // Semua kotak disinkronkan supaya mengetik di HP terlihat di
                // layar lebar, dan sebaliknya.
                kotak.forEach((lain) => {
                    if (lain !== sumber) lain.value = sumber.value;
                });

                terapkan(sumber.value);
            });
        });
    }

    // Tanda bagian yang sedang dibaca. Browser tidak pernah mengirim fragmen URL,
    // jadi posisi scroll yang dipakai sebagai rujukan.
    const tandai = (id) => {
        tautan.forEach((tautan) => {
            const aktif = tautan.dataset.panduanTautan === id;

            tautan.classList.toggle('bg-brand-600', aktif);
            tautan.classList.toggle('font-medium', aktif);
            tautan.classList.toggle('text-white', aktif);
            tautan.classList.toggle('text-slate-600', !aktif);
            tautan.classList.toggle('hover:bg-brand-50', !aktif);
            tautan.classList.toggle('hover:text-brand-800', !aktif);
        });
    };

    if (tautan.length > 0) {
        const bagian = tautan
            .map((tautan) => document.getElementById(tautan.dataset.panduanTautan))
            .filter(Boolean);

        const sedang = () => {
            const garis = window.scrollY + 120;
            let id = bagian.length > 0 ? bagian[0].id : null;

            bagian.forEach((el) => {
                if (el.offsetTop <= garis) id = el.id;
            });

            return id;
        };

        tandai(sedang());
        window.addEventListener('scroll', () => tandai(sedang()), { passive: true });
    }
})();
</script>