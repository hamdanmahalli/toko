@extends('layouts.admin')

@section('judul', 'Shift')

@section('konten')
    <x-page-header class="mb-4" judul="Template Shift"
                   sub="Pola jam kerja per hari beserta batas toleransi telat.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3v3m8-3v3M4.5 9h15M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h2M9 16.5h2M13 13h2"/>
            </svg>
        </x-slot:ikon>
        <x-slot:aksi>
            <a href="{{ route('admin.shift.create') }}"
               class="flex shrink-0 items-center gap-1.5 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 transition hover:brightness-105 active:scale-[.99]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
                Tambah template
            </a>
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-4 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-xs leading-relaxed text-brand-900">
        <p class="font-semibold">Hanya untuk jabatan yang wajib memakai template.</p>
        <p class="mt-1">
            Manajer dan kepala toko memakai template ini. Kasir dan pramuniaga tidak perlu template sama sekali,
            karena jam datang mereka sudah menentukan shift lewat
            <a href="{{ route('admin.window.index') }}" class="font-medium underline">Window Shift</a>.
        </p>
    </div>

    <form method="GET" class="mb-3 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama template"
               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <select name="scope" class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="">Semua cakupan</option>
            @foreach ($scope as $value => $label)
                <option value="{{ $value }}" @selected(request('scope') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
            Filter
        </button>
    </form>

    <div class="space-y-3">
        @forelse ($template as $t)
            <div class="card p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-slate-900">{{ $t->nama }}</h2>
                            @if ($t->kode)
                                <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[11px] font-medium text-white">{{ $t->kode }}</span>
                            @endif
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $t->tipe() === \App\Enums\ShiftTipe::Tetap ? 'bg-slate-100 text-slate-600' : 'bg-brand-100 text-brand-700' }}">
                                {{ $t->tipe()->label() }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                {{ $t->scope->label() }}{{ $t->shop ? ' · '.$t->shop->nama : '' }}
                            </span>
                            @if (! $t->aktif)
                                <span class="rounded-full bg-merah-100 px-2 py-0.5 text-[11px] font-medium text-merah-700">Nonaktif</span>
                            @endif
                        </div>
                        @if ($t->keterangan)
                            <p class="mt-1 text-xs text-slate-500">{{ $t->keterangan }}</p>
                        @endif
                        <p class="tabular mt-1 text-xs text-slate-500">
                            {{ $t->employee_assignments_count }} karyawan ·
                            @if ($t->tipe() === \App\Enums\ShiftTipe::Interval)
                                {{ $t->intervalsAktif()->count() }} sesi jam kerja
                            @else
                                {{ $t->slots->where('aktif', true)->count() }} hari kerja
                            @endif
                            @if ($t->fleksibelTipe())
                                · {{ $t->fleksibelTipe()->label() }}
                            @endif
                            @if ($t->jamCutOff())
                                · cut-off {{ $t->jamCutOff()->format('H:i') }}
                            @endif
                            @if ($t->durasiMaks())
                                · maksimal {{ $t->durasiMaksLabel() }}
                            @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('admin.shift.edit', $t) }}"
                           class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                            Ubah
                        </a>

                        @can('shift.kelola')
                                    <form method="POST" action="{{ route('admin.shift.destroy', $t) }}"
                                          data-konfirmasi="Hapus template &quot;{{ $t->nama }}&quot; beserta slot dan penugasannya? Riwayat absensi lama tidak berubah, tetapi karyawan yang memakai template ini akan kehilangan jam kerjanya."
                                          data-konfirmasi-judul="Hapus template?"
                                          data-konfirmasi-tombol="Hapus"
                                          data-konfirmasi-bahaya>
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-merah-300 px-3 py-1.5 text-sm font-medium text-merah-600 hover:bg-merah-50">
                                    Hapus
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>

                @php $aktif = $t->slots->where('aktif', true)->sortBy('hari'); @endphp

                @if ($aktif->isEmpty())
                    <p class="mt-3 rounded-lg bg-merah-50 px-3 py-2 text-xs text-merah-700">
                        Belum ada hari kerja aktif, jadi template ini tidak bisa dipakai untuk absensi.
                    </p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[34rem] text-left text-xs">
                            <thead class="bg-slate-50/70 text-[11px] font-medium text-slate-400">
                                <tr>
                                    <th class="px-2.5 py-1.5 font-medium">Hari</th>
                                    <th class="px-2.5 py-1.5 font-medium">Masuk</th>
                                    <th class="px-2.5 py-1.5 font-medium">Batas telat</th>
                                    <th class="px-2.5 py-1.5 font-medium">Istirahat</th>
                                    <th class="px-2.5 py-1.5 font-medium">Pulang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($aktif as $s)
                                    <tr>
                                        <td class="px-2.5 py-1.5 font-medium text-slate-700">{{ $s->hariLabel() }}</td>
                                        <td class="tabular px-2.5 py-1.5 text-slate-600">{{ $s->jam_masuk->format('H:i') }}</td>
                                        <td class="tabular px-2.5 py-1.5 text-slate-600">{{ $s->batas_telat->format('H:i') }}</td>
                                        <td class="tabular px-2.5 py-1.5 text-slate-600">
                                            {{ $s->mulai_istirahat && $s->selesai_istirahat
                                                ? $s->mulai_istirahat->format('H:i').'–'.$s->selesai_istirahat->format('H:i')
                                                : '-' }}
                                        </td>
                                        <td class="tabular px-2.5 py-1.5 text-slate-600">{{ $s->jam_pulang->format('H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @empty
            <div class="card px-4 py-10 text-center">
                <p class="text-sm text-slate-500">
                    Belum ada template shift. Tambahkan template supaya karyawan punya jam kerja dan batas telat.
                </p>
            </div>
        @endforelse
    </div>
@endsection
