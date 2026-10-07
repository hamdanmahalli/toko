<?php

namespace App\Services;

use App\Enums\AbsenArah;
use App\Enums\AbsenGagalReason;
use App\Enums\AbsenMasukStatus;
use App\Enums\AbsenMethod;
use App\Enums\AbsenPulangStatus;
use App\Enums\FleksibelTipe;
use App\Enums\ShiftTipe;
use App\Exceptions\AbsenException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\ShiftInterval;
use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use App\Models\ShiftWindow;
use App\Models\Shop;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Otot tulang absensi.
 *
 * Semua aturan berikut ditegakkan DI SINI, bukan di controller atau di browser:
 *  - geofence dihitung ulang dari koordinat, nilai jarak dari browser dibuang
 *  - status telat dibandingkan dengan batas_telat shift yang dipakai, bukan
 *    dengan asumsi jam_masuk generik
 *  - status pulang dibandingkan dengan jam_pulang shift yang sama dengan saat
 *    masuk, supaya orang yang masuk shift Malam tidak dinilai dengan jam
 *    pulang shift Siang
 *  - durasi kerja dihitung dan disimpan sebagai catatan, tidak dipotong
 *
 * Shift yang dipakai ditentukan ShiftService: karyawan yang jabatannya tidak
 * wajib punya template (kasir, pramuniaga) ikut Window Shift berdasarkan jam
 * datang, sedangkan manajer mengikuti template shift yang ditugaskan.
 */
class AbsenService
{
    public function __construct(
        private readonly GeoService $geo,
        private readonly ShiftService $shift,
        private readonly ShiftWindowService $window,
    ) {}

    // ------------------------------------------------------------------
    // Absen masuk
    // ------------------------------------------------------------------

    public function absenMasuk(
        Employee $employee,
        ?float $latitude,
        ?float $longitude,
        AbsenMethod $metode = AbsenMethod::Qr,
        ?float $akurasi = null,
        ?string $foto = null,
        ?string $deviceId = null,
        ?int $aktorId = null,
        ?CarbonInterface $sekarang = null,
        bool $pakaiLokasiToko = false,
    ): Attendance {
        $sekarang ??= Carbon::now();

        $this->pastikanKaryawanAktif($employee);

        $toko = $employee->shop;

        if ($pakaiLokasiToko) {
            [$latitude, $longitude, $akurasi] = $this->koordinatToko($toko);
        }

        $jarak = $this->ukurJarak($toko, $latitude, $longitude, $akurasi);

        $tanggal = $sekarang->toDateString();

        $acuan = $this->shift->acuanUntuk($employee, $sekarang);
        $interval = $acuan['interval'];

        $sesi = $this->sesiBerikutnya($employee, $tanggal, $acuan);

        // Jam cut-off menahan scan masuk, bukan jam kerja shift. Orang yang telat
        // sudah tidak boleh masuk, jadi absensi baru dibuat kalau belum lewat.
        if ($this->lewatJamCutOff($employee, $sekarang, $acuan)) {
            throw AbsenException::dari(AbsenGagalReason::LewatJamCutOff);
        }

        [$status, $catatan] = $this->nilaiMasuk($employee, $acuan['slot'], $sekarang, $acuan['template'], $interval);
        $catatan = array_merge($catatan, $this->anotasiAkurasi($akurasi, $toko));
        $catatan = array_merge($catatan, $this->anotasiWindow($employee, $sekarang, 'masuk', $acuan['window']));

        return $this->simpan([
            'employee_id' => $employee->id,
            'shop_id' => $toko->id,
            'tanggal' => $tanggal,
            'sesi' => $sesi,
            'jam_masuk' => $sekarang->format('H:i:s'),
            'durasi_maks_menit' => $acuan['durasi_maks_menit'],
            'shift_label_masuk' => $acuan['window']?->nama ?? $interval?->nama,
            'shift_window_id' => $acuan['window']?->id,
            'status_masuk' => $status,
            'latitude_masuk' => $latitude,
            'longitude_masuk' => $longitude,
            'jarak_masuk_meter' => round($jarak, 1),
            'accuracy_masuk_meter' => $akurasi === null ? null : round($akurasi, 1),
            'foto_masuk' => $foto,
            'metode' => $metode->value,
            'device_id' => $deviceId,
            'catatan' => $this->gabungCatatan($catatan),
            'created_by' => $aktorId,
        ]);
    }

