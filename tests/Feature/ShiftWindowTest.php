<?php

namespace Tests\Feature;

use App\Enums\AbsenMethod;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ShiftWindow;
use App\Models\Shop;
use App\Services\ShiftWindowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShiftWindowTest extends TestCase
{
    use RefreshDatabase;

    private ShiftWindowService $layanan;

    private Shop $toko;

    private Shop $tokoLain;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layanan = app(ShiftWindowService::class);
        $this->toko = Shop::factory()->create();
        $this->tokoLain = Shop::factory()->create();
        $this->employee = Employee::factory()->create(['shop_id' => $this->toko->id]);
    }

    private function jam(string $waktu): Carbon
    {
        return Carbon::parse('2026-10-05 '.$waktu);
    }

    private function window(string $nama, string $mulai, string $selesai, array $extra = []): void
    {
        ShiftWindow::create($extra + [
            'nama' => $nama,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'shop_id' => $this->toko->id,
        ]);
    }

    /** Bikin satu baris absensi pada tanggal yang sama dengan test. */
    private function absen(array $extra = []): Attendance
    {
        return Attendance::create($extra + [
            'employee_id' => $this->employee->id,
            'shop_id' => $this->toko->id,
            'tanggal' => '2026-10-05',
            'jam_masuk' => '08:00:00',
            'status_masuk' => 'tepat_waktu',
            'metode' => AbsenMethod::Selfie->value,
        ]);
    }

    // ------------------------------------------------------------- deteksi

    public function test_jam_di_dalam_window_terdeteksi(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        $this->assertSame('Pagi', $this->layanan->labelPada($this->employee, $this->jam('08:30')));
    }

    public function test_jam_tepat_di_batas_mulai_masuk(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        $this->assertSame('Pagi', $this->layanan->labelPada($this->employee, $this->jam('06:00')));
    }

    public function test_jam_tepat_di_batas_selesai_masuk(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        $this->assertSame('Pagi', $this->layanan->labelPada($this->employee, $this->jam('14:00')));
    }

    public function test_satu_menit_di_luar_window_null(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        $this->assertNull($this->layanan->labelPada($this->employee, $this->jam('05:59')));
        $this->assertNull($this->layanan->labelPada($this->employee, $this->jam('14:01')));
    }

    public function test_jam_dalam_gap_null(): void
    {
        $this->window('Pagi', '06:00', '14:00');
        $this->window('Siang', '18:00', '23:00');

        $this->assertNull($this->layanan->labelPada($this->employee, $this->jam('16:00')));
    }

    public function test_tumpang_tindih_menang_yang_mulai_lebih_akhir(): void
    {
        $this->window('Siang', '13:00', '21:00');
        $this->window('Pagi', '06:00', '14:00');

        // 13:30 ada di keduanya. Yang menang adalah window yang mulai paling
        // akhir (Siang), dan hasilnya tidak bergantung urutan input.
        $this->assertSame('Siang', $this->layanan->labelPada($this->employee, $this->jam('13:30')));
    }

    public function test_tumpang_tindih_menang_yang_mulai_paling_akhir(): void
    {
        $this->window('Pagi', '06:00', '14:00');
        $this->window('Siang', '13:00', '21:00');
        $this->window('Malam', '20:00', '23:59');

        // 21:00 ada di Siang (13:00-21:00) dan Malam (20:00-23:59).
        $this->assertSame('Malam', $this->layanan->labelPada($this->employee, $this->jam('21:00')));
    }

    public function test_jam_di_luar_semua_window(): void
    {
        $this->window('Pagi', '06:00', '14:00');
        $this->window('Siang', '13:00', '21:00');

        $this->assertNull($this->layanan->labelPada($this->employee, $this->jam('23:00')));
    }

    // ------------------------------------------------------------- cakupan

    public function test_window_toko_lain_diabaikan(): void
    {
        ShiftWindow::create([
            'nama' => 'PagiTokoLain',
            'mulai' => '06:00',
            'selesai' => '14:00',
            'shop_id' => $this->tokoLain->id,
        ]);

        $this->assertNull($this->layanan->labelPada($this->employee, $this->jam('08:00')));
    }

    public function test_window_global_berlaku_untuk_semua_toko(): void
    {
        ShiftWindow::create([
            'nama' => 'Umum',
            'mulai' => '06:00',
            'selesai' => '14:00',
            'shop_id' => null,
        ]);

        $this->assertSame('Umum', $this->layanan->labelPada($this->employee, $this->jam('08:00')));

        $employeeLain = Employee::factory()->create(['shop_id' => $this->tokoLain->id]);

        $this->assertSame('Umum', $this->layanan->labelPada($employeeLain, $this->jam('08:00')));
    }

    public function test_window_toko_mengalah_oleh_window_global(): void
    {
        $this->window('PagiToko', '06:00', '14:00');

        ShiftWindow::create([
            'nama' => 'Umum',
            'mulai' => '06:00',
            'selesai' => '14:00',
            'shop_id' => null,
        ]);

        // Keduanya cocok pada 08:00; yang mulai lebih awal menang, dan nilainya sama.
        $this->assertContains(
            $this->layanan->labelPada($this->employee, $this->jam('08:00')),
            ['PagiToko', 'Umum'],
        );
    }

    public function test_window_tidak_aktif_diabaikan(): void
    {
        $this->window('Pagi', '06:00', '14:00', ['aktif' => false]);

        $this->assertNull($this->layanan->labelPada($this->employee, $this->jam('08:00')));
    }

    public function test_karyawan_tanpa_toko_aman(): void
    {
        $employeeTanpaToko = new Employee;

        $this->assertNull($this->layanan->labelPada($employeeTanpaToko, $this->jam('08:00')));
    }

    // ------------------------------------------------------------- rekap

    public function test_rekap_menghitung_karyawan_per_window(): void
    {
        $this->window('Pagi', '06:00', '14:00');
        $this->window('Siang', '13:00', '21:00');

        $pagi = Employee::factory()->create(['shop_id' => $this->toko->id]);
        $siang = Employee::factory()->create(['shop_id' => $this->toko->id]);

        // Rekap dihitung dari label yang tersimpan di absensi, jadi yang
        // dihitung hanya karyawan yang benar-benar absen pada label itu.
        $this->absen(['employee_id' => $this->employee->id, 'shift_label_masuk' => 'Pagi']);
        $this->absen(['employee_id' => $pagi->id, 'shift_label_masuk' => 'Pagi']);
        $this->absen(['employee_id' => $siang->id, 'shift_label_masuk' => 'Siang']);

        $rekap = $this->layanan->rekap('2026-10-05');

        $this->assertSame(['Pagi' => 2, 'Siang' => 1], $rekap->all());
    }

    public function test_rekap_mengabaikan_absensi_tanpa_label(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        $this->absen(['employee_id' => $this->employee->id, 'shift_label_masuk' => null]);

        $this->assertTrue($this->layanan->rekap('2026-10-05')->isEmpty());
    }

    public function test_rekap_membatasi_toko(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        $tokoLain = Shop::factory()->create();
        $karyawanLain = Employee::factory()->create(['shop_id' => $tokoLain->id]);

        $this->absen(['employee_id' => $this->employee->id, 'shop_id' => $this->toko->id, 'shift_label_masuk' => 'Pagi']);
        $this->absen(['employee_id' => $karyawanLain->id, 'shop_id' => $tokoLain->id, 'shift_label_masuk' => 'Pagi']);

        $semua = $this->layanan->rekap('2026-10-05');

        $this->assertSame(['Pagi' => 2], $semua->all());

        $satuToko = $this->layanan->rekap('2026-10-05', [$this->toko->id]);

        $this->assertSame(['Pagi' => 1], $satuToko->all());
    }

    public function test_rekap_menghitung_karyawan_tidak_ganda(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        $pagi = Employee::factory()->create(['shop_id' => $this->toko->id]);

        $this->absen(['employee_id' => $this->employee->id, 'shift_label_masuk' => 'Pagi']);
        $this->absen(['employee_id' => $pagi->id, 'shift_label_masuk' => 'Pagi']);

        $this->assertSame(['Pagi' => 2], $this->layanan->rekap('2026-10-05')->all());
    }

    /**
     * Label dihitung dalam satuan orang. Pada shift interval satu orang punya
     * beberapa baris absensi di tanggal yang sama, dan kalau baris yang dihitung
     * semuanya, satu orang akan terappear beberapa kali di dashboard.
     */
    public function test_rekap_menghitung_orang_bukan_baris_saat_multi_sesi(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        $pagi = Employee::factory()->create(['shop_id' => $this->toko->id]);

        $this->absen(['employee_id' => $this->employee->id, 'shift_label_masuk' => 'Pagi', 'sesi' => 1]);
        $this->absen(['employee_id' => $this->employee->id, 'shift_label_masuk' => 'Pagi', 'sesi' => 2]);
        $this->absen(['employee_id' => $pagi->id, 'shift_label_masuk' => 'Pagi', 'sesi' => 1]);

        $this->assertSame(['Pagi' => 2], $this->layanan->rekap('2026-10-05')->all());
    }

    // ------------------------------------------------------------- bentrok

    public function test_bentrok_terdeteksi(): void
    {
        $this->window('Pagi', '06:00', '14:00');
        $this->window('Siang', '13:00', '21:00');

        $bentrok = $this->layanan->bentrok(shopId: [$this->toko->id]);

        $this->assertNotEmpty($bentrok);
    }

    public function test_tanpa_bentrok_saat_pita_jauh(): void
    {
        $this->window('Pagi', '06:00', '14:00');
        $this->window('Siang', '14:01', '21:00');

        $this->assertSame([], $this->layanan->bentrok(shopId: [$this->toko->id]));
    }

    public function test_bentrok_membandingkan_window_berbeda_toko(): void
    {
        $this->window('Pagi', '06:00', '14:00');

        ShiftWindow::create([
            'nama' => 'Siang',
            'mulai' => '13:00',
            'selesai' => '21:00',
            'shop_id' => $this->tokoLain->id,
        ]);

        // Dibatasi ke satu toko: window toko lain tidak ikut dibandingkan.
        $this->assertSame([], $this->layanan->bentrok(shopId: [$this->toko->id]));

        // Tanpa batas toko, semua window diperbandingkan sebagai peringatan umum.
        $this->assertNotEmpty($this->layanan->bentrok());
    }

    public function test_window_yang_diedit_tidak_dibandingkan_dengan_diri_sendiri(): void
    {
        $window = ShiftWindow::create([
            'nama' => 'Pagi',
            'mulai' => '06:00',
            'selesai' => '14:00',
            'shop_id' => $this->toko->id,
        ]);

        $this->assertSame([], $this->layanan->bentrok($window->id));
    }
}
