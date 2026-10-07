<?php

namespace Tests\Feature;

use App\Enums\PayrollType;
use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminKaryawanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach (['dashboard.lihat', 'karyawan.lihat', 'karyawan.kelola'] as $nama) {
            Permission::create(['name' => $nama, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    private function pemilik(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'pemilik', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);

        return $user->fresh();
    }

    private function supervisor(User $user, Shop $toko): User
    {
        $role = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);
        $user->shops()->attach($toko);

        return $user->fresh();
    }

    private function dataKaryawan(Shop $toko, array $tambahan = []): array
    {
        return array_merge([
            'nama' => 'Budi Santoso',
            'nip' => 'K-100',
            'telepon' => '08123456789',
            'email' => 'budi@toko.test',
            'shop_id' => $toko->id,
            'tipe_payroll' => PayrollType::Harian->value,
            'gaji_harian' => 150000,
            'aktif' => 1,
        ], $tambahan);
    }

    public function test_pemilik_bisa_membuka_daftar_karyawan(): void
    {
        $this->actingAs($this->pemilik())
            ->get('/admin/karyawan')
            ->assertOk()
            ->assertSee('Belum ada karyawan yang cocok');
    }

    public function test_karyawan_biasa_tidak_bisa_buka_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('karyawan', 'web'));

        $this->actingAs($user)->get('/admin/karyawan')->assertForbidden();
    }

    public function test_bisa_menambah_karyawan_dengan_akun(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan($toko, [
                'buat_akun' => 1,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ]))
            ->assertRedirect(route('admin.karyawan.index'));

        $this->assertDatabaseHas('employees', ['nama' => 'Budi Santoso', 'shop_id' => $toko->id]);

        $user = User::where('email', 'budi@toko.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->aktif);
        $this->assertTrue($user->hasRole('karyawan'));
        $this->assertTrue(Hash::check('rahasia123', $user->password));

        $this->assertDatabaseHas('employees', [
            'user_id' => $user->id,
            'qr_version' => 1,
        ]);
    }

    public function test_bisa_menambah_karyawan_tanpa_akun(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan($toko))
            ->assertRedirect(route('admin.karyawan.index'));

        $karyawan = Employee::where('nip', 'K-100')->first();
        $this->assertNull($karyawan->user_id);
    }

    public function test_karyawan_baru_diizinkan_memakai_perangkat_presensi(): void
    {
        $toko = Shop::factory()->create();

        // Formulir menggirimkan checkbox tercentang secara bawaan, jadi
        // izin perangkat presensi tersimpan aktif untuk karyawan baru.
        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan($toko, ['boleh_presensi' => 1]))
            ->assertRedirect(route('admin.karyawan.index'));

        $this->assertDatabaseHas('employees', [
            'nip' => 'K-100',
            'boleh_presensi' => true,
        ]);
    }

    public function test_izin_perangkat_presensi_bisa_dicabut_dari_form(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        $this->actingAs($this->pemilik())
            ->put("/admin/karyawan/{$karyawan->id}", [
                'nama' => $karyawan->nama,
                'shop_id' => $toko->id,
                'tipe_payroll' => PayrollType::Harian->value,
                'aktif' => 1,
                // Checkbox `boleh_presensi` tidak dikirim = tidak diizinkan.
            ])
            ->assertRedirect(route('admin.karyawan.index'));

        $this->assertFalse($karyawan->fresh()->boleh_presensi);
    }

    public function test_izin_perangkat_presensi_dipertahankan_saat_ubah(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id, 'boleh_presensi' => true]);

        $this->actingAs($this->pemilik())
            ->put("/admin/karyawan/{$karyawan->id}", [
                'nama' => $karyawan->nama,
                'shop_id' => $toko->id,
                'tipe_payroll' => PayrollType::Harian->value,
                'aktif' => 1,
                'boleh_presensi' => 1,
            ])
            ->assertRedirect(route('admin.karyawan.index'));

        $this->assertTrue($karyawan->fresh()->boleh_presensi);
    }

    public function test_password_waajib_diisi_jika_membuat_akun(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan($toko, ['buat_akun' => 1]))
            ->assertSessionHasErrors('password');
    }

    public function test_nip_karyawan_unik(): void
    {
        $toko = Shop::factory()->create();

        // NIP sudah dipakai karyawan lain
        Employee::factory()->create(['shop_id' => $toko->id, 'nip' => 'K-100']);

        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan($toko))
            ->assertSessionHasErrors('nip');

        $this->assertSame(1, Employee::where('nip', 'K-100')->count());
    }

    public function test_email_akun_karyawan_unik(): void
    {
        $toko = Shop::factory()->create();

        Employee::factory()->denganAkun()->create([
            'shop_id' => $toko->id,
            'nip' => 'K-300',
            'email' => 'budi@toko.test',
        ]);

        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan($toko, [
                'buat_akun' => 1,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ]))
            ->assertSessionHasErrors('email');
    }

    public function test_hanya_toko_yang_valid_diterima(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/karyawan', $this->dataKaryawan(Shop::factory()->create(), ['shop_id' => 99999]))
            ->assertSessionHasErrors('shop_id');
    }

    public function test_mengubah_email_ikut_memperbarui_akun(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->denganAkun()->create(['shop_id' => $toko->id]);

        $this->actingAs($this->pemilik())
            ->put("/admin/karyawan/{$karyawan->id}", [
                'nama' => 'Nama Baru',
                'shop_id' => $toko->id,
                'email' => 'baru@toko.test',
                'tipe_payroll' => PayrollType::Harian->value,
                'aktif' => 1,
            ])
            ->assertRedirect(route('admin.karyawan.index'));

        $this->assertSame('baru@toko.test', $karyawan->user->fresh()->email);
    }

    public function test_membuat_akun_ketika_mengubah_karyawan(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->create([
            'shop_id' => $toko->id,
            'nip' => 'K-100',
            'email' => 'budi@toko.test',
        ]);

        $this->assertNull($karyawan->user_id);

        $this->actingAs($this->pemilik())
            ->put("/admin/karyawan/{$karyawan->id}", $this->dataKaryawan($toko, [
                'buat_akun' => 1,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ]))
            ->assertRedirect(route('admin.karyawan.index'));

        $user = User::where('email', 'budi@toko.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('karyawan'));
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertSame($user->id, $karyawan->fresh()->user_id);
    }

    public function test_menonaktifkan_karyawan_juga_menonaktifkan_akun(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->denganAkun()->create(['shop_id' => $toko->id]);

        $this->actingAs($this->pemilik())
            ->delete("/admin/karyawan/{$karyawan->id}")
            ->assertRedirect(route('admin.karyawan.index'));

        $this->assertFalse($karyawan->fresh()->aktif);
        $this->assertFalse($karyawan->user->fresh()->aktif);
        $this->assertNotNull($karyawan->fresh()->tanggal_keluar);
    }

    public function test_rotasi_qr_menolak_kartu_lama(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id, 'qr_version' => 1]);

        $qr = app(QrService::class);
        $lama = $qr->token($karyawan);
        $this->assertNotNull($qr->cariKaryawan($lama));

        $this->actingAs($this->pemilik())
            ->post("/admin/karyawan/{$karyawan->id}/rotasi-qr")
            ->assertRedirect();

        $this->assertSame(2, $karyawan->fresh()->qr_version);
        $this->assertNull($qr->cariKaryawan($lama));
    }

    public function test_reset_password_mengganti_hash(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->denganAkun()->create(['shop_id' => $toko->id]);

        $this->actingAs($this->pemilik())
            ->post("/admin/karyawan/{$karyawan->id}/password", [
                'password' => 'passwordbaru1',
                'password_confirmation' => 'passwordbaru1',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('passwordbaru1', $karyawan->user->fresh()->password));
    }

    public function test_halaman_qr_menampilkan_token(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        $this->actingAs($this->pemilik())
            ->get("/admin/karyawan/{$karyawan->id}/qr")
            ->assertOk()
            ->assertSee(app(QrService::class)->token($karyawan));
    }

    public function test_supervisor_hanya_melihat_karyawan_tokonya(): void
    {
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();

        Employee::factory()->create(['shop_id' => $milik->id, 'nama' => 'Karyawan Milik']);
        Employee::factory()->create(['shop_id' => $orangLain->id, 'nama' => 'Karyawan Orang Lain']);

        $supervisor = $this->supervisor(User::factory()->create(), $milik);

        $this->actingAs($supervisor)
            ->get('/admin/karyawan')
            ->assertOk()
            ->assertSee('Karyawan Milik')
            ->assertDontSee('Karyawan Orang Lain');
    }

    public function test_supervisor_tidak_boleh_menugaskan_ke_toko_orang_lain(): void
    {
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();

        $supervisor = $this->supervisor(User::factory()->create(), $milik);

        $this->actingAs($supervisor)
            ->post('/admin/karyawan', $this->dataKaryawan($milik, ['shop_id' => $orangLain->id]))
            ->assertForbidden();

        $this->assertDatabaseMissing('employees', ['nama' => 'Budi Santoso']);
    }

    public function test_supervisor_tidak_bisa_lihat_qr_karyawan_orang_lain(): void
    {
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();
        $karyawan = Employee::factory()->create(['shop_id' => $orangLain->id]);

        $supervisor = $this->supervisor(User::factory()->create(), $milik);

        $this->actingAs($supervisor)
            ->get("/admin/karyawan/{$karyawan->id}/qr")
            ->assertNotFound();
    }
}
