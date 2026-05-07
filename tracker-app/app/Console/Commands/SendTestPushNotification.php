<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trooper;
use App\Notifications\Tests\TestPushNotification;
use Illuminate\Console\Command;

class SendTestPushNotification extends Command
{
    protected $signature = 'tracker:send-test-push {trooper_id} {--url= : Path to open in the app (e.g. /events/details/42)}';

    protected $description = 'Send a random test push notification to a trooper.';

    private const NOTIFICATIONS = [
        ['title' => 'New Event: Celebration VIII', 'body' => 'Orlando Convention Center · May 22–25'],
        ['title' => 'New Event: Tampa Bay Comic Con', 'body' => 'Tampa Convention Center · July 4–6'],
        ['title' => 'New Event: MegaCon Orlando', 'body' => 'Orange County Convention Center'],
        ['title' => 'Event Cancelled: Star Wars Night', 'body' => 'This event has been cancelled.'],
        ['title' => 'Event Cancelled: Kids Cancer Walk', 'body' => 'This event has been cancelled.'],
        ['title' => 'Reminder: Suit up for Galaxy\'s Edge', 'body' => 'Event starts tomorrow at 10:00 AM'],
    ];

    public function handle(): int
    {
        $trooper = Trooper::find($this->argument('trooper_id'));

        if (!$trooper)
        {
            $this->error("No trooper found with ID {$this->argument('trooper_id')}.");

            return self::FAILURE;
        }

        $notification = self::NOTIFICATIONS[array_rand(self::NOTIFICATIONS)];
        $url = $this->option('url') ?? '/events';

        $trooper->notify(new TestPushNotification($notification['title'], $notification['body'], $url));

        $this->info("Sent to {$trooper->display_name}: {$notification['title']} → {$url}");

        return self::SUCCESS;
    }
}
