<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use PHPUnit\Framework\Attributes\DataProvider;
use App\Enums\AdministrativeNotifications;
use App\Enums\NotificationChannels;
use App\Enums\TrooperNotifications;
use App\Messages\Troopers\Commands\UpdateNotificationPreference;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateNotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public static function notification_preference_cases(): array
    {
        return [
            'administrative notification with null preferences' => [
                AdministrativeNotifications::TROOPER_REQUESTS,
                null,
                [
                    AdministrativeNotifications::TROOPER_REQUESTS->value => [
                        NotificationChannels::MAIL->value => true,
                    ],
                ],
            ],
            'trooper notification with empty preferences' => [
                TrooperNotifications::EVENT_CREATED,
                [],
                [
                    TrooperNotifications::EVENT_CREATED->value => [
                        NotificationChannels::MAIL->value => true,
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('notification_preference_cases')]
    public function test_handle_updates_notification_preferences_for_both_enums_and_initial_states(
        AdministrativeNotifications|TrooperNotifications $notification,
        ?array $initial_preferences,
        array $expected_preferences,
    ): void {
        $trooper = Trooper::factory()
            ->asActive()
            ->create([
                Trooper::NOTIFICATION_PREFERENCES => $initial_preferences,
            ]);

        $subject = new UpdateNotificationPreference(
            trooper: $trooper,
            notification: $notification,
            channel: NotificationChannels::MAIL,
            enabled: true,
        );

        $subject->handle();

        $trooper->refresh();

        $this->assertSame($expected_preferences, $trooper->{Trooper::NOTIFICATION_PREFERENCES});
        $this->assertDatabaseHas('tt_troopers', [
            Trooper::ID => $trooper->{Trooper::ID},
        ]);
    }
}
