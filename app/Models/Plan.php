<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
