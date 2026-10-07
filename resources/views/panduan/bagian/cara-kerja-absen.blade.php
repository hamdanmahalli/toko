<p class="mb-3 text-[13px] leading-relaxed text-slate-600">
    Bagian ini menjelaskan kenapa seorang karyawan bisa tercatat terlambat atau tidak, dan kenapa
    kadang statusnya justru kosong. Aturan ini dibaca berurutan dari yang paling spesifik.
</p>

<h3 class="font-display text-[15px] text-slate-900">Urutan penentuan aturan</h3>

<x-panduan.tabel
    :kolom="['Urutan', 'Aturan yang dipakai', 'Kapan dipakai']"
    :baris="[
        ['1', 'Penugasan template khusus karyawan',
         'Kalau ada template yang ditugaskan langsung ke karyawan tersebut, aturan ini yang dipakai, walau pun ia kasir.'],
        ['2', 'Window shift',
         'Kalau jabatan tidak wajib template. Jam datang karyawan menentukan dia masuk window mana.'],
        ['3', 'Template milik toko',
         'Kalau jabatan wajib template dan toko punya template sendiri.'],
        ['4', 'Template semua toko',
         'Kalau toko tidak punya template sendiri.'],
        ['5', 'Nilai bawaan dari pengaturan',
         'Hanya dipakai kalau sama sekali tidak ada template. Bawaannya masuk 08:00, batas telat 08:15, pulang 17:00.'],
    ]"/>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Status absen masuk</h3>

@php($barisStatusMasuk = [
    ['Absen masuk pada sesi interval yang berlaku', 'Tepat Waktu',
     'Sudah pasti tepat waktu karena jam datangnya sudah dibatasi sesi itu sendiri.'],
    ['Jabatan wajib template tapi hari itu tidak ada jadwal', 'Kosong',
     'Catatan <span class="italic">tidak ada shift di hari ini</span>. Tidak dihitung terlambat.'],
    ['Shift fleksibel jenis bebas atau durasi tetap', 'Tepat Waktu',
     'Yang dinilai adalah durasi kerja, bukan kepatuhan datang.'],
    ['Shift tetap atau fleksibel jenis terbatas', 'Tepat Waktu atau Terlambat',
     'Dibandingkan dengan batas telat milik shift itu sendiri, bukan jam masuk umum.'],
])

<x-panduan.tabel
    :kolom="['Kondisi', 'Status', 'Penjelasan']"
    :baris="$barisStatusMasuk"/>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Status absen pulang</h3>

@php($barisStatusPulang = [
    ['Pulang pada sesi interval yang berlaku', 'Tepat Waktu atau Pulang Cepat',
     'Dibandingkan dengan jam selesai sesi.'],
    ['Jabatan wajib template tapi hari itu tidak ada jadwal', 'Kosong',
     'Catatan <span class="italic">tidak ada shift di hari ini</span>.'],
    ['Shift fleksibel jenis bebas', 'Tepat Waktu',
     'Pulang cepat tidak bisa dihitung karena tidak ada jam pulang yang jadi acuan.'],
    ['Shift tetap atau fleksibel jenis terbatas', 'Tepat Waktu atau Pulang Cepat',
     'Dibandingkan dengan jam pulang harapan.'],
])

<x-panduan.tabel
    :kolom="['Kondisi', 'Status', 'Penjelasan']"
    :baris="$barisStatusPulang"/>

<div class="mt-3">
    <x-panduan.catatan tipe="info" judul="Status kosong bukan berarti gagal">
        Status kosong muncul ketika tidak ada aturan yang bisa dipakai untuk menilai. Absennya
        tetap tercatat, hanya tidak diberi label tepat waktu atau terlambat.
    </x-panduan.catatan>
</div>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Pemeriksaan lokasi</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Sebelum aturan jam kerja dijalankan, lokasi selalu diperiksa lebih dulu. Ada tiga pemeriksaan
    berurutan, dan kegagalan di salah satunya langsung membatalkan absen tanpa menyimpan apa pun.
</p>

@php($barisPemeriksaanLokasi = [
    ['Koordinat terbaca', 'Koordinat kosong, atau di luar rentang yang sah untuk garis bumi.'],
    ['Akurasi GPS cukup', 'Akurasi bacaan lebih kasar dari batas yang diatur, yaitu 500 meter secara bawaan. Di dalam toko angka 100 sampai 300 meter masih dianggap wajar.'],
    ['Masih di dalam radius toko', 'Jarak dari titik koordinat toko lebih besar dari radius toko. Tepat di garis batas masih diterima.'],
])

