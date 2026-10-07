<?php

namespace Tests\Feature;

use App\Enums\AbsenMethod;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private function logger(?User $user = null, string $ip = '127.0.0.1', ?string $agent = 'Uji'): AuditLogger
    {
        $request = Request::create('/uji', 'POST', server: [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $agent,
        ]);

        if ($user !== null) {
            $request->setUserResolver(fn () => $user);
        }

        return new AuditLogger($request);
    }

    public function test_mencatat_aksi_dengan_pengguna_saat_ini(): void
    {
        $user = User::factory()->create();

        $this->logger($user)->catat('karyawan.ubah');

        $log = AuditLog::sole();

        $this->assertSame('karyawan.ubah', $log->aksi);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('127.0.0.1', $log->ip);
        $this->assertSame('Uji', $log->user_agent);
    }

    public function test_aksi_tanpa_login_tetap_tercatat(): void
    {
        $this->logger()->catat('presensi.pindai');

        $log = AuditLog::sole();

        $this->assertNull($log->user_id, 'Perangkat presensi tidak punya akun, tapi jejaknya tetap harus ada.');
        $this->assertSame('presensi.pindai', $log->aksi);
    }

    public function test_entitas_diisi_dari_model(): void
    {
        $employee = Employee::factory()->create();

        $this->logger()->catat('employee.ubah', $employee);

        $log = AuditLog::sole();

        $this->assertSame('employee', $log->entitas);
        $this->assertSame((string) $employee->id, $log->entitas_id);
    }

    public function test_id_entitas_bisa_diisi_langsung(): void
    {
        $this->logger()->catat('presensi.pindai', null, [], 'presensi-abc');

        $this->assertSame('presensi-abc', AuditLog::sole()->entitas_id);
    }

    public function test_payload_bersarang_dan_tipe_kompleks_aman(): void
    {
        $employee = Employee::factory()->create();

        $this->logger()->catat('presensi.pindai', null, [
            'token' => 'ATOKO:1:1:abc',
            'jumlah' => 3,
            'benar' => true,
            'kosong' => null,
            'waktu' => Carbon::parse('2026-10-05 08:00'),
            'karyawan' => $employee,
            'dalam' => ['a' => 1, 'b' => ['c' => 2]],
            'tidakBisa' => fn () => 'nilai',
        ]);

        $payload = AuditLog::sole()->payload;

        $this->assertSame('ATOKO:1:1:abc', $payload['token']);
        $this->assertSame(3, $payload['jumlah']);
        $this->assertTrue($payload['benar']);
        $this->assertNull($payload['kosong']);
        $this->assertSame('2026-10-05T08:00:00', substr($payload['waktu'], 0, 19));

        // jsonb tidak menjamin urutan key, jadi tiap key dicek terpisah.
        $this->assertSame('Employee', $payload['karyawan']['model']);
        $this->assertSame($employee->id, $payload['karyawan']['id']);

        $this->assertSame(['a' => 1, 'b' => ['c' => 2]], $payload['dalam']);
        $this->assertNull($payload['tidakBisa'], 'Closure tidak bisa jadi jsonb, jadi dibuang.');
    }

    public function test_enum_disimpan_sebagai_nilai_skalarnya(): void
    {
        $this->logger()->catat('absen.masuk', null, ['metode' => AbsenMethod::Presensi]);

        $this->assertSame('presensi', AuditLog::sole()->payload['metode']);
    }

    public function test_kegagalan_audit_tidak_menggagalkan_aksi(): void
    {
        // Kegagalan disimulasikan lewat event model, bukan lewat insert yang
        // gagal: di dalam transaksi test, satu perintah yang ditolak
        // PostgreSQL akan membatalkan seluruh transaksi berikutnya.
        AuditLog::creating(fn () => throw new \RuntimeException('tabel audit mati'));

        // Tidak ada exception yang boleh lolos ke pemanggil.
        $this->logger()->catat('karyawan.ubah');

        $this->assertTrue(AuditLog::query()->doesntExist());
    }

    public function test_catat_gagal_menandai_berhasil_false(): void
    {
        $this->logger()->catatGagal('presensi.pindai', 'qr_tidak_valid', ['token' => 'ATOKO:9:1:zz']);

        $payload = AuditLog::sole()->payload;

        $this->assertFalse($payload['berhasil']);
        $this->assertSame('qr_tidak_valid', $payload['alasan']);
        $this->assertSame('ATOKO:9:1:zz', $payload['token']);
    }

    public function test_catat_absensi_mencatat_rincian_sesi(): void
    {
        $employee = Employee::factory()->create();

        $absensi = $employee->attendances()->create([
            'shop_id' => $employee->shop_id,
            'tanggal' => '2026-10-05',
            'sesi' => 2,
            'jam_masuk' => '08:00',
            'durasi_menit' => 240,
            'metode' => AbsenMethod::Presensi->value,
        ]);

        $this->logger()->catatAbsensi($absensi, 'masuk', presensi: 'presensi-1');

        $log = AuditLog::sole();

        $this->assertSame('absen.masuk', $log->aksi);
        $this->assertSame('attendance', $log->entitas);
        $this->assertSame(2, $log->payload['sesi']);
        $this->assertSame('presensi', $log->payload['metode']);
        $this->assertSame(240, $log->payload['durasi_menit']);
        $this->assertSame('presensi-1', $log->payload['presensi']);
    }
}
