<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Halaman Pengguna adalah satu-satunya tempat tabel `user_shop` terisi.
 * Tanpa itu, supervisor tidak punya toko sama sekali dan tidak bisa memantau
 * apa pun, jadi penugasan di sini adalah bagian dari keamanan, bukan sekadar
 * fitur administer.
 */
class AdminPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = [
        'dashboard.lihat',
        'pengguna.lihat',
        'pengguna.kelola',
        'peran.kelola',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach (self::PERMISSION as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
        }

        // Peran dasar harus sudah ada, kalau tidak setiap payload yang
        // menukar peran akan gagal validasi dan controller tidak dijalankan.
        foreach (['pemilik', 'supervisor', 'karyawan'] as $nama) {
            Role::findOrCreate($nama, 'web');
        }
    }

    /** @param  array<int, string>  $permission */
    private function dengan(string $peran, array $permission = self::PERMISSION): User
    {
        $role = Role::findOrCreate($peran, 'web');
        $role->syncPermissions($permission);

        return tap(User::factory()->create(), fn (User $u) => $u->assignRole($role));
    }

    public function test_admin_melihat_daftar_pengguna(): void
    {
        $toko = Shop::factory()->create();
        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach($toko);

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengguna')
            ->assertOk()
            ->assertSee($supervisor->name)
            ->assertSee($supervisor->email)
            ->assertSee($toko->nama);
    }

    public function test_akun_tanpa_permission_ditolak(): void
    {
        $user = $this->dengan('karyawan', ['dashboard.lihat']);

        $this->actingAs($user)->get('/admin/pengguna')->assertForbidden();
    }

    public function test_bisa_lihat_tidak_bisa_simpan(): void
    {
        $toko = Shop::factory()->create();
        $user = $this->dengan('pengguna-baca', ['dashboard.lihat', 'pengguna.lihat']);
        $target = $this->dengan('supervisor');

        $this->actingAs($user)->get('/admin/pengguna')->assertOk();

        $this->actingAs($this->dengan('pengguna-baca', ['dashboard.lihat', 'pengguna.lihat']))
            ->put("/admin/pengguna/{$target->id}", ['peran' => ['pemilik']])
            ->assertForbidden();

        $this->assertDatabaseMissing('user_shop', [
            'user_id' => $target->id,
            'shop_id' => $toko->id,
        ]);
    }

    public function test_admin_menugaskan_toko_ke_supervisor(): void
    {
        $tokoA = Shop::factory()->create();
        $tokoB = Shop::factory()->create();
        $supervisor = $this->dengan('supervisor');

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$supervisor->id}", [
                'peran' => ['supervisor'],
                'toko' => [$tokoA->id, $tokoB->id],
                'aktif' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertSame(
            [$tokoA->id, $tokoB->id],
            $supervisor->fresh()->shops()->pluck('shops.id')->sort()->values()->all(),
        );
    }

    public function test_mengosongkan_toko_mencabut_semua_akses(): void
    {
        $toko = Shop::factory()->create();
        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach($toko);

        // Tidak mengirim `toko` sama sekali = dicabut semua, bukan "tidak diubah".
        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$supervisor->id}", [
                'peran' => ['supervisor'],
                'aktif' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(0, $supervisor->fresh()->shops()->count());
    }

    public function test_peran_global_membuang_penugasan_toko(): void
    {
        $toko = Shop::factory()->create();
        $pemilik = $this->dengan('pemilik');

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$pemilik->id}", [
                'peran' => ['pemilik'],
                'toko' => [$toko->id],
                'aktif' => '1',
            ])
            ->assertRedirect();

        // Baris penugasan untuk akun global tidak berarti apa-apa dan hanya
        // membingungkan pembaca audit.
        $this->assertSame(0, $pemilik->fresh()->shops()->count());
    }

    public function test_peran_wajib_diisi(): void
    {
        $supervisor = $this->dengan('supervisor');

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$supervisor->id}", ['peran' => []])
            ->assertSessionHasErrors('peran');
    }

    public function test_peran_tidak_dikenal_ditolak(): void
    {
        $supervisor = $this->dengan('supervisor');

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$supervisor->id}", ['peran' => ['dewa']])
            ->assertSessionHasErrors('peran.*');

        $this->assertSame(['supervisor'], $supervisor->fresh()->getRoleNames()->all());
    }

    public function test_toko_tidak_dikenal_ditolak(): void
    {
        $supervisor = $this->dengan('supervisor');

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$supervisor->id}", [
                'peran' => ['supervisor'],
                'toko' => [999999],
            ])
            ->assertSessionHasErrors('toko.*');

        $this->assertSame(0, $supervisor->fresh()->shops()->count());
    }

    public function test_perubahan_peran_menonaktifkan_sesi_aktif(): void
    {
        $supervisor = $this->dengan('supervisor');
        $supervisor->forceFill(['active_session_id' => 'sesi-lama'])->save();

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$supervisor->id}", [
                'peran' => ['karyawan'],
                'aktif' => '1',
            ])
            ->assertRedirect();

        // Sesi yang masih hidup setelah perubahan hak akses bisa dipakai untuk
        // Hal yang tak lagi dibolehinya.
        $this->assertNull($supervisor->fresh()->active_session_id);
    }

    public function test_tidak_bisa_menonaktifkan_akun_sendiri(): void
    {
        $admin = $this->dengan('pemilik', self::PERMISSION);

        $this->actingAs($admin)
            ->put("/admin/pengguna/{$admin->id}", [
                'peran' => ['pemilik'],
                'aktif' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertTrue($admin->fresh()->aktif);
    }

    public function test_status_aktif_bisa_dimatikan(): void
    {
        $supervisor = $this->dengan('supervisor');

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$supervisor->id}", [
                'peran' => ['supervisor'],
                'aktif' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($supervisor->fresh()->aktif);
    }

    public function test_halaman_memperingatkan_akun_tanpa_penugasan(): void
    {
        $supervisor = $this->dengan('supervisor');

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengguna')
            ->assertOk()
            ->assertSee('Belum ditugaskan ke toko mana pun');
    }

    public function test_halaman_menjelaskan_peran_global_tidak_perlu_toko(): void
    {
        $pemilik = $this->dengan('pemilik');

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengguna')
            ->assertOk()
            ->assertSee('sudah memberi akses ke semua toko')
            ->assertDontSee('Belum ditugaskan ke toko mana pun');
    }

    public function test_supervisor_tidak_mendapat_toko_di_luar_jangkauan(): void
    {
        $milik = Shop::factory()->create(['nama' => 'Toko Milik']);
        $orangLain = Shop::factory()->create(['nama' => 'Toko Orang Lain']);

        $user = $this->dengan('pengguna-baca', ['dashboard.lihat', 'pengguna.lihat', 'pengguna.kelola']);
        $user->shops()->attach($milik);

        $this->actingAs($user->fresh())
            ->get('/admin/pengguna')
            ->assertOk()
            ->assertSee('Toko Milik')
            ->assertDontSee('Toko Orang Lain');
    }

    public function test_filter_berdasarkan_peran(): void
    {
        $supervisor = $this->dengan('supervisor');
        $this->dengan('karyawan');

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengguna?peran=supervisor')
            ->assertOk()
            ->assertSee($supervisor->name);

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengguna?peran=tidak-ada')
            ->assertOk()
            ->assertDontSee($supervisor->name);
    }
}
