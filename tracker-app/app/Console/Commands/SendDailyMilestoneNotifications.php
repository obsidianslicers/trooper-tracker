<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendTrooperMilestoneNotificationsJob;
use Illuminate\Console\Command;

class SendDailyMilestoneNotifications extends Command
{
    protected $signature = 'tracker:send-daily-milestone-notifications';

    protected $description = 'Send the daily trooper milestone notification roundup.';

    public function handle(): void
    {
        dispatch(new SendTrooperMilestoneNotificationsJob);
    }
}
