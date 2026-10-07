<p class="mb-4 text-[13px] leading-relaxed text-slate-600">
    Halaman admin hanya terbuka untuk akun pemilik dan supervisor, dan hanya untuk toko yang
    diawasi. Menu yang tidak terlihat berarti akun belum punya izin untuk halaman itu.
</p>

<div class="space-y-5">
    @include('panduan.bagian.admin.dasbor')
    @include('panduan.bagian.admin.toko')
    @include('panduan.bagian.admin.karyawan')
    @include('panduan.bagian.admin.jabatan')
    @include('panduan.bagian.admin.template-shift')
    @include('panduan.bagian.admin.window')
    @include('panduan.bagian.admin.pengajuan')
    @include('panduan.bagian.admin.laporan')
    @include('panduan.bagian.admin.pengguna')
</div>