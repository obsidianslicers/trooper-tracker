<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use PHPUnit\Framework\Attributes\DataProvider;
use App\Enums\AdministrativeNotifications;
use App\Enums\NotificationChannels;
use App\Enums\TrooperNotifications;
use App\Http\Requests\Account\UpdateNotificationPreferenceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateNotificationPreferenceRequestTest extends TestCase
{
    use RefreshDatabase;

    public static function notification_enum_cases(): array
    {
        return [
            'administrative notification' => [AdministrativeNotifications::TROOPER_REQUESTS->value],
            'trooper notification' => [TrooperNotifications::EVENT_CREATED->value],
        ];
    }

    public function test_authorize_returns_true(): void
    {
        $subject = new UpdateNotificationPreferenceRequest;

        $this->assertTrue($subject->authorize());
    }

    #[DataProvider('notification_enum_cases')]
    public function test_rules_accepts_values_from_both_notification_enums(string $notification): void
    {
        $subject = new UpdateNotificationPreferenceRequest;

        $validator = Validator::make(
            [
                'notification' => $notification,
                'channel' => NotificationChannels::MAIL->value,
                'enabled' => true,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails(), sprintf('Expected %s to pass validation.', $notification));
    }

    public function test_rules_rejects_unknown_notification_value(): void
    {
        $subject = new UpdateNotificationPreferenceRequest;

        $validator = Validator::make(
            [
                'notification' => 'unknown-notification',
                'channel' => NotificationChannels::MAIL->value,
                'enabled' => true,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notification', $validator->errors()->toArray());
    }
}
