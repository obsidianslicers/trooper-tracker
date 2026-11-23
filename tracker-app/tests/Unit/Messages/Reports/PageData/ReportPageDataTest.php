<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Reports\PageData;

use App\Enums\EventTrooperStatus;
use App\Messages\Reports\PageData\EventSummaryPageData;
use App\Messages\Reports\PageData\EventTypeCountPageData;
use App\Messages\Reports\PageData\ReportsDashboardPageData;
use App\Messages\Reports\PageData\StatusChangeLogPageData;
use App\Messages\Reports\PageData\TroopersWithoutActivityPageData;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\ModelChange;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPageDataTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moderator = Trooper::factory()->asModerator()->create();
        $this->organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($this->moderator)
            ->forOrganization($this->organization)->asModerator()->create();
    }

    public function test_dashboard_sends_formatted_numbers_for_its_window(): void
    {
        $subject = new ReportsDashboardPageData(60);
        $result = $subject->handle();

        $this->assertSame(60, $result['days']);
        $this->assertSame([30, 60, 90, 180, 360], $result['lookbacks']);
        $this->assertIsString($result['personnel']['active_count']);
        $this->assertSame(EventTrooperStatus::ATTENDED->label(), $result['attended']);
    }

    public function test_dashboard_defaults_to_thirty_days(): void
    {
        $this->assertSame(30, (new ReportsDashboardPageData)->handle()['days']);
    }

    public function test_event_summary_shows_money_and_a_dash_for_none(): void
    {
        $event = $this->closedEvent('Library Day');
        $shift = EventShift::factory()->forEvent($event)->create([
            EventShift::CHARITY_DIRECT_FUNDS => 1250.5,
            EventShift::CHARITY_INDIRECT_FUNDS => 0,
        ]);
        EventTrooper::factory()->forEventShift($shift)->asAttended()->count(2)->create();

        $result = (new EventSummaryPageData($this->moderator))->handle();
        $row = $result['events'][0];

        $this->assertSame('Library Day', $row['name']);
        $this->assertSame(route('admin.events.edit', ['event' => $event]), $row['url']);
        $this->assertSame('1', $row['shifts']);
        $this->assertSame('2', $row['total_trooper_count']);
        $this->assertSame('1,250.50', $row['direct']);
        $this->assertSame('-', $row['indirect']);
        $this->assertSame('1,250.50', $result['totals']['direct']);
    }

    public function test_event_type_count_links_each_type_to_its_events(): void
    {
        $event = $this->closedEvent('Library Day');
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($shift)->asAttended()->create();

        $result = (new EventTypeCountPageData($this->moderator))->handle();
        $row = $result['event_types'][0];

        $this->assertSame($event->event_type_id, $row['id']);
        $this->assertSame(EventType::find($event->event_type_id)->name, $row['name']);
        $this->assertStringContainsString('event_type_id='.$event->event_type_id, $row['events_url']);
        $this->assertSame('1', $row['count']);
        $this->assertSame('1', $result['totals']['total_trooper_count']);
    }

    public function test_event_type_count_shows_dashes_with_nothing_closed(): void
    {
        $result = (new EventTypeCountPageData($this->moderator))->handle();

        $this->assertSame([], $result['event_types']);
        $this->assertSame('-', $result['totals']['count']);
    }

    public function test_troopers_without_activity_says_never_for_no_last_visit(): void
    {
        $trooper = Trooper::factory()->asMember()->create([Trooper::LAST_ACTIVE_AT => null]);
        $trooper->trooper_assignments()->create([
            'organization_id' => $this->organization->id,
            'is_member' => true,
        ]);

        $result = (new TroopersWithoutActivityPageData($this->moderator))->handle();
        $row = collect($result['troopers'])->firstWhere('id', $trooper->id);

        $this->assertSame('Never', $row['last_seen']);
        $this->assertSame(route('admin.troopers.show', ['trooper' => $trooper]), $row['profile_url']);
    }

    public function test_status_change_log_names_who_marked_the_troop(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $trooper->trooper_assignments()->create([
            'organization_id' => $this->organization->id,
            'is_member' => true,
        ]);
        $event = Event::factory()->create([Event::NAME => 'Library Day']);
        $shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)
            ->asAttended()->create();
        ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName(EventTrooper::STATUS)
            ->withOldValue(EventTrooperStatus::GOING->value)
            ->withNewValue(EventTrooperStatus::ATTENDED->value)
            ->createdAt(now()->subDays(5))
            ->state([ModelChange::TROOPER_ID => $this->moderator->id])
            ->create();

        $result = (new StatusChangeLogPageData($this->moderator))->handle();
        $row = $result['changes'][0];

        $this->assertSame('Library Day', $row['event_name']);
        $this->assertSame($trooper->display_name, $row['trooper_name']);
        $this->assertSame($this->moderator->display_name, $row['updated_by']);
        $this->assertStringContainsString('tab=changes', $row['trooper_url']);
    }

    private function closedEvent(string $name): Event
    {
        return Event::factory()->asClosed()->withOrganization($this->organization)
            ->withEventStart(now()->subDays(10))->create([Event::NAME => $name]);
    }
}
