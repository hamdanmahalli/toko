<?php

namespace Tests\Feature;

use App\Enums\JenisKas;
use App\Enums\KategoriKas;
use App\Models\CashBook;
use App\Models\CashBookTransaction;
use App\Models\Employee;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class KasTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSION = [
        'dashboard.lihat',
        'kas.lihat',
        'kas.buat',
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

    private function karyawan(array $permission = self::PERMISSION): Employee
    {
        $user = User::factory()->create();
        $peran = Role::findOrCreate('karyawan', 'web');
        $peran->givePermissionTo($permission);
        $user->assignRole($peran);

        return Employee::factory()->denganAkun($user->fresh())->create([
            'shop_id' => Shop::factory()->create()->id,
        ]);
    }

    /** Setiap request HTTP memakai session id baru; bersihkan agar tidak dianggap perangkat lain. */
    private function lanjut(Employee $karyawan): void
    {
        $karyawan->user->forceFill(['active_session_id' => null])->save();
    }

    public function test_halaman_kas_karyawan_tampil(): void
    {
        $karyawan = $this->karyawan();
        CashBook::factory()->untuk($karyawan)->create(['nama' => 'Kas Warung']);

        $this->actingAs($karyawan->user)
            ->get('/kas')
            ->assertOk()
            ->assertSee('Buku Kas')
            ->assertSee('Kas Warung')
            ->assertSee('Buku baru');
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/kas')->assertRedirect(route('masuk'));
    }

    public function test_tanpa_permission_ditolak(): void
    {
        $karyawan = $this->karyawan(['dashboard.lihat']);

        $this->actingAs($karyawan->user)->get('/kas')->assertForbidden();
    }

    public function test_karyawan_membuat_buku_kas(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/kas', ['nama' => 'Kas Utama', 'saldo_awal' => 'Rp 50.000', 'aktif' => '1'])
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('cash_books', [
            'employee_id' => $karyawan->id,
            'nama' => 'Kas Utama',
            'saldo_awal' => 50000,
        ]);
    }

    public function test_transaksi_masuk_menambah_saldo(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create(['saldo_awal' => 10000]);

        $this->actingAs($karyawan->user)
            ->post("/kas/{$buku->id}/transaksi", [
                'jenis' => JenisKas::Masuk->value,
                'kategori' => KategoriKas::Penjualan->value,
                'tanggal' => '2026-03-01',
                'jumlah' => '25.000',
                'keterangan' => 'Jualan pagi',
            ])
            ->assertRedirect()
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('cash_book_transactions', [
            'cash_book_id' => $buku->id,
            'jenis' => JenisKas::Masuk->value,
            'kategori' => KategoriKas::Penjualan->value,
            'jumlah' => 25000,
        ]);

        $this->assertSame(35000.0, $buku->fresh()->saldoSaatIni());
    }

    public function test_transaksi_keluar_mengurangi_saldo(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create(['saldo_awal' => 100000]);

        $this->actingAs($karyawan->user)
            ->post("/kas/{$buku->id}/transaksi", [
                'jenis' => 'keluar',
                'kategori' => KategoriKas::Belanja->value,
                'tanggal' => '2026-03-02',
                'jumlah' => '40000',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cash_book_transactions', [
            'cash_book_id' => $buku->id,
            'jenis' => JenisKas::Keluar->value,
            'kategori' => KategoriKas::Belanja->value,
            'jumlah' => 40000,
        ]);

        $this->assertSame(60000.0, $buku->fresh()->saldoSaatIni());
    }

    public function test_transaksi_wajib_nominal(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create();

        $this->actingAs($karyawan->user)
            ->post("/kas/{$buku->id}/transaksi", [
                'jenis' => 'masuk',
                'kategori' => KategoriKas::Penjualan->value,
                'tanggal' => '2026-03-01',
                'jumlah' => '',
            ])
            ->assertSessionHasErrors('jumlah');

        $this->assertDatabaseCount('cash_book_transactions', 0);
    }

    public function test_kategori_tidak_dikenal_ditolak(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create();

        $this->actingAs($karyawan->user)
            ->post("/kas/{$buku->id}/transaksi", [
                'jenis' => 'masuk',
                'kategori' => 'entah',
                'tanggal' => '2026-03-01',
                'jumlah' => '1000',
            ])
            ->assertSessionHasErrors('kategori');
    }

    public function test_halaman_input_transaksi_terpisah(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create();

        $this->actingAs($karyawan->user)
            ->get("/kas/{$buku->id}/transaksi/tambah")
            ->assertOk()
            ->assertSee('Catat transaksi')
            ->assertSee('Total Pemasukan')
            ->assertSee('Uang masuk')
            ->assertSee('Uang keluar')
            ->assertSee('Penjualan')
            ->assertSee('Belanja')
            ->assertSee('Simpan');
    }

    public function test_kategori_bawaan_disemai_untuk_karyawan(): void
    {
        $karyawan = $this->karyawan();

        $this->assertDatabaseCount('cash_categories', 0);

        $this->actingAs($karyawan->user)->get('/kas')->assertOk();

        $this->assertDatabaseCount('cash_categories', count(KategoriKas::cases()));
        $this->assertDatabaseHas('cash_categories', [
            'employee_id' => $karyawan->id,
            'kode' => KategoriKas::Penjualan->value,
            'nama' => KategoriKas::Penjualan->label(),
            'jenis' => JenisKas::Masuk->value,
        ]);
    }

    public function test_kategori_harus_sesuai_jenis(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create();

        $this->actingAs($karyawan->user)
            ->post("/kas/{$buku->id}/transaksi", [
                'jenis' => 'masuk',
                'kategori' => KategoriKas::Belanja->value,
                'tanggal' => '2026-03-01',
                'jumlah' => '1000',
            ])
            ->assertSessionHasErrors('kategori');

        $this->assertDatabaseCount('cash_book_transactions', 0);
    }

    public function test_kategori_nonaktif_tidak_bisa_dipakai(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create();

        $this->actingAs($karyawan->user)->get('/kas')->assertOk();

        $karyawan->cashCategories()->where('kode', KategoriKas::Penjualan->value)->update(['aktif' => false]);

        $this->lanjut($karyawan);

        $this->actingAs($karyawan->user)
            ->post("/kas/{$buku->id}/transaksi", [
                'jenis' => 'masuk',
                'kategori' => KategoriKas::Penjualan->value,
                'tanggal' => '2026-03-01',
                'jumlah' => '1000',
            ])
            ->assertSessionHasErrors('kategori');
    }

    public function test_kategori_bisa_ditambah_diubah_dan_dihapus(): void
    {
        $karyawan = $this->karyawan();

        $this->actingAs($karyawan->user)
            ->post('/kas/kategori', ['nama' => 'Beli stok', 'jenis' => 'keluar', 'urutan' => 3, 'aktif' => '1'])
            ->assertRedirect(route('kas.kategori.index'));

        $kategori = $karyawan->cashCategories()->where('nama', 'Beli stok')->firstOrFail();
        $this->assertSame('beli_stok', $kategori->kode);
        $this->assertSame(JenisKas::Keluar, $kategori->jenis);

        $this->lanjut($karyawan);

        $this->actingAs($karyawan->user)
            ->put("/kas/kategori/{$kategori->id}", ['nama' => 'Belanja stok', 'jenis' => 'keluar', 'urutan' => 1, 'aktif' => '1'])
            ->assertRedirect(route('kas.kategori.index'));

        $kategori->refresh();
        $this->assertSame('Belanja stok', $kategori->nama);
        $this->assertSame('beli_stok', $kategori->kode, 'Kode tidak berubah saat nama diubah.');

        $this->lanjut($karyawan);

        $this->actingAs($karyawan->user)
            ->delete("/kas/kategori/{$kategori->id}")
            ->assertRedirect(route('kas.kategori.index'));

        $this->assertDatabaseMissing('cash_categories', ['id' => $kategori->id]);
    }

    public function test_kategori_terpakai_atau_bawaan_dinonaktifkan_bukan_dihapus(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create(['saldo_awal' => 0]);

        // Sentuh /kas dulu supaya kategori bawaan tersemai.
        $this->actingAs($karyawan->user)->get('/kas')->assertOk();

        $bawaan = $karyawan->cashCategories()->where('kode', KategoriKas::Penjualan->value)->firstOrFail();

        $this->lanjut($karyawan);

        $this->actingAs($karyawan->user)
            ->delete("/kas/kategori/{$bawaan->id}")
            ->assertRedirect(route('kas.kategori.index'));

        $this->assertDatabaseHas('cash_categories', ['id' => $bawaan->id, 'aktif' => false]);
    }

    public function test_laporan_memakai_nama_kategori_dari_tabel(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create();

        $this->actingAs($karyawan->user)->get('/kas')->assertOk();

        $karyawan->cashCategories()
            ->where('kode', KategoriKas::Penjualan->value)
            ->update(['nama' => 'Omzet harian']);

        CashBookTransaction::factory()->untuk($buku)->masuk()->create([
            'kategori' => KategoriKas::Penjualan->value,
            'tanggal' => '2026-02-05',
            'jumlah' => 9000,
        ]);

        $this->lanjut($karyawan);

        $this->actingAs($karyawan->user)
            ->get('/kas/laporan?dari=2026-02-01&sampai=2026-02-28')
            ->assertOk()
            ->assertSee('Omzet harian');
    }

    public function test_transaksi_menghapus_saldo_kembali(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create(['saldo_awal' => 0]);
        $transaksi = CashBookTransaction::factory()->masuk()->untuk($buku)->create(['jumlah' => 15000]);

        $this->actingAs($karyawan->user)
            ->delete("/kas/{$buku->id}/transaksi/{$transaksi->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('cash_book_transactions', ['id' => $transaksi->id]);
        $this->assertSame(0.0, $buku->fresh()->saldoSaatIni());
    }

    public function test_transaksi_bisa_diubah(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create(['saldo_awal' => 0]);
        $trx = CashBookTransaction::factory()->untuk($buku)->masuk()->create([
            'kategori' => KategoriKas::Penjualan->value,
            'tanggal' => '2026-03-01',
            'jumlah' => 5000,
        ]);

        $this->actingAs($karyawan->user)
            ->get("/kas/{$buku->id}/transaksi/{$trx->id}/ubah")
            ->assertOk()
            ->assertSee('Ubah transaksi')
            ->assertSee('Hapus transaksi');

        $this->lanjut($karyawan);

        $this->actingAs($karyawan->user)
            ->put("/kas/{$buku->id}/transaksi/{$trx->id}", [
                'jenis' => 'keluar',
                'kategori' => KategoriKas::Belanja->value,
                'tanggal' => '2026-03-05',
                'jumlah' => '12.000',
                'keterangan' => 'Belanja bahan',
            ])
            ->assertRedirect(route('kas.show', $buku));

        $this->assertDatabaseHas('cash_book_transactions', [
            'id' => $trx->id,
            'jenis' => 'keluar',
            'kategori' => KategoriKas::Belanja->value,
            'jumlah' => 12000,
            'keterangan' => 'Belanja bahan',
        ]);
    }

    public function test_buku_karyawan_lain_tidak_ditemukan(): void
    {
        $milik = $this->karyawan();
        $lain = $this->karyawan();
        $bukuLain = CashBook::factory()->untuk($lain)->create();

        $this->actingAs($milik->user)
            ->get("/kas/{$bukuLain->id}")
            ->assertNotFound();

        // Setiap panggilan HTTP di test memakai session id baru, jadi kosongkan
        // penanda perangkat agar request kedua tidak dianggap sesi lain.
        $milik->user->forceFill(['active_session_id' => null])->save();

        $this->actingAs($milik->user)
            ->delete("/kas/{$bukuLain->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('cash_books', ['id' => $bukuLain->id]);
    }

    public function test_menghapus_buku_menghapus_transaksinya(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create();
        CashBookTransaction::factory()->untuk($buku)->create();

        $this->actingAs($karyawan->user)
            ->delete("/kas/{$buku->id}")
            ->assertRedirect(route('kas.index'));

        $this->assertDatabaseCount('cash_books', 0);
        $this->assertDatabaseCount('cash_book_transactions', 0);
    }

    public function test_laporan_menghitung_ringkasan_periode(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create(['saldo_awal' => 5000]);

        // Di luar periode: harus masuk saldo awal, bukan dihitung sebagai pemasukan.
        CashBookTransaction::factory()->untuk($buku)->masuk()->create(['tanggal' => '2026-01-31', 'jumlah' => 1000]);
        CashBookTransaction::factory()->untuk($buku)->masuk()->create(['tanggal' => '2026-02-05', 'jumlah' => 20000]);
        CashBookTransaction::factory()->untuk($buku)->keluar()->create(['tanggal' => '2026-02-10', 'jumlah' => 8000]);

        $this->actingAs($karyawan->user)
            ->get('/kas/laporan?dari=2026-02-01&sampai=2026-02-28')
            ->assertOk()
            ->assertSee('Saldo awal')
            ->assertSee('Rp 6.000')
            ->assertSee('Rp 20.000')
            ->assertSee('Rp 8.000')
            ->assertSee('Rp 18.000');
    }

    public function test_laporan_pdf_dan_excel_bisa_diunduh(): void
    {
        $karyawan = $this->karyawan();
        $buku = CashBook::factory()->untuk($karyawan)->create();
        CashBookTransaction::factory()->untuk($buku)->masuk()->create(['tanggal' => '2026-02-05']);

        $this->actingAs($karyawan->user)
            ->get('/kas/laporan/pdf?dari=2026-02-01&sampai=2026-02-28')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $karyawan->user->forceFill(['active_session_id' => null])->save();

        $this->actingAs($karyawan->user)
            ->get('/kas/laporan/excel?dari=2026-02-01&sampai=2026-02-28')
            ->assertOk();
    }

    public function test_enum_kategori_menurunkan_jenis(): void
    {
        $this->assertSame(JenisKas::Masuk, KategoriKas::Penjualan->jenis());
        $this->assertSame(JenisKas::Masuk, KategoriKas::Modal->jenis());
        $this->assertSame(JenisKas::Keluar, KategoriKas::Belanja->jenis());
        $this->assertSame(JenisKas::Keluar, KategoriKas::Lainnya->jenis());

        $masuk = KategoriKas::optionsFor(JenisKas::Masuk);
        $this->assertArrayHasKey(KategoriKas::Penjualan->value, $masuk);
        $this->assertArrayNotHasKey(KategoriKas::Belanja->value, $masuk);
    }
}
