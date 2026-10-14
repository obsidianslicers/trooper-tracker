<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Channels\FcmChannel;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Models\Trooper;
use App\Notifications\Admin\EventRosterActivityNotification;
use App\Notifications\Admin\PermissionRequestReviewedNotification;
use App\Notifications\Admin\PermissionRequestSubmittedNotification;
use App\Notifications\BaseNotification;
use App\Notifications\Events\EventForumPostNotification;
use App\Notifications\Events\EventUpdatedNotification;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

class NotificationPreferenceRoutingTest extends TestCase
{
    public static function opt_out_notifications(): array
    {
        return [
            'event updated' => [
                EventUpdatedNotification::class,
                TrooperNotifications::EVENT_UPDATED,
            ],
            'event forum post' => [
                EventForumPostNotification::class,
                TrooperNotifications::EVENT_FORUM_POST,
            ],
            'event roster activity' => [
                EventRosterActivityNotification::class,
                TrooperNotifications::EVENT_ROSTER_ACTIVITY,
            ],
            'permission request submitted' => [
                PermissionRequestSubmittedNotification::class,
                AdministrativeNotifications::PERMISSION_REQUESTS,
            ],
            'permission request reviewed' => [
                PermissionRequestReviewedNotification::class,
                AdministrativeNotifications::PERMISSION_REQUEST_REVIEWED,
            ],
        ];
    }

    #[DataProvider('opt_out_notifications')]
    public function test_via_drops_channels_the_trooper_disabled(
        string $notification_class,
        AdministrativeNotifications|TrooperNotifications $category
    ): void {
        $trooper = Trooper::factory()->make([
            Trooper::EMAIL => 'trooper@example.com',
            Trooper::PUSH_NOTIFICATIONS_ENABLED => true,
            Trooper::NOTIFICATION_PREFERENCES => [$category->value => ['mail' => false]],
        ]);

        $channels = $this->makeNotification($notification_class)->via($trooper);

        $this->assertContains('database', $channels);
        $this->assertContains(FcmChannel::class, $channels);
        $this->assertNotContains('mail', $channels);
    }

    #[DataProvider('opt_out_notifications')]
    public function test_via_sends_on_every_channel_by_default(
        string $notification_class,
        AdministrativeNotifications|TrooperNotifications $category
    ): void {
        $trooper = Trooper::factory()->make([
            Trooper::EMAIL => 'trooper@example.com',
            Trooper::PUSH_NOTIFICATIONS_ENABLED => true,
            Trooper::NOTIFICATION_PREFERENCES => null,
        ]);

        $channels = $this->makeNotification($notification_class)->via($trooper);

        $this->assertEqualsCanonicalizing(['database', FcmChannel::class, 'mail'], $channels);
    }

    /**
     * via() only reads the category, so skip the constructor and its model dependencies.
     *
     * @param  class-string<BaseNotification>  $notification_class
     */
    private function makeNotification(string $notification_class): BaseNotification
    {
        return (new ReflectionClass($notification_class))->newInstanceWithoutConstructor();
    }
}
