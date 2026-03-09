<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Http\Requests\Account\NotificationRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NotificationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new NotificationRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_notification_frequency(): void
    {
        $subject = new NotificationRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::NOTIFICATION_FREQUENCY, $rules);
        $this->assertContains('required', $rules[Trooper::NOTIFICATION_FREQUENCY]);
    }

    public function test_rules_validates_notification_frequency_is_valid_enum(): void
    {
        $subject = new NotificationRequest;

        $validator = Validator::make(
            [
                Trooper::NOTIFICATION_FREQUENCY => 'invalid-frequency',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::NOTIFICATION_FREQUENCY, $validator->errors()->toArray());
    }

    public function test_rules_accepts_instant_notification_frequency(): void
    {
        $subject = new NotificationRequest;

        $validator = Validator::make(
            [
                Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_daily_notification_frequency(): void
    {
        $subject = new NotificationRequest;

        $validator = Validator::make(
            [
                Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_never_notification_frequency(): void
    {
        $subject = new NotificationRequest;

        $validator = Validator::make(
            [
                Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::NEVER->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validates_organization_should_notify_is_boolean(): void
    {
        $subject = new NotificationRequest;
        $rules = $subject->rules();

        $validator_path = 'organizations.*.' . TrooperAssignment::SHOULD_NOTIFY;
        $this->assertArrayHasKey($validator_path, $rules);
        $this->assertContains('boolean', $rules[$validator_path]);
    }

    public function test_rules_allows_valid_notification_request(): void
    {
        $subject = new NotificationRequest;

        $validator = Validator::make(
            [
                Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT->value,
                'organizations' => [
                    1 => [TrooperAssignment::SHOULD_NOTIFY => true],
                    2 => [TrooperAssignment::SHOULD_NOTIFY => false],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
