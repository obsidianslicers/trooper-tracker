<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Messages\Account\PageData\PushNotificationsPageData;
use App\Messages\Account\Queries\GetTrooperNotifications;
use App\Messages\Events\Queries\GetTroopsToConfirm;
use App\Models\Trooper;
use App\Notifications\Events\EventShiftCompletedNotification;
use Illuminate\Support\Collection;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class PushNotificationsPageDataTest extends TestCase
{
    use FakesMessages;

    public function test_handle_returns_notifications_without_reminders_and_the_troops_to_confirm(): void
    {
        $trooper = Trooper::factory()->make();
        $notifications = new Collection(['notification']);
        $troops = new Collection(['troop']);
        $fake = $this->fakeMessages([
            GetTrooperNotifications::class => $notifications,
            GetTroopsToConfirm::class => $troops,
        ]);

        $subject = new PushNotificationsPageData($trooper);
        $result = $subject->handle();

        $this->assertSame($notifications->all(), $result['notifications']->collection->map->resource->all());
        $this->assertSame($troops->all(), $result['troops_to_confirm']->collection->map->resource->all());
        $fake->assertCalled(
            GetTrooperNotifications::class,
            fn (array $params) => $params['trooper'] === $trooper
                && $params['except_type'] === EventShiftCompletedNotification::class,
        );
        $fake->assertCalled(GetTroopsToConfirm::class, fn (array $params) => $params['trooper'] === $trooper);
    }
}
