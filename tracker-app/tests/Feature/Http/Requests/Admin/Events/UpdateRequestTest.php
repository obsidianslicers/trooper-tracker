<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Events;

use App\Http\Requests\Admin\Events\UpdateRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
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
    private function setupMockedRoute(UpdateRequest $request, ?Event $event): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('event')
            ->andReturn($event);
        $mock_route->shouldReceive('parameter')
            ->with('event', \Mockery::any())
            ->andReturn($event);
        $request->setRouteResolver(fn () => $mock_route);
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn () => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Event not found or unauthorized.');

        $subject = new UpdateRequest;
        $subject->setUserResolver(fn () => $this->moderator);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_returns_common_rules(): void
    {
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn () => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        // Verify common rules are included (from CommonRules trait)
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
    }

    public function test_messages_provides_custom_error_for_troopers_allowed(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->event);
        $messages = $subject->messages();

        $this->assertArrayHasKey(Event::TROOPERS_ALLOWED.'.required_if', $messages);
        $this->assertStringContainsString('troopers allowed', $messages[Event::TROOPERS_ALLOWED.'.required_if']);
    }

    public function test_messages_provides_custom_error_for_handlers_allowed(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->event);
        $messages = $subject->messages();

        $this->assertArrayHasKey(Event::HANDLERS_ALLOWED.'.required_if', $messages);
        $this->assertStringContainsString('handlers allowed', $messages[Event::HANDLERS_ALLOWED.'.required_if']);
    }

    public function test_rules_rejects_event_end_before_event_start(): void
    {
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn () => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                Event::NAME => 'Updated Event',
                Event::TYPE => 'other',
                Event::STATUS => 'open',
                Event::EVENT_START => '2024-01-15 14:00:00',
                Event::EVENT_END => '2024-01-15 13:00:00',
                Event::TENTATIVE_SIGNUPS_ALLOWED => false,
                Event::SECURE_STAGING_AREA => false,
                Event::ALLOW_BLASTERS => false,
                Event::ALLOW_PROPS => false,
                Event::PARKING_AVAILABLE => false,
                Event::ACCESSIBLE => false,
                Event::REQUIRE_MISSION_BRIEF_ACK => false,
                Event::CREATE_FORUM_THREAD => false,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Event::EVENT_END, $validator->errors()->toArray());
    }

    public function test_rules_accepts_event_end_after_event_start(): void
    {
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn () => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                Event::NAME => 'Updated Event',
                Event::TYPE => 'other',
                Event::STATUS => 'open',
                Event::EVENT_START => '2024-01-15 13:00:00',
                Event::EVENT_END => '2024-01-15 14:00:00',
                Event::TENTATIVE_SIGNUPS_ALLOWED => false,
                Event::SECURE_STAGING_AREA => false,
                Event::ALLOW_BLASTERS => false,
                Event::ALLOW_PROPS => false,
                Event::PARKING_AVAILABLE => false,
                Event::ACCESSIBLE => false,
                Event::REQUIRE_MISSION_BRIEF_ACK => false,
                Event::CREATE_FORUM_THREAD => false,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->errors()->has(Event::EVENT_END));
    }
}
