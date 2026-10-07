<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ShiftTemplate;
use Illuminate\Database\Eloquent\Collection;

/**
 * Menentukan template shift yang dipakai seorang karyawan pada tanggal tertentu.
 *
 * Urutan resolusi:
 * 1. Penugasan eksplisit di `employee_shifts` yang berlaku pada tanggal itu.
 * 2. Template global aktif (fallback).
 * 3. Template aktif milik toko karyawannya.
 *
 * Kalau tidak ada sama sekali, karyawan tetap boleh absen; hanya jam masuk
 * dan batas telat yang tidak punya acuan.
 */
class ShiftResolver
{
    public function __construct(private readonly ShiftService $shift) {}

    public function untuk(Employee $employee, ?string $tanggal = null): ?ShiftTemplate
    {
        $tanggal ??= now()->toDateString();

        $template = $this->dariPenugasan($employee, $tanggal);

        if ($template !== null) {
            return $template;
        }

        return $this->dariFallback($employee);
    }

    /** Template hasil penugasan yang berlaku pada tanggal tertentu. */
    public function dariPenugasan(Employee $employee, ?string $tanggal = null): ?ShiftTemplate
    {
        $tanggal ??= now()->toDateString();

        return ShiftTemplate::query()
            ->where('aktif', true)
            ->whereIn('id', $employee->shiftAssignments()
                ->berlakuPada($tanggal)
                ->pluck('shift_template_id'))
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Template global, lalu template milik toko karyawan.
     *
     * Hanya untuk jabatan yang wajib punya template. Kasir dan pramuniaga
     * mengikuti window shift sesuai jam datang, jadi offering template global
     * ke mereka hanya membingungkan.
     */
    public function dariFallback(Employee $employee): ?ShiftTemplate
    {
        if (! $this->shift->wajibTemplate($employee)) {
            return null;
        }

        return ShiftTemplate::query()
            ->where('aktif', true)
            ->where(function ($q) use ($employee) {
                $q->whereNull('shop_id')
                    ->orWhere('shop_id', $employee->shop_id);
            })
            ->orderBy('id')
            ->get()
            // Template global menang lebih dulu, baru template toko.
            ->sortBy(fn (ShiftTemplate $t) => $t->shop_id === null ? 0 : 1)
            ->first();
    }

    /**
     * Template yang boleh dipilih untuk karyawan ini: template global aktif dan
     * template aktif milik tokonya saja.
     *
     * @return Collection<int, ShiftTemplate>
     */
    public function pilihanUntuk(?int $shopId): Collection
    {
        return ShiftTemplate::query()
            ->where('aktif', true)
            ->where(fn ($q) => $q->whereNull('shop_id')
                ->when($shopId, fn ($w) => $w->orWhere('shop_id', $shopId)))
            ->orderBy('nama')
            ->get();
    }
}
