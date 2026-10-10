<?php

namespace Tests\Feature;

use App\Mail\KirimAkunBaru;
use App\Mail\KirimPasswordBaru;
use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Halaman Pengguna adalah tempat utama tabel `user_shop` diatur. Tanpa itu,
 * supervisor tidak punya toko sama sekali dan tidak bisa memantau apa pun,
 * jadi penugasan di sini adalah bagian dari keamanan, bukan sekadar fitur
 * administer. Selain itu, akun yang tertaut data karyawan otomatis diwarisi
 * toko karyawannya (lihat `User::sertakanToko`).
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

    public function test_halaman_pengguna_menampilkan_perangkat_dan_tombol_hapus_saja(): void
    {
        $target = $this->dengan('karyawan');
        $perangkat = $target->devices()->create(['device_token' => 'hp-1', 'status' => 'pending']);

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengguna')
            ->assertOk()
            ->assertSee('Perangkat yang tercatat')
            // "Setujui"/"Tolak" dihapus: penyelesaian ganti perangkat adalah
            // Hapus perangkat lama, perangkat baru otomatis jadi yang pertama.
            ->assertDontSee('Setujui')
            ->assertDontSee('Tolak')
            ->assertSee(route('admin.pengguna.perangkat-hapus', [$target, $perangkat]), false);
    }

    public function test_bisa_hapus_perangkat_akun_target(): void
    {
        $target = $this->dengan('karyawan');
        $perangkat = $target->devices()->create(['device_token' => 'hp-1', 'status' => 'pending']);

        $this->actingAs($this->dengan('pemilik'))
            ->delete("/admin/pengguna/{$target->id}/perangkat/{$perangkat->id}")
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('user_devices', ['id' => $perangkat->id]);
    }

    public function test_tanpa_perangkat_kelola_tidak_bisa_hapus_perangkat(): void
    {
        $target = $this->dengan('karyawan');
        $perangkat = $target->devices()->create(['device_token' => 'hp-1', 'status' => 'pending']);

        // Boleh melihat pengguna, tapi bukan mengurus perangkat.
        $pengawas = $this->dengan('pengawas', ['dashboard.lihat', 'pengguna.lihat']);

        $this->actingAs($pengawas)
            ->delete("/admin/pengguna/{$target->id}/perangkat/{$perangkat->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('user_devices', ['id' => $perangkat->id]);
    }

    public function test_tidak_bisa_hapus_perangkat_akun_lain(): void
    {
        $target = $this->dengan('karyawan');
        $lain = $this->dengan('karyawan');
        $perangkat = $lain->devices()->create(['device_token' => 'hp-lain', 'status' => 'pending']);

        $this->actingAs($this->dengan('pemilik'))
            ->delete("/admin/pengguna/{$target->id}/perangkat/{$perangkat->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('user_devices', ['id' => $perangkat->id]);
    }

    public function test_admin_menghapus_akun_target(): void
    {
        $target = $this->dengan('karyawan');
        $karyawan = Employee::factory()->create([
            'user_id' => $target->id,
            'shop_id' => Shop::factory()->create()->id,
            'aktif' => true,
        ]);
        $target->forceFill(['active_session_id' => 'sesi-lama'])->save();

        $this->actingAs($this->dengan('pemilik'))
            ->delete("/admin/pengguna/{$target->id}")
            ->assertRedirect()
            ->assertSessionHas('sukses');

        // Akun nonaktif + sesi diputus; karyawan dilepas supaya bisa dibuatkan
        // akun baru, tapi riwayatnya tetap aman (baris users tidak hilang).
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'aktif' => false,
            'active_session_id' => null,
        ]);
        $this->assertDatabaseHas('employees', ['id' => $karyawan->id, 'user_id' => null]);
    }

    public function test_admin_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $pemilik = $this->dengan('pemilik');

        $this->actingAs($pemilik)
            ->delete("/admin/pengguna/{$pemilik->id}")
            ->assertSessionHas('galat');

        $this->assertDatabaseHas('users', ['id' => $pemilik->id, 'aktif' => true]);
    }

    public function test_tanpa_pengguna_kelola_tidak_bisa_menghapus_akun(): void
    {
        $target = $this->dengan('karyawan');
        $pengawas = $this->dengan('pengawas', ['dashboard.lihat', 'pengguna.lihat']);

        $this->actingAs($pengawas)
            ->delete("/admin/pengguna/{$target->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'aktif' => true]);
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

    public function test_toko_karyawan_tercentang_di_halaman(): void
    {
        $toko = Shop::factory()->create();
        $akun = User::factory()->create(['name' => 'Budi']);
        Employee::factory()->create([
            'shop_id' => $toko->id,
            'user_id' => $akun->id,
            'email' => 'budi@toko.test',
        ]);

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengguna')
            ->assertOk()
            ->assertSee('dari data karyawan')
            ->assertDontSee('Belum ditugaskan ke toko mana pun');
    }

    public function test_simpan_tidak_menghapus_toko_karyawan(): void
    {
        $toko = Shop::factory()->create();
        $akun = User::factory()->create();
        Employee::factory()->create(['shop_id' => $toko->id, 'user_id' => $akun->id]);

        // Form tidak mengirim `toko` sama sekali karena checkbox karyawan
        // dimatikan di UI; toko itu tetap harus bertahan.
        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/pengguna/{$akun->id}", [
                'peran' => ['karyawan'],
                'aktif' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_shop', [
            'user_id' => $akun->id,
            'shop_id' => $toko->id,
        ]);
    }

    public function test_command_selaras_toko_mengisi_akun_lama(): void
    {
        $toko = Shop::factory()->create();
        $akun = User::factory()->create();
        Employee::factory()->create(['shop_id' => $toko->id, 'user_id' => $akun->id]);

        $this->assertDatabaseMissing('user_shop', ['user_id' => $akun->id]);

        $this->artisan('pengguna:selaras-toko')->assertSuccessful();

        $this->assertDatabaseHas('user_shop', [
            'user_id' => $akun->id,
            'shop_id' => $toko->id,
        ]);
    }

    public function test_admin_mengatur_ulang_password_dan_mengirim_email(): void
    {
        Mail::fake();

        $target = User::factory()->create(['email' => 'budi@toko.test']);
        $target->assignRole('karyawan');
        $hashLama = $target->password;

        // Sesi lama harus ikut diputus supaya pemegang password lama tidak
        // tetap masuk setelah password diatur ulang.
        $target->forceFill(['active_session_id' => 'sesi-lama'])->save();

        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengguna/{$target->id}/reset-password")
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $terkirim = null;
        Mail::assertSent(KirimPasswordBaru::class, function (KirimPasswordBaru $mail) use ($target, &$terkirim) {
            $terkirim = $mail;

            return $mail->hasTo($target->email) && $mail->akun->is($target);
        });

        $target->refresh();
        $this->assertNotSame($hashLama, $target->password);
        $this->assertTrue(Hash::check($terkirim->passwordBaru, $target->password));
        $this->assertNull($target->active_session_id);
    }

    public function test_aturs_ulang_password_dibatalkan_bila_email_gagal(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('smtp mati'));

        $target = User::factory()->create();
        $target->assignRole('karyawan');
        $hashLama = $target->password;

        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengguna/{$target->id}/reset-password")
            ->assertRedirect()
            ->assertSessionHas('galat');

        // Transaksi di-rollback: password lama tetap berlaku supaya akun
        // tidak terkunci tanpa password yang diketahui siapa pun.
        $this->assertSame($hashLama, $target->fresh()->password);
    }

    public function test_supervisor_tidak_bisa_aturs_ulang_password(): void
    {
        Mail::fake();

        $target = User::factory()->create();
        $target->assignRole('karyawan');

        $this->actingAs($this->dengan('supervisor', ['dashboard.lihat', 'pengguna.lihat']))
            ->post("/admin/pengguna/{$target->id}/reset-password")
            ->assertForbidden();

        Mail::assertNothingSent();
    }
}
