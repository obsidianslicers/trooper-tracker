<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\Events\GetEventsToCloseQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetEventsToCloseQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_active_events_that_have_ended(): void
    {
        // Create an active event that ended yesterday (OPEN status is considered active)
        $ended_event = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::yesterday(),
        ]);

        $subject = new GetEventsToCloseQuery();

        $result = $subject();

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($ended_event));
    }

    public function test_it_excludes_active_events_that_have_not_ended(): void
    {
        // Create an active event that ends tomorrow
        Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::tomorrow(),
        ]);

        $subject = new GetEventsToCloseQuery();

        $result = $subject();

        $this->assertCount(0, $result);
    }

    public function test_it_excludes_cancelled_events_that_have_ended(): void
    {
        // Create a cancelled event that ended yesterday
        Event::factory()->create([
            'status' => EventStatus::CANCELLED,
            'event_end' => Carbon::yesterday(),
        ]);

        $subject = new GetEventsToCloseQuery();

        $result = $subject();

        $this->assertCount(0, $result);
    }

    public function test_it_excludes_closed_events_that_have_ended(): void
    {
        // Create a closed event that ended yesterday
        Event::factory()->create([
            'status' => EventStatus::CLOSED,
            'event_end' => Carbon::yesterday(),
        ]);

        $subject = new GetEventsToCloseQuery();

        $result = $subject();

        $this->assertCount(0, $result);
    }

    public function test_it_returns_multiple_active_events_that_have_ended(): void
    {
        // Create multiple active events that have ended
        $ended_event1 = Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::yesterday(),
        ]);

        $ended_event2 = Event::factory()->create([
            'status' => EventStatus::DRAFT,
            'event_end' => Carbon::parse('-2 days'),
        ]);

        $ended_event3 = Event::factory()->create([
            'status' => EventStatus::SIGN_UP_LOCKED,
            'event_end' => Carbon::parse('-1 week'),
        ]);

        // Create an event that hasn't ended yet
        Event::factory()->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::tomorrow(),
        ]);

        $subject = new GetEventsToCloseQuery();

        $result = $subject();

        $this->assertCount(3, $result);
        $this->assertTrue($result->contains($ended_event1));
        $this->assertTrue($result->contains($ended_event2));
        $this->assertTrue($result->contains($ended_event3));
    }

    public function test_it_returns_empty_collection_when_no_events_need_closing(): void
    {
        // Create only future events
        Event::factory()->count(3)->create([
            'status' => EventStatus::OPEN,
            'event_end' => Carbon::tomorrow(),
        ]);

        $subject = new GetEventsToCloseQuery();

        $result = $subject();

        $this->assertCount(0, $result);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }
}
