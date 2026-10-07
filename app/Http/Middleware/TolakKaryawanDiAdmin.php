<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak akun yang tertaut ke data karyawan dari area admin.
 *
 * Role `karyawan` memegang permission yang sama dengan admin, misalnya
 * `dashboard.lihat` dan `pengajuan.lihat`, sehingga tanpa penjaga ini akun
 * karyawan bisa membuka `/admin` hanya dengan mengetik alamatnya. Cakupan toko
 * sudah gagal-tertutup sehingga tidak ada kebocoran data, tapi halamannya memang
 * tidak pernah boleh terbuka untuk akun karyawan.
 *
 * Seseorang yang perlu ikut mengelola cukup diputus tautan `user_id` dari data
 * karyawannya, bukan dengan memberi role baru ke akun yang sama.
 */
class TolakKaryawanDiAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->employee()->exists()) {
            abort(403, 'Halaman admin hanya untuk pengelola toko.');
        }

        return $next($request);
    }
}