    // ------------------------------------------------------------------
    // Absen pulang
    // ------------------------------------------------------------------

    public function absenPulang(
        Employee $employee,
        ?float $latitude,
        ?float $longitude,
        AbsenMethod $metode = AbsenMethod::Qr,
        ?float $akurasi = null,
        ?string $foto = null,
        ?string $deviceId = null,
        ?CarbonInterface $sekarang = null,
        bool $pakaiLokasiToko = false,
    ): Attendance {
        $sekarang ??= Carbon::now();

        $this->pastikanKaryawanAktif($employee);

        $toko = $employee->shop;

        if ($pakaiLokasiToko) {
            [$latitude, $longitude, $akurasi] = $this->koordinatToko($toko);
        }

        $jarak = $this->ukurJarak($toko, $latitude, $longitude, $akurasi);

        $tanggal = $sekarang->toDateString();

        $attendance = $this->absensiTerbuka($employee, $sekarang);

        if ($attendance === null) {
            throw AbsenException::dari(AbsenGagalReason::BelumAbsenMasuk, ['tanggal' => $tanggal]);
        }

        if ($attendance->jam_pulang !== null) {
            throw AbsenException::dari(AbsenGagalReason::SudahAbsenPulang, ['tanggal' => $tanggal]);
        }

        // Acuan jam pulang harus mengikuti shift yang dipakai saat MASUK, bukan
        // jam pulang itu sendiri. Kalau orang masuk 21:30 pada shift Malam dan
        // pulang 23:00, resolve dari jam pulang bisa mendarat di window lain.
        $acuan = $this->acuanUntukPulang($employee, $attendance);
        [$status, $catatan] = $this->nilaiPulang(
            $employee,
            $acuan['slot'],
            $sekarang,
            $acuan['template'],
            $this->waktuMasukAbsensi($attendance),
            $acuan['interval'],
        );
        $catatan = array_merge($catatan, $this->anotasiAkurasi($akurasi, $toko));
        $catatan = array_merge($catatan, $this->anotasiWindow($employee, $sekarang, 'pulang', $acuan['window']));

        $attendance->fill([
            'jam_pulang' => $sekarang->format('H:i:s'),
            'durasi_menit' => $this->hitungDurasi($attendance, $sekarang, $acuan['slot']),
            'durasi_maks_menit' => $acuan['durasi_maks_menit'],
            'shift_label_pulang' => $acuan['window']?->nama ?? $acuan['interval']?->nama,
            'status_pulang' => $status,
            'latitude_pulang' => $latitude,
            'longitude_pulang' => $longitude,
            'jarak_pulang_meter' => round($jarak, 1),
            'accuracy_pulang_meter' => $akurasi === null ? null : round($akurasi, 1),
            'foto_pulang' => $foto,
            'device_id' => $deviceId ?? $attendance->device_id,
        ]);

        if ($catatan !== []) {
            $attendance->catatan = $this->gabungCatatan(
                explode('; ', (string) $attendance->catatan),
                $catatan,
            );
        }

        $attendance->save();

        return $attendance;
    }

    /**
     * Arah absensi berikutnya untuk seorang karyawan.
     *
     * Dipakai perangkat presensi untuk memberi tahu "sekarang pindai untuk
     * masuk atau pulang", bukan untuk memutuskan: penolakan tetap datang dari
     * absenMasuk() atau absenPulang() sesuai aturan yang berlaku.
     */
    public function arahBerikutnya(Employee $employee, ?CarbonInterface $sekarang = null): AbsenArah
    {
        $sekarang ??= Carbon::now();

        return $this->absensiTerbuka($employee, $sekarang) === null
            ? AbsenArah::Masuk
            : AbsenArah::Pulang;
    }

