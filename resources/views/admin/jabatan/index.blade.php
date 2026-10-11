@extends('layouts.admin')

@section('judul', 'Jabatan')

@section('konten')
    <x-page-header class="mb-4" judul="Jabatan"
                   sub="Berlaku untuk seluruh toko. Aturan jam kerja diatur lewat template shift.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M4 7h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Zm0 5h16"/>
            </svg>
        </x-slot:ikon>
        <x-slot:aksi>
            <a href="{{ route('admin.jabatan.create') }}"
               class="flex shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:brightness-105 active:scale-[.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Tambah jabatan
            </a>
        </x-slot:aksi>
    </x-page-header>

    <form method="GET" class="mb-3 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode jabatan"
               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="">Semua status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>
        <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
            Filter
        </button>
    </form>

    <div class="card overflow-hidden">
        @if ($jabatan->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-slate-500">
                Belum ada jabatan. Tambahkan jabatan seperti Kasir, Admin, atau Supervisor.
            </p>
        @else
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-medium text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Jabatan</th>
                        <th class="px-4 py-2.5 font-medium">Karyawan</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($jabatan as $j)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $j->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $j->kode }}</p>
                                @if ($j->deskripsi)
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $j->deskripsi }}</p>
                                @endif
                            </td>
                            <td class="tabular px-4 py-3 text-slate-600">{{ $j->employees_count }}</td>
                            <td class="px-4 py-3">
                                @if ($j->aktif)
                                    <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-medium text-brand-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.jabatan.edit', $j) }}" class="text-sm font-medium text-brand-600 hover:underline">Ubah</a>
                                @if ($j->employees_count === 0 && $j->aktif)
                                    <span class="px-1 text-slate-300">|</span>
                                    <form method="POST" action="{{ route('admin.jabatan.destroy', $j) }}" class="inline"
                                          onsubmit="return confirm('Hapus jabatan {{ $j->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-sm font-medium text-merah-600 hover:underline">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
