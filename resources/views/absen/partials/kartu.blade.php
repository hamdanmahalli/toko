{{--
    Kartu absensi cetak. Dipakai oleh halaman karyawan (/saya-qr) dan admin
    (/admin/karyawan/{id}/qr) supaya keduanya mencetak kartu yang sama persis.

    Dua kode, satu isi: barcode Code128 untuk scanner USB perangkat presensi, QR sebagai
    kode pendamping untuk reader kamera atau pemindaian manual. Keduanya berisi token yang
    sama, jadi rotasi kartu membatalkan keduanya sekaligus.
--}}
<div class="kartu-qr card border-t-4 border-merah-500 p-6 text-center print:p-0 print:shadow-none">
    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">
        {{ $pengguna->shop->nama }}
    </p>
    <p class="mt-1 font-display text-xl text-slate-900">{{ $pengguna->nama }}</p>
    <p class="text-xs text-slate-500">{{ $pengguna->nip ?? 'Tanpa NIP' }}</p>

    <div class="mx-auto mt-4 w-fit rounded-2xl bg-white p-4 ring-1 ring-slate-200">
        {!! $qrSvg !!}
    </div>

    {{-- Barcode ini yang dibaca scanner perangkat presensi. Lebarnya dibuat pendek
     supaya muat di kartu dompet, tapi tetap cukup tinggi untuk discan. --}}
    <div class="mt-5">
        <p class="mb-1 text-[11px] font-medium uppercase tracking-wider text-slate-400">
            Kode untuk perangkat presensi
        </p>
        <div class="mx-auto w-full max-w-[300px] bg-white">
            {!! $barcodeSvg !!}
        </div>
        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
            Tahan kartu di scanner perangkat presensi, atau ketik kode di bawah secara manual.
        </p>
    </div>

    <code class="mt-3 block select-all break-all rounded-lg bg-slate-50 px-3 py-2 text-center text-[11px] text-slate-500">
        {{ $token }}
    </code>

    <p class="mt-3 text-[11px] text-slate-400">Versi {{ $pengguna->qr_version }}</p>
</div>

<style>
    /* Hanya berlaku di halaman yang merender kartu. Fungsinya supaya tombol
       "Cetak" menghasilkan kartu saja, bukan seluruh halaman dengan menu. */
    @media print {
        header, nav, aside, #menu-admin {
            display: none !important;
        }

        body {
            background: #fff;
        }
    }
</style>