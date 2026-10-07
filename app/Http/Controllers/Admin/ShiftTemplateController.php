<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FleksibelTipe;
use App\Enums\ShiftScope;
use App\Enums\ShiftTipe;
use App\Http\Controllers\Controller;
use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use App\Models\Shop;
use App\Support\CakupanToko;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Template shift: pola jam kerja per hari (Senin-Minggu) plus batas toleransi
 * telat. Satu karyawan memakai satu template lewat tabel `employee_shifts`.
 */
class ShiftTemplateController extends Controller
{
    /** Urutan hari yang enak dibaca manusia, bukan urutan Carbon. */
    private const HARI = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        0 => 'Minggu',
    ];

    public function __construct(private readonly CakupanToko $cakupan) {}

    public function index(Request $request): View
    {
        $query = ShiftTemplate::query()
            ->with(['shop', 'slots', 'intervals'])
            ->withCount('employeeAssignments')
            ->when($request->filled('q'), fn ($q) => $q->where('nama', 'ilike', "%{$request->string('q')}%"))
            ->when($request->filled('scope'), fn ($q) => $q->where('scope', $request->string('scope')->value))
            ->orderBy('nama');

        // Supervisor hanya melihat template milik tokonya.
        $this->batasiCakupan($query, $request);

        return view('admin.shift.index', [
            'template' => $query->get(),
            'scope' => ShiftScope::options(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.shift.form', [
            'template' => new ShiftTemplate(['scope' => ShiftScope::Toko, 'aktif' => true]),
            'hari' => self::HARI,
            'toko' => $this->pilihanToko($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $template = DB::transaction(function () use ($data) {
            $template = ShiftTemplate::create(Arr::except($data, ['slot', 'interval']));
            $this->simpanSlot($template, $data['slot']);
            $this->simpanInterval($template, $data['interval']);

            return $template;
        });

        return redirect()
            ->route('admin.shift.index')
            ->with('sukses', 'Template shift "'.$template->nama.'" disimpan.');
    }

    public function edit(Request $request, ShiftTemplate $template): View
    {
        $this->pastikanBoleh($request, $template);

        return view('admin.shift.form', [
            'template' => $template->load(['slots', 'intervals']),
            'hari' => self::HARI,
            'toko' => $this->pilihanToko($request),
        ]);
    }

    public function update(Request $request, ShiftTemplate $template): RedirectResponse
    {
        $this->pastikanBoleh($request, $template);

        $data = $this->validasi($request, $template);

        DB::transaction(function () use ($template, $data) {
            $template->update(Arr::except($data, ['slot', 'interval']));
            $this->simpanSlot($template, $data['slot']);
            $this->simpanInterval($template, $data['interval']);
        });

        return redirect()
            ->route('admin.shift.index')
            ->with('sukses', 'Template shift "'.$template->nama.'" diperbarui.');
    }

    /**
     * Hard delete sesuai permintaan: template beserta slot dan penugasannya
     * hilang. `attendances` sengaja tidak punya FK ke template, jadi riwayat
     * absensi lama tetap utuh.
     */
    public function destroy(Request $request, ShiftTemplate $template): RedirectResponse
    {
        $this->pakahBoleh($request, $template);

        $nama = $template->nama;
        $jumlahKaryawan = $template->employeeAssignments()->where('aktif', true)->count();

        $template->delete();

        return redirect()
            ->route('admin.shift.index')
            ->with('sukses', 'Template shift "'.$nama.'" dihapus'
                .($jumlahKaryawan > 0
                    ? ', termasuk penugasan untuk '.$jumlahKaryawan.' karyawan. Karyawan itu sekarang tidak punya template shift.'
                    : '.'));
    }

    /** Supervisor tidak boleh membuat template global. */
    private function validasi(Request $request, ?ShiftTemplate $template = null): array
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'kode' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9._-]+$/'],
            'tipe' => ['nullable', Rule::enum(ShiftTipe::class)],
            'fleksibel_tipe' => ['nullable', Rule::enum(FleksibelTipe::class)],
            'durasi_kerja_menit' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'jam_cut_off' => ['nullable', 'date_format:H:i'],
            'durasi_maks_menit' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'scope' => ['required', Rule::enum(ShiftScope::class)],
            'shop_id' => ['nullable', 'required_if:scope,toko', Rule::exists('shops', 'id')],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'aktif' => ['nullable', 'boolean'],

            // Template interval tidak punya jadwal per hari, jadi slot boleh kosong di
            // situ. Untuk tipe lain slot wajib ada dan isinya dicek di
            // validasiSlot() supaya pesan errornya menyebut nama hari.
            'slot' => [$this->tipeRequest($request) === ShiftTipe::Interval->value ? 'nullable' : 'required', 'array'],
            'slot.*.aktif' => ['nullable', 'boolean'],
            'slot.*.jam_masuk' => ['nullable', 'date_format:H:i'],
            'slot.*.batas_telat' => ['nullable', 'date_format:H:i'],
            'slot.*.jam_pulang' => ['nullable', 'date_format:H:i'],
            'slot.*.mulai_istirahat' => ['nullable', 'date_format:H:i'],
            'slot.*.selesai_istirahat' => ['nullable', 'date_format:H:i'],
        ], [
            'kode.regex' => 'Kode template hanya boleh berisi huruf, angka, titik, garis, dan garis bawah.',
            'durasi_maks_menit.max' => 'Durasi maksimum tidak boleh melebihi 24 jam (1440 menit).',
            'durasi_kerja_menit.required_if' => 'Shift fleksibel durasi tetap wajib diisi durasi kerjanya.',
            'durasi_kerja_menit.max' => 'Durasi kerja tidak boleh melebihi 24 jam (1440 menit).',
            // Batas durasi per hari divalidasi di validasiSlot() supaya pesannya
            // bisa menyebut nama hari, bukan "slot.1".
        ]);

        $validated['aktif'] = $request->boolean('aktif');
        $validated['kode'] = $this->kodeBersih($validated['kode'] ?? null);
        $validated['durasi_maks_menit'] = $this->durasiBersih($validated['durasi_maks_menit'] ?? null);
        $validated = $this->rapikanTipe($validated);

        // Template global tidak butuh toko; template per toko wajib punya toko.
        $validated['shop_id'] = $validated['scope'] === ShiftScope::Toko->value
            ? $validated['shop_id']
            : null;

        // Cakupan: supervisor hanya boleh template global atau toko sendiri.
        if ($validated['scope'] === ShiftScope::Toko->value) {
            abort_unless($this->cakupan->boleh($request->user(), (int) $validated['shop_id']), 403);
        } elseif ($validated['scope'] === ShiftScope::Global->value && ! $this->cakupan->semuaToko($request->user())) {
            abort(403, 'Hanya pemilik yang bisa membuat template global.');
        }

        $validated['slot'] = $this->validasiSlot($request);
        $validated['interval'] = $this->validasiInterval($request, $validated['tipe']);

        return $validated;
    }

    /**
     * Validasi sesi interval untuk template bertipe interval.
     *
     * Template selain interval mengabaikan sesi sama sekali, jadi input sesi
     * yang terlampir tidak divalidasi dan tidak disimpan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function validasiInterval(Request $request, string $tipe): array
    {
        if ($tipe !== ShiftTipe::Interval->value) {
            return [];
        }

        $sesi = [];

        foreach ((array) $request->input('interval', []) as $index => $isi) {
            // Baris tanpa nama dan tanpa jam dianggap baris kosong, bukan salah
            // input: mengosongkan satu sesi tidak boleh menggagalkan penyimpanan.
            if (blank($isi['nama'] ?? null) && blank($isi['mulai'] ?? null)) {
                continue;
            }

            // Validasi per sesi memakai validator terpisah supaya pesan error
            // bisa menyebut sesi mana yang salah. Kunci error dipetakan ke
            // path aslinya supaya form menyorot baris yang benar.
            $validator = validator(
                [
                    'nama' => $isi['nama'] ?? null,
                    'mulai' => $isi['mulai'] ?? null,
                    'selesai' => $isi['selesai'] ?? null,
                    'durasi_min_menit' => $isi['durasi_min_menit'] ?? null,
                ],
                [
                    'nama' => ['required', 'string', 'max:60'],
                    'mulai' => ['required', 'date_format:H:i'],
                    // Jam selesai boleh lebih awal dari jam mulai karena sesi
                    // malam lanjut ke hari berikutnya, tapi tidak boleh sama.
                    'selesai' => ['required', 'date_format:H:i', 'not_in:'.($isi['mulai'] ?? '')],
                    'durasi_min_menit' => ['nullable', 'integer', 'min:1', 'max:1440'],
                ],
                [
                    'nama.required' => 'Nama sesi wajib diisi.',
                    'mulai.required' => 'Jam mulai sesi wajib diisi.',
                    'selesai.required' => 'Jam selesai sesi wajib diisi.',
                    'selesai.not_in' => 'Jam selesai sesi tidak boleh sama dengan jam mulai.',
                    'durasi_min_menit.max' => 'Durasi minimum sesi tidak boleh melebihi 24 jam (1440 menit).',
                ],
            );

            if ($validator->fails()) {
                throw ValidationException::withMessages(
                    collect($validator->errors()->messages())
                        ->mapWithKeys(fn (array $pesan, string $field) => ["interval.$index.$field" => $pesan])
                        ->all()
                );
            }

            $isiValid = $validator->validated();

            $sesi[] = [
                'nama' => $isiValid['nama'],
                'mulai' => $isiValid['mulai'].':00',
                'selesai' => $isiValid['selesai'].':00',
                'durasi_min_menit' => $this->durasiBersih($isiValid['durasi_min_menit'] ?? null),
                'aktif' => true,
            ];
        }

        if ($sesi === []) {
            throw ValidationException::withMessages([
                'interval' => 'Shift interval wajib punya minimal satu sesi jam kerja.',
            ]);
        }

        return $sesi;
    }

    /** Sesi disimpan berurutan mengikuti urutan yang tampil di form. */
    private function simpanInterval(ShiftTemplate $template, array $sesi): void
    {
        $template->intervals()->delete();

        foreach ($sesi as $urutan => $data) {
            $template->intervals()->create($data + ['urutan' => $urutan + 1]);
        }
    }

    /**
     * Rapikan slot: hari tanpa jam masuk dianggap libur, jam wajib naik,
     * dan batas telat tidak boleh lebih besar dari jam pulang.
     *
     * @return array<int, array<string, mixed>>
     */
    private function validasiSlot(Request $request): array
    {
        $tipe = ShiftTipe::from($this->tipeRequest($request));

        // Template interval memakai sesi jam kerja, bukan jadwal per hari, jadi
        // tidak ada slot yang perlu divalidasi.
        if ($tipe === ShiftTipe::Interval) {
            return [];
        }

        $slot = [];
        $bolehLintasMalam = $tipe->bolehLintasMalam();

        foreach (self::HARI as $hari => $label) {
            $isi = $request->input("slot.$hari", []);

            if (! $request->boolean("slot.$hari.aktif")) {
                continue;
            }

            // Shift fleksibel dan interval boleh jam pulang lebih awal dari jam
            // masuk karena memang lanjut ke hari berikutnya. Shift tetap tidak,
            // supaya aturan lama tidak berubah diam-diam.
            $jamPulang = $bolehLintasMalam
                ? ['required', 'date_format:H:i', 'not_in:'.$isi['jam_masuk']]
                : ['required', 'date_format:H:i', 'after:batas_telat'];

            $validator = validator(
                [
                    'jam_masuk' => $isi['jam_masuk'] ?? null,
                    'batas_telat' => $isi['batas_telat'] ?? null,
                    'jam_pulang' => $isi['jam_pulang'] ?? null,
                    'durasi_maks_menit' => $isi['durasi_maks_menit'] ?? null,
                    'mulai_istirahat' => $isi['mulai_istirahat'] ?? null,
                    'selesai_istirahat' => $isi['selesai_istirahat'] ?? null,
                ],
                [
                    'jam_masuk' => ['required', 'date_format:H:i'],
                    'batas_telat' => $bolehLintasMalam
                        ? ['nullable', 'date_format:H:i']
                        : ['required', 'date_format:H:i', 'after:jam_masuk'],
                    'jam_pulang' => $jamPulang,
                    'durasi_maks_menit' => ['nullable', 'integer', 'min:1', 'max:1440'],
                    // Jam istirahat hanya bisa dibandingkan dengan jam shift
                    // bila shift-nya tidak melewati tengah malam.
                    'mulai_istirahat' => $bolehLintasMalam
                        ? ['nullable', 'date_format:H:i']
                        : ['nullable', 'date_format:H:i', 'after:jam_masuk', 'before:jam_pulang'],
                    'selesai_istirahat' => $bolehLintasMalam
                        ? ['nullable', 'date_format:H:i', 'after:mulai_istirahat']
                        : ['nullable', 'date_format:H:i', 'after:mulai_istirahat', 'before:jam_pulang'],
                ],
                [
                    'jam_masuk.required' => "Jam masuk wajib diisi untuk $label.",
                    'batas_telat.required' => "Batas telat wajib diisi untuk $label.",
                    'jam_pulang.required' => "Jam pulang wajib diisi untuk $label.",
                    'jam_pulang.not_in' => "Jam pulang tidak boleh sama dengan jam masuk di $label.",
                    'durasi_maks_menit.max' => "Durasi maksimum untuk $label tidak boleh melebihi 24 jam.",
                ],
            );

            $validator->validate();

            $slot[$hari] = [
                'hari' => $hari,
                'jam_masuk' => $isi['jam_masuk'],
                'batas_telat' => $isi['batas_telat'],
                'jam_pulang' => $isi['jam_pulang'],
                'durasi_maks_menit' => $this->durasiBersih($isi['durasi_maks_menit'] ?? null),
                'mulai_istirahat' => $isi['mulai_istirahat'] ?? null,
                'selesai_istirahat' => $isi['selesai_istirahat'] ?? null,
                'aktif' => true,
            ];
        }

        if ($slot === []) {
            throw ValidationException::withMessages([
                'slot' => 'Isi minimal satu hari kerja sebelum menyimpan template.',
            ]);
        }

        return $slot;
    }

    /** @param array<int, array<string, mixed>> $slots */
    private function simpanSlot(ShiftTemplate $template, array $slots): void
    {
        // Slot yang tidak lagi dipilih dinonaktifkan, bukan dihapus, agar
        // riwayat absensi lama tetap punya rujukan slot.
        $template->slots()->whereNotIn('hari', array_keys($slots))->update(['aktif' => false]);

        foreach ($slots as $hari => $data) {
            ShiftSlot::updateOrCreate(
                ['shift_template_id' => $template->id, 'hari' => $hari],
                $data + ['aktif' => true],
            );
        }
    }

    /** Tipe shift yang dikirim form, dengan default yang sama seperti rapikanTipe(). */
    private function tipeRequest(Request $request): string
    {
        return (ShiftTipe::tryFrom((string) $request->input('tipe', ShiftTipe::Tetap->value)) ?? ShiftTipe::Tetap)->value;
    }

    /** Kode dinormalisasi ke huruf besar supaya tidak terduplikasi cuma karena beda huruf. */
    private function kodeBersih(?string $kode): ?string
    {
        $kode = strtoupper(trim((string) $kode));

        return $kode === '' ? null : $kode;
    }

    /**
     * Bersihkan kolom yang hanya relevan untuk satu tipe shift.
     *
     * Menghapus nilai yang tidak berlaku itu penting: sisa `durasi_kerja_menit`
     * dari template yang diubah jadi shift tetap akan diam-diam dipakai lagi
     * begitu tipenya dikembalikan ke fleksibel.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function rapikanTipe(array $validated): array
    {
        $tipe = ShiftTipe::tryFrom((string) ($validated['tipe'] ?? '')) ?? ShiftTipe::Tetap;
        $validated['tipe'] = $tipe->value;

        if ($tipe !== ShiftTipe::Fleksibel) {
            $validated['fleksibel_tipe'] = null;
            $validated['durasi_kerja_menit'] = null;

            return $validated;
        }

        if (($validated['fleksibel_tipe'] ?? null) === null) {
            throw ValidationException::withMessages([
                'fleksibel_tipe' => 'Pilih jenis shift fleksibel yang dipakai.',
            ]);
        }

        if ($validated['fleksibel_tipe'] === FleksibelTipe::DurasiTetap->value) {
            $validated['durasi_kerja_menit'] = $this->durasiBersih($validated['durasi_kerja_menit'] ?? null);

            if ($validated['durasi_kerja_menit'] === null) {
                throw ValidationException::withMessages([
                    'durasi_kerja_menit' => 'Durasi kerja wajib diisi untuk shift fleksibel durasi tetap.',
                ]);
            }
        } else {
            $validated['durasi_kerja_menit'] = null;
        }

        return $validated;
    }

    private function durasiBersih(mixed $menit): ?int
    {
        $menit = $menit === null || $menit === '' ? null : (int) $menit;

        return $menit !== null && $menit > 0 ? $menit : null;
    }

    private function batasiCakupan($query, Request $request): void
    {
        if ($this->cakupan->semuaToko($request->user())) {
            return;
        }

        $idToko = $this->cakupan->idToko($request->user());

        // Supervisor: template global (tanpa toko) atau template tokonya.
        $query->where(fn ($q) => $q->whereNull('shop_id')->orWhereIn('shop_id', $idToko));
    }

    private function pastikanBoleh(Request $request, ShiftTemplate $template): void
    {
        $this->pakahBoleh($request, $template);

        if (! $this->cakupan->semuaToko($request->user()) && $template->scope === ShiftScope::Global) {
            abort(404);
        }
    }

    private function pakahBoleh(Request $request, ShiftTemplate $template): void
    {
        if ($template->shop_id !== null) {
            abort_unless($this->cakupan->boleh($request->user(), $template->shop_id), 404);
        }
    }

    /** @return Collection<int, Shop> */
    private function pilihanToko(Request $request)
    {
        return $this->cakupan->semuaToko($request->user())
            ? Shop::orderBy('nama')->get()
            : $request->user()->shops()->orderBy('nama')->get();
    }
}
