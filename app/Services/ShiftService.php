<?php

namespace App\Services;

use App\Enums\FleksibelTipe;
use App\Enums\ShiftScope;
use App\Enums\ShiftTipe;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Setting;
use App\Models\ShiftInterval;
use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use App\Models\ShiftWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Menentukan jam kerja seorang karyawan pada tanggal tertentu.
 *
 * Urutan prioritas:
 *   1. Penugasan shift individual yang berlaku pada tanggal itu
 *   2. Window shift yang cocok dengan jam datang, untuk jabatan yang tidak
 *      wajib punya template (kasir, pramuniaga)
 *   3. Template shift milik tokonya
 *   4. Template shift global
 *   5. Nilai bawaan dari tabel settings
 *
 * Langkah 2 penting: tanpa itu, kasir diam-diam tetap ikut aturan template
 * global walau tidak pernah ditugaskan template apa pun, dan permintaan
 * "kasir tidak perlu input template" jadi tidak ada artinya.
 */
class ShiftService
{
    public function __construct(private readonly ShiftWindowService $window) {}

    /**
     * True bila karyawan ini mengikuti template shift.
     *
     * Jabatan tanpa jabatan dianggap wajib template supaya perilakunya tidak
     * berubah diam-diam untuk karyawan yang belum diklasifikasikan.
     */
    public function wajibTemplate(Employee $employee): bool
    {
        return $employee->position?->wajibTemplate() ?? true;
    }

    /**
     * Acuan jam kerja untuk satu karyawan pada waktu tertentu.
     *
     * Mengembalikan `slot` untuk penilaian telat/pulang sekaligus `window`
     * asalnya. Untuk karyawan bertemplate `window` selalu null, supaya
     * pemanggil bisa membedakan "pakai template" dari "pakai window".
     *
     * `durasi_maks_menit` hanya batas durasi kerja yang disimpan sebagai
     * catatan; durasi sebenarnya tidak pernah dipotong di sini supaya tidak
     * bercampur dengan perhitungan lembur.
     *
     * `template` ikut dikembalikan karena tipe shift, jenis fleksibel, dan jam
     * cut-off hanya ada di sana, sedangkan pemanggil_absensi butuh semuanya
     * dalam satu tampilan.
     *
     * @return array{slot: ?ShiftSlot, window: ?ShiftWindow, interval: ?ShiftInterval, durasi_maks_menit: ?int, template: ?ShiftTemplate}
     */
    public function acuanUntuk(Employee $employee, CarbonInterface $waktu): array
    {
        // Penugasan template eksplisit menang untuk semua jabatan, termasuk
        // kasir. Kalau kasir sengaja ditugaskan template, itu keputusan admin.
        $ditugaskan = $this->templateDitugaskan($employee, $waktu);

        if ($ditugaskan !== null) {
            return $this->acuanDariTemplate($ditugaskan, $waktu);
        }

        // Tanpa penugasan, kasir dan pramuniaga ikut window shift.
        if (! $this->wajibTemplate($employee)) {
            $window = $this->window->windowPada($employee, $waktu);

            return [
                'slot' => $window === null ? null : $this->window->slotDariWindow($window),
                'window' => $window,
                'interval' => null,
                'durasi_maks_menit' => $window?->durasiMaks(),
                'template' => null,
            ];
        }

        $template = $this->templateOtomatis($employee);

        if ($template === null) {
            return [
                'slot' => $this->slotCadangan(),
                'window' => null,
                'interval' => null,
                'durasi_maks_menit' => null,
                'template' => null,
            ];
        }

        return $this->acuanDariTemplate($template, $waktu);
    }

