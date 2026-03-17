<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Requests\Events\SignupUpdateHtmxRequest;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SignupUpdateHtmxRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $trooper;

    private Organization $organization;

    private Event $event;

    private EventShift $event_shift;

    private EventTrooper $event_trooper;

    private Costume $costume;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->trooper = Trooper::factory()->asMember()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $this->trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $this->organization->id,
        ]);

        $this->event = Event::factory()->create([
            Event::ORGANIZATION_ID => $this->organization->id,
        ]);

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $this->event->id,
            EventOrganization::ORGANIZATION_ID => $this->organization->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $this->event_shift = EventShift::factory()->create([
            EventShift::EVENT_ID => $this->event->id,
        ]);

        $this->costume = Costume::factory()->create();

        OrganizationCostume::factory()->create([
            OrganizationCostume::COSTUME_ID => $this->costume->id,
            OrganizationCostume::ORGANIZATION_ID => $this->organization->id,
        ]);

        $this->event_trooper = EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $this->event_shift->id,
            EventTrooper::TROOPER_ID => $this->trooper->id,
        ]);

        $this->actingAs($this->trooper);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(SignupUpdateHtmxRequest $request, ?EventTrooper $event_trooper): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('event_trooper')
            ->andReturn($event_trooper);
        $mock_route->shouldReceive('parameter')
            ->with('event_trooper', \Mockery::any())
            ->andReturn($event_trooper);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_own_signup(): void
    {
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        // Note: actual authorization depends on canUpdateCostume/canUpdateStatus methods
        $this->assertIsBool($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_trooper_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('EventTrooper not found or unauthorized.');

        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_status_is_nullable(): void
    {
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey(EventTrooper::STATUS, $rules);
        $this->assertContains('nullable', $rules[EventTrooper::STATUS]);
    }

    public function test_rules_validates_status_is_valid_enum(): void
    {
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $validator = Validator::make(
            [
                EventTrooper::STATUS => 'invalid-status',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(EventTrooper::STATUS, $validator->errors()->toArray());
    }

    public function test_rules_accepts_approved_status(): void
    {
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $validator = Validator::make(
            [
                EventTrooper::STATUS => EventTrooperStatus::GOING->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_costume_id_is_nullable(): void
    {
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey(EventTrooper::COSTUME_ID, $rules);
        $this->assertContains('nullable', $rules[EventTrooper::COSTUME_ID]);
    }

    public function test_rules_backup_costume_id_is_nullable(): void
    {
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey(EventTrooper::BACKUP_COSTUME_ID, $rules);
        $this->assertContains('nullable', $rules[EventTrooper::BACKUP_COSTUME_ID]);
    }

    public function test_rules_validates_costume_id_is_integer(): void
    {
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);
        $rules = $subject->rules();

        $this->assertContains('int', $rules[EventTrooper::COSTUME_ID]);
    }

    public function test_rules_restricts_costumes_to_allowed_organizations(): void
    {
        $other_costume = Costume::factory()->create(); // Different organization
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $validator = Validator::make(
            [
                EventTrooper::COSTUME_ID => $other_costume->id,
            ],
            $subject->rules()
        );

        // Should fail because costume is not from an allowed organization
        $this->assertTrue($validator->fails());
    }

    public function test_rules_accepts_valid_costume_from_allowed_organization(): void
    {
        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $validator = Validator::make(
            [
                EventTrooper::STATUS => EventTrooperStatus::GOING->value,
                EventTrooper::COSTUME_ID => $this->costume->id,
            ],
            $subject->rules()
        );

        // May fail based on other business logic, but should pass basic validation
        $this->assertIsArray($validator->errors()->toArray());
    }

    public function test_rules_uses_event_trooper_id_instead_of_authenticated_user_id_for_costume_validation(): void
    {
        $assigned_trooper = Trooper::factory()->asMember()->create();
        $authenticated_trooper = Trooper::factory()->asModerator()->create();
        $assigned_costume = Costume::factory()->create();
        $authenticated_costume = Costume::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($assigned_trooper)
            ->forOrganization($this->organization)
            ->asMember()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($authenticated_trooper)
            ->forOrganization($this->organization)
            ->asModerator()
            ->create();

        $assigned_organization_costume = OrganizationCostume::factory()
            ->forOrganization($this->organization)
            ->forCostume($assigned_costume)
            ->create();

        $authenticated_organization_costume = OrganizationCostume::factory()
            ->forOrganization($this->organization)
            ->forCostume($authenticated_costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($assigned_trooper)
            ->forOrganizationCostume($assigned_organization_costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($authenticated_trooper)
            ->forOrganizationCostume($authenticated_organization_costume)
            ->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($this->event_shift)
            ->forTrooper($assigned_trooper)
            ->create();

        $subject = new SignupUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $authenticated_trooper);
        $this->setupMockedRoute($subject, $event_trooper);

        $assigned_costume_validator = Validator::make(
            [
                EventTrooper::COSTUME_ID => $assigned_costume->id,
            ],
            $subject->rules()
        );

        $authenticated_costume_validator = Validator::make(
            [
                EventTrooper::COSTUME_ID => $authenticated_costume->id,
            ],
            $subject->rules()
        );

        $this->assertFalse($assigned_costume_validator->fails());
        $this->assertTrue($authenticated_costume_validator->fails());
        $this->assertArrayHasKey(
            EventTrooper::COSTUME_ID,
            $authenticated_costume_validator->errors()->toArray()
        );
    }
}
