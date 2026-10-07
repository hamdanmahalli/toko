<?php

namespace App\Support;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aturan cakupan toko.
 *
 * - Peran global (lihat `config/absensi.php`) melihat semua toko.
 * - Semua akun lain hanya melihat toko yang ditugaskan lewat `user_shop`.
 * - Akun tanpa penugasan apa pun melihat nol toko, bukan semua toko.
 * - Karyawan otomatis tersempit ke toko dari data karyawannya sendiri.
 */
class CakupanToko
{
    /** @return Collection<int, int> ID toko yang boleh diakses. Kosong = tidak ada toko sama sekali. */
    public function idToko(User $user): Collection
    {
        return $user->shops()->pluck('shops.id');
    }

    public function semuaToko(User $user): bool
    {
        return $user->hasRole($this->peranGlobal());
    }

    /** @return array<int, string> */
    private function peranGlobal(): array
    {
        return (array) config('absensi.peran_toko_global', ['pemilik']);
    }

    /** Batasi query apa pun yang punya kolom `shop_id`. */
    public function batasi(Builder $query, User $user, string $kolom = 'shop_id'): Builder
    {
        if ($this->semuaToko($user)) {
            return $query;
        }

        return $query->whereIn($kolom, $this->idToko($user));
    }

    /** True bila pengguna boleh menyentuh toko tertentu. */
    public function boleh(User $user, Shop|int $toko): bool
    {
        if ($this->semuaToko($user)) {
            return true;
        }

        $id = $toko instanceof Shop ? $toko->id : $toko;

        return $this->idToko($user)->contains($id);
    }

    /**
     * True bila akun ini belum ditugaskan ke toko apa pun, jadi tampilannya
     * perlu peringatan supaya admin tahu kenapa semuanya kosong.
     */
    public function tanpaPenugasan(User $user): bool
    {
        return ! $this->semuaToko($user) && $user->shops()->doesntExist();
    }
}
