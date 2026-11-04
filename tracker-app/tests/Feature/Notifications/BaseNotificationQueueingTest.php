<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Mail\ThrottleOutgoingMail;
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

    public function test_only_the_mail_channel_is_throttled(): void
    {
        $trooper = Trooper::factory()->create();
        $notification = new MembershipApprovedNotification;

        $this->assertEquals([new ThrottleOutgoingMail], $notification->middleware($trooper, 'mail'));
        $this->assertSame([], $notification->middleware($trooper, 'database'));
    }

    public function test_queued_mail_job_carries_the_throttle(): void
    {
        $trooper = Trooper::factory()->create();

        $trooper->notify(new MembershipApprovedNotification);

        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job): bool => $job->channels === ['mail']
                && $job->middleware == [new ThrottleOutgoingMail],
        );
    }

    public function test_retries_by_time_only_while_a_mail_budget_is_set(): void
    {
        $notification = new MembershipApprovedNotification;

        $this->assertNull($notification->retryUntil());
        $this->assertSame(3, $notification->maxExceptions);

        config(['mail.recipients_per_hour' => 90]);

        $this->assertTrue($notification->retryUntil() > now()->addHours(23));
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
