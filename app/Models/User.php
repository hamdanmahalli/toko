<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'telepon', 'aktif'])]
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
        ];
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
}
