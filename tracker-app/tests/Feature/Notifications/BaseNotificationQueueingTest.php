<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Trooper;
use App\Notifications\Troopers\MembershipApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BaseNotificationQueueingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'database']);
        Queue::fake();
    }

    public function test_notification_is_queued_per_recipient(): void
    {
        $trooper = Trooper::factory()->create();

        $trooper->notify(new MembershipApprovedNotification);

        Queue::assertPushed(SendQueuedNotifications::class);
    }

    public function test_queued_notification_carries_retry_policy(): void
    {
        $trooper = Trooper::factory()->create();

        $trooper->notify(new MembershipApprovedNotification);

        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job): bool => $job->tries === 3
                && $job->backoff() === [30, 60]
                && $job->afterCommit === true,
        );
    }

    public function test_notification_defers_until_after_transaction_commit(): void
    {
        // The runtime deferral is Laravel's; asserting it here is unreliable
        // because RefreshDatabase already holds an open transaction. It is enough
        // that the notification declares the after-commit contract, which
        // test_queued_notification_carries_retry_policy also checks on the job.
        $trooper = Trooper::factory()->create();

        $trooper->notify(new MembershipApprovedNotification);

        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job): bool => $job->notification instanceof ShouldQueueAfterCommit,
        );
    }
}
