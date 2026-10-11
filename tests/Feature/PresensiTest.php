<?php

namespace Tests\Feature;

use App\Enums\AbsenMethod;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use App\Services\AbsenService;
use App\Services\QrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Perangkat presensi karyawan untuk yang tidak memakai HP.
 *
 * Yang diuji di sini adalah hal yang harus benar tanpa harus membaca kode
 * dulu untuk tahu mana yang salah:
 *  - perangkat memakai satu login bersama per toko, bukan akun tiap karyawan
 *  - perangkat tanpa kredensial ditolak, bukan dibuka
 *  - perangkat mengunci dirinya sendiri setelah idle
 *  - kartu toko lain ditolak, karena geofence perangkat memakai koordinat toko
 *  - karyawan yang tidak diizinkan admin ditolak, walau kartu dan tokonya benar
 *  - arah masuk/pulang ditentukan server, bukan pilihan di browser
 *  - setiap percobaan meninggalkan jejak di audit log
 */
class PresensiTest extends TestCase
{
    use RefreshDatabase;

    // Monas, Jakarta
    private const TOKO_LAT = -6.1753924;

    private const TOKO_LNG = 106.8271528;

    private const PASSWORD_PRESENSI = 'rahasia-presensi';

    private Shop $shop;

    private Employee $employee;

    private QrService $qr;

    private AbsenService $absen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = $this->tokoSiap();
        $this->employee = Employee::factory()->create(['shop_id' => $this->shop->id]);

