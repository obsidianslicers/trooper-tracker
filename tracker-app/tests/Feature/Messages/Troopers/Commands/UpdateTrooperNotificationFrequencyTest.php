<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands;

use App\Enums\NotificationFrequency;
use App\Messages\Troopers\Commands\UpdateTrooperNotificationFrequency;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTrooperNotificationFrequencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_updates_trooper_notification_frequency(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withNotificationFrequency(NotificationFrequency::INSTANT)
            ->create();

        $subject = new UpdateTrooperNotificationFrequency(
            trooper: $trooper,
            notification_frequency: NotificationFrequency::DAILY,
        );

        $subject->handle();

        $trooper->refresh();

        $this->assertSame(NotificationFrequency::DAILY, $trooper->{Trooper::NOTIFICATION_FREQUENCY});
        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->{Trooper::ID},
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY->value,
        ]);
    }
}
