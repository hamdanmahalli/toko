<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Username selalu disimpan huruf kecil dan hanya berisi a-z, 0-9, titik,
 * garis bawah, dan strip, supaya unik tanpa bergantung pada huruf besar/
 * kecil database (PostgreSQL, MySQL, SQLite).
 */
class Username
{
    public function normalisasi(string $nilai): string
    {
        return mb_strtolower(trim($nilai));
    }

    public function eksis(string $username): bool
    {
        return User::where('username', $this->normalisasi($username))->exists();
    }

    /** Usulan yang pasti belum dipakai, dari nama/email prefiks. */
    public function usulkan(string $dasar): string
    {
        $dasar = Str::slug($dasar, '', 'id');
        $dasar = preg_replace('/[^a-z0-9_.-]/', '', $dasar);
        $dasar = mb_substr($dasar ?: 'pengguna', 0, 40);

        $coba = $dasar;
        $i = 1;
        while ($this->eksis($coba)) {
            $i++;
            $coba = $dasar.$i;
        }

        return $coba;
    }

    public function dariEmail(string $email): string
    {
        return $this->usulkan(explode('@', mb_strtolower(trim($email)), 2)[0] ?? '');
    }
}
