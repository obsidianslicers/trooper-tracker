<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_calendar_page(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.calendar'));

        $response->assertOk();
        $response->assertViewIs('pages.events.calendar');
    }

    public function test_invoke_shows_holidays_on_their_days(): void
    {
        $this->travelTo(Carbon::parse('2026-11-12 09:00:00'));

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.calendar'));

        $response->assertOk();
        $response->assertViewHas('holidays', fn (array $holidays) => $holidays['2026-11-26'] === ['Thanksgiving Day']
            && $holidays['2027-07-04'] === ['Independence Day']
            //  the calendar runs November 2026 through October 2027
            && !isset($holidays['2026-10-12'])
            && !isset($holidays['2027-11-11']));
        $response->assertSeeInOrder(['data-event-date="2026-11-26"', 'Thanksgiving Day', 'data-event-date="2026-11-27"'], false);
    }

    public function test_invoke_shows_no_holidays_when_turned_off(): void
    {
        config(['tracker.calendar.holidays' => 'none']);

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.calendar'));

        $response->assertOk();
        $response->assertDontSee('calendar-holiday');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.calendar'));

        $response->assertRedirect(route('auth.login'));
    }
}
