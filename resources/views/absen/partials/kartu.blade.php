{{--
    Kartu absensi karyawan.

    Latar kartu (header hijau, gelombang, dan frame foto) sudah disediakan
    sebagai gambar di public/img/kartu-karyawan.png. View ini hanya menempelkan
    foto karyawan ke dalam frame putih dan menuliskan data (nama, NIP, jabatan,
    QR + barcode) di area putih bawah.

    Dipakai oleh halaman admin (/admin/karyawan/{id}/qr) dan halaman karyawan
    (/saya-qr) supaya keduanya menampilkan kartu yang sama persis.
--}}
<div class="kartu-pegawai" id="kartu-pegawai">
    {{-- Foto karyawan, mengisi frame putih pada latar. --}}
    <div class="kartu-foto">
        @if ($pengguna->fotoUrl())
            <img src="{{ $pengguna->fotoUrl() }}" alt="Foto {{ $pengguna->nama }}">
        @else
            <span class="kartu-foto-kosong">
                <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/>
                </svg>
            </span>
        @endif
    </div>

    {{-- Data karyawan di area putih bawah. --}}
    <div class="kartu-info">
        <p class="kartu-nama">{{ $pengguna->nama }}</p>
        <p class="kartu-nip">{{ $pengguna->nip ?? 'Tanpa NIP' }}</p>
        <p class="kartu-jabatan">{{ $pengguna->position->nama ?? 'Karyawan' }}</p>

        {{-- Dua kode, satu isi: QR untuk kamera, barcode untuk scanner USB
             perangkat presensi. Keduanya berisi token yang sama. --}}
        <div class="kartu-kode">
            <p class="sr-only">Kode untuk perangkat presensi</p>
            <div class="kartu-qr">{!! $qrSvg !!}</div>
            <div class="kartu-barcode">{!! $barcodeSvg !!}</div>
        </div>
    </div>
</div>

<style>
    /* Semua ukuran memakai satuan container (cqw) supaya kartu bisa mengecil
       di layar HP tanpa kehilangan proporsi. Ukuran asli latar 638x1012. */
    .kartu-pegawai {
        position: relative;
        width: 100%;
        max-width: 360px;
        aspect-ratio: 638 / 1012;
        container-type: inline-size;
        border-radius: 14px;
        overflow: hidden;
        background-color: #2d5a43;
        background-image: url('{{ asset('img/kartu-karyawan.png') }}');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        box-shadow: 0 18px 40px -20px rgba(45, 90, 67, .55);
    }

    /* Frame putih pada latar: kiri 31.5%, atas 20.8%, 37% x 22.6%. */
    .kartu-foto {
        position: absolute;
        left: 33.7%;
        top: 21.9%;
        width: 33%;
        height: 20.9%;
        overflow: hidden;
        border-radius: 3.6cqw;
        background: #fff;
    }

    .kartu-foto img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center 28%;
    }

    .kartu-foto-kosong {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: #cbd5e1;
    }

    .kartu-foto-kosong svg {
        width: 46%;
        height: 46%;
    }

    .kartu-info {
        position: absolute;
        left: 8%;
        right: 8%;
        top: 45.5%;
        bottom: 8%;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .kartu-nama {
        font-size: 6.4cqw;
        font-weight: 700;
        line-height: 1.08;
        color: #2d5a43;
    }

    .kartu-nip {
        margin-top: 1.8cqw;
        font-size: 3.6cqw;
        font-weight: 500;
        letter-spacing: .06em;
        color: #64748b;
    }

    .kartu-jabatan {
        margin-top: 1.2cqw;
        font-size: 3.8cqw;
        color: #475569;
    }

    .kartu-kode {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8cqw;
        width: 100%;
    }

    .kartu-qr {
        width: 33cqw;
        height: 33cqw;
        padding: 2.4cqw;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 3cqw;
    }

    .kartu-qr svg {
        display: block;
        width: 100%;
        height: 100%;
    }

    .kartu-barcode {
        width: 80%;
    }

    .kartu-barcode svg {
        display: block;
        width: 100%;
        height: auto;
    }

    /* Hanya berlaku di halaman yang merender kartu: supaya "Cetak" menghasilkan
       kartu saja, bukan seluruh halaman dengan menu. */
    @media print {
        header, nav, aside, #menu-admin {
            display: none !important;
        }

        body {
            background: #fff;
        }

        .kartu-pegawai {
            width: 90mm;
            max-width: none;
            box-shadow: none;
        }
    }
</style>
