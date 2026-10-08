@extends('layouts.app')

@section('judul', 'Profil')

@section('konten')
    <div class="mb-4">
        <h1 class="font-display text-xl text-slate-900">Profil</h1>
        <p class="text-xs text-slate-500">Username, password, dan perangkat yang diizinkan untuk akunmu.</p>
    </div>

    <div class="space-y-4">
        <div class="card p-4">
            <p class="font-medium text-slate-900">{{ $akun->name }}</p>
            <p class="text-xs text-slate-500">{{ $akun->email }}</p>
            <p class="mt-1 text-xs text-slate-500">Username: <strong>{{ $akun->username ?: '—' }}</strong></p>
            <p class="mt-1 text-[11px] text-slate-400">
                Peran: {{ $akun->getRoleNames()->join(', ') ?: '—' }}
            </p>
        </div>

        <form method="POST" action="{{ route('profil.username') }}" class="card p-4">
            @csrf
            <p class="mb-2 text-[13px] font-medium text-slate-600">Ganti username</p>

            <div class="flex gap-2">
                <input type="text" name="username" value="{{ old('username', $akun->username) }}"
                       minlength="3" maxlength="50" required autocomplete="off"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                              focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                <button class="shrink-0 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                    Simpan
                </button>
            </div>
            <p class="mt-1.5 text-[11px] text-slate-400">Login memakai username atau email.</p>
        </form>

        <form method="POST" action="{{ route('profil.password') }}" class="card p-4">
            @csrf
            <p class="mb-2 text-[13px] font-medium text-slate-600">Ganti password</p>

            <div class="space-y-2">
                <input type="password" name="password_lama" placeholder="Password lama" required autocomplete="current-password"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                              focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                <input type="password" name="password" placeholder="Password baru (min. 8 karakter)" required autocomplete="new-password"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                              focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
                <input type="password" name="password_confirmation" placeholder="Ulangi password baru" required autocomplete="new-password"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-sm outline-none transition
                              focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100">
            </div>

            <button class="mt-3 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                Ganti password
            </button>
        </form>

        <div class="card p-4">
            <p class="mb-2 text-[13px] font-medium text-slate-600">Perangkat yang diizinkan</p>

            @if ($akun->devices->isEmpty())
                <p class="text-[13px] text-slate-500">
                    Belum ada perangkat tercatat. Perangkat baru dibuat otomatis saat kamu mencoba login,
                    lalu menunggu persetujuan pemilik atau kepala toko.
                </p>
            @else
                <div class="space-y-2">
                    @foreach ($akun->devices as $perangkat)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-[13px] text-slate-700">
                                    {{ $perangkat->label ?: 'Perangkat' }}
                                </p>
                                <p class="truncate text-[11px] text-slate-400">
                                    {{ $perangkat->device_token }} ·
                                    {{ $perangkat->last_seen_at?->diffForHumans() ?? 'belum pernah dipakai' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                    'bg-brand-50 text-brand-700' => $perangkat->status->value === 'approved',
                                    'bg-amber-50 text-amber-700' => $perangkat->status->value === 'pending',
                                    'bg-rose-50 text-rose-700' => $perangkat->status->value === 'rejected',
                                ])>
                                    {{ $perangkat->status->label() }}
                                </span>

                                @if ($perangkat->status->value !== 'rejected')
                                    <form method="POST"
                                          action="{{ route('profil.perangkat-cabut', $perangkat) }}">
                                        @csrf
                                        <button class="text-[11px] font-medium text-rose-600 hover:text-rose-700">
                                            Cabut
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection