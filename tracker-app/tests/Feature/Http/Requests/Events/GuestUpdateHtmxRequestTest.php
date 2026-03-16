<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Events;

use App\Enums\EventGuestStatus;
use App\Http\Requests\Events\GuestUpdateHtmxRequest;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GuestUpdateHtmxRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $trooper;

    private EventShift $event_shift;

    private EventGuest $event_guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trooper = Trooper::factory()->asMember()->create();
        $this->event_shift = EventShift::factory()->create();

        $this->event_guest = EventGuest::factory()
            ->forEventShift($this->event_shift)
            ->forTrooper($this->trooper)
            ->create();

        $this->actingAs($this->trooper);
    }

    private function setupMockedRoute(GuestUpdateHtmxRequest $request, ?EventGuest $event_guest): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('event_guest')
            ->andReturn($event_guest);
        $mock_route->shouldReceive('parameter')
            ->with('event_guest', \Mockery::any())
            ->andReturn($event_guest);

        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_own_guest_signup(): void
    {
        $subject = new GuestUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_guest);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_guest_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('EventGuest not found or unauthorized.');

        $subject = new GuestUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_authorize_returns_false_when_trooper_does_not_own_guest_signup(): void
    {
        $other_trooper = Trooper::factory()->asMember()->create();

        $subject = new GuestUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $other_trooper);
        $this->setupMockedRoute($subject, $this->event_guest);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_name_and_status_are_nullable(): void
    {
        $subject = new GuestUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_guest);

        $rules = $subject->rules();

        $this->assertArrayHasKey(EventGuest::NAME, $rules);
        $this->assertContains('nullable', $rules[EventGuest::NAME]);

        $this->assertArrayHasKey(EventGuest::STATUS, $rules);
        $this->assertContains('nullable', $rules[EventGuest::STATUS]);
    }

    public function test_rules_reject_invalid_status_value(): void
    {
        $subject = new GuestUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_guest);

        $validator = Validator::make([
            EventGuest::STATUS => 'invalid-status',
        ], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(EventGuest::STATUS, $validator->errors()->toArray());
    }

    public function test_rules_accept_valid_status_value(): void
    {
        $subject = new GuestUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_guest);

        $validator = Validator::make([
            EventGuest::STATUS => EventGuestStatus::GOING->value,
        ], $subject->rules());

        $this->assertFalse($validator->fails());
    }
}
