<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AturanAbsensi;
use App\Http\Controllers\Controller;
use App\Models\ShiftWindow;
use App\Models\Shop;
use App\Services\ShiftWindowService;
use App\Support\CakupanToko;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pita waktu shift (Pagi, Siang, ...) yang menentukan shift seorang karyawan
 * berdasarkan jam datangnya. Absen jam 07:30 masuk pita Pagi.
 *
 * Berlaku untuk jabatan yang TIDAK wajib memakai template shift, yaitu kasir dan
 * pramuniaga. Batas telat diambil dari kolom `batas_telat` window tersebut, dan
 * jam pulang dibandingkan dengan `selesai`.
 *
 * Jabatan yang wajib memakai template shift (manajer, kepala toko) tidak
 * terpengaruh oleh window sama sekali.
 */
class ShiftWindowController extends Controller
{
    public function __construct(
        private readonly CakupanToko $cakupan,
        private readonly ShiftWindowService $window,
    ) {}

    public function index(Request $request): View
    {
        $query = ShiftWindow::query()
            ->with('shop')
            ->orderBy('mulai')
            ->orderBy('urutan')
            ->orderBy('nama');

        $semuaToko = $this->cakupan->semuaToko($request->user());
        $idToko = $semuaToko ? collect() : $this->cakupan->idToko($request->user());

        // Supervisor melihat window global dan window tokonya sendiri saja.
        if (! $semuaToko) {
            $query->where(fn ($q) => $q->whereNull('shop_id')->orWhereIn('shop_id', $idToko));
        }

        return view('admin.window.index', [
            'window' => $query->get(),
            // Peringatan tumpang tindih juga harus dibatasi cakupan; kalau tidak,
            // supervisor bisa melihat nama window milik toko lain lewat peringatan ini.
            'bentrok' => $semuaToko ? $this->window->bentrok() : $this->window->bentrok(shopId: $idToko->all()),
            'toko' => $this->pilihanToko($request),
            // Supervisor tidak boleh memilih window global, jadi opsinya disembunyikan.
            'bolehSemuaToko' => $semuaToko,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        ShiftWindow::create($data);

        return redirect()
            ->route('admin.window.index')
            ->with('sukses', 'Window shift "'.$data['nama'].'" disimpan.');
    }

    public function update(Request $request, ShiftWindow $window): RedirectResponse
    {
        $this->pastikanBoleh($request, $window);

        $data = $this->validasi($request);

        $window->update($data);

        return redirect()
            ->route('admin.window.index')
            ->with('sukses', 'Window shift "'.$window->nama.'" diperbarui.');
    }

    /**
     * Menonaktifkan, bukan menghapus. Riwayat absensi menunjuk window ini lewat
     * `shift_window_id` dan menyimpan salinan jamnya sebagai teks, jadi
     * menghapus barisnya hanya akan meninggalkan absensi lama tanpa acuan.
     */
    public function destroy(Request $request, ShiftWindow $window): RedirectResponse
    {
        $this->pastikanBoleh($request, $window);

        $nama = $window->nama;

        $window->update(['aktif' => false]);

        return redirect()
            ->route('admin.window.index')
            ->with('sukses', 'Window shift "'.$nama.'" dinonaktifkan. Riwayat absensi lama tidak berubah.');
    }

    public function aktifkan(Request $request, ShiftWindow $window): RedirectResponse
    {
        $this->pastikanBoleh($request, $window);

        $window->update(['aktif' => true]);

        return redirect()
            ->route('admin.window.index')
            ->with('sukses', 'Window shift "'.$window->nama.'" diaktifkan.');
    }

    private function validasi(Request $request): array
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'kode' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9._-]+$/'],
            'mulai' => ['required', 'date_format:H:i'],
            'selesai' => ['required', 'date_format:H:i', 'after:mulai'],
            'batas_telat' => ['nullable', 'date_format:H:i', 'after_or_equal:mulai', 'before_or_equal:selesai'],
            'aturan_absensi' => ['nullable', Rule::enum(AturanAbsensi::class)],
            'durasi_maks_menit' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'shop_id' => ['nullable', Rule::exists('shops', 'id')],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'batas_telat.after_or_equal' => 'Batas telat tidak boleh sebelum jam mulai.',
            'batas_telat.before_or_equal' => 'Batas telat tidak boleh setelah jam selesai.',
            'aturan_absensi.enum' => 'Aturan absensi tidak dikenal.',
            'durasi_maks_menit.max' => 'Durasi maksimum tidak boleh melebihi 24 jam (1440 menit).',
            'kode.regex' => 'Kode window hanya boleh berisi huruf, angka, titik, garis, dan garis bawah.',
        ]);

        $validated['aktif'] = $request->boolean('aktif');
        $validated['urutan'] = (int) ($validated['urutan'] ?? 0);
        // Batas telat kosong berarti toleransi nol: harus datang tepat di jam mulai.
        $validated['batas_telat'] = $validated['batas_telat'] ?? null;
        $validated['kode'] = $this->kodeBersih($validated['kode'] ?? null);
        $validated['durasi_maks_menit'] = $this->durasiBersih($validated['durasi_maks_menit'] ?? null);
        // Tanpa aturan yang dipilih, window diperlakukan toleran supaya admitir
        // jam datang di luar window tidak diam-diam jadi penanda terlambat.
        $validated['aturan_absensi'] = $validated['aturan_absensi'] ?? AturanAbsensi::Toleran->value;
        $shopId = $validated['shop_id'] ?? null;

        $validated['shop_id'] = $shopId !== null ? (int) $shopId : null;

        if ($validated['shop_id'] !== null) {
            abort_unless($this->cakupan->boleh($request->user(), $validated['shop_id']), 403);
        } elseif (! $this->cakupan->semuaToko($request->user())) {
            abort(403, 'Hanya pemilik yang bisa membuat window untuk semua toko.');
        }

        return $validated;
    }

    private function pastikanBoleh(Request $request, ShiftWindow $window): void
    {
        if ($window->shop_id === null) {
            abort_unless($this->cakupan->semuaToko($request->user()), 404);

            return;
        }

        abort_unless($this->cakupan->boleh($request->user(), $window->shop_id), 404);
    }

    /** Kode dinormalisasi ke huruf besar supaya tidak terduplikasi cuma karena beda huruf. */
    private function kodeBersih(?string $kode): ?string
    {
        $kode = strtoupper(trim((string) $kode));

        return $kode === '' ? null : $kode;
    }

    private function durasiBersih(mixed $menit): ?int
    {
        $menit = $menit === null || $menit === '' ? null : (int) $menit;

        return $menit !== null && $menit > 0 ? $menit : null;
    }

    /** @return Collection<int, Shop> */
    private function pilihanToko(Request $request)
    {
        return $this->cakupan->semuaToko($request->user())
            ? Shop::orderBy('nama')->get()
            : $request->user()->shops()->orderBy('nama')->get();
    }
}
