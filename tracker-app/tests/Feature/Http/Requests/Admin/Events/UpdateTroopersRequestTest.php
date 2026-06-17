<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Models\Costume;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTroopersRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Organization $organization;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->moderator = Trooper::factory()->asModerator()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $this->moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $this->organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->event = Event::factory()->create([
            Event::ORGANIZATION_ID => $this->organization->id,
        ]);

        $this->actingAs($this->moderator);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(UpdateTroopersRequest $request, ?Event $event): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('event')
            ->andReturn($event);
        $mock_route->shouldReceive('parameter')
            ->with('event', \Mockery::any())
            ->andReturn($event);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $subject = new UpdateTroopersRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Event not found or unauthorized.');

        $subject = new UpdateTroopersRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_validates_trooper_status_is_nullable(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertArrayHasKey('troopers.*.status', $rules);
        $this->assertContains('nullable', $rules['troopers.*.status']);
    }

    public function test_rules_validates_status_is_valid_enum(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'troopers' => [
                    ['status' => 'invalid-status'],
                ],
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_rules_accepts_approved_status(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'troopers' => [
                    ['status' => EventTrooperStatus::GOING->value],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_pending_status(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'troopers' => [
                    ['status' => EventTrooperStatus::PENDING->value],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_declined_status(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'troopers' => [
                    ['status' => EventTrooperStatus::CANCELLED->value],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_null_status(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'troopers' => [
                    ['status' => null],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_multiple_troopers(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'troopers' => [
                    ['status' => EventTrooperStatus::GOING->value],
                    ['status' => EventTrooperStatus::PENDING->value],
                    ['status' => EventTrooperStatus::CANCELLED->value],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_existing_nullable_costume_id(): void
    {
        $costume = Costume::factory()->create();
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'troopers' => [
                    ['costume_id' => $costume->id],
                    ['costume_id' => null],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_rejects_missing_costume_id(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'troopers' => [
                    ['costume_id' => 999999],
                ],
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_rules_validates_organization_selection_is_boolean(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $valid = Validator::make(
            [
                'troopers' => [
                    ['organization_selection' => '1'],
                ],
            ],
            $subject->rules()
        );
        $invalid = Validator::make(
            [
                'troopers' => [
                    ['organization_selection' => 'definitely'],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid->fails());
    }

    public function test_rules_validates_organization_ids_are_array_of_integers(): void
    {
        $subject = new UpdateTroopersRequest;
        $this->setupMockedRoute($subject, $this->event);

        $valid = Validator::make(
            [
                'troopers' => [
                    ['organization_ids' => [1, 2]],
                ],
            ],
            $subject->rules()
        );
        $invalid_list = Validator::make(
            [
                'troopers' => [
                    ['organization_ids' => '1'],
                ],
            ],
            $subject->rules()
        );
        $invalid_item = Validator::make(
            [
                'troopers' => [
                    ['organization_ids' => ['abc']],
                ],
            ],
            $subject->rules()
        );

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid_list->fails());
        $this->assertTrue($invalid_item->fails());
    }
}
