<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Support\CakupanToko;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Cakupan toko harus gagal-tertutup.
 *
 * Sebelumnya `semuaToko()` mengembalikan true untuk siapa pun yang tidak punya
 * baris di `user_shop`. Artinya supervisor yang belum pernah ditugaskan justru
 * melihat seluruh cabang — kebalikan dari yang diinginkan.
 */
class CakupanTokoTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(string $peran): User
    {
        return tap(User::factory()->create(), function (User $u) use ($peran) {
            $u->assignRole(Role::findOrCreate($peran, 'web'));
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->delete();
        Permission::query()->delete();
    }

    public function test_peran_global_boleh_semua_toko(): void
    {
        $cakupan = app(CakupanToko::class);

        $this->assertTrue($cakupan->semuaToko($this->pengguna('pemilik')));
    }

    public function test_supervisor_tanpa_penugasan_tidak_boleh_semua_toko(): void
    {
        $cakupan = app(CakupanToko::class);
        $supervisor = $this->pengguna('supervisor');

        // Ini inti perbaikannya: kosong berarti tidak ada toko, bukan semua toko.
        $this->assertFalse($cakupan->semuaToko($supervisor));
        $this->assertTrue($cakupan->idToko($supervisor)->isEmpty());
        $this->assertTrue($cakupan->tanpaPenugasan($supervisor));
    }

    public function test_supervisor_hanya_boleh_toko_yang_ditugaskan(): void
    {
        $cakupan = app(CakupanToko::class);
        $milik = Shop::factory()->create();
        $orangLain = Shop::factory()->create();

        $supervisor = $this->pengguna('supervisor');
        $supervisor->shops()->attach($milik);

        $this->assertFalse($cakupan->semuaToko($supervisor));
        $this->assertFalse($cakupan->tanpaPenugasan($supervisor));
        $this->assertTrue($cakupan->boleh($supervisor, $milik));
        $this->assertFalse($cakupan->boleh($supervisor, $orangLain));
    }

    public function test_batasi_query_menyembunyikan_baris_toko_luar_jangkauan(): void
    {
        $cakupan = app(CakupanToko::class);
        $milik = Shop::factory()->create();
        Shop::factory()->create();

        $supervisor = $this->pengguna('supervisor');
        $supervisor->shops()->attach($milik);

        $terlihat = $cakupan->batasi(Shop::query(), $supervisor, 'id')->pluck('id');

        $this->assertSame([$milik->id], $terlihat->all());
    }

    public function test_peran_global_bersama_toko_yang_ditugaskan_tetap_boleh_semua(): void
    {
        $cakupan = app(CakupanToko::class);
        $pemilik = $this->pengguna('pemilik');
        $pemilik->shops()->attach(Shop::factory()->create());

        $this->assertTrue($cakupan->semuaToko($pemilik));
        $this->assertFalse($cakupan->tanpaPenugasan($pemilik));
    }

    public function test_peran_global_bisa_dikustomisasi_lewat_config(): void
    {
        config(['absensi.peran_toko_global' => ['admin']]);

        $cakupan = app(CakupanToko::class);

        $this->assertTrue($cakupan->semuaToko($this->pengguna('admin')));
        $this->assertFalse($cakupan->semuaToko($this->pengguna('pemilik')));
    }
}
