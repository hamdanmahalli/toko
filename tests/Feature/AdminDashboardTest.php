<?php

namespace Tests\Feature;

use App\Enums\AbsenMasukStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach ([
            'dashboard.lihat',
            'toko.lihat',
            'karyawan.lihat',
            'karyawan.kelola',
            'absen.catat',
            'pengajuan.lihat',
        ] as $nama) {
            Permission::create(['name' => $nama, 'guard_name' => 'web']);
        }

        Role::create(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    private function pemilik(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'pemilik', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);

        return $user->fresh();
    }

    public function test_pemilik_diarahkan_ke_dasbor_admin_dari_beranda(): void
    {
        $this->actingAs($this->pemilik())
            ->get('/beranda')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dasbor_admin_tampil(): void
    {
        $toko = Shop::factory()->create(['nama' => 'Toko Pusat']);
        Shop::factory()->tanpaKoordinat()->create(['nama' => 'Toko Tanpa Titik']);
        Employee::factory()->create(['shop_id' => $toko->id]);

        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Toko Pusat')
            ->assertSee('Toko Tanpa Titik')
            ->assertSee('Karyawan')
            ->assertSee('Hadir')
            ->assertSee('Belum ada koordinat');
    }

    public function test_dasbor_menghitung_hadir_dan_terlambat(): void
    {
        $toko = Shop::factory()->create();
        $hadir = Employee::factory()->create(['shop_id' => $toko->id]);
        $terlambat = Employee::factory()->create(['shop_id' => $toko->id]);

        $pabrik = fn (Employee $k, string $status) => Attendance::create([
            'employee_id' => $k->id,
            'shop_id' => $toko->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => now()->setTime(8, 0),
            'status_masuk' => $status,
            'metode' => 'qr',
        ]);

        $pabrik($hadir, AbsenMasukStatus::TepatWaktu->value);
        $pabrik($terlambat, AbsenMasukStatus::Terlambat->value);

        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['Hadir', '2'])
            ->assertSee('1', false);
    }

    public function test_daftar_belum_pulang_menampilkan_nama(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id, 'nama' => 'Andi Pratama']);

        Attendance::create([
            'employee_id' => $karyawan->id,
            'shop_id' => $toko->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => now()->setTime(8, 0),
            'status_masuk' => AbsenMasukStatus::TepatWaktu->value,
            'metode' => 'qr',
        ]);

        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Andi Pratama')
            ->assertSee('Belum pulang');
    }

    public function test_pengajuan_tertunda_terhitung(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        LeaveRequest::factory()->create([
            'employee_id' => $karyawan->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Perlu persetujuan')
            ->assertSee('pengajuan menunggu keputusan')
            ->assertSee(route('admin.pengajuan.index', ['hanya_pending' => 1]));
    }

    public function test_perangkat_menunggu_terhitung(): void
    {
        Permission::create(['name' => 'perangkat.kelola', 'guard_name' => 'web']);

        $toko = Shop::factory()->create();
        $akun = User::factory()->create();
        Employee::factory()->create(['shop_id' => $toko->id, 'user_id' => $akun->id]);

        $akun->devices()->create(['device_token' => 'hp-baru', 'status' => 'pending']);
        // Perangkat yang sudah disetujui tidak ikut dihitung.
        $akun->devices()->create(['device_token' => 'hp-lama', 'status' => 'approved']);

        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk()
            ->assertViewHas('perangkatMenunggu', 1)
            ->assertSee('Perangkat baru belum dikenal')
            ->assertSee(route('admin.pengguna.index'));
    }

    public function test_perangkat_menunggu_dibatasi_toko_yang_diawasi(): void
    {
        Permission::create(['name' => 'perangkat.kelola', 'guard_name' => 'web']);

        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();

        $akunMilik = User::factory()->create();
        Employee::factory()->create(['shop_id' => $milik->id, 'user_id' => $akunMilik->id]);
        $akunMilik->devices()->create(['device_token' => 'a', 'status' => 'pending']);

        $akunLain = User::factory()->create();
        Employee::factory()->create(['shop_id' => $orangLain->id, 'user_id' => $akunLain->id]);
        $akunLain->devices()->create(['device_token' => 'b', 'status' => 'pending']);

        $supervisor = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
        $supervisor->assignRole($role);
        $supervisor->shops()->attach($milik);

        $this->actingAs($supervisor->fresh())
            ->get('/admin')
            ->assertOk()
            ->assertViewHas('perangkatMenunggu', 1);
    }

    public function test_supervisor_hanya_melihat_tokonya(): void
    {
        $milik = Shop::factory()->create(['nama' => 'Toko Milik']);
        $orangLain = Shop::factory()->create(['nama' => 'Toko Orang Lain']);

        Employee::factory()->count(3)->create(['shop_id' => $milik->id]);
        Employee::factory()->count(5)->create(['shop_id' => $orangLain->id]);

        $supervisor = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
        $supervisor->assignRole($role);
        $supervisor->shops()->attach($milik);

        // Hanya toko milik supervisor yang boleh muncul di halaman ini.
        $this->actingAs($supervisor->fresh())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Toko Milik')
            ->assertSee('Toko yang Anda awasi')
            ->assertDontSee('Toko Orang Lain');
    }

    public function test_pemilik_melihat_semua_toko(): void
    {
        Shop::factory()->create(['nama' => 'Toko Satu']);
        Shop::factory()->create(['nama' => 'Toko Dua']);

        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Toko Satu')
            ->assertSee('Toko Dua')
            ->assertSee('Seluruh toko');
    }

    public function test_karyawan_tidak_bisa_buka_dasbor_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('karyawan', 'web'));

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_dasbor_menampilkan_rekap_window_hari_ini(): void
    {
        $toko = Shop::factory()->create();

        $pabrik = fn (string $label, string $tanggal) => Attendance::create([
            'employee_id' => Employee::factory()->create(['shop_id' => $toko->id])->id,
            'shop_id' => $toko->id,
            'tanggal' => $tanggal,
            'jam_masuk' => '08:00:00',
            'status_masuk' => AbsenMasukStatus::TepatWaktu->value,
            'metode' => 'qr',
            'shift_label_masuk' => $label,
        ]);

        $hariIni = now()->toDateString();

        $pabrik('Pagi', $hariIni);
        $pabrik('Pagi', $hariIni);
        $pabrik('Siang', $hariIni);
        // Absensi lama tidak boleh ikut terhitung.
        $pabrik('Malam', now()->subDay()->toDateString());

        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Per window')
            ->assertSee('2 orang')
            ->assertDontSee('Malam');
    }

    public function test_dasbor_tanpa_label_tetap_aman(): void
    {
        $this->actingAs($this->pemilik())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Belum ada yang absen hari ini');
    }
}
