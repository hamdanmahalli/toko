<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Membaca riwayat sendiri dan mencatat absen adalah dua hak yang berbeda.
 * Sebelum dipisah, keduanya memakai `absen.catat`, jadi akun yang punya
 * `absen.lihat` (diberikan ke role karyawan) tidak pernah bisa dipakai, dan
 * akun yang tidak boleh mencatat tetap bisa membuka halaman baca.
 */
class IzinAbsenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Employee $employee;

    /**
     * @param  array<int, string>  $permissions
     */
    private function akun(array $permissions): User
    {
        foreach ($permissions as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
        }

        $role = Role::findOrCreate('uji', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create(['aktif' => true]);
        $user->assignRole($role);

        $this->user = $user;
        $this->employee = Employee::factory()->denganAkun($user)->create();

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();
    }

    public function test_akun_baca_bisa_membuka_riwayat_tanpa_izin_mencatat(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.lihat']);

        $this->actingAs($user)
            ->get('/riwayat')
            ->assertOk();
    }

    public function test_akun_bisa_membuka_kartu_qr_tanpa_izin_mencatat(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.lihat']);

        $this->actingAs($user)
            ->get('/saya-qr')
            ->assertOk()
            ->assertSee($this->employee->nama);
    }

    public function test_akun_baca_tidak_bisa_mencatat_absen(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.lihat']);

        $this->actingAs($user)
            ->post('/absen', [
                'arah' => 'masuk',
                'latitude' => $this->employee->shop->latitude,
                'longitude' => $this->employee->shop->longitude,
                'accuracy' => 12,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_akun_tanpa_izin_baca_tidak_bisa_membuka_riwayat(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.catat']);

        $this->actingAs($user)
            ->get('/riwayat')
            ->assertForbidden();
    }

    public function test_akun_tanpa_izin_baca_tidak_bisa_membuka_kartu_qr(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.catat']);

        $this->actingAs($user)
            ->get('/saya-qr')
            ->assertForbidden();
    }

    public function test_beranda_tidak_menampilkan_tombol_absen_bagi_akun_baca(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.lihat']);

        $this->actingAs($user)
            ->get('/beranda')
            ->assertOk()
            ->assertDontSee('absen-tombol', false)
            ->assertDontSee('Datang');
    }

    public function test_beranda_menampilkan_tombol_absen_bagi_akun_mencatat(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.catat']);

        $this->actingAs($user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('absen-tombol', false)
            ->assertSee('Datang');
    }

    public function test_menu_bawah_menyembunyikan_qr_dan_riwayat_bagi_akun_baca(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.catat']);

        $this->actingAs($user)
            ->get('/beranda')
            ->assertOk()
            ->assertDontSee(route('absen.riwayat'))
            ->assertDontSee(route('absen.qr'));
    }

    /**
     * Setiap request diuji dengan session id baru, jadi middleware satu
     * perangkat mengeluarkan akun yang `active_session_id`-nya masih terikat
     * ke request sebelumnya.
     */
    private function lanjut(string $url)
    {
        $this->user->forceFill(['active_session_id' => null])->save();

        return $this->actingAs($this->user)->get($url);
    }

    public function test_akun_yang_memiliki_keduanya_berfungsi_penuh(): void
    {
        $user = $this->akun(['dashboard.lihat', 'absen.catat', 'absen.lihat']);

        $this->actingAs($user)->get('/riwayat')->assertOk();
        $this->lanjut('/saya-qr')->assertOk();
        $this->lanjut('/beranda')->assertSee('Datang');
    }
}
