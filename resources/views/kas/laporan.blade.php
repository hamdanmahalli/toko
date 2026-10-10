@extends('layouts.app')

@section('judul', 'Laporan Kas')

@section('konten')
    @php $filter = request()->only(['buku', 'dari', 'sampai']); @endphp

    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('kas.index') }}" aria-label="Kembali"
           class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/>
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="font-display text-xl text-slate-900">Laporan Kas</h1>
            <p class="text-xs text-slate-500">Rekap pemasukan dan pengeluaran per periode.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('kas.laporan') }}" class="card mb-4 space-y-3 p-4">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-600" for="buku">Buku</label>
            <select id="buku" name="buku"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                <option value="">Semua buku</option>
                @foreach ($buku as $b)
                    <option value="{{ $b->id }}" @selected($bukuId === $b->id)>{{ $b->nama }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-600" for="dari">Dari</label>
                <input id="dari" name="dari" type="date" value="{{ $dari->toDateString() }}"
                       class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-slate-600" for="sampai">Sampai</label>
                <input id="sampai" name="sampai" type="date" value="{{ $sampai->toDateString() }}"
                       class="tabular w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
        </div>

        <button class="w-full rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 active:scale-[.99]">
            Tampilkan
        </button>
    </form>

    <div class="mb-4 grid grid-cols-2 gap-2">
        <div class="rounded-2xl bg-white p-3.5 shadow-sm ring-1 ring-black/5">
            <p class="text-[11px] text-slate-500">Saldo awal</p>
            <p class="tabular mt-0.5 text-base font-semibold text-slate-900">{{ \App\Support\Rupiah::format($rekap['ringkasan']['saldo_awal']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-3.5 shadow-sm ring-1 ring-black/5">
            <p class="text-[11px] text-slate-500">Saldo akhir</p>
            <p class="tabular mt-0.5 text-base font-semibold text-brand-700">{{ \App\Support\Rupiah::format($rekap['ringkasan']['saldo_akhir']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-3.5 shadow-sm ring-1 ring-black/5">
            <p class="text-[11px] text-slate-500">Pemasukan</p>
            <p class="tabular mt-0.5 text-base font-semibold text-brand-700">{{ \App\Support\Rupiah::format($rekap['ringkasan']['masuk']) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-3.5 shadow-sm ring-1 ring-black/5">
            <p class="text-[11px] text-slate-500">Pengeluaran</p>
            <p class="tabular mt-0.5 text-base font-semibold text-merah-600">{{ \App\Support\Rupiah::format($rekap['ringkasan']['keluar']) }}</p>
        </div>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-2">
        <a href="{{ route('kas.laporan.pdf', $filter) }}"
           class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/>
            </svg>
            Unduh PDF
        </a>
        <a href="{{ route('kas.laporan.excel', $filter) }}"
           class="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/>
            </svg>
            Unduh Excel
        </a>
    </div>

    @if (empty($rekap['baris']))
        <p class="card px-4 py-10 text-center text-sm text-slate-500">
            Tidak ada transaksi pada periode ini.
        </p>
    @else
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-slate-100 text-[11px] uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-3 py-2.5 font-medium">Tanggal</th>
                        <th class="px-3 py-2.5 font-medium">Buku</th>
                        <th class="px-3 py-2.5 font-medium">Kategori</th>
                        <th class="px-3 py-2.5 text-right font-medium">Masuk</th>
                        <th class="px-3 py-2.5 text-right font-medium">Keluar</th>
                        <th class="px-3 py-2.5 text-right font-medium">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rekap['baris'] as $b)
                        <tr>
                            <td class="tabular px-3 py-2.5 text-slate-600">{{ \Illuminate\Support\Carbon::parse($b['tanggal'])->format('d/m/Y') }}</td>
                            <td class="px-3 py-2.5 text-slate-700">{{ $b['buku'] }}</td>
                            <td class="px-3 py-2.5 text-slate-700">
                                {{ $b['kategori'] }}
                                @if ($b['keterangan'])
                                    <span class="block text-[11px] text-slate-400">{{ $b['keterangan'] }}</span>
                                @endif
                            </td>
                            <td class="tabular px-3 py-2.5 text-right text-brand-700">{{ $b['masuk'] ? \App\Support\Rupiah::format($b['masuk']) : '-' }}</td>
                            <td class="tabular px-3 py-2.5 text-right text-merah-600">{{ $b['keluar'] ? \App\Support\Rupiah::format($b['keluar']) : '-' }}</td>
                            <td class="tabular px-3 py-2.5 text-right font-medium text-slate-800">{{ \App\Support\Rupiah::format($b['saldo']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-slate-100 text-sm font-semibold text-slate-800">
                    <tr>
                        <td class="px-3 py-2.5" colspan="3">Total</td>
                        <td class="tabular px-3 py-2.5 text-right text-brand-700">{{ \App\Support\Rupiah::format($rekap['ringkasan']['masuk']) }}</td>
                        <td class="tabular px-3 py-2.5 text-right text-merah-600">{{ \App\Support\Rupiah::format($rekap['ringkasan']['keluar']) }}</td>
                        <td class="tabular px-3 py-2.5 text-right text-slate-900">{{ \App\Support\Rupiah::format($rekap['ringkasan']['saldo_akhir']) }}</td>
                        </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection
