<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Events;

use App\Enums\EventStatus;
use App\Http\Requests\Admin\Events\CreateRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for CreateRequest.
 *
 * Verifies:
 * - Authorization logic (administrators and moderators can create, regular troopers cannot)
 * - Validation rules for all event fields
 * - Organization_id must belong to an organization the user moderates
 * - Common validation rules are applied
 */
class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private CreateRequest $subject;
    private Trooper $user;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CreateRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->asModerator()
            ->withAssignment($this->organization, moderator: true)
            ->create();

        $this->subject->setUserResolver(fn() => $this->user);
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $this->subject->setUserResolver(fn() => $trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_validation_passes_with_valid_minimal_data(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_when_organization_id_is_missing(): void
    {
        // Arrange
        $data = [
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::ORGANIZATION_ID));
    }

    public function test_validation_fails_when_organization_id_does_not_exist(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => 999999,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::ORGANIZATION_ID));
    }

    public function test_validation_fails_when_organization_is_not_moderated_by_user(): void
    {
        // Arrange
        $other_organization = Organization::factory()->create();
        $data = [
            Event::ORGANIZATION_ID => $other_organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::ORGANIZATION_ID));
    }

    public function test_validation_passes_when_organization_is_moderated_by_user(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_when_name_is_missing(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::NAME));
    }

    public function test_validation_fails_when_status_is_invalid(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => 'invalid_status',
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::STATUS));
    }

    public function test_validation_fails_when_event_start_is_missing(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_END => '2026-06-01 16:00:00',
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::EVENT_START));
    }

    public function test_validation_fails_when_event_end_is_before_event_start(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 16:00:00',
            Event::EVENT_END => '2026-06-01 10:00:00',
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::EVENT_END));
    }

    public function test_validation_passes_with_optional_venue_information(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::VENUE => 'Convention Center',
            Event::VENUE_ADDRESS => '123 Main St',
            Event::VENUE_CITY => 'Orlando',
            Event::VENUE_STATE => 'FL',
            Event::VENUE_ZIP => '32801',
            Event::VENUE_COUNTRY => 'USA',
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_optional_contact_information(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::CONTACT_NAME => 'John Doe',
            Event::CONTACT_PHONE => '555-1234',
            Event::CONTACT_EMAIL => 'john@example.com',
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_contact_email(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::CONTACT_EMAIL => 'not-an-email',
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::CONTACT_EMAIL));
    }

    public function test_validation_passes_with_capacity_limits(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::TROOPERS_ALLOWED => 20,
            Event::HANDLERS_ALLOWED => 5,
            Event::FRIENDS_ALLOWED => 10,
            Event::TENTATIVE_SIGNUPS_ALLOWED => true,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_negative_troopers_allowed(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::TROOPERS_ALLOWED => -1,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::TROOPERS_ALLOWED));
    }

    public function test_validation_passes_with_shifts_allowed_in_range(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::SHIFTS_ALLOWED => 3,
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_shifts_allowed_below_minimum(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::SHIFTS_ALLOWED => 0,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::SHIFTS_ALLOWED));
    }

    public function test_validation_fails_with_shifts_allowed_above_maximum(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::SHIFTS_ALLOWED => 100000,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::SHIFTS_ALLOWED));
    }

    public function test_validation_passes_with_null_shifts_allowed(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::SHIFTS_ALLOWED => null,
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_geographic_coordinates(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::LATITUDE => 28.5383,
            Event::LONGITUDE => -81.3792,
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_latitude(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::LATITUDE => 95.0, // Invalid: > 90
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::LATITUDE));
    }

    public function test_validation_passes_with_boolean_flags(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::TENTATIVE_SIGNUPS_ALLOWED => true,
            Event::SECURE_STAGING_AREA => true,
            Event::ALLOW_BLASTERS => true,
            Event::ALLOW_PROPS => true,
            Event::PARKING_AVAILABLE => true,
            Event::ACCESSIBLE => true,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_rules_includes_organization_id_validation(): void
    {
        // Act
        $rules = $this->subject->rules();

        // Assert
        $this->assertArrayHasKey(Event::ORGANIZATION_ID, $rules);
        $this->assertIsArray($rules[Event::ORGANIZATION_ID]);
        $this->assertContains('required', $rules[Event::ORGANIZATION_ID]);
    }

    public function test_rules_includes_common_rules_from_trait(): void
    {
        // Act
        $rules = $this->subject->rules();

        // Assert
        $this->assertArrayHasKey(Event::NAME, $rules);
        $this->assertArrayHasKey(Event::STATUS, $rules);
        $this->assertArrayHasKey(Event::EVENT_START, $rules);
        $this->assertArrayHasKey(Event::EVENT_END, $rules);
    }

    public function test_administrator_can_create_event_for_any_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        $any_organization = Organization::factory()->create();
        $data = [
            Event::ORGANIZATION_ID => $any_organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_valid_charity_information(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Charity Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::CHARITY_NAME => 'Make-A-Wish Foundation',
            Event::CHARITY_HOURS => 150,
            Event::CHARITY_DIRECT_FUNDS => 5000,
            Event::CHARITY_INDIRECT_FUNDS => 2500,
            Event::CHARITY_NOTES => 'Funds raised through silent auction',
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_negative_charity_hours(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::CHARITY_HOURS => -5,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::CHARITY_HOURS));
    }

    public function test_validation_fails_with_negative_charity_direct_funds(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::CHARITY_DIRECT_FUNDS => -1000,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::CHARITY_DIRECT_FUNDS));
    }

    public function test_validation_fails_with_negative_charity_indirect_funds(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::CHARITY_INDIRECT_FUNDS => -500,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::CHARITY_INDIRECT_FUNDS));
    }

    public function test_validation_passes_with_zero_charity_funds(): void
    {
        // Arrange
        $data = [
            Event::ORGANIZATION_ID => $this->organization->id,
            Event::NAME => 'Test Event',
            Event::STATUS => EventStatus::OPEN->value,
            Event::EVENT_START => '2026-06-01 10:00:00',
            Event::EVENT_END => '2026-06-01 16:00:00',
            Event::CHARITY_DIRECT_FUNDS => 0,
            Event::CHARITY_INDIRECT_FUNDS => 0,
            Event::TENTATIVE_SIGNUPS_ALLOWED => false,
            Event::SECURE_STAGING_AREA => false,
            Event::ALLOW_BLASTERS => false,
            Event::ALLOW_PROPS => false,
            Event::PARKING_AVAILABLE => false,
            Event::ACCESSIBLE => false,
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }
}
