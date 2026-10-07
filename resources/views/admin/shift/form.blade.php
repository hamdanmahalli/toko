@use('App\Enums\FleksibelTipe')
@use('App\Enums\ShiftTipe')

@extends('layouts.admin')

@section('judul', $template->exists ? 'Ubah Template Shift' : 'Tambah Template Shift')

@section('konten')
    <h1 class="mb-1 font-display text-xl text-slate-900">
        {{ $template->exists ? 'Ubah template shift' : 'Tambah template shift' }}
    </h1>
    <p class="mb-4 text-xs text-slate-500">
        Isi jam kerjanya sesuai tipe shift. Tipe tetap memakai jadwal per hari, shift fleksibel memakai jam datang dan pulang,
        sedangkan shift interval memakai daftar sesi jam kerja.
    </p>

    <form method="POST"
          action="{{ $template->exists ? route('admin.shift.update', $template) : route('admin.shift.store') }}"
          class="space-y-4">
        @csrf
        @if ($template->exists)
            @method('PUT')
        @endif

        {{-- error validasi slot per hari --}}
        @error('slot')
            <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
        @enderror

        <div class="space-y-4 card p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="nama">Nama template</label>
                    <input id="nama" name="nama" required value="{{ old('nama', $template->nama) }}"
                           placeholder="Shift Pagi"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    @error('nama')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="scope">Berlaku untuk</label>
                    <select id="scope" name="scope"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        @foreach (\App\Enums\ShiftScope::cases() as $s)
                            <option value="{{ $s->value }}" @selected(old('scope', $template->scope?->value ?? 'toko') === $s->value)>
                                {{ $s->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('scope')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="kode">Kode</label>
                    <input id="kode" name="kode" maxlength="20" value="{{ old('kode', $template->kode) }}"
                           placeholder="PAGI"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <p class="mt-1 text-xs text-slate-500">Kode pendek untuk laporan, otomatis jadi huruf besar.</p>
                    @error('kode')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="durasi_maks_menit">Durasi maksimum (menit)</label>
                    <input id="durasi_maks_menit" name="durasi_maks_menit" type="number" min="1" max="1440"
                           value="{{ old('durasi_maks_menit', $template->durasi_maks_menit) }}"
                           placeholder="480"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <p class="mt-1 text-xs text-slate-500">
                        Berlaku untuk semua hari. Bisa ditimpa per hari di tabel bawah. Kosongkan bila tidak ada batas.
                    </p>
                    @error('durasi_maks_menit')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="tipe">Tipe shift</label>
                    <select id="tipe" name="tipe"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        @foreach (ShiftTipe::cases() as $t)
                            <option value="{{ $t->value }}"
                                @selected(old('tipe', $template->tipe()?->value ?? ShiftTipe::Tetap->value) === $t->value)>
                                {{ $t->label() }} — {{ $t->keterangan() }}
                            </option>
                        @endforeach
                    </select>
                    @error('tipe')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div id="baris-fleksibel" class="hidden">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="fleksibel_tipe">Jenis fleksibel</label>
                    <select id="fleksibel_tipe" name="fleksibel_tipe"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                        <option value="">-- pilih --</option>
                        @foreach (FleksibelTipe::cases() as $f)
                            <option value="{{ $f->value }}" @selected(old('fleksibel_tipe', $template->fleksibel_tipe?->value) === $f->value)>
                                {{ $f->label() }} — {{ $f->keterangan() }}
                            </option>
                        @endforeach
                    </select>
                    @error('fleksibel_tipe')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div id="baris-durasi-kerja" class="hidden">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="durasi_kerja_menit">Durasi kerja (menit)</label>
                    <input id="durasi_kerja_menit" name="durasi_kerja_menit" type="number" min="1" max="1440"
                           value="{{ old('durasi_kerja_menit', $template->durasi_kerja_menit) }}" placeholder="360"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <p class="mt-1 text-xs text-slate-500">Jam pulang dihitung dari jam datang ditambah durasi ini.</p>
                    @error('durasi_kerja_menit')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700" for="jam_cut_off">Jam cut-off absen masuk</label>
                    <input id="jam_cut_off" name="jam_cut_off" type="time" value="{{ old('jam_cut_off', $template->jam_cut_off?->format('H:i')) }}"
                           class="tabular w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <p class="mt-1 text-xs text-slate-500">
                        Setelah jam ini scan masuk ditolak. Kosongkan bila tidak ada batas jam scan.
                    </p>
                    @error('jam_cut_off')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="shop_id">Toko</label>
                <select id="shop_id" name="shop_id"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
                    <option value="">Semua toko</option>
                    @foreach ($toko as $t)
                        <option value="{{ $t->id }}" @selected((int) old('shop_id', $template->shop_id) === $t->id)>{{ $t->nama }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Wajib dipilih kalau cakupan di atas adalah "Per Toko".</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="keterangan">Keterangan</label>
                <input id="keterangan" name="keterangan" value="{{ old('keterangan', $template->keterangan) }}"
                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-500">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="aktif" value="1"
                       @checked(old('aktif', $template->exists ? $template->aktif : true))
                       class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                Template aktif
            </label>
        </div>

        {{-- Error validasi sesi interval --}}
        @error('interval')
            <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
        @enderror
        @error('interval.*.nama')
            <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
        @enderror
        @error('interval.*.mulai')
            <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
        @enderror
        @error('interval.*.selesai')
            <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
        @enderror
        @error('interval.*.durasi_min_menit')
            <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
        @enderror

        <div id="panel-slot" class="overflow-x-auto card p-5">
            <table class="w-full min-w-[54rem] text-left text-sm">
                <thead class="border-b border-slate-100 text-[11px] font-medium text-slate-400">
                    <tr>
                        <th class="w-10 px-2 py-2"></th>
                        <th class="px-2 py-2 font-medium">Hari</th>
                        <th class="px-2 py-2 font-medium">Masuk</th>
                        <th class="px-2 py-2 font-medium">Batas telat</th>
                        <th class="px-2 py-2 font-medium">Istirahat mulai</th>
                        <th class="px-2 py-2 font-medium">Istirahat selesai</th>
                        <th class="px-2 py-2 font-medium">Pulang</th>
                        <th class="px-2 py-2 font-medium">Durasi maks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($hari as $index => $label)
                        @php
                            $s = $template->slots->firstWhere('hari', $index);
                            $key = old("slot.$index", $s ? [
                                'jam_masuk' => $s->jam_masuk?->format('H:i'),
                                'batas_telat' => $s->batas_telat?->format('H:i'),
                                'jam_pulang' => $s->jam_pulang?->format('H:i'),
                                'durasi_maks_menit' => $s->durasi_maks_menit,
                                'mulai_istirahat' => $s->mulai_istirahat?->format('H:i'),
                                'selesai_istirahat' => $s->selesai_istirahat?->format('H:i'),
                            ] : []);
                            $aktifHari = (bool) old("slot.$index.aktif", $s !== null);
                        @endphp

                        <tr class="{{ $aktifHari ? '' : 'opacity-60' }}">
                            <td class="px-2 py-2.5 text-center">
                                <input type="checkbox" name="slot[{{ $index }}][aktif]" value="1"
                                       @checked($aktifHari)
                                       class="rounded border-slate-200 text-brand-600 focus:ring-brand-500">
                            </td>
                            <td class="px-2 py-2.5 font-medium text-slate-700">{{ $label }}</td>
                            <td class="px-2 py-2.5">
                                <input type="time" name="slot[{{ $index }}][jam_masuk]" value="{{ $key['jam_masuk'] ?? '' }}"
                                       class="tabular w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="time" name="slot[{{ $index }}][batas_telat]" value="{{ $key['batas_telat'] ?? '' }}"
                                       class="tabular w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="time" name="slot[{{ $index }}][mulai_istirahat]" value="{{ $key['mulai_istirahat'] ?? '' }}"
                                       class="tabular w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="time" name="slot[{{ $index }}][selesai_istirahat]" value="{{ $key['selesai_istirahat'] ?? '' }}"
                                       class="tabular w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="time" name="slot[{{ $index }}][jam_pulang]" value="{{ $key['jam_pulang'] ?? '' }}"
                                       class="tabular w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-2.5">
                                <input type="number" name="slot[{{ $index }}][durasi_maks_menit]" min="1" max="1440"
                                       value="{{ $key['durasi_maks_menit'] ?? '' }}" placeholder="—"
                                       class="tabular w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
                <button type="button" id="isi-semua"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                    Centang hari kerja (Senin–Jumat)
                </button>
                <span>Istirahat boleh dikosongkan bila tidak ada jam istirahat.</span>
                <span>Durasi maks kosong berarti ikut nilai template di atas.</span>
                <span id="petunjuk-lintas-malam" class="hidden font-medium text-amber-700">
                    Tipe ini boleh melewati tengah malam: jam pulang boleh lebih awal dari jam masuk.
                </span>
            </div>
        </div>

        {{-- Sesi interval menggantikan slot harian: satu template bisa punya
             beberapa sesi scan masuk/pulang dalam sehari. --}}
        <div id="panel-interval" class="hidden space-y-3 card p-5">
            <div>
                <h2 class="font-display text-[15px] text-slate-900">Sesi jam kerja</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Karyawan scan masuk dan pulang sekali per sesi, berurutan dari atas.
                    Sesi boleh melewati tengah malam bila jam selesai lebih awal dari jam mulai.
                </p>
            </div>

            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="border-b border-slate-100 text-[11px] font-medium text-slate-400">
                    <tr>
                        <th class="w-10 px-2 py-2"></th>
                        <th class="px-2 py-2 font-medium">Nama sesi</th>
                        <th class="px-2 py-2 font-medium">Mulai</th>
                        <th class="px-2 py-2 font-medium">Selesai</th>
                        <th class="px-2 py-2 font-medium">Durasi minimum (menit)</th>
                        <th class="w-10 px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody id="daftar-interval" class="divide-y divide-slate-100">
                    @php
                        $sesiLama = old(
                            'interval',
                            $template->intervalsAktif()->map(fn ($s) => [
                                'nama' => $s->nama,
                                'mulai' => $s->mulai->format('H:i'),
                                'selesai' => $s->selesai->format('H:i'),
                                'durasi_min_menit' => $s->durasi_min_menit,
                            ])->all(),
                        );
                        $sesiLama = $sesiLama ?: [['nama' => '', 'mulai' => '', 'selesai' => '', 'durasi_min_menit' => '']];
                    @endphp

                    @foreach ($sesiLama as $i => $sesi)
                        <tr>
                            <td class="px-2 py-2.5 text-center text-xs tabular text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-2 py-1.5">
                                <input name="interval[{{ $i }}][nama]" value="{{ $sesi['nama'] ?? '' }}"
                                       placeholder="Shift Pagi"
                                       class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-1.5">
                                <input type="time" name="interval[{{ $i }}][mulai]" value="{{ $sesi['mulai'] ?? '' }}"
                                       class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-1.5">
                                <input type="time" name="interval[{{ $i }}][selesai]" value="{{ $sesi['selesai'] ?? '' }}"
                                       class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-1.5">
                                <input type="number" min="1" max="1440" name="interval[{{ $i }}][durasi_min_menit]"
                                       value="{{ $sesi['durasi_min_menit'] ?? '' }}" placeholder="240"
                                       class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none focus:border-brand-500">
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <button type="button" class="hapus-sesi text-xs font-medium text-rose-600 hover:text-rose-700">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="tambah-sesi"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                    Tambah sesi
                </button>
                <span class="text-xs text-slate-500">Durasi minimum kosong berarti dihitung dari jam mulai dan selesai.</span>
            </div>
        </div>

        <div class="flex gap-2">
            <button class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:scale-[.99]">
                Simpan
            </button>
            <a href="{{ route('admin.shift.index') }}"
               class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:border-brand-400">
                Batal
            </a>
        </div>
    </form>

    <script>
        document.getElementById('isi-semua').addEventListener('click', () => {
            document.querySelectorAll('input[type="checkbox"][name$="[aktif]"]').forEach((box) => {
                box.checked = box.name.includes('[1]') || box.name.includes('[2]')
                    || box.name.includes('[3]') || box.name.includes('[4]') || box.name.includes('[5]');
            });
        });

        // Kolom jenis fleksibel dan durasi kerja hanya relevan untuk tipe yang
        // memakainya. Disembunyikan supaya form tidak terasa meminta isian yang
        // tidak akan dipakai.
        (function () {
            const tipe = document.getElementById('tipe');
            const fleksibel = document.getElementById('baris-fleksibel');
            const durasiKerja = document.getElementById('baris-durasi-kerja');
            const jenis = document.getElementById('fleksibel_tipe');
            const lintasMalam = document.getElementById('petunjuk-lintas-malam');
            const panelSlot = document.getElementById('panel-slot');
            const panelInterval = document.getElementById('panel-interval');

            const terapkan = () => {
                const nilaiTipe = tipe.value;
                const nilaiJenis = jenis.value;

                fleksibel.classList.toggle('hidden', nilaiTipe !== 'fleksibel');
                durasiKerja.classList.toggle('hidden', nilaiJenis !== 'durasi_tetap');
                lintasMalam.classList.toggle('hidden', nilaiTipe === 'tetap');

                // Slot harian dan sesi interval saling menggantikan, bukan
                // ditumpuk: tipe interval tidak punya jam masuk/pulang harian.
                panelSlot.classList.toggle('hidden', nilaiTipe === 'interval');
                panelInterval.classList.toggle('hidden', nilaiTipe !== 'interval');
            };

            tipe.addEventListener('change', terapkan);
            jenis.addEventListener('change', terapkan);
            terapkan();

            // Baris sesi ditambaht dengan indeks berurutan supaya nama field-nya
            // tetap berbentuk interval[0], interval[1], dan seterusnya.
            const daftar = document.getElementById('daftar-interval');

            const barisSesi = (nama, mulai, selesai, durasi) => `
                <td class="px-2 py-2.5 text-center text-xs tabular text-slate-400">${daftar.children.length + 1}</td>
                <td class="px-2 py-1.5">
                    <input name="interval[${daftar.children.length}][nama]" value="${nama || ''}" placeholder="Shift Pagi"
                           class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none focus:border-brand-500">
                </td>
                <td class="px-2 py-1.5">
                    <input type="time" name="interval[${daftar.children.length}][mulai]" value="${mulai || ''}"
                           class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none focus:border-brand-500">
                </td>
                <td class="px-2 py-1.5">
                    <input type="time" name="interval[${daftar.children.length}][selesai]" value="${selesai || ''}"
                           class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none focus:border-brand-500">
                </td>
                <td class="px-2 py-1.5">
                    <input type="number" min="1" max="1440" name="interval[${daftar.children.length}][durasi_min_menit]"
                           value="${durasi || ''}" placeholder="240"
                           class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none focus:border-brand-500">
                </td>
                <td class="px-2 py-1.5 text-center">
                    <button type="button" class="hapus-sesi text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                </td>`;

            document.getElementById('tambah-sesi').addEventListener('click', () => {
                const tr = document.createElement('tr');
                tr.innerHTML = barisSesi();
                daftar.appendChild(tr);
            });

            daftar.addEventListener('click', (event) => {
                if (! event.target.classList.contains('hapus-sesi')) {
                    return;
                }

                // Sesi terakhir tidak boleh dihapus: template interval tanpa
                // sesi akan ditolak server, jadi lebih baik dicegah di sini.
                if (daftar.children.length > 1) {
                    event.target.closest('tr').remove();
                }
            });
        })();
    </script>
@endsection
