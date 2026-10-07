<div>
    <h3 class="font-display text-[15px] text-slate-900">Dasbor</h3>
    <p class="mt-2 text-[13px] leading-relaxed text-slate-600">
        Ringkasan hari ini: jumlah karyawan aktif, yang sudah hadir, yang terlambat, dan yang
        belum absen pulang. Di bawahnya ada tabel per toko beserta status geofence tiap toko,
        rekap jumlah orang per window shift, pengajuan yang menunggu keputusan, dan daftar
        karyawan yang belum absen pulang.
    </p>
    <div class="mt-2">
        <x-panduan.catatan tipe="info">
            Bagian <span class="font-medium">Per window</span> hanya menghitung kasir dan
            pramuniaga. Manajer dan kepala toko mengikuti template shift, jadi tidak muncul di sana.
            Angka dihitung dari label yang tersimpan saat absen terjadi, bukan dari window yang
            sedang aktif sekarang.
        </x-panduan.catatan>
    </div>
</div>