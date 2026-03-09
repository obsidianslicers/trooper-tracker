<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Enums\EventType;
use App\Features\Reports\Queries\GetEventTypeCountQuery;
use App\Features\Reports\Queries\GetEventTypeCountQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventTypeCountQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_event_type_statistics(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create(['type' => EventType::REGULAR]);
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create(['type' => EventType::CHARITY]);

        $subject = new GetEventTypeCountQueryHandler();

        $result = $subject(new GetEventTypeCountQuery($moderator, 30));

        $this->assertCount(2, $result);
    }

    public function test_invoke_groups_events_by_type(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->count(3)->create(['type' => EventType::REGULAR]);

        $subject = new GetEventTypeCountQueryHandler();

        $result = $subject(new GetEventTypeCountQuery($moderator, 30));

        $this->assertSame(3, $result->first()->count);
        $this->assertSame(EventType::REGULAR, $result->first()->event_type);
    }

    public function test_invoke_calculates_total_trooper_count(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create(['type' => EventType::REGULAR]);
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->count(5)->create();

        $subject = new GetEventTypeCountQueryHandler();

        $result = $subject(new GetEventTypeCountQuery($moderator, 30));

        $this->assertSame(5, $result->first()->total_trooper_count);
    }

    public function test_invoke_calculates_unique_trooper_count(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event1 = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create(['type' => EventType::REGULAR]);
        $event2 = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(5))->create(['type' => EventType::REGULAR]);

        $shift1 = EventShift::factory()->forEvent($event1)->create();
        $shift2 = EventShift::factory()->forEvent($event2)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->create();

        $subject = new GetEventTypeCountQueryHandler();

        $result = $subject(new GetEventTypeCountQuery($moderator, 30));

        $this->assertSame(1, $result->first()->unique_trooper_count);
    }

    public function test_invoke_respects_lookback_period(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-02-15'))->create(['type' => EventType::REGULAR]);
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-15'))->create(['type' => EventType::REGULAR]);

        $subject = new GetEventTypeCountQueryHandler();

        $result = $subject(new GetEventTypeCountQuery($moderator, Carbon::parse('2026-02-01')));

        $this->assertSame(1, $result->first()->count);
    }
}
