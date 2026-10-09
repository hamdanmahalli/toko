<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;

/**
 * Kirim notifikasi uji ke perangkat APK milik seorang pengguna.
 *
 * Contoh:
 *   php artisan push:uji pemilik@tokomm.online --pesan="Ini uji coba"
 */
class UjiPush extends Command
{
    protected $signature = 'push:uji
        {pengguna : Email atau username penerima}
        {--judul=Absensi Toko : Judul notifikasi}
        {--pesan=Notifikasi uji dari Absensi Toko MM : Isi notifikasi}';

    protected $description = 'Kirim notifikasi FCM uji ke perangkat terdaftar milik pengguna.';

    public function handle(FcmService $fcm): int
    {
        if (! $fcm->terkonfigurasi()) {
            $this->error('FCM belum dikonfigurasi. Isi FCM_PROJECT_ID dan kredensial service account dulu.');

            return self::FAILURE;
        }

        $login = (string) $this->argument('pengguna');
        $kolom = str_contains($login, '@') ? 'email' : 'username';

        $user = User::query()->where($kolom, $login)->first();

        if ($user === null) {
            $this->error("Pengguna \"{$login}\" tidak ditemukan.");

            return self::FAILURE;
        }

        $jumlah = $user->pushTokens()->count();

        if ($jumlah === 0) {
            $this->warn("Pengguna \"{$login}\" belum punya token perangkat terdaftar.");

            return self::FAILURE;
        }

        $sukses = $fcm->kirimKeUser(
            $user->getKey(),
            (string) $this->option('judul'),
            (string) $this->option('pesan'),
            ['jenis' => 'uji'],
        );

        $this->info("Terkirim ke {$sukses} dari {$jumlah} perangkat.");

        return $sukses > 0 ? self::SUCCESS : self::FAILURE;
    }
}
