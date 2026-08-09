<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Http\Requests\Account\UpdatePushNotificationsRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdatePushNotificationsRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new UpdatePushNotificationsRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_push_notifications_enabled(): void
    {
        $subject = new UpdatePushNotificationsRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::PUSH_NOTIFICATIONS_ENABLED, $rules);
        $this->assertContains('required', $rules[Trooper::PUSH_NOTIFICATIONS_ENABLED]);
        $this->assertContains('boolean', $rules[Trooper::PUSH_NOTIFICATIONS_ENABLED]);
    }

    public function test_rules_rejects_non_boolean_value(): void
    {
        $subject = new UpdatePushNotificationsRequest;

        $validator = Validator::make(
            [Trooper::PUSH_NOTIFICATIONS_ENABLED => 'yes'],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            Trooper::PUSH_NOTIFICATIONS_ENABLED,
            $validator->errors()->toArray()
        );
    }

    public function test_rules_accepts_boolean_values(): void
    {
        $subject = new UpdatePushNotificationsRequest;

        foreach ([true, false] as $value)
        {
            $validator = Validator::make(
                [Trooper::PUSH_NOTIFICATIONS_ENABLED => $value],
                $subject->rules()
            );

            $this->assertFalse($validator->fails());
        }
    }
}