    /**
     * Acuan jam untuk menilai absen pulang.
     *
     * Kalau absensi ini punya window yang tersimpan, pakailah itu. Fallback ke
     * perhitungan ulang dari jam masuk supaya data lama yang belum punya
     * shift_window_id tetap dinilai dengan hasil yang sama.
     *
     * @return array{slot: ?object, window: ?ShiftWindow, durasi_maks_menit: ?int, template: ?ShiftTemplate}
     */
    private function acuanUntukPulang(Employee $employee, Attendance $attendance): array
    {
        $window = $attendance->shift_window_id === null
            ? null
            : ShiftWindow::find($attendance->shift_window_id);

        if ($window !== null) {
            return [
                'slot' => $this->window->slotDariWindow($window),
                'window' => $window,
                'interval' => null,
                'durasi_maks_menit' => $window->durasiMaks(),
                'template' => null,
            ];
        }

        $waktuMasuk = $this->waktuMasukAbsensi($attendance);

        return $this->shift->acuanUntuk($employee, $waktuMasuk ?? now());
    }

    /**
     * Nomor sesi berikutnya untuk scan masuk, sekaligus memeriksa kuota.
     *
     * Shift biasa punya satu sesi per hari. Template interval punya satu sesi
     * per sesi yang dikonfigurasi admin, jadi karyawan boleh scan masuk
     * berulang kali selama masih ada sesi tersisa. Sesi diberikan berurutan
     * mengikuti sesi yang sudah terpakai, bukan urutan jam, supaya layar rekap
     * tidak berubah-ubah urutan.
     *
     * @param  array{slot: ?object, window: ?object, interval: ?object, durasi_maks_menit: ?int, template: ?ShiftTemplate}  $acuan
     *
     * @throws AbsenException
     */
    private function sesiBerikutnya(Employee $employee, string $tanggal, array $acuan): int
    {
        $template = $acuan['template'];

        // Template interval dengan waktu ini di luar semua sesinya: scan masuk
        // ditolak karena tidak ada sesi yang sedang berlangsung.
        if ($template?->tipe() === ShiftTipe::Interval && $acuan['interval'] === null) {
            throw AbsenException::dari(AbsenGagalReason::DiLuarSesiInterval);
        }

        $terpakai = $employee->attendances()->whereDate('tanggal', $tanggal)->count();

        $kuota = $template?->tipe() === ShiftTipe::Interval
            ? $template->intervalsAktif()->count()
            : 1;

        if ($terpakai >= $kuota) {
            throw AbsenException::dari(AbsenGagalReason::SudahAbsenMasuk, ['tanggal' => $tanggal]);
        }

        return $terpakai + 1;
    }

    /**
     * Absensi yang masih terbuka untuk ditutup dengan absen pulang.
     *
     * Normally itu absensi hari ini. Tapi shift fleksibel dan interval boleh
     * melewati tengah malam, jadi absensi yang dibuat jam 20:00 suatu hari
     * ditutup jam 03:00 hari berikutnya. Lookback ke kemarin hanya dibuka bila
     * shift-nya memang lintas malam; tanpa itu, orang yang lupa absen pulang
     * kemarin akan bisa menutup absensi jadi 20 jam.
     */
    private function absensiTerbuka(Employee $employee, CarbonInterface $sekarang): ?Attendance
    {
        $hariIni = $employee->attendances()
            ->whereDate('tanggal', $sekarang->toDateString())
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_pulang')
            ->orderByDesc('sesi')
            ->first();

        if ($hariIni !== null) {
            return $hariIni;
        }

        $kemarin = $sekarang->copy()->subDay()->toDateString();
        $kandidat = $employee->attendances()
            ->whereDate('tanggal', $kemarin)
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_pulang')
            ->orderByDesc('sesi')
            ->first();

        if ($kandidat === null) {
            return null;
        }

        $waktuMasuk = $this->waktuMasukAbsensi($kandidat);

        if ($waktuMasuk === null) {
            return null;
        }

        $acuan = $this->shift->acuanUntuk($employee, $waktuMasuk);

        $bisaLintasMalam = $acuan['interval'] !== null
            ? $acuan['interval']->lintasMalam()
            : $acuan['template']?->bolehLintasMalam() === true && $acuan['slot']?->lintasMalam() === true;

        return $bisaLintasMalam ? $kandidat : null;
    }

