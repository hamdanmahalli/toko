@extends('layouts.admin')

@section('judul', 'Kartu Absensi')

@section('konten')
    <h1 class="mb-4 font-display text-xl text-slate-900">Kartu {{ $karyawan->nama }}</h1>

    <div class="grid gap-4 lg:grid-cols-2">
        <div>
            @include('absen.partials.kartu', ['pengguna' => $karyawan])

            <button type="button" onclick="window.print()"
                    class="mt-3 w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 print:hidden">
                Cetak kartu
            </button>
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
                    <button class="rounded-lg border border-amber-300 px-3 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50">
                        Ganti kartu
                    </button>
                </form>
            </div>

            <div class="card p-5">
                <h2 class="mb-2 font-display text-[15px] text-slate-900">Mencatat lewat perangkat presensi</h2>
                <p class="text-xs leading-relaxed text-slate-600">
                    Kartu ini bisa dipakai di perangkat presensi
                    <span class="font-medium text-slate-700">{{ $karyawan->shop->nama }}</span> tanpa
                    perlu HP. Di perangkat presensi toko lain akan ditolak.
                </p>
                <a href="{{ route('presensi.form', $karyawan->shop->kode) }}" target="_blank" rel="noopener"
                   class="mt-3 inline-block rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Buka halaman presensi
                </a>
            </div>

            <a href="{{ route('admin.karyawan.index') }}"
               class="block rounded-xl border border-slate-200 py-2.5 text-center text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                Kembali ke daftar karyawan
            </a>
        </div>
    </div>
@endsection