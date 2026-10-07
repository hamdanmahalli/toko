<?php

namespace Tests\Feature;

use App\Enums\LeaveType;
use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PengajuanTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = [
        'dashboard.lihat',
        'absen.catat',
        'toko.lihat',
        'karyawan.lihat',
        'karyawan.kelola',
        'pengajuan.lihat',
        'pengajuan.buat',
        'pengajuan.setujui',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();

        foreach (self::PERMISSION as $nama) {
            Permission::create(['name' => $nama, 'guard_name' => 'web']);
        }

        Role::create(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    private function dengan(string $role, array $permission = self::PERMISSION): User
    {
        $user = User::factory()->create();
        $peran = Role::findOrCreate($role, 'web');
        $peran->givePermissionTo($permission);
        $user->assignRole($peran);

        return $user->fresh();
    }

    /** Karyawan yang sudah punya akun login dan permission pengajuan. */
    private function karyawan(?Shop $toko = null): Employee
    {
        $user = $this->dengan('karyawan', ['dashboard.lihat', 'absen.catat', 'pengajuan.lihat', 'pengajuan.buat']);

        return Employee::factory()->denganAkun($user)->create([
            'shop_id' => ($toko ?? Shop::factory()->create())->id,
        ]);
    }

    public function test_halaman_pengajuan_karyawan_tampil(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->get('/pengajuan')
            ->assertOk()
            ->assertSee('Pengajuan')
            ->assertSee('Ajukan izin / cuti')
            ->assertSee('Ajukan lembur')
            ->assertSee(route('pengajuan.index'))
            ->assertSee(route('pengajuan.izin'));
    }

    public function test_karyawan_mengirim_pengajuan_izin(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/pengajuan/izin', [
                'jenis' => LeaveType::Cuti->value,
                'tanggal_mulai' => '2026-03-01',
                'tanggal_selesai' => '2026-03-03',
                'keterangan' => 'Libur keluarga',
            ])
            ->assertRedirect(route('pengajuan.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $karyawan->id,
            'jenis' => LeaveType::Cuti->value,
            'tanggal_mulai' => '2026-03-01',
            'tanggal_selesai' => '2026-03-03',
            'jumlah_hari' => 3,
            'status' => RequestStatus::Pending->value,
        ]);
    }

    public function test_pengajuan_izin_wajib_valid(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/pengajuan/izin', [
                'jenis' => 'cuti',
                'tanggal_mulai' => '2026-03-05',
                'tanggal_selesai' => '2026-03-01',
            ])
            ->assertSessionHasErrors('tanggal_selesai');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_jenis_pengajuan_tidak_dikenal_ditolak(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/pengajuan/izin', [
                'jenis' => 'libur',
                'tanggal_mulai' => '2026-03-01',
                'tanggal_selesai' => '2026-03-01',
            ])
            ->assertSessionHasErrors('jenis');
    }

    public function test_rentang_pengajuan_dibatasi(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/pengajuan/izin', [
                'jenis' => LeaveType::Cuti->value,
                'tanggal_mulai' => '2026-01-01',
                'tanggal_selesai' => '2026-12-31',
            ])
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_karyawan_mengirim_pengajuan_lembur(): void
    {
        $toko = Shop::factory()->create();
        $karyawan = $this->karyawan($toko);
        $karyawan->update(['tarif_jam' => 20000]);

        $this->actingAs($karyawan->user)
            ->post('/pengajuan/lembur', [
                'tanggal' => '2026-03-01',
                'jam_mulai' => '18:00',
                'jam_selesai' => '21:30',
                'keterangan' => 'Rekap stok',
            ])
            ->assertRedirect(route('pengajuan.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('overtime_requests', [
            'employee_id' => $karyawan->id,
            'tanggal' => '2026-03-01',
            'durasi_jam' => 3.5,
            'tarif_per_jam' => 20000,
            'total_lembur' => 70000,
            'status' => RequestStatus::Pending->value,
        ]);
    }

    public function test_lembur_melewati_tengah_malam(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/pengajuan/lembur', [
                'tanggal' => '2026-03-01',
                'jam_mulai' => '22:00',
                'jam_selesai' => '01:00',
            ])
            ->assertRedirect(route('pengajuan.index'));

        $this->assertDatabaseHas('overtime_requests', [
            'employee_id' => $karyawan->id,
            'durasi_jam' => 3,
        ]);
    }

    public function test_jam_selesai_sama_dengan_mulai_ditolak(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/pengajuan/lembur', [
                'tanggal' => '2026-03-01',
                'jam_mulai' => '18:00',
                'jam_selesai' => '18:00',
            ])
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertDatabaseCount('overtime_requests', 0);
    }

    public function test_lembur_lebih_dari_12_jam_ditolak(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/pengajuan/lembur', [
                'tanggal' => '2026-03-01',
                'jam_mulai' => '00:00',
                'jam_selesai' => '23:00',
            ])
            ->assertRedirect()
            ->assertSessionHas('galat');
    }

    public function test_karyawan_membatalkan_pengajuannya(): void
    {
        $karyawan = $this->karyawan();
        $pengajuan = LeaveRequest::factory()->untuk($karyawan)->create();

        $this->actingAs($karyawan->user)
            ->post("/pengajuan/izin/{$pengajuan->id}/batal")
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Cancelled->value,
        ]);
    }

    public function test_pengajuan_yang_sudah_diproses_tidak_bisa_dibatalkan(): void
    {
        $karyawan = $this->karyawan();
        $pengajuan = LeaveRequest::factory()->untuk($karyawan)->disetujui()->create();

        $this->actingAs($karyawan->user)
            ->post("/pengajuan/izin/{$pengajuan->id}/batal")
            ->assertRedirect()
            ->assertSessionHas('galat')
            ->assertSessionMissing('sukses');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Approved->value,
        ]);
    }

    public function test_tidak_bisa_membatalkan_pengajuan_orang_lain(): void
    {
        $saya = $this->karyawan();
        $orangLain = $this->karyawan();
        $pengajuan = LeaveRequest::factory()->untuk($orangLain)->create();

        $this->actingAs($saya->user)
            ->post("/pengajuan/izin/{$pengajuan->id}/batal")
            ->assertNotFound();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Pending->value,
        ]);
    }

    public function test_halaman_pengajuan_menampilkan_daftar(): void
    {
        $karyawan = $this->karyawan();

        LeaveRequest::factory()->untuk($karyawan)->create([
            'jenis' => LeaveType::Sakit->value,
            'keterangan' => 'Demam',
        ]);
        OvertimeRequest::factory()->untuk($karyawan)->create();

        $this->actingAs($karyawan->user)
            ->get('/pengajuan')
            ->assertOk()
            ->assertSee('Sakit')
            ->assertSee('Demam')
            ->assertSee('Menunggu')
            ->assertSee('Lembur');
    }

    public function test_akun_tanpa_data_karyawan_tidak_bisa_mengakses(): void
    {
        $user = $this->dengan('karyawan', ['dashboard.lihat', 'pengajuan.lihat']);

        $this->actingAs($user)->get('/pengajuan')->assertForbidden();
    }

    // ------------------------------------------------------------ sisi admin

    public function test_admin_melihat_daftar_pengajuan(): void
    {
        $karyawan = $this->karyawan();

        $pengajuanIzin = LeaveRequest::factory()->untuk($karyawan)->create(['keterangan' => 'Urusan keluarga']);
        OvertimeRequest::factory()->untuk($karyawan)->create();

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee($karyawan->nama)
            ->assertSee('Urusan keluarga')
            ->assertSee('Setujui')
            ->assertSee(route('admin.pengajuan.setujui', ['izin', $pengajuanIzin->id]));
    }

    public function test_admin_menyetujui_pengajuan_izin(): void
    {
        $karyawan = $this->karyawan();
        $pengajuan = LeaveRequest::factory()->untuk($karyawan)->create();
        $admin = $this->dengan('pemilik');

        $this->actingAs($admin)
            ->post("/admin/pengajuan/izin/{$pengajuan->id}/setujui")
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Approved->value,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_menolak_pengajuan_wajib_catatan(): void
    {
        $karyawan = $this->karyawan();
        $pengajuan = LeaveRequest::factory()->untuk($karyawan)->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengajuan/izin/{$pengajuan->id}/tolak")
            ->assertSessionHasErrors('catatan');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Pending->value,
        ]);
    }

    public function test_admin_menolak_dengan_catatan(): void
    {
        $karyawan = $this->karyawan();
        $pengajuan = LeaveRequest::factory()->untuk($karyawan)->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengajuan/izin/{$pengajuan->id}/tolak", ['catatan' => 'Bukti tidak jelas'])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Rejected->value,
            'catatan_reviewer' => 'Bukti tidak jelas',
        ]);
    }

    public function test_admin_menyetujui_pengajuan_lembur(): void
    {
        $karyawan = $this->karyawan();
        $pengajuan = OvertimeRequest::factory()->untuk($karyawan)->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengajuan/lembur/{$pengajuan->id}/setujui")
            ->assertRedirect();

        $this->assertDatabaseHas('overtime_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Approved->value,
        ]);
    }

    public function test_pengajuan_yang_sudah_diproses_tidak_bisa_diulang(): void
    {
        $karyawan = $this->karyawan();
        $pengajuan = LeaveRequest::factory()->untuk($karyawan)->disetujui()->create();

        // Bukan 422: atasan yang klik dua kali harus dapat pesan, bukan halaman error.
        $this->actingAs($this->dengan('pemilik'))
            ->post("/admin/pengajuan/izin/{$pengajuan->id}/tolak", ['catatan' => 'Mau ubah'])
            ->assertRedirect()
            ->assertSessionHas('galat');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Approved->value,
        ]);
    }

    public function test_klik_ganda_setuju_hanya_memperoses_sekali(): void
    {
        $karyawan = $this->karyawan();
        $pengajuan = OvertimeRequest::factory()->untuk($karyawan)->create();
        $admin = $this->dengan('pemilik');

        $this->actingAs($admin)
            ->post("/admin/pengajuan/lembur/{$pengajuan->id}/setujui")
            ->assertRedirect()
            ->assertSessionHas('sukses');

        // POST kedua untuk pengajuan yang sama harus aman, bukan error.
        // Tiap panggilan post() di test memakai session id baru, jadi aturan satu
        // perangkat akan mengeluarkan user. Kosongkan agar sesi baru diadopsi.
        $admin->forceFill(['active_session_id' => null])->save();

        // POST kedua untuk pengajuan yang sama: harus dapat pesan, bukan error.
        $kedua = $this->post("/admin/pengajuan/lembur/{$pengajuan->id}/setujui");

        $kedua->assertRedirect()->assertSessionHas('galat');
        $kedua->assertSessionMissing('sukses');

        $this->assertDatabaseHas('overtime_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Approved->value,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_supervisor_hanya_melihat_pengajuan_tokonya(): void
    {
        $milik = Shop::factory()->create(['nama' => 'Toko Milik']);
        $orangLain = Shop::factory()->create(['nama' => 'Toko Orang Lain']);

        $karyawanMilik = $this->karyawan($milik);
        $karyawanLain = $this->karyawan($orangLain);

        LeaveRequest::factory()->untuk($karyawanMilik)->create(['keterangan' => 'Milik saya']);
        LeaveRequest::factory()->untuk($karyawanLain)->create(['keterangan' => 'Milik orang lain']);

        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach($milik);

        $this->actingAs($supervisor->fresh())
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee($karyawanMilik->nama)
            ->assertSee('Milik saya')
            ->assertDontSee($karyawanLain->nama)
            ->assertDontSee('Milik orang lain');
    }

    public function test_supervisor_tidak_bisa_menyetujui_pengajuan_toko_orang(): void
    {
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();

        $karyawanLain = $this->karyawan($orangLain);
        $pengajuan = LeaveRequest::factory()->untuk($karyawanLain)->create();

        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach($milik);

        $this->actingAs($supervisor->fresh())
            ->post("/admin/pengajuan/izin/{$pengajuan->id}/setujui")
            ->assertNotFound();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $pengajuan->id,
            'status' => RequestStatus::Pending->value,
        ]);
    }

    public function test_filter_hanya_pending(): void
    {
        $karyawan = $this->karyawan();

        LeaveRequest::factory()->untuk($karyawan)->create(['keterangan' => 'Masih nunggu']);
        LeaveRequest::factory()->untuk($karyawan)->disetujui()->create(['keterangan' => 'Sudah disetujui']);

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan?hanya_pending=1')
            ->assertOk()
            ->assertSee('Masih nunggu')
            ->assertDontSee('Sudah disetujui');
    }

    public function test_tanpa_permission_ditolak(): void
    {
        $user = $this->dengan('karyawan', ['dashboard.lihat']);

        $this->actingAs($user)->get('/admin/pengajuan')->assertForbidden();
    }

    public function test_bisa_lihat_tidak_bisa_setujui(): void
    {
        $user = $this->dengan('supervisor', ['dashboard.lihat', 'pengajuan.lihat']);
        $karyawan = $this->karyawan();
        $pengajuan = LeaveRequest::factory()->untuk($karyawan)->create();
        $user->shops()->attach($karyawan->shop_id);

        $this->actingAs($user->fresh())->get('/admin/pengajuan')->assertOk();

        // Route persetujuan memakai permission pengajuan.setujui.
        $this->actingAs($this->dengan('supervisor', ['dashboard.lihat', 'pengajuan.lihat']))
            ->post("/admin/pengajuan/izin/{$pengajuan->id}/setujui")
            ->assertForbidden();
    }

    // ------------------------------------------------- filter, halaman, alasan

    public function test_formulir_pengajuan_menampilkan_pilihan_toko(): void
    {
        $toko = $this->karyawan()->shop;

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee('Semua toko')
            ->assertSee($toko->nama);
    }

    public function test_filter_toko_membatasi_daftar(): void
    {
        $tokoA = Shop::factory()->create(['nama' => 'Toko A']);
        $tokoB = Shop::factory()->create(['nama' => 'Toko B']);

        $diA = $this->karyawan($tokoA);
        $diB = $this->karyawan($tokoB);

        LeaveRequest::factory()->untuk($diA)->create(['keterangan' => 'Pengajuan dari A']);
        LeaveRequest::factory()->untuk($diB)->create(['keterangan' => 'Pengajuan dari B']);

        $this->actingAs($this->dengan('pemilik'))
            ->get("/admin/pengajuan?shop={$tokoA->id}")
            ->assertOk()
            ->assertSee('Pengajuan dari A')
            ->assertDontSee('Pengajuan dari B');
    }

    public function test_filter_toko_luar_cakupan_tidak_membocorkan_data(): void
    {
        $milik = Shop::factory()->create(['nama' => 'Toko Milik']);
        $orangLain = Shop::factory()->create(['nama' => 'Toko Orang Lain']);

        $karyawanMilik = $this->karyawan($milik);
        $karyawanLain = $this->karyawan($orangLain);

        LeaveRequest::factory()->untuk($karyawanMilik)->create(['keterangan' => 'Milik saya']);
        LeaveRequest::factory()->untuk($karyawanLain)->create(['keterangan' => 'Milik orang lain']);

        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach($milik);

        // Pilih toko yang bukan haknya: daftar toko harus menutupi dulu, dan
        // permintaan ke id lain tidak boleh mengembalikan pengajuan orang lain.
        $this->actingAs($supervisor->fresh())
            ->get("/admin/pengajuan?shop={$orangLain->id}")
            ->assertOk()
            ->assertDontSee('Toko Orang Lain')
            ->assertDontSee('Milik orang lain');
    }

    public function test_daftar_pengajuan_dipaginasi(): void
    {
        $karyawan = $this->karyawan();

        foreach (range(1, 25) as $nomor) {
            LeaveRequest::factory()->untuk($karyawan)->create(['keterangan' => 'Pengajuan #'.str_pad((string) $nomor, 2, '0', STR_PAD_LEFT)]);
        }

        // Urutan terbaru dulu, jadi halaman pertama memuat 25 sampai 06.
        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee('Pengajuan #25')
            ->assertDontSee('Pengajuan #05')
            ->assertSee('page_izin=2', false);

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan?page_izin=2')
            ->assertOk()
            ->assertSee('Pengajuan #05')
            ->assertDontSee('Pengajuan #25');
    }

    public function test_halaman_lembur_dipaginasi_terpisah_dari_izin(): void
    {
        $karyawan = $this->karyawan();

        foreach (range(1, 22) as $nomor) {
            OvertimeRequest::factory()->untuk($karyawan)->create(['keterangan' => 'Lembur #'.str_pad((string) $nomor, 2, '0', STR_PAD_LEFT)]);
        }

        // Nomor halaman lembur tidak boleh ikut terpotong oleh nomor halaman izin.
        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan?page_lembur=2')
            ->assertOk()
            ->assertSee('Lembur #02')
            ->assertSee('Lembur #01')
            ->assertDontSee('Lembur #22');
    }

    public function test_jumlah_pending_menghitung_semua_halaman(): void
    {
        $karyawan = $this->karyawan();

        foreach (range(1, 25) as $nomor) {
            LeaveRequest::factory()->untuk($karyawan)->create();
        }

        // Angka "menunggu keputusan" tidak boleh terpotong pagination, jadi
        // harus 25 meskipun tabel hanya menampilkan 20 baris.
        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee('25</span> pengajuan menunggu keputusan', false);
    }

    public function test_tolak_menampilkan_form_alasan_bukan_teks_baku(): void
    {
        $karyawan = $this->karyawan();
        LeaveRequest::factory()->untuk($karyawan)->create();

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee('data-alasan-tolak', false)
            ->assertDontSee('Ditolak atasan');
    }

    public function test_tolak_lembur_juga_menampilkan_form_alasan(): void
    {
        $karyawan = $this->karyawan();
        OvertimeRequest::factory()->untuk($karyawan)->create();

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/pengajuan')
            ->assertOk()
            ->assertSee('data-alasan-tolak', false)
            ->assertDontSee('Ditolak atasan');
    }
}
