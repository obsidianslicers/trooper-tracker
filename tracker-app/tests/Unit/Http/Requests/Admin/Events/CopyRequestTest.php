<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Events;

use App\Http\Requests\Admin\Events\CopyRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for CopyRequest.
 *
 * Verifies:
 * - Authorization logic (user must have update permission on the event)
 * - Validation rules for event name and start date
 * - Proper error handling when event is not found
 */
class CopyRequestTest extends TestCase
{
    use RefreshDatabase;

    private CopyRequest $subject;
    private Trooper $user;
    private Organization $organization;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CopyRequest();
        $this->organization = Organization::factory()->create();
        $this->event = Event::factory()->withOrganization($this->organization)->create();
        $this->user = Trooper::factory()->asAdministrator()->create();

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['event' => $this->event]);
        });
    }

    /**
     * Creates a mock route object with parameters
     */
    private function getMockRoute(array $parameters = []): object
    {
        return new class ($parameters)
        {
            public function __construct(private array $parameters)
            {
            }

            public function parameter(string $name)
            {
                return $this->parameters[$name] ?? null;
            }
        };
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_moderator_of_organization(): void
    {
        // Arrange
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($this->organization, moderator: true)
            ->create();
        $this->subject->setUserResolver(fn() => $moderator);

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_unauthorized_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $this->subject->setUserResolver(fn() => $trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_not_found(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->subject->authorize();
    }

    public function test_validation_passes_with_valid_data(): void
    {
        // Arrange
        $data = [
            Event::NAME => 'COPY OF Original Event',
            Event::EVENT_START => now()->addDays(3)->toDateTimeString(),
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_name(): void
    {
        // Arrange
        $data = [
            Event::NAME => '',
            Event::EVENT_START => now()->addDays(3)->toDateTimeString(),
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::NAME));
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        // Arrange
        $data = [
            Event::NAME => str_repeat('a', 129),
            Event::EVENT_START => now()->addDays(3)->toDateTimeString(),
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::NAME));
    }

    public function test_validation_passes_with_name_at_max_length(): void
    {
        // Arrange
        $data = [
            Event::NAME => str_repeat('a', 128),
            Event::EVENT_START => now()->addDays(3)->toDateTimeString(),
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_event_start(): void
    {
        // Arrange
        $data = [
            Event::NAME => 'COPY OF Original Event',
            Event::EVENT_START => '',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::EVENT_START));
    }

    public function test_validation_fails_with_invalid_date_format(): void
    {
        // Arrange
        $data = [
            Event::NAME => 'COPY OF Original Event',
            Event::EVENT_START => 'not-a-date',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::EVENT_START));
    }

    public function test_validation_fails_with_start_date_in_past(): void
    {
        // Arrange
        $data = [
            Event::NAME => 'COPY OF Original Event',
            Event::EVENT_START => now()->subDays(1)->toDateTimeString(),
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Event::EVENT_START));
    }

    public function test_validation_passes_with_start_date_in_future(): void
    {
        // Arrange
        $data = [
            Event::NAME => 'COPY OF Original Event',
            Event::EVENT_START => now()->addDay()->toDateTimeString(),
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }
}
