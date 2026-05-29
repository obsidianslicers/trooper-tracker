<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Events;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Http\Requests\Events\AttendanceUpdateHtmxRequest;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AttendanceUpdateHtmxRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $trooper;

    private EventShift $event_shift;

    private EventTrooper $event_trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trooper = Trooper::factory()->asMember()->create();

        $event = Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
        ]);

        $this->event_shift = EventShift::factory()->create([
            EventShift::EVENT_ID => $event->id,
            EventShift::STATUS => EventStatus::CLOSED,
        ]);

        $this->event_trooper = EventTrooper::factory()
            ->forEventShift($this->event_shift)
            ->forTrooper($this->trooper)
            ->asGoing()
            ->create();

        $this->actingAs($this->trooper);
    }

    private function setupMockedRoute(
        AttendanceUpdateHtmxRequest $request,
        ?EventTrooper $event_trooper
    ): void {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('event_trooper')
            ->andReturn($event_trooper);
        $mock_route->shouldReceive('parameter')
            ->with('event_trooper', \Mockery::any())
            ->andReturn($event_trooper);

        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_when_trooper_can_mark_attendance(): void
    {
        $subject = new AttendanceUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_trooper_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('EventTrooper not found or unauthorized.');

        $subject = new AttendanceUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_authorize_returns_false_when_trooper_cannot_mark_attendance(): void
    {
        $other_trooper = Trooper::factory()->asMember()->create();

        $subject = new AttendanceUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $other_trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_status_is_nullable(): void
    {
        $subject = new AttendanceUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $rules = $subject->rules();

        $this->assertArrayHasKey(EventTrooper::STATUS, $rules);
        $this->assertContains('nullable', $rules[EventTrooper::STATUS]);
    }

    public function test_rules_reject_invalid_status_value(): void
    {
        $subject = new AttendanceUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $validator = Validator::make([
            EventTrooper::STATUS => 'invalid-status',
        ], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(EventTrooper::STATUS, $validator->errors()->toArray());
    }

    public function test_rules_accept_attended_status(): void
    {
        $this->assertStatusPassesValidation(EventTrooperStatus::ATTENDED->value);
    }

    public function test_rules_accept_unable_to_attend_status(): void
    {
        $this->assertStatusPassesValidation(EventTrooperStatus::UNABLE_TO_ATTEND->value);
    }

    public function test_rules_accept_no_show_status(): void
    {
        $this->assertStatusPassesValidation(EventTrooperStatus::NO_SHOW->value);
    }

    private function assertStatusPassesValidation(string $status): void
    {
        $subject = new AttendanceUpdateHtmxRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $this->setupMockedRoute($subject, $this->event_trooper);

        $validator = Validator::make([
            EventTrooper::STATUS => $status,
        ], $subject->rules());

        $this->assertFalse($validator->fails());
    }
}