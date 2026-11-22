<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowEventCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_renders_the_svelte_calendar(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 09:00:00'));
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $start = Carbon::parse('2026-12-05 10:00:00');
        $event = Event::factory()->create([
            Event::NAME => 'Toy drive',
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(3),
        ]);
        EventShift::factory()->forEvent($event)->create([
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(3),
        ]);

        $response = $this->actingAs($trooper)->get(route('events.calendar'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/Calendar')
            ->has('months', 12)
            ->where('months.0.key', '2026-11')
            ->where('months.0.weeks.0.0.date', '2026-11-01')
            ->where('months.11.key', '2027-10')
            ->has('weekdays', 7)
            ->where('today', '2026-11-12')
            ->has('events', 1)
            ->where('events.0.name', 'Toy drive')
            ->where('events.0.date', '2026-12-05')
            ->where('events.0.url', route('events.display', ['event' => $event]))
            ->where('events.0.is_mine', false)
            ->has('hosting_organizations'));
    }

    public function test_invoke_shows_holidays_on_their_days(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 09:00:00'));

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.calendar'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page)
        {
            $holidays = $page->toArray()['props']['holidays'];

            $this->assertSame(['Thanksgiving Day'], $holidays['2026-11-26']);
            $this->assertSame(['Independence Day'], $holidays['2027-07-04']);
            //  the calendar runs November 2026 through October 2027
            $this->assertArrayNotHasKey('2026-10-12', $holidays);
            $this->assertArrayNotHasKey('2027-11-11', $holidays);
        });
    }

    public function test_invoke_shows_no_holidays_when_turned_off(): void
    {
        config(['tracker.calendar.holidays' => 'none']);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.calendar'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $this->assertEmpty($page->toArray()['props']['holidays']));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.calendar'));

        $response->assertRedirect(route('auth.login'));
    }
}
