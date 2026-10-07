@php($langkahKaryawanBaru = [
    ['Minta akun ke atasan',
     'Akun dibuat otomatis dari menu Karyawan di mode admin. Atasan yang memberikan email dan password awal.'],
    ['Masuk memakai email dan password',
     'Butuh izin lokasi di peramban. Kalau ditolak, absen tidak akan bisa tercatat.'],
    ['Absen masuk saat tiba',
     'Tekan tombol <span class="font-medium text-slate-700">Absen Masuk</span> di bawah layar beranda. Tunggu sampai muncul keterangan berhasil.'],
    ['Absen pulang saat pulang',
     'Tombol <span class="font-medium text-slate-700">Absen Pulang</span> baru aktif setelah absen masuk tercatat.'],
    ['Simpan kartu absen',
     'Buka halaman <span class="font-medium text-slate-700">Kartu Saya</span>, lalu cetak kartunya. Kartu itu juga dipakai lewat perangkat presensi toko.'],
])

<h3 class="font-display text-[15px] text-slate-900">Karyawan baru</h3>

<x-panduan.langkah :langkah="$langkahKaryawanBaru"/>

@php($langkahAdminBaru = [
    ['Isi data toko lebih dulu',
     'Menu <span class="font-medium text-slate-700">Toko</span>. Koordinat dan radius wajib diisi karena geofence memakai titik itu.'],
    ['Buat jabatan sesuai aturan kerja',
     'Menu <span class="font-medium text-slate-700">Jabatan</span>. Centang <span class="font-medium text-slate-700">Wajib memakai template shift</span> untuk manajer atau kepala toko.'],
    ['Susun jadwal shift',
     'Menu <span class="font-medium text-slate-700">Shift</span> untuk template mingguan, menu <span class="font-medium text-slate-700">Window</span> untuk rentang jam kasir dan pramuniaga.'],
    ['Tambahkan karyawan',
     'Menu <span class="font-medium text-slate-700">Karyawan</span>. Centang <span class="font-medium text-slate-700">Buat akun</span> supaya karyawan bisa absen sendiri.'],
    ['Siapkan perangkat presensi di toko yang dipakai',
     'Alamat perangkat presensi dan user serta password-nya ada di form toko. Cetak kartu barcode tiap karyawan supaya bisa absen tanpa HP.'],
    ['Tetapkan siapa yang mengawasi toko mana',
     'Menu <span class="font-medium text-slate-700">Pengguna</span>. Supervisor yang belum ditugaskan ke toko mana pun tidak akan melihat data apa pun.'],
    ['Periksa pengajuan tiap pagi',
     'Menu <span class="font-medium text-slate-700">Pengajuan</span>. Penolakan wajib disertai alasan.'],
])

<h3 class="mt-6 font-display text-[15px] text-slate-900">Admin yang baru memakai aplikasi</h3>

<x-panduan.langkah :langkah="$langkahAdminBaru"/>

<div class="mt-4">
    <x-panduan.catatan tipe="penting" judul="Urutan ini penting">
        Aturan dihitung berurutan dari data yang paling spesifik. Jabatan/template/window harus
        sudah siap sebelum karyawan ditambahkan, karena absensi pertama kali memakai aturan yang
        berlaku saat itu dan disimpan permanen sebagai catatan.
    </x-panduan.catatan>
</div>