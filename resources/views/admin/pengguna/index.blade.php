@extends('layouts.admin')

@section('judul', 'Pengguna')

@section('konten')
    <x-page-header class="mb-4" judul="Pengguna" sub="Akun, peran, dan perangkat yang terhubung.">
        <x-slot:ikon>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 7.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0ZM5 19.5a4.5 4.5 0 0 1 9 0M17 10.5a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM15.5 19.5a3.5 3.5 0 0 0-1-2.45"/>
            </svg>
        </x-slot:ikon>
    </x-page-header>

    @can('pengguna.kelola')
        <details class="group card mb-4 overflow-hidden">
            <summary class="flex cursor-pointer select-none items-center justify-between gap-3 px-4 py-3.5 hover:bg-slate-50">
                <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10 5a1 1 0 0 1 1 1v3h3a1 1 0 1 1 0 2h-3v3a1 1 0 1 1-2 0v-3H6a1 1 0 1 1 0-2h3V6a1 1 0 0 1 1-1Z"/>
                        </svg>
                    </span>
                    Tambah akun
                </span>
                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180"
                     viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.41 10 12.17l4.77-4.76a1 1 0 1 1 1.42 1.42l-5.48 5.47a1 1 0 0 1-1.42 0L3.8 8.83a1 1 0 0 1 1.42-1.42Z" clip-rule="evenodd"/>
                </svg>
            </summary>

            <form method="POST" action="{{ route('admin.pengguna.store') }}"
                  class="grid gap-4 border-t border-slate-100 p-4">
                @csrf

                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50/60">
                        <input type="radio" name="mode" value="karyawan" checked
                               class="text-brand-600 focus:ring-brand-500">
                        Dari data karyawan
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50/60">
                        <input type="radio" name="mode" value="bebas"
                               class="text-brand-600 focus:ring-brand-500">
                        Akun baru tanpa karyawan
                    </label>
                </div>

                <label data-mode="karyawan" class="grid gap-1">
                    <span class="text-xs font-medium text-slate-600">Karyawan</span>
                    <select name="nip"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        <option value="">Pilih karyawan…</option>
                        @foreach ($tanpaAkun as $k)
                            <option value="{{ $k->nip }}">{{ $k->nip }} — {{ $k->nama }} ({{ $k->shop->nama }})</option>
                        @endforeach
                    </select>
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

                <div class="grid gap-1.5">
                    <span class="text-xs font-medium text-slate-600">Peran</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($peran as $r)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="peran[]" value="{{ $r->name }}" class="peer sr-only">
                                <span class="inline-block rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300">
                                    {{ $r->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <button class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        Buat akun
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

                    radios.forEach(function (radio) {
                        radio.addEventListener('change', toggle);
                    });

                    toggle();
                })();
            </script>
        </details>
    @endcan

    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
        <input type="text" name="q" value="{{ request('q') }}"
               placeholder="Cari nama, email, atau username…"
               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500 sm:flex-1">
        <div class="flex gap-2">
            <select name="peran"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500 sm:w-auto">
                <option value="">Semua peran</option>
                @foreach ($peran as $r)
                    <option value="{{ $r->name }}" @selected(request('peran') === $r->name)>{{ $r->name }}</option>
                @endforeach
            </select>
            <button class="shrink-0 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                Filter
            </button>
        </div>
    </form>

    @if ($pengguna->isEmpty())
        <p class="card px-4 py-8 text-center text-sm text-slate-500">
            Tidak ada akun yang cocok.
        </p>
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($pengguna as $u)
                @php
                    // Peran global mengabaikan tabel user_shop, jadi isi
                    // penugasan tidak ditampilkan sebagai sesuatu yang aktif.
                    $sudahGlobal = $u->roles->pluck('name')->intersect($peranGlobal)->isNotEmpty();
                    $idTokoTerpilih = $u->shops->pluck('id')->all();
                    // Toko dari data karyawan selalu dianggap tercentang dan
                    // tidak bisa dilepas dari sini (sumbernya data karyawan).
                    $tokoKaryawanId = $u->employee?->shop_id;
                    $perangkatMenunggu = $u->devices->where('status', \App\Enums\StatusPerangkat::Pending)->count();
                    $foto = $u->employee?->fotoUrl();
                @endphp

                <div class="card overflow-hidden">
                    {{-- Wajah kartu pengenal --}}
                    <div class="border-t-4 border-brand-600 p-4">
                        <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">
                            {{ $u->employee?->shop->nama ?? 'Akun pengguna' }}
                        </p>

                        <div class="mt-3 flex items-center gap-3">
                            <span class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                                @if ($foto)
                                    <img src="{{ $foto }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-300">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/>
                                        </svg>
                                    </span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-display text-[15px] text-slate-900">{{ $u->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $u->email }}</p>
                                @if ($u->employee)
                                    <p class="truncate text-[11px] text-slate-400">
                                        {{ $u->employee->nama }} · {{ $u->employee->position->nama ?? 'Tanpa jabatan' }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                'bg-brand-50 text-brand-700' => $u->aktif,
                                'bg-slate-100 text-slate-500' => ! $u->aktif,
                            ])>
                                {{ $u->aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>

                            @foreach ($u->roles as $peranAkun)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                    {{ $peranAkun->name }}
                                </span>
                            @endforeach

                            @if ($u->bebas_perangkat)
                                <span class="rounded-full bg-merah-50 px-2 py-0.5 text-[11px] font-medium text-merah-700">
                                    Bebas perangkat
                                </span>
                            @endif
                        </div>

                        @if ($u->active_session_id)
                            <p class="mt-2 text-[11px] text-merah-600">Sedang dipakai di perangkat lain.</p>
                        @endif

                        <div class="mt-3 flex items-center justify-between gap-2 border-t border-dashed border-slate-200 pt-2.5 text-[11px] text-slate-400">
                            <span class="truncate">{{ $u->username ?: $u->email }}</span>
                            @if ($u->employee?->nip)
                                <span class="shrink-0">NIP {{ $u->employee->nip }}</span>
                            @endif
                        </div>
                    </div>

                    <details class="group border-t border-slate-100">
                        <summary class="flex cursor-pointer select-none items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Kelola akun
                            </span>
                            <span class="flex items-center gap-2">
                                @if ($perangkatMenunggu > 0)
                                    <span class="rounded-full bg-merah-50 px-2 py-0.5 text-[11px] font-medium text-merah-700">
                                        {{ $perangkatMenunggu }} perangkat menunggu
                                    </span>
                                @endif
                                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180"
                                     viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.41 10 12.17l4.77-4.76a1 1 0 1 1 1.42 1.42l-5.48 5.47a1 1 0 0 1-1.42 0L3.8 8.83a1 1 0 0 1 1.42-1.42Z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </summary>

                        <form id="update-{{ $u->id }}" method="POST" action="{{ route('admin.pengguna.update', $u) }}"
                              class="grid gap-4 border-t border-slate-100 p-4">
                            @csrf
                            @method('PUT')

                            <div class="grid gap-1.5">
                                <span class="text-xs font-medium text-slate-600">Peran</span>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($peran as $r)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="peran[]" value="{{ $r->name }}" class="peer sr-only"
                                                   @checked(in_array($r->name, $u->roles->pluck('name')->all(), true))>
                                            <span class="inline-block rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300">
                                                {{ $r->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid gap-1.5">
                                <span class="text-xs font-medium text-slate-600">Toko yang diawasi</span>

                                @if ($sudahGlobal)
                                    <span class="sr-only">Peran ini sudah memberi akses ke semua toko.</span>
                                @elseif ($tokoKaryawanId)
                                    <span class="sr-only">Salah satu toko diambil dari data karyawan.</span>
                                @elseif (count($idTokoTerpilih) === 0)
                                    <span class="sr-only">Belum ditugaskan ke toko mana pun.</span>
                                @endif

                                <div class="grid gap-1.5 rounded-lg border border-slate-200 p-2 sm:grid-cols-2">
                                    @forelse ($toko as $t)
                                        <label class="flex items-center gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="toko[]" value="{{ $t->id }}"
                                                   @checked(in_array($t->id, $idTokoTerpilih, true))
                                                   @disabled($sudahGlobal)
                                                   class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                                            <span class="truncate">
                                                {{ $t->nama }}
                                                @if ($t->id === $tokoKaryawanId)
                                                    <span class="sr-only">dari data karyawan</span>
                                                @endif
                                            </span>
                                        </label>
                                    @empty
                                        <p class="text-[11px] text-slate-400">Tidak ada toko yang boleh Anda assign.</p>
                                    @endforelse
                                </div>
                            </div>

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
                        </form>

                        @can('pengguna.kelola')
<form id="reset-{{ $u->id }}" method="POST"
                          action="{{ route('admin.pengguna.reset-password', $u) }}"
                          data-konfirmasi="Password baru akan dikirim ke {{ $u->email }}. Password lama dan sesi yang sedang aktif akan berhenti berlaku."
                          data-konfirmasi-judul="Kirim password baru?"
                          data-konfirmasi-tombol="Kirim">
                            @csrf
                        </form>

                        @if ($u->id !== auth()->id())
                            <form id="hapus-{{ $u->id }}" method="POST"
                                  action="{{ route('admin.pengguna.hapus', $u) }}"
                                  data-konfirmasi="Akun {{ $u->name }} akan dinonaktifkan, semua sesi diputus, dan data karyawan dilepas dari akun ini. Riwayat absensi tetap tersimpan."
                                  data-konfirmasi-judul="Hapus akun?"
                                  data-konfirmasi-tombol="Hapus akun"
                                  data-konfirmasi-bahaya>
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif

                        <div class="flex flex-wrap items-center justify-end gap-2 px-4 pb-4">
                            @if ($u->id !== auth()->id())
                                <button type="submit" form="hapus-{{ $u->id }}"
                                        class="rounded-lg border border-merah-200 px-3 py-1.5 text-sm font-medium text-merah-600 hover:bg-merah-50">
                                    Hapus akun
                                </button>
                            @endif
                            <button type="submit" form="reset-{{ $u->id }}"
                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                                Reset password
                            </button>
                            <button type="submit" form="update-{{ $u->id }}"
                                    class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700">
                                Simpan
                            </button>
                        </div>
                        @endcan

                        @if ($u->devices->isNotEmpty())
                            <div class="space-y-1.5 border-t border-slate-100 p-4">
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
                                            'bg-merah-50 text-merah-700' => in_array($perangkat->status->value, ['pending', 'rejected'], true),
                                        ])>
                                            {{ $perangkat->status->label() }}
                                        </span>

                                        @can('perangkat.kelola')
                                            <span class="flex shrink-0 items-center gap-1.5">
                                                <form method="POST"
                                                      action="{{ route('admin.pengguna.perangkat-hapus', [$u, $perangkat]) }}"
                                                      data-konfirmasi="Perangkat baru nanti akan dianggap perangkat pertama."
                                                      data-konfirmasi-judul="Hapus perangkat?"
                                                      data-konfirmasi-tombol="Hapus"
                                                      data-konfirmasi-bahaya>
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
                    </details>
                </div>
            @endforeach
        </div>

        @if ($pengguna->hasPages())
            <div class="mt-4">{{ $pengguna->links() }}</div>
        @endif
    @endif
@endsection
