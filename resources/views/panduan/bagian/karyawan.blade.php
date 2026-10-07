<p class="mb-3 text-[13px] leading-relaxed text-slate-600">
    Halaman ini berlaku untuk akun ber peran karyawan. Semua menu di bawah ini hanya bisa dibuka
    kalau akun punya izin yang sesuai, jadi sebagian menu bisa tidak terlihat.
</p>

<h3 class="font-display text-[15px] text-slate-900">Masuk dan keluar</h3>

<ul class="mt-2 space-y-2 text-[13px] leading-relaxed text-slate-600">
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Lima kali gagal.</span> Setelah lima percobaan
            salah, form masuk dikunci selama 60 detik.
        </span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Pastikan akun belum dipakai di HP lain.</span>
            Kalau muncul <span class="italic">Akun Anda sedang dipakai di perangkat lain</span>,
            akun sedang aktif di perangkat berbeda.
        </span>
    </li>
</ul>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Beranda</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Menampilkan sapaan sesuai jam, status absensi hari ini, jam masuk, jam pulang, batas telat
    beserta sisa waktunya, serta ringkasan bulan ini: jumlah hari hadir, total jam kerja, dan
    jumlah kali terlambat.
</p>

<div class="mt-2">
    <x-panduan.catatan tipe="info" judul="Tombol hanya muncul kalau punya izin">
        Tombol absen muncul di bagian bawah layar hanya untuk akun yang punya izin mencatat.
        Kalau tombolnya tidak ada, akun tersebut memang hanya boleh melihat, dan tidak bisa
        mencatat kehadiran sendiri.
    </x-panduan.catatan>
</div>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Absen datang</h3>

@php($langkahAbsenDatang = [
    ['Tekan tombol <span class="font-medium text-slate-700">Absen Masuk</span>',
     'Tombol ini ada di panel melayang paling bawah layar beranda.'],
    ['Izinkan browser mengambil lokasi',
     'Muncul permintaan izin lokasi di atas atau bawah peramban. Pilih izinkan. Tanpa izin ini absen tidak akan tercatat.'],
    ['Tunggu pembacaan GPS',
     'Aplikasi mengambil beberapa sampel lalu memilih yang paling akurat, jadi biasanya perlu beberapa detik.'],
    ['Tunggu muncul hasil',
     'Berhasil: <span class="italic">Absen masuk tercatat pukul 08:02. Tepat waktu.</span> Gagal akan ditulis merah di atas tombol.'],
])

<x-panduan.langkah :langkah="$langkahAbsenDatang"/>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Absen pulang</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Tombol <span class="font-medium text-slate-700">Absen Pulang</span> baru bisa dipakai setelah
    absen masuk tercatat. Setelah berhasil, muncul keterangan seperti
    <span class="italic">Absen pulang tercatat pukul 17:02. Durasi kerja 8,7 jam.</span>
</p>

<div class="mt-2">
    <x-panduan.catatan tipe="penting" judul="Kalau GPS tidak mau terbaca">
        <p>
            Sinyal GPS di dalam ruangan sering jelek. Berdiri dekat jendela atau keluar sebentar
            biasanya sudah cukup. Aplikasi menunggu sampai 8 detik mencari sinyal yang lebih baik.
        </p>
        <p class="mt-1">
            Aplikasi hanya bisa membaca lokasi lewat HTTPS. Kalau memakai alamat jaringan lokal
            (misalnya <span class="italic">192.168.x.x</span>), browser akan memblokir permintaan
            lokasi. Gunakan <span class="italic">localhost</span> atau pasang sertifikat HTTPS.
        </p>
    </x-panduan.catatan>
</div>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Kartu absen</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Halaman <span class="font-medium text-slate-700">Kartu Saya</span> menampilkan kartu identitas
    untuk absen dari perangkat presensi toko: barcode dibaca scanner USB, sedangkan QR dan kode
    teks di bawahnya memuat kode yang sama untuk dibaca reader kamera atau diketik manual. Absen
    dari HP tidak memakai kartu ini—cukup tombol di beranda.
</p>

<ul class="mt-2 space-y-2 text-[13px] leading-relaxed text-slate-600">
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Cetak kartu ini sekali</span> lalu bawa setiap
            hari. Tombol <span class="font-medium text-slate-700">Cetak kartu</span> mencetak kartu
            saja, tanpa menu dan tombol lain.
        </span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Kode manual ada di bawah barcode</span> untuk
            jaga-jaga kalau barcode tergores atau tidak terbaca scanner.
        </span>
    </li>
</ul>