    /** Jam masuk pada baris absensi, diubah jadi Carbon agar bisa dibandingkan. */
    private function waktuMasukAbsensi(Attendance $attendance): ?Carbon
    {
        if ($attendance->jam_masuk === null) {
            return null;
        }

        return $attendance->tanggal->copy()->setTimeFrom($attendance->jam_masuk);
    }

    /**
     * Durasi kerja dalam menit, dikurangi waktu istirahat yang dijadwalkan
     * pada shift yang dipakai. Sisa di bawah nol dibulatkan ke nol.
     *
     * Perbandingan memakai tanggal lengkap, bukan jam saja, karena shift
     * fleksibel boleh melewati tengah malam: masuk 20:00 dan pulang 03:00
     * berjarak 7 jam, bukan minus 17 jam.
     *
     * Ini murni angka untuk laporan; tidak ada yang dipotong dari hitungan
     * lembur.
     */
    private function hitungDurasi(Attendance $attendance, CarbonInterface $sekarang, ?object $slot): ?int
    {
        $masuk = $this->waktuMasukAbsensi($attendance);

        if ($masuk === null) {
            return null;
        }

        $pulang = Carbon::parse($attendance->tanggal->toDateString())
            ->setTimeFrom($sekarang)
            ->second(0);

        if ($pulang->lessThanOrEqualTo($masuk)) {
            $pulang = $pulang->addDay();
        }

        $istirahat = $slot instanceof ShiftSlot ? $slot->durasiIstirahat() : 0;

        // Selisih timestamp, bukan diffInMinutes: versi Carbon yang dipakai di
        // sini menandai selisih secara terbalik dan mudah salah baca.
        $selisih = intdiv($pulang->getTimestamp() - $masuk->getTimestamp(), 60);

        return max(0, $selisih - $istirahat);
    }

    // ------------------------------------------------------------------
    // Allowance manual oleh atasan (permission absen.override)
    // ------------------------------------------------------------------

    /**
     * Catat absen untuk karyawan lain tanpa geofence, misalnya ketika GPS
     * tidak pernah bisa dipakai di lokasi itu. Tetap melewati validasi duplicate.
     */
    public function catatManual(
        Employee $employee,
        CarbonInterface $tanggal,
        ?string $jamMasuk = null,
        ?string $jamPulang = null,
        ?string $catatan = null,
        ?int $aktorId = null,
    ): Attendance {
        $this->pastikanKaryawanAktif($employee);

        $waktuMasuk = Carbon::parse($jamMasuk ?: $tanggal->format('Y-m-d').' 08:00');
        $waktuPulang = Carbon::parse($jamPulang ?: $tanggal->format('Y-m-d').' 17:00');

        // Sama seperti absen reguler: shift ditentukan dari jam MASUK, lalu
        // absen pulang dinilai memakai shift itu juga.
        $acuan = $this->shift->acuanUntuk($employee, $waktuMasuk);
        $sesi = $this->sesiBerikutnya($employee, $tanggal->toDateString(), $acuan);

        [$statusMasuk] = $this->nilaiMasuk($employee, $acuan['slot'], $waktuMasuk, $acuan['template'], $acuan['interval']);
        [$statusPulang] = $this->nilaiPulang(
            $employee,
            $acuan['slot'],
            $waktuPulang,
            $acuan['template'],
            $waktuMasuk,
            $acuan['interval'],
        );

        return $this->simpan([
            'employee_id' => $employee->id,
            'shop_id' => $employee->shop_id,
            'tanggal' => $tanggal->toDateString(),
            'sesi' => $sesi,
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'durasi_menit' => $this->hitungDurasi(
                new Attendance(['tanggal' => $tanggal, 'jam_masuk' => $jamMasuk]),
                $waktuPulang,
                $acuan['slot'],
            ),
            'durasi_maks_menit' => $acuan['durasi_maks_menit'],
            'shift_label_masuk' => $acuan['window']?->nama ?? $acuan['interval']?->nama,
            'shift_window_id' => $acuan['window']?->id,
            'shift_label_pulang' => $acuan['window']?->nama ?? $acuan['interval']?->nama,
            'status_masuk' => $statusMasuk,
            'status_pulang' => $statusPulang,
            'metode' => AbsenMethod::Presensi->value,
            'catatan' => $catatan ? trim('[manual] '.$catatan) : '[manual] dicatat oleh atasan',
            'created_by' => $aktorId,
        ]);
    }

