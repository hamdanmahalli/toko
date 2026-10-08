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
 * Klaim akun mandiri: admin membuat data karyawan (NIP + email), karyawan
 * mengklaim sendiri dengan memilih username, password awal dikirim email,
 * dan perangkat tempat klaim langsung diizinkan.
 */
class KlaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();
        Role::findOrCreate('karyawan', 'web');
    }

    private function karyawan(array $atribut = []): Employee
    {
        return Employee::factory()->create($atribut + [
            'shop_id' => Shop::factory()->create()->id,
            'nip' => 'K-1001',
            'email' => 'budi@toko.test',
            'user_id' => null,
            'aktif' => true,
        ]);
    }

    public function test_halaman_klaim_tampil_untuk_tamu(): void
    {
        $this->get('/klaim')
            ->assertOk()
            ->assertSee('name="nip"', false)
            ->assertSee('name="username"', false)
            ->assertSee('name="device_id"', false);
    }

    public function test_klaim_berhasil_membuat_akun_dan_mengirim_email(): void
    {
        $karyawan = $this->karyawan();

        $this->post('/klaim', [
            'nip' => 'K-1001',
            'email' => 'budi@toko.test',
            'username' => 'Budi.Kasir',
            'device_id' => 'hp-budi',
        ])->assertRedirect(route('masuk'))->assertSessionHas('sukses');

        $akun = User::where('username', 'budi.kasir')->firstOrFail();

        $this->assertSame('budi@toko.test', $akun->email);
        $this->assertTrue($akun->hasRole('karyawan'));
        $this->assertSame($akun->id, $karyawan->fresh()->user_id);

        // Perangkat tempat klaim otomatis dipercaya, bukan pending.
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $akun->id,
            'device_token' => 'hp-budi',
            'status' => 'approved',
        ]);

        Mail::assertSent(KirimAkunBaru::class, function (KirimAkunBaru $mail) use ($akun) {
            return $mail->hasTo($akun->email)
                && $mail->akun->is($akun)
                && $mail->passwordAwal !== '';
        });
    }

    public function test_akun_baru_langsung_bisa_login_dengan_username_dan_password_dari_email(): void
    {
        $this->karyawan();

        $this->post('/klaim', [
            'nip' => 'K-1001',
            'email' => 'budi@toko.test',
            'username' => 'budikasir',
            'device_id' => 'hp-budi',
        ]);

        $akun = User::where('username', 'budikasir')->firstOrFail();
        $passwordAwal = null;

        Mail::assertSent(KirimAkunBaru::class, function (KirimAkunBaru $mail) use ($akun, &$passwordAwal): bool {
            if (! $mail->akun->is($akun)) {
                return false;
            }
            $passwordAwal = $mail->passwordAwal;

            return true;
        });

        $this->assertNotEmpty($passwordAwal);

        // Login memakai username, password dari email, dan perangkat yang
        // otomatis disetujui saat klaim.
        $this->post('/masuk', [
            'login' => 'budikasir',
            'password' => $passwordAwal,
            'device_id' => 'hp-budi',
        ])->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($akun);
    }

    public function test_nip_tidak_cocok_ditolak(): void
    {
        $this->karyawan();

        $this->post('/klaim', [
            'nip' => 'K-9999',
            'email' => 'budi@toko.test',
            'username' => 'budikasir',
        ])->assertRedirect()->assertSessionHas('galat');

        $this->assertDatabaseMissing('users', ['username' => 'budikasir']);
    }

    public function test_email_tidak_cocok_ditolak(): void
    {
        $this->karyawan();

        $this->post('/klaim', [
            'nip' => 'K-1001',
            'email' => 'oranglain@toko.test',
            'username' => 'budikasir',
        ])->assertRedirect()->assertSessionHas('galat');

        $this->assertDatabaseMissing('users', ['username' => 'budikasir']);
    }

    public function test_karyawan_sudah_punya_akun_tidak_bisa_diklaim_ulang(): void
    {
        $user = User::factory()->create(['username' => 'budi.lama']);
        $this->karyawan(['user_id' => $user->id]);

        $this->post('/klaim', [
            'nip' => 'K-1001',
            'email' => 'budi@toko.test',
            'username' => 'budi.baru',
        ])->assertRedirect()->assertSessionHas('galat');

        $this->assertDatabaseMissing('users', ['username' => 'budi.baru']);
    }

    public function test_karyawan_tidak_aktif_tidak_bisa_diklaim(): void
    {
        $this->karyawan(['aktif' => false]);

        $this->post('/klaim', [
            'nip' => 'K-1001',
            'email' => 'budi@toko.test',
            'username' => 'budikasir',
        ])->assertRedirect()->assertSessionHas('galat');

        $this->assertDatabaseMissing('users', ['username' => 'budikasir']);
    }

    public function test_username_sudah_dipakai_ditolak(): void
    {
        $this->karyawan();
        User::factory()->create(['username' => 'sudahdipakai']);

        $this->post('/klaim', [
            'nip' => 'K-1001',
            'email' => 'budi@toko.test',
            'username' => 'sudahdipakai',
        ])->assertRedirect()->assertSessionHas('galat');

        $this->assertSame(1, User::count());
    }

    public function test_username_tidak_valid_ditolak(): void
    {
        $this->karyawan();

        $this->post('/klaim', [
            'nip' => 'K-1001',
            'email' => 'budi@toko.test',
            'username' => 'spasi dan simbol!',
        ])->assertSessionHasErrors('username');

        $this->assertDatabaseMissing('users', ['username' => 'spasi dan simbol!']);
    }

    public function test_username_terlalu_pendek_ditolak(): void
    {
        $this->karyawan();

        $this->post('/klaim', [
            'nip' => 'K-1001',
            'email' => 'budi@toko.test',
            'username' => 'ab',
        ])->assertSessionHasErrors('username');
    }
}
