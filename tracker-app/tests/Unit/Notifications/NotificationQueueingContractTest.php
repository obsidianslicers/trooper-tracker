<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Notifications\Admin\EventRosterActivityNotification;
use App\Notifications\BaseNotification;
use App\Notifications\Events\EventForumPostNotification;
use App\Notifications\Troopers\MembershipApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class NotificationQueueingContractTest extends TestCase
{
    public function test_base_notification_queues_after_commit(): void
    {
        $subject = new ReflectionClass(BaseNotification::class);

        $this->assertTrue($subject->implementsInterface(ShouldQueue::class));
        $this->assertTrue($subject->implementsInterface(ShouldQueueAfterCommit::class));
    }

    /**
     * @return list<array{class-string}>
     */
    public static function notificationProvider(): array
    {
        return [
            [MembershipApprovedNotification::class],
            [EventRosterActivityNotification::class],
            [EventForumPostNotification::class],
        ];
    }

    /**
     * @dataProvider notificationProvider
     *
     * @param  class-string  $notification
     */
    public function test_notification_is_queued(string $notification): void
    {
        $this->assertInstanceOf(ShouldQueue::class, (new ReflectionClass($notification))->newInstanceWithoutConstructor());
    }
}