    // ------------------------------------------------------------------
    // Status & anotasi
    // ------------------------------------------------------------------

    /**
     * @return array{0: ?AbsenMasukStatus, 1: array<int, string>}
     */
    public function nilaiMasuk(
        Employee $employee,
        ?object $slot,
        CarbonInterface $sekarang,
        ?ShiftTemplate $template = null,
        ?ShiftInterval $interval = null,
    ): array {
        $catatan = $this->anotasiKonteks($employee, $sekarang);

        // Sesi interval sudah membatasi scan ke dalam rentang sesinya, jadi
        // selalu tepat waktu. Tidak ada jam masuk harian yang perlu dinilai.
        if ($interval !== null) {
            return [AbsenMasukStatus::TepatWaktu, [...$catatan, 'sesi '.$interval->rentang()]];
        }

        if ($slot === null) {
            $catatan = [...$catatan, ...$this->catatanTanpaAcuan($employee)];
            $terlambat = $this->terlambatKarenaDiLuarArea($employee);

            return [$terlambat ? AbsenMasukStatus::Terlambat : null, $catatan];
        }

        // Fleksibel bebas dan durasi tetap tidak menilai jam datang: yang dinilai
        // adalah durasi kerjanya, bukan kepatuhan datang. Hanya jenis terbatas
        // yang memakai jam masuk dan batas telat sebagai patokan.
        if ($template?->tipe() === ShiftTipe::Fleksibel
            && $template->fleksibelTipe() !== FleksibelTipe::Terbatas) {
            return [AbsenMasukStatus::TepatWaktu, $catatan];
        }

        $batasTelat = $this->shift->waktu($sekarang, $slot->batas_telat);
        $terlambat = $sekarang->greaterThan($batasTelat);

        return [$terlambat ? AbsenMasukStatus::Terlambat : AbsenMasukStatus::TepatWaktu, $catatan];
    }

    /**
     * @return array{0: ?AbsenPulangStatus, 1: array<int, string>}
     */
    public function nilaiPulang(
        Employee $employee,
        ?object $slot,
        CarbonInterface $sekarang,
        ?ShiftTemplate $template = null,
        ?CarbonInterface $waktuMasuk = null,
        ?ShiftInterval $interval = null,
    ): array {
        $catatan = $this->anotasiKonteks($employee, $sekarang);

        // Sesi interval punya jam selesai sendiri, jadi pulang cepat dihitung
        // terhadap akhir sesi itu, bukan terhadap jam pulang template.
        if ($interval !== null) {
            $targetPulang = $this->jamSelesaiSesi($interval, $waktuMasuk);
            $pulangCepat = $sekarang->lessThan($targetPulang);

            return [$pulangCepat ? AbsenPulangStatus::PulangCepat : AbsenPulangStatus::TepatWaktu, $catatan];
        }

        if ($slot === null) {
            return [null, [...$catatan, ...$this->catatanTanpaAcuan($employee)]];
        }

        // Fleksibel bebas tidak punya acuan jam pulang sama sekali, jadi pulang
        // cepat tidak bisa dihitung. Yang tersisa hanya durasi kerja.
        if ($template?->tipe() === ShiftTipe::Fleksibel
            && $template->fleksibelTipe() === FleksibelTipe::Bebas) {
            return [AbsenPulangStatus::TepatWaktu, $catatan];
        }

        $targetPulang = $this->shift->jamPulangHarapan(
            $slot instanceof ShiftSlot ? $slot : null,
            $template,
            $waktuMasuk ?? $sekarang,
        );

        if ($targetPulang === null) {
            return [null, $catatan];
        }

        $pulangCepat = $sekarang->lessThan($targetPulang);

        return [$pulangCepat ? AbsenPulangStatus::PulangCepat : AbsenPulangStatus::TepatWaktu, $catatan];
    }

