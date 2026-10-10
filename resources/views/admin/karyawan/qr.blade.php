@extends('layouts.admin')

@section('judul', 'Kartu Absensi')

@section('konten')
    <h1 class="mb-4 font-display text-xl text-slate-900 print:hidden">Kartu {{ $karyawan->nama }}</h1>

    <div class="grid gap-4 lg:grid-cols-2">
        <div>
            @include('absen.partials.kartu', ['pengguna' => $karyawan])

            <div class="mt-3 flex gap-2 print:hidden">
                <button type="button" onclick="window.print()"
                        class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Cetak kartu
                </button>
                <button type="button" data-unduh-kartu="#kartu-pegawai"
                        data-label="{{ \Illuminate\Support\Str::slug($karyawan->nama) ?: 'karyawan' }}"
                        class="flex-1 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                    Unduh PNG
                </button>
            </div>
        </div>

        <div class="space-y-4 print:hidden">
            <div class="card p-5">
                <h2 class="mb-2 font-display text-[15px] text-slate-900">Kode manual</h2>
                <code class="block select-all break-all rounded-lg bg-slate-50 px-3 py-2.5 text-center text-xs text-slate-700">
                    {{ $token }}
                </code>
                <p class="mt-2 text-xs leading-relaxed text-slate-500">
                    Berlaku selama versi QR masih {{ $karyawan->qr_version }}. Kode yang sama
                    tercetak di QR dan barcode, jadi tidak ada yang perlu dicocokkan manual.
                </p>
            </div>

            <div class="card p-5">
                <h2 class="mb-2 font-display text-[15px] text-slate-900">Mengganti kartu</h2>
                <p class="text-xs leading-relaxed text-slate-600">
                    Naikkan versi QR untuk membatalkan semua kartu lama. Kartu lama yang sudah tercetak
                    otomatis ditolak, lalu cetak ulang dari halaman ini.
                </p>
                <form method="POST" action="{{ route('admin.karyawan.rotasi-qr', $karyawan) }}" class="mt-3">
                    @csrf
                    <button class="rounded-lg border border-merah-300 px-3 py-2 text-sm font-medium text-merah-700 hover:bg-merah-50">
                        Ganti kartu
                    </button>
                </form>
            </div>

            <a href="{{ route('admin.karyawan.index') }}"
               class="block rounded-xl border border-slate-200 py-2.5 text-center text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                Kembali ke daftar karyawan
            </a>
        </div>
    </div>
@endsection