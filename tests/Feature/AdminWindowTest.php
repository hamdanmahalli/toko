<?php

namespace Tests\Feature;

use App\Enums\AturanAbsensi;
use App\Models\ShiftWindow;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminWindowTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = [
        'dashboard.lihat',
        'shift.lihat',
        'shift.kelola',
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
    }

    private function pemilik(): User
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('pemilik', 'web');
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);

        return $user->fresh();
    }

    /** @return array<string, mixed> */
    private function data(array $tambahan = []): array
    {
        return array_merge([
            'nama' => 'Pagi',
            'mulai' => '06:00',
            'batas_telat' => '06:30',
            'selesai' => '14:00',
            'urutan' => 0,
            'aktif' => 1,
        ], $tambahan);
    }

    // ------------------------------------------------------------- baca

    public function test_default_aturan_window_konsisten_dengan_form(): void
    {
        $toko = Shop::factory()->create();

        // Default harus sama di tiga tempat: kolom database, model, dan form.
        // Kalau kolomnya menyimpang, window yang dibuat lewat seed atau impor
        // dapat sifat ketat tanpa pernah ada yang memintanya.
        $window = ShiftWindow::create([
            'nama' => 'Pagi',
            'mulai' => '06:00',
            'batas_telat' => '06:30',
            'selesai' => '14:00',
            'shop_id' => $toko->id,
        ]);

        $this->assertSame(AturanAbsensi::Toleran, $window->fresh()->aturan());

        $this->assertDatabaseHas('shift_windows', [
            'id' => $window->id,
            'aturan_absensi' => AturanAbsensi::Toleran->value,
        ]);
    }

    public function test_admin_melihat_daftar_window(): void
    {
        ShiftWindow::create($this->data(['shop_id' => Shop::factory()->create()->id]));

        $this->actingAs($this->pemilik())
            ->get('/admin/window')
            ->assertOk()
            ->assertSee('Window Shift')
            ->assertSee('Pagi');
    }

    public function test_halaman_menelaskan_window_menentukan_shift(): void
    {
        $this->actingAs($this->pemilik())
            ->get('/admin/window')
            ->assertOk()
            ->assertSee('Berlaku untuk kasir dan pramuniaga')
            ->assertSee('batas telat', false);
    }

    public function test_halaman_peringatan_tumpang_tindih_menyebut_yang_menang(): void
    {
        ShiftWindow::create($this->data(['nama' => 'Pagi', 'mulai' => '06:00', 'selesai' => '14:00']));
        ShiftWindow::create($this->data(['nama' => 'Siang', 'mulai' => '13:00', 'selesai' => '21:00']));

        $this->actingAs($this->pemilik())
            ->get('/admin/window')
            ->assertOk()
            ->assertSee('Yang dipakai adalah window yang jam mulainya paling akhir');
    }

    public function test_halaman_memperingatkan_window_tumpang_tindih(): void
    {
        ShiftWindow::create($this->data(['nama' => 'Pagi', 'mulai' => '06:00', 'selesai' => '14:00']));
        ShiftWindow::create($this->data(['nama' => 'Siang', 'mulai' => '13:00', 'selesai' => '21:00']));

        $this->actingAs($this->pemilik())
            ->get('/admin/window')
            ->assertOk()
            ->assertSee('saling tumpang tindih');
    }

    // ------------------------------------------------------------- tulis

    public function test_admin_bisa_membuat_window(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/window', $this->data(['shop_id' => $toko->id]))
            ->assertRedirect('/admin/window')
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_windows', [
            'nama' => 'Pagi',
            'mulai' => '06:00:00',
            'selesai' => '14:00:00',
            'shop_id' => $toko->id,
        ]);
    }

    public function test_admin_bisa_membuat_window_untuk_semua_toko(): void
    {
        $this->actingAs($this->pemilik())
            ->post('/admin/window', $this->data(['shop_id' => null]))
            ->assertRedirect('/admin/window');

        $this->assertDatabaseHas('shift_windows', ['nama' => 'Pagi', 'shop_id' => null]);
    }

    public function test_batas_telat_tersimpan(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/window', $this->data(['shop_id' => $toko->id, 'batas_telat' => '06:45']))
            ->assertRedirect('/admin/window')
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_windows', [
            'nama' => 'Pagi',
            'mulai' => '06:00:00',
            'batas_telat' => '06:45:00',
            'selesai' => '14:00:00',
        ]);
    }

    public function test_batas_telat_boleh_kosong(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/window', $this->data(['shop_id' => $toko->id, 'batas_telat' => null]))
            ->assertRedirect('/admin/window')
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_windows', ['nama' => 'Pagi', 'batas_telat' => null]);
    }

    public function test_kode_durasi_dan_aturan_tersimpan(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/window', $this->data([
                'shop_id' => $toko->id,
                'kode' => 'pagi',
                'aturan_absensi' => AturanAbsensi::Ketat->value,
                'durasi_maks_menit' => 480,
            ]))
            ->assertRedirect('/admin/window')
            ->assertSessionHas('sukses');

        // Kode dinormalisasi jadi huruf besar supaya PAGI dan pagi tidak jadi dua window.
        $this->assertDatabaseHas('shift_windows', [
            'nama' => 'Pagi',
            'kode' => 'PAGI',
            'aturan_absensi' => AturanAbsensi::Ketat->value,
            'durasi_maks_menit' => 480,
        ]);
    }

    public function test_aturan_absensi_tidak_boleh_di_luar_daftar(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->from('/admin/window')
            ->post('/admin/window', $this->data(['shop_id' => $toko->id, 'aturan_absensi' => 'ngawur']))
            ->assertSessionHasErrors('aturan_absensi');

        $this->assertDatabaseCount('shift_windows', 0);
    }

    public function test_durasi_maks_hanya_boleh_1_sampai_1440_menit(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->from('/admin/window')
            ->post('/admin/window', $this->data(['shop_id' => $toko->id, 'durasi_maks_menit' => 0]))
            ->assertSessionHasErrors('durasi_maks_menit');

        $this->actingAs($this->pemilik())
            ->from('/admin/window')
            ->post('/admin/window', $this->data(['shop_id' => $toko->id, 'durasi_maks_menit' => 2000]))
            ->assertSessionHasErrors('durasi_maks_menit');

        $this->assertDatabaseCount('shift_windows', 0);
    }

    public function test_durasi_maks_boleh_kosong(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/window', $this->data(['shop_id' => $toko->id, 'durasi_maks_menit' => '']))
            ->assertRedirect('/admin/window')
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_windows', ['nama' => 'Pagi', 'durasi_maks_menit' => null]);
    }

    public function test_tanpa_aturan_absensi_window_diperlakukan_toleran(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->post('/admin/window', $this->data(['shop_id' => $toko->id]))
            ->assertRedirect('/admin/window')
            ->assertSessionHas('sukses');

        $this->assertSame(AturanAbsensi::Toleran, ShiftWindow::firstOrFail()->aturan());
    }

    public function test_batas_telat_tidak_boleh_sebelum_jam_mulai(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->from('/admin/window')
            ->post('/admin/window', $this->data(['shop_id' => $toko->id, 'batas_telat' => '05:30']))
            ->assertSessionHasErrors('batas_telat');

        $this->assertDatabaseCount('shift_windows', 0);
    }

    public function test_batas_telat_tidak_boleh_setelah_jam_selesai(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->pemilik())
            ->from('/admin/window')
            ->post('/admin/window', $this->data(['shop_id' => $toko->id, 'batas_telat' => '15:00']))
            ->assertSessionHasErrors('batas_telat');

        $this->assertDatabaseCount('shift_windows', 0);
    }

    public function test_selesai_harus_setelah_mulai(): void
    {
        $this->actingAs($this->pemilik())
            ->from('/admin/window')
            ->post('/admin/window', $this->data(['mulai' => '14:00', 'selesai' => '06:00']))
            ->assertRedirect('/admin/window')
            ->assertSessionHasErrors('selesai');

        $this->assertDatabaseCount('shift_windows', 0);
    }

    public function test_nama_wajib_diisi(): void
    {
        $this->actingAs($this->pemilik())
            ->from('/admin/window')
            ->post('/admin/window', $this->data(['nama' => '']))
            ->assertSessionHasErrors('nama');
    }

    public function test_admin_bisa_memperbarui_window(): void
    {
        $window = ShiftWindow::create($this->data(['shop_id' => null]));

        $this->actingAs($this->pemilik())
            ->put("/admin/window/{$window->id}", $this->data([
                'nama' => 'Sub Pagi',
                'mulai' => '05:00',
                'selesai' => '13:00',
                'aktif' => 1,
            ]))
            ->assertRedirect('/admin/window');

        $this->assertDatabaseHas('shift_windows', [
            'id' => $window->id,
            'nama' => 'Sub Pagi',
            'mulai' => '05:00:00',
            'selesai' => '13:00:00',
        ]);
    }

    /** Menonaktifkan supaya label lama di riwayat absensi tetap utuh. */
    public function test_window_dinonaktifkan_bukan_dihapus(): void
    {
        $window = ShiftWindow::create($this->data(['shop_id' => null]));

        $this->actingAs($this->pemilik())
            ->delete("/admin/window/{$window->id}")
            ->assertRedirect('/admin/window');

        $this->assertDatabaseHas('shift_windows', ['id' => $window->id, 'aktif' => false]);
    }

    public function test_window_bisa_diaktifkan_lagi(): void
    {
        $window = ShiftWindow::create($this->data(['shop_id' => null, 'aktif' => false]));

        $this->actingAs($this->pemilik())
            ->post("/admin/window/{$window->id}/aktifkan")
            ->assertRedirect('/admin/window');

        $this->assertDatabaseHas('shift_windows', ['id' => $window->id, 'aktif' => true]);
    }

    // ------------------------------------------------------------- izin & cakupan

    /**
     * Setiap request di test memakai session id baru, jadi aturan satu
     * perangkat akan mengeluarkan user pada request kedua. Kosongkan agar
     * session baru itu diadopsi.
     */
    private function lanjutSesi(User $user): User
    {
        $user->forceFill(['active_session_id' => null])->save();

        return $user->fresh();
    }

    public function test_tanpa_permission_ditolak(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('karyawan', 'web');
        $role->givePermissionTo(Permission::whereIn('name', ['dashboard.lihat'])->get());
        $user->assignRole($role);

        $this->actingAs($user->fresh())
            ->get('/admin/window')
            ->assertForbidden();

        $this->actingAs($this->lanjutSesi($user))
            ->post('/admin/window', $this->data())
            ->assertForbidden();
    }

    public function test_lihat_tidak_bisa_mengelola(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo(Permission::whereIn('name', ['dashboard.lihat', 'shift.lihat'])->get());
        $user->assignRole($role);

        $this->actingAs($user->fresh())
            ->get('/admin/window')
            ->assertOk();

        $this->actingAs($this->lanjutSesi($user))
            ->post('/admin/window', $this->data())
            ->assertForbidden();
    }

    public function test_supervisor_tidak_bisa_membuat_window_semua_toko(): void
    {
        $toko = Shop::factory()->create();

        $user = User::factory()->create();
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);
        $user->shops()->attach($toko);

        $this->actingAs($user->fresh())
            ->post('/admin/window', $this->data(['shop_id' => null]))
            ->assertForbidden();

        // Tapi boleh untuk tokonya sendiri.
        $this->actingAs($this->lanjutSesi($user))
            ->post('/admin/window', $this->data(['shop_id' => $toko->id]))
            ->assertRedirect('/admin/window');
    }

    public function test_supervisor_tidak_bisa_mengubah_window_toko_lain(): void
    {
        $windowLain = ShiftWindow::create($this->data([
            'nama' => 'Milik Orang Lain',
            'shop_id' => Shop::factory()->create()->id,
        ]));

        $toko = Shop::factory()->create();

        $user = User::factory()->create();
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);
        $user->shops()->attach($toko);

        $this->actingAs($user->fresh())
            ->put("/admin/window/{$windowLain->id}", $this->data([
                'nama' => 'Dibajak',
                'shop_id' => $windowLain->shop_id,
            ]))
            ->assertNotFound();
    }

    public function test_supervisor_hanya_lihat_window_tokonya(): void
    {
        ShiftWindow::create($this->data(['nama' => 'Milik Saya', 'shop_id' => Shop::factory()->create()->id]));
        ShiftWindow::create($this->data(['nama' => 'Milik Orang Lain', 'shop_id' => Shop::factory()->create()->id]));

        $toko = Shop::factory()->create();

        $user = User::factory()->create();
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);
        $user->shops()->attach($toko);

        $this->actingAs($user->fresh())
            ->get('/admin/window')
            ->assertOk()
            ->assertDontSee('Milik Orang Lain');
    }

    public function test_peringatan_tumpang_tindih_tidak_bocor_window_toko_lain(): void
    {
        // Dua window milik toko lain yang saling tumpang tindih.
        $tokoLain = Shop::factory()->create();
        ShiftWindow::create($this->data(['nama' => 'Rahasia Satu', 'shop_id' => $tokoLain->id]));
        ShiftWindow::create($this->data(['nama' => 'Rahasia Dua', 'shop_id' => $tokoLain->id]));

        $toko = Shop::factory()->create();

        $user = User::factory()->create();
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);
        $user->shops()->attach($toko);

        $this->actingAs($user->fresh())
            ->get('/admin/window')
            ->assertOk()
            ->assertDontSee('Rahasia Satu')
            ->assertDontSee('Rahasia Dua');
    }

    public function test_supervisor_tidak_melihat_opsi_semua_toko(): void
    {
        $toko = Shop::factory()->create();

        $user = User::factory()->create();
        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo(Permission::all());
        $user->assignRole($role);
        $user->shops()->attach($toko);

        $this->actingAs($user->fresh())
            ->get('/admin/window')
            ->assertOk()
            ->assertDontSee('Semua toko');
    }

    public function test_pemilik_melihat_opsi_semua_toko(): void
    {
        $this->actingAs($this->pemilik())
            ->get('/admin/window')
            ->assertOk()
            ->assertSee('Semua toko');
    }

    public function test_form_tambah_menawarkan_kolom_aktif(): void
    {
        $this->actingAs($this->pemilik())
            ->get('/admin/window')
            ->assertOk()
            ->assertSee('name="aktif"', false)
            ->assertSee('name="urutan"', false);
    }

    public function test_window_yang_dibuat_lewat_form_langsung_aktif(): void
    {
        // Isi persis seperti yang dikirim form HTML.
        $this->actingAs($this->pemilik())
            ->post('/admin/window', [
                '_token' => csrf_token(),
                'nama' => 'Pagi',
                'mulai' => '06:00',
                'selesai' => '14:00',
                'shop_id' => '',
                'urutan' => '0',
                'aktif' => '1',
            ])
            ->assertRedirect('/admin/window');

        $this->assertDatabaseHas('shift_windows', ['nama' => 'Pagi', 'aktif' => true]);
    }

    public function test_form_ubah_tersedia_dan_bisa_menyimpan(): void
    {
        $window = ShiftWindow::create($this->data(['nama' => 'Pagi', 'shop_id' => null]));
        $pemilik = $this->pemilik();

        $this->actingAs($pemilik)
            ->get('/admin/window')
            ->assertOk()
            ->assertSee('ubah-'.$window->id, false);

        $this->actingAs($this->lanjutSesi($pemilik))
            ->put("/admin/window/{$window->id}", [
                '_token' => csrf_token(),
                '_method' => 'PUT',
                'nama' => 'Pagi Baru',
                'mulai' => '05:30',
                'selesai' => '13:30',
                'shop_id' => '',
                'urutan' => '2',
                'aktif' => '1',
            ])
            ->assertRedirect('/admin/window');

        $this->assertDatabaseHas('shift_windows', ['nama' => 'Pagi Baru', 'urutan' => 2]);
    }
}
