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
            ->assertSee('name="password"', false)
            ->assertDontSee('Masuk untuk melanjutkan');
    }

    public function test_root_mengalihkan_ke_halaman_masuk(): void
    {
        $this->get('/')->assertRedirect('/beranda');
        $this->get('/beranda')->assertRedirect(route('masuk'));
    }

    public function test_bisa_login_dengan_kredensial_benar(): void
    {
        $user = User::factory()->create([
            'email' => 'siti@toko.test',
            'password' => Hash::make('rahasia123'),
        ]);

        $response = $this->post('/masuk', [
            'email' => 'siti@toko.test',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('beranda'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_salah_ditolak(): void
    {
        User::factory()->create(['email' => 'siti@toko.test']);

        $this->post('/masuk', [
            'email' => 'siti@toko.test',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_tidak_aktif_tidak_bisa_login(): void
    {
        User::factory()->create([
            'email' => 'siti@toko.test',
            'aktif' => false,
        ]);

        $this->post('/masuk', [
            'email' => 'siti@toko.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_tidak_valid_ditolak(): void
    {
        $this->post('/masuk', [
            'email' => 'bukan-email',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

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
                'email' => 'siti@toko.test',
                'password' => 'salah',
            ]);
        }

        $respons = $this->post('/masuk', [
            'email' => 'siti@toko.test',
            'password' => 'password',
        ]);

        $respons->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            $respons->getSession()->get('errors')->first('email'),
        );
    }
}
