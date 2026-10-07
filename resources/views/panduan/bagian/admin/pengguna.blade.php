<div>
    <h3 class="font-display text-[15px] text-slate-900">Pengguna</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Halaman ini mengatur peran, status aktif, dan toko yang diawasi setiap akun. Ini satu-satunya
        tempat tabel penugasan toko terisi, jadi supervisor yang belum ditugaskan tidak punya akses
        ke toko mana pun.
    </p>

    <x-panduan.tabel
        class="mt-2"
        :kolom="['Bagian', 'Fungsi']"
        :baris="[
            ['Cari dan filter', 'Mencari berdasarkan nama atau email, dan menyaring berdasarkan peran.'],
            ['Peran', 'Boleh memilih lebih dari satu peran. Tahan Ctrl atau Cmd saat memilih.'],
            ['Toko yang diawasi', 'Daftar centang toko. Hanya toko yang boleh diawasi oleh akun yang sedang login.'],
            ['Akun aktif', 'Bila dimatikan, akun dikeluarkan dari sesi berjalan dan tidak bisa masuk lagi.'],
            ['Simpan', 'Menyimpan peran, toko, dan status aktif sekaligus.'],
        ]"/>

    <h4 class="mt-4 font-display text-[14px] text-slate-900">Aturan yang perlu diperhatikan</h4>
    <ul class="mt-2 space-y-2 text-[13px] leading-relaxed text-slate-600">
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>
                <span class="font-medium text-slate-800">Peran pemilik mengabaikan daftar toko.</span>
                Pilih peran pemilik dan daftar centang toko otomatis dinonaktifkan karena peran itu
                sudah memberi akses ke seluruh jaringan.
            </span>
        </li>
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>
                <span class="font-medium text-slate-800">Tidak bisa menonaktifkan akun sendiri.</span>
                Ini dicegah supaya admin tidak terkunci dari halaman ini.
            </span>
        </li>
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>
                <span class="font-medium text-slate-800">Tidak semua peran bisa dipilih.</span>
                Admin tanpa izin mengelola peran tidak akan melihat peran pemilik sebagai pilihan.
            </span>
        </li>
        <li class="flex gap-2">
            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-brand-500"></span>
            <span>
                <span class="font-medium text-slate-800">Menyimpan akan mengeluarkan akun itu dari
                perangkat yang sedang dipakai.</span>
                Ini disengaja karena perubahan peran atau toko berarti hak akses berubah total.
            </span>
        </li>
    </ul>

    <div class="mt-3 space-y-2">
        <x-panduan.catatan tipe="info">
            Halaman ini hanya mengubah akun yang sudah ada. Untuk membuat akun baru, gunakan menu
            Karyawan. Menonaktifkan akun di sini tidak menghapus data loginnnya maupun riwayat
            absensinya.
        </x-panduan.catatan>

        <x-panduan.catatan tipe="penting" judul="Tidak ada reset password di halaman ini">
            Reset password hanya tersedia di halaman ubah karyawan, dan hanya untuk akun yang
            tertaut ke data karyawan. Akun pengelola perlu dibuat ulang lewat pengaturan awal
            aplikasi.
        </x-panduan.catatan>
    </div>
</div>