<?php

namespace Tests\Feature;

use App\Enums\PayrollStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\PayrollRun;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Factory yang dirujuk HasFactory pada model hidup harus benar-benar ada.
 * Model yang menyebut factory tapi filetanya hilang akan fatal begitu
 *>::factory() dipanggil, jadi presence-nya dijaga lewat test.
 */
class FactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_factory_berfungsi(): void
    {
        $position = Position::factory()->create();

        $this->assertTrue($position->exists);
        $this->assertTrue($position->aktif);
        $this->assertFalse($position->wajibTemplate());

        $this->assertTrue(Position::factory()->wajibTemplate()->create()->wajibTemplate());
        $this->assertFalse(Position::factory()->tidakAktif()->create()->aktif);
    }

    public function test_employee_factory_dan_position_bisa_dipakai_bersamaan(): void
    {
        // EmployeeFactory::manajer() memanggil Position::firstOrCreate, jadi
        // keduanya harus bisa hidup berdampingan tanpa bentrok kode unik.
        $karyawan = Employee::factory()->manajer()->create();

        $this->assertTrue($karyawan->position->wajibTemplate());
        $this->assertSame('MANAGER', $karyawan->position->kode);
    }

    public function test_payroll_run_factory_berfungsi(): void
    {
        $run = PayrollRun::factory()->create();

        $this->assertTrue($run->exists);
        $this->assertSame(PayrollStatus::Draft, $run->status);
        $this->assertTrue($run->periode->isStartOfMonth(), 'Periode disimpan sebagai tanggal pertama bulan.');
        $this->assertFalse($run->isLocked());
    }

    public function test_payroll_run_final_mengunci_dan_mencatat_pembuat(): void
    {
        $admin = User::factory()->create();

        $run = PayrollRun::factory()->final($admin)->create();

        $this->assertSame(PayrollStatus::Final, $run->status);
        $this->assertTrue($run->isLocked());
        $this->assertSame($admin->id, $run->generated_by);
        $this->assertNotNull($run->generated_at);
    }

    public function test_payroll_detail_factory_mengikuti_toko_karyawan(): void
    {
        $karyawan = Employee::factory()->create();
        $run = PayrollRun::factory()->create();

        $detail = PayrollDetail::factory()->untuk($karyawan, $run)->create();

        $this->assertTrue($detail->exists);
        $this->assertSame($karyawan->shop_id, $detail->shop_id, 'Toko detail mengikuti toko karyawan.');
        $this->assertSame($run->id, $detail->payroll_run_id);
    }

    public function test_audit_log_factory_berfungsi(): void
    {
        $log = AuditLog::factory()->untuk('employee', 7)->create();

        $this->assertTrue($log->exists);
        $this->assertSame('employee', $log->entitas);
        $this->assertSame('7', $log->entitas_id);
        $this->assertNull($log->user_id);
    }

    public function test_scope_untuk_entitas_mencari_log_yang_benar(): void
    {
        $employee = Employee::factory()->create();

        AuditLog::factory()->untuk('employee', $employee->id)->create();
        AuditLog::factory()->create(['entitas' => 'shift_window', 'entitas_id' => '99']);

        $found = AuditLog::untukEntitas('employee', $employee->id)->get();

        $this->assertCount(1, $found);
        $this->assertSame('employee', $found->first()->entitas);
    }
}
