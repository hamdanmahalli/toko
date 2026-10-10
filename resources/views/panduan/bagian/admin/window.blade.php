<div>
    <h3 class="font-display text-[15px] text-slate-900">Window Shift</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Rentang jam untuk kasir dan pramuniaga. Berbeda dengan template, window tidak perlu
        ditugaskan ke siapa pun: jam datang karyawan sendiri yang menentukan dia masuk window mana.
    </p>

    @php($barisKolomWindow = [
    ['Nama', 'Nama window, misalnya <span class="italic">Pagi</span>. <span class="text-merah-700">Wajib.</span>'],
    ['Kode', 'Kode pendek untuk laporan, misalnya <span class="italic">PAGI</span>.'],
    ['Mulai / Selesai', 'Rentang jam window. Jam selesai harus setelah jam mulai. <span class="text-merah-700">Wajib.</span>'],
    ['Batas telat', 'Lewat dari jam ini berarti terlambat. Harus di antara jam mulai dan selesai. Kosongkan berarti harus datang tepat di jam mulai.'],
    ['Aturan absensi', '<span class="font-medium text-slate-800">Ketat</span> berarti absen di luar rentang shift langsung dihitung terlambat. <span class="font-medium text-slate-800">Toleran</span> berarti tetap dicatat, tapi tidak dihitung terlambat.'],
    ['Durasi maksimum (menit)', 'Catatan batas durasi kerja. Ini catatan saja, durasi tetap dihitung penuh.'],
    ['Toko', 'Kosongkan untuk semua toko. Hanya pemilik yang boleh membuat window untuk semua toko.'],
    ['Urutan', 'Angka 0 sampai 999 untuk urutan tampilan dan pemutusan saat window tumpang tindih.'],
    ['Aktif dipakai untuk menentukan shift', 'Window nonaktif dipakai untuk rekap, tapi tidak dipakai untuk menentukan shift.'],
])

<x-panduan.tabel :kolom="['Kolom', 'Keterangan']" :baris="$barisKolomWindow"/>

<h4 class="mt-4 font-display text-[14px] text-slate-900">Contoh</h4>

@php($barisContohWindow = [
    ['Pagi', '06:00', '14:00', '06:15', 'Toleran'],
    ['Siang', '13:00', '21:00', '13:30', 'Toleran'],
    ['Malam', '21:00', '23:59', '21:30', 'Ketat'],
])

<x-panduan.tabel :kolom="['Nama', 'Mulai', 'Selesai', 'Batas telat', 'Aturan']" :baris="$barisContohWindow"/>

    <div class="mt-3 space-y-2">
        <x-panduan.catatan tipe="info" judul="Window tumpang tindih">
            Jika ada window yang saling tumpang tindih, yang dipakai adalah window yang jam
            mulainya paling akhir. Jadi pada contoh di atas, karyawan yang datang pukul 13:30
            masuk ke window Siang, bukan Pagi. Aplikasi menampilkan peringatan bila
            tumpang tindih terdeteksi, tetapi tetap membolehkannya.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="penting" judul="Aturan absensi berlaku kalau ada yang ketat">
            Absen di luar semua window akan dihitung terlambat kalau ada satu saja window aktif
            yang berniat <span class="font-medium">Ketat</span>. Kalau semua window toleran,
            absen tetap dicatat tanpa penilaian. Kalau belum ada satu pun window di toko itu,
            tidak ada aturan yang ditegakkan.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="info" judul="Menghapus window">
            Tombol hapus pada window hanya menonaktifkannya, tidak menghapus. Arsip absensi lama
            tetap utuh. Window bisa diaktifkan kembali kapan saja.
        </x-panduan.catatan>
    </div>
</div>