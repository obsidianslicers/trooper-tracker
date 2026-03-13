<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Events;

use App\Http\Requests\Admin\Events\CopyRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CopyRequestTest extends TestCase
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
    private function setupMockedRoute(CopyRequest $request, ?Event $event): void
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
        $subject = new CopyRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Event not found or unauthorized.');

        $subject = new CopyRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_requires_name(): void
    {
        $subject = new CopyRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Event::NAME, $rules);
        $this->assertContains('required', $rules[Event::NAME]);
    }

    public function test_rules_validates_name_is_string(): void
    {
        $subject = new CopyRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertContains('string', $rules[Event::NAME]);
    }

    public function test_rules_validates_name_max_length(): void
    {
        $subject = new CopyRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                Event::NAME => str_repeat('a', 129), // max is 128
                Event::EVENT_START => now()->addDays(10)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Event::NAME, $validator->errors()->toArray());
    }

    public function test_rules_requires_event_start(): void
    {
        $subject = new CopyRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Event::EVENT_START, $rules);
        $this->assertContains('required', $rules[Event::EVENT_START]);
    }

    public function test_rules_validates_event_start_is_date(): void
    {
        $subject = new CopyRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertContains('date', $rules[Event::EVENT_START]);
    }

    public function test_rules_validates_event_start_is_after_today(): void
    {
        $subject = new CopyRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                Event::NAME => 'Copied Event',
                Event::EVENT_START => now()->subDays(1)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Event::EVENT_START, $validator->errors()->toArray());
    }

    public function test_rules_accepts_future_event_start(): void
    {
        $subject = new CopyRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                Event::NAME => 'Copied Event',
                Event::EVENT_START => now()->addDays(10)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
