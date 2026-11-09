<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_events_list_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertOk();
        $response->assertViewIs('pages.events.list');
    }

    public function test_invoke_marks_only_the_events_that_do_not_count(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $uncounted = EventType::factory()->uncounted()->create([EventType::NAME => 'Armor Party Plus']);
        $this->upcomingEvent('Armor build night', $uncounted->slug);
        $this->upcomingEvent('Hospital visit', EventType::DEFAULT_SLUG);

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertOk();
        $response->assertSeeInOrder(['Armor build night', 'not-counted-pill', 'not-counted-notice'], false);
        $response->assertSee("Armor Party Plus events don't count toward troop totals.");
        $this->assertSame(1, substr_count($response->getContent(), 'not-counted-pill'));
        $this->assertSame(1, substr_count($response->getContent(), 'not-counted-notice'));
    }

    public function test_invoke_loads_event_types_with_the_events(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $uncounted = EventType::factory()->uncounted()->create();
        foreach (range(1, 3) as $i)
        {
            $this->upcomingEvent("Event {$i}", $uncounted->slug);
        }

        DB::enableQueryLog();
        $this->actingAs($trooper)->get(route('events.list'))->assertOk();
        $type_queries = collect(DB::getQueryLog())
            ->filter(fn (array $q) => str_contains($q['query'], 'from "tt_event_types"'));

        $this->assertCount(1, $type_queries);
    }

    private function upcomingEvent(string $name, string $type): Event
    {
        $start = now()->addWeek();
        $event = Event::factory()->create([
            Event::NAME => $name,
            Event::TYPE => $type,
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(4),
        ]);
        EventShift::factory()->forEvent($event)->create([
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(4),
        ]);

        return $event;
    }

    public function test_invoke_renders_hosting_organization_parents_for_upward_filtering(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertOk();
        $response->assertSee("data-hosting-organization-parents='", false);
        $response->assertSee("\"{$region->id}\":\"{$club->id}\"", false);
        $response->assertSee("\"{$unit->id}\":\"{$region->id}\"", false);
    }

    public function test_invoke_shows_support_widget_only_when_a_goal_is_set(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        config(['tracker.support.goal' => '300']);
        $this->actingAs($trooper)->get(route('events.list'))
            ->assertSee(route('widgets.support-htmx'), false);

        config(['tracker.support.goal' => null]);
        $this->actingAs($trooper)->get(route('events.list'))
            ->assertDontSee(route('widgets.support-htmx'), false);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
