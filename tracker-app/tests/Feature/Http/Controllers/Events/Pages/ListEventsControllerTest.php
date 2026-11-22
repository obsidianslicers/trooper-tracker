<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListEventsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_renders_the_svelte_events_list(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = $this->upcomingEvent('Hospital visit', EventType::idNamed('Regular'));

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/List')
            ->has('events', 1)
            ->where('events.0.id', $event->id)
            ->where('events.0.name', 'Hospital visit')
            ->where('events.0.url', route('events.display', ['event' => $event]))
            ->has('events.0.shifts', 1)
            ->missing('costume_organizations')
            ->has('hosting_organizations')
            ->has('notices'));
    }

    public function test_invoke_marks_only_the_events_that_do_not_count(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $uncounted = EventType::factory()->uncounted()->create([EventType::NAME => 'Armor Party Plus']);
        $this->upcomingEvent('Armor build night', $uncounted->id);
        $this->upcomingEvent('Hospital visit', EventType::idNamed('Regular'), 8);

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('events.0.name', 'Armor build night')
            ->where('events.0.not_counted_notice', EventType::NOT_COUNTED_NOTICE)
            ->where('events.1.name', 'Hospital visit')
            ->where('events.1.not_counted_notice', null));
    }

    public function test_invoke_loads_event_types_with_the_events(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $uncounted = EventType::factory()->uncounted()->create();
        foreach (range(1, 3) as $i)
        {
            $this->upcomingEvent("Event {$i}", $uncounted->id);
        }

        DB::enableQueryLog();
        $this->actingAs($trooper)->get(route('events.list'))->assertOk();
        $type_queries = collect(DB::getQueryLog())
            ->filter(fn (array $q) => preg_match('/from ["`]tt_event_types["`]/', $q['query']) === 1);

        $this->assertCount(1, $type_queries);
    }

    public function test_invoke_sends_hosting_organization_parents_for_upward_filtering(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertInertia(function (Assert $page) use ($club, $region, $unit)
        {
            $hosts = collect($page->toArray()['props']['hosting_organizations'])->keyBy('id');

            $this->assertNull($hosts[$club->id]['parent_id']);
            $this->assertSame($club->id, $hosts[$region->id]['parent_id']);
            $this->assertSame($region->id, $hosts[$unit->id]['parent_id']);
            $this->assertSame(2, $hosts[$unit->id]['depth']);
        });
    }

    public function test_invoke_defers_the_support_goal_only_when_a_goal_is_set(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        config(['tracker.support.goal' => '300']);
        $this->actingAs($trooper)->get(route('events.list'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('support')
                ->loadDeferredProps(fn (Assert $reload) => $reload
                    ->where('support.goal', 300)
                    ->has('support.message')));

        config(['tracker.support.goal' => null]);
        $this->actingAs($trooper)->get(route('events.list'))
            ->assertInertia(fn (Assert $page) => $page->where('support', null));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.list'));

        $response->assertRedirect(route('auth.login'));
    }

    private function upcomingEvent(string $name, int $type, int $days = 7): Event
    {
        $start = now()->addDays($days);
        $event = Event::factory()->create([
            Event::NAME => $name,
            Event::EVENT_TYPE_ID => $type,
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
}
