<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Bantu menyematkan gambar disk "public" sebagai data URI.
 *
 * PDF (DomPDF) dan lingkungan lain tidak selalu bisa membaca berkas lewat URL
 * relatif atau path Windows, jadi isi berkas dikodekan langsung ke base64.
 * Ini juga membuat laporan tetap utuh walau dibuka tanpa koneksi.
 */
class Gambar
{
    public static function dataUri(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        return 'data:'.$disk->mimeType($path).';base64,'.base64_encode($disk->get($path));
    }
}
