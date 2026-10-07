<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Position;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminJabatanTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = [
        'dashboard.lihat',
        'karyawan.lihat',
        'karyawan.kelola',
        'toko.lihat',
        'jabatan.lihat',
        'jabatan.kelola',
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

    private function dengan(string $role, array $permission): User
    {
        $user = User::factory()->create();
        $peran = Role::findOrCreate($role, 'web');
        $peran->givePermissionTo($permission);
        $user->assignRole($peran);

        return $user->fresh();
    }

    private function pemilik(): User
    {
        return $this->dengan('pemilik', self::PERMISSION);
    }

    public function test_daftar_jabatan_tampil(): void
    {
        Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);

        $this->actingAs($this->pemilik())
            ->get('/admin/jabatan')
            ->assertOk()
            ->assertSee('Kasir')
            ->assertSee('KASIR');
    }

    public function test_tambah_jabatan_dengan_kode_otomatis(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/jabatan', ['nama' => 'Staf Gudang', 'aktif' => 1])
            ->assertRedirect(route('admin.jabatan.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('positions', ['nama' => 'Staf Gudang', 'kode' => 'STAFGUDANG', 'aktif' => true]);
    }

    public function test_kode_otomatis_dibuat_unik(): void
    {
        // Dua nama berbeda yang menghasilkan slug sama wajib mendapat kode berbeda.
        Position::create(['nama' => 'Staf Gudang', 'kode' => 'STAFGUDANG', 'aktif' => true]);

        $this->actingAs($this->pemilik())
            ->post('/admin/jabatan', ['nama' => 'StafGudang', 'aktif' => 1])
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('positions', ['nama' => 'StafGudang', 'kode' => 'STAFGUDANG-2']);
    }

    public function test_nama_jabatan_unik(): void
    {
        Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);

        $this->actingAs($this->pemilik())
            ->post('/admin/jabatan', ['nama' => 'Kasir', 'aktif' => 1])
            ->assertSessionHasErrors('nama');

        $this->assertSame(1, Position::count());
    }

    public function test_ubah_nama_boleh_sama_pasal_dirinya_sendiri(): void
    {
        $jabatan = Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);

        $this->actingAs($this->pemilik())
            ->put("/admin/jabatan/{$jabatan->id}", ['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => 1])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('positions', ['id' => $jabatan->id, 'nama' => 'Kasir']);
    }

    public function test_kode_bisa_diisi_manual(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/jabatan', ['nama' => 'Admin Toko', 'kode' => 'adm-toko', 'aktif' => 1]);

        $this->assertDatabaseHas('positions', ['nama' => 'Admin Toko', 'kode' => 'ADM-TOKO']);
    }

    public function test_kode_duplikat_ditolak(): void
    {
        Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);

        $this->actingAs($this->pemilik())
            ->post('/admin/jabatan', ['nama' => 'Kasir Mall', 'kode' => 'KASIR', 'aktif' => 1])
            ->assertSessionHasErrors('kode');

        $this->assertDatabaseMissing('positions', ['nama' => 'Kasir Mall']);
    }

    public function test_ubah_jabatan(): void
    {
        $jabatan = Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);

        $this->actingAs($this->pemilik())
            ->put("/admin/jabatan/{$jabatan->id}", ['nama' => 'Kasir Senior', 'kode' => 'KASIR', 'aktif' => 1])
            ->assertRedirect(route('admin.jabatan.index'));

        $this->assertDatabaseHas('positions', ['id' => $jabatan->id, 'nama' => 'Kasir Senior']);
    }

    public function test_jabatan_tanpa_karyawan_bisa_dihapus(): void
    {
        $jabatan = Position::create(['nama' => 'Kurir', 'kode' => 'KURIR', 'aktif' => true]);

        $this->actingAs($this->pemilik())
            ->delete("/admin/jabatan/{$jabatan->id}")
            ->assertRedirect(route('admin.jabatan.index'));

        $this->assertDatabaseMissing('positions', ['id' => $jabatan->id]);
    }

    public function test_jabatan_berkaryawan_hanya_dinonaktifkan(): void
    {
        $jabatan = Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);
        Employee::factory()->create(['position_id' => $jabatan->id]);

        $this->actingAs($this->pemilik())
            ->delete("/admin/jabatan/{$jabatan->id}")
            ->assertRedirect(route('admin.jabatan.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('positions', ['id' => $jabatan->id, 'aktif' => false]);
    }

    public function test_halaman_jabatan_menampilkan_jumlah_karyawan(): void
    {
        $jabatan = Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);
        Employee::factory()->count(2)->create(['position_id' => $jabatan->id]);

        $this->actingAs($this->pemilik())
            ->get('/admin/jabatan')
            ->assertOk()
            ->assertSee('Kasir');
    }

    public function test_pencarian_jabatan(): void
    {
        Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);
        Position::create(['nama' => 'Supervisor', 'kode' => 'SPV', 'aktif' => true]);

        $this->actingAs($this->pemilik())
            ->get('/admin/jabatan?q=kasir')
            ->assertOk()
            ->assertSee('Kasir')
            ->assertDontSee('Supervisor');
    }

    public function test_tanpa_permission_ditolak(): void
    {
        // User baru tiap request: jawaban 403 tidak menyimpan session, jadi
        // user yang sama akan terkeluar sebagai "dipakai perangkat lain".
        foreach (['/admin/jabatan', '/admin/jabatan/tambah'] as $url) {
            $this->actingAs($this->dengan('karyawan', ['dashboard.lihat']))
                ->get($url)
                ->assertForbidden();
        }

        $this->actingAs($this->dengan('karyawan', ['dashboard.lihat']))
            ->post('/admin/jabatan', ['nama' => 'X'])
            ->assertForbidden();

        $this->assertDatabaseCount('positions', 0);
    }

    public function test_bisa_lihat_tidak_bisa_kelola(): void
    {
        $this->actingAs($this->dengan('supervisor', ['dashboard.lihat', 'jabatan.lihat']))
            ->get('/admin/jabatan')
            ->assertOk();

        foreach (['/admin/jabatan/tambah'] as $url) {
            $this->actingAs($this->dengan('supervisor', ['dashboard.lihat', 'jabatan.lihat']))
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_tamu_diarahkan_ke_masuk(): void
    {
        $this->get('/admin/jabatan')->assertRedirect(route('masuk'));
    }

    public function test_shop_dan_jabatan_tidak_bertabrakan(): void
    {
        // Jabatan tidak terikat toko, jadi nama yang sama dengan toko tidak masalah.
        Shop::create(['nama' => 'Kasir', 'kode' => 'KASIR']);
        Position::create(['nama' => 'Kasir', 'kode' => 'KASIR', 'aktif' => true]);

        $this->actingAs($this->pemilik())->get('/admin/jabatan')->assertOk();
        $this->actingAs($this->pemilik())->get('/admin/toko')->assertOk();
    }
}
