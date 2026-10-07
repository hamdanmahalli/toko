<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SatuPerangkatTest extends TestCase
{
    use RefreshDatabase;

    protected const PERMISSION = ['dashboard.lihat', 'absen.catat'];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach (self::PERMISSION as $nama) {
            Permission::create(['name' => $nama, 'guard_name' => 'web']);
        }
    }

    private function karyawan(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'karyawan', 'guard_name' => 'web']);
        $role->givePermissionTo(self::PERMISSION);
        $user->assignRole($role);

        // Akun karyawan nyata selalu tertaut ke data karyawan.
        Employee::factory()->denganAkun($user)->create();

        return $user;
    }

    public function test_akun_tanpa_data_karyawan_dan_tanpa_akses_admin_ditolak(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'karyawan', 'guard_name' => 'web']);
        $role->givePermissionTo(self::PERMISSION);
        $user->assignRole($role);

        $this->actingAs($user)->get('/beranda')->assertForbidden();
    }

    public function test_sesi_pertama_menjadi_pemilik_sesi_aktif(): void
    {
        $user = $this->karyawan();

        $this->actingAs($user)->get('/beranda')->assertOk();

        $user->refresh();

        $this->assertNotNull($user->active_session_id);
    }

    public function test_perangkat_kedua_diusir(): void
    {
        $user = $this->karyawan();

        // perangkat pertama sudah露面 dan menyimap session id miliknya
        $this->actingAs($user)->get('/beranda');

        // perangkat kedua memakai session id berbeda
        $user->refresh();
        $idlama = $user->active_session_id;
        $user->forceFill(['active_session_id' => $idlama.'-lain'])->save();

        $this->actingAs($user)->get('/beranda')->assertRedirect(route('masuk'));
        $this->assertGuest();
    }

    public function test_akun_yang_dinonaktifkan_langsung_keluar(): void
    {
        $user = $this->karyawan();

        $this->actingAs($user)->get('/beranda');

        $user->forceFill(['aktif' => false])->save();

        $this->actingAs($user)->get('/beranda')->assertRedirect(route('masuk'));
        $this->assertGuest();
    }
}
