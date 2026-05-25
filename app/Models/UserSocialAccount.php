<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocialAccount extends Model
{
    protected $fillable = ['user_id', 'provider', 'provider_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(related: User::class);
    }
}