<x-panduan.tabel
    :kolom="['Pemeriksaan', 'Kapan gagal']"
    :baris="$barisPemeriksaanLokasi"/>

<div class="mt-3">
    <x-panduan.catatan tipe="penting" judul="Jarak dihitung ulang di server">
        Aplikasi menghitung ulang jarak sendiri dari koordinat yang dikirim. Angka jarak yang
        dikirim perangkat tidak pernah dipakai, jadi jarak yang tersimpan selalu benar.
    </x-panduan.catatan>
</div>

<div class="mt-3">
    <x-panduan.catatan tipe="info" judul="Absen dari perangkat presensi melewati tiga pemeriksaan ini juga">
        Perangkat presensi memakai koordinat tokonya sendiri, bukan GPS perangkat, jadi jarak
        selalu 0 meter dan akurasi GPS tidak dihitung. Aturan jam kerja, jam cut-off, dan jenis
        shift tetap berlaku seperti absen dari HP. Kartu yang bukan milik toko itu langsung
        ditolak sebelum jam kerja dievaluasi, begitu juga kartu karyawan yang tidak diizinkan
        memakai perangkat presensi.
    </x-panduan.catatan>
</div>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Sesi dan jam cut-off</h3>

<ul class="mt-2 space-y-2 text-[13px] leading-relaxed text-slate-600">
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Absen masuk hanya boleh sekali per hari.</span>
            Untuk template tipe tetap dan fleksibel, scan masuk kedua di hari yang sama akan
            ditolak dengan pesan sudah absen masuk. Hanya template tipe interval yang mengizinkan
            scan masuk berulang, sekali per sesi.
        </span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Jam cut-off hanya menahan scan masuk.</span>
            Ini batas jam memindai, bukan batas jam kerja. Orang yang terlambat sudah tidak boleh
            masuk, jadi absensinya baru dibuat kalau belum lewat cut-off. Karyawan yang ikut window
            shift tidak terkena aturan ini.
        </span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Absen pulang memakai shift saat masuk.</span>
            Kalau masuk jam 21:30 pada shift Malam lalu pulang jam 23:00, perhitungannya tetap
            memakai shift Malam, bukan shift yang sedang berlaku jam 23:00.
        </span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Shift tengah malam tetap bisa ditutup keesokannya.</span> Absen pulang dari shift yang benar-benar melewati tengah malam masih
            bisa ditutup pada hari berikutnya, supaya shift 22:00 sampai 02:00 tidak menggantung.
        </span>
    </li>
</ul>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Catatan otomatis</h3>

<p class="mt-2 text-[13px] leading-relaxed text-slate-600">
    Aplikasi menulis catatan pada baris absensi tanpa menolak absen. Catatan yang mungkin muncul:
</p>

<ul class="mt-2 space-y-1.5 text-[13px] leading-relaxed text-slate-600">
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span><span class="italic">tanggal ini libur</span></span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span><span class="italic">memiliki pengajuan ... disetujui</span></span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span><span class="italic">tidak ada shift di hari ini</span></span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span><span class="italic">jam masuk di luar semua window shift yang berlaku</span></span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span><span class="italic">akurasi GPS ... m, lebih kasar dari radius toko</span></span>
    </li>
</ul>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Cara durasi kerja dihitung</h3>

<ol class="mt-2 space-y-2 text-[13px] leading-relaxed text-slate-600">
    <li class="flex gap-2">
        <span class="mt-px flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-semibold text-brand-800">1</span>
        <span>Dihitung dari tanggal penuh, bukan hanya jam. Masuk 20:00 lalu pulang 03:00 dihitung 7 jam, bukan minus 17 jam.</span>
    </li>
    <li class="flex gap-2">
        <span class="mt-px flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-semibold text-brand-800">2</span>
        <span>Jam istirahat yang sudah dijadwalkan dipotong dari durasi.</span>
    </li>
    <li class="flex gap-2">
        <span class="mt-px flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-semibold text-brand-800">3</span>
        <span>Nilai durasi maksimum tidak memotong apa pun. Durasi selalu dihitung penuh supaya tidak bercampur dengan perhitungan lembur.</span>
    </li>
    <li class="flex gap-2">
        <span class="mt-px flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-semibold text-brand-800">4</span>
        <span>Sesi yang belum absen pulang punya durasi kosong, bukan nol jam. Di laporan dihitung nol menit tapi ditandai di kolom Belum pulang.</span>
    </li>
</ol>