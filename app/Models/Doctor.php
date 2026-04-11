<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Doctor extends Model
{
    use LogsActivity , Notifiable;

    protected $fillable = [
        'user_id',
        'billing_mode',
        'specialization',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'doctor_patient', 'doctor_id', 'patient_id');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function medications()
    {
        return $this->hasMany(Medication::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscriptions::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscriptions::class)
            ->whereIn('status', ['active', 'cancelled'])
            ->where('expires_at', '>', now())
            ->whereHas('plan', function ($query) {
                $query->whereColumn('subscriptions.used_summaries', '<', 'plans.summaries_limit');
            })
            ->latest();
    }

    public function latestSubscription()
    {
        return $this->hasOne(Subscriptions::class)->latestOfMany();
    }

    public function hasFeature(string $featureName): bool
    {
        if ($this->billing_mode === 'pay_per_use') {
            return true;
        }
        $sub = $this->activeSubscription;
        if (! $sub) {
            return false;
        }
        $features = is_string($sub->plan->features) ? json_decode($sub->plan->features, true) : $sub->plan->features;

        return in_array($featureName, $features ?? []);
    }
}
