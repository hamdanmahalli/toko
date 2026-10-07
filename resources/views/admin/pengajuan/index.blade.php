@extends('layouts.admin')

@section('judul', 'Pengajuan')

@section('konten')
    <div class="mb-4">
        <h1 class="font-display text-xl text-slate-900">Pengajuan</h1>
        <p class="text-xs text-slate-500">
            @if ($jumlahPending > 0)
                <span class="tabular font-medium text-amber-600">{{ $jumlahPending }}</span> pengajuan menunggu keputusan.
            @else
                Tidak ada pengajuan yang menunggu keputusan.
            @endif
        </p>
    </div>

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto_auto_auto]">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau NIP karyawan"
               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <select name="status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="">Semua status</option>
            @foreach ($status as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="shop" class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="">Semua toko</option>
            @foreach ($toko as $t)
                <option value="{{ $t->id }}" @selected(request('shop') == $t->id)>{{ $t->nama }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600">
            <input type="checkbox" name="hanya_pending" value="1" @checked(request('hanya_pending'))
                   class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
            Menunggu
        </label>
        <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
            Filter
        </button>
    </form>

    @php
    $bolehTinjau = fn ($p) => $p->status->isPending();
@endphp

    <section class="mb-5">
        <h2 class="mb-2 font-display text-[15px] text-slate-900">Izin, sakit, cuti, dinas</h2>

        @if ($izin->count() === 0)
            <p class="card px-4 py-8 text-center text-sm text-slate-500">
                Tidak ada pengajuan izin atau cuti yang cocok.
            </p>
        @else
            <div class="card overflow-x-auto">
                <table class="w-full min-w-[52rem] text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-medium text-slate-400">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Karyawan</th>
                            <th class="px-4 py-2.5 font-medium">Jenis</th>
                            <th class="px-4 py-2.5 font-medium">Tanggal</th>
                            <th class="px-4 py-2.5 font-medium">Keterangan</th>
                            <th class="px-4 py-2.5 font-medium">Status</th>
                            <th class="px-4 py-2.5 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($izin as $p)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-800">{{ $p->employee->nama }}</p>
                                    <p class="text-xs text-slate-500">{{ $p->employee->shop->nama }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $p->jenis->label() }}</td>
                                <td class="tabular px-4 py-3 text-slate-600">
                                    {{ $p->tanggal_mulai->format('d/m/Y') }}
                                    &ndash;
                                    {{ $p->tanggal_selesai->format('d/m/Y') }}
                                    <span class="block text-[11px] text-slate-400">
                                        {{ rtrim(rtrim(number_format((float) $p->jumlah_hari, 1, ',', '.'), '0'), ',') }} hari
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ \Illuminate\Support\Str::limit($p->keterangan, 60) ?: '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-status-pengajuan :status="$p->status" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($bolehTinjau($p))
                                        @can('pengajuan.setujui')
                                            <form method="POST" class="inline"
                                                  data-kirim-sekali
                                                  action="{{ route('admin.pengajuan.setujui', ['izin', $p->id]) }}">
                                                @csrf
                                                <button class="rounded-lg bg-brand-600 px-2.5 py-1.5 text-xs font-medium text-white transition hover:bg-brand-700">
                                                    Setujui
                                                </button>
                                            </form>
                                            <form method="POST" class="inline"
                                                  data-alasan-tolak
                                                  action="{{ route('admin.pengajuan.tolak', ['izin', $p->id]) }}">
                                                @csrf
                                                <input type="hidden" name="catatan" value="">
                                                <button class="rounded-lg border border-rose-300 px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                                    Tolak
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Menunggu keputusan</span>
                                        @endcan
                                    @else
                                        <span class="text-xs text-slate-400">
                                            {{ $p->reviewer?->name ?? 'Sudah diproses' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($izin->hasPages())
                <div class="mt-2">{{ $izin->links() }}</div>
            @endif
        @endif
    </section>

    <section>
        <h2 class="mb-2 font-display text-[15px] text-slate-900">Lembur</h2>

        @if ($lembur->count() === 0)
            <p class="card px-4 py-8 text-center text-sm text-slate-500">
                Tidak ada pengajuan lembur yang cocok.
            </p>
        @else
            <div class="card overflow-x-auto">
                <table class="w-full min-w-[52rem] text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-medium text-slate-400">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Karyawan</th>
                            <th class="px-4 py-2.5 font-medium">Tanggal</th>
                            <th class="px-4 py-2.5 font-medium">Jam</th>
                            <th class="px-4 py-2.5 font-medium">Estimasi</th>
                            <th class="px-4 py-2.5 font-medium">Status</th>
                            <th class="px-4 py-2.5 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($lembur as $p)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-800">{{ $p->employee->nama }}</p>
                                    <p class="text-xs text-slate-500">{{ $p->employee->shop->nama }}</p>
                                </td>
                                <td class="tabular px-4 py-3 text-slate-600">
                                    {{ $p->tanggal->format('d/m/Y') }}
                                    {{-- Keterangan wajib kelihatan: alasan lembur itu
                                         bagian dari keputusan atasan. --}}
                                    <span class="mt-0.5 block text-[11px] font-normal normal-case text-slate-500">
                                        {{ \Illuminate\Support\Str::limit($p->keterangan, 60) ?: 'Tanpa keterangan' }}
                                    </span>
                                </td>
                                <td class="tabular px-4 py-3 text-slate-600">
                                    {{ $p->jam_mulai->format('H:i') }}&ndash;{{ $p->jam_selesai->format('H:i') }}
                                    <span class="block text-[11px] text-slate-400">
                                        {{ rtrim(rtrim(number_format((float) $p->durasi_jam, 2, ',', '.'), '0'), ',') }} jam
                                    </span>
                                </td>
                                <td class="tabular px-4 py-3 text-slate-600">
                                    @if ($p->tarif_per_jam)
                                        Rp {{ number_format((float) $p->total_lembur, 0, ',', '.') }}
                                    @else
                                        <span class="text-xs text-slate-400">Tarif belum diatur</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-status-pengajuan :status="$p->status" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($bolehTinjau($p))
                                        @can('pengajuan.setujui')
                                            <form method="POST" class="inline"
                                                  data-kirim-sekali
                                                  action="{{ route('admin.pengajuan.setujui', ['lembur', $p->id]) }}">
                                                @csrf
                                                <button class="rounded-lg bg-brand-600 px-2.5 py-1.5 text-xs font-medium text-white transition hover:bg-brand-700">
                                                    Setujui
                                                </button>
                                            </form>
                                            <form method="POST" class="inline"
                                                  data-alasan-tolak
                                                  action="{{ route('admin.pengajuan.tolak', ['lembur', $p->id]) }}">
                                                @csrf
                                                <input type="hidden" name="catatan" value="">
                                                <button class="rounded-lg border border-rose-300 px-2.5 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                                    Tolak
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Menunggu keputusan</span>
                                        @endcan
                                    @else
                                        <span class="text-xs text-slate-400">
                                            {{ $p->reviewer?->name ?? 'Sudah diproses' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($lembur->hasPages())
                <div class="mt-2">{{ $lembur->links() }}</div>
            @endif
        @endif
    </section>

    <script>
        // Cegah double submit: nonaktifkan tombol begitu form pertama dikirim.
        document.querySelectorAll('form[data-kirim-sekali]').forEach((form) => {
            form.addEventListener('submit', () => {
                form.querySelectorAll('button').forEach((tombol) => {
                    tombol.disabled = true;
                    tombol.classList.add('pointer-events-none', 'opacity-60');
                });
            });
        });

        // Penolakan wajib beralasan karena karyawannya perlu tahu apa yang salah.
        // Alasannya dibaca lewat prompt supaya tabel tetap ringkas, lalu form
        // dikirim hanya setelah isinya tidak kosong.
        document.querySelectorAll('form[data-alasan-tolak]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.sudahDicek) return;

                event.preventDefault();

                const jawaban = window.prompt('Alasan penolakan (wajib diisi, dibaca karyawan):');

                if (jawaban === null) return;

                const alasan = jawaban.trim();

                if (! alasan) {
                    window.alert('Alasan penolakan wajib diisi.');
                    return;
                }

                form.querySelector('[name="catatan"]').value = alasan;
                form.dataset.sudahDicek = '1';
                form.querySelectorAll('button').forEach((tombol) => {
                    tombol.disabled = true;
                    tombol.classList.add('pointer-events-none', 'opacity-60');
                });
                form.submit();
            });
        });
    </script>
@endsection
