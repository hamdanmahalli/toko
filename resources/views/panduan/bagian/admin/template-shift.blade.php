<div>
    <h3 class="font-display text-[15px] text-slate-900">Template Shift</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Jadwal mingguan yang berlaku untuk semua orang dengan jabatan wajib template, seperti
        manajer dan kepala toko. Setiap hari punya jam masuk, batas telat, jam pulang, dan jam
        istirahat sendiri.
    </p>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Kolom utama</h4>
@php($barisKolomTemplate = [
    ['Nama template', 'Nama template, wajib diisi.'],
    ['Berlaku untuk', '<span class="font-medium text-slate-800">Semua Toko</span>, <span class="font-medium text-slate-800">Per Toko</span>, atau <span class="font-medium text-slate-800">Per Karyawan</span>. Hanya pemilik yang boleh membuat template untuk semua toko.'],
    ['Kode', 'Kode pendek untuk laporan, otomatis jadi huruf besar.'],
    ['Tipe shift', 'Penentuan cara jam kerja dihitung. Lihat penjelasan di bawah.'],
    ['Durasi maksimum (menit)', 'Catatan batas durasi kerja, berlaku untuk semua hari dan bisa ditimpa per hari. Angka ini tidak memotong durasi yang dihitung.'],
    ['Jam cut-off absen masuk', 'Setelah jam ini, scan masuk ditolak. Kosongkan bila tidak ada batas jam scan.'],
    ['Toko', 'Wajib diisi kalau <span class="font-medium text-slate-800">Berlaku untuk</span> di atas diisi <span class="font-medium text-slate-800">Per Toko</span>.'],
    ['Template aktif', 'Template nonaktif tidak dipakai untuk menentukan jam kerja.'],
])

<x-panduan.tabel :kolom="['Kolom', 'Keterangan']" :baris="$barisKolomTemplate"/>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Tiga tipe shift</h4>
    @php($barisTipeShift = [
    ['<span class="font-medium text-slate-800">Tetap</span>',
     'Jam masuk dan jam pulang sama untuk semua orang.',
     'Jam pulang harus setelah batas telat. Tidak boleh melewati tengah malam.'],
    ['<span class="font-medium text-slate-800">Fleksibel</span>',
     'Boleh datang di rentang jam tertentu atau bebas.',
     'Ada tiga jenis: <span class="font-medium">Bebas</span> (absen kapan saja),
      <span class="font-medium">Terbatas</span> (harus antara jam masuk dan batas telat), dan
      <span class="font-medium">Durasi tetap</span> (jam pulang dihitung dari jam datang
      ditambah durasi kerja).'],
    ['<span class="font-medium text-slate-800">Interval</span>',
     'Satu hari dibagi beberapa sesi scan masuk dan pulang.',
     'Karyawan bisa scan masuk dan pulang sekali per sesi, berurutan dari atas. Boleh melewati
      tengah malam bila jam selesai lebih awal dari jam mulai.'],
])

<x-panduan.tabel :kolom="['Tipe', 'Arti', 'Catatan']" :baris="$barisTipeShift"/>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Tabel per hari</h4>
    <p class="mt-1 text-[13px] leading-relaxed text-slate-600">
        Centang hari yang merupakan hari kerja, lalu isi jamnya. Hari yang tidak dicentang berarti
        libur. Untuk tipe tetap, batas telat wajib diisi dan urutannya harus masuk, lalu batas
        telat, lalu pulang.
    </p>

    <div class="mt-3 space-y-2">
        <x-panduan.catatan tipe="info" judul="Durasi maksimum bukan pemotong">
            Nilai durasi maksimum hanya disimpan sebagai catatan. Durasi kerja tetap dihitung
            penuh dari jam datang sampai jam pulang, supaya tidak bercampur dengan perhitungan
            lembur.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="penting" judul="Menghapus template">
            Menghapus template ikut menghapus jadwal hariannya dan penugasan karyawan yang memakainya.
            Riwayat absensi lama tetap aman, tapi karyawan tersebut tidak lagi punya jam kerja
            sampai ditugaskan template lain.
        </x-panduan.catatan>
    </div>
</div>