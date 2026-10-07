<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanduanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_panduan_bisa_dibuka_tanpa_login(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();
        $response->assertSee('Panduan Penggunaan');
    }

    public function test_halaman_panduan_menggunakan_layout_panduan_sendiri(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();
        $response->assertSee('Daftar isi');
        $response->assertSee('Cara memakai aplikasi absensi Toko MM');
        $response->assertSee('Mulai Cepat', false);
    }

    public function test_semua_delapan_bagian_panduan_tampil(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();

        foreach ([
            'tentang',
            'mulai-cepat',
            'peran',
            'karyawan',
            'admin',
            'cara-kerja-absen',
            'masalah',
            'pertanyaan',
        ] as $id) {
            $response->assertSee('id="'.$id.'"', false);
        }
    }

    public function test_tag_komponen_blade_tidak_bocor_ke_halaman(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();
        $response->assertDontSee('<x-panduan', false);
        $response->assertDontSee('@php', false);
        $response->assertDontSee('{!!', false);
    }

    public function test_panduan_menjelaskan_perangkat_presensi_dan_kartu_absen(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();

        foreach ([
            'presensi',
            'barcode',
            'Kartu absen',
            'User presensi',
            'dipakai bersama',
            'perangkat presensi',
        ] as $frasa) {
            $response->assertSee($frasa);
        }
    }

    public function test_daftar_isi_memuat_semua_bagian(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();

        foreach ([
            'Tentang Aplikasi',
            'Mulai Cepat',
            'Peran dan Hak Akses',
            'Panduan Karyawan',
            'Panduan Admin',
            'Cara Kerja Absen',
            'Masalah dan Solusinya',
            'Pertanyaan Umum',
        ] as $judul) {
            $response->assertSee($judul);
        }
    }

    public function test_isi_panduan_mencakup_ketentuan_penting(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();

        foreach ([
            'Lima kali gagal',
            '60 detik',
            'Kartu QR',
            'Template Shift',
            'Window Shift',
            'Radius (meter)',
            'Di luar jam cut-off',
            'tidak ada shift di hari ini',
            '8 detik',
        ] as $frasa) {
            $response->assertSee($frasa);
        }
    }

    public function test_panduan_menautkan_ke_modul_geofence_dan_pengguna(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();
        $response->assertSee('geofence');
        $response->assertSee('Pengguna');
    }

    public function test_kolom_pencarian_ada(): void
    {
        $response = $this->get('/panduan');

        $response->assertOk();
        $response->assertSee('placeholder="Cari panduan"', false);
    }

    public function test_halaman_masuk_tautkan_ke_panduan(): void
    {
        $this->get('/masuk')->assertOk()->assertSee('Butuh bantuan? Baca panduan');
    }
}