    /**
     * Acuan jam kerja dari sebuah template.
     *
     * Template bertipe interval tidak punya jam masuk/pulang harian, jadi yang
     * dipakai adalah sesi yang mencakup waktu sekarang. Bila waktu itu tidak
     * berada di sesi mana pun, `interval` null dan pemanggil yang memutuskan
     * scan ditolak atau tidak.
     *
     * @return array{slot: ?ShiftSlot, window: ?ShiftWindow, interval: ?ShiftInterval, durasi_maks_menit: ?int, template: ?ShiftTemplate}
     */
    private function acuanDariTemplate(ShiftTemplate $template, CarbonInterface $waktu): array
    {
        if ($template->tipe() === ShiftTipe::Interval) {
            $interval = $template->intervalPada($waktu);

            return [
                'slot' => null,
                'window' => null,
                'interval' => $interval,
                'durasi_maks_menit' => $interval?->durasi(),
                'template' => $template,
            ];
        }

        $slot = $this->slotDariTemplate($template, $waktu);

        return [
            'slot' => $slot,
            'window' => null,
            'interval' => null,
            'durasi_maks_menit' => $slot?->durasiMaks(),
            'template' => $template,
        ];
    }

    /**
     * Jam paling lambat scan masuk untuk karyawan ini, atau null bila tidak ada.
     *
     * Ini aturan scanning, bukan aturan jam kerja, jadi berlaku untuk semua
     * jabatan yang memang mengikuti template shift.
     */
    public function jamCutOffUntuk(Employee $employee, CarbonInterface $waktu): ?Carbon
    {
        $template = $this->templateUntuk($employee, $waktu);

        if ($template?->jamCutOff() === null) {
            return null;
        }

        return $this->waktu($waktu, $template->jamCutOff());
    }

    /**
     * Jam pulang yang diharapkan untuk seorang karyawan.
     *
     * Tiga sumber, sesuai urutan prioritas:
     *   1. fleksibel durasi tetap: jam datang + durasi kerja, jadi orang yang
     *      datang lebih pagi juga pulang lebih pagi
     *   2. jam_pulang pada slot, digeser ke hari berikutnya bila shift-nya
     *      memang melewati tengah malam
     *   3. null, tidak ada acuan yang bisa dinilai
     */
    public function jamPulangHarapan(
        ?ShiftSlot $slot,
        ?ShiftTemplate $template,
        CarbonInterface $waktuMasuk,
    ): ?Carbon {
        $durasiTetap = $template?->fleksibelTipe() === FleksibelTipe::DurasiTetap
            ? $template->durasiKerja()
            : null;

        if ($durasiTetap !== null) {
            return Carbon::parse($waktuMasuk)->addMinutes($durasiTetap);
        }

        if ($slot === null || $slot->jam_pulang === null) {
            return null;
        }

        $pulang = $this->waktu($waktuMasuk, $slot->jam_pulang);

        // Jam pulang yang lebih awal dari jam masuk hanya masuk akal kalau
        // shift-nya memang boleh melewati tengah malam.
        if ($template?->bolehLintasMalam() === true && $slot->lintasMalam()) {
            $pulang = $pulang->addDay();
        }

        return $pulang;
    }

    /**
     * Slot shift untuk satu karyawan pada satu tanggal.
     *
     * Null berarti hari libur / tidak ada shift. Untuk jabatan yang tidak
     * wajib template, nilai settings bawaan tidak boleh dipakai: kasir tanpa
     * window yang cocok harus tetap tidak dinilai, bukan diam-diam dianggap
     * tepat waktu.
     */
    public function slotUntuk(Employee $employee, CarbonInterface $tanggal): ?ShiftSlot
    {
        $template = $this->templateUntuk($employee, $tanggal);

        if ($template === null) {
            return $this->wajibTemplate($employee) ? $this->slotCadangan() : null;
        }

        return $this->slotDariTemplate($template, $tanggal);
    }

