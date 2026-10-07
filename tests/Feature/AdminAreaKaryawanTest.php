<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Area admin hanya untuk akun pengelola.
 *
 * Peran `karyawan` memegang permission yang sama dengan admin, jadi tanpa
 * middleware `tolak_karyawan` akun karyawan cukup mengetik `/admin` untuk
 * sampai ke sana. Cakupan toko sudah menutup kebocoran data, tetapi halamannya
 * sendiri tidak boleh terbuka.
 */
class AdminAreaKaryawanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        // Permission sengaja dibuat sama persis untuk karyawan dan admin supaya
        // tes ini benar-benar menguji middleware, bukan sekadar permission.
        foreach (['dashboard.lihat', 'pengajuan.lihat', 'toko.lihat', 'absen.lihat'] as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
        }

        foreach (['pemilik', 'supervisor', 'karyawan'] as $nama) {
            Role::findOrCreate($nama, 'web')->syncPermissions([
                'dashboard.lihat',
                'pengajuan.lihat',
                'toko.lihat',
                'absen.lihat',
            ]);
        }
    }

    private function karyawan(): User
    {
        $toko = Shop::factory()->create();
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('karyawan', 'web'));

        Employee::factory()->create(['user_id' => $user->id, 'shop_id' => $toko->id]);

        return $user->fresh();
    }

    private function pemilik(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('pemilik', 'web'));

        return $user;
    }

    public function test_karyawan_ditolak_dari_dasbor_admin(): void
    {
        $this->actingAs($this->karyawan())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_karyawan_ditolak_dari_halaman_pengajuan_admin(): void
    {
        $this->actingAs($this->karyawan())
            ->get('/admin/pengajuan')
            ->assertForbidden();
    }

    public function test_karyawan_ditolak_dari_halaman_toko(): void
    {
        $this->actingAs($this->karyawan())
            ->get('/admin/toko')
            ->assertForbidden();
    }

    public function test_karyawan_tidak_bisa_menulis_perubahan_toko(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->karyawan())
            ->put("/admin/toko/{$toko->id}", ['nama' => 'Diretas'])
            ->assertForbidden();

        $this->assertDatabaseHas('shops', ['id' => $toko->id, 'nama' => $toko->nama]);
    }

    // Setiap halaman dipisah tesnya sendiri: dua request beruntun memakai akun
    // yang sama akan saling mengeluarkan lewat `MenegakkanSatuPerangkat`, bukan
    // karena middleware admin.
    public function test_karyawan_masih_bisa_masuk_ke_beranda(): void
    {
        $this->actingAs($this->karyawan())->get('/beranda')->assertOk();
    }

    public function test_karyawan_masih_bisa_melihat_kartu_qr(): void
    {
        $this->actingAs($this->karyawan())->get('/saya-qr')->assertOk();
    }

    public function test_karyawan_masih_bisa_melihat_riwayat(): void
    {
        $this->actingAs($this->karyawan())->get('/riwayat')->assertOk();
    }

    public function test_pemilik_tanpa_data_karyawan_tetap_bisa_masuk(): void
    {
        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk();
    }

    public function test_akun_bukan_karyawan_tanpa_peran_tidak_bisa_masuk(): void
    {
        // Tanpa role apa pun, middleware tidak intervene; penolakan tetap
        // datang dari permission seperti biasa.
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
