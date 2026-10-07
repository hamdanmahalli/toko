<?php

namespace App\Services;

use App\Enums\AbsenMasukStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Rekap laporan yang dijahit dari tabel absensi.
 *
 * Laporan dihitung ulang dari baris absensi yang benar-benar tercatat, bukan
 * dari shift/window yang berlaku sekarang, supaya mengubah konfigurasi shift
 * tidak mengubah angka laporan bulan lalu.
 */
class LaporanService
{
    /**
     * Rekap durasi kerja per karyawan untuk satu rentang tanggal.
     *
     * Agregasi dikelompokkan per karyawan, jadi yang ditarik ke memori hanya
     * satu baris per karyawan, bukan satu baris per sesi absensi. Karena itu
     * angka rangkuman bisa dijumlahkan dari hasil yang sama: menjumlahkan
     * perhalaman hanya akan menghitung sebagian data.
     *
     * @param  array<int, int>|null  $shopId  null = semua toko
     * @return array{baris: Collection<int, array<string, mixed>>, ringkasan: array<string, int>}
     */
    public function durasiKaryawan(
        string $dari,
        string $sampai,
        ?array $shopId = null,
        ?string $cari = null,
    ): array {
        $agregat = $this->dasar($dari, $sampai, $shopId, $cari)
            ->groupBy('employee_id')
            ->orderByRaw('sum(coalesce(durasi_menit, 0)) desc')
            ->select('employee_id')
            ->selectRaw('count(distinct tanggal) as hari')
            ->selectRaw('count(*) as sesi')
            ->selectRaw('sum(coalesce(durasi_menit, 0)) as menit')
            ->selectRaw('sum(case when jam_pulang is null then 1 else 0 end) as belum_pulang')
            ->selectRaw('sum(case when status_masuk = ? then 1 else 0 end) as terlambat', [
                AbsenMasukStatus::Terlambat->value,
            ])
            ->get();

        // Employee diambil terpisah lalu dipasang ke baris agregat, supaya
        // tidak perlu join yang bisa menggandakan baris absensi.
        $karyawan = Employee::query()
            ->with(['shop', 'position'])
            ->whereIn('id', $agregat->pluck('employee_id'))
            ->get()
            ->keyBy('id');

        $baris = $agregat
            ->filter(fn ($r) => $karyawan->has($r->employee_id))
            ->map(fn ($r) => [
                'employee' => $karyawan->get($r->employee_id),
                'hari' => (int) $r->hari,
                'sesi' => (int) $r->sesi,
                'menit' => (int) $r->menit,
                'belum_pulang' => (int) $r->belum_pulang,
                'terlambat' => (int) $r->terlambat,
            ])
            ->values();

        return [
            'baris' => $baris,
            'ringkasan' => [
                'karyawan' => $baris->count(),
                'hari' => $baris->sum('hari'),
                'sesi' => $baris->sum('sesi'),
                'menit' => (int) $baris->sum('menit'),
                'belum_pulang' => (int) $baris->sum('belum_pulang'),
                'terlambat' => (int) $baris->sum('terlambat'),
            ],
        ];
    }

    /**
     * Query dasar yang sudah difilter tanggal, toko, dan pencarian.
     *
     * @param  array<int, int>|null  $shopId
     */
    private function dasar(string $dari, string $sampai, ?array $shopId, ?string $cari): Builder
    {
        return Attendance::query()
            ->whereBetween('tanggal', [$dari, $sampai])
            ->when($shopId !== null, fn ($q) => $q->whereIn('shop_id', $shopId))
            ->when($cari !== null && $cari !== '', fn ($q) => $q->whereHas('employee', fn ($e) => $e
                ->where('nama', 'ilike', "%{$cari}%")
                ->orWhere('nip', 'ilike', "%{$cari}%")));
    }
}
