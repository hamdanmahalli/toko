<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    private function akun(array $atribut = []): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('karyawan', 'web');

        $user = User::factory()->create($atribut);
        $user->assignRole('karyawan');

        return $user;
    }

    public function test_tamu_dialihkan_ke_halaman_masuk(): void
    {
        $this->get('/profil')->assertRedirect(route('masuk'));
    }

    public function test_halaman_menampilkan_info_akun_dan_perangkat(): void
    {
        $user = $this->akun(['username' => 'siti.kasir']);
        $user->devices()->create([
            'device_token' => 'hp-1',
            'label' => 'Samsung A54',
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get('/profil')
            ->assertOk()
            ->assertSee('siti.kasir')
            ->assertSee('Samsung A54')
            ->assertSee('Diizinkan');
    }

    public function test_ganti_password_berhasil(): void
    {
        $user = $this->akun();

        $this->actingAs($user)->post('/profil/password', [
            'password_lama' => 'password',
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'rahasiabaru123',
        ])->assertRedirect()->assertSessionHas('sukses');

        $this->assertTrue(Hash::check('rahasiabaru123', $user->fresh()->password));
    }

    public function test_password_lama_salah_ditolak(): void
    {
        $user = $this->akun();

        $this->actingAs($user)->post('/profil/password', [
            'password_lama' => 'ngasal',
            'password' => 'rahasiabaru123',
            'password_confirmation' => 'rahasiabaru123',
        ])->assertRedirect()->assertSessionHas('galat');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_baru_terlalu_pendek_ditolak(): void
    {
        $user = $this->akun();

        $this->actingAs($user)->post('/profil/password', [
            'password_lama' => 'password',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])->assertSessionHasErrors('password');
    }

    public function test_ganti_username_berhasil(): void
    {
        $user = $this->akun(['username' => 'siti.lama']);

        $this->actingAs($user)->post('/profil/username', [
            'username' => 'siti.baru',
        ])->assertRedirect()->assertSessionHas('sukses');

        $this->assertSame('siti.baru', $user->fresh()->username);
    }

    public function test_username_baru_langsung_berlaku_untuk_login(): void
    {
        $user = $this->akun(['username' => 'siti.baru']);
        $user->devices()->create(['device_token' => 'hp-1', 'status' => 'approved']);

        $this->post('/masuk', [
            'login' => 'siti.baru',
            'password' => 'password',
            'device_id' => 'hp-1',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_username_sudah_dipakai_ditolak(): void
    {
        $user = $this->akun(['username' => 'siti.saya']);
        User::factory()->create(['username' => 'punyaorang']);

        $this->actingAs($user)->post('/profil/username', [
            'username' => 'punyaorang',
        ])->assertRedirect()->assertSessionHas('galat');

        $this->assertSame('siti.saya', $user->fresh()->username);
    }

    public function test_username_tidak_valid_ditolak(): void
    {
        $user = $this->akun();

        $this->actingAs($user)->post('/profil/username', [
            'username' => 'spasi nggak boleh',
        ])->assertSessionHasErrors('username');
    }

    public function test_username_boleh_tetap_sama(): void
    {
        $user = $this->akun(['username' => 'siti.sama']);

        $this->actingAs($user)->post('/profil/username', [
            'username' => 'siti.sama',
        ])->assertRedirect()->assertSessionHas('pesan');
    }

    public function test_cabut_perangkat_sendiri(): void
    {
        $user = $this->akun();
        $perangkat = $user->devices()->create([
            'device_token' => 'hp-hilang',
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->post(route('profil.perangkat-cabut', $perangkat))
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertSame('rejected', $perangkat->fresh()->status->value);
    }

    public function test_tidak_bisa_cabut_perangkat_orang_lain(): void
    {
        $user = $this->akun();
        $lain = User::factory()->create();
        $perangkat = $lain->devices()->create([
            'device_token' => 'hp-lain',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('profil.perangkat-cabut', $perangkat));

        $this->assertSame('approved', $perangkat->fresh()->status->value);
        $this->assertSame(1, UserDevice::count());
    }
}
