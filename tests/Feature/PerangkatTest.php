<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Penjagaan perangkat: akun karyawan hanya boleh login dari perangkat yang
 * disetujui pemilik/kepala toko. Peran pemilik dan supervisor (lihat
 * config/absensi.php) tidak dibatasi, begitu pula akun dengan
 * `bebas_perangkat`.
 */
class PerangkatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach (['pemilik', 'supervisor', 'karyawan'] as $nama) {
            Role::findOrCreate($nama, 'web');
        }
    }

    private function karyawan(array $atribut = []): User
    {
        $user = User::factory()->create($atribut);
        $user->assignRole('karyawan');

        return $user;
    }

    public function test_karyawan_tanpa_device_id_ditolak(): void
    {
        $this->karyawan(['email' => 'siti@toko.test']);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_percobaan_dari_perangkat_baru_dicatat_sebagai_pending(): void
    {
        $user = $this->karyawan(['email' => 'siti@toko.test']);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
            'device_id' => 'hp-baru-123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_token' => 'hp-baru-123',
            'status' => 'pending',
        ]);
    }

    public function test_perangkat_pending_ditolak(): void
    {
        $user = $this->karyawan(['email' => 'siti@toko.test']);
        $user->devices()->create(['device_token' => 'hp-1', 'status' => 'pending']);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
            'device_id' => 'hp-1',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_perangkat_disetujui_dibolehkan(): void
    {
        $user = $this->karyawan(['email' => 'siti@toko.test']);
        $user->devices()->create(['device_token' => 'hp-1', 'status' => 'approved']);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
            'device_id' => 'hp-1',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(
            $user->devices()->where('device_token', 'hp-1')->first()?->last_seen_at,
        );
    }

    public function test_perangkat_ditolak_mendapat_pesan_tegas(): void
    {
        $user = $this->karyawan(['email' => 'siti@toko.test']);
        $user->devices()->create(['device_token' => 'hp-1', 'status' => 'rejected']);

        $respons = $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
            'device_id' => 'hp-1',
        ]);

        $respons->assertSessionHasErrors('login');
        $this->assertStringContainsString(
            'sudah ditolak',
            $respons->getSession()->get('errors')->first('login'),
        );
        $this->assertGuest();
    }

    public function test_perangkat_yang_sudah_ditolak_tidak_dibuat_baru(): void
    {
        $user = $this->karyawan(['email' => 'siti@toko.test']);
        $user->devices()->create(['device_token' => 'hp-1', 'status' => 'rejected']);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
            'device_id' => 'hp-1',
        ]);

        $this->assertSame(1, $user->devices()->count());
        $this->assertSame('rejected', $user->devices()->first()->status->value);
    }

    public function test_pemilik_tidak_dibatasi_perangkat(): void
    {
        $user = User::factory()->create(['email' => 'pemilik@toko.test']);
        $user->assignRole('pemilik');

        $this->post('/masuk', [
            'login' => 'pemilik@toko.test',
            'password' => 'password',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_supervisor_tidak_dibatasi_perangkat(): void
    {
        $user = User::factory()->create(['email' => 'kepala@toko.test']);
        $user->assignRole('supervisor');

        $this->post('/masuk', [
            'login' => 'kepala@toko.test',
            'password' => 'password',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_akun_dengan_bebas_perangkat_dibolehkan(): void
    {
        $user = $this->karyawan([
            'email' => 'siti@toko.test',
            'bebas_perangkat' => true,
        ]);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, $user->devices()->count());
    }

    public function test_akun_tanpa_peran_tidak_dibatasi_perangkat(): void
    {
        // Akun lama (mis. pemilik yang dibuat sebelum fitur perangkat ada)
        // tidak mendadak terkunci; penjagaan hanya untuk peran karyawan.
        $user = User::factory()->create(['email' => 'lama@toko.test']);

        $this->post('/masuk', [
            'login' => 'lama@toko.test',
            'password' => 'password',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_device_id_dipenuhi_hingga_64_karakter(): void
    {
        $user = $this->karyawan(['email' => 'siti@toko.test']);
        $terlalu = str_repeat('x', 64);

        $this->post('/masuk', [
            'login' => 'siti@toko.test',
            'password' => 'password',
            'device_id' => $terlalu,
        ])->assertSessionHasErrors('login');

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_token' => $terlalu,
            'status' => 'pending',
        ]);
        $this->assertSame(1, $user->devices()->count());
    }
}
