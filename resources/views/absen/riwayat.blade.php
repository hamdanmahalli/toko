@extends('layouts.app')

@section('judul', 'Riwayat Absen')

@section('konten')
@inject('geo', 'App\Services\GeoService')

<div class="card masuk p-5">
    <div class="mb-3 flex items-center justify-between gap-2">
        <h1 class="font-display text-[15px] text-slate-900">Riwayat absen</h1>
        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700">
            {{ $riwayat->total() }} hari
        </span>
    </div>

    @if ($riwayat->isEmpty())
        <p class="py-6 text-center text-sm text-slate-400">Belum ada riwayat.</p>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($riwayat as $a)
                <li class="py-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-800">
                            {{ $a->tanggal->translatedFormat('l, d F Y') }}
                        </p>
                        <div class="flex shrink-0 items-center gap-1.5">
                            @if ($a->sesi > 1)
                                {{-- Shift interval bisa punya beberapa sesi sehari,
                                     jadi nomor sesinya perlu terlihat supaya dua
                                     baris tanggal yang sama tidak tertukar. --}}
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    Sesi {{ $a->sesi }}
                                </span>
                            @endif
                            @if ($a->shift_label_masuk)
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700">
                                    {{ $a->shift_label_masuk }}
                                </span>
                            @endif
                            @if ($a->metode === \App\Enums\AbsenMethod::Presensi)
                                {{-- Absen dari perangkat presensi dicatat tanpa HP, jadi methodenya perlu
                                     terlihat supaya atasan tahu ini bukan
                                     absen dari HP milik karyawan. --}}
                                <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700">
                                    {{ $a->metode->label() }}
                                </span>
                            @endif
                            @if ($a->status_masuk)
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $a->status_masuk->badgeClass() }}">
                                    {{ $a->status_masuk->label() }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <dl class="tabular mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-500">
                        <div class="flex gap-1">
                            <dt>Masuk:</dt>
                            <dd class="font-medium text-slate-700">{{ $a->jam_masuk?->format('H:i') ?? '-' }}</dd>
                        </div>
                        <div class="flex gap-1">
                            <dt>Pulang:</dt>
                            <dd class="font-medium text-slate-700">{{ $a->jam_pulang?->format('H:i') ?? '-' }}</dd>
                        </div>
                        @if ($a->durasiKerjaLabel())
                            <div class="flex gap-1">
                                <dt>Durasi:</dt>
                                <dd class="font-medium text-slate-700">{{ $a->durasiKerjaLabel() }}</dd>
                            </div>
                        @endif
                        @if ($a->jarak_masuk_meter !== null)
                            <div class="flex gap-1">
                                <dt>Jarak:</dt>
                                <dd class="font-medium text-slate-700">{{ $geo->formatJarak((float) $a->jarak_masuk_meter) }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($a->catatan)
                        <p class="mt-1.5 text-[11px] text-amber-700">{{ $a->catatan }}</p>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="mt-4">{{ $riwayat->links() }}</div>
    @endif
</div>
@endsection
