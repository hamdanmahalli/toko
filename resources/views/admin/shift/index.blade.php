@extends('layouts.admin')

@section('judul', 'Shift')

@section('konten')
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-xl text-slate-900">Template Shift</h1>
            <p class="text-xs text-slate-500">Pola jam kerja per hari beserta batas toleransi telat.</p>
        </div>
        <a href="{{ route('admin.shift.create') }}"
           class="shrink-0 rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-brand-700">
            Tambah template
        </a>
    </div>

    <div class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-xs leading-relaxed text-sky-900">
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
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $t->tipe() === \App\Enums\ShiftTipe::Tetap ? 'bg-slate-100 text-slate-600' : 'bg-sky-100 text-sky-700' }}">
                                {{ $t->tipe()->label() }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                {{ $t->scope->label() }}{{ $t->shop ? ' · '.$t->shop->nama : '' }}
                            </span>
                            @if (! $t->aktif)
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700">Nonaktif</span>
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
                            <form method="POST"
                                  action="{{ route('admin.shift.destroy', $t) }}"
                                  onsubmit="return confirm('Hapus template &quot;{{ $t->nama }}&quot; beserta slot dan penugasannya? Riwayat absensi lama tidak berubah, tetapi karyawan yang memakai template ini akan kehilangan jam kerjanya.')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-rose-300 px-3 py-1.5 text-sm font-medium text-rose-600 hover:bg-rose-50">
                                    Hapus
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>

                @php $aktif = $t->slots->where('aktif', true)->sortBy('hari'); @endphp

                @if ($aktif->isEmpty())
                    <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
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
