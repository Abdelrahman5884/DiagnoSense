<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\{CreditAdded, PlanSubscribed, UsageThresholdReached};
use Illuminate\Console\Command;

class TestDoctorNotifications extends Command
{
    protected $signature = 'doctor:test {contact}';
    protected $description = 'Seed notifications to a specific doctor and get their token';

    public function handle()
    {
        $contact = $this->argument('contact');

        $user = User::where('contact', $contact)->with('doctor')->first();

        if (!$user || !$user->doctor) {
            $this->error("Doctor profile not found for this contact!");
            return;
        }

        $this->info("Seeding notifications for Dr. {$user->name}...");
        $user->doctor->notify(new CreditAdded(500, 1000));
        $user->doctor->notify(new PlanSubscribed('Premium'));
        $user->doctor->notify(new UsageThresholdReached(80));

        $user->tokens()->delete();
        $token = $user->createToken('DoctorTestToken')->plainTextToken;

        $this->newLine();
        $this->info("✅ Notifications sent to the Doctor channel.");
        $this->warn("🚀 Access Token:");
        $this->line($token);
        $this->newLine();
    }
}
