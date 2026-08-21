<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Http\Requests\Account\UpdateNotificationFrequencyRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateNotificationFrequencyRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new UpdateNotificationFrequencyRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_notification_frequency(): void
    {
        $subject = new UpdateNotificationFrequencyRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::NOTIFICATION_FREQUENCY, $rules);
        $this->assertContains('required', $rules[Trooper::NOTIFICATION_FREQUENCY]);
        $this->assertContains('string', $rules[Trooper::NOTIFICATION_FREQUENCY]);
        $this->assertContains('max:16', $rules[Trooper::NOTIFICATION_FREQUENCY]);
    }

    public function test_rules_rejects_invalid_notification_frequency(): void
    {
        $subject = new UpdateNotificationFrequencyRequest;

        $validator = Validator::make(
            [
                Trooper::NOTIFICATION_FREQUENCY => 'invalid-frequency',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            Trooper::NOTIFICATION_FREQUENCY,
            $validator->errors()->toArray()
        );
    }

    public function test_rules_accepts_valid_notification_frequency(): void
    {
        $subject = new UpdateNotificationFrequencyRequest;

        $validator = Validator::make(
            [
                Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
