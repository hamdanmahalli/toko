<div class="card p-5">
    <p class="text-[13px] leading-relaxed text-slate-600">
        Aplikasi ini dipakai untuk mencatat kehadiran karyawan per toko. Setiap karyawan
        mencatat kehadirannya saat datang dan pulang, dari HP dengan pemeriksaan lokasi di dalam
        toko, atau dari perangkat presensi toko lewat kartunya, lalu sistem menentukan apakah
        kehadiran itu tepat waktu, terlambat, atau terlalu cepat.
    </p>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Semua perhitungan dilakukan ulang di server. Aplikasi tidak mempercayai angka yang
        dikirimkan perangkat, jadi hasil absensi tidak bisa dimanipulasi dari sisi karyawan.
    </p>
</div>

@php($barisBagian = [
    ['<span class="font-medium text-slate-800">Mode karyawan</span>', 'Semua karyawan',
     'Absen datang dan pulang, melihat kartu absen, riwayat sendiri, dan mengajukan izin atau lembur.'],
    ['<span class="font-medium text-slate-800">Mode admin</span>', 'Pemilik dan supervisor',
     'Mengatur toko, karyawan, jabatan, jadwal shift, menyetujui pengajuan, dan melihat laporan.'],
    ['<span class="font-medium text-slate-800">Presensi Karyawan</span>', 'Karyawan yang tidak membawa HP',
     'Halaman pemindai kartu di toko untuk mereka yang tidak bisa absen dari HP. Satu user dan password per toko dipakai bersama untuk membukanya.'],
    ['<span class="font-medium text-slate-800">Panduan ini</span>', 'Siapa saja',
     'Halaman yang sedang dibaca. Terbuka tanpa login, jadi bisa dibaca siapa saja.'],
])

<x-panduan.tabel :kolom="['Bagian', 'Dipakai siapa', 'Isi']" :baris="$barisBagian"/>

<div class="mt-3">
    <x-panduan.catatan tipe="info" judul="Dua istilah yang perlu dibedakan">
        <p>
            <span class="font-medium">Jabatan</span> menentukan aturan jam absen seorang karyawan:
            wajib mengikuti template shift, atau cukup ikut rentang window.
        </p>
        <p class="mt-1">
            <span class="font-medium">Toko</span> adalah cabang tempat karyawan bekerja. Setiap toko punya
            titik koordinat dan radius sendiri yang dipakai untuk memeriksa lokasi saat absen.
        </p>
    </x-panduan.catatan>
</div>