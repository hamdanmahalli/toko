<?php

namespace Tests\Unit;

use App\Enums\AbsenMasukStatus;
use App\Enums\AbsenMethod;
use App\Enums\AbsenPulangStatus;
use App\Enums\LeaveType;
use App\Enums\PayrollStatus;
use App\Enums\PayrollType;
use App\Enums\RequestStatus;
use App\Enums\ShiftScope;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Helper options()/values() sempat memakai array_column() pada objek enum,
 * yang selalu menghasilkan array kosong sehingga dropdown di form jadi kosong.
 */
class EnumOptionsTest extends TestCase
{
    /** @return array<string, array{0: class-string}> */
    public static function enumList(): array
    {
        return [
            'LeaveType' => [LeaveType::class],
            'RequestStatus' => [RequestStatus::class],
            'AbsenMethod' => [AbsenMethod::class],
            'AbsenMasukStatus' => [AbsenMasukStatus::class],
            'AbsenPulangStatus' => [AbsenPulangStatus::class],
            'PayrollStatus' => [PayrollStatus::class],
            'PayrollType' => [PayrollType::class],
            'ShiftScope' => [ShiftScope::class],
        ];
    }

    #[DataProvider('enumList')]
    public function test_options_tidak_kosong(string $enum): void
    {
        $options = $enum::options();

        $this->assertNotEmpty($options, $enum.'::options() harus berisi minimal satu pilihan.');

        foreach ($options as $value => $label) {
            $this->assertIsString($value);
            $this->assertNotSame('', $label, 'Label di '.$enum.' tidak boleh kosong.');
        }
    }

    /** Hanya enum yang punya values() yang diuji di sini. */
    #[DataProvider('enumList')]
    public function test_values_tidak_kosong(string $enum): void
    {
        if (! method_exists($enum, 'values')) {
            $this->assertTrue(true);

            return;
        }

        $values = $enum::values();

        $this->assertNotEmpty($values, $enum.'::values() harus berisi minimal satu nilai.');

        foreach ($values as $value) {
            $this->assertIsString($value);
            $this->assertNotSame('', $value);
        }
    }

    public function test_key_options_sama_dengan_values(): void
    {
        $this->assertSame(LeaveType::values(), array_keys(LeaveType::options()));
    }

    public function test_semua_kasus_ada_di_options(): void
    {
        $this->assertCount(count(LeaveType::cases()), LeaveType::options());
        $this->assertCount(count(RequestStatus::cases()), RequestStatus::options());
        $this->assertCount(count(ShiftScope::cases()), ShiftScope::options());
    }
}
