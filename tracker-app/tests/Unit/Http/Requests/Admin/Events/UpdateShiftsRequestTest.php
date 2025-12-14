<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Events;

use App\Http\Requests\Admin\Events\UpdateShiftsRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateShiftsRequestTest extends TestCase
{
    use RefreshDatabase;

    private UpdateShiftsRequest $subject;
    private Trooper $user;
    private Organization $organization;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateShiftsRequest();
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

    public function test_validation_passes_with_valid_shift_data(): void
    {
        // Arrange
        $good_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                    'status' => 'open',
                ],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_multiple_shifts(): void
    {
        // Arrange
        $good_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                    'status' => 'open',
                ],
                [
                    'date' => '2025-12-26',
                    'starts_at' => '09:00',
                    'ends_at' => '13:00',
                    'status' => 'draft',
                ],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_date(): void
    {
        // Arrange
        $bad_data = [
            'shifts' => [
                [
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('shifts.0.date'));
    }

    public function test_validation_fails_with_invalid_date_format(): void
    {
        // Arrange
        $bad_data = [
            'shifts' => [
                [
                    'date' => 'invalid-date',
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('shifts.0.date'));
    }

    public function test_validation_fails_with_missing_starts_at(): void
    {
        // Arrange
        $bad_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'ends_at' => '14:00',
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('shifts.0.starts_at'));
    }

    public function test_validation_fails_with_invalid_starts_at_format(): void
    {
        // Arrange
        $bad_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'starts_at' => '10:00:00',
                    'ends_at' => '14:00',
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('shifts.0.starts_at'));
    }

    public function test_validation_fails_with_missing_ends_at(): void
    {
        // Arrange
        $bad_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'starts_at' => '10:00',
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('shifts.0.ends_at'));
    }

    public function test_validation_fails_with_invalid_ends_at_format(): void
    {
        // Arrange
        $bad_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'starts_at' => '10:00',
                    'ends_at' => '14:00:00',
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('shifts.0.ends_at'));
    }

    public function test_validation_passes_with_null_status(): void
    {
        // Arrange
        $good_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                    'status' => null,
                ],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_valid_status(): void
    {
        // Arrange
        $good_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                    'status' => 'open',
                ],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_status(): void
    {
        // Arrange
        $bad_data = [
            'shifts' => [
                [
                    'date' => '2025-12-25',
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                    'status' => 'invalid_status',
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('shifts.0.status'));
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
