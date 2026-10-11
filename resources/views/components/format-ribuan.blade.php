{{-- Pemisah ribuan saat mengetik. Beri atribut data-format-ribuan pada input
     nominal supaya "50000" tampil "50.000". Server tetap membersihkan titik
     sebelum menyimpan, jadi angka yang dikirim selalu bersih. --}}
@once
    @push('kaki')
    <script>
    (() => {
        const format = (nilai) => nilai.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        document.querySelectorAll('[data-format-ribuan]').forEach((input) => {
            if (input.value) input.value = format(input.value);

            input.addEventListener('input', () => {
                const digitSebelum = input.value.slice(0, input.selectionStart).replace(/\D/g, '').length;

                input.value = format(input.value);

                let terlihat = 0;
                let pos = input.value.length;
                for (let i = 0; i < input.value.length; i++) {
                    if (/\d/.test(input.value[i])) terlihat++;
                    if (terlihat === digitSebelum) { pos = i + 1; break; }
                }

                input.setSelectionRange(pos, pos);
            });
        });
    })();
    </script>
    @endpush
@endonce
