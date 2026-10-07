<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminTokoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach (['dashboard.lihat', 'toko.lihat', 'toko.kelola'] as $nama) {
            Permission::create(['name' => $nama, 'guard_name' => 'web']);
        }
    }

    private function pemilik(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'pemilik', 'guard_name' => 'web']);
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

    private function dataToko(array $tambahan = []): array
    {
        return array_merge([
            'nama' => 'Toko Baru',
            'kode' => 'TB-01',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meter' => 150,
            'zona_waktu' => 'Asia/Jakarta',
            'aktif' => 1,
        ], $tambahan);
    }

    public function test_pemilik_bisa_membuka_daftar_toko(): void
    {
        $this->actingAs($this->pemilik())
            ->get('/admin/toko')
            ->assertOk()
            ->assertSee('Belum ada toko');
    }

    public function test_karyawan_tidak_bisa_membuka_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'karyawan', 'guard_name' => 'web']));

        $this->actingAs($user)->get('/admin/toko')->assertForbidden();
    }

    public function test_bisa_menambah_toko(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/toko', $this->dataToko())
            ->assertRedirect(route('admin.toko.index'));

        $this->assertDatabaseHas('shops', ['nama' => 'Toko Baru', 'kode' => 'TB-01']);
    }

    public function test_kode_toko_unik(): void
    {
        Shop::factory()->create(['kode' => 'TB-01']);
        $pemilik = $this->pemilik();

        $this->actingAs($pemilik)
            ->post('/admin/toko', $this->dataToko())
            ->assertSessionHasErrors('kode');

        $this->assertSame(1, Shop::where('kode', 'TB-01')->count());
    }

    public function test_koordinat_diluar_jangkauan_ditolak(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/toko', $this->dataToko(['latitude' => 999]))
            ->assertSessionHasErrors('latitude');
    }

    public function test_zona_waktu_harus_valid(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/toko', $this->dataToko(['zona_waktu' => 'Bukan/Zona']))
            ->assertSessionHasErrors('zona_waktu');
    }

    public function test_bisa_mengubah_toko(): void
    {
        $toko = Shop::factory()->create(['nama' => 'Toko Lama']);

        $this->actingAs($this->pemilik())
            ->put("/admin/toko/{$toko->id}", $this->dataToko(['nama' => 'Toko Baru', 'kode' => $toko->kode]))
            ->assertRedirect(route('admin.toko.index'));

        $this->assertSame('Toko Baru', $toko->fresh()->nama);
    }

    public function test_login_presensi_disimpan_sebagai_hash(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/toko', $this->dataToko([
                'presensi_user' => 'PRESENSI-101',
                'presensi_password' => 'rahasia-presensi',
            ]))
            ->assertRedirect(route('admin.toko.index'));

        $toko = Shop::where('kode', 'TB-01')->firstOrFail();

        $this->assertSame('presensi-101', $toko->presensi_user, 'user dinormalisasi ke huruf kecil');
        $this->assertNotSame('rahasia-presensi', $toko->presensi_password);
        $this->assertTrue($toko->cocokkanPresensiLogin('presensi-101', 'rahasia-presensi'));
        $this->assertFalse($toko->cocokkanPresensiLogin('presensi-101', 'salah'));
    }

    public function test_presensi_belum_terisi_kredensial_ditandai_belum_siap(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/toko', $this->dataToko())
            ->assertRedirect(route('admin.toko.index'));

        $toko = Shop::where('kode', 'TB-01')->firstOrFail();

        $this->assertNull($toko->presensi_user);
        $this->assertNull($toko->presensi_password);
        $this->assertFalse($toko->presensiSiap());
        $this->assertFalse($toko->cocokkanPresensiLogin('apa-saja', 'apa-saja'));
    }

    public function test_password_presensi_kosong_artinya_tidak_diubah(): void
    {
        $toko = Shop::factory()->create([
            'presensi_user' => 'presensi-101',
            'presensi_password' => Shop::hashPresensiPassword('rahasia-presensi'),
        ]);

        $this->actingAs($this->pemilik())
            ->put("/admin/toko/{$toko->id}", $this->dataToko([
                'kode' => $toko->kode,
                'presensi_user' => $toko->presensi_user,
                'presensi_password' => '',
            ]))
            ->assertRedirect(route('admin.toko.index'));

        $toko->refresh();

        $this->assertTrue($toko->cocokkanPresensiLogin('presensi-101', 'rahasia-presensi'));
    }

    public function test_user_presensi_harus_unik_antar_toko(): void
    {
        Shop::factory()->create(['presensi_user' => 'presensi-101']);

        $this->actingAs($this->pemilik())
            ->post('/admin/toko', $this->dataToko(['presensi_user' => 'presensi-101']))
            ->assertSessionHasErrors('presensi_user');
    }

    public function test_user_presensi_yang_sama_boleh_diubah_tanpa_menabrak_sendiri(): void
    {
        $toko = Shop::factory()->create(['presensi_user' => 'presensi-101']);

        $this->actingAs($this->pemilik())
            ->put("/admin/toko/{$toko->id}", $this->dataToko([
                'kode' => $toko->kode,
                'presensi_user' => 'presensi-101',
            ]))
            ->assertRedirect(route('admin.toko.index'))
            ->assertSessionMissing('errors');

        $this->assertSame('presensi-101', $toko->fresh()->presensi_user);
    }

    public function test_password_presensi_terlalu_pendek_ditolak(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/toko', $this->dataToko([
                'presensi_user' => 'presensi-101',
                'presensi_password' => 'pendek',
            ]))
            ->assertSessionHasErrors('presensi_password');
    }

    public function test_hash_password_presensi_tidak_berubah_kali_lagi(): void
    {
        $sudah = Hash::make('rahasia-presensi');

        $this->assertSame($sudah, Shop::hashPresensiPassword($sudah));
        $this->assertNotSame('rahasia-presensi', Shop::hashPresensiPassword('rahasia-presensi'));
        $this->assertNull(Shop::hashPresensiPassword(''));
        $this->assertNull(Shop::hashPresensiPassword('   '));
        $this->assertNull(Shop::hashPresensiPassword(null));
    }

    public function test_kredensial_presensi_tidak_bocor_keluar_dari_model(): void
    {
        $toko = Shop::factory()->create([
            'presensi_user' => 'presensi-101',
            'presensi_password' => Hash::make('rahasia-presensi'),
        ]);

        $this->assertArrayNotHasKey('presensi_user', $toko->toArray());
        $this->assertArrayNotHasKey('presensi_password', $toko->toArray());
        $this->assertArrayNotHasKey('presensi_password', $toko->jsonSerialize());
    }

    public function test_menghapus_toko_hanya_menonaktifkan(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->delete("/admin/toko/{$toko->id}")
            ->assertRedirect(route('admin.toko.index'));

        $this->assertDatabaseHas('shops', ['id' => $toko->id, 'aktif' => false]);
    }

    public function test_supervisor_melihat_hanya_tokonya(): void
    {
        $milik = Shop::factory()->create(['nama' => 'Toko Milik']);
        Shop::factory()->create(['nama' => 'Toko Orang Lain']);

        $supervisor = $this->supervisor(User::factory()->create(), $milik);

        $this->actingAs($supervisor)
            ->get('/admin/toko')
            ->assertOk()
            ->assertSee('Toko Milik')
            ->assertDontSee('Toko Orang Lain');
    }

    public function test_supervisor_tidak_boleh_mengubah_toko_orang_lain(): void
    {
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();

        $supervisor = $this->supervisor(User::factory()->create(), $milik);

        $this->actingAs($supervisor)
            ->put("/admin/toko/{$orangLain->id}", $this->dataToko(['kode' => $orangLain->kode]))
            ->assertNotFound();

        $this->assertTrue($orangLain->fresh()->aktif);
    }

    public function test_supervisor_bisa_mengubah_tokonya_sendiri(): void
    {
        $milik = Shop::factory()->create(['nama' => 'Nama Lama']);

        $supervisor = $this->supervisor(User::factory()->create(), $milik);

        $this->actingAs($supervisor)
            ->put("/admin/toko/{$milik->id}", $this->dataToko([
                'nama' => 'Nama Baru',
                'kode' => $milik->kode,
            ]))
            ->assertRedirect(route('admin.toko.index'));

        $this->assertSame('Nama Baru', $milik->fresh()->nama);
    }

    public function test_pemilik_melihat_semua_toko(): void
    {
        Shop::factory()->create(['nama' => 'Toko Satu']);
        Shop::factory()->create(['nama' => 'Toko Dua']);

        $this->actingAs($this->pemilik())
            ->get('/admin/toko')
            ->assertOk()
            ->assertSee('Toko Satu')
            ->assertSee('Toko Dua');
    }
}