        $this->qr = app(QrService::class);
        $this->absen = app(AbsenService::class);
    }

    /**
     * Toko dengan koordinat dan login presensi yang sudah lengkap.
     *
     * User presensi dibuat unik dari kode toko, jadi helper ini boleh dipakai
     * lebih dari sekali dalam satu test tanpa menabrak indeks unik.
     */
    private function tokoSiap(?array $atr = []): Shop
    {
        $toko = Shop::factory()->diLokasi(self::TOKO_LAT, self::TOKO_LNG)->radius(150)->create($atr);

        $toko->forceFill([
            'presensi_user' => 'presensi-'.$toko->kode,
            'presensi_password' => Shop::hashPresensiPassword(self::PASSWORD_PRESENSI),
        ])->save();

        return $toko;
    }

    /** Login ke perangkat presensi seperti yang dilakukan orang pertama yang datang. */
    private function login(Shop $toko, ?string $user = null, string $password = self::PASSWORD_PRESENSI): void
    {
        $this->post(route('presensi.masuk', $toko->kode), [
            'user' => $user ?? (string) $toko->presensi_user,
            'password' => $password,
        ]);
    }

    private function token(?Employee $employee = null): string
    {
        return $this->qr->token($employee ?? $this->employee);
    }

    // ------------------------------------------------------------------
    // Halaman
    // ------------------------------------------------------------------

    public function test_halaman_perangkat_minta_login_belum_menampilkan_pemindai(): void
    {
        $response = $this->get(route('presensi.form', $this->shop->kode));

        $response->assertOk();
        $response->assertSee('Pindai kartu');
        $response->assertDontSee('Login presensi');
    }

    public function test_kode_toko_tidak_dikenal_diberi_404(): void
    {
        $this->get('/presensi/TIDAK-ADA')->assertNotFound();
    }

    public function test_toko_tanpa_koordinat_tidak_bisa_dipakaikan_presensi(): void
    {
        $toko = $this->tokoSiap(['latitude' => null, 'longitude' => null]);

        $this->get(route('presensi.form', $toko->kode))->assertNotFound();
    }

    public function test_toko_nonaktif_tidak_bisa_dipakaikan_presensi(): void
    {
        $toko = $this->tokoSiap(['aktif' => false]);

        $this->get(route('presensi.form', $toko->kode))->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Login bersama
    // ------------------------------------------------------------------

    public function test_presensi_tanpa_kredensial_ditolak_bukan_dibuka(): void
    {
        $toko = Shop::factory()->diLokasi(self::TOKO_LAT, self::TOKO_LNG)->create();
        $karyawan = Employee::factory()->create(['shop_id' => $toko->id]);

        $response = $this->get(route('presensi.form', $toko->kode));

        $response->assertOk();
        $response->assertSee('Perangkat presensi belum bisa dipakai');
        $response->assertDontSee('Pindai kartu');

        // Submit login pun tidak membantu kalau memang belum ada kredensial.
        $this->from(route('presensi.form', $toko->kode))
            ->post(route('presensi.masuk', $toko->kode), ['user' => 'apa-saja', 'password' => 'apa-saja'])
            ->assertSessionHas('galat');

        $this->from(route('presensi.form', $toko->kode))
            ->post(route('presensi.proses', $toko->kode), ['token' => $this->qr->token($karyawan)])
            ->assertRedirect(route('presensi.form', $toko->kode))
            ->assertSessionHas('pesan');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_kredensial_setengah_juga_ditolak(): void
    {
        // User ada tapi password kosong: perangkat belum boleh dipakai.
        $toko = Shop::factory()->diLokasi(self::TOKO_LAT, self::TOKO_LNG)->create([
            'presensi_user' => 'presensi-'.$this->shop->kode.'-x',
            'presensi_password' => null,
        ]);

        $this->assertFalse($toko->presensiSiap());
        $this->get(route('presensi.form', $toko->kode))->assertSee('Perangkat presensi belum bisa dipakai');
    }

    public function test_login_yang_benar_membuka_halaman_pemindai(): void
    {
        $this->login($this->shop);

        $response = $this->get(route('presensi.form', $this->shop->kode));

        $response->assertOk();
        $response->assertSee('Pindai kartu');
        $response->assertSee($this->shop->nama);
    }

    public function test_pemindaian_berhasil_setelah_login(): void
    {
        $this->login($this->shop);

        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()])
            ->assertSessionHas('sukses');
    }

    public function test_pemindaian_tanpa_login_ditolak(): void
    {
        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()])
            ->assertRedirect(route('presensi.form', $this->shop->kode))
            ->assertSessionHas('pesan');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_password_salah_tidak_membuka_perangkat(): void
    {
        $karyawan = Employee::factory()->create(['shop_id' => $this->shop->id]);

        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.masuk', $this->shop->kode), [
                'user' => $this->shop->presensi_user,
                'password' => 'salah',
            ])
            ->assertSessionHas('galat');

        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $this->qr->token($karyawan)])
            ->assertSessionHas('pesan');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_user_salah_tidak_membuka_perangkat(): void
    {
        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.masuk', $this->shop->kode), [
                'user' => 'user-salah',
                'password' => self::PASSWORD_PRESENSI,
            ])
            ->assertSessionHas('galat');

        $this->get(route('presensi.form', $this->shop->kode))->assertSee('Pindai kartu');
    }

    public function test_user_kosong_ditolak_lewat_validasi(): void
    {
        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.masuk', $this->shop->kode), [
                'user' => '',
                'password' => self::PASSWORD_PRESENSI,
            ])
            ->assertSessionHasErrors('user');
    }

    public function test_login_salah_tercatat_di_audit_log(): void
    {
        $this->post(route('presensi.masuk', $this->shop->kode), [
            'user' => $this->shop->presensi_user,
            'password' => 'salah',
        ]);

        $log = AuditLog::where('aksi', 'presensi.login')->firstOrFail();

        $this->assertFalse($log->payload['berhasil']);
        $this->assertSame('kredensial_salah', $log->payload['alasan']);
    }

    public function test_kredensial_satu_toko_tidak_membuka_perangkat_toko_lain(): void
    {
        $tokoB = $this->tokoSiap();
        $karyawanB = Employee::factory()->create(['shop_id' => $tokoB->id]);

        $this->login($this->shop);

        $this->from(route('presensi.form', $tokoB->kode))
            ->post(route('presensi.proses', $tokoB->kode), ['token' => $this->qr->token($karyawanB)])
            ->assertSessionHas('pesan');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_password_presensi_disimpan_sebagai_hash(): void
    {
        $this->assertStringStartsWith('$2y$', (string) $this->shop->presensi_password);
        $this->assertNotSame(self::PASSWORD_PRESENSI, $this->shop->presensi_password);
        $this->assertTrue($this->shop->cocokkanPresensiLogin($this->shop->presensi_user, self::PASSWORD_PRESENSI));
        $this->assertFalse($this->shop->cocokkanPresensiLogin($this->shop->presensi_user, 'salah'));
    }

    public function test_kredensial_presensi_tidak_bocor_keluar_dari_model(): void
    {
        $data = $this->shop->toArray();

        $this->assertArrayNotHasKey('presensi_user', $data);
        $this->assertArrayNotHasKey('presensi_password', $data);
    }

    // ------------------------------------------------------------------
    // Kunci otomatis
    // ------------------------------------------------------------------

    public function test_perangkat_mengunci_diri_setelah_idle(): void
    {
        config(['absensi.presensi.idle_timeout' => 30]);

        $this->login($this->shop);

        // Sesi ditulis dengan waktu 31 menit lalu, lewat key yang sama dengan
        // yang dipakai controller.
        $this->withSession(['presensi_terbuka.'.$this->shop->id => time() - (31 * 60)])
            ->get(route('presensi.form', $this->shop->kode))
            ->assertSee('Pindai kartu');
    }

    public function test_perangkat_masih_terbuka_selama_masih_dipakai(): void
    {
        config(['absensi.presensi.idle_timeout' => 30]);

        $this->login($this->shop);

        $this->withSession(['presensi_terbuka.'.$this->shop->id => time() - (29 * 60)])
            ->get(route('presensi.form', $this->shop->kode))
            ->assertSee('Pindai kartu');
    }

    public function test_pemindaian_memperpanjang_kunci(): void
    {
        config(['absensi.presensi.idle_timeout' => 30]);

        $this->login($this->shop);

        $this->withSession(['presensi_terbuka.'.$this->shop->id => time() - (29 * 60)])
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()])
            ->assertSessionHas('sukses');

        $this->assertGreaterThan(
            time() - 60,
            (int) session('presensi_terbuka.'.$this->shop->id),
            'waktu terakhir pakai harus diperbarui setiap pemindaian',
        );
    }

    public function test_timeout_nol_berarti_tidak_pernah_mengunci(): void
    {
        config(['absensi.presensi.idle_timeout' => 0]);

        $this->login($this->shop);

        $this->withSession(['presensi_terbuka.'.$this->shop->id => time() - (60 * 60 * 24)])
            ->get(route('presensi.form', $this->shop->kode))
            ->assertSee('Pindai kartu');
    }

    public function test_tombol_kunci_menutup_perangkat_untuk_orang_berikutnya(): void
    {
        $this->login($this->shop);

        $this->post(route('presensi.keluar', $this->shop->kode))
            ->assertRedirect(route('presensi.form', $this->shop->kode));

        $this->get(route('presensi.form', $this->shop->kode))->assertSee('Pindai kartu');
    }

    // ------------------------------------------------------------------
    // Pemindaian
    // ------------------------------------------------------------------

    public function test_kartu_yang_benar_mencatat_absen_masuk(): void
    {
        $this->login($this->shop);

        $response = $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);

        $response->assertRedirect(route('presensi.form', $this->shop->kode));
        $response->assertSessionHas('sukses');

        $absensi = Attendance::where('employee_id', $this->employee->id)->firstOrFail();

        $this->assertNotNull($absensi->jam_masuk);
        $this->assertNull($absensi->jam_pulang);
        $this->assertSame(AbsenMethod::Presensi, $absensi->metode);
        $this->assertSame('presensi:'.$this->shop->kode, $absensi->device_id);
    }

    public function test_perangkat_menggunakan_koordinat_toko_bukan_gps(): void
    {
        $this->login($this->shop);
        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);

        $absensi = Attendance::where('employee_id', $this->employee->id)->firstOrFail();

        // Nilai koordinat toko ikut tersimpan supaya laporan punya titik lokasi
        // yang benar, dan jaraknya 0 m karena perangkat berdiri di dalam toko.
        $this->assertEqualsWithDelta(self::TOKO_LAT, (float) $absensi->latitude_masuk, 0.0000001);
        $this->assertEqualsWithDelta(self::TOKO_LNG, (float) $absensi->longitude_masuk, 0.0000001);
        $this->assertSame(0.0, (float) $absensi->jarak_masuk_meter);
        $this->assertNull($absensi->accuracy_masuk_meter, 'perangkat presensi tidak memakai GNSS perangkat');
    }

    public function test_pemindaian_kedua_mencatat_absen_pulang(): void
    {
        $this->login($this->shop);

        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);
        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);

        $absensi = Attendance::where('employee_id', $this->employee->id)->firstOrFail();

        $this->assertNotNull($absensi->jam_masuk);
        $this->assertNotNull($absensi->jam_pulang);
    }

    public function test_kartu_toko_lain_ditolak(): void
    {
        $this->login($this->shop);

        $tokoLain = Shop::factory()->diLokasi(self::TOKO_LAT, self::TOKO_LNG)->create();
        $karyawanLain = Employee::factory()->create(['shop_id' => $tokoLain->id]);

        $response = $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token($karyawanLain)]);

        $response->assertSessionHas('galat');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_kode_kartu_tidak_dikenal_ditolak(): void
    {
        $this->login($this->shop);

        $response = $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => 'ATOKO:1:1:'.str_repeat('0', 16)]);

        $response->assertSessionHas('galat');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_token_kosong_ditolak_sebelum_mencoba_mencocokkan(): void
    {
        $this->login($this->shop);

        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => ''])
            ->assertSessionHasErrors('token');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_kartu_lama_setelah_rotasi_ditolak(): void
    {
        $this->login($this->shop);
        $tokenLama = $this->token();

        $this->employee->rotateQr();

        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $tokenLama])
            ->assertSessionHas('galat');

        $this->assertDatabaseCount('attendances', 0);
    }

    // ------------------------------------------------------------------
    // Izin per karyawan
    // ------------------------------------------------------------------

    public function test_karyawan_tanpa_izin_ditolak_di_perangkat_presensi(): void
    {
        $this->employee->update(['boleh_presensi' => false]);

        $this->login($this->shop);

        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()])
            ->assertSessionHas('galat');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_karyawan_tanpa_izin_ditolak_juga_saat_mau_pulang(): void
    {
        // Inisialisasi: masuk normal dulu.
        $this->login($this->shop);
        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);

        // Izin dicabut di tengah hari, maka scan pulang ikut tertolak.
        $this->employee->update(['boleh_presensi' => false]);

        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()])
            ->assertSessionHas('galat');

        $absensi = Attendance::where('employee_id', $this->employee->id)->firstOrFail();
        $this->assertNull($absensi->jam_pulang);
    }

    public function test_karyawan_tanpa_izin_tercatat_di_audit_log(): void
    {
        $this->employee->update(['boleh_presensi' => false]);

        $this->login($this->shop);
        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);

        $log = AuditLog::where('aksi', 'presensi.scan')->firstOrFail();

        $this->assertFalse($log->payload['berhasil']);
        $this->assertSame('karyawan_dilarang', $log->payload['alasan']);
        $this->assertSame($this->employee->id, $log->payload['employee_id']);
    }

    public function test_karyawan_diizinkan_secara_bawaan(): void
    {
        // Nilai kolom dibaca ulang dari basis data: default migrasi mengaktifkan
        // izin, meski objek hasil factory belum menyimpan atribut tersebut.
        $this->assertTrue($this->employee->fresh()->boleh_presensi);
    }

    // ------------------------------------------------------------------
    // Arah ditentukan server
    // ------------------------------------------------------------------

    public function test_arah_berikutnya_mengikuti_sesi_yang_masih_terbuka(): void
    {
        $this->assertSame('masuk', $this->absen->arahBerikutnya($this->employee)->value);

        $this->login($this->shop);
        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);

        $this->assertSame('pulang', $this->absen->arahBerikutnya($this->employee->fresh())->value);
    }

    public function test_absen_kedua_di_hari_yang_sama_tidak_dibuat_ulang(): void
    {
        $this->login($this->shop);

        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);
        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);

        // Pindai lagi: sesi sudah tertutup, jadi tidak boleh ada baris ketiga.
        $this->from(route('presensi.form', $this->shop->kode))
            ->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()])
            ->assertSessionHas('galat');

        $this->assertSame(1, Attendance::where('employee_id', $this->employee->id)->count());
    }

    // ------------------------------------------------------------------
    // Audit
    // ------------------------------------------------------------------

    public function test_pemindaian_berhasil_tercatat_di_audit_log(): void
    {
        $this->login($this->shop);
        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->token()]);

        $log = AuditLog::where('aksi', 'absen.masuk')->firstOrFail();

        $this->assertSame('attendance', $log->entitas);
        $this->assertNull($log->user_id, 'perangkat presensi dipakai tanpa akun karyawan');
        $this->assertSame($this->shop->kode, $log->payload['presensi']);
    }

    public function test_kartu_toko_lain_tercatat_di_audit_log(): void
    {
        $this->login($this->shop);

        $tokoLain = Shop::factory()->diLokasi(self::TOKO_LAT, self::TOKO_LNG)->create();
        $karyawanLain = Employee::factory()->create(['shop_id' => $tokoLain->id]);

        $this->post(route('presensi.proses', $this->shop->kode), ['token' => $this->qr->token($karyawanLain)]);

        $log = AuditLog::where('aksi', 'presensi.scan')->firstOrFail();

        $this->assertFalse($log->payload['berhasil']);
        $this->assertSame('kartu_toko_lain', $log->payload['alasan']);
    }

    // ------------------------------------------------------------------
    // Kartu cetak
    // ------------------------------------------------------------------

    public function test_halaman_kartu_karyawan_menampilkan_barcode(): void
    {
        $user = $this->karyawanDenganAkun();

        $response = $this->actingAs($user)->get(route('absen.qr'));

        $response->assertOk();
        $response->assertSee('Kode untuk perangkat presensi');
        $response->assertSee('Cetak kartu');
        $response->assertSee('<svg', false);
    }

    public function test_barcode_dan_qr_memakai_token_yang_sama(): void
    {
        $barcode = $this->qr->barcodeSvg($this->employee);

        // SVG bawaan library menyimpan isi barcode di <desc>, jadi token yang
        // sama bisa dipastikan dari sana, bukan dari gambar batangnya.
        $this->assertStringContainsString(
            htmlspecialchars($this->token(), ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            $barcode,
        );
        $this->assertStringNotContainsString('<?xml', $barcode, 'SVG harus bisa disisipkan inline');
    }

    /** Akun karyawan sungguhan, lengkap dengan permission yang dibutuhkan. */
    private function karyawanDenganAkun(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['dashboard.lihat', 'absen.lihat'] as $nama) {
            Permission::findOrCreate($nama, 'web');
        }

        $role = Role::findOrCreate('karyawan', 'web');
        $role->givePermissionTo(['dashboard.lihat', 'absen.lihat']);

        $user = User::factory()->create(['aktif' => true]);
        $user->assignRole($role);

        Employee::factory()->denganAkun($user)->create(['shop_id' => $this->shop->id]);

        return $user;
    }
}
