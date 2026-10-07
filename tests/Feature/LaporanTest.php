<?php

namespace Tests\Feature;

use App\Enums\AbsenMasukStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use App\Services\LaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private Shop $toko;

    private Shop $tokoLain;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'laporan.lihat', 'guard_name' => 'web']);

        $this->toko = Shop::factory()->create();
        $this->tokoLain = Shop::factory()->create();
        $this->admin = $this->adminSemuaToko();
    }

    private function adminSemuaToko(): User
    {
        $role = Role::findOrCreate('pemilik', 'web');
        $role->givePermissionTo('laporan.lihat');

        $user = User::factory()->create(['aktif' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function supervisor(Shop ...$toko): User
    {
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo('laporan.lihat');

        $user = User::factory()->create(['aktif' => true]);
        $user->assignRole($role);
        $user->shops()->attach(array_map(fn ($t) => $t->id, $toko));

        return $user;
    }

    /** Bikin satu baris absensi selesai dengan durasi tertentu. */
    private function sesi(Employee $employee, string $tanggal, int $sesi, int $durasiMenit, array $extra = []): Attendance
    {
        return Attendance::create($extra + [
            'employee_id' => $employee->id,
            'shop_id' => $employee->shop_id,
            'tanggal' => $tanggal,
            'sesi' => $sesi,
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '12:00:00',
            'durasi_menit' => $durasiMenit,
            'status_masuk' => AbsenMasukStatus::TepatWaktu->value,
            'metode' => 'qr',
        ]);
    }

    public function test_halaman_laporan_bisa_diakses(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index'))
            ->assertOk()
            ->assertSee('Laporan durasi kerja');
    }

    public function test_laporan_default_menampilkan_absensi_bulan_ini(): void
    {
        $karyawan = Employee::factory()->create(['shop_id' => $this->toko->id]);

        $this->sesi($karyawan, now()->toDateString(), 1, 240);

        $hasil = app(LaporanService::class)
            ->durasiKaryawan(now()->startOfMonth()->toDateString(), now()->toDateString());

        $this->assertSame(1, $hasil['ringkasan']['karyawan']);
        $this->assertSame(1, $hasil['ringkasan']['hari']);
        $this->assertSame(240, $hasil['ringkasan']['menit']);
    }

    /**
     * Durasi harus dijumlah dari semua sesi pada satu tanggal, bukan diambil
     * dari satu baris saja. Ini yang terjadi pada shift interval.
     */
    public function test_durasi_menjumlahkan_semua_sesi_satu_hari(): void
    {
        $karyawan = Employee::factory()->create(['shop_id' => $this->toko->id]);

        $this->sesi($karyawan, now()->toDateString(), 1, 180);
        $this->sesi($karyawan, now()->toDateString(), 2, 240);

        $hasil = app(LaporanService::class)
            ->durasiKaryawan(now()->toDateString(), now()->toDateString());

        $this->assertSame(1, $hasil['ringkasan']['karyawan'], 'Dua sesi satu orang tetap satu karyawan.');
        $this->assertSame(1, $hasil['ringkasan']['hari'], 'Dua sesi satu tanggal tetap satu hari.');
        $this->assertSame(2, $hasil['ringkasan']['sesi']);
        $this->assertSame(420, $hasil['ringkasan']['menit']);

        $baris = $hasil['baris']->first();
        $this->assertSame(420, $baris['menit']);
    }

    public function test_hari_dihitung_dari_tanggal_unik_bukan_jumlah_sesi(): void
    {
        $karyawan = Employee::factory()->create(['shop_id' => $this->toko->id]);

        $this->sesi($karyawan, '2026-10-01', 1, 240);
        $this->sesi($karyawan, '2026-10-01', 2, 120);
        $this->sesi($karyawan, '2026-10-02', 1, 300);

        $hasil = app(LaporanService::class)->durasiKaryawan('2026-10-01', '2026-10-02');

        $this->assertSame(2, $hasil['baris']->first()['hari']);
        $this->assertSame(3, $hasil['baris']->first()['sesi']);
        $this->assertSame(660, $hasil['baris']->first()['menit']);
    }

    public function test_sesi_belum_pulang_dihitung_terpisah_dan_tidak_menambah_durasi(): void
    {
        $karyawan = Employee::factory()->create(['shop_id' => $this->toko->id]);

        $this->sesi($karyawan, '2026-10-01', 1, 240);
        $this->sesi($karyawan, '2026-10-01', 2, 0, [
            'jam_pulang' => null,
            'durasi_menit' => null,
        ]);

        $hasil = app(LaporanService::class)->durasiKaryawan('2026-10-01', '2026-10-01');

        $this->assertSame(240, $hasil['ringkasan']['menit'], 'Sesi yang belum pulang tidak menambah durasi.');
        $this->assertSame(1, $hasil['ringkasan']['belum_pulang']);
    }

    public function test_hanya_absensi_dalam_rentang_yang_dihitung(): void
    {
        $karyawan = Employee::factory()->create(['shop_id' => $this->toko->id]);

        $this->sesi($karyawan, '2026-09-30', 1, 600);
        $this->sesi($karyawan, '2026-10-01', 1, 240);
        $this->sesi($karyawan, '2026-10-05', 1, 300);

        $hasil = app(LaporanService::class)->durasiKaryawan('2026-10-01', '2026-10-02');

        $this->assertSame(1, $hasil['ringkasan']['hari']);
        $this->assertSame(240, $hasil['ringkasan']['menit']);
    }

    public function test_pencarian_menyaring_berdasarkan_nama_dan_nip(): void
    {
        $siti = Employee::factory()->create(['shop_id' => $this->toko->id, 'nama' => 'Siti Aminah', 'nip' => 'NS001']);
        $budi = Employee::factory()->create(['shop_id' => $this->toko->id, 'nama' => 'Budi Santoso', 'nip' => 'NS002']);

        $this->sesi($siti, '2026-10-01', 1, 240);
        $this->sesi($budi, '2026-10-01', 1, 300);

        $layanan = app(LaporanService::class);

        $this->assertSame(['Siti Aminah'], $layanan->durasiKaryawan('2026-10-01', '2026-10-01', null, 'Siti')['baris']
            ->pluck('employee.nama')->all());
        $this->assertSame(['Budi Santoso'], $layanan->durasiKaryawan('2026-10-01', '2026-10-01', null, 'NS002')['baris']
            ->pluck('employee.nama')->all());
    }

    public function test_baris_diurutkan_dari_total_durasi_terbesar(): void
    {
        $sepi = Employee::factory()->create(['shop_id' => $this->toko->id]);
        $sibuk = Employee::factory()->create(['shop_id' => $this->toko->id]);

        $this->sesi($sepi, '2026-10-01', 1, 120);
        $this->sesi($sibuk, '2026-10-01', 1, 500);

        $hasil = app(LaporanService::class)->durasiKaryawan('2026-10-01', '2026-10-01');

        $this->assertSame(500, $hasil['baris']->first()['menit']);
    }

    public function test_halaman_laporan_menampilkan_angka_durasi(): void
    {
        $karyawan = Employee::factory()->create([
            'shop_id' => $this->toko->id,
            'nama' => 'Siti Aminah',
        ]);

        $this->sesi($karyawan, now()->toDateString(), 1, 390);

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index'))
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertSee('6j 30m');
    }

    public function test_rentang_bawah_dari_sampai_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index', ['dari' => '2026-10-10', 'sampai' => '2026-10-01']))
            ->assertSessionHasErrors('sampai');
    }

    public function test_tanggal_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index', ['dari' => 'bukan-tanggal']))
            ->assertSessionHasErrors('dari');
    }

    public function test_rentang_lebih_dari_dua_tahun_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index', [
                'dari' => now()->subYears(3)->toDateString(),
                'sampai' => now()->toDateString(),
            ]))
            ->assertSessionHasErrors('dari');
    }

    public function test_tanggal_mulai_di_masa_depan_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index', [
                'dari' => now()->addWeek()->toDateString(),
                'sampai' => now()->addWeek()->toDateString(),
            ]))
            ->assertSessionHasErrors('dari');
    }

    public function test_rentang_setahun_untuk_payroll_diterima(): void
    {
        $karyawan = Employee::factory()->create(['shop_id' => $this->toko->id]);
        $this->sesi($karyawan, now()->subMonths(11)->toDateString(), 1, 240);

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index', [
                'dari' => now()->subYear()->toDateString(),
                'sampai' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($karyawan->nama);
    }

    public function test_supervisor_hanya_melihat_tokonya(): void
    {
        $milikSaya = Employee::factory()->create(['shop_id' => $this->toko->id, 'nama' => 'Milik Saya']);
        $milikLain = Employee::factory()->create(['shop_id' => $this->tokoLain->id, 'nama' => 'Milik Toko Lain']);

        $this->sesi($milikSaya, now()->toDateString(), 1, 240);
        $this->sesi($milikLain, now()->toDateString(), 1, 300);

        $supervisor = $this->supervisor($this->toko);

        $this->actingAs($supervisor)
            ->get(route('admin.laporan.index'))
            ->assertOk()
            ->assertSee('Milik Saya')
            ->assertDontSee('Milik Toko Lain');
    }

    public function test_supervisor_tidak_bisa_maksa_memilih_toko_orang_lain(): void
    {
        $milikLain = Employee::factory()->create(['shop_id' => $this->tokoLain->id, 'nama' => 'Milik Toko Lain']);
        $this->sesi($milikLain, now()->toDateString(), 1, 300);

        $supervisor = $this->supervisor($this->toko);

        // Memilih toko di luar cakupan tidak boleh membocorkan apa pun; hasil
        // paling buruk adalah seluruh toko milik supervisor.
        $this->actingAs($supervisor)
            ->get(route('admin.laporan.index', ['shop' => $this->tokoLain->id]))
            ->assertOk()
            ->assertDontSee('Milik Toko Lain');
    }

    public function test_halaman_laporan_menampilkan_nama_toko_pilihan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index'))
            ->assertOk()
            ->assertSee($this->toko->nama)
            ->assertSee($this->tokoLain->nama);
    }

    public function test_pagination_tidak_menggugurkan_rangkuman(): void
    {
        $karyawan = Employee::factory()->count(30)->create(['shop_id' => $this->toko->id]);

        foreach ($karyawan as $employee) {
            $this->sesi($employee, now()->toDateString(), 1, 240);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.laporan.index', ['page' => 2]))
            ->assertOk()
            // 30 karyawan dengan 25 per halaman: halaman kedua hanya berisi 5
            // baris, tapi rangkuman tetap mencakup 30 orang.
            ->assertSee('30 orang');
    }
}
