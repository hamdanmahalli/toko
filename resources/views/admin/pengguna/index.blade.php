@extends('layouts.admin')

@section('judul', 'Pengguna')

@section('konten')
    <div class="mb-4">
        <h1 class="font-display text-xl text-slate-900">Pengguna</h1>
        <p class="text-xs text-slate-500">
            Peran, status aktif, dan toko yang diawasi setiap akun.
            @can('peran.kelola')
                Akun dengan peran {{ implode(', ', $peranGlobal) }} sudah boleh semua toko.
            @endcan
        </p>
    </div>

    @can('pengguna.kelola')
        <details class="card mb-4">
            <summary class="cursor-pointer select-none px-4 py-3 text-sm font-medium text-slate-700">
                Tambah akun
            </summary>

            <form method="POST" action="{{ route('admin.pengguna.store') }}"
                  class="grid gap-3 border-t border-slate-100 p-4">
                @csrf

                <fieldset class="grid gap-1.5">
                    <legend class="mb-1 text-xs font-medium text-slate-600">Sumber akun</legend>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="radio" name="mode" value="karyawan" checked
                               class="text-brand-600 focus:ring-brand-500">
                        Dari data karyawan (email karyawan)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="radio" name="mode" value="bebas"
                               class="text-brand-600 focus:ring-brand-500">
                        Akun baru tanpa data karyawan
                    </label>
                </fieldset>

                <label data-mode="karyawan" class="grid gap-1">
                    <span class="text-xs font-medium text-slate-600">Karyawan</span>
                    <select name="nip"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        <option value="">Pilih karyawan…</option>
                        @foreach ($tanpaAkun as $k)
                            <option value="{{ $k->nip }}">{{ $k->nip }} — {{ $k->nama }} ({{ $k->shop->nama }})</option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-slate-400">
                        Hanya karyawan aktif yang sudah punya email dan belum punya akun.
                    </span>
                </label>

                <div data-mode="bebas" class="hidden grid gap-3 sm:grid-cols-2">
                    <label class="grid gap-1">
                        <span class="text-xs font-medium text-slate-600">Nama</span>
                        <input type="text" name="nama"
                               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    </label>
                    <label class="grid gap-1">
                        <span class="text-xs font-medium text-slate-600">Email</span>
                        <input type="email" name="email"
                               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    </label>
                </div>

                <label class="grid gap-1">
                    <span class="text-xs font-medium text-slate-600">Peran</span>
                    <select name="peran[]" multiple size="3"
                            class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-brand-500">
                        @foreach ($peran as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-slate-400">
                        Tahan Ctrl/Cmd untuk memilih lebih dari satu. Username dibuat otomatis dari email.
                    </span>
                </label>

                <div>
                    <button class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                        Buat akun &amp; kirim password
                    </button>
                </div>
            </form>

            <script>
                (function () {
                    const form = document.currentScript.closest('details');
                    const radios = form.querySelectorAll('input[name="mode"]');

                    function toggle() {
                        const mode = form.querySelector('input[name="mode"]:checked').value;
                        form.querySelectorAll('[data-mode]').forEach(function (el) {
                            el.classList.toggle('hidden', el.dataset.mode !== mode);
                        });
                    }

                    radios.forEach(function (r) { r.addEventListener('change', toggle); });
                    toggle();
                })();
            </script>
        </details>
    @endcan

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto]">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email"
               class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
        <select name="peran" class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            <option value="">Semua peran</option>
            @foreach ($peran as $r)
                <option value="{{ $r->name }}" @selected(request('peran') === $r->name)>{{ $r->name }}</option>
            @endforeach
        </select>
        <button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
            Filter
        </button>
    </form>

    @if ($pengguna->isEmpty())
        <p class="card px-4 py-8 text-center text-sm text-slate-500">
            Tidak ada akun yang cocok.
        </p>
    @else
        <div class="space-y-3">
            @foreach ($pengguna as $u)
                @php
                    // Peran global mengabaikan tabel user_shop, jadi isi
                    // penugasan tidak ditampilkan sebagai sesuatu yang aktif.
                    $sudahGlobal = $u->roles->pluck('name')->intersect($peranGlobal)->isNotEmpty();
                    $idTokoTerpilih = $u->shops->pluck('id')->all();
                @endphp

                <form method="POST" class="card p-4"
                      action="{{ route('admin.pengguna.update', $u) }}">
                    @csrf
                    @method('PUT')

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900">
                                {{ $u->name }}
                                @if ($u->employee)
                                    <span class="text-xs font-normal text-slate-500">
                                        ({{ $u->employee->nama }} · {{ $u->employee->shop->nama }})
                                    </span>
                                @endif
                            </p>
                            <p class="truncate text-xs text-slate-500">{{ $u->email }}</p>
                            @if ($u->username)
                                <p class="truncate text-[11px] text-slate-400">
                                    username: {{ $u->username }}
                                    @if ($u->bebas_perangkat)
                                        · <span class="text-brand-700">bebas perangkat</span>
                                    @endif
                                </p>
                            @endif
                            @if ($u->active_session_id)
                                <p class="mt-1 text-[11px] text-amber-600">Sedang dipakai di perangkat lain</p>
                            @endif
                        </div>

                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium
                                     {{ $u->aktif ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $u->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-medium text-slate-600">Peran</span>
                            <select name="peran[]" multiple size="4"
                                    class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-brand-500">
                                @foreach ($peran as $r)
                                    <option value="{{ $r->name }}"
                                            @selected(in_array($r->name, $u->roles->pluck('name')->all(), true))>
                                        {{ $r->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="mt-1 block text-[11px] text-slate-400">
                                Tahan Ctrl/Cmd untuk memilih lebih dari satu.
                            </span>
                        </label>

                        <div>
                            <span class="text-xs font-medium text-slate-600">Toko yang diawasi</span>

                            @if ($sudahGlobal)
                                <p class="mt-1 rounded-lg bg-brand-50 px-2 py-1.5 text-[11px] text-brand-700">
                                    Peran {{ $u->roles->pluck('name')->intersect($peranGlobal)->join(', ') }}
                                    sudah memberi akses ke semua toko, jadi penugasan di bawah diabaikan.
                                </p>
                            @elseif ($tanpaPenugasan($u))
                                <p class="mt-1 rounded-lg bg-amber-50 px-2 py-1.5 text-[11px] text-amber-700">
                                    Belum ditugaskan ke toko mana pun, jadi akun ini tidak melihat data apa pun.
                                </p>
                            @endif

                            <div class="mt-1 max-h-28 space-y-1 overflow-y-auto rounded-lg border border-slate-200 px-2 py-1.5">
                                @forelse ($toko as $t)
                                    <label class="flex items-center gap-2 text-sm text-slate-600">
                                        <input type="checkbox" name="toko[]" value="{{ $t->id }}"
                                               @checked(in_array($t->id, $idTokoTerpilih, true))
                                               @disabled($sudahGlobal)
                                               class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                                        {{ $t->nama }}
                                        <span class="text-[11px] text-slate-400">{{ $t->kode }}</span>
                                    </label>
                                @empty
                                    <p class="text-[11px] text-slate-400">Tidak ada toko yang boleh Anda assign.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="hidden" name="aktif" value="0">
                                <input type="checkbox" name="aktif" value="1" @checked($u->aktif)
                                       class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                                Akun aktif
                            </label>

                            @can('pengguna.kelola')
                                <label class="flex items-center gap-2 text-sm text-slate-600">
                                    <input type="hidden" name="bebas_perangkat" value="0">
                                    <input type="checkbox" name="bebas_perangkat" value="1" @checked($u->bebas_perangkat)
                                           class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                                    Bebas perangkat
                                </label>
                            @endcan
                        </div>

                        @can('pengguna.kelola')
                            <button class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                                Simpan
                            </button>
                        @endcan
                    </div>

                    @if ($u->devices->isNotEmpty())
                        <div class="mt-3 space-y-1.5 border-t border-slate-100 pt-3">
                            <p class="text-xs font-medium text-slate-600">Perangkat yang tercatat</p>

                            @foreach ($u->devices as $perangkat)
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-[12px] text-slate-600">
                                            {{ $perangkat->label ?: 'Perangkat' }}
                                        </p>
                                        <p class="truncate text-[11px] text-slate-400">
                                            {{ $perangkat->device_token }} ·
                                            {{ $perangkat->last_seen_at?->diffForHumans() ?? 'belum pernah dicoba' }}
                                        </p>
                                    </div>

                                    <span @class([
                                        'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium',
                                        'bg-brand-50 text-brand-700' => $perangkat->status->value === 'approved',
                                        'bg-amber-50 text-amber-700' => $perangkat->status->value === 'pending',
                                        'bg-rose-50 text-rose-700' => $perangkat->status->value === 'rejected',
                                    ])>
                                        {{ $perangkat->status->label() }}
                                    </span>

                                    @can('perangkat.kelola')
                                        <span class="flex shrink-0 items-center gap-1.5">
                                            @if ($perangkat->status->value !== 'approved')
                                                <form method="POST"
                                                      action="{{ route('admin.pengguna.perangkat-setujui', [$u, $perangkat]) }}">
                                                    @csrf
                                                    <button class="text-[11px] font-medium text-brand-700 hover:text-brand-800">
                                                        Setujui
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($perangkat->status->value !== 'rejected')
                                                <form method="POST"
                                                      action="{{ route('admin.pengguna.perangkat-tolak', [$u, $perangkat]) }}">
                                                    @csrf
                                                    <button class="text-[11px] font-medium text-rose-600 hover:text-rose-700">
                                                        Tolak
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST"
                                                  action="{{ route('admin.pengguna.perangkat-hapus', [$u, $perangkat]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-[11px] font-medium text-slate-400 hover:text-slate-600">
                                                    Hapus
                                                </button>
                                            </form>
                                        </span>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @endif
                </form>
            @endforeach
        </div>

        @if ($pengguna->hasPages())
            <div class="mt-4">{{ $pengguna->links() }}</div>
        @endif
    @endif
@endsection