<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'username', 'password', 'telepon', 'aktif', 'bebas_perangkat'])]
#[Hidden(['password', 'remember_token', 'active_session_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** Akun baru aktif secara default, sama seperti default kolom di migrasi. */
    protected $attributes = [
        'aktif' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
            'bebas_perangkat' => 'boolean',
        ];
    }

    /** Perangkat tempat akun ini diizinkan login (diatur lewat halaman Pengguna). */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /** Token FCM perangkat Android (APK) milik akun ini. */
    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class);
    }

    /**
     * Peran yang tidak terikat ke perangkat tertentu: pemilik dan supervisor
     * adalah manusia yang sudah dipercaya, dan `bebas_perangkat` memberi
     * pengecualian per akun (mis. HP diperbaiki sementara).
     */
    public function perangkatBebas(): bool
    {
        return $this->bebas_perangkat
            || $this->hasAnyRole((array) config('absensi.perangkat_bebas_roles', []));
    }

    /** Karyawan dengan penjagaan perangkat aktif harus login dari perangkat disetujui. */
    public function wajibPerangkat(): bool
    {
        return $this->hasRole('karyawan') && ! $this->perangkatBebas();
    }

    /** Akun login yang tertaut ke data karyawan (null untuk Pemilik/Supervisor). */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Toko yang boleh diawasi akun ini.
     *
     * Kosong berarti akun ini tidak punya akses ke toko mana pun sampai ada
     * penugasan; hanya peran global yang tidak dibatasi ini.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'user_shop');
    }

    /**
     * Tambahkan satu toko ke daftar "toko yang diawasi" tanpa menghapus
     * penugasan lain. Dipakai supaya akun yang tertaut data karyawan otomatis
     * mewarisi toko karyawannya, ikut saat karyawan pindah toko.
     */
    public function sertakanToko(?int $shopId): void
    {
        if ($shopId === null) {
            return;
        }

        $this->shops()->syncWithoutDetaching([$shopId]);
    }
}
