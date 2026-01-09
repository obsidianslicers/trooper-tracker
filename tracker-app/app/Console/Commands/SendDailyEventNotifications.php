<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NotificationFrequency;
use App\Mail\Events\DailyEventNotification;
use App\Models\Trooper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
 */
class SendDailyEventNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:send-daily-event-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily event notifications.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $with = [
            'event_notifications' => function ($q)
            {
                $q->whereNull('processed_at');
            }
        ];

        $troopers = Trooper::active()
            ->with($with)
            ->where(Trooper::NOTIFICATION_FREQUENCY, NotificationFrequency::DAILY)
            ->whereHas('event_notifications', function ($q)
            {
                $q->whereNull('processed_at');
            })
            ->get();


        foreach ($troopers as $trooper)
        {
            if ($trooper->emailAppearsValid())
            {
                if ($trooper->notification_frequency === NotificationFrequency::DAILY)
                {
                    Mail::to($trooper->email)->queue(new DailyEventNotification($trooper->event_notifications));

                    foreach ($trooper->event_notifications as $event_notification)
                    {
                        $event_notification->processed_at = now();
                        $event_notification->save();
                    }
                }
            }
        }
    }
}
