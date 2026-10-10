<div>
    <h3 class="font-display text-[15px] text-slate-900">Toko</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Data cabang beserta titik koordinat dan radius yang dipakai untuk memeriksa lokasi absen.
        Buat toko dulu sebelum menambah karyawan.
    </p>

@php($barisKolomToko = [
    ['Nama toko', 'Nama yang tampil di daftar dan laporan. Maksimal 120 karakter. <span class="text-merah-700">Wajib.</span>'],
    ['Kode', 'Kode pendek dan unik. Dipakai untuk pencarian cepat dan untuk alamat perangkat presensi. <span class="text-merah-700">Wajib.</span>'],
    ['Alamat', 'Alamat lengkap cabang.'],
    ['Latitude / Longitude', 'Titik tengah area geofence. Ambil dari Google Maps: klik lokasi toko lalu salin angka setelah tanda <span class="italic">@</span>. Contoh <span class="italic">-6.1753924, 106.8271528</span>.'],
    ['Radius (meter)', 'Jarak yang masih dianggap berada di dalam toko. Minimal 20, maksimal 5000 meter.'],
    ['Buka / Tutup', 'Jam buka dan tutup toko. Jam tutup harus setelah jam buka.'],
    ['Zona waktu', 'Zona waktu toko. Default <span class="italic">Asia/Jakarta</span>.'],
    ['User presensi', 'User untuk masuk ke halaman perangkat presensi. Unik antar toko. <span class="text-merah-700">Wajib</span> supaya perangkat presensi bisa dipakai.'],
    ['Password presensi', 'Password untuk halaman perangkat presensi, minimal 8 karakter. Kosongkan berarti password lama tidak diubah.'],
    ['Toko aktif', 'Bila dimatikan, toko berhenti dipakai untuk absensi. Riwayat yang sudah tercatat tetap utuh.'],
])

<x-panduan.tabel :kolom="['Kolom', 'Keterangan']" :baris="$barisKolomToko"/>

<div class="mt-3">
    <x-panduan.catatan tipe="penting" judul="Tanpa koordinat, geofence tidak jalan">
        Toko yang latitude dan longitudenya kosong akan menolak semua percobaan absen dengan
        pesan <span class="italic">Toko Anda belum diisi koordinat, geofence tidak bisa
        diperiksa.</span> Toko yang dinonaktifkan tidak hilang datanya, dan riwayat absensi lamanya
        tetap utuh.
    </x-panduan.catatan>
</div>

<div class="mt-3">
    <x-panduan.catatan tipe="info" judul="Menyiapkan perangkat presensi toko">
        <p>
            Form toko menampilkan alamat perangkat presensi, misalnya
            <span class="italic">/presensi/TK-0001</span>. Alamat itu bisa langsung dibuka di
            perangkat presensi dan di-cache sebagai QR Code untuk ditempel di dekat perangkat.
        </p>
        <p class="mt-1">
            Perangkat presensi memakai satu user dan satu password yang dipakai bersama seluruh
            karyawan di toko itu, bukan akun per orang. Jadi siapa pun yang datang lebih dulu bisa
            langsung login dengan kredensial itu lalu memindai kartunya, tanpa perlu akun pribadi.
        </p>
        <p class="mt-1">
            Selain kredensial, tiap karyawan juga perlu diizinkan lewat kolom
            <span class="italic">Boleh absen lewat perangkat presensi</span> di form karyawan.
            Bawaannya diizinkan, jadi tanpa perubahan apa pun kartu langsung bisa dipakai.
        </p>
        <p class="mt-1">
            Selama user dan password belum diisi, halaman perangkat presensi menolak dibuka dan
            hanya menampilkan catatan untuk menghubungi admin. Perangkat tidak pernah terbuka
            tanpa kredensial.
        </p>
    </x-panduan.catatan>
</div>

<div class="mt-3">
    <x-panduan.catatan tipe="penting" judul="Perangkat presensi mengunci dirinya sendiri">
        Sesi perangkat presensi berakhir kalau dibiarkan tidak dipakai selama 30 menit, atau saat
        tombol <span class="italic">Kunci perangkat</span> ditekan. Ini menutup celah kalau
        perangkat ditinggal tanpa sengaja. Lamanya bisa diubah lewat variabel
        <span class="italic">PRESENSI_IDLE_TIMEOUT</span>.
    </x-panduan.catatan>
</div>
</div>