<?php

namespace Tests\Feature;

use App\Enums\AbsenGagalReason;
use App\Enums\AbsenMasukStatus;
use App\Enums\AbsenMethod;
use App\Enums\AbsenPulangStatus;
use App\Enums\AturanAbsensi;
use App\Enums\FleksibelTipe;
use App\Enums\LeaveType;
use App\Enums\RequestStatus;
use App\Enums\ShiftScope;
use App\Enums\ShiftTipe;
use App\Exceptions\AbsenException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\ShiftTemplate;
use App\Models\ShiftWindow;
use App\Models\Shop;
use App\Services\AbsenService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AbsenServiceTest extends TestCase
{
    use RefreshDatabase;

    // Monas, Jakarta
    private const TOKO_LAT = -6.1753924;

    private const TOKO_LNG = 106.8271528;

    private AbsenService $absen;

    private Shop $shop;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->absen = app(AbsenService::class);
        $this->shop = Shop::factory()->diLokasi(self::TOKO_LAT, self::TOKO_LNG)->radius(150)->create();
        $this->employee = Employee::factory()->create(['shop_id' => $this->shop->id]);

        // template global dengan slot Senin-Sabtu
        ShiftTemplate::factory()->denganSlot([1, 2, 3, 4, 5, 6])->create([
            'scope' => ShiftScope::Global,
        ]);
    }

    /** Koordinat di dalam radius toko. */
    private function koordinatDalamToko(): array
    {
        return [self::TOKO_LAT + 0.0005, self::TOKO_LNG + 0.0005];
    }

    private function koordinatJauh(): array
    {
        return [self::TOKO_LAT + 0.05, self::TOKO_LNG];
    }

    // ------------------------------------------------------------------
    // Window shift menentukan shift (bukan lagi label informatif)
    //
    // Kasir/pramuniaga (pakai_template = false): jam datang yang menentukan
    // shift, dan batas telat diambil dari window itu.
    // Manajer (pakai_template = true): template yang menentukan, window diabaikan.
    // ------------------------------------------------------------------

    /**
     * Window default helper: sifatnya ketat, jadi scan di luar pitanya dihitung
     * terlambat. Ditulis eksplisit, bukan mengandalkan default kolom yang bisa
     * berubah sendiri.
     */
    private function window(string $nama, string $mulai, string $selesai, ?string $batasTelat = null): void
    {
        ShiftWindow::create([
            'nama' => $nama,
            'mulai' => $mulai,
            'batas_telat' => $batasTelat,
            'selesai' => $selesai,
            'aturan_absensi' => AturanAbsensi::Ketat,
            'shop_id' => $this->shop->id,
        ]);
    }

    /** Window yang scan di luar pitanya tidak dihitung terlambat. */
    private function windowToleran(string $nama, string $mulai, string $selesai, ?string $batasTelat = null): void
    {
        ShiftWindow::create([
            'nama' => $nama,
            'mulai' => $mulai,
            'batas_telat' => $batasTelat,
            'selesai' => $selesai,
            'aturan_absensi' => AturanAbsensi::Toleran,
            'shop_id' => $this->shop->id,
        ]);
    }

    /** Jadikan karyawan utama seorang kasir: shift ikut window. */
    private function jadiKasir(): Employee
    {
        $this->employee->forceFill([
            'position_id' => Employee::factory()->kasir()->create()->position_id,
        ])->save();

        return $this->employee->fresh();
    }

    /** Jadikan karyawan utama seorang manajer: shift ikut template. */
    private function jadiManajer(): Employee
    {
        $this->employee->forceFill([
            'position_id' => Employee::factory()->manajer()->create()->position_id,
        ])->save();

        return $this->employee->fresh();
    }

    public function test_window_menentukan_shift_karyawan_bukan_template(): void
    {
        $kasir = $this->jadiKasir();

        $this->window('Pagi', '06:00', '12:00', '06:30');
        $this->window('Siang', '12:00', '21:00', '12:15');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 06:20'));

        $this->assertSame('Pagi', $att->shift_label_masuk);
        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
    }

    public function test_karyawan_bershift_window_tidak_perlu_template(): void
    {
        $kasir = $this->jadiKasir();

        $this->window('Pagi', '06:00', '12:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 06:20'));

        // Datang sebelum batas telat window, walau template global di setUp
        // menetapkan batas telat 08:15. Yang dipakai adalah window.
        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
    }

    public function test_batas_telat_mengikuti_window(): void
    {
        $kasir = $this->jadiKasir();

        $this->window('Pagi', '06:00', '12:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 06:45'));

        $this->assertSame('Pagi', $att->shift_label_masuk);
        $this->assertSame(AbsenMasukStatus::Terlambat, $att->status_masuk);
    }

    public function test_batas_telat_kosong_berarti_harus_tepat_di_jam_mulai(): void
    {
        $kasir = $this->jadiKasir();

        // Tidak ada batas_telat: toleransi nol.
        $this->window('Pagi', '06:00', '12:00');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 06:01'));

        $this->assertSame(AbsenMasukStatus::Terlambat, $att->status_masuk);
    }

    public function test_manajer_mengabaikan_window_dan_pakai_template(): void
    {
        $manajer = $this->jadiManajer();

        $this->window('Pagi', '06:00', '12:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        // Datang 06:45: window Pagi sudah terlambat, tapi manajer mengikuti
        // template global yang batas telatnya 08:15.
        $att = $this->absen->absenMasuk($manajer, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 06:45'));

        $this->assertNull($att->shift_label_masuk);
        $this->assertNull($att->shift_window_id);
        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
    }

    public function test_absen_pulang_mengikuti_window_saat_masuk(): void
    {
        $kasir = $this->jadiKasir();

        $this->window('Pagi', '06:00', '12:00', '06:30');
        $this->window('Siang', '12:00', '21:00', '12:15');

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 07:30'));

        // Pulang jam 18:00 ada di pita Siang, tapi shift-nya tetap Pagi karena
        // yang dipakai adalah window saat masuk.
        $att = $this->absen->absenPulang($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 18:00'));

        $this->assertSame('Pagi', $att->shift_label_masuk);
        $this->assertSame('Pagi', $att->shift_label_pulang);
    }

    public function test_pulang_cepat_dihitung_dari_jam_selesai_window(): void
    {
        $kasir = $this->jadiKasir();

        // Pagi 06:00-12:00: harus pulang setelah 12:00.
        $this->window('Pagi', '06:00', '12:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 07:00'));
        $att = $this->absen->absenPulang($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 11:00'));

        $this->assertSame(AbsenPulangStatus::PulangCepat, $att->status_pulang);
    }

    public function test_di_luar_semua_window_dihitung_terlambat_karena_ketat(): void
    {
        $kasir = $this->jadiKasir();

        // Window ketat: scan di luar pitanya dihitung terlambat.
        $this->window('Pagi', '06:00', '12:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 22:00'));

        $this->assertNull($att->shift_label_masuk);
        $this->assertSame(AbsenMasukStatus::Terlambat, $att->status_masuk);
        $this->assertStringContainsString('di luar semua window', (string) $att->catatan);
    }

    public function test_di_luar_semua_window_tidak_dinilai_kala_toleran(): void
    {
        $kasir = $this->jadiKasir();

        $this->windowToleran('Pagi', '06:00', '12:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 22:00'));

        // Scan tetap diterima, hanya tidak dihitung terlambat.
        $this->assertNull($att->shift_label_masuk);
        $this->assertNull($att->status_masuk);
        $this->assertStringContainsString('di luar semua window', (string) $att->catatan);
    }

    public function test_satu_window_ketap_saja_sudah_cukup(): void
    {
        $kasir = $this->jadiKasir();

        // Jari yang satu toleran, yang satu ketat: yang ketat yang menentukan.
        $this->windowToleran('Pagi', '06:00', '12:00', '06:30');
        $this->window('Siang', '12:00', '21:00', '12:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 23:00'));

        $this->assertSame(AbsenMasukStatus::Terlambat, $att->status_masuk);
    }

    public function test_tumpang_tindih_dipilih_yang_mulai_paling_akhir(): void
    {
        $kasir = $this->jadiKasir();

        // Pagi 06:00-14:00, Siang 13:00-21:00 tumpang tindih di 13:00-14:00.
        $this->window('Pagi', '06:00', '14:00', '06:30');
        $this->window('Siang', '13:00', '21:00', '13:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 13:30'));

        $this->assertSame('Siang', $att->shift_label_masuk);
    }

    public function test_tanpa_window_karyawan_window_tidak_dinilai(): void
    {
        $kasir = $this->jadiKasir();

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->assertNull($att->shift_label_masuk);
        $this->assertNull($att->status_masuk);
        // Belum ada window sama sekali, jadi tidak ada aturan yang bisa
        // ditegakkan. Tidak ada catatan supaya tidak menambah noise.
        $this->assertNull($att->catatan);
    }

    public function test_penugasan_template_menang_atas_window_untuk_kasir(): void
    {
        $kasir = $this->jadiKasir();

        // Ada window yang cocok...
        $this->window('Pagi', '06:00', '12:00', '06:30');

        // ...tapi kasir ini sengaja ditugaskan template dengan batas telat 09:00.
        $template = ShiftTemplate::create([
            'nama' => 'Template Pagi',
            'scope' => ShiftScope::Global,
        ]);
        $template->slots()->create([
            'hari' => Carbon::parse('2026-10-05')->dayOfWeek,
            'jam_masuk' => '07:00',
            'batas_telat' => '09:00',
            'jam_pulang' => '15:00',
            'aktif' => true,
        ]);
        EmployeeShift::create([
            'employee_id' => $kasir->id,
            'shift_template_id' => $template->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        // Datang 07:30: template meltedak tepat waktu (batas 09:00), sedangkan
        // window Pagi sudah telat (batas 06:30). Penugasan admin yang menang.
        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 07:30'));

        $this->assertNull($att->shift_window_id);
        $this->assertNull($att->shift_label_masuk);
        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
    }

    public function test_durasi_kerja_tersimpan(): void
    {
        $kasir = $this->jadiKasir();

        $this->window('Pagi', '06:00', '14:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 08:00'));
        $att = $this->absen->absenPulang($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 15:00'));

        $this->assertSame(420, $att->durasi_menit);
        $this->assertSame(7.0, $att->durasiKerja());
        $this->assertSame('7j 0m', $att->durasiKerjaLabel());
    }

    public function test_durasi_kerja_dikurangi_waktu_istirahat(): void
    {
        $manajer = $this->jadiManajer();

        $template = ShiftTemplate::create([
            'nama' => 'Template Istirahat',
            'scope' => ShiftScope::Global,
        ]);
        $template->slots()->create([
            'hari' => Carbon::parse('2026-10-05')->dayOfWeek,
            'jam_masuk' => '08:00',
            'batas_telat' => '08:15',
            'jam_pulang' => '17:00',
            'mulai_istirahat' => '12:00',
            'selesai_istirahat' => '13:00',
            'aktif' => true,
        ]);
        EmployeeShift::create([
            'employee_id' => $manajer->id,
            'shift_template_id' => $template->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($manajer, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 08:00'));
        $att = $this->absen->absenPulang($manajer, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 17:00'));

        // 9 jam kerja dikurangi 1 jam istirahat.
        $this->assertSame(480, $att->durasi_menit);
    }

    public function test_durasi_maks_disimpan_sebagai_catatan_bukan_dipotong(): void
    {
        $kasir = $this->jadiKasir();

        ShiftWindow::create([
            'nama' => 'Pagi',
            'mulai' => '06:00',
            'batas_telat' => '06:30',
            'selesai' => '14:00',
            'durasi_maks_menit' => 480,
            'shop_id' => $this->shop->id,
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 08:00'));
        $att = $this->absen->absenPulang($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 17:00'));

        // Batas 480 menit, sedangkan kerja sebenarnya 9 jam (540 menit).
        // Durasi tetap 540: batas ini murni catatan, tidak dipotong.
        $this->assertSame(480, $att->durasi_maks_menit);
        $this->assertSame(540, $att->durasi_menit);
        $this->assertTrue($att->lewatDurasiMaks());
    }

    public function test_durasi_maks_default_template_dipakai_bila_slot_kosong(): void
    {
        $manajer = $this->jadiManajer();

        $template = ShiftTemplate::create([
            'nama' => 'Template Kantor',
            'scope' => ShiftScope::Global,
            'durasi_maks_menit' => 480,
        ]);
        // Slot sengaja tanpa durasi_maks_menit supaya default template yang dipakai.
        $template->slots()->create([
            'hari' => Carbon::parse('2026-10-05')->dayOfWeek,
            'jam_masuk' => '08:00',
            'batas_telat' => '08:15',
            'jam_pulang' => '17:00',
            'aktif' => true,
        ]);
        EmployeeShift::create([
            'employee_id' => $manajer->id,
            'shift_template_id' => $template->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($manajer, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 08:00'));
        $att = $this->absen->absenPulang($manajer, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 17:00'));

        $this->assertSame(480, $att->durasi_maks_menit);
        $this->assertSame(540, $att->durasi_menit);
        $this->assertTrue($att->lewatDurasiMaks());
    }

    public function test_durasi_dihitung_dari_jam_masuk_bukan_jam_sekarang(): void
    {
        $manajer = $this->jadiManajer();

        $template = ShiftTemplate::create([
            'nama' => 'Template Malam',
            'scope' => ShiftScope::Global,
        ]);
        $template->slots()->create([
            'hari' => Carbon::parse('2026-10-05')->dayOfWeek,
            'jam_masuk' => '20:00',
            'batas_telat' => '20:15',
            'jam_pulang' => '23:59',
            'aktif' => true,
        ]);
        EmployeeShift::create([
            'employee_id' => $manajer->id,
            'shift_template_id' => $template->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($manajer, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 21:00'));
        $att = $this->absen->absenPulang($manajer, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 23:30'));

        $this->assertSame(150, $att->durasi_menit);
    }

    public function test_durasi_menit_kosong_saat_absen_pulang_belum(): void
    {
        $kasir = $this->jadiKasir();

        $this->window('Pagi', '06:00', '14:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->assertNull($att->durasi_menit);
        $this->assertNull($att->durasiKerja());
        $this->assertNull($att->durasiKerjaLabel());
        $this->assertFalse($att->lewatDurasiMaks());
    }

    public function test_window_yang_diedit_tidak_mengganggu_absensi_lama(): void
    {
        $kasir = $this->jadiKasir();

        $this->window('Pagi', '06:00', '12:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 07:30'));
        $windowId = $att->shift_window_id;

        $this->assertNotNull($windowId);

        ShiftWindow::find($windowId)->update(['nama' => 'Pagi Revisi']);

        $this->assertDatabaseHas('attendances', [
            'id' => $att->id,
            'shift_label_masuk' => 'Pagi',
        ]);
    }

    public function test_karyawan_tanpa_jabatan_tetap_berstatus(): void
    {
        // Tanpa jabatan, sistem tidak tahu apakah wajib template, jadi
        // Template yang dipakai supaya perilakunya tidak berubah diam-diam.
        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, akurasi: 10.0, sekarang: Carbon::parse('2026-10-05 07:30'));

        $this->assertNull($att->shift_label_masuk);
        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
    }

    // ------------------------------------------------------------------

    public function test_absen_masuk_dalam_radius_berhasil_dan_jarak_dihitung_server(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, AbsenMethod::Qr, null, null, null, null, Carbon::parse('2026-10-05 08:00'));

        $this->assertNotNull($att->jam_masuk);
        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
        $this->assertSame($this->shop->id, (int) $att->shop_id);

        // jarak harus dihitung ulang di server, sekitar 78 m (0.0005 derajat)
        $this->assertNotNull($att->jarak_masuk_meter);
        $this->assertGreaterThan(50, (float) $att->jarak_masuk_meter);
        $this->assertLessThan(100, (float) $att->jarak_masuk_meter);
    }

    public function test_menerima_jarak_palsu_dari_browser_tetap_ditolak(): void
    {
        // klien mengirim jarak 0 m (berpura-pura di dalam toko)
        $this->expectException(AbsenException::class);

        $this->absen->absenMasuk(
            $this->employee,
            ...$this->koordinatJauh(),
            akurasi: 10.0,
            sekarang: Carbon::parse('2026-10-05 08:00'),
        );
    }

    public function test_ditolak_bila_di_luar_radius(): void
    {
        try {
            $this->absen->absenMasuk($this->employee, ...$this->koordinatJauh(), sekarang: Carbon::parse('2026-10-05 08:00'));
            $this->fail('harusnya ditolak karena di luar radius');
        } catch (AbsenException $e) {
            $this->assertSame(AbsenGagalReason::DiluarRadius, $e->reason);
            $this->assertArrayHasKey('radius_meter', $e->konteks);
            $this->assertArrayHasKey('jarak_meter', $e->konteks);
        }
    }

    public function test_ditolak_bila_akurasi_gps_terlalu_kasar(): void
    {
        Setting::simpan(['geofence.akurasi_maks_meter' => 100]);

        [$lat, $lng] = $this->koordinatDalamToko();

        try {
            $this->absen->absenMasuk($this->employee, $lat, $lng, akurasi: 250.0, sekarang: Carbon::parse('2026-10-05 08:00'));
            $this->fail('harusnya ditolak karena akurasi GPS buruk');
        } catch (AbsenException $e) {
            $this->assertSame(AbsenGagalReason::AkurasiGpsTerlaluKasar, $e->reason);
        }
    }

    /** Bawaan 500 m: akurasi kasar di dalam toko tetap boleh absen. */
    public function test_akurasi_kasar_masih_boleh_absen(): void
    {
        Setting::simpan(['geofence.akurasi_maks_meter' => 500]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $absen = $this->absen->absenMasuk(
            $this->employee,
            $lat,
            $lng,
            akurasi: 250.0,
            sekarang: Carbon::parse('2026-10-05 08:00'),
        );

        $this->assertNotNull($absen->jam_masuk);
        $this->assertEquals(250.0, (float) $absen->accuracy_masuk_meter);
    }

    /** Akurasi lebih kasar dari radius toko dicatat supaya atasan bisa mengecek. */
    public function test_akurasi_kasar_dicatat_di_catatan(): void
    {
        Setting::simpan(['geofence.akurasi_maks_meter' => 500]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $absen = $this->absen->absenMasuk(
            $this->employee,
            $lat,
            $lng,
            akurasi: 220.0,
            sekarang: Carbon::parse('2026-10-05 08:00'),
        );

        $this->assertStringContainsString('akurasi GPS 220 m', (string) $absen->catatan);
    }

    /** Akurasi bagus tidak perlu catatan tambahan. */
    public function test_akurasi_baik_tidak_ikut_dicatat(): void
    {
        Setting::simpan(['geofence.akurasi_maks_meter' => 500]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $absen = $this->absen->absenMasuk(
            $this->employee,
            $lat,
            $lng,
            akurasi: 12.0,
            sekarang: Carbon::parse('2026-10-05 08:00'),
        );

        $this->assertStringNotContainsString('akurasi GPS', (string) $absen->catatan);
    }

    /** Tanpa nilai setting, batas bawaan tetap longgar. */
    public function test_batas_bawaan_akurasi_adalah_500_meter(): void
    {
        Setting::query()->delete();

        [$lat, $lng] = $this->koordinatDalamToko();

        $absen = $this->absen->absenMasuk(
            $this->employee,
            $lat,
            $lng,
            akurasi: 300.0,
            sekarang: Carbon::parse('2026-10-05 08:00'),
        );

        $this->assertNotNull($absen->jam_masuk);
    }

    public function test_ditolak_bila_toko_tidak_punya_koordinat(): void
    {
        $shopTanpaKoordinat = Shop::factory()->tanpaKoordinat()->create();
        $employee = Employee::factory()->create(['shop_id' => $shopTanpaKoordinat->id]);

        $this->expectException(AbsenException::class);

        $this->absen->absenMasuk($employee, self::TOKO_LAT, self::TOKO_LNG, sekarang: Carbon::parse('2026-10-05 08:00'));
    }

    public function test_absen_masuk_kedua_ditolak(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->expectException(AbsenException::class);
        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 10:00'));
    }

    public function test_database_menolak_absen_ganda_walau_ada_race_condition(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        // bypass pengecekan aplikasi untuk menguji unique index
        Attendance::create([
            'employee_id' => $this->employee->id,
            'shop_id' => $this->shop->id,
            'tanggal' => '2026-10-05',
            'jam_masuk' => '08:00',
            'metode' => AbsenMethod::Qr,
        ]);

        $this->expectException(QueryException::class);

        Attendance::create([
            'employee_id' => $this->employee->id,
            'shop_id' => $this->shop->id,
            'tanggal' => '2026-10-05',
            'jam_masuk' => '08:30',
            'metode' => AbsenMethod::Qr,
        ]);
    }

    // ------------------------------------------------------------------
    // Status telat
    // ------------------------------------------------------------------

    public function test_tepat_waktu_saat_tepat_di_batas_telat(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        // batas telat 08:15 -> 08:15:00 masih tepat waktu
        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:15:00'));

        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
    }

    public function test_terlambat_bila_melewati_batas_telat(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:16:00'));

        $this->assertSame(AbsenMasukStatus::Terlambat, $att->status_masuk);
    }

    public function test_batas_telat_bukan_jam_masuk_yang_dipakai(): void
    {
        // shift jam_masuk 09:00, batas telat 09:30
        ShiftTemplate::query()->delete();
        ShiftTemplate::factory()->denganSlot([1, 2, 3, 4, 5, 6], '09:00', '09:30', '17:00')
            ->create(['scope' => ShiftScope::Global]);

        [$lat, $lng] = $this->koordinatDalamToko();

        // 09:10: masih dalam toleransi walau sudah lewat jam_masuk
        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 09:10:00'));

        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk,
            '09:10 seharusnya tepat waktu karena masih di dalam batas telat 09:30');
    }

    // ------------------------------------------------------------------
    // Absen pulang
    // ------------------------------------------------------------------

    public function test_absen_pulang_dibandingkan_ke_jam_pulang_bukan_batas_telat(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        // 16:59 -> pulang cepat (jam_pulang 17:00)
        $att = $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 16:59'));
        $this->assertSame(AbsenPulangStatus::PulangCepat, $att->status_pulang);

        $this->assertEqualsWithDelta(8.98, $att->durasiKerja(), 0.01);
    }

    public function test_pulang_tepat_waktu(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
        $att = $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 17:00'));

        $this->assertSame(AbsenPulangStatus::TepatWaktu, $att->status_pulang);
        $this->assertEqualsWithDelta(9.0, $att->durasiKerja(), 0.01);
    }

    public function test_pulang_tanpa_masuk_ditolak(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        $this->expectException(AbsenException::class);
        $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 17:00'));
    }

    public function test_pulang_kedua_ditolak(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
        $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 17:00'));

        $this->expectException(AbsenException::class);
        $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 18:00'));
    }

    public function test_absen_pulang_juga_dijaga_geofence(): void
    {
        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->expectException(AbsenException::class);
        $this->absen->absenPulang($this->employee, ...$this->koordinatJauh(), sekarang: Carbon::parse('2026-10-05 17:00'));
    }

    // ------------------------------------------------------------------
    // Anotasi konteks
    // ------------------------------------------------------------------

    public function test_cuti_yang_disetujui_tidak_memblokir_absen(): void
    {
        LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'jenis' => LeaveType::Cuti,
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-05',
            'jumlah_hari' => 1,
            'status' => RequestStatus::Approved,
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->assertNotNull($att->jam_masuk, 'absen harus tetap berhasil saat ada cuti disetujui');
        $this->assertStringContainsString('Cuti', (string) $att->catatan);
    }

    public function test_hari_libur_ditandai_pada_catatan(): void
    {
        Holiday::factory()->create([
            'nama' => 'Hari Raya',
            'tanggal' => '2026-10-05',
            'shop_id' => null,
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->assertStringContainsString('libur', (string) $att->catatan);
    }

    public function test_hari_libur_toko_lain_tidak_berlaku(): void
    {
        Holiday::factory()->create([
            'nama' => 'Libur Toko Lain',
            'tanggal' => '2026-10-05',
            'shop_id' => Shop::factory()->create()->id,
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->assertStringNotContainsString('libur', (string) $att->catatan);
    }

    public function test_hari_tanpa_slot_masih_boleh_absen_dengan_catatan(): void
    {
        // template global hanya punya slot Senin-Sabtu, jadi hari Minggu
        // tidak ada jam kerja sama sekali
        $minggu = Carbon::parse('2026-10-05')->next(Carbon::SUNDAY);
        $this->assertSame(Carbon::SUNDAY, $minggu->dayOfWeek);

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: $minggu->setTime(9, 0));

        $this->assertNotNull($att->jam_masuk);
        $this->assertNull($att->status_masuk, 'tanpa slot tidak boleh ada status masuk');
        $this->assertNull($att->status_pulang);
        $this->assertStringContainsString('tidak ada shift', (string) $att->catatan);
    }

    public function test_jatuh_ke_nilai_bawaan_setting_bila_template_shift_kosong(): void
    {
        // seluruh template dihapus -> sistem tidak boleh mati, memakai nilai settings
        ShiftTemplate::query()->delete();
        Setting::simpan([
            'umum.jam_masuk' => '09:00',
            'umum.batas_telat' => '09:30',
            'umum.jam_pulang' => '18:00',
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        // 09:10 -> masih dalam toleransi bawaan 09:30
        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 09:10:00'));

        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
        $this->assertStringNotContainsString('tidak ada shift', (string) $att->catatan);

        // 09:31 -> terlambat menurut nilai bawaan, bukan 08:15
        $employeeLain = Employee::factory()->create(['shop_id' => $this->shop->id]);
        $attLain = $this->absen->absenMasuk($employeeLain, $lat, $lng, sekarang: Carbon::parse('2026-10-05 09:31:00'));

        $this->assertSame(AbsenMasukStatus::Terlambat, $attLain->status_masuk);
    }

    // ------------------------------------------------------------------
    // Tipe shift fleksibel dan jam cut-off
    // ------------------------------------------------------------------

    /**
     * Template fleksibel yang ditugaskan ke karyawan utama.
     *
     * @param  array<string, mixed>  $slot
     */
    private function templateFleksibel(array $tambahan = [], array $slot = []): ShiftTemplate
    {
        $template = ShiftTemplate::create(array_merge([
            'nama' => 'Shift Fleksibel',
            'scope' => ShiftScope::Global,
            'tipe' => ShiftTipe::Fleksibel,
            'fleksibel_tipe' => FleksibelTipe::Bebas,
        ], $tambahan));

        $template->slots()->create(array_merge([
            'hari' => Carbon::parse('2026-10-05')->dayOfWeek,
            'jam_masuk' => '08:00',
            'batas_telat' => '10:00',
            'jam_pulang' => '17:00',
            'aktif' => true,
        ], $slot));

        EmployeeShift::create([
            'employee_id' => $this->employee->id,
            'shift_template_id' => $template->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        return $template;
    }

    public function test_lewat_jam_cut_off_menolak_absen_masuk(): void
    {
        $this->templateFleksibel(['jam_cut_off' => '09:00']);

        [$lat, $lng] = $this->koordinatDalamToko();

        try {
            $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 09:30'));
            $this->fail('Absen masuk seharusnya ditolak karena lewat jam cut-off.');
        } catch (AbsenException $e) {
            $this->assertSame(AbsenGagalReason::LewatJamCutOff, $e->reason);
        }

        $this->assertSame(0, Attendance::count());
    }

    public function test_sebelum_jam_cut_off_masih_boleh_absen_masuk(): void
    {
        $this->templateFleksibel(['jam_cut_off' => '09:00']);

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:50'));

        $this->assertNotNull($att->jam_masuk);
    }

    public function test_jam_cut_off_tidak_berlaku_untuk_karyawan_window(): void
    {
        // Kasir tidak punya penugasan template, jadi template global beserta jam
        // cut-off-nya tidak boleh ikut menilai absennya.
        ShiftTemplate::query()->update(['jam_cut_off' => '09:00']);

        $kasir = $this->jadiKasir();

        $this->window('Pagi', '06:00', '14:00', '06:30');

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($kasir, $lat, $lng, sekarang: Carbon::parse('2026-10-05 09:30'));

        $this->assertNotNull($att->jam_masuk);
    }

    public function test_fleksibel_bebas_tidak_menghitung_terlambat(): void
    {
        $this->templateFleksibel(['fleksibel_tipe' => FleksibelTipe::Bebas], [
            'jam_masuk' => '08:00',
            'batas_telat' => '09:00',
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        // Datang jauh sebelum jam masuk dan lewat batas telat sekaligus tidak
        // masalah: yang dinilai shift fleksibel bebas adalah durasinya.
        $att = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 10:30'));

        $this->assertSame(AbsenMasukStatus::TepatWaktu, $att->status_masuk);
    }

    public function test_fleksibel_terbatas_masih_menghitung_terlambat(): void
    {
        $this->templateFleksibel(['fleksibel_tipe' => FleksibelTipe::Terbatas], [
            'jam_masuk' => '08:00',
            'batas_telat' => '09:00',
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $tepatWaktu = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:50'));
        $this->assertSame(AbsenMasukStatus::TepatWaktu, $tepatWaktu->status_masuk);

        $employeeLain = Employee::factory()->create(['shop_id' => $this->shop->id]);
        EmployeeShift::create([
            'employee_id' => $employeeLain->id,
            'shift_template_id' => ShiftTemplate::latest('id')->first()->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        $terlambat = $this->absen->absenMasuk($employeeLain, $lat, $lng, sekarang: Carbon::parse('2026-10-05 09:30'));
        $this->assertSame(AbsenMasukStatus::Terlambat, $terlambat->status_masuk);
    }

    public function test_fleksibel_durasi_tetap_menghitung_pulang_cepat(): void
    {
        $this->templateFleksibel([
            'fleksibel_tipe' => FleksibelTipe::DurasiTetap,
            'durasi_kerja_menit' => 360,
        ], [
            'jam_masuk' => '08:00',
            'batas_telat' => '12:00',
            'jam_pulang' => '17:00',
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
        // 6 jam kerja dari jam 08:00 berarti pulang jam 14:00.
        $terlaluCepat = $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 12:00'));
        $this->assertSame(AbsenPulangStatus::PulangCepat, $terlaluCepat->status_pulang);

        $employeeLain = Employee::factory()->create(['shop_id' => $this->shop->id]);
        EmployeeShift::create([
            'employee_id' => $employeeLain->id,
            'shift_template_id' => ShiftTemplate::latest('id')->first()->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        $this->absen->absenMasuk($employeeLain, $lat, $lng, sekarang: Carbon::parse('2026-10-05 09:00'));
        $tepatWaktu = $this->absen->absenPulang($employeeLain, $lat, $lng, sekarang: Carbon::parse('2026-10-05 15:00'));
        $this->assertSame(AbsenPulangStatus::TepatWaktu, $tepatWaktu->status_pulang);
        $this->assertSame(360, $tepatWaktu->durasi_menit);
    }

    public function test_fleksibel_lintas_malam_absen_pulang_di_hari_berikutnya(): void
    {
        $this->templateFleksibel([
            'fleksibel_tipe' => FleksibelTipe::Terbatas,
            'jam_cut_off' => '23:00',
        ], [
            'jam_masuk' => '20:00',
            'batas_telat' => '22:00',
            'jam_pulang' => '03:00',
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 20:00'));
        $att = $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-06 03:00'));

        // Absensi tetap milik tanggal 5, cuma jam pulang-nya yang jatuh tanggal 6.
        $this->assertSame('2026-10-05', $att->tanggal->toDateString());
        $this->assertSame(420, $att->durasi_menit);
        $this->assertSame(AbsenPulangStatus::TepatWaktu, $att->status_pulang);
    }

    public function test_absensi_kemarin_tidak_ditutup_bila_shift_tidak_lintas_malam(): void
    {
        $manajer = $this->jadiManajer();

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($manajer, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->expectException(AbsenException::class);

        $this->absen->absenPulang($manajer, $lat, $lng, sekarang: Carbon::parse('2026-10-06 03:00'));
    }

    // ------------------------------------------------------------------
    // Shift interval: beberapa sesi scan masuk/pulang per hari
    // ------------------------------------------------------------------

    /**
     * Template interval dengan sesi yang sudah ditentukan.
     *
     * @param  array<int, array<string, mixed>>  $sesi
     */
    private function templateInterval(array $sesi): ShiftTemplate
    {
        $template = ShiftTemplate::create([
            'nama' => 'Shift Interval',
            'scope' => ShiftScope::Global,
            'tipe' => ShiftTipe::Interval,
        ]);

        foreach ($sesi as $urutan => $isi) {
            $template->intervals()->create($isi + [
                'urutan' => $urutan + 1,
                'aktif' => true,
            ]);
        }

        EmployeeShift::create([
            'employee_id' => $this->employee->id,
            'shift_template_id' => $template->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        return $template;
    }

    public function test_interval_membuat_se_absensi_berurutan(): void
    {
        $this->templateInterval([
            ['nama' => 'Pagi', 'mulai' => '08:00:00', 'selesai' => '12:00:00', 'durasi_min_menit' => 240],
            ['nama' => 'Siang', 'mulai' => '12:00:00', 'selesai' => '16:00:00', 'durasi_min_menit' => 240],
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $sesiSatu = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
        $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 12:00'));

        $sesiDua = $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 13:00'));
        $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 16:00'));

        $this->assertSame(1, $sesiSatu->sesi);
        $this->assertSame(2, $sesiDua->sesi);
        $this->assertSame(240, $sesiSatu->fresh()->durasi_menit);
        $this->assertSame(180, $sesiDua->fresh()->durasi_menit);
        $this->assertSame('Pagi', $sesiSatu->shift_label_masuk);
        $this->assertSame('Siang', $sesiDua->shift_label_masuk);
        $this->assertSame(240, $sesiSatu->fresh()->durasi_maks_menit);
    }

    public function test_interval_menolak_scan_di_luar_seluruh_sesi(): void
    {
        $this->templateInterval([
            ['nama' => 'Pagi', 'mulai' => '08:00:00', 'selesai' => '12:00:00', 'durasi_min_menit' => 240],
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        try {
            $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 15:00'));
            $this->fail('Scan di luar sesi harus ditolak.');
        } catch (AbsenException $e) {
            $this->assertSame(AbsenGagalReason::DiLuarSesiInterval, $e->reason);
        }

        $this->assertSame(0, Attendance::count());
    }

    public function test_interval_menolak_scan_masuk_setelah_semua_sesi_terpakai(): void
    {
        $this->templateInterval([
            ['nama' => 'Pagi', 'mulai' => '08:00:00', 'selesai' => '12:00:00', 'durasi_min_menit' => 240],
            ['nama' => 'Siang', 'mulai' => '12:00:00', 'selesai' => '16:00:00', 'durasi_min_menit' => 240],
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
        $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 12:00'));
        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 13:00'));
        $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 16:00'));

        // Sesi ketiga tidak ada, dan waktu itu sudah di luar sesi terakhir.
        $this->expectException(AbsenException::class);

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 16:00'));
    }

    public function test_interval_menilai_pulang_cepat_terhadap_akhir_sesi(): void
    {
        $this->templateInterval([
            ['nama' => 'Pagi', 'mulai' => '08:00:00', 'selesai' => '12:00:00', 'durasi_min_menit' => 240],
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
        $terlaluCepat = $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 10:00'));

        $this->assertSame(AbsenPulangStatus::PulangCepat, $terlaluCepat->status_pulang);
        $this->assertSame(AbsenMasukStatus::TepatWaktu, $terlaluCepat->status_masuk);
    }

    public function test_interval_lintas_malam_bisa_ditutup_hari_berikutnya(): void
    {
        $this->templateInterval([
            ['nama' => 'Malam', 'mulai' => '20:00:00', 'selesai' => '02:00:00', 'durasi_min_menit' => 360],
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 20:00'));
        $att = $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-06 02:00'));

        $this->assertSame('2026-10-05', $att->tanggal->toDateString());
        $this->assertSame(360, $att->durasi_menit);
        $this->assertSame(AbsenPulangStatus::TepatWaktu, $att->status_pulang);
    }

    public function test_interval_yang_tidak_lintas_malam_tidak_bisa_ditutup_kemarin(): void
    {
        $this->templateInterval([
            ['nama' => 'Pagi', 'mulai' => '08:00:00', 'selesai' => '12:00:00', 'durasi_min_menit' => 240],
        ]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->expectException(AbsenException::class);

        $this->absen->absenPulang($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-06 11:00'));
    }

    public function test_shift_biasa_masih_tolak_absen_masuk_kedua(): void
    {
        $manajer = $this->jadiManajer();

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($manajer, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
        $this->absen->absenPulang($manajer, $lat, $lng, sekarang: Carbon::parse('2026-10-05 17:00'));

        $this->expectException(AbsenException::class);

        $this->absen->absenMasuk($manajer, $lat, $lng, sekarang: Carbon::parse('2026-10-05 17:30'));
    }

    public function test_sesi_default_satu_bukan_null(): void
    {
        $manajer = $this->jadiManajer();

        [$lat, $lng] = $this->koordinatDalamToko();

        $att = $this->absen->absenMasuk($manajer, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));

        $this->assertSame(1, $att->sesi);
    }

    // ------------------------------------------------------------------
    // Status karyawan
    // ------------------------------------------------------------------

    public function test_karyawan_tidak_aktif_ditolak(): void
    {
        $employee = Employee::factory()->tidakAktif()->create(['shop_id' => $this->shop->id]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->expectException(AbsenException::class);
        $this->absen->absenMasuk($employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
    }

    public function test_absen_dua_karyawan_berbeda_tidak_bentrok(): void
    {
        $employeeLain = Employee::factory()->create(['shop_id' => $this->shop->id]);

        [$lat, $lng] = $this->koordinatDalamToko();

        $this->absen->absenMasuk($this->employee, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:00'));
        $attLain = $this->absen->absenMasuk($employeeLain, $lat, $lng, sekarang: Carbon::parse('2026-10-05 08:05'));

        $this->assertNotNull($attLain->jam_masuk);
        $this->assertSame(2, Attendance::count());
    }
}
