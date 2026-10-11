<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan berkas yang tersimpan di disk publik (foto karyawan, bukti
 * transaksi kas) lewat aplikasi, bukan lewat symlink `public/storage`.
 *
 * Di shared hosting, `php artisan storage:link` sering gagal (symlink dilarang)
 * sehingga semua berkas unggahan 404 sementara aset biasa tetap tampil. Dengan
 * route ini, syarat symlink hilang dan gambar tetap muncul.
 */
class MediaController extends Controller
{
    public function tampil(string $path): StreamedResponse
    {
        // Normalisasi pemisah lalu tolak upaya keluar dari disk publik.
        $path = str_replace('\\', '/', $path);

        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, "\0")) {
            abort(404);
        }

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        // `response` menyajikan streaming dengan Content-Type yang benar,
        // mendukung rentang byte (video/pratinjau) dan permintaan bersyarat.
        // Header cache lama dipakai supaya nama berkas acak tidak diunduh ulang.
        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
