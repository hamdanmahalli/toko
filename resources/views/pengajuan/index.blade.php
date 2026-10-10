@extends('layouts.app')

@section('judul', 'Pengajuan')

@section('konten')
    <div class="mb-4">
        <h1 class="font-display text-xl text-slate-900">Pengajuan</h1>
        <p class="mt-0.5 text-xs text-slate-500">Izin, cuti, dinas, dan lembur yang kamu ajukan.</p>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-2">
        {{-- Tanpa JavaScript tautan tetap membuka halaman form; dengan JavaScript
             form yang sama muncul sebagai pop-up. --}}
        <a href="{{ route('pengajuan.izin') }}" data-buka-modal="modal-izin"
           class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-3 text-center text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:brightness-105 active:scale-[.99]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Ajukan izin / cuti
        </a>
        <a href="{{ route('pengajuan.lembur') }}" data-buka-modal="modal-lembur"
           class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:border-brand-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                <circle cx="12" cy="12" r="9"/>
            </svg>
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
                                        \App\Enums\RequestStatus::Rejected => 'bg-merah-100 text-merah-700',
                                        \App\Enums\RequestStatus::Cancelled => 'bg-slate-100 text-slate-500',
                                        default => 'bg-merah-100 text-merah-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $warna }}">
                                    {{ $p->status->label() }}
                                </span>
                                @if ($p->status->isPending())
                                    <form method="POST" action="{{ route('pengajuan.batal', ['izin', $p->id]) }}"
                                          class="mt-2" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                        @csrf
                                        <button class="text-xs font-medium text-merah-600 hover:underline">Batalkan</button>
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
                                        \App\Enums\RequestStatus::Rejected => 'bg-merah-100 text-merah-700',
                                        \App\Enums\RequestStatus::Cancelled => 'bg-slate-100 text-slate-500',
                                        default => 'bg-merah-100 text-merah-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $warna }}">
                                    {{ $p->status->label() }}
                                </span>
                                @if ($p->status->isPending())
                                    <form method="POST" action="{{ route('pengajuan.batal', ['lembur', $p->id]) }}"
                                          class="mt-2" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                        @csrf
                                        <button class="text-xs font-medium text-merah-600 hover:underline">Batalkan</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Pop-up form izin / cuti. --}}
    <dialog id="modal-izin"
            class="m-auto w-[calc(100%-2rem)] max-w-md rounded-[24px] bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-900/40 backdrop:backdrop-blur-sm">
        <form method="POST" action="{{ route('pengajuan.izin.store') }}" class="p-5">
            @csrf

            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-lg text-slate-900">Ajukan izin / cuti</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Pengajuan langsung terkirim ke atasan.</p>
                </div>
                <button type="button" data-tutup-modal aria-label="Tutup"
                        class="-mr-1 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="jenis">Jenis</label>
                    <select id="jenis" name="jenis" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        @foreach ($jenis as $value => $label)
                            <option value="{{ $value }}" @selected(old('jenis', 'izin') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tanggal_mulai">Mulai</label>
                        <input id="tanggal_mulai" name="tanggal_mulai" type="date" required
                               value="{{ old('tanggal_mulai', now()->toDateString()) }}"
                               class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tanggal_selesai">Selesai</label>
                        <input id="tanggal_selesai" name="tanggal_selesai" type="date" required
                               value="{{ old('tanggal_selesai', now()->toDateString()) }}"
                               class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="3" placeholder="Contoh: acara keluarga di luar kota"
                              class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <button type="button" data-tutup-modal
                        class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Batal
                </button>
                <button class="flex-1 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
                    Kirim pengajuan
                </button>
            </div>
        </form>
    </dialog>

    {{-- Pop-up form lembur. --}}
    <dialog id="modal-lembur"
            class="m-auto w-[calc(100%-2rem)] max-w-md rounded-[24px] bg-white p-0 text-slate-800 shadow-2xl backdrop:bg-slate-900/40 backdrop:backdrop-blur-sm">
        <form method="POST" action="{{ route('pengajuan.lembur.store') }}" class="p-5">
            @csrf

            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-lg text-slate-900">Ajukan lembur</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Isi jam kerja tambahan di luar jadwal.</p>
                </div>
                <button type="button" data-tutup-modal aria-label="Tutup"
                        class="-mr-1 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tanggal_lembur">Tanggal</label>
                    <input id="tanggal_lembur" name="tanggal" type="date" required
                           value="{{ old('tanggal', now()->toDateString()) }}"
                           class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="jam_mulai">Mulai</label>
                        <input id="jam_mulai" name="jam_mulai" type="time" required value="{{ old('jam_mulai', '18:00') }}"
                               class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="jam_selesai">Selesai</label>
                        <input id="jam_selesai" name="jam_selesai" type="time" required value="{{ old('jam_selesai', '20:00') }}"
                               class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    </div>
                </div>

                <p class="rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    @if ($tarifDefault)
                        Tarif lembur karyawan: <span class="tabular">Rp {{ number_format((float) $tarifDefault, 0, ',', '.') }}</span> per jam.
                    @else
                        Tarif lembur belum diatur pada data karyawan, jadi total akan dihitung manual oleh atasan.
                    @endif
                </p>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="keterangan_lembur">Keterangan</label>
                    <textarea id="keterangan_lembur" name="keterangan" rows="3" placeholder="Contoh: rekap stok akhir bulan"
                              class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="mt-5 flex gap-2">
                <button type="button" data-tutup-modal
                        class="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Batal
                </button>
                <button class="flex-1 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
                    Kirim pengajuan
                </button>
            </div>
        </form>
    </dialog>
@endsection

@push('kaki')
<script>
(() => {
    const buka = (dialog) => {
        if (dialog && typeof dialog.showModal === 'function') {
            dialog.showModal();
            return true;
        }
        return false;
    };

    document.querySelectorAll('[data-buka-modal]').forEach((pemicu) => {
        pemicu.addEventListener('click', (event) => {
            const dialog = document.getElementById(pemicu.getAttribute('data-buka-modal'));
            // Tanpa dukungan dialog, biarkan tautan membuka halaman form biasa.
            if (buka(dialog)) event.preventDefault();
        });
    });

    document.querySelectorAll('dialog [data-tutup-modal]').forEach((tombol) => {
        tombol.addEventListener('click', () => tombol.closest('dialog')?.close());
    });

    // Klik di area gelap di luar kartu menutup pop-up.
    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    });

    // Bila pengajuan gagal divalidasi, buka kembali pop-up yang bersangkutan.
    @if ($errors->any() && old('jam_mulai'))
        buka(document.getElementById('modal-lembur'));
    @elseif ($errors->any() && old('tanggal_mulai'))
        buka(document.getElementById('modal-izin'));
    @endif
})();
</script>
@endpush
