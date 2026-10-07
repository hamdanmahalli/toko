<p class="mb-4 text-[13px] leading-relaxed text-slate-600">
    Pertanyaan yang paling sering ditanyakan, dijawab satu per satu. Bagian ini bisa dibaca
    tanpa perlu membuka halaman lain.
</p>

<div class="space-y-4">
    <x-panduan.catatan tipe="info" judul="Kenapa saya harus izin lokasi?">
        Aplikasi memeriksa lokasi di server untuk memastikan absen dicatat dari dalam area toko.
        Izin lokasi hanya dibaca di perangkat sendiri dan koordinatnya tidak dibagikan ke pihak lain.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kenapa lokasi tidak bisa dibaca sama sekali?">
        Peramban hanya mengizinkan pembacaan lokasi lewat HTTPS. Aplikasi yang dibuka lewat alamat
        jaringan lokal akan diblokir. Gunakan localhost untuk pengujian, atau pasang sertifikat
        HTTPS untuk alamat yang dipakai orang lain.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Apakah GPS dibutuhkan terus-menerus?">
        Tidak. Lokasi hanya dibaca saat tombol absen ditekan, lalu berhenti. Aplikasi tidak melacak
        pergerakan sepanjang hari.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kenapa cartanya tidak muncul di dasbor saya?">
        Kartu absen tersedia untuk akun yang tertaut ke data karyawan. Kalau tidak muncul, data
        karyawannya belum tertaut atau sudah dinonaktifkan.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kenapa kartu memuat QR dan barcode?">
        Satu kode identitas dicetak dalam dua bentuk supaya dibaca perangkat apa pun di toko:
        barcode dibaca scanner USB perangkat presensi, sedangkan QR memuat kode yang sama untuk
        dibaca reader kamera atau diketik manual. Absen dari HP tidak memakai kartu ini—cukup
        tombol di beranda yang memeriksa lokasi. Memindai dari perangkat presensi tetap dicatat
        seperti absen biasa, hanya lokasi yang diambil dari koordinat toko.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kenapa jam kerja saya 0 menit?">
        Yang perlu dicek adalah kolom durasi pada baris absensi. Ada dua kemungkinan: sesi masuk
        belum ditutup dengan absen pulang, atau jam istirahat yang dijadwalkan membuat durasi
        bersihnya habis.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Bisa absen dari luar toko?">
        Tidak dalam kondisi normal, karena pemeriksaan jarak akan menolak, kecuali radius toko
        memang sengaja dibuat sangat besar.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kenapa jam paling lambat saya berbeda setelah jadwal diubah?">
        Status keterlambatannya ikut dengan aturan yang berlaku saat itu. Absen yang sudah
        tersimpan tidak dihitung ulang, jadi laporan bulan lalu tetap sama seperti aslinya.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kenapa pengajuan saya belum diproses?">
        Pengajuan menunggu keputusan atasan. Statusnya berubah otomatis setelah atasan menyetujui
        atau menolak, dan penolakan selalu disertai alasan.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Apakah bisa membatalkan pengajuan yang sudah disetujui?">
        Pengajuan bisa dibatalkan sendiri selama belum diproses. Setelah statusnya berubah menjadi
        disetujui atau ditolak, keputusan tidak bisa diubah lagi.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Bagaimana cara mengaktifkan kembali akun yang dinonaktifkan?">
        Buka menu <span class="font-medium">Pengguna</span>, lalu aktifkan kembali centang Akun aktif.
        Orang tersebut harus keluar dari perangkat yang sedang dipakai sebelum bisa masuk lagi.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Bagaimana cara promosi karyawan jadi pengawas?">
        Berikan peran yang diperlukan dari menu <span class="font-medium">Pengguna</span>, lalu
        tentukan toko yang diawasi. Ada satu syarat tambahan: akun harus diputus dari data
        karyawan supaya tidak otomatis tertolak di area admin.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Apakah semua toko bisa punya jadwal berbeda?">
        Bisa. Template shift dan window shift bisa dibuat khusus satu toko, atau berlaku untuk
        semua toko sekaligus. Aturan paling spesifik selalu menang.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Kenapa kasir saya ikut template shift?">
        Kalau kasir punya template yang ditugaskan langsung, aturan itu yang dipakai, walau
        jabatan secara normal tidak wajib template. Kosongkan penugasan template di halaman
        karyawan untuk mengembalikan ke aturan window.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Berapa lama satu akun boleh dipakai?">
        Satu akun hanya boleh aktif di satu perangkat pada satu waktu. Aplikasi tidak melacak
        siapa yang sedang memakai akun tertentu, tapi aturan ini membuat absensi pada hari itu
        hanya bisa tercatat satu kali, bukan dari dua perangkat berbeda.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Apakah data absensi bisa dihapus?">
        Data yang sudah tercatat tidak bisa dihapus dari aplikasi. Menonaktifkan karyawan atau
        toko hanya melingkupkan datanya dari daftar aktif, sementara riwayat tetap utuh.
    </x-panduan.catatan>

    <x-panduan.catatan tipe="info" judul="Siapa yang harus dihubungi?">
        Hubungi pemilik atau supervisor toko lebih dulu. Mereka punya akses ke halaman admin untuk
        memeriksa pengaturan yang menyebabkan masalah.
    </x-panduan.catatan>
</div>
