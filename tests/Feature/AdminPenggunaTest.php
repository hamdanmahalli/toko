<?php

namespace Tests\Feature;

use App\Mail\KirimAkunBaru;
use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        'perangkat.kelola',
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

    public function test_bebas_perangkat_bisa_dinyalakan_dan_dimatikan(): void
    {
        $target = $this->dengan('karyawan');

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$target->id}", [
                'peran' => ['karyawan'],
                'aktif' => '1',
                'bebas_perangkat' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($target->fresh()->bebas_perangkat);

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$target->id}", [
                'peran' => ['karyawan'],
                'aktif' => '1',
            ])
            ->assertRedirect();

        $this->assertFalse($target->fresh()->bebas_perangkat);
    }

    public function test_admin_menyetujui_perangkat_pending(): void
    {
        $target = $this->dengan('karyawan');
        $perangkat = $target->devices()->create(['device_token' => 'hp-1', 'status' => 'pending']);

        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengguna/{$target->id}/perangkat/{$perangkat->id}/setujui")
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertSame('approved', $perangkat->fresh()->status->value);
    }

    public function test_admin_menolak_perangkat(): void
    {
        $target = $this->dengan('karyawan');
        $perangkat = $target->devices()->create(['device_token' => 'hp-1', 'status' => 'pending']);

        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengguna/{$target->id}/perangkat/{$perangkat->id}/tolak")
            ->assertRedirect();

        $this->assertSame('rejected', $perangkat->fresh()->status->value);
    }

    public function test_akun_tanpa_perangkat_kelola_tidak_bisa_menyetujui(): void
    {
        $target = $this->dengan('karyawan');
        $perangkat = $target->devices()->create(['device_token' => 'hp-1', 'status' => 'pending']);

        // Boleh melihat pengguna, tapi bukan memberi izin perangkat.
        $pengawas = $this->dengan('pengawas', ['dashboard.lihat', 'pengguna.lihat']);

        $this->actingAs($pengawas)
            ->post("/admin/pengguna/{$target->id}/perangkat/{$perangkat->id}/setujui")
            ->assertForbidden();

        $this->assertSame('pending', $perangkat->fresh()->status->value);
    }

    public function test_perangkat_di_luar_akun_target_ditolak(): void
    {
        $target = $this->dengan('karyawan');
        $lain = $this->dengan('karyawan');
        $perangkat = $lain->devices()->create(['device_token' => 'hp-lain', 'status' => 'pending']);

        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengguna/{$target->id}/perangkat/{$perangkat->id}/setujui")
            ->assertNotFound();

        $this->assertSame('pending', $perangkat->fresh()->status->value);
    }

    private function karyawan(array $atribut = []): Employee
    {
        return Employee::factory()->create($atribut + [
            'shop_id' => Shop::factory()->create()->id,
            'nip' => 'K-2001',
            'email' => 'baru@toko.test',
            'user_id' => null,
            'aktif' => true,
        ]);
    }

    public function test_form_tambah_akun_tampil_untuk_pengguna_kelola(): void
    {
        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengguna')
            ->assertOk()
            ->assertSee('Tambah akun')
            ->assertSee('name="mode"', false);
    }

    public function test_admin_membuat_akun_dari_data_karyawan(): void
    {
        Mail::fake();
        $karyawan = $this->karyawan();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/pengguna', [
                'mode' => 'karyawan',
                'nip' => 'K-2001',
                'peran' => ['karyawan'],
            ])
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $akun = User::where('email', 'baru@toko.test')->firstOrFail();

        $this->assertSame($karyawan->nama, $akun->name);
        $this->assertSame('baru', $akun->username);
        $this->assertTrue($akun->hasRole('karyawan'));
        $this->assertSame($akun->id, $karyawan->fresh()->user_id);

        // Akun tertaut mewarisi toko karyawannya.
        $this->assertSame(
            [$karyawan->shop_id],
            $akun->shops()->pluck('shops.id')->all(),
        );

        Mail::assertSent(KirimAkunBaru::class, fn (KirimAkunBaru $mail) => $mail->hasTo('baru@toko.test')
            && $mail->akun->is($akun)
            && $mail->passwordAwal !== '');
    }

    public function test_akun_bebas_dibuat_tanpa_data_karyawan(): void
    {
        Mail::fake();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/pengguna', [
                'mode' => 'bebas',
                'nama' => 'Pak Bos',
                'email' => 'bos@toko.test',
                'peran' => ['supervisor'],
            ])
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $akun = User::where('email', 'bos@toko.test')->firstOrFail();

        $this->assertSame('Pak Bos', $akun->name);
        $this->assertTrue($akun->hasRole('supervisor'));
        $this->assertNull($akun->employee);
    }

    public function test_karyawan_tanpa_email_tidak_bisa_dibuatkan_akun(): void
    {
        Mail::fake();
        $this->karyawan(['email' => null]);

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/pengguna', [
                'mode' => 'karyawan',
                'nip' => 'K-2001',
                'peran' => ['karyawan'],
            ])
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertSame(1, User::count());
    }

    public function test_karyawan_yang_sudah_punya_akun_tidak_bisa_dibuat_lagi(): void
    {
        Mail::fake();
        $lama = User::factory()->create();
        $this->karyawan(['user_id' => $lama->id]);

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/pengguna', [
                'mode' => 'karyawan',
                'nip' => 'K-2001',
                'peran' => ['karyawan'],
            ])
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertDatabaseMissing('users', ['email' => 'baru@toko.test']);
    }

    public function test_kegagalan_email_membatalkan_pembuatan_akun(): void
    {
        $karyawan = $this->karyawan();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp mati'));

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/pengguna', [
                'mode' => 'karyawan',
                'nip' => 'K-2001',
                'peran' => ['karyawan'],
            ])
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertSame(1, User::count());
        $this->assertNull($karyawan->fresh()->user_id);
    }

    public function test_tanpa_permission_kelola_tidak_bisa_membuat_akun(): void
    {
        Mail::fake();
        $this->karyawan();

        $this->actingAs($this->dengan('pengguna-baca', ['dashboard.lihat', 'pengguna.lihat']))
            ->post('/admin/pengguna', [
                'mode' => 'karyawan',
                'nip' => 'K-2001',
                'peran' => ['karyawan'],
            ])
            ->assertForbidden();

        $this->assertSame(1, User::count());
    }

    public function test_karyawan_di_luar_jangkauan_toko_ditolak(): void
    {
        Mail::fake();
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();
        $this->karyawan(['shop_id' => $orangLain->id]);

        $user = $this->dengan('pengguna-baca', ['dashboard.lihat', 'pengguna.lihat', 'pengguna.kelola']);
        $user->shops()->attach($milik);

        $this->actingAs($user->fresh())
            ->post('/admin/pengguna', [
                'mode' => 'karyawan',
                'nip' => 'K-2001',
                'peran' => ['karyawan'],
            ])
            ->assertForbidden();

        $this->assertSame(1, User::count());
    }

    public function test_peran_di_luar_wewenang_ditolak(): void
    {
        Mail::fake();

        // Punya `pengguna.kelola` tetapi bukan `peran.kelola`, jadi tidak boleh
        // memberi peran pemilik walau dikirim lewat POST langsung.
        $this->actingAs($this->dengan('pengguna-baca', ['dashboard.lihat', 'pengguna.lihat', 'pengguna.kelola']))
            ->post('/admin/pengguna', [
                'mode' => 'bebas',
                'nama' => 'Nakal',
                'email' => 'nakal@toko.test',
                'peran' => ['pemilik'],
            ])
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertDatabaseMissing('users', ['email' => 'nakal@toko.test']);
    }
}
