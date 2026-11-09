<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Reports\Queries;

use App\Messages\Reports\Queries\GetEventTypeCount;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventTypeCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_event_type_statistics(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create(['type' => 'regular']);
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create(['type' => 'charity']);

        $result = (new GetEventTypeCount($moderator, 30))->handle();

        $this->assertCount(2, $result);
    }

    public function test_handle_groups_events_by_type(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->count(3)->create(['type' => 'regular']);

        $result = (new GetEventTypeCount($moderator, 30))->handle();

        $this->assertSame(3, $result->first()->count);
        $this->assertSame('regular', $result->first()->event_type->slug);
    }

    public function test_handle_calculates_total_trooper_count(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create(['type' => 'regular']);
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->count(5)->create();

        $result = (new GetEventTypeCount($moderator, 30))->handle();

        $this->assertSame(5, $result->first()->total_trooper_count);
    }

    public function test_handle_calculates_unique_trooper_count(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create(['type' => 'regular']);
        $event2 = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create(['type' => 'regular']);

        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->create();

        $result = (new GetEventTypeCount($moderator, 30))->handle();

        $this->assertSame(1, $result->first()->unique_trooper_count);
    }

    public function test_handle_respects_lookback_period(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-02-15'))->create(['type' => 'regular']);
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-15'))->create(['type' => 'regular']);

        $result = (new GetEventTypeCount($moderator, Carbon::parse('2026-02-01')))->handle();

        $this->assertSame(1, $result->first()->count);
    }
}
