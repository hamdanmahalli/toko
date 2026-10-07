<div>
    <h3 class="font-display text-[15px] text-slate-900">Persetujuan Pengajuan</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Halaman pengajuan menampilkan dua tabel terpisah: pengajuan izin, sakit, cuti, dinas di
        atas, dan pengajuan lembur di bawahnya. Keduanya punya pagination sendiri, jadi menyaring
        izin tidak membuat daftar lembur ikut berubah.
    </p>

    <x-panduan.tabel
        class="mt-2"
        :kolom="['Filter', 'Fungsi']"
        :baris="[
            ['Cari nama atau NIP', 'Mencari berdasarkan nama atau NIP karyawan.'],
            ['Status', 'Menyaring berdasarkan status pengajuan.'],
            ['Toko', 'Menyaring berdasarkan toko karyawan. Supervisor hanya melihat toko yang diawasi.'],
            ['Menunggu', 'Checkbox untuk hanya menampilkan pengajuan yang belum diputuskan.'],
        ]"/>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Menyetujui dan menolak</h4>
    <ul class="mt-2 space-y-2 text-[13px] leading-relaxed text-slate-600">
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>
                <span class="font-medium text-slate-800">Menyetujui</span> tidak wajib disertai
                keterangan, tapi baik bila ada catatan.
            </span>
        </li>
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>
                <span class="font-medium text-slate-800">Menolak wajib menyertakan alasan.</span>
                Aplikasi akan meminta alasan lebih dulu. Tanpa alasan, pengajuan tidak bisa ditolak.
            </span>
        </li>
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>
                <span class="font-medium text-slate-800">Status tidak bisa dibatalkan.</span>
                Setelah disetujui atau ditolak, keputusan tidak bisa diubah dari halaman ini.
                Karyawan yang belum punya kepastian bisa membatalkan pengajuannya sendiri.
            </span>
        </li>
    </ul>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Keterangan pada tabel lembur</h4>
    <p class="mt-1 text-[13px] leading-relaxed text-slate-600">
        Jam kerja lembur beserta estimasi nominalnya ditampilkan berdampingan dengan keterangan.
        Bila tarif per jam belum diatur pada data karyawan, kolom estimasi menuliskan
        <span class="italic">Tarif belum diatur</span>.
    </p>

    <div class="mt-3">
        <x-panduan.catatan tipe="info">
            Pengajuan yang sudah disetujui otomatis muncul sebagai catatan pada hari absensi
            karyawan, supaya tidak perlu ditebak apakah hari itu sudah ada izin atau belum.
        </x-panduan.catatan>
    </div>
</div>