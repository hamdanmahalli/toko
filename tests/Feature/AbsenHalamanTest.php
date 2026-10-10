<?php

namespace Tests\Feature;

use App\Enums\AbsenMethod;
use App\Enums\ShiftScope;
use App\Enums\ShiftTipe;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Services\AbsenService;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AbsenHalamanTest extends TestCase
{
    use RefreshDatabase;

    // Role karyawan sungguhan punya `absen.lihat` juga (lihat RolePermissionSeeder);
    // membaca riwayat dan mencatat absen sudah dipisah jadi dua permission.
    protected const PERMISSION = ['dashboard.lihat', 'absen.catat', 'absen.lihat'];

    private User $user;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach (self::PERMISSION as $nama) {
            Permission::create(['name' => $nama, 'guard_name' => 'web']);
        }

        $role = Role::create(['name' => 'karyawan', 'guard_name' => 'web']);
        $role->givePermissionTo(self::PERMISSION);

        $this->user = User::factory()->create(['aktif' => true]);
        $this->user->assignRole($role);

        $this->employee = Employee::factory()
            ->denganAkun($this->user)
            ->create(['nama' => 'Siti Rahma']);
    }

    private function koordinatToko(): array
    {
        return [
            'latitude' => $this->employee->shop->latitude,
            'longitude' => $this->employee->shop->longitude,
            'accuracy' => 12,
        ];
    }

    public function test_beranda_menampilkan_nama_karyawan(): void
    {
        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('Siti Rahma');
    }

    /**
     * Tanpa layout, halaman karyawan tampil sebagai teks polos tanpa CSS.
     * Test ini menjaga setiap halaman karyawan tetap memakai layout.
     *
     * `csrf-token` ikut diperiksa karena `absen/tombol.blade.php` membacanya
     * dari DOM lewat `document.querySelector`. Layout karyawan pernah tidak
     * punya meta tersebut, jadi script-nya meledak dengan
     * "Cannot read properties of null" dan tombol absen mati tanpa error
     * yang terlihat di UI.
     */
    public function test_halaman_karyawan_tetap_berpakai_layout(): void
    {
        foreach (['/beranda', '/saya-qr', '/riwayat'] as $url) {
            $this->actingAs($this->karyawanBaru())
                ->get($url)
                ->assertOk()
                ->assertSee('<!DOCTYPE html>', false)
                ->assertSee('rel="stylesheet"', false)
                ->assertSee('rel="preload" as="style"', false)
                ->assertSee('name="csrf-token"', false);
        }
    }

    public function test_halaman_pengajuan_karyawan_berpakai_layout(): void
    {
        foreach (['/pengajuan', '/pengajuan/izin', '/pengajuan/lembur'] as $url) {
            $this->actingAs($this->karyawanBaru(['pengajuan.lihat', 'pengajuan.buat']))
                ->get($url)
                ->assertOk()
                ->assertSee('<!DOCTYPE html>', false)
                ->assertSee('rel="stylesheet"', false);
        }
    }

    /** Akun baru tiap permintaan: middleware satu perangkat memutus sesi lama. */
    private function karyawanBaru(array $permission = []): User
    {
        $permission = array_merge(self::PERMISSION, $permission);

        foreach ($permission as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
        }

        $role = Role::findOrCreate('karyawan', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create(['aktif' => true]);
        $user->assignRole($role);

        Employee::factory()->denganAkun($user)->create();

        return $user->fresh();
    }

    public function test_beranda_menampilkan_status_hari_ini(): void
    {
        app(AbsenService::class)->absenMasuk(
            employee: $this->employee,
            latitude: $this->employee->shop->latitude,
            longitude: $this->employee->shop->longitude,
            metode: AbsenMethod::Qr,
            akurasi: 10,
        );

        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('Absensi hari ini')
            ->assertSee('Sudah absen masuk')
            ->assertSee(now()->format('H:i'));
    }

    public function test_beranda_menampilkan_jam_shift_hari_ini(): void
    {
        $template = ShiftTemplate::factory()->create(['nama' => 'Shift Pagi']);
        ShiftSlot::factory()->create([
            'shift_template_id' => $template->id,
            'hari' => now()->dayOfWeek,
            'jam_masuk' => '08:00',
            'batas_telat' => '08:15',
            'jam_pulang' => '17:00',
        ]);

        $this->employee->pasangShift($template->id, now()->subDays(3)->toDateString());

        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('08:00')
            ->assertSee('08:15')
            ->assertSee('17:00')
            ->assertSee('Belum absen masuk');
    }

    public function test_beranda_tanpa_template_menyebut_jam_bawaan_toko(): void
    {
        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('Belum ada template shift')
            ->assertSee('08:15');
    }

    public function test_beranda_tidak_menyebut_jam_bawaan_bila_template_ada(): void
    {
        $template = ShiftTemplate::factory()->create();
        ShiftSlot::factory()->create([
            'shift_template_id' => $template->id,
            'hari' => now()->dayOfWeek,
            'jam_masuk' => '09:00',
            'batas_telat' => '09:15',
            'jam_pulang' => '18:00',
        ]);

        $this->employee->pasangShift($template->id, now()->subMonth()->toDateString());

        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('09:15')
            ->assertDontSee('Belum ada template shift');
    }

    public function test_beranda_menampilkan_ringkasan_bulan_ini(): void
    {
        $absen = app(AbsenService::class);

        $absen->absenMasuk(
            employee: $this->employee,
            latitude: $this->employee->shop->latitude,
            longitude: $this->employee->shop->longitude,
            metode: AbsenMethod::Qr,
            akurasi: 10,
        );

        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('Hari hadir')
            ->assertSee('Jam kerja')
            ->assertSee('Kali terlambat')
            ->assertSee(now()->translatedFormat('M Y'));
    }

    public function test_beranda_menghitung_hari_hadir_dari_tanggal_unik(): void
    {
        // Satu hari dengan beberapa sesi harus tetap dihitung sebagai satu hari
        // hadir, bukan sebanyak jumlah baris absensinya.
        $absensi = [
            ['tanggal' => now()->toDateString(), 'sesi' => 1, 'durasi_menit' => 240],
            ['tanggal' => now()->toDateString(), 'sesi' => 2, 'durasi_menit' => 180],
            ['tanggal' => now()->subDay()->toDateString(), 'sesi' => 1, 'durasi_menit' => 480],
        ];

        foreach ($absensi as $isi) {
            $this->employee->attendances()->create($isi + [
                'shop_id' => $this->employee->shop_id,
                'jam_masuk' => '08:00',
                'jam_pulang' => '12:00',
            ]);
        }

        $statistik = $this->actingAs($this->user)->get('/beranda')
            ->assertOk()
            ->viewData('statistik');

        $this->assertSame(2, $statistik['hari']);
        $this->assertSame(15.0, $statistik['jam']);
    }

    public function test_beranda_menampilkan_semua_sesi_interval(): void
    {
        $template = ShiftTemplate::create([
            'nama' => 'Shift Interval',
            'scope' => ShiftScope::Global,
            'tipe' => ShiftTipe::Interval,
        ]);

        foreach (['Pagi' => ['08:00', '12:00'], 'Siang' => ['13:00', '16:00']] as $nama => [$mulai, $selesai]) {
            $template->intervals()->create([
                'nama' => $nama,
                'mulai' => $mulai.':00',
                'selesai' => $selesai.':00',
                'urutan' => $template->intervals()->count() + 1,
                'aktif' => true,
            ]);
        }

        $this->employee->pasangShift($template->id, now()->subDays(3)->toDateString());

        $absen = app(AbsenService::class);

        $absen->absenMasuk(
            employee: $this->employee,
            latitude: $this->employee->shop->latitude,
            longitude: $this->employee->shop->longitude,
            metode: AbsenMethod::Qr,
            akurasi: 10,
            sekarang: Carbon::parse(now()->toDateString().' 08:00'),
        );
        $absen->absenPulang(
            employee: $this->employee,
            latitude: $this->employee->shop->latitude,
            longitude: $this->employee->shop->longitude,
            metode: AbsenMethod::Qr,
            akurasi: 10,
            sekarang: Carbon::parse(now()->toDateString().' 12:00'),
        );
        $absen->absenMasuk(
            employee: $this->employee,
            latitude: $this->employee->shop->latitude,
            longitude: $this->employee->shop->longitude,
            metode: AbsenMethod::Qr,
            akurasi: 10,
            sekarang: Carbon::parse(now()->toDateString().' 13:00'),
        );

        $this->actingAs($this->user);

        $this->get('/beranda')
            ->assertOk()
            // Sesi pertama sudah tutup, jadi hari ini belum bisa disebut lengkap.
            ->assertSee('Sudah absen masuk')
            ->assertDontSee('Absensi hari ini lengkap')
            // Kedua sesi tetap harus terlihat, bukan cuma sesi terakhir.
            ->assertSee('Pagi')
            ->assertSee('Siang');

        // Tiap request diuji dengan session id baru, jadi middleware satu
        // perangkat akan mengeluarkan akun kalau active_session_id tidak dilepas.
        $this->user->forceFill(['active_session_id' => null])->save();

        $this->actingAs($this->user)
            ->get('/riwayat')
            ->assertOk()
            ->assertSee('Sesi 2');
    }

    public function test_beranda_menampilkan_jumlah_pengajuan_menunggu(): void
    {
        LeaveRequest::factory()->untuk($this->employee)->create();

        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('1 pengajuan menunggu');
    }

    public function test_beranda_menampilkan_tautan_cepat(): void
    {
        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee(route('absen.qr'))
            ->assertSee(route('absen.riwayat'))
            ->assertSee('Kartu QR')
            ->assertSee('Riwayat');
    }

    public function test_beranda_menampilkan_tautan_pengajuan_bila_diizinkan(): void
    {
        $role = Role::findByName('karyawan', 'web');
        $role->givePermissionTo(Permission::firstOrCreate([
            'name' => 'pengajuan.lihat',
            'guard_name' => 'web',
        ]));

        $this->user->refresh();

        $this->actingAs($this->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee(route('pengajuan.index'))
            ->assertSee('Pengajuan');
    }

    public function test_beranda_membuat_inisial_nama_tunggal(): void
    {
        $karyawan = Employee::factory()->denganAkun(User::factory()->create())->create(['nama' => 'Lutfi']);
        $karyawan->user->assignRole(Role::findByName('karyawan', 'web'));

        $this->assertSame('LU', $karyawan->initials());

        $this->actingAs($karyawan->user)
            ->get('/beranda')
            ->assertOk()
            ->assertSee('Lutfi');
    }

    public function test_kartu_qr_tampil_dengan_token(): void
    {
        $token = app(QrService::class)->token($this->employee);

        $this->actingAs($this->user)
            ->get('/saya-qr')
            ->assertOk()
            ->assertSee($token);
    }

    public function test_riwayat_bisa_diakses(): void
    {
        $this->actingAs($this->user)->get('/riwayat')->assertOk();
    }

    public function test_absen_masuk_via_ajax_tersimpan(): void
    {
        $respons = $this->actingAs($this->user)
            ->postJson('/absen', ['arah' => 'masuk'] + $this->koordinatToko());

        $respons->assertOk();
        $respons->assertJsonPath('sukses', true);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'tanggal' => now()->toDateString(),
        ]);
    }

    public function test_absen_masuk_luar_toko_ditolak_dengan_json(): void
    {
        $respons = $this->actingAs($this->user)
            ->postJson('/absen', [
                'arah' => 'masuk',
                'latitude' => -6.9,
                'longitude' => 106.8,
                'accuracy' => 10,
            ]);

        $respons->assertStatus(422);
        $respons->assertJsonPath('sukses', false);
    }

    public function test_koordinat_wajib_ada(): void
    {
        $this->actingAs($this->user)
            ->postJson('/absen', ['arah' => 'masuk'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_arah_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->user)
            ->postJson('/absen', ['arah' => 'maling'] + $this->koordinatToko())
            ->assertJsonValidationErrors('arah');
    }

    public function test_absen_pulang_tanpa_masuk_ditolak(): void
    {
        $respons = $this->actingAs($this->user)
            ->postJson('/absen', ['arah' => 'pulang'] + $this->koordinatToko());

        $respons->assertStatus(422);
    }

    public function test_absen_ganda_ditolak(): void
    {
        // baris absensi sudah ada dari request sebelumnya, jadi POST ini harus ditolak
        app(AbsenService::class)->absenMasuk(
            employee: $this->employee,
            latitude: $this->employee->shop->latitude,
            longitude: $this->employee->shop->longitude,
            metode: AbsenMethod::Qr,
            akurasi: 10,
        );

        $respons = $this->actingAs($this->user)
            ->postJson('/absen', ['arah' => 'masuk'] + $this->koordinatToko());

        $respons->assertStatus(422);
        $respons->assertJsonPath('sukses', false);
    }

    public function test_akun_tanpa_data_karyawan_dilarang(): void
    {
        $atasan = User::factory()->create();
        $atasan->assignRole(Role::findByName('karyawan', 'web'));

        $this->actingAs($atasan)
            ->get('/saya-qr')
            ->assertForbidden();
    }
}