    /**
     * Jam selesai sebuah sesi interval, dihitung dari tanggal absensinya.
     *
     * Sesi 22:00-02:00 dihitung sebagai 02:00 keesokan harinya, jadi absensi
     * tetap menempel pada tanggal scan masuk.
     */
    private function jamSelesaiSesi(ShiftInterval $interval, ?CarbonInterface $waktuMasuk): Carbon
    {
        $selesai = Carbon::parse(($waktuMasuk ?? now())->toDateString())->setTimeFrom($interval->selesai)->second(0);

        return $waktuMasuk !== null && $selesai->lessThanOrEqualTo(Carbon::parse($waktuMasuk))
            ? $selesai->addDay()
            : $selesai;
    }

    /**
     * True bila scan masuk sudah lewat jam cut-off shift karyawan ini.
     *
     * Jam cut-off hanya dibaca dari template yang dipakai, jadi kasir yang
     * ikut window shift tidak terpengaruh.
     *
     * @param  array{slot: ?ShiftSlot, window: ?ShiftWindow, durasi_maks_menit: ?int, template: ?ShiftTemplate}  $acuan
     */
    private function lewatJamCutOff(Employee $employee, CarbonInterface $sekarang, array $acuan): bool
    {
        if ($acuan['template'] === null) {
            return false;
        }

        $cutOff = $this->shift->jamCutOffUntuk($employee, $sekarang);

        return $cutOff !== null && $sekarang->greaterThan($cutOff);
    }

    /**
     * Apakah scan di luar semua window harus dihitung terlambat.
     *
     * Tiga kondisi, semuanya sengaja dibedakan:
     *   - karyawan bertemplate: tidak pernah, window memang tidak berlaku
     *   - belum ada window sama sekali: tidak bisa ditegakkan, fiturnya belum dipakai
     *   - ada window dan ada yang disetel ketat: ya, dihitung terlambat
     */
    private function terlambatKarenaDiLuarArea(Employee $employee): bool
    {
        if ($this->shift->wajibTemplate($employee)) {
            return false;
        }

        return $this->window->aturanDiLuarArea($employee)?->diLuarAreaTerlambat() ?? false;
    }

    /**
     * Catatan ketika tidak ada acuan jam untuk hari itu.
     *
     * Hanya untuk karyawan bertemplate, karena di sisi mereka "tidak ada slot
     * hari ini" berarti libur atau jadwal belum diisi. Untuk karyawan window,
     * kasus yang sama sudah dijelaskan oleh anotasiWindow: scan di luar semua
     * pita diberi tahu di sana, sedangkan bila belum ada window sama sekali
     * memang belum ada aturan yang bisa ditegakkan sehingga tidak perlu
     * dicatat tiap absen.
     *
     * @return array<int, string>
     */
    private function catatanTanpaAcuan(Employee $employee): array
    {
        return $this->shift->wajibTemplate($employee)
            ? ['tidak ada shift di hari ini']
            : [];
    }

