@extends('layouts.app')

@section('judul', 'Pengajuan')

@section('konten')
    <div class="mb-4">
        <h1 class="font-display text-xl text-slate-900">Pengajuan</h1>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-2">
        <a href="{{ route('pengajuan.izin') }}"
           class="rounded-xl bg-brand-600 px-4 py-3 text-sm font-medium text-white hover:bg-brand-700">
            Ajukan izin / cuti
        </a>
        <a href="{{ route('pengajuan.lembur') }}"
           class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-medium text-slate-700 hover:bg-slate-50 focus:border-brand-400">
            Ajukan lembur
        </a>
    </div>

    <section class="mb-5">
        <h2 class="mb-2 font-display text-[15px] text-slate-900">Izin, sakit, cuti, dinas</h2>

        @if ($izin->isEmpty())
            <p class="card px-4 py-8 text-center text-sm text-slate-500">
                Belum ada pengajuan izin atau cuti.
            </p>
        @else
            <div class="space-y-2">
                @foreach ($izin as $p)
                    <div class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800">
                                    {{ $p->jenis->label() }}
                                    <span class="text-xs font-normal text-slate-500">
                                        {{ $p->tanggal_mulai->format('d/m/Y') }}
                                        &ndash;
                                        {{ $p->tanggal_selesai->format('d/m/Y') }}
                                    </span>
                                </p>
                                <p class="tabular mt-0.5 text-xs text-slate-500">
                                    {{ rtrim(rtrim(number_format((float) $p->jumlah_hari, 1, ',', '.'), '0'), ',') }} hari
                                </p>
                                @if ($p->keterangan)
                                    <p class="mt-1 text-xs text-slate-500">{{ $p->keterangan }}</p>
                                @endif
                                @if ($p->catatan_reviewer)
                                    <p class="mt-1 rounded-lg bg-slate-50 px-2 py-1 text-xs text-slate-600">
                                        Catatan atasan: {{ $p->catatan_reviewer }}
                                    </p>
                                @endif
                            </div>

                            <div class="shrink-0 text-right">
                                @php
                                    $warna = match ($p->status) {
                                        \App\Enums\RequestStatus::Approved => 'bg-brand-100 text-brand-800',
                                        \App\Enums\RequestStatus::Rejected => 'bg-rose-100 text-rose-700',
                                        \App\Enums\RequestStatus::Cancelled => 'bg-slate-100 text-slate-500',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $warna }}">
                                    {{ $p->status->label() }}
                                </span>
                                @if ($p->status->isPending())
                                    <form method="POST" action="{{ route('pengajuan.batal', ['izin', $p->id]) }}"
                                          class="mt-2" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                        @csrf
                                        <button class="text-xs font-medium text-rose-600 hover:underline">Batalkan</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <h2 class="mb-2 font-display text-[15px] text-slate-900">Lembur</h2>

        @if ($lembur->isEmpty())
            <p class="card px-4 py-8 text-center text-sm text-slate-500">
                Belum ada pengajuan lembur.
            </p>
        @else
            <div class="space-y-2">
                @foreach ($lembur as $p)
                    <div class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="tabular text-sm font-medium text-slate-800">
                                    {{ $p->tanggal->format('d/m/Y') }}
                                    <span class="font-normal text-slate-500">
                                        {{ $p->jam_mulai->format('H:i') }}&ndash;{{ $p->jam_selesai->format('H:i') }}
                                    </span>
                                </p>
                                <p class="tabular mt-0.5 text-xs text-slate-500">
                                    {{ rtrim(rtrim(number_format((float) $p->durasi_jam, 2, ',', '.'), '0'), ',') }} jam
                                    @if ($p->tarif_per_jam)
                                        &middot; {{ 'Rp '.number_format((float) $p->total_lembur, 0, ',', '.') }}
                                    @endif
                                </p>
                                @if ($p->keterangan)
                                    <p class="mt-1 text-xs text-slate-500">{{ $p->keterangan }}</p>
                                @endif
                                @if ($p->catatan_reviewer)
                                    <p class="mt-1 rounded-lg bg-slate-50 px-2 py-1 text-xs text-slate-600">
                                        Catatan atasan: {{ $p->catatan_reviewer }}
                                    </p>
                                @endif
                            </div>

                            <div class="shrink-0 text-right">
                                @php
                                    $warna = match ($p->status) {
                                        \App\Enums\RequestStatus::Approved => 'bg-brand-100 text-brand-800',
                                        \App\Enums\RequestStatus::Rejected => 'bg-rose-100 text-rose-700',
                                        \App\Enums\RequestStatus::Cancelled => 'bg-slate-100 text-slate-500',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $warna }}">
                                    {{ $p->status->label() }}
                                </span>
                                @if ($p->status->isPending())
                                    <form method="POST" action="{{ route('pengajuan.batal', ['lembur', $p->id]) }}"
                                          class="mt-2" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                        @csrf
                                        <button class="text-xs font-medium text-rose-600 hover:underline">Batalkan</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
