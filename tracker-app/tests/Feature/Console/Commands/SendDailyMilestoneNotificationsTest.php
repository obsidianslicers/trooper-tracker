<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Jobs\SendTrooperMilestoneNotificationsJob;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SendDailyMilestoneNotificationsTest extends TestCase
{
    public function test_command_dispatches_daily_roundup_job(): void
    {
        Bus::fake();

        $this->artisan('tracker:send-daily-milestone-notifications')->assertSuccessful();

        Bus::assertDispatchedTimes(SendTrooperMilestoneNotificationsJob::class, 1);
    }
}
