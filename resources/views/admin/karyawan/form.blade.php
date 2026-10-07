@extends('layouts.admin')

@section('judul', $karyawan->exists ? 'Ubah Karyawan' : 'Tambah Karyawan')

@section('konten')
    <h1 class="mb-4 font-display text-xl text-slate-900">
        {{ $karyawan->exists ? 'Ubah karyawan' : 'Tambah karyawan' }}
    </h1>

    <form method="POST"
          action="{{ $karyawan->exists ? route('admin.karyawan.update', $karyawan) : route('admin.karyawan.store') }}"
          class="space-y-4">
        @csrf
        @if ($karyawan->exists)
            @method('PUT')
        @endif

        <div class="space-y-4 card p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="nama">Nama lengkap</label>
                    <input id="nama" name="nama" required value="{{ old('nama', $karyawan->nama) }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="nip">NIP</label>
                    <input id="nip" name="nip" value="{{ old('nip', $karyawan->nip) }}" placeholder="K-001"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="telepon">Telepon</label>
                    <input id="telepon" name="telepon" value="{{ old('telepon', $karyawan->telepon) }}" placeholder="08xxxxxxxxxx"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $karyawan->email) }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    @if (! $karyawan->user)
                        <p class="mt-1 text-xs text-slate-500">
                            Wajib diisi bila membuat akun login di bagian bawah, dan harus unik —
                            tidak boleh sama dengan akun lain (termasuk akun pemilik/supervisor).
                        </p>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="shop_id">Toko</label>
                    <select id="shop_id" name="shop_id" required
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        <option value="">-- pilih --</option>
                        @foreach ($toko as $t)
                            <option value="{{ $t->id }}" @selected(old('shop_id', $karyawan->shop_id) == $t->id)>{{ $t->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="position_id">Jabatan</label>
                    <select id="position_id" name="position_id"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        <option value="">-- pilih --</option>
                        @foreach ($jabatan as $j)
                            <option value="{{ $j->id }}" data-pakai-template="{{ $j->pakai_template ? '1' : '0' }}"
                                    @selected(old('position_id', $karyawan->position_id) == $j->id)>{{ $j->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tanggal_masuk">Tanggal masuk</label>
                    <input id="tanggal_masuk" name="tanggal_masuk" type="date"
                           value="{{ old('tanggal_masuk', $karyawan->tanggal_masuk?->toDateString()) }}"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="shift_template_id">Template shift</label>
                    <select id="shift_template_id" name="shift_template_id"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        <option value="">-- belum ada shift --</option>
                        @foreach ($shift as $s)
                            <option value="{{ $s->id }}"
                                    @selected((int) old('shift_template_id', $karyawan->shiftBerlaku()?->shift_template_id) === $s->id)>
                                {{ $s->nama }}{{ $s->shop_id ? ' · '.$s->shop->nama : ' · semua toko' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500" data-catatan-template>
                        @if ($pakaiTemplateDefault)
                            Wajib untuk jabatan ini. Template menentukan jam masuk dan batas telat.
                        @else
                            Jabatannya mengikuti Window Shift, jadi template di sini tidak wajib. Kalau diisi,
                            template tetap dipakai dan mengesampingkan window.
                        @endif
                    </p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="shift_mulai_berlaku">Shift berlaku mulai</label>
                    <input id="shift_mulai_berlaku" name="shift_mulai_berlaku" type="date"
                           value="{{ old('shift_mulai_berlaku', now()->toDateString()) }}"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <p class="mt-1 text-xs text-slate-500">
                        Kosongkan untuk memakai tanggal masuk karyawan.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tipe_payroll">Tipe gaji</label>
                    <select id="tipe_payroll" name="tipe_payroll" required
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        @foreach (\App\Enums\PayrollType::cases() as $tipe)
                            <option value="{{ $tipe->value }}"
                                @selected(old('tipe_payroll', $karyawan->tipe_payroll?->value) === $tipe->value)>
                                {{ $tipe->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="gaji_harian">Gaji harian (Rp)</label>
                    <input id="gaji_harian" name="gaji_harian" type="number" step="1000" min="0"
                           value="{{ old('gaji_harian', $karyawan->gaji_harian) }}"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tarif_jam">Tarif per jam (Rp)</label>
                    <input id="tarif_jam" name="tarif_jam" type="number" step="500" min="0"
                           value="{{ old('tarif_jam', $karyawan->tarif_jam) }}"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="catatan">Catatan</label>
                <input id="catatan" name="catatan" value="{{ old('catatan', $karyawan->catatan) }}"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="aktif" value="1"
                       @checked(old('aktif', $karyawan->exists ? $karyawan->aktif : true))
                       class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                Karyawan aktif
            </label>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="boleh_presensi" value="1"
                       @checked(old('boleh_presensi', $karyawan->exists ? $karyawan->boleh_presensi : true))
                       class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                Boleh absen lewat perangkat presensi
            </label>
            <p class="-mt-2 text-xs leading-relaxed text-slate-500">
                Kartunya bisa dipindai di perangkat presensi toko. Kosongkan untuk karyawan yang
                hanya boleh absen dari HP. Kolom ini tidak memengaruhi absen dari HP.
            </p>
        </div>

        @unless ($karyawan->user)
            <div class="space-y-3 card p-5">
                <h2 class="font-display text-[15px] text-slate-900">Buat akun login</h2>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input id="buat_akun" type="checkbox" name="buat_akun" value="1"
                           class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                    Buat akun supaya karyawan bisa absen sendiri
                </label>
                <p class="text-xs leading-relaxed text-slate-500">
                    Centang untuk memberi akun login. Akun memakai nama &amp; email yang diisi di bagian
                    atas plus password di bawah ini, lalu otomatis mendapat peran karyawan. Tanpa centang,
                    karyawan absen lewat perangkat presensi toko memakai kartunya, bukan dari HP.
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">Password</label>
                        <input id="password" name="password" type="password" minlength="8" autocomplete="new-password"
                               placeholder="Minimal 8 karakter"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password_confirmation">Ulangi password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password"
                               placeholder="Ketik ulang password"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    </div>
                </div>
                <p class="text-xs text-slate-500">Akun akan mendapat peran karyawan dan ikut aturan satu akun satu perangkat.</p>
            </div>
        @endunless

        <div class="flex flex-wrap gap-2">
            <button class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[.99]">
                Simpan
            </button>
            <a href="{{ route('admin.karyawan.index') }}"
               class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                Batal
            </a>
        </div>
    </form>

    @if ($karyawan->exists)
        <div class="mt-6 space-y-4">
            <div class="flex flex-wrap items-center gap-2 card p-4">
                <a href="{{ route('admin.karyawan.qr', $karyawan) }}"
                   class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                    Lihat kartu QR
                </a>

                <form method="POST" action="{{ route('admin.karyawan.rotasi-qr', $karyawan) }}">
                    @csrf
                    <button class="rounded-lg border border-amber-300 px-3 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50">
                        Ganti kartu QR
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.karyawan.destroy', $karyawan) }}"
                      onsubmit="return confirm('Nonaktifkan {{ $karyawan->nama }}?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                        Nonaktifkan
                    </button>
                </form>
            </div>

            @if ($karyawan->user)
                <details class="card p-5">
                    <summary class="cursor-pointer font-display text-[15px] text-slate-900">Ganti password karyawan</summary>
                    <form method="POST" action="{{ route('admin.karyawan.reset-password', $karyawan) }}"
                          class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="pw">Password baru</label>
                            <input id="pw" name="password" type="password" required autocomplete="new-password"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="pw2">Ulangi password</label>
                            <input id="pw2" name="password_confirmation" type="password" required autocomplete="new-password"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        </div>
                        <div class="sm:col-span-2">
                            <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                                Simpan password baru
                            </button>
                        </div>
                    </form>
                </details>
            @endif
        </div>
    @endif

    <script>
        (() => {
            const jabatan = document.getElementById('position_id');
            const catatan = document.querySelector('[data-catatan-template]');

            if (jabatan && catatan) {
                const wajib = 'Wajib untuk jabatan ini. Template menentukan jam masuk dan batas telat.';
                const bebas = 'Jabatannya mengikuti Window Shift, jadi template di sini tidak wajib. Kalau diisi, template tetap dipakai dan mengesampingkan window.';

                jabatan.addEventListener('change', () => {
                    const opsi = jabatan.selectedOptions[0];
                    const pakaiTemplate = opsi?.dataset.pakaiTemplate === '1';

                    catatan.textContent = pakaiTemplate ? wajib : bebas;
                });
            }

            const buatAkun = document.getElementById('buat_akun');
            const email = document.getElementById('email');
            const pw = document.getElementById('password');
            const pw2 = document.getElementById('password_confirmation');

            if (buatAkun && email && pw && pw2) {
                // Saat centang "buat akun" dinyalakan, email dan password wajib
                // lewat validasi peramban dulu, supaya tidak kena pesan error
                // server berbahasa Inggris yang membingungkan.
                const atur = () => {
                    const perlu = buatAkun.checked;

                    email.toggleAttribute('required', perlu);
                    pw.toggleAttribute('required', perlu);
                    pw.toggleAttribute('minlength', perlu);
                    pw2.toggleAttribute('required', perlu);
                    pw2.toggleAttribute('minlength', perlu);
                };

                buatAkun.addEventListener('change', atur);
            }
        })();
    </script>
@endsection
