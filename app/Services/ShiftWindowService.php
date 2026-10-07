<?php

namespace App\Services;

use App\Enums\AturanAbsensi;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ShiftSlot;
use App\Models\ShiftWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menentukan pita shift mana yang cocok dengan jam absensi.
 *
 * Dipakai untuk menentukan shift karyawan yang jabatannya tidak wajib punya
 * template shift (kasir, pramuniaga): jam datangnya sendiri yang menentukan
 * dia masuk shift Pagi, Siang, atau Malam. TIDAK dipakai untuk karyawan yang
 * jabatan-nya `pakai_template`; mereka tetap memakai ShiftSlot.
 *
 * Yang menang saat tumpang tindih adalah window yang mulai paling akhir, dan
 * tiap window punya `batas_telat` sendiri yang eksplisit. Kalau tidak begitu,
 * karyawan bisa refleks "datang lewat batas telat tapi masih di dalam window"
 * supaya tidak tercatat terlambat.
 *
 * Tiap window juga punya aturan ketat/toleran yang menentukan apa yang terjadi
 * kalau jam absen jatuh di luar semua pita.
 */
class ShiftWindowService
{
    /**
     * Window yang memuat waktu ini, null bila di luar semua window.
     *
     * Saat beberapa window cocok (tumpang tindih), yang dipilih adalah yang
     * `mulai`-nya paling akhir: orang yang datang jam 13:30 ketika ada
     * Pagi 06:00-14:00 dan Siang 13:00-21:00 masuk ke Siang.
     */
    public function windowPada(Employee $employee, CarbonInterface $waktu): ?ShiftWindow
    {
        if ($employee->shop_id === null) {
            return null;
        }

        return $this->daftarUntuk($employee)
            ->first(fn (ShiftWindow $w) => $w->mencakup($waktu));
    }

    /**
     * @return Collection<int, ShiftWindow>
     */
    public function daftarUntuk(Employee $employee)
    {
        // Urutan sengaja dibalik: mulai paling dulu diperiksa lebih awal agar
        // window yang mulai paling akhir langsung jadi pemenang.
        return ShiftWindow::query()
            ->untukToko($employee->shop_id)
            ->orderByDesc('mulai')
            ->orderByDesc('urutan')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Nama window untuk jam ini, atau null bila tidak ada yang cocok.
     */
    public function labelPada(Employee $employee, CarbonInterface $waktu): ?string
    {
        return $this->windowPada($employee, $waktu)?->nama;
    }

    /**
     * Bentukkan ShiftSlot dari sebuah window supaya penentuan status telat dan
     * pulang bisa memakai kode yang sama dengan karyawan bertemplate.
     *
     * `batas_telat` diambil dari window (toleransi eksplisit per window), dan
     * `jam_pulang` diambil dari jam selesai window. `jam_masuk` diisi dengan
     * batas telat supaya tidak pernah lebih cepat daripada batas tersebut.
     */
    public function slotDariWindow(ShiftWindow $window): ShiftSlot
    {
        return new ShiftSlot([
            'jam_masuk' => $window->jamBatasTelat(),
            'batas_telat' => $window->jamBatasTelat(),
            'jam_pulang' => $window->jamPulang(),
            'durasi_maks_menit' => $window->durasiMaks(),
            'aktif' => true,
        ]);
    }

    /**
     * Sikap yang berlaku ketika jam absen jatuh di luar semua window.
     *
     * Kalau salah satu window yang berlaku disetel ketat, allora scan di luar
     * semua pita dianggap terlambat. Kalau semuanya toleran, scan tetap
     * diterima tetapi statusnya dibiarkan kosong.
     *
     * Mengembalikan null bila tidak ada window sama sekali, karena disitu
     * fiturnya memang belum dipakai dan tidak ada aturan yang bisa ditegakkan.
     */
    public function aturanDiLuarArea(Employee $employee): ?AturanAbsensi
    {
        $daftar = $this->daftarUntuk($employee);

        if ($daftar->isEmpty()) {
            return null;
        }

        return $daftar->contains(fn (ShiftWindow $w) => $w->aturan()->diLuarAreaTerlambat())
            ? AturanAbsensi::Ketat
            : AturanAbsensi::Toleran;
    }

    /**
     * Rekap karyawan per window untuk satu tanggal.
     *
     * Dihitung dari label yang benar-benar tersimpan di tabel absensi, bukan
     * dari window yang berlaku saat ini. Kalau dihitung dari window, karyawan
     * yang belum absen hari itu ikut terhitung seolah sudah masuk shift.
     *
     * Satu karyawan boleh punya beberapa baris pada tanggal yang sama (sistem
     * interval), jadi yang dihitung orangnya, bukan baris absensinya.
     *
     * @param  array<int, int>|null  $shopId  null = semua toko
     * @return Collection<string, int>
     */
    public function rekap(string $tanggal, ?array $shopId = null): Collection
    {
        return Attendance::query()
            ->whereDate('tanggal', $tanggal)
            ->whereNotNull('shift_label_masuk')
            ->when($shopId !== null, fn ($q) => $q->whereIn('shop_id', $shopId))
            ->groupBy('shift_label_masuk')
            ->orderBy('shift_label_masuk')
            ->selectRaw('shift_label_masuk, count(distinct employee_id) as jumlah')
            ->pluck('jumlah', 'shift_label_masuk');
    }

    /**
     * Memeriksa window yang akan bertumpuk dengan window lain.
     *
     * Tumpang tindih sah dipakai, hanya dilaporkan supaya atasan tahu label
     * mana yang menang. Daftar dibatasi ke toko yang sedang dilihat supaya
     * nama window toko lain tidak bocor lewat peringatan ini.
     *
     * @param  int|null  $ignoreId  window yang sedang diedit, supaya tidak membandingkan dirinya sendiri
     * @param  array<int, int>|null  $shopId  null = semua toko
     * @return array<int, string>
     */
    public function bentrok(?int $ignoreId = null, ?array $shopId = null): array
    {
        $query = ShiftWindow::query()
            ->where('aktif', true)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->when(
                $shopId !== null,
                fn ($q) => $q->where(fn ($w) => $w->whereNull('shop_id')->orWhereIn('shop_id', $shopId)),
            )
            ->orderBy('mulai')
            ->get();

        $berpotongan = [];

        foreach ($query as $satu) {
            foreach ($query as $dua) {
                if ($satu->id === $dua->id) {
                    continue;
                }

                $bentrok = $satu->mulai <= $dua->selesai && $dua->mulai <= $satu->selesai;

                if (! $bentrok) {
                    continue;
                }

                $pasangan = collect([$satu->nama, $dua->nama])->sort()->implode(' ↔ ');

                $berpotongan[$pasangan] = ($berpotongan[$pasangan] ?? 0) + 1;
            }
        }

        return array_keys($berpotongan);
    }
}
