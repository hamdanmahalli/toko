<div>
    <h3 class="font-display text-[15px] text-slate-900">Karyawan</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Data karyawan, jabatan, gaji, penugasan shift, dan kartu absen.
    </p>

@php($barisKolomKaryawan = [
    ['Nama lengkap', 'Nama yang tampil di daftar, laporan, dan pengajuan. <span class="text-amber-700">Wajib.</span>'],
    ['NIP', 'Nomor induk karyawan. Boleh dikosongkan, tapi harus unik bila diisi.'],
    ['Telepon', 'Nomor kontak. Boleh dikosongkan.'],
    ['Email', 'Dipakai sebagai alamat login kalau akun dibuat. Harus unik. <span class="text-amber-700">Wajib</span> bila membuat akun.'],
    ['Toko', 'Toko tempat karyawan bekerja. Supervisor hanya bisa memilih toko yang diawasi. <span class="text-amber-700">Wajib.</span>'],
    ['Jabatan', 'Menentukan apakah karyawan wajib mengikuti template shift atau cukup ikut window.'],
    ['Template shift', 'Penugasan template khusus untuk satu karyawan. Mengosongkannya berarti kembali mengikuti aturan jabatan dan toko.'],
    ['Shift berlaku mulai', 'Tanggal mulai penugasan template. Kosongkan untuk memakai tanggal masuk karyawan.'],
    ['Tanggal masuk', 'Tanggal karyawan mulai bekerja.'],
    ['Tipe gaji', '<span class="font-medium text-slate-800">Gaji Harian</span> atau <span class="font-medium text-slate-800">Gaji Per Jam</span>. <span class="text-amber-700">Wajib.</span>'],
    ['Gaji harian (Rp)', 'Nominal gaji per hari untuk tipe gaji harian.'],
    ['Tarif per jam (Rp)', 'Nominal per jam untuk tipe gaji per jam.'],
    ['Catatan', 'Catatan bebas, maksimal 255 karakter.'],
    ['Karyawan aktif', 'Bila dimatikan, karyawan tidak bisa absen dan akun logannya ikut dinonaktifkan. Riwayat tetap tersimpan.'],
])

<x-panduan.tabel :kolom="['Kolom', 'Keterangan']" :baris="$barisKolomKaryawan"/>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Buat akun login</h4>
    <p class="mt-1 text-[13px] leading-relaxed text-slate-600">
        Bagian <span class="font-medium text-slate-700">Buat akun login</span> hanya muncul saat
        menambah karyawan baru. Setelah akun ada, bagian ini hilang dan tidak muncul lagi.
    </p>
    <ul class="mt-2 space-y-1.5 text-[13px] leading-relaxed text-slate-600">
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>Akun otomatis mendapat peran <span class="font-medium text-slate-800">karyawan</span>: bisa absen, melihat kartu dan riwayat sendiri, serta mengajukan izin dan lembur.</span>
        </li>
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>Password minimal 8 karakter dan harus diketik dua kali.</span>
        </li>
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>Akun ikut aturan satu akun satu perangkat.</span>
        </li>
    </ul>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Kartu absen</h4>
    <p class="mt-1 text-[13px] leading-relaxed text-slate-600">
        Setiap karyawan punya satu kartu pribadi yang memuat QR Code dan barcode dengan kode yang
        sama. Kartu dipakai untuk absen dari perangkat presensi toko: barcode untuk scanner USB,
        QR untuk reader kamera atau diketik manual. Absen dari HP tidak memakai kartu ini,
        melainkan tombol di beranda yang memeriksa lokasi. Tombol
        <span class="font-medium text-slate-700">Ganti kartu QR</span> menerbitkan versi baru.
        Semua kartu lama yang sudah dicetak langsung tidak berlaku, di toko mana pun. Gunakan ini
        kalau kartu hilang atau dicuri.
    </p>
    <p class="mt-1 text-[13px] leading-relaxed text-slate-600">
        Halaman <span class="font-medium text-slate-700">Kartu QR</span> punya tombol cetak untuk
        mencetak kartu QR dan barcode sekaligus. Kartu dari toko lain tidak akan diterima di
        perangkat presensi toko tersebut.
    </p>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Izin perangkat presensi</h4>
    <p class="mt-1 text-[13px] leading-relaxed text-slate-600">
        Centang <span class="font-medium text-slate-700">Boleh absen lewat perangkat presensi</span>
        untuk mengizinkan karyawan memakai kartunya di perangkat presensi toko, misalnya untuk
        karyawan yang tidak membawa HP. Centang ini bawaan aktif setiap karyawan baru. Karyawan
        yang tidak diizinkan akan ditolak perangkatnya, dengan alasan tersimpan di audit log.
        Mengosongkannya sama sekali tidak mengubah absen dari HP.
    </p>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Nonaktifkan karyawan</h4>
    <p class="mt-1 text-[13px] leading-relaxed text-slate-600">
        Tombol <span class="font-medium text-slate-700">Nonaktifkan</span> ada di halaman ubah
        karyawan. Karyawan tidak dihapus, hanya ditandai tidak aktif dan tanggal keluar diisi,
        supaya riwayat absensinya tetap bisa dibaca di laporan.
    </p>

    <div class="mt-3">
        <x-panduan.catatan tipe="info">
            Ada dua hak akses terpisah: <span class="font-medium">tambah dan ubah</span> memakai izin
            <span class="italic">karyawan.kelola</span>. Hak <span class="italic">karyawan.hapus</span>
            disimpan untuk penghapusan permanen di masa depan dan belum dipakai.
        </x-panduan.catatan>
    </div>
</div>