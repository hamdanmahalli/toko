<div>
    <h3 class="font-display text-[15px] text-slate-900">Jabatan</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Jabatan menjawab pertanyaan paling penting: apakah jam kerja seorang karyawan ditentukan
        oleh jadwal mingguan, atau oleh jam datangnya sendiri.
    </p>

    @php($barisKolomJabatan = [
    ['Nama jabatan', 'Nama jabatan, harus unik. <span class="text-amber-700">Wajib.</span>'],
    ['Kode', 'Kode pendek untuk laporan. Boleh dikosongkan; kalau begitu dibuat otomatis dari nama jabatan.'],
    ['Deskripsi', 'Penjelasan bebas, maksimal 500 karakter.'],
    ['Wajib memakai template shift', 'Centang untuk manajer atau kepala toko. Bila tidak dicentang, jam datang karyawan sendiri yang menentukan dia masuk shift mana.'],
    ['Jabatan aktif', 'Menentukan apakah jabatan ini bisa dipilih saat menambah karyawan.'],
])

<x-panduan.tabel :kolom="['Kolom', 'Keterangan']" :baris="$barisKolomJabatan"/>

<h4 class="mt-4 font-display text-[14px] text-slate-900">Contoh pembagian yang wajar</h4>

@php($barisContohJabatan = [
    ['Kasir', 'Tidak wajib', 'Window shift, ditentukan jam datang'],
    ['Pramuniaga', 'Tidak wajib', 'Window shift, ditentukan jam datang'],
    ['Supervisor', 'Wajib', 'Template shift'],
    ['Manajer Toko', 'Wajib', 'Template shift'],
])

<x-panduan.tabel :kolom="['Jabatan', 'Pengaturan', 'Aturan yang dipakai']" :baris="$barisContohJabatan"/>

    <div class="mt-3 space-y-2">
        <x-panduan.catatan tipe="info" judul="Karyawan tanpa jabatan">
            Karyawan yang belum punya jabatan dianggap wajib memakai template shift, supaya aturan
            absennya tidak berubah diam-diam. Tetap sebaiknya jabatan diisi langsung.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="penting" judul="Mengubah pengaturan ini tidak menghapus apa pun">
            Mengosongkan centang wajib template tidak akan menghapus template yang sudah terlanjur
            ditugaskan. Karyawan yang sudah punya penugasan template tetap memakainya sampai
            penugasannya diubah atau dikosongkan di halaman karyawan.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="info" judul="Jabatan yang masih dipakai">
            Menghapus jabatan hanya bisa dilakukan kalau tidak ada karyawan yang memakainya. Kalau
            masih dipakai, jabatan otomatis dinonaktifkan supaya riwayat karyawan tidak rusak.
        </x-panduan.catatan>
    </div>
</div>