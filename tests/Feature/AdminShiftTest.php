<?php

namespace Tests\Feature;

use App\Enums\AbsenMasukStatus;
use App\Enums\FleksibelTipe;
use App\Enums\ShiftScope;
use App\Enums\ShiftTipe;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ShiftSlot;
use App\Models\ShiftTemplate;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminShiftTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = [
        'dashboard.lihat',
        'toko.lihat',
        'karyawan.lihat',
        'karyawan.kelola',
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

    /** Payload slot 5 hari kerja (Senin-Jumat). */
    private function payload(array $ubah = [], array $tambahan = []): array
    {
        $slot = [
            1 => ['jam_masuk' => '08:00', 'batas_telat' => '08:15', 'jam_pulang' => '17:00'],
            2 => ['jam_masuk' => '08:00', 'batas_telat' => '08:15', 'jam_pulang' => '17:00'],
            3 => ['jam_masuk' => '08:00', 'batas_telat' => '08:15', 'jam_pulang' => '17:00'],
            4 => ['jam_masuk' => '08:00', 'batas_telat' => '08:15', 'jam_pulang' => '17:00'],
            5 => ['jam_masuk' => '08:00', 'batas_telat' => '08:15', 'jam_pulang' => '17:00'],
        ];

        foreach ($slot as $hari => $isi) {
            $slot[$hari]['aktif'] = 1;
        }

        return array_merge([
            'nama' => 'Shift Pagi',
            'scope' => ShiftScope::Toko->value,
            'aktif' => 1,
            'slot' => $slot,
        ], $tambahan, $ubah);
    }

    public function test_daftar_template_tampil(): void
    {
        ShiftTemplate::factory()->denganSlot()->create(['nama' => 'Shift Pagi']);

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/shift')
            ->assertOk()
            ->assertSee('Shift Pagi')
            ->assertSee('Senin')
            ->assertSee('08:00')
            ->assertSee('17:00');
    }

    public function test_halaman_tambah_tampil(): void
    {
        Shop::factory()->create(['nama' => 'Toko Pusat']);

        $this->actingAs($this->dengan('pemilik'))
            ->get('/admin/shift/tambah')
            ->assertOk()
            ->assertSee('Toko Pusat')
            ->assertSee('Minggu');
    }

    public function test_simpan_template_dengan_slot(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $this->payload(['shop_id' => $toko->id]))
            ->assertRedirect(route('admin.shift.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_templates', ['nama' => 'Shift Pagi', 'shop_id' => $toko->id]);

        $this->assertDatabaseHas('shift_slots', [
            'shift_template_id' => ShiftTemplate::first()->id,
            'hari' => 1,
            'jam_masuk' => '08:00:00',
            'batas_telat' => '08:15:00',
            'jam_pulang' => '17:00:00',
        ]);

        // 5 hari kerja, bukan 7.
        $this->assertSame(5, ShiftSlot::count());
    }

    public function test_slot_istirahat_tersimpan(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'][1]['mulai_istirahat'] = '12:00';
        $data['slot'][1]['selesai_istirahat'] = '13:00';

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_slots', [
            'shift_template_id' => ShiftTemplate::first()->id,
            'hari' => 1,
            'mulai_istirahat' => '12:00:00',
            'selesai_istirahat' => '13:00:00',
        ]);
    }

    public function test_kode_dan_durasi_maks_template_tersimpan(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'kode' => 'pagi',
                'durasi_maks_menit' => 480,
            ]))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_templates', [
            'nama' => 'Shift Pagi',
            'kode' => 'PAGI',
            'durasi_maks_menit' => 480,
        ]);
    }

    public function test_slot_boleh_menimpa_durasi_maks_template(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id, 'durasi_maks_menit' => 480]);
        // Hari Senin dibatasi 8 jam, hari lain ikut default template.
        $data['slot'][1]['durasi_maks_menit'] = 480;
        $data['slot'][2]['durasi_maks_menit'] = 600;

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHas('sukses');

        $template = ShiftTemplate::firstOrFail();

        $this->assertDatabaseHas('shift_slots', ['shift_template_id' => $template->id, 'hari' => 1, 'durasi_maks_menit' => 480]);
        $this->assertDatabaseHas('shift_slots', ['shift_template_id' => $template->id, 'hari' => 2, 'durasi_maks_menit' => 600]);
        // Hari yang dibiarkan kosong jadi null supaya jatuh ke default template.
        $this->assertDatabaseHas('shift_slots', ['shift_template_id' => $template->id, 'hari' => 3, 'durasi_maks_menit' => null]);
        $this->assertSame(600, $template->slots()->where('hari', 2)->firstOrFail()->durasiMaks());
        $this->assertSame(480, $template->slots()->where('hari', 3)->firstOrFail()->durasiMaks());
    }

    public function test_durasi_maks_slot_ditolak_bila_di_atas_24_jam(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'][1]['durasi_maks_menit'] = 5000;

        $this->actingAs($this->dengan('pemilik'))
            ->from(route('admin.shift.create'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors('durasi_maks_menit');

        $this->assertDatabaseCount('shift_templates', 0);
    }

    public function test_hari_tidak_dicentang_dilewati(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        unset($data['slot'][5]);

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('shift_slots', ['hari' => 5]);
    }

    public function test_interval_bisa_disimpan_dengan_beberapa_sesi(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Interval->value,
                'slot' => [],
                'interval' => [
                    ['nama' => 'Pagi', 'mulai' => '08:00', 'selesai' => '12:00', 'durasi_min_menit' => 240],
                    ['nama' => 'Siang', 'mulai' => '12:00', 'selesai' => '16:00', 'durasi_min_menit' => 240],
                ],
            ]))
            ->assertSessionHas('sukses');

        $template = ShiftTemplate::firstOrFail();

        // Tipe interval memakai sesi, jadi tidak boleh ada slot harian tersimpan.
        $this->assertSame(0, $template->slots()->count());
        $this->assertDatabaseHas('shift_intervals', [
            'shift_template_id' => $template->id,
            'nama' => 'Pagi',
            'urutan' => 1,
            'durasi_min_menit' => 240,
        ]);
        $this->assertDatabaseHas('shift_intervals', [
            'shift_template_id' => $template->id,
            'nama' => 'Siang',
            'urutan' => 2,
        ]);
    }

    public function test_interval_wajib_punya_minimal_satu_sesi(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->from(route('admin.shift.create'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Interval->value,
                'slot' => [],
                'interval' => [],
            ]))
            ->assertSessionHasErrors('interval');

        $this->assertDatabaseCount('shift_templates', 0);
    }

    public function test_interval_menolak_jam_selesai_sama_dengan_jam_mulai(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->from(route('admin.shift.create'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Interval->value,
                'slot' => [],
                'interval' => [
                    ['nama' => 'Pagi', 'mulai' => '08:00', 'selesai' => '08:00'],
                ],
            ]))
            ->assertSessionHasErrors('interval.0.selesai');

        $this->assertDatabaseCount('shift_templates', 0);
    }

    public function test_tipe_lain_mengabaikan_sesi_interval(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Tetap->value,
                // Sesi ngawur sengaja dikirim: tipe tetap tidak boleh memakainya.
                'interval' => [
                    ['nama' => 'Pagi', 'mulai' => '08:00', 'selesai' => ''],
                ],
            ]))
            ->assertSessionHas('sukses');

        $this->assertDatabaseCount('shift_intervals', 0);
    }

    public function test_interval_lama_ikut_terhapus_saat_template_diubah_tipe(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::create([
            'nama' => 'Shift Interval',
            'scope' => ShiftScope::Toko,
            'shop_id' => $toko->id,
            'tipe' => ShiftTipe::Interval,
        ]);
        $template->intervals()->create([
            'nama' => 'Pagi',
            'mulai' => '08:00:00',
            'selesai' => '12:00:00',
            'urutan' => 1,
            'aktif' => true,
        ]);

        $this->actingAs($this->dengan('pemilik'))
            ->put('/admin/shift/'.$template->id, $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Tetap->value,
            ]))
            ->assertSessionHas('sukses');

        // Sesi yang tidak berlaku harus hilang, bukan diam-diam aktif lagi
        // begitu tipenya dikembalikan ke interval.
        $this->assertDatabaseCount('shift_intervals', 0);
    }

    public function test_fleksibel_durasi_tetap_bisa_disimpan(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Fleksibel->value,
                'fleksibel_tipe' => FleksibelTipe::DurasiTetap->value,
                'durasi_kerja_menit' => 360,
                'jam_cut_off' => '09:00',
            ]))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_templates', [
            'nama' => 'Shift Pagi',
            'tipe' => ShiftTipe::Fleksibel->value,
            'fleksibel_tipe' => FleksibelTipe::DurasiTetap->value,
            'durasi_kerja_menit' => 360,
        ]);

        $this->assertDatabaseHas('shift_templates', ['nama' => 'Shift Pagi', 'jam_cut_off' => '09:00:00']);
    }

    public function test_fleksibel_wajib_pilih_jenis(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->from(route('admin.shift.create'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Fleksibel->value,
            ]))
            ->assertSessionHasErrors('fleksibel_tipe');

        $this->assertDatabaseCount('shift_templates', 0);
    }

    public function test_fleksibel_durasi_tetap_wajib_isi_durasi_kerja(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->from(route('admin.shift.create'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Fleksibel->value,
                'fleksibel_tipe' => FleksibelTipe::DurasiTetap->value,
                'durasi_kerja_menit' => null,
            ]))
            ->assertSessionHasErrors('durasi_kerja_menit');

        $this->assertDatabaseCount('shift_templates', 0);
    }

    public function test_tipe_tetap_membuang_nilai_khusus_fleksibel(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $this->payload([
                'shop_id' => $toko->id,
                'tipe' => ShiftTipe::Tetap->value,
                'fleksibel_tipe' => FleksibelTipe::DurasiTetap->value,
                'durasi_kerja_menit' => 360,
            ]))
            ->assertSessionHas('sukses');

        // Nilai yang tidak berlaku harus dibuang, bukan disimpan diam-diam dan
        // dipakai lagi begitu tipenya dikembalikan ke fleksibel.
        $template = ShiftTemplate::firstOrFail();
        $this->assertSame(ShiftTipe::Tetap, $template->tipe());
        $this->assertNull($template->fleksibel_tipe);
        $this->assertNull($template->durasi_kerja_menit);
    }

    public function test_fleksibel_boleh_jam_pulang_lewat_tengah_malam(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload([
            'shop_id' => $toko->id,
            'tipe' => ShiftTipe::Fleksibel->value,
            'fleksibel_tipe' => FleksibelTipe::Terbatas->value,
        ]);
        $data['slot'][1]['jam_masuk'] = '20:00';
        $data['slot'][1]['batas_telat'] = '22:00';
        $data['slot'][1]['jam_pulang'] = '03:00';

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_slots', [
            'shift_template_id' => ShiftTemplate::firstOrFail()->id,
            'hari' => 1,
            'jam_masuk' => '20:00:00',
            'jam_pulang' => '03:00:00',
        ]);
    }

    public function test_tetap_masih_menolak_jam_pulang_sebelum_jam_masuk(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'][1]['jam_masuk'] = '20:00';
        $data['slot'][1]['batas_telat'] = '21:00';
        $data['slot'][1]['jam_pulang'] = '03:00';

        $this->actingAs($this->dengan('pemilik'))
            ->from(route('admin.shift.create'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors('jam_pulang');

        $this->assertDatabaseCount('shift_templates', 0);
    }

    public function test_jam_pulang_tidak_boleh_sama_dengan_jam_masuk_pada_fleksibel(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload([
            'shop_id' => $toko->id,
            'tipe' => ShiftTipe::Fleksibel->value,
            'fleksibel_tipe' => FleksibelTipe::Terbatas->value,
        ]);
        $data['slot'][1]['jam_pulang'] = $data['slot'][1]['jam_masuk'];

        $this->actingAs($this->dengan('pemilik'))
            ->from(route('admin.shift.create'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors('jam_pulang');

        $this->assertDatabaseCount('shift_templates', 0);
    }

    public function test_hari_aktif_tanpa_jam_masuk_ditolak(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'][1]['jam_masuk'] = '';

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors();

        $this->assertSame(0, ShiftTemplate::count());
    }

    public function test_batas_telat_harus_setelah_jam_masuk(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'][1]['batas_telat'] = '07:30';

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors();

        $this->assertSame(0, ShiftTemplate::count());
    }

    public function test_jam_pulang_harus_setelah_batas_telat(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'][1]['jam_pulang'] = '08:00';

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors();
    }

    public function test_istirahat_harus_di_antar_jam(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'][1]['mulai_istirahat'] = '18:00';

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors();
    }

    public function test_wajib_punya_satu_hari_kerja(): void
    {
        $toko = Shop::factory()->create();
        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'] = [];

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors('slot');
    }

    public function test_template_per_toko_wajib_pilih_toko(): void
    {
        $data = $this->payload(['scope' => ShiftScope::Toko->value, 'shop_id' => null]);

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $data)
            ->assertSessionHasErrors();

        $this->assertSame(0, ShiftTemplate::count());
    }

    public function test_template_global_tidak_pakai_toko(): void
    {
        $toko = Shop::factory()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->post('/admin/shift', $this->payload([
                'scope' => ShiftScope::Global->value,
                'shop_id' => $toko->id,
            ]))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('shift_templates', [
            'nama' => 'Shift Pagi',
            'scope' => ShiftScope::Global->value,
            'shop_id' => null,
        ]);
    }

    public function test_ubah_template_mengganti_slot(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::factory()->denganSlot([1, 2])->create(['shop_id' => $toko->id]);

        $data = $this->payload(['shop_id' => $toko->id]);
        $data['slot'][1]['jam_masuk'] = '09:00';
        $data['slot'][1]['batas_telat'] = '09:30';
        $data['slot'][1]['jam_pulang'] = '18:00';

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/shift/{$template->id}", $data)
            ->assertRedirect(route('admin.shift.index'));

        $slot = ShiftSlot::where('shift_template_id', $template->id)->where('hari', 1)->first();

        $this->assertSame('09:00:00', $slot->jam_masuk->format('H:i:s'));
    }

    public function test_slot_yang_dihilangkan_dinonaktifkan_bukan_dihapus(): void
    {
        $toko = Shop::factory()->create();

        // Sabtu ikut ada, lalu dibuang saat template diubah ke hari kerja saja.
        $template = ShiftTemplate::factory()->denganSlot([1, 2, 6])->create(['shop_id' => $toko->id]);

        $data = $this->payload(['shop_id' => $toko->id]);

        $this->actingAs($this->dengan('pemilik'))
            ->put("/admin/shift/{$template->id}", $data);

        $this->assertDatabaseHas('shift_slots', [
            'shift_template_id' => $template->id,
            'hari' => 1,
            'aktif' => true,
        ]);

        $this->assertDatabaseHas('shift_slots', [
            'shift_template_id' => $template->id,
            'hari' => 6,
            'aktif' => false,
        ]);

        // Barisnya tetap ada supaya rujukan absensi lama tidak putus.
        $this->assertDatabaseHas('shift_slots', ['shift_template_id' => $template->id, 'hari' => 6]);
    }

    public function test_halaman_ubah_menampilkan_slot_lama(): void
    {
        $template = ShiftTemplate::factory()->denganSlot([1], '07:30', '08:00', '16:00')->create();

        $this->actingAs($this->dengan('pemilik'))
            ->get("/admin/shift/{$template->id}/ubah")
            ->assertOk()
            ->assertSee('07:30');
    }

    public function test_template_tanpa_karyawan_bisa_dihapus(): void
    {
        $template = ShiftTemplate::factory()->denganSlot()->create();

        $this->actingAs($this->dengan('pemilik'))
            ->delete("/admin/shift/{$template->id}")
            ->assertRedirect(route('admin.shift.index'));

        $this->assertDatabaseMissing('shift_templates', ['id' => $template->id]);
        $this->assertSame(0, ShiftSlot::count());
    }

    public function test_template_berkaryawan_dihapus_beserta_penugasannya(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::factory()->denganSlot()->create(['shop_id' => $toko->id]);
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        $karyawan->shiftAssignments()->create([
            'shift_template_id' => $template->id,
            'aktif' => true,
        ]);

        $this->actingAs($this->dengan('pemilik'))
            ->delete("/admin/shift/{$template->id}")
            ->assertRedirect(route('admin.shift.index'));

        // Hard delete: template, slot, dan penugasan ikut hilang.
        $this->assertDatabaseMissing('shift_templates', ['id' => $template->id]);
        $this->assertDatabaseMissing('shift_slots', ['shift_template_id' => $template->id]);
        $this->assertDatabaseMissing('employee_shifts', ['shift_template_id' => $template->id]);
        // Karyawannya sendiri tetap ada, hanya penugasannya yang hilang.
        $this->assertDatabaseHas('employees', ['id' => $karyawan->id]);
    }

    public function test_hapus_template_tidak_menghapus_riwayat_absensi(): void
    {
        $toko = Shop::factory()->create();
        $template = ShiftTemplate::factory()->denganSlot()->create(['shop_id' => $toko->id]);
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        $absensi = Attendance::create([
            'employee_id' => $karyawan->id,
            'shop_id' => $toko->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => '08:00',
            'status_masuk' => AbsenMasukStatus::TepatWaktu,
        ]);

        $this->actingAs($this->dengan('pemilik'))
            ->delete("/admin/shift/{$template->id}")
            ->assertRedirect(route('admin.shift.index'));

        // Riwayat absensi tidak boleh ikut terhapus.
        $this->assertDatabaseHas('attendances', ['id' => $absensi->id]);
    }

    public function test_tampilan_daftar_ada_tombol_hapus(): void
    {
        ShiftTemplate::factory()->denganSlot()->create(['nama' => 'Shift Pagi']);

        $this->actingAs($this->dengan('pemilik'))
            ->get(route('admin.shift.index'))
            ->assertOk()
            ->assertSee('Hapus')
            ->assertSee('beserta slot dan penugasannya', false);
    }

    public function test_supervisor_hanya_melihat_template_miliknya(): void
    {
        $milik = Shop::factory()->create(['nama' => 'Toko Milik']);
        $orangLain = Shop::factory()->create(['nama' => 'Toko Orang Lain']);

        ShiftTemplate::factory()->denganSlot()->create(['nama' => 'Shift Milik', 'shop_id' => $milik->id]);
        ShiftTemplate::factory()->denganSlot()->create(['nama' => 'Shift Orang Lain', 'shop_id' => $orangLain->id]);

        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach($milik);

        $this->actingAs($supervisor->fresh())
            ->get('/admin/shift')
            ->assertOk()
            ->assertSee('Shift Milik')
            ->assertDontSee('Shift Orang Lain');
    }

    public function test_supervisor_lihat_template_global(): void
    {
        ShiftTemplate::factory()->denganSlot()->create([
            'nama' => 'Shift Global',
            'scope' => ShiftScope::Global,
        ]);

        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach(Shop::factory()->create());

        $this->actingAs($supervisor->fresh())
            ->get('/admin/shift')
            ->assertOk()
            ->assertSee('Shift Global');
    }

    public function test_supervisor_tidak_bisa_ubah_template_global(): void
    {
        $template = ShiftTemplate::factory()->denganSlot()->create(['scope' => ShiftScope::Global]);

        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach(Shop::factory()->create());

        $this->actingAs($supervisor->fresh())
            ->get("/admin/shift/{$template->id}/ubah")
            ->assertNotFound();
    }

    public function test_supervisor_tidak_bisa_buat_template_global(): void
    {
        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach(Shop::factory()->create());

        $this->actingAs($supervisor->fresh())
            ->post('/admin/shift', $this->payload(['scope' => ShiftScope::Global->value]))
            ->assertForbidden();

        $this->assertSame(0, ShiftTemplate::count());
    }

    public function test_supervisor_tidak_bisa_buat_template_untuk_toko_orang(): void
    {
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();

        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach($milik);

        $this->actingAs($supervisor->fresh())
            ->post('/admin/shift', $this->payload(['shop_id' => $orangLain->id]))
            ->assertForbidden();

        $this->assertSame(0, ShiftTemplate::count());
    }

    public function test_supervisor_tidak_bisa_ubah_template_toko_orang(): void
    {
        $orangLain = Shop::factory()->create();
        $template = ShiftTemplate::factory()->denganSlot()->create(['shop_id' => $orangLain->id]);

        $supervisor = $this->dengan('supervisor');
        $supervisor->shops()->attach(Shop::factory()->create());

        $this->actingAs($supervisor->fresh())
            ->put("/admin/shift/{$template->id}", $this->payload(['shop_id' => $orangLain->id]))
            ->assertNotFound();
    }

    public function test_tanpa_permission_ditolak(): void
    {
        foreach (['/admin/shift', '/admin/shift/tambah'] as $url) {
            // User baru tiap request: jawaban 403 tidak menyimpan session, jadi
            // user yang sama akan terkeluar sebagai "dipakai perangkat lain".
            $this->actingAs($this->dengan('karyawan', ['dashboard.lihat']))
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_bisa_lihat_tidak_bisa_kelola(): void
    {
        $this->actingAs($this->dengan('supervisor', ['dashboard.lihat', 'shift.lihat']))
            ->get('/admin/shift')
            ->assertOk();

        foreach (['/admin/shift/tambah'] as $url) {
            $this->actingAs($this->dengan('supervisor', ['dashboard.lihat', 'shift.lihat']))
                ->get($url)
                ->assertForbidden();
        }
    }
}
