<p class="mb-3 text-[13px] leading-relaxed text-slate-600">
    Ada tiga peran bawaan. Hak akses tiap peran sudah ditentukan, jadi saat memilih peran di
    menu Pengguna, halamannya menyesuaikan sendiri.
</p>

@php($barisPeran = [
    ['<span class="font-medium text-slate-800">Pemilik</span>',
     'Semua menu, seluruh jaringan toko, termasuk template dan window untuk semua toko.',
     '—'],
    ['<span class="font-medium text-slate-800">Supervisor</span>',
     'Semua menu admin, tetapi hanya untuk toko yang ditugaskan kepadanya.',
     'Tidak bisa menghapus karyawan, memfinalisasi payroll, mengubah peran, mengelola pengguna, dan mengubah pengaturan.'],
    ['<span class="font-medium text-slate-800">Karyawan</span>',
     'Hanya beranda, kartu absen, riwayat sendiri, dan pengajuan.',
     'Tidak bisa membuka menu admin sama sekali.'],
])

<x-panduan.tabel :kolom="['Peran', 'Bisa melihat', 'Tidak bisa']" :baris="$barisPeran"/>

<div class="mt-3">
    <x-panduan.catatan tipe="info" judul="Presensi Karyawan bukan peran">
        Halaman perangkat presensi tidak memakai peran apa pun karena tidak ada akun karyawan di
        dalamnya. Yang menjaga perangkat itu adalah satu user dan password bersama per toko, lalu
        kartu: hanya kartu milik toko tersebut yang bisa dicatat di sana.
    </x-panduan.catatan>
</div>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Cakupan toko</h3>

<p class="mb-3 text-[13px] leading-relaxed text-slate-600">
    Hanya peran <span class="font-medium text-slate-800">pemilik</span> yang bisa melihat dan mengelola
    seluruh jaringan. Supervisor dan akun lain selalu terbatas pada daftar toko yang ditugaskan
    di menu Pengguna.
</p>

<x-panduan.catatan tipe="penting" judul="Supervisor tanpa penugasan melihat nol toko">
    Kalau supervisor belum ditugaskan ke toko mana pun, halaman-halamannya tetap terbuka tapi
    isinya kosong. Ini perilaku yang disengaja supaya tidak ada kebocoran data antar cabang.
    Perbaiki dari menu <span class="font-medium">Pengguna</span>.
</x-panduan.catatan>

<h3 class="mt-5 font-display text-[15px] text-slate-900">Aturan lain yang berlaku</h3>

<ul class="mt-2 space-y-2 text-[13px] leading-relaxed text-slate-600">
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Satu akun satu perangkat.</span>
            Akun yang sama tidak bisa dipakai dari dua perangkat sekaligus. Sesi yang lebih lama
            akan dikeluarkan otomatis.
        </span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Menonaktifkan akun langsung berlaku.</span>
            Akun yang dinonaktifkan dikeluarkan dari sesi yang sedang berjalan dan tidak bisa
            masuk lagi. Riwayat absensinya tetap tersimpan.
        </span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Perubahan hak akses memaksa keluar.</span>
            Setiap kali peran atau toko yang diawasi berubah, sesi aktif ikut dibatalkan.
        </span>
    </li>
    <li class="flex gap-2">
        <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
        <span>
            <span class="font-medium text-slate-800">Menonaktifkan diri sendiri dicegah.</span>
            Supaya admin tidak terkunci dari halaman pengaturannya.
        </span>
    </li>
</ul>