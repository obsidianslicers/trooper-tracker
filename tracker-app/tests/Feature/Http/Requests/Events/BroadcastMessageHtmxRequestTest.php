<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Events;

use App\Http\Requests\Events\BroadcastMessageHtmxRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BroadcastMessageHtmxRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $this->moderator = Trooper::factory()->asModerator()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $this->moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);
    }

    private function setupMockedRoute(
        BroadcastMessageHtmxRequest $request,
        ?Event $event
    ): void {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('event')
            ->andReturn($event);
        $mock_route->shouldReceive('parameter')
            ->with('event', \Mockery::any())
            ->andReturn($event);

        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_allows_moderator_to_update_event(): void
    {
        $subject = new BroadcastMessageHtmxRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_is_missing(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Event not found or unauthorized.');

        $subject = new BroadcastMessageHtmxRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_accepts_a_string_message(): void
    {
        $subject = new BroadcastMessageHtmxRequest;
        $validator = Validator::make(
            ['message' => 'The event roster is ready.'],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_rejects_a_missing_message(): void
    {
        $subject = new BroadcastMessageHtmxRequest;
        $validator = Validator::make([], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('message', $validator->errors()->toArray());
    }

    public function test_rules_rejects_a_non_string_message(): void
    {
        $subject = new BroadcastMessageHtmxRequest;
        $validator = Validator::make(
            ['message' => ['not', 'a', 'string']],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('message', $validator->errors()->toArray());
    }
}