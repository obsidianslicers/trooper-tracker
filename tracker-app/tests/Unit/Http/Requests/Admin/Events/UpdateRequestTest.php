<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Events;

use App\Http\Requests\Admin\Events\UpdateRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private UpdateRequest $subject;
    private Trooper $user;
    private Organization $organization;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->asModerator()
            ->withAssignment($this->organization, moderator: true)
            ->create();
        $this->event = Event::factory()->for($this->organization)->create();

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['event' => $this->event]);
        });
    }

    private function getMockRoute(array $parameters = []): object
    {
        return new class ($parameters)
        {
            public function __construct(private array $parameters)
            {
            }

            public function parameter(string $key, $default = null)
            {
                return $this->parameters[$key] ?? $default;
            }
        };
    }

    public function test_authorize_returns_true_for_moderator_of_organization(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->subject->setUserResolver(fn() => $trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_moderator_of_different_organization(): void
    {
        // Arrange
        $different_org = Organization::factory()->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($different_org, moderator: true)
            ->create();
        $this->subject->setUserResolver(fn() => $moderator);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Event not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_validation_passes_with_valid_data(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test Event Name',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'secure_staging_area' => true,
            'allow_blasters' => true,
            'allow_props' => true,
            'parking_available' => true,
            'accessible' => true,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_name(): void
    {
        // Arrange
        $bad_data = [
            'name' => '',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'name' => str_repeat('a', 129),
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_passes_with_name_at_max_length(): void
    {
        // Arrange
        $good_data = [
            'name' => str_repeat('a', 128),
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_status(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_fails_with_invalid_status(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'invalid_status',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_fails_with_missing_event_start(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_end' => '2025-12-25 14:00:00',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('event_start'));
    }

    public function test_validation_fails_with_missing_event_end(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('event_end'));
    }

    public function test_validation_fails_with_event_end_before_event_start(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 14:00:00',
            'event_end' => '2025-12-25 10:00:00',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('event_end'));
    }

    public function test_validation_passes_with_optional_contact_information(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'contact_name' => 'John Doe',
            'contact_phone' => '555-1234',
            'contact_email' => 'john@example.com',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_contact_email(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'contact_email' => 'not-an-email',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('contact_email'));
    }

    public function test_validation_passes_with_optional_venue_information(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'venue' => 'Convention Center',
            'venue_address' => '123 Main St',
            'venue_city' => 'Orlando',
            'venue_state' => 'FL',
            'venue_zip' => '32801',
            'venue_country' => 'USA',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_troopers_allowed_in_range(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'troopers_allowed' => 50,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_troopers_allowed_below_minimum(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'troopers_allowed' => 0,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('troopers_allowed'));
    }

    public function test_validation_fails_with_troopers_allowed_above_maximum(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'troopers_allowed' => 100000,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('troopers_allowed'));
    }

    public function test_validation_passes_with_latitude_and_longitude(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'latitude' => 28.5383,
            'longitude' => -81.3792,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_latitude_out_of_range(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'latitude' => 91,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('latitude'));
    }

    public function test_validation_fails_with_longitude_out_of_range(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'longitude' => 181,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('longitude'));
    }

    public function test_validation_passes_with_organization_can_attend_as_boolean(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'organizations' => [
                ['can_attend' => true],
                ['can_attend' => false],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_prepare_for_validation_sets_default_can_attend_to_false(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'organizations' => [
                1 => [/* can_attend not set */],
            ],
        ];

        // Act
        $this->subject->merge($data);
        $this->invokeMethod($this->subject, 'prepareForValidation');

        // Assert
        $this->assertFalse($this->subject->input('organizations.1.can_attend'));
    }

    public function test_prepare_for_validation_coerces_can_attend_to_boolean(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Event',
            'status' => 'open',
            'event_start' => '2025-12-25 10:00:00',
            'event_end' => '2025-12-25 14:00:00',
            'organizations' => [
                1 => ['can_attend' => 'on'],
                2 => ['can_attend' => '1'],
                3 => ['can_attend' => 'true'],
            ],
        ];

        // Act
        $this->subject->merge($data);
        $this->invokeMethod($this->subject, 'prepareForValidation');

        // Assert
        $this->assertTrue($this->subject->input('organizations.1.can_attend'));
        $this->assertTrue($this->subject->input('organizations.2.can_attend'));
        $this->assertTrue($this->subject->input('organizations.3.can_attend'));
    }

    public function test_authorize_returns_true_for_moderator_of_parent_organization(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $unit_event = Event::factory()->for($unit)->create();

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($region, moderator: true)
            ->create();

        $this->subject->setUserResolver(fn() => $moderator);
        $this->subject->setRouteResolver(function () use ($unit_event)
        {
            return $this->getMockRoute(['event' => $unit_event]);
        });

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }
}
