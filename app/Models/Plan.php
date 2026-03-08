<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stripe\Subscription;

class Plan extends Model
{
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
