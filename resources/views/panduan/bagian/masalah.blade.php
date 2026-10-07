<p class="mb-4 text-[13px] leading-relaxed text-slate-600">
    Halaman ini disusun berdasarkan keluhan yang paling sering muncul. Cari kata kunci yang
    mirip dengan pesan yang muncul di layar.
</p>

<div class="space-y-4">
    <x-panduan.catatan tipe="larangan" judul="Tidak bisa login: Akun sedang dipakai di perangkat lain">
        <p>
            Satu akun hanya boleh aktif di satu perangkat. Tunggu sampai perangkat lain dipakai
            lagi, atau minta pemilik untuk mengaktifkan kembali akun dari menu
            <span class="font-medium">Pengguna</span>.
        </p>
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Tidak bisa login: Form terkunci">
        Form terkunci selama 60 detik setelah lima kali percobaan salah. Tunggu hitungannya habis,
        lalu periksa kembali email dan kata sandinya.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Tidak bisa login: Email atau kata sandi salah">
        Perhatikan huruf kapital dan spasi yang tidak sengaja. Email harus sama persis dengan yang
        dipakai saat akun dibuat.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Akun dinonaktifkan">
        Akun dinonaktifkan berarti tidak bisa masuk. Hubungi pemilik, karena pengaktifan kembali
        dilakukan dari menu <span class="font-medium">Pengguna</span>.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Halaman admin otomatis menutup dengan 403">
        Akun ini tertaut ke data karyawan, jadi memang tidak boleh masuk ke area admin. Promosi
        menjadi pengelola dilakukan dengan memutus tautan karyawannya.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Absen ditolak: Gagal menyimpan lokasi">
        Berdiri dekat jendela atau keluar sebentar, lalu coba lagi. Aplikasi menunggu sampai 8 detik
        mencari sinyal yang lebih baik.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Absen ditolak: Akurasi GPS kurang baik">
        Akurasi bacaan terlalu kasar. Tunggu beberapa detik sampai angka akurasi turun, atau pindah
        ke tempat yang lebih terbuka.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Absen ditolak: Terlalu jauh dari toko">
        Jarak dari titik koordinat toko melebihi radius toko. Kalau seharusnya sudah berada di dalam
        toko, minta pemilik memeriksa koordinat dan radius toko.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Absen ditolak: Di luar area geofence toko">
        Sama seperti terlalu jauh, tetapi pesan ini muncul ketika koordinat toko sudah diisi dan
        pemeriksaan jarak menolaknya.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Absen ditolak: Sudah absen masuk hari ini">
        Untuk shift tetap dan fleksibel, hanya boleh satu absen masuk per hari. Absen pulang dulu,
        atau hubungi atasan kalau ini keliru.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Absen ditolak: Di luar jam cut-off">
        Jam cut-off adalah batas terakhir memindai. Absen setelah jam itu tidak bisa direkam. Batas
        ini bisa diubah di template shift.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Absen ditolak: Belum absen masuk">
        Tombol absen pulang hanya aktif setelah absen masuk tercatat.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Absen ditolak: Sesi interval sudah dipakai">
        Setiap sesi hanya boleh satu scan masuk dan satu scan pulang. Kalau sudah scan pulang di sesi
        itu, sesi berikutnya baru bisa dipakai.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="larangan" judul="Tombol absen tidak muncul">
        Akun tidak punya izin mencatat absensi, sehingga tombol disembunyikan dan halaman hanya
        menampilkan riwayat.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="penting" judul="Tepat waktu walau datang agak telat">
        Shift fleksibel jenis bebas dan durasi tetap menilai durasi kerja, bukan kepatuhan datang. Jadi
        orang yang datang agak telat tapi durasinya cukup tetap ditulis Tepat Waktu.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="penting" judul="Status kosong atau tidak ada shift di hari ini">
        Hari itu tidak termasuk hari kerja di template, jadi tidak ada aturan yang bisa dipakai.
        Absen tetap tercatat, hanya tidak diberi label.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="penting" judul="Terlambat padahal tidak merasa telat">
        Periksa catatan pada baris absensi. Kalau muncul catatan jam masuk di luar semua window
        shift, berarti aturan yang dipakai adalah window, bukan template.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="penting" judul="Jumlah jam di dasbor tidak sama dengan laporan">
        Dasbor menampilkan bulan berjalan, laporan bisa menampilkan rentang lain. Bandingkan
        rentang tanggal yang sama sebelum menyimpulkan ada selisih.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kartu absen tidak berlaku lagi">
        Kartu sudah diganti dari halaman karyawan, jadi versi kodenya yang lama tidak berlaku lagi,
        baik di HP maupun di perangkat presensi. Minta versi kode yang terbaru dan cetak ulang.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Perangkat presensi ditolak sebelum bisa dipakai">
        Halaman perangkat presensi menolak dibuka kalau user dan password presensi di toko itu
        belum diisi. Isi keduanya di menu <span class="font-medium">Toko</span>, lalu muat ulang
        halamannya.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="User atau password presensi salah">
        Kredensial presensi milik toko itu sendiri, bukan akun karyawan. Kalau sudah dipastikan
        benar tapi masih ditolak, cek lagi spasi di awal atau akhir kolom, dan pastikan user
        presensi tidak dipakai toko lain.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kartu ditolak di perangkat presensi toko">
        Kode yang dipindai bukan milik toko tempat perangkat itu berada, atau karyawannya memang
        tidak diizinkan absen lewat perangkat presensi. Kartu harus milik toko tempat karyawan
        bekerja. Kalau benar, periksa juga apakah kode pada kartu sudah versi terbaru.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Supervisor tidak bisa membuka toko tertentu">
        Toko itu belum masuk daftar toko yang diawasi. Buka menu <span class="font-medium">Pengguna</span>,
        lalu centang tokonya di bagian Toko yang diawasi.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Menu tidak muncul di sidebar">
        Setiap menu mengikuti izin akun. Menu yang tidak muncul berarti akun tidak punya izin
        halaman itu, dan itu memang perilaku yang dimaksud.
    </x-panduan.catatan>
</div>