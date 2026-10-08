<?php

namespace App\Models;

use App\Enums\StatusPerangkat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $fillable = ['user_id', 'device_token', 'label', 'status', 'last_seen_at'];

    protected $casts = [
        'status' => StatusPerangkat::class,
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
