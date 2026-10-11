<?php

namespace App\Services;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Models\CashCategory;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Kategori kas milik tiap karyawan.
 *
 * Kategori bawaan disemai "saat dibutuhkan" (lazy) supaya akun lama ikut
 * mendapatkannya tanpa seeder manual, dan supaya menghapus/menonaktifkan
 * sebuah bawaan tidak dibatalkan oleh penyemaian berikutnya.
 */
class KasKategoriService
{
    /** Pastikan semua kategori bawaan sudah ada untuk karyawan ini. */
    public function pastikanDefault(Employee $employee): void
    {
        $ada = $employee->cashCategories()->pluck('kode')->all();
        $kurang = [];

        foreach (KategoriKas::defaults() as $default) {
            if (! in_array($default['kode'], $ada, true)) {
                $kurang[] = [
                    'employee_id' => $employee->id,
                    'kode' => $default['kode'],
                    'nama' => $default['nama'],
                    'jenis' => $default['jenis']->value,
                    'urutan' => $default['urutan'],
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($kurang !== []) {
            CashCategory::query()->insert($kurang);
        }
    }

    /**
     * Kategori aktif satu karyawan, terurutkan. Bila $jenis diisi, hanya
     * kategori dengan jenis itu.
     *
     * @return Collection<int, CashCategory>
     */
    public function daftar(Employee $employee, ?JenisKas $jenis = null): Collection
    {
        return $employee->cashCategories()
            ->when($jenis !== null, fn ($q) => $q->where('jenis', $jenis->value))
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    /**
     * Kategori yang boleh dipilih saat mencatat transaksi: hanya yang aktif.
     *
     * @return Collection<int, CashCategory>
     */
    public function opsi(Employee $employee, JenisKas $jenis): Collection
    {
        return $employee->cashCategories()
            ->where('jenis', $jenis->value)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    /** Semua kategori (termasuk nonaktif) sebagai peta kode => nama, untuk label riwayat. */
    public function petaNama(Employee $employee): array
    {
        return $employee->cashCategories()->pluck('nama', 'kode')->all();
    }

    /**
     * Kode unik dari nama kategori. Kode dipakai transaksi dan tidak ikut
     * berubah saat nama diperbarui.
     */
    public function kodeUnik(Employee $employee, string $nama, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($nama, '_');

        if ($dasar === '') {
            $dasar = 'kategori';
        }

        $dasar = substr($dasar, 0, 40);
        $kandidat = $dasar;
        $urut = 2;

        while (
            $employee->cashCategories()
                ->where('kode', $kandidat)
                ->when($abaikanId, fn ($q) => $q->whereKeyNot($abaikanId))
                ->exists()
        ) {
            $potong = max(1, 40 - strlen((string) $urut) - 1);
            $kandidat = substr($dasar, 0, $potong).'_'.$urut;
            $urut++;
        }

        return $kandidat;
    }
}
