<div>
    <h3 class="font-display text-[15px] text-slate-900">Laporan Durasi Kerja</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Rekap durasi kerja per karyawan. Tanpa filter, laporan menampilkan bulan berjalan sampai
        hari ini.
    </p>

    <x-panduan.tabel
        class="mt-2"
        :kolom="['Filter', 'Keterangan']"
        :baris="[
            ['Cari nama atau NIP', 'Mencari karyawan tertentu.'],
            ['Dari / Sampai', 'Rentang tanggal absensi.'],
            ['Toko', 'Menyaring per toko. Supervisor hanya melihat toko yang diawasi.'],
        ]"/>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Kolom laporan</h4>
    @php($barisKolomLaporan = [
    ['Karyawan', 'Nama dan NIP, beserta jabatan bila ada.'],
    ['Toko', 'Toko tempat bekerja.'],
    ['Hari', 'Jumlah hari berbeda yang punya catatan absensi.'],
    ['Sesi', 'Jumlah pasangan absen masuk dan pulang.'],
    ['Total durasi', 'Akumulasi durasi kerja dalam bentuk jam dan menit, misalnya <span class="italic">9j 30m</span>.'],
    ['Rata-rata / hari', 'Total durasi dibagi jumlah hari.'],
    ['Terlambat', 'Jumlah sesi yang tercatat terlambat.'],
    ['Belum pulang', 'Jumlah sesi yang sudah absen masuk tapi belum absen pulang.'],
])

<x-panduan.tabel :kolom="['Kolom', 'Arti']" :baris="$barisKolomLaporan"/>

    <div class="mt-3 space-y-2">
        <x-panduan.catatan tipe="info" judul="Angka lama tidak ikut berubah">
            Laporan dihitung ulang dari baris absensi yang benar-benar tercatat, bukan dari aturan
            shift yang berlaku sekarang. Mengubah jadwal shift tidak mengubah angka bulan lalu.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="penting" judul="Batas rentang tanggal">
            Rentang dibatasi dua tahun ke belakang sampai satu bulan ke depan. Tanggal yang tidak
            wajar akan ditolak, bukan dipotong diam-diam, supaya angka yang tampil selalu sama
            dengan yang diminta.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="info" judul="Cara durasi dihitung">
            Durasi dihitung dari tanggal penuh, bukan hanya jam, sehingga shift yang melewati
            tengah malam tetap terhitung benar. Jam istirahat yang sudah dijadwalkan dipotong dari
            durasi kerja. Sesi yang belum absen pulang dihitung nol menit tapi tetap menambah
            kolom Belum pulang.
        </x-panduan.catatan>
    </div>
</div>