    /**
     * Catatan otomatis: hari libur dan pengajuan yang sudah disetujui.
     * Keduanya TIDAK memblokir absen, hanya dicatat agar terlihat di laporan.
     *
     * @return array<int, string>
     */
    private function anotasiKonteks(Employee $employee, CarbonInterface $sekarang): array
    {
        $catatan = [];
        $tanggal = $sekarang->toDateString();

        $libur = Holiday::query()
            ->untukToko($employee->shop_id)
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($libur) {
            $catatan[] = 'tanggal ini libur';
        }

        /** @var ?LeaveRequest $izin */
        $izin = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->disetujui()
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->latest('id')
            ->first();

        if ($izin !== null) {
            $catatan[] = 'memiliki pengajuan '.$izin->jenis->label().' disetujui';
        }

        return $catatan;
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /**
     * Catatan otomatis soal window shift.
     *
     * Untuk karyawan yang jabatannya tidak wajib punya template, tidak adanya
     * window yang cocok berarti tidak ada acuan jam sama sekali. Itu perlu
     * dicatat karena absennya tetap diterima, hanya statusnya kosong.
     *
     * @return array<int, string>
     */
    private function anotasiWindow(
        Employee $employee,
        CarbonInterface $sekarang,
        string $arah,
        ?ShiftWindow $window = null,
    ): array {
        // Sudah ketemu window: tidak ada yang perlu dicatat.
        if ($window !== null) {
            return [];
        }

        // Karyawan bertemplate tidak pernah bergantung pada window.
        if ($this->shift->wajibTemplate($employee)) {
            return [];
        }

        // Kalau tabel window masih kosong, fitur ini memang belum dipakai.
        // Mencatat "di luar window" tiap absen hanya menambah noise.
        if ($this->window->daftarUntuk($employee)->isEmpty()) {
            return [];
        }

        return ['jam '.$arah.' di luar semua window shift yang berlaku'];
    }

    /**
     * Koordinat toko untuk dipakai saat absen dicatat dari perangkat presensi.
     *
     * Perangkat berdiri di dalam toko, jadi koordinat yang dipakai adalah
     * koordinat toko itu sendiri: jarak selalu 0 m dan akurasi GPS perangkat
     * tidak relevan. Nilai koordinat toko tetap disimpan di baris absensi
     * supaya laporan punya titik lokasi yang benar, bukan null.
     *
     * Akurasi sengaja dikembalikan null karena tidak ada GNSS yang mengukur.
     *
     * @return array{0: float, 1: float, 2: null}
     */
    private function koordinatToko(Shop $toko): array
    {
        if (! $toko->hasGeofence()) {
            throw AbsenException::dari(AbsenGagalReason::TokoTanpaKoordinat, [
                'shop_id' => $toko->id,
            ]);
        }

        return [(float) $toko->latitude, (float) $toko->longitude, null];
    }

    /**
     * Validasi koordinat + akurasi, lalu hitung jarak ke toko di server.
     */
    private function ukurJarak(
        Shop $toko,
        ?float $lat,
        ?float $lng,
        ?float $akurasi,
    ): float {
        $this->geo->pastikanKoordinat($lat, $lng);
        $this->geo->pastikanAkurasi($akurasi);

        return $this->geo->pastikanDalamRadius($toko, $lat, $lng);
    }

    /**
     * Catatan bila akurasi GPS lebih kasar dari radius toko. Absensi tetap
     * tercatat, tapi atasan punya jejak untuk mengecek ulang.
     *
     * @return array<int, string>
     */
    private function anotasiAkurasi(?float $akurasi, Shop $toko): array
    {
        if (! $this->geo->akurasiPerluPerhatian($akurasi, $toko)) {
            return [];
        }

        return ['akurasi GPS '.round((float) $akurasi).' m, lebih kasar dari radius toko'];
    }

    /**
     * Gabungkan potongan catatan, buang yang kosong dan duplikat.
     *
     * @param  array<int, string>  ...$bagian
     */
    private function gabungCatatan(array ...$bagian): ?string
    {
        $semua = [];

        foreach ($bagian as $satuBagian) {
            foreach ($satuBagian as $potongan) {
                $potongan = trim((string) $potongan);

                if ($potongan !== '' && $potongan !== '-' && ! in_array($potongan, $semua, true)) {
                    $semua[] = $potongan;
                }
            }
        }

        return $semua === [] ? null : implode('; ', $semua);
    }

    private function pastikanKaryawanAktif(Employee $employee): void
    {
        if (! $employee->aktif) {
            throw AbsenException::dari(AbsenGagalReason::KaryawanTidakAktif);
        }

        if ($employee->shop === null) {
            throw AbsenException::dari(AbsenGagalReason::PegawaiTidakDitemukan, [
                'employee_id' => $employee->id,
            ]);
        }
    }

    /**
     * Simpan dengan transaction. Unique index (employee_id, tanggal) yang
     * menjadi penjaga terakhir bila dua permintaan datang bersamaan.
     *
     * @param  array<string, mixed>  $data
     */
    private function simpan(array $data): Attendance
    {
        try {
            return DB::transaction(fn () => Attendance::create($data));
        } catch (QueryException $e) {
            if ($this->kenaUniqueAbsen($e)) {
                throw AbsenException::dari(AbsenGagalReason::SudahAbsenMasuk, [
                    'tanggal' => $data['tanggal'],
                ]);
            }

            throw $e;
        }
    }

    private function kenaUniqueAbsen(QueryException $e): bool
    {
        $sqlState = (string) $e->getCode();

        // PostgreSQL: 23505 (unique_violation), MySQL: 23000/1062
        return in_array($sqlState, ['23505', '23000'], true)
            && str_contains($e->getMessage(), 'attendances_employee_id_tanggal_sesi_unique');
    }
}