    /** Slot milik template untuk hari tertentu, atau null bila hari itu libur. */
    private function slotDariTemplate(ShiftTemplate $template, CarbonInterface $tanggal): ?ShiftSlot
    {
        $slot = $template->relationLoaded('slots')
            ? $template->slots->firstWhere('hari', $tanggal->dayOfWeek)
            : $template->slots()->where('hari', $tanggal->dayOfWeek)->first();

        if ($slot === null || ! $slot->aktif) {
            return null;
        }

        // Slot dibaca lewat `with('slots')` sehingga relasi template-nya belum
        // terisi. Dipasangkan manual supaya default durasi template tetap
        // terbaca oleh slot yang dimuat lewat template lain.
        $slot->setRelation('template', $template);

        return $slot;
    }

    /** Template shift yang dipakai karyawan pada tanggal tertentu. */
    public function templateUntuk(Employee $employee, CarbonInterface $tanggal): ?ShiftTemplate
    {
        // Penugasan eksplisit selalu dihormati, termasuk untuk kasir.
        $ditugaskan = $this->templateDitugaskan($employee, $tanggal);

        if ($ditugaskan !== null) {
            return $ditugaskan;
        }

        // Template yang dipilih otomatis hanya untuk jabatan yang memang wajib
        // punya template; kalau tidak, kasir ikut aturan global tanpa pernah
        // ditugaskan.
        if (! $this->wajibTemplate($employee)) {
            return null;
        }

        return $this->templateOtomatis($employee);
    }

    /**
     * Template milik toko karyawan, atau template global sebagai cadangan.
     * Template toko lebih diprioritaskan karena lebih spesifik.
     */
    private function templateOtomatis(Employee $employee): ?ShiftTemplate
    {
        return ShiftTemplate::query()
            ->aktif()
            ->where(fn ($q) => $q
                ->where('scope', ShiftScope::Toko->value)->where('shop_id', $employee->shop_id)
                ->orWhere('scope', ShiftScope::Global->value))
            ->with('slots')
            ->orderByRaw('CASE scope WHEN ? THEN 0 ELSE 1 END', [ShiftScope::Toko->value])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Template dari penugasan manual yang berlaku pada tanggal itu, kalau ada.
     *
     * Penugasan terbaru menang bila ada lebih dari satu, dan template yang
     * tidak aktif atau milik toko lain diabaikan karena tidak sah untuk
     * karyawan ini.
     */
    private function templateDitugaskan(Employee $employee, CarbonInterface $tanggal): ?ShiftTemplate
    {
        $tanggalSql = $tanggal->toDateString();

        $penugasan = EmployeeShift::query()
            ->where('employee_id', $employee->id)
            ->berlakuPada($tanggalSql)
            ->with('template.slots')
            ->get()
            ->sortByDesc(fn (EmployeeShift $p) => $p->mulai_berlaku?->timestamp ?? 0)
            ->first(function (EmployeeShift $p) use ($employee) {
                $template = $p->template;

                if ($template === null || ! $template->aktif) {
                    return false;
                }

                // template per toko hanya sah untuk karyawan toko tersebut
                if ($template->scope === ShiftScope::Toko && $template->shop_id !== $employee->shop_id) {
                    return false;
                }

                return true;
            });

        return $penugasan?->template;
    }

    /**
     * Slot cadangan yang dibangun dari tabel settings, dipakai hanya bila
     * seluruh template shift sudah dihapus, supaya sistem tidak mati total.
     */
    public function slotCadangan(): ShiftSlot
    {
        return new ShiftSlot([
            'jam_masuk' => Setting::ambil('umum.jam_masuk', '08:00'),
            'batas_telat' => Setting::ambil('umum.batas_telat', '08:15'),
            'jam_pulang' => Setting::ambil('umum.jam_pulang', '17:00'),
            'aktif' => true,
        ]);
    }

    /** Carbon pada tanggal yang sama dengan slot shift (bikin perbandingan jam aman). */
    public function waktu(CarbonInterface $tanggal, CarbonInterface $slot): Carbon
    {
        return Carbon::parse($tanggal->toDateString().' '.$slot->format('H:i:s'));
    }
}
