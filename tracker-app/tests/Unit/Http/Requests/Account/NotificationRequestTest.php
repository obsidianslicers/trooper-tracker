<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Http\Requests\Account\NotificationRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for NotificationRequest.
 *
 * Verifies:
 * - Authorization always returns true for authenticated users
 * - notification_frequency validation (required, valid enum values)
 * - organizations.*.should_notify validation (optional boolean)
 * - Validation rules are correctly structured
 */
class NotificationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        // Arrange
        $subject = new NotificationRequest();

        // Act
        $result = $subject->authorize();

        // Assert
        $this->assertTrue($result);
    }

    public function test_rules_require_notification_frequency(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        // Act
        $validator = Validator::make([], $rules);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('notification_frequency', $validator->errors()->messages());
    }

    public function test_rules_accept_valid_notification_frequency_values(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        foreach (NotificationFrequency::cases() as $frequency)
        {
            // Act
            $validator = Validator::make([
                'notification_frequency' => $frequency->value,
            ], $rules);

            // Assert
            $this->assertFalse(
                $validator->fails(),
                "Validation should pass for {$frequency->value}"
            );
        }
    }

    public function test_rules_reject_invalid_notification_frequency_values(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        $invalid_values = ['invalid', 'hourly', 'weekly', 123, true, null];

        foreach ($invalid_values as $invalid_value)
        {
            // Act
            $validator = Validator::make([
                'notification_frequency' => $invalid_value,
            ], $rules);

            // Assert
            $this->assertTrue(
                $validator->fails(),
                "Validation should fail for invalid value: " . json_encode($invalid_value)
            );
            $this->assertArrayHasKey('notification_frequency', $validator->errors()->messages());
        }
    }

    public function test_rules_accept_boolean_should_notify_values(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        $valid_data = [
            'notification_frequency' => NotificationFrequency::INSTANT->value,
            'organizations' => [
                1 => ['should_notify' => true],
                2 => ['should_notify' => false],
            ],
        ];

        // Act
        $validator = Validator::make($valid_data, $rules);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_rules_allow_missing_organizations(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        $data = [
            'notification_frequency' => NotificationFrequency::INSTANT->value,
            // organizations not provided
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_rules_allow_empty_organizations_array(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        $data = [
            'notification_frequency' => NotificationFrequency::INSTANT->value,
            'organizations' => [],
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_rules_allow_organizations_without_should_notify(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        $data = [
            'notification_frequency' => NotificationFrequency::INSTANT->value,
            'organizations' => [
                1 => [], // should_notify not provided
            ],
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_rules_handle_multiple_organizations(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        $data = [
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [
                1 => ['should_notify' => true],
                2 => ['should_notify' => false],
                3 => ['should_notify' => true],
                4 => ['should_notify' => false],
            ],
        ];

        // Act
        $validator = Validator::make($data, $rules);

        // Assert
        $this->assertFalse($validator->fails());
    }

    public function test_rules_structure_contains_notification_frequency_rule(): void
    {
        // Arrange
        $subject = new NotificationRequest();

        // Act
        $rules = $subject->rules();

        // Assert
        $this->assertArrayHasKey(Trooper::NOTIFICATION_FREQUENCY, $rules);
        $this->assertIsArray($rules[Trooper::NOTIFICATION_FREQUENCY]);
        $this->assertContains('required', $rules[Trooper::NOTIFICATION_FREQUENCY]);
    }

    public function test_rules_structure_contains_organizations_should_notify_rule(): void
    {
        // Arrange
        $subject = new NotificationRequest();

        // Act
        $rules = $subject->rules();

        // Assert
        $this->assertArrayHasKey('organizations.*.should_notify', $rules);
        $this->assertContains('boolean', $rules['organizations.*.should_notify']);
    }

    public function test_rules_notification_frequency_validates_against_enum(): void
    {
        // Arrange
        $subject = new NotificationRequest();
        $rules = $subject->rules();

        // Assert that the validation rule includes the enum validator
        $notification_rule = $rules[Trooper::NOTIFICATION_FREQUENCY];
        $this->assertIsArray($notification_rule);

        // Find the 'in:' rule
        $in_rule = collect($notification_rule)->first(function ($rule)
        {
            return is_string($rule) && str_starts_with($rule, 'in:');
        });

        $this->assertNotNull($in_rule, 'Should have an "in:" validation rule');
        $this->assertStringContainsString(NotificationFrequency::INSTANT->value, $in_rule);
        $this->assertStringContainsString(NotificationFrequency::DAILY->value, $in_rule);
        $this->assertStringContainsString(NotificationFrequency::NEVER->value, $in_rule);
    }
}
