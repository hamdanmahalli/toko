<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Jabatan (posisi) bersifat global untuk seluruh toko, jadi tidak perlu
 * dipecah per toko. Yang per toko adalah penugasan shift-nya.
 */
class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Position::query()
            ->withCount('employees')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'ilike', "%{$request->string('q')}%")
                ->orWhere('kode', 'ilike', "%{$request->string('q')}%")))
            ->when($request->filled('status'), fn ($q) => match ($request->string('status')->value) {
                'aktif' => $q->where('aktif', true),
                'nonaktif' => $q->where('aktif', false),
                default => $q,
            })
            ->orderBy('nama');

        return view('admin.jabatan.index', [
            'jabatan' => $query->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.jabatan.form', ['jabatan' => new Position(['aktif' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        Position::create($data);

        return redirect()
            ->route('admin.jabatan.index')
            ->with('sukses', 'Jabatan "'.$data['nama'].'" ditambahkan.');
    }

    public function edit(Position $jabatan): View
    {
        return view('admin.jabatan.form', ['jabatan' => $jabatan]);
    }

    public function update(Request $request, Position $jabatan): RedirectResponse
    {
        $jabatan->update($this->validasi($request, $jabatan));

        return redirect()
            ->route('admin.jabatan.index')
            ->with('sukses', 'Jabatan "'.$jabatan->nama.'" diperbarui.');
    }

    public function destroy(Position $jabatan): RedirectResponse
    {
        // Jabatan yang masih dipakai karyawan tidak boleh hilang, karena akan
        // merusak data karyawan. Nonaktifkan saja supaya riwayat tetap terbaca.
        if ($jabatan->employees()->exists()) {
            $jabatan->update(['aktif' => false]);

            return redirect()
                ->route('admin.jabatan.index')
                ->with('sukses', 'Jabatan "'.$jabatan->nama.'" masih dipakai karyawan sehingga dinonaktifkan, bukan dihapus.');
        }

        $nama = $jabatan->nama;
        $jabatan->delete();

        return redirect()
            ->route('admin.jabatan.index')
            ->with('sukses', 'Jabatan "'.$nama.'" dihapus.');
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Position $jabatan = null): array
    {
        $validated = $request->validate([
            'nama' => [
                'required', 'string', 'max:120',
                Rule::unique('positions', 'nama')->ignore($jabatan?->id),
            ],
            'kode' => [
                'nullable', 'string', 'max:30',
                Rule::unique('positions', 'kode')->ignore($jabatan?->id),
            ],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'aktif' => ['nullable', 'boolean'],
            'pakai_template' => ['nullable', 'boolean'],
        ]);

        // Kode dibiarkan kosong di form, lalu diturunkan dari nama jabatan.
        $validated['kode'] = $this->kode($validated['kode'] ?? null, $validated['nama'], $jabatan?->id);

        // Checkbox tidak terkirim berarti tidak aktif.
        $validated['aktif'] = $request->boolean('aktif');

        // Sama seperti "aktif": centang berarti wajib template shift.
        $validated['pakai_template'] = $request->boolean('pakai_template');

        return $validated;
    }

    /** Turunkan kode unik dari nama jabatan bila tidak diisi manual. */
    private function kode(?string $kode, string $nama, ?int $abaikanId = null): string
    {
        if (filled($kode)) {
            return Str::upper($kode);
        }

        $dasar = Str::upper(Str::substr(Str::slug($nama, ''), 0, 30));

        if ($dasar === '') {
            $dasar = 'JABATAN';
        }

        $kandidat = $dasar;
        $urut = 2;

        while (Position::where('kode', $kandidat)->when($abaikanId, fn ($q) => $q->whereKeyNot($abaikanId))->exists()) {
            $kandidat = Str::upper(Str::substr($dasar, 0, 27).'-'.$urut);
            $urut++;
        }

        return $kandidat;
    }
}
