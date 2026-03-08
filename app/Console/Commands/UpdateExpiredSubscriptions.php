<?php

namespace App\Console\Commands;

use App\Models\Subscriptions;
use Illuminate\Console\Command;

class UpdateExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update active subscriptions to expired if the end date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $affected = Subscriptions::whereIn('status', ['active','cancelled'])
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Successfully expired {$affected} subscriptions.");
    }
}
