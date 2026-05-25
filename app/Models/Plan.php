<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stripe\Subscription;

class Plan extends Model
{
    use HasFactory;

    const PAY_PER_USE_PRICE = 20.00;

    protected $fillable = [
        'name',
        'price',
        'summaries_limit',
        'duration_days',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
