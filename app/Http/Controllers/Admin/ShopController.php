<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Support\CakupanToko;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __construct(private readonly CakupanToko $cakupan) {}

    public function index(Request $request): View
    {
        $query = Shop::query()
            ->withCount('employees')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'ilike', "%{$request->string('q')}%")
                ->orWhere('kode', 'ilike', "%{$request->string('q')}%")))
            ->orderBy('nama');

        // Supervisor hanya melihat toko yang ditugaskan kepadanya.
        $this->cakupan->batasi($query, $request->user(), 'id');

        return view('admin.toko.index', ['toko' => $query->get()]);
    }

    public function create(): View
    {
        return view('admin.toko.form', ['toko' => new Shop]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        Shop::create($data);

        return redirect()
            ->route('admin.toko.index')
            ->with('sukses', 'Toko "'.$data['nama'].'" ditambahkan.');
    }

    public function edit(Request $request, Shop $toko): View
    {
        $this->pastikanBoleh($request, $toko);

        return view('admin.toko.form', ['toko' => $toko]);
    }

    public function update(Request $request, Shop $toko): RedirectResponse
    {
        $this->pastikanBoleh($request, $toko);

        $toko->update($this->validasi($request, $toko));

        return redirect()
            ->route('admin.toko.index')
            ->with('sukses', 'Toko "'.$toko->nama.'" diperbarui.');
    }

    public function destroy(Request $request, Shop $toko): RedirectResponse
    {
        $this->pastikanBoleh($request, $toko);

        // Jangan sampai data absensi hilang; nonaktifkan saja.
        $toko->update(['aktif' => false]);

        return redirect()
            ->route('admin.toko.index')
            ->with('sukses', 'Toko "'.$toko->nama.'" dinonaktifkan. Riwayat absensi tetap tersimpan.');
    }

    /**
     * 404 (bukan 403) untuk toko di luar cakupan, supaya keberadaan toko
     * milik orang lain tidak terkonfirmasi ke supervisor.
     */
    private function pastikanBoleh(Request $request, Shop $toko): void
    {
        abort_unless($this->cakupan->boleh($request->user(), $toko), 404);
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Shop $toko = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('shops', 'kode')->ignore($toko?->id)],
            'alamat' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_meter' => ['nullable', 'integer', 'between:20,5000'],
            'zona_waktu' => ['nullable', 'string', 'max:64', 'timezone'],
            'buka' => ['nullable', 'date_format:H:i'],
            'tutup' => ['nullable', 'date_format:H:i', 'after:buka'],
            'presensi_user' => [
                'nullable', 'string', 'max:60',
                Rule::unique('shops', 'presensi_user')->ignore($toko?->id),
            ],
            'presensi_password' => ['nullable', 'string', 'min:8', 'max:60'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $data['presensi_user'] = filled($data['presensi_user'] ?? null)
            ? mb_strtolower(trim($data['presensi_user']))
            : null;

        // Password dikosongkan berarti "jangan diubah", bukan "hapus password".
        // Kalau yang kosong ikut tersimpan, perangkat presensi yang tadinya
        // jalan terkunci begitu saja karena ada form admin yang sengaja tidak
        // mengirim kolomnya.
        $password = trim((string) ($data['presensi_password'] ?? ''));

        if ($password !== '') {
            $data['presensi_password'] = Shop::hashPresensiPassword($password);
        } else {
            unset($data['presensi_password']);
        }

        return $data;
    }
}
