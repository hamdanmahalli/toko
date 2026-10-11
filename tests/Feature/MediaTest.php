<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Penyajian berkas publik lewat route /media (pengganti symlink public/storage).
 */
class MediaTest extends TestCase
{
    public function test_berkas_publik_disajikan(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('karyawan/foto.jpg', 'isi-gambar');

        $respon = $this->get('/media/karyawan/foto.jpg');
        $respon->assertOk();
        $this->assertSame('isi-gambar', $respon->streamedContent());
    }

    public function test_berkas_tidak_ada_menghasilkan_404(): void
    {
        Storage::fake('public');

        $this->get('/media/karyawan/tidak-ada.jpg')->assertNotFound();
    }

    public function test_keluar_dari_folder_publik_ditolak(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('.env', 'RAHASIA');

        // Upaya menembus folder publik harus ditolak, bukan menyajikan .env.
        $this->get('/media/karyawan/../../.env')->assertNotFound();
        $this->get('/media/%2e%2e%2f%2e%2e%2f.env')->assertNotFound();
    }
}
