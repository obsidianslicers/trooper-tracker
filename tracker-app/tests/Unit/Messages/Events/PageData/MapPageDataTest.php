<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData;

use App\Enums\EventStatus;
use App\Messages\Events\PageData\MapPageData;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sends_the_pins_for_upcoming_events_with_a_location(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = $this->upcomingEvent([Event::LATITUDE => 27.95, Event::LONGITUDE => -82.46]);

        $subject = new MapPageData($trooper);
        $result = $this->resolve($subject->handle());

        $this->assertCount(1, $result['events']);
        $pin = $result['events'][0];
        $this->assertSame($event->id, $pin['id']);
        $this->assertSame($event->name, $pin['name']);
        $this->assertSame(route('events.display', ['event' => $event]), $pin['url']);
        $this->assertSame($event->time_display, $pin['date_range']);
        $this->assertSame($event->organization_id, $pin['organization_id']);
        $this->assertEqualsWithDelta(27.95, $pin['lat'], 0.0001);
        $this->assertEqualsWithDelta(-82.46, $pin['lng'], 0.0001);
        $this->assertSame(
            ['id', 'name', 'url', 'date_range', 'venue_address', 'organization_id', 'lat', 'lng'],
            array_keys($pin),
        );
    }

    public function test_handle_leaves_out_events_without_a_location(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->upcomingEvent([Event::LATITUDE => null, Event::LONGITUDE => null]);

        $result = $this->resolve((new MapPageData($trooper))->handle());

        $this->assertSame([], $result['events']);
    }

    public function test_handle_leaves_out_past_events(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $start = now()->subDays(3)->startOfHour();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(2),
        ]);
        $this->shift($event);

        $result = $this->resolve((new MapPageData($trooper))->handle());

        $this->assertSame([], $result['events']);
    }

    public function test_handle_sends_the_squads_to_filter_by(): void
    {
        config(['services.google.maps_api_key' => 'key']);
        $trooper = Trooper::factory()->asActive()->create();

        $result = $this->resolve((new MapPageData($trooper))->handle());

        $this->assertArrayHasKey('hosting_organizations', $result);
        $this->assertTrue($result['has_map']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolve(array $data): array
    {
        return json_decode(json_encode($data), true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upcomingEvent(array $attributes): Event
    {
        $start = now()->addDays(3)->startOfHour();

        $event = Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(8),
            ...$attributes,
        ]);
        $this->shift($event);

        return $event;
    }

    private function shift(Event $event): void
    {
        EventShift::factory()->forEvent($event)->create([
            EventShift::SHIFT_STARTS_AT => $event->event_start,
            EventShift::SHIFT_ENDS_AT => $event->event_end,
        ]);
    }
}
