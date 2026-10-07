<?php

namespace App\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

#[
    Fillable([
        'nama', 'kode', 'alamat', 'latitude', 'longitude',
        'radius_meter', 'zona_waktu', 'buka', 'tutup', 'aktif',
        'presensi_user', 'presensi_password',
    ]),
    Hidden(['presensi_user', 'presensi_password']),
]
class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meter' => 'integer',
            'buka' => 'datetime:H:i',
            'tutup' => 'datetime:H:i',
            'aktif' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Atasan (supervisor/pemilik) yang:*:*mengawasi toko ini. */
    public function atasan(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_shop');
    }

    /** Toko yang punya koordinat sehingga geofence bisa dipakai. */
    public function hasGeofence(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function geofenceAktif(): bool
    {
        return $this->aktif && $this->hasGeofence();
    }

    /**
     * True bila perangkat presensi punya login bersama yang lengkap.
     *
     * Sifatnya gagal-tertutup: perangkat tanpa kredensial tidak bisa dibuka
     * sama sekali, bukan dibiarkan terbuka seperti dulu. Alasannya, perangkat
     * ini berdiri di dalam toko tapi halamannya bisa dibuka siapa saja yang
     * tahu URL-nya, jadi "tidak diisi" berarti bisa dibuka siapa saja juga.
     */
    public function presensiSiap(): bool
    {
        return filled($this->presensi_user) && filled($this->presensi_password);
    }

    /**
     * Periksa login perangkat presensi. Password dibandingkan lewat hash,
     * bukan teks biasa.
     *
     * Password tetap diperiksa walau user sudah salah. Kalau pemeriksaan
     * user dihentikan lebih dulu, lama prosesnya jadi berbeda antara "user
     * salah" dan "user benar", dan itu memberi petunjuk ke orang yang menebak.
     */
    public function cocokkanPresensiLogin(?string $user, ?string $password): bool
    {
        if (! $this->presensiSiap()) {
            return false;
        }

        $userBenar = is_string($user)
            && $user !== ''
            && hash_equals((string) $this->presensi_user, $user);

        $passwordBenar = is_string($password)
            && $password !== ''
            && Hash::check($password, (string) $this->presensi_password);

        return $userBenar && $passwordBenar;
    }

    /**
     * Hash password presensi untuk disimpan.
     *
     * Input kosong menghasilkan null, bukan hash dari string kosong: hash
     * kosong akan mengunci perangkat permanen karena password kosong tidak
     * pernah diterima. String yang sudah berawalan `$2y$` dibiarkan apa
     * adanya supaya tidak ter-hash dua kali saat form tidak diisi ulang.
     */
    public static function hashPresensiPassword(?string $password): ?string
    {
        $password = (string) $password;

        if (trim($password) === '') {
            return null;
        }

        return str_starts_with($password, '$2y$') ? $password : Hash::make($password);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
