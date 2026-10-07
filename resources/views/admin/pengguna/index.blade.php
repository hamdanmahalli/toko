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
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="hidden" name="aktif" value="0">
                            <input type="checkbox" name="aktif" value="1" @checked($u->aktif)
                                   class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                            Akun aktif
                        </label>

                        @can('pengguna.kelola')
                            <button class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                                Simpan
                            </button>
                        @endcan
                    </div>
                </form>
            @endforeach
        </div>

        @if ($pengguna->hasPages())
            <div class="mt-4">{{ $pengguna->links() }}</div>
        @endif
    @endif
@endsection