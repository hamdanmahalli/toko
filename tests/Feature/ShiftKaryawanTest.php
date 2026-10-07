<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use App\Models\Shop;
use App\Models\User;
use App\Services\ShiftResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ShiftKaryawanTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = [
        'dashboard.lihat',
        'toko.lihat',
        'karyawan.lihat',
        'karyawan.kelola',
        'shift.lihat',
        'shift.kelola',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach (self::PERMISSION as $nama) {
            Permission::create(['name' => $nama, 'guard_name' => 'web']);
        }

        Role::create(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    private function pemilik(): User
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('pemilik', 'web');
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);

        return $user->fresh();
    }

    /** @return array<string, mixed> */
    private function dataKaryawan(array $tambahan = []): array
    {
        $toko = $tambahan['shop'] ??= Shop::factory()->create();

        return array_merge([
            'nama' => 'Andi Pratama',
            'nip' => 'K-0001',
            'shop_id' => $toko->id,
            'tipe_payroll' => 'harian',
            'gaji_harian' => 150000,
            'aktif' => 1,
        ], array_diff_key($tambahan, ['shop' => true]));
    }

    public function test_karyawan_bisa_diberi_template_shift_saat_dibuat(): void
    {
        $template = ShiftTemplate::factory()->denganSlot()->create(['nama' => 'Shift Pagi']);

        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan([
                'shift_template_id' => $template->id,
                'shift_mulai_berlaku' => '2026-01-01',
            ]))
            ->assertRedirect(route('admin.karyawan.index'));

        $karyawan = Employee::first();

        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $karyawan->id,
            'shift_template_id' => $template->id,
            'mulai_berlaku' => '2026-01-01',
            'aktif' => true,
        ]);
    }

    public function test_template_shift_bisa_dikosongkan(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan(['shop' => $toko]))
            ->assertRedirect(route('admin.karyawan.index'));

        $this->assertDatabaseCount('employee_shifts', 0);
    }

    public function test_ganti_shift_menutup_penugasan_lama(): void
    {
        $toko = Shop::factory()->create();
        $lama = ShiftTemplate::factory()->create(['nama' => 'Shift Lama']);
        $baru = ShiftTemplate::factory()->create(['nama' => 'Shift Baru']);

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);
        $karyawan->pasangShift($lama->id, '2026-01-01');

        $this->actingAs($this->pemilik())
            ->put("/admin/karyawan/{$karyawan->id}", $this->dataKaryawan([
                'shop' => $toko,
                'nip' => $karyawan->nip,
                'shift_template_id' => $baru->id,
                'shift_mulai_berlaku' => '2026-02-01',
            ]))
            ->assertRedirect(route('admin.karyawan.index'));

        // Penugasan lama ditutup, bukan dihapus.
        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $karyawan->id,
            'shift_template_id' => $lama->id,
            'selesai_berlaku' => '2026-01-31',
            'aktif' => false,
        ]);

        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $karyawan->id,
            'shift_template_id' => $baru->id,
            'mulai_berlaku' => '2026-02-01',
            'aktif' => true,
        ]);

        // Hanya satu penugasan aktif.
        $this->assertSame(1, $karyawan->shiftAssignments()->where('aktif', true)->count());
    }

    public function test_memilih_shift_yang_sama_tidak_mengganti_penugasan(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::factory()->create();

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);
        $karyawan->pasangShift($template->id, '2026-01-01');

        $this->actingAs($this->pemilik())
            ->put("/admin/karyawan/{$karyawan->id}", $this->dataKaryawan([
                'shop' => $toko,
                'nip' => $karyawan->nip,
                'shift_template_id' => $template->id,
                'shift_mulai_berlaku' => '2026-03-01',
            ]));

        $this->assertSame(1, $karyawan->shiftAssignments()->count());
    }

    public function test_template_toko_lain_tidak_bisa_dipilih(): void
    {
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();
        $templateLain = ShiftTemplate::factory()->create(['shop_id' => $orangLain->id]);

        $karyawan = Employee::factory()->create(['shop_id' => $milik->id]);

        $this->actingAs($this->pemilik())
            ->put("/admin/karyawan/{$karyawan->id}", $this->dataKaryawan([
                'shop' => $milik,
                'nip' => $karyawan->nip,
                'shift_template_id' => $templateLain->id,
            ]))
            ->assertStatus(422);

        $this->assertDatabaseCount('employee_shifts', 0);
    }

    public function test_template_nonaktif_tidak_ditawarkan(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::factory()->create(['aktif' => false, 'nama' => 'Shift Lama Mati']);

        $this->actingAs($this->pemilik())
            ->get('/admin/karyawan/tambah')
            ->assertOk()
            ->assertDontSee('Shift Lama Mati');
    }

    public function test_halaman_karyawan_menampilkan_shift(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::factory()->create(['nama' => 'Shift Pagi']);

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id, 'nama' => 'Budi Santoso']);
        $karyawan->pasangShift($template->id);

        $this->actingAs($this->pemilik())
            ->get('/admin/karyawan')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Shift Pagi');
    }

    public function test_penugasan_aktif_lain_ikut_ditutup(): void
    {
        $toko = Shop::factory()->create();
        $pertama = ShiftTemplate::factory()->create(['nama' => 'Shift Pertama']);
        $kedua = ShiftTemplate::factory()->create(['nama' => 'Shift Kedua']);
        $ketiga = ShiftTemplate::factory()->create(['nama' => 'Shift Ketiga']);

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        // Penugasan berjalan sejak awal.
        $karyawan->pasangShift($pertama->id, '2026-01-01');

        // Data lama: penugasan terjadwal masih aktif dan belum ada tanggal selesai.
        $karyawan->shiftAssignments()->create([
            'shift_template_id' => $kedua->id,
            'mulai_berlaku' => '2026-05-01',
            'aktif' => true,
        ]);

        $this->assertSame(2, $karyawan->shiftAssignments()->where('aktif', true)->count());

        // Ganti penugasan berlaku 2026-03-01: jadwal 05-01 harus dibatalkan.
        $karyawan->pasangShift($ketiga->id, '2026-03-01');

        $this->assertSame(1, $karyawan->shiftAssignments()->where('aktif', true)->count());

        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $karyawan->id,
            'shift_template_id' => $ketiga->id,
            'mulai_berlaku' => '2026-03-01',
            'aktif' => true,
        ]);

        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $karyawan->id,
            'shift_template_id' => $kedua->id,
            'selesai_berlaku' => '2026-04-30',
            'aktif' => false,
        ]);
    }

    public function test_melepas_shift_menutup_penugasan_aktif(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::factory()->create(['nama' => 'Shift Pagi']);

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);
        $karyawan->pasangShift($template->id, '2026-01-01');

        $this->actingAs($this->pemilik())
            ->put("/admin/karyawan/{$karyawan->id}", $this->dataKaryawan([
                'shop' => $toko,
                'nip' => $karyawan->nip,
                'shift_template_id' => null,
                'shift_mulai_berlaku' => '2026-02-01',
            ]))
            ->assertRedirect(route('admin.karyawan.index'));

        $this->assertSame(0, $karyawan->shiftAssignments()->where('aktif', true)->count());
        $this->assertDatabaseHas('employee_shifts', [
            'employee_id' => $karyawan->id,
            'selesai_berlaku' => '2026-01-31',
            'aktif' => false,
        ]);
    }

    public function test_resolver_memakai_penugasan(): void
    {
        $toko = Shop::factory()->create();
        $global = ShiftTemplate::factory()->create(['nama' => 'Global']);
        $pilih = ShiftTemplate::factory()->create(['nama' => 'Pilihan']);

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);
        $karyawan->pasangShift($pilih->id, '2026-01-01');

        $resolver = app(ShiftResolver::class);

        $this->assertSame($pilih->id, $resolver->untuk($karyawan, '2026-02-01')->id);
    }

    public function test_resolver_jatuh_ke_template_global(): void
    {
        $toko = Shop::factory()->create();
        $global = ShiftTemplate::factory()->create(['nama' => 'Global']);

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        $resolver = app(ShiftResolver::class);

        $this->assertSame($global->id, $resolver->untuk($karyawan)->id);
    }

    public function test_resolver_jatuh_ke_template_toko(): void
    {
        $toko = Shop::factory()->create();
        $tokoLain = Shop::factory()->create();

        ShiftTemplate::factory()->create(['shop_id' => $tokoLain->id, 'nama' => 'Template Toko Lain']);
        $templateToko = ShiftTemplate::factory()->create(['shop_id' => $toko->id, 'nama' => 'Template Toko']);

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        $resolver = app(ShiftResolver::class);

        $this->assertSame($templateToko->id, $resolver->untuk($karyawan)->id);
    }

    public function test_resolver_null_bila_tidak_ada_template(): void
    {
        $karyawan = Employee::factory()->create();

        $this->assertNull(app(ShiftResolver::class)->untuk($karyawan));
    }

    public function test_resolver_pakai_slot_hari_yang_tepat(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::factory()->create(['nama' => 'Shift Pagi']);

        // 2026-01-05 adalah Senin (hari 1).
        ShiftSlot::factory()->create([
            'shift_template_id' => $template->id,
            'hari' => 1,
            'jam_masuk' => '08:00',
            'batas_telat' => '08:15',
        ]);

        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);
        $karyawan->pasangShift($template->id);

        $slot = app(ShiftResolver::class)->untuk($karyawan, '2026-01-05')->slotFor(1);

        $this->assertNotNull($slot);
        $this->assertSame('08:15', $slot->batas_telat->format('H:i'));
    }
}
