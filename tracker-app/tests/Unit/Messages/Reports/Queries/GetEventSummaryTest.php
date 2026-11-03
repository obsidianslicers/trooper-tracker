<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Reports\Queries;

use App\Messages\Reports\Queries\GetEventSummary;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class GetEventSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_closed_events_for_moderator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create();
        Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create(); // Other org

        $result = (new GetEventSummary($moderator, 30))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($event->id, $result->first()->id);
    }

    public function test_handle_adds_event_shifts_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create();
        EventShift::factory()->forEvent($event)->count(3)->create();

        $result = (new GetEventSummary($moderator, 30))->handle();

        $this->assertTrue(isset($result->first()->event_shifts_count));
        $this->assertSame(3, $result->first()->event_shifts_count);
    }

    public function test_handle_adds_total_trooper_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift)->asAttended()->count(5)->create();

        $result = (new GetEventSummary($moderator, 30))->handle();

        $this->assertTrue(isset($result->first()->total_trooper_count));
        $this->assertSame(5, $result->first()->total_trooper_count);
    }

    public function test_handle_adds_unique_trooper_count_property(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withOrganization($org)->withEventStart(now()->subDays(10))->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()->forEventShift($shift1)->forTrooper($trooper)->asAttended()->create();
        EventTrooper::factory()->forEventShift($shift2)->forTrooper($trooper)->asAttended()->create();

        $result = (new GetEventSummary($moderator, 30))->handle();

        $this->assertTrue(isset($result->first()->unique_trooper_count));
        $this->assertSame(1, $result->first()->unique_trooper_count);
    }

    public function test_handle_respects_lookback_period(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-02-15'))->create();
        Event::factory()->asClosed()->withOrganization($org)->withEventStart(Carbon::parse('2026-01-15'))->create();

        $result = (new GetEventSummary($moderator, Carbon::parse('2026-02-01')))->handle();

        $this->assertCount(1, $result);
    }

    public function test_handle_respects_show_all_parameter(): void
    {
        Trooper::factory()->asModerator()->create();

        Event::factory()->asClosed()->withEventStart(now()->subDays(10))->count(3)->create();

        $result = (new GetEventSummary(new Trooper, 30, true))->handle();

        $this->assertCount(3, $result);
    }

    public function test_handle_can_paginate_all_event_history(): void
    {
        Event::factory()->asClosed()->withEventStart(now()->subYears(5))->create();
        Event::factory()->asClosed()->withEventStart(now()->subDays(5))->count(2)->create();

        $result = (new GetEventSummary(
            moderator: new Trooper,
            lookback: null,
            show_all: true,
            page_size: 2,
        ))->handle();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(3, $result->total());
        $this->assertSame(2, $result->lastPage());
    }
}
