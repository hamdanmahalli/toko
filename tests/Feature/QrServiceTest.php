<?php

namespace Tests\Feature;

use App\Enums\AbsenGagalReason;
use App\Exceptions\AbsenException;
use App\Models\Employee;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrServiceTest extends TestCase
{
    use RefreshDatabase;

    private QrService $qr;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qr = app(QrService::class);
        $this->employee = Employee::factory()->create();
    }

    public function test_token_memiliki_format_yang_diharapkan(): void
    {
        $token = $this->qr->token($this->employee);

        $this->assertMatchesRegularExpression(
            '/^ATOKO:'.$this->employee->id.':1:[a-f0-9]{16}$/',
            $token,
        );
    }

    public function test_token_valid_menghasilkan_karyawan_yang_benar(): void
    {
        $employee = $this->qr->cariKaryawan($this->qr->token($this->employee));

        $this->assertNotNull($employee);
        $this->assertSame($this->employee->id, $employee->id);
    }

    public function test_kartunya_dipalsukan_tidak_diterima(): void
    {
        // tanda tangan dikarang
        $palsu = 'ATOKO:'.$this->employee->id.':1:'.str_repeat('0', 16);

        $this->assertNull($this->qr->cariKaryawan($palsu));
    }

    public function test_id_karyawan_lain_yang_dicampur_tidak_diterima(): void
    {
        $token = $this->qr->token($this->employee);
        $bagian = explode(':', $token);

        // ganti id tapi tanda tangan milik karyawan asli
        $dipalsukan = 'ATOKO:9999:'.$bagian[2].':'.$bagian[3];

        $this->assertNull($this->qr->cariKaryawan($dipalsukan));
    }

    public function test_rotasi_qr_membatalkan_kartu_lama(): void
    {
        $tokenLama = $this->qr->token($this->employee);

        $this->assertNotNull($this->qr->cariKaryawan($tokenLama));

        $this->employee->rotateQr();

        $this->assertNull($this->qr->cariKaryawan($tokenLama), 'kartu lama harus tidak berlaku');
        $this->assertNotNull($this->qr->cariKaryawan($this->qr->token($this->employee->fresh())));
    }

    public function test_format_tak_sah_ditolak(): void
    {
        foreach (['', 'abc', 'ATOKO:1', 'ATOKO:a:b:c', 'LAIN:1:1:abcd'] as $token) {
            $this->assertNull($this->qr->cariKaryawan($token), "token '{$token}' seharusnya ditolak");
        }
    }

    public function test_wajib_karyawan_melempar_exception(): void
    {
        $this->expectException(AbsenException::class);

        $this->qr->wajibKaryawan('ATOKO:1:1:'.str_repeat('0', 16));
    }

    public function test_wajib_karyawan_menyertakan_alasan(): void
    {
        try {
            $this->qr->wajibKaryawan('ATOKO:1:1:'.str_repeat('0', 16));
            $this->fail('harusnya melempar exception');
        } catch (AbsenException $e) {
            $this->assertSame(AbsenGagalReason::QrTidakValid, $e->reason);
        }
    }

    public function test_svg_dan_data_uri_terhasilkan(): void
    {
        $svg = $this->qr->svg($this->employee, 200);

        $this->assertStringContainsString('<svg', $svg);

        $uri = $this->qr->dataUri($this->employee, 200);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
    }

    public function test_token_berbeda_untuk_karyawan_berbeda(): void
    {
        $employeeLain = Employee::factory()->create();

        $this->assertNotSame($this->qr->token($this->employee), $this->qr->token($employeeLain));
    }
}
