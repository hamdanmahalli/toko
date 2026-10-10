<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthTest extends TestCase
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

    public function test_halaman_masuk_tampil_untuk_tamu(): void
    {
        // Teks "Masuk untuk melanjutkan" sengaja dihapus: logo dan tombol
        // sudah cukup jelas, kalimat tambahan cuma noise.
        $this->get('/masuk')
            ->assertOk()
            ->assertSee('logo-toko-mm.png', false)
            ->assertSee('name="login"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('name="ingat"', false)
            ->assertDontSee('Remember Me')
            ->assertDontSee('Masuk untuk melanjutkan');
    }

    public function test_root_menampilkan_halaman_sambutan_untuk_tamu(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Let&rsquo;s Get You Set Up<br>for Success', false)
            ->assertSee('img/login.png', false)
            ->assertSee(route('masuk'), false);

        $this->get('/beranda')->assertRedirect(route('masuk'));
    }

    public function test_root_mengalihkan_karyawan_yang_sudah_login_ke_beranda(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('beranda'));
    }

    public function test_bisa_login_dengan_email(): void
    {
        $user = User::factory()->create([
            'email' => 'siti@toko.test',
            'password' => Hash::make('rahasia123'),
        ]);

        $response = $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('beranda'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_bisa_login_dengan_username(): void
    {
        $user = User::factory()->create([
            'email' => 'siti@toko.test',
            'username' => 'siti.kasir',
            'password' => Hash::make('rahasia123'),
        ]);

        // Kolom tunggal: tanpa `@` diartikan sebagai username.
        $this->post('/masuk', [
            'login' => 'SITI.KASIR',
            'password' => 'rahasia123',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_salah_ditolak(): void
    {
        User::factory()->create(['email' => 'siti@toko.test']);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'salah',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_akun_tidak_aktif_tidak_bisa_login(): void
    {
        User::factory()->create([
            'email' => 'siti@toko.test',
            'aktif' => false,
        ]);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_tidak_dikenal_ditolak_dengan_pesan_baku(): void
    {
        $respons = $this->post('/masuk', [
            'login' => 'bukan-ada',
            'password' => 'rahasia123',
        ]);

        $respons->assertSessionHasErrors('login');
        $this->assertSame(
            'Email/username atau password salah.',
            $respons->getSession()->get('errors')->first('login'),
        );
        $this->assertGuest();
    }

    public function test_logout_menghapus_sesi(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/keluar')->assertRedirect(route('masuk'));

        $this->assertGuest();
    }

    public function test_halaman_tertutup_untuk_tamu(): void
    {
        $this->get('/beranda')->assertRedirect(route('masuk'));
    }

    public function test_login_kunci_sementara_setelah_lima_percobaan(): void
    {
        User::factory()->create(['email' => 'siti@toko.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/masuk', [
                'login' => 'siti@toko.test',
                'password' => 'salah',
            ]);
        }

        $respons = $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
        ]);

        $respons->assertSessionHasErrors('login');

        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            $respons->getSession()->get('errors')->first('login'),
        );
    }
}
