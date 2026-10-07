<?php

namespace Tests\Feature;

use App\Enums\ShiftScope;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Setting;
use App\Models\ShiftTemplate;
use App\Models\Shop;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShiftService $shift;

    private Shop $toko;

    private Shop $tokoLain;

    private Employee $employee;

    /** 2026-10-05 = Senin (dayOfWeek 1). */
    private const SENIN = '2026-10-05';

    protected function setUp(): void
    {
        parent::setUp();

        $this->shift = app(ShiftService::class);
        $this->toko = Shop::factory()->create();
        $this->tokoLain = Shop::factory()->create();
        $this->employee = Employee::factory()->create(['shop_id' => $this->toko->id]);
    }

    public function test_tanpa_template_falls_back_ke_setting(): void
    {
        Setting::simpan([
            'umum.jam_masuk' => '07:00',
            'umum.batas_telat' => '07:45',
            'umum.jam_pulang' => '16:00',
        ]);

        $slot = $this->shift->slotUntuk($this->employee, Carbon::parse(self::SENIN));

        $this->assertNotNull($slot);
        $this->assertSame('07:00', $slot->jam_masuk->format('H:i'));
        $this->assertSame('16:00', $slot->jam_pulang->format('H:i'));
    }

    public function test_template_global_dipakai(): void
    {
        ShiftTemplate::factory()->denganSlot([1], '08:00', '08:15', '17:00')
            ->create(['scope' => ShiftScope::Global]);

        $slot = $this->shift->slotUntuk($this->employee, Carbon::parse(self::SENIN));

        $this->assertSame('08:00', $slot->jam_masuk->format('H:i'));
    }

    public function test_template_toko_menang_atas_global(): void
    {
        ShiftTemplate::factory()->denganSlot([1], '06:00', '06:15', '14:00')
            ->create(['scope' => ShiftScope::Global, 'nama' => 'Global']);

        ShiftTemplate::factory()->denganSlot([1], '09:00', '09:45', '18:00')
            ->create(['scope' => ShiftScope::Toko, 'shop_id' => $this->toko->id, 'nama' => 'Toko']);

        $slot = $this->shift->slotUntuk($this->employee, Carbon::parse(self::SENIN));

        $this->assertSame('09:00', $slot->jam_masuk->format('H:i'), 'template toko harus menang');
    }

    public function test_template_toko_lain_diabaikan(): void
    {
        ShiftTemplate::factory()->denganSlot([1], '08:00', '08:15', '17:00')
            ->create(['scope' => ShiftScope::Global, 'nama' => 'Global']);

        ShiftTemplate::factory()->denganSlot([1], '11:00', '11:45', '20:00')
            ->create(['scope' => ShiftScope::Toko, 'shop_id' => $this->tokoLain->id, 'nama' => 'Toko Lain']);

        $slot = $this->shift->slotUntuk($this->employee, Carbon::parse(self::SENIN));

        $this->assertSame('08:00', $slot->jam_masuk->format('H:i'), 'hanya boleh memakai template global');
    }

    public function test_penugasan_individual_menang_atas_semua(): void
    {
        ShiftTemplate::factory()->denganSlot([1], '06:00', '06:15', '14:00')
            ->create(['scope' => ShiftScope::Global, 'nama' => 'Global']);

        ShiftTemplate::factory()->denganSlot([1], '09:00', '09:45', '18:00')
            ->create(['scope' => ShiftScope::Toko, 'shop_id' => $this->toko->id, 'nama' => 'Toko']);

        $khusus = ShiftTemplate::factory()->denganSlot([1], '13:00', '13:30', '22:00')
            ->create(['scope' => ShiftScope::Individual, 'nama' => 'Khusus']);

        EmployeeShift::create([
            'employee_id' => $this->employee->id,
            'shift_template_id' => $khusus->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);

        $slot = $this->shift->slotUntuk($this->employee, Carbon::parse(self::SENIN));

        $this->assertSame('13:00', $slot->jam_masuk->format('H:i'));
    }

    public function test_penugasan_dengan_tanggal_berlaku_di_masa_depan_belum_dipakai(): void
    {
        ShiftTemplate::factory()->denganSlot([1], '06:00', '06:15', '14:00')
            ->create(['scope' => ShiftScope::Global, 'nama' => 'Global']);

        $khusus = ShiftTemplate::factory()->denganSlot([1], '13:00', '13:30', '22:00')
            ->create(['scope' => ShiftScope::Individual, 'nama' => 'Khusus']);

        EmployeeShift::create([
            'employee_id' => $this->employee->id,
            'shift_template_id' => $khusus->id,
            'mulai_berlaku' => '2027-01-01',
            'aktif' => true,
        ]);

        $slot = $this->shift->slotUntuk($this->employee, Carbon::parse(self::SENIN));

        $this->assertSame('06:00', $slot->jam_masuk->format('H:i'));
    }

    public function test_hari_tanpa_slot_berhasil_null(): void
    {
        ShiftTemplate::factory()->denganSlot([1], '08:00', '08:15', '17:00')
            ->create(['scope' => ShiftScope::Global]);

        $minggu = Carbon::parse(self::SENIN)->next(Carbon::SUNDAY);

        $this->assertNull($this->shift->slotUntuk($this->employee, $minggu));
    }

    public function test_slot_tidak_aktif_dianggap_hari_libur(): void
    {
        $template = ShiftTemplate::factory()->denganSlot([1], '08:00', '08:15', '17:00')
            ->create(['scope' => ShiftScope::Global]);

        $template->slots()->update(['aktif' => false]);

        $this->assertNull($this->shift->slotUntuk($this->employee, Carbon::parse(self::SENIN)));
    }
}
