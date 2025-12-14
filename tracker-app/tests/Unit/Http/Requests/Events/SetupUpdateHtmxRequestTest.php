<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Requests\Events\SetupUpdateHtmxRequest;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SetupUpdateHtmxRequestTest extends TestCase
{
    use RefreshDatabase;

    private SetupUpdateHtmxRequest $subject;
    private Trooper $user;
    private Organization $organization;
    private Event $event;
    private EventShift $event_shift;
    private EventTrooper $event_trooper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SetupUpdateHtmxRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->withAssignment($this->organization, member: true)
            ->create();
        $this->event = Event::factory()->for($this->organization)->create();
        $this->event_shift = EventShift::factory()->for($this->event)->create();
        $this->event_trooper = EventTrooper::factory()
            ->for($this->event_shift, 'event_shift')
            ->for($this->user, 'trooper')
            ->create();

        EventOrganization::factory()
            ->for($this->event)
            ->for($this->organization)
            ->create(['can_attend' => true]);

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['event_trooper' => $this->event_trooper]);
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

    public function test_authorize_returns_true_for_event_trooper_owner(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_moderator_of_trooper(): void
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

    public function test_authorize_returns_true_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_different_trooper(): void
    {
        // Arrange
        $other_trooper = Trooper::factory()->create();
        $this->subject->setUserResolver(fn() => $other_trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_trooper_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('EventTrooper not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_validation_passes_with_valid_status(): void
    {
        // Arrange
        $good_data = [
            'status' => EventTrooperStatus::GOING->value,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_null_status(): void
    {
        // Arrange
        $good_data = [
            'status' => null,
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
            'status' => 'invalid_status',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_passes_with_valid_costume_from_allowed_organization(): void
    {
        // Arrange
        $costume = OrganizationCostume::factory()->for($this->organization)->create();
        TrooperCostume::factory()
            ->for($this->user, 'trooper')
            ->for($costume, 'organization_costume')
            ->create();

        $good_data = [
            'costume_id' => $costume->id,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_null_costume_id(): void
    {
        // Arrange
        $good_data = [
            'costume_id' => null,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_costume_from_organization_not_allowed_to_attend(): void
    {
        // Arrange
        $other_org = Organization::factory()->create();
        EventOrganization::factory()
            ->for($this->event)
            ->for($other_org)
            ->create(['can_attend' => false]);

        $costume = OrganizationCostume::factory()->for($other_org)->create();
        TrooperCostume::factory()
            ->for($this->user, 'trooper')
            ->for($costume, 'organization_costume')
            ->create();

        $bad_data = [
            'costume_id' => $costume->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('costume_id'));
    }

    public function test_validation_fails_with_costume_not_owned_by_trooper(): void
    {
        // Arrange
        $other_trooper = Trooper::factory()->create();
        $costume = OrganizationCostume::factory()->for($this->organization)->create();
        TrooperCostume::factory()
            ->for($other_trooper, 'trooper')
            ->for($costume, 'organization_costume')
            ->create();

        $bad_data = [
            'costume_id' => $costume->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('costume_id'));
    }

    public function test_validation_fails_with_costume_id_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'status' => str_repeat('a', 17),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }

    public function test_validation_passes_with_status_at_max_length(): void
    {
        // Arrange
        $good_data = [
            'status' => str_repeat('a', 16),
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));
    }
}