<div class="mt-2">
    <x-panduan.catatan tipe="info">
        Kartu berlaku sampai diganti atasan lewat menu
        <span class="font-medium">Karyawan → Ganti kartu</span>. Setelah diganti, semua kartu lama
        langsung tidak berlaku di seluruh toko, jadi kartu yang dicetak ulang wajib dipakai.
    </x-panduan.catatan>
</div>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Absen tanpa HP lewat perangkat presensi</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Kalau tidak membawa HP, absen bisa dicatat dari perangkat presensi toko. Beranda Anda
    menampilkan alamatnya dalam bentuk teks; buka alamat itu di perangkat presensi toko, bukan
    di HP. Perangkat itu dipakai bersama jadi tidak memakai akun pribadi Anda: yang dipakai
    adalah user dan password presensi yang sama untuk semua orang di toko itu.
</p>

@php($langkahAbsenPresensi = [
    ['Buka halaman presensi di perangkat toko',
     'Alamatnya tertera di beranda Anda. Jangan dibuka dari HP, cukup di perangkat presensi toko.'],
    ['Masukkan user dan password presensi',
     'Diberikan pemilik toko. Sama untuk semua karyawan di toko tersebut, jadi tidak perlu akun pribadi. Setelah berhasil, halaman pemindai langsung terbuka.'],
    ['Pindai barcode kartu',
     'Tahan kartu di depan scanner sampai kode muncul di kotak pencarian, lalu tekan Enter. Scanner sudah dikonfigurasi otomatis untuk menekan Enter.'],
    ['Tunggu hasilnya',
     'Berhasil: <span class="italic">Absen masuk Budi tercatat pukul 08:02. Tepat waktu.</span> Gagal ditulis merah dengan alasannya, dan kartu berikutnya langsung bisa dipindai.'],
])

<x-panduan.langkah :langkah="$langkahAbsenPresensi"/>

<div class="mt-2">
    <x-panduan.catatan tipe="penting" judul="Tidak perlu pilih Masuk atau Pulang">
        Perangkat presensi sudah tahu yang mana yang perlu dicatat: yang dipindai dicatat sebagai
        absen masuk kalau Anda belum absen masuk hari itu, dan sebagai absen pulang kalau sudah
        ada. Jadi halaman perangkat presensi hanya punya satu tombol.
    </x-panduan.catatan>
</div>

<div class="mt-2">
    <x-panduan.catatan tipe="info" judul="Kalau perangkat sudah terbuka, tidak perlu login lagi">
        Sesi perangkat presensi bertahan selama dipakai dan menutup diri sendiri kalau dibiarkan
        tidak dipakai. Kalau mau menutupnya duluan, tekan <span class="italic">Kunci perangkat</span>
        di bawah halaman pemindai.
    </x-panduan.catatan>
</div>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Kartu dari toko lain akan ditolak di perangkat ini. Absensi yang tercatat dari perangkat
    presensi ditandai <span class="font-medium text-slate-700">Presensi Karyawan</span> di
    riwayat, supaya atasan tahu itu absen tanpa HP. Karyawan yang tidak diizinkan admin tidak
    bisa memakai perangkat ini sama sekali, dan harus absen dari HP atau menghubungi admin.
</p>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Riwayat absen</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Tab <span class="font-medium text-slate-700">Riwayat</span> menampilkan daftar kehadiran
    per tanggal. Setiap baris memuat jam masuk, jam pulang, durasi kerja, jarak dari titik toko,
    label shift, dan status keterlambatannya.
</p>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Mengajukan izin</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Tab <span class="font-medium text-slate-700">Pengajuan</span>. Pilih jenis pengajuan, isi
    tanggal dan keterangan, lalu kirim. Statusnya berubah dari <span class="font-medium">Menunggu</span>
    menjadi <span class="font-medium">Disetujui</span> atau <span class="font-medium">Ditolak</span>
    setelah atasan memutuskan.
</p>

<x-panduan.tabel
    class="mt-2"
    :kolom="['Jenis', 'Keterangan']"
    :baris="[
        ['Izin', 'Keperluan pribadi, tidak dibayar.'],
        ['Sakit', 'Beristirahat karena sakit, tidak dibayar.'],
        ['Cuti', 'Cuti resmi, termasuk yang dibayar.'],
        ['Dinas Luar', 'Perjalanan dinas, termasuk yang dibayar.'],
    ]"/>

<p class="mt-3 text-[13px] leading-relaxed text-slate-600">
    Pengajuan yang sudah disetujui bisa dibatalkan sendiri selama belum diproses atasan.
    Penolakan selalu disertai alasan supaya karyawan tahu apa yang perlu diperbaiki.
</p>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Mengajukan lembur</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Isi tanggal, jam mulai, jam selesai, dan keterangan pekerjaan. Keterangan wajib diisi karena
    alasan lembur adalah bagian dari keputusan atasan.
</p>