<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\ReconcileEventRosterCommand;
use App\Features\Events\Commands\ReconcileEventRosterCommandHandler;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Deterministic standby queue ordering during reconciliation.
 *
 * Standby troopers are evaluated in signed_up_at ASC order with
 * event_trooper.id ASC as the tie-breaker. A standby whose station is full is
 * skipped without losing priority: their signed_up_at is untouched and they
 * are promoted first once capacity opens.
 *
 * @see ReconcileEventRosterCommandHandler
 */
class ReconcileEventRosterQueueOrderTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Event $event;
    private EventShift $shift;
    private EventShiftStation $target_station;
    private EventShiftStation $other_station;
    private EventOrganization $event_org;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();

        $this->org = Organization::factory()->create();

        $this->event = Event::factory()->create([
            Event::TROOPERS_ALLOWED => null,
        ]);

        $this->event_org = EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($this->org)
            ->canAttend()
            ->create([
                EventOrganization::TROOPERS_ALLOWED => 2,
            ]);

        $this->shift = EventShift::factory()->forEvent($this->event)->create();

        $this->target_station = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        $this->other_station = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();
    }

    private function reconcile(): void
    {
        $subject = app(ReconcileEventRosterCommandHandler::class);

        $subject(new ReconcileEventRosterCommand($this->event, Trooper::factory()->create()));
    }

    private function makeTrooper(
        EventTrooperStatus $status,
        EventShiftStation $station,
        Carbon $signed_up_at,
    ): EventTrooper {
        return EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper(Trooper::factory()->create())
            ->forEventShiftStation($station)
            ->create([
                EventTrooper::STATUS => $status,
                EventTrooper::ORGANIZATION_ID => $this->org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => $signed_up_at,
            ]);
    }

    private function orgGoingCount(): int
    {
        return EventTrooper::where(EventTrooper::ORGANIZATION_ID, $this->org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
    }

    private function stationGoingCount(EventShiftStation $station): int
    {
        return EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $station->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
    }

    public function test_older_signed_up_at_is_promoted_first(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $older_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, now()->subMinutes(2));
        $newer_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, now()->subMinute());

        $this->assertSame(0, $this->orgGoingCount(), 'Exactly one organization spot is available: 0 / 1');

        $this->reconcile();

        $older_cet->refresh();
        $newer_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $older_cet->status, 'Older signup promoted first');
        $this->assertSame(EventTrooperStatus::STAND_BY, $newer_cet->status, 'Newer signup waits');
    }

    public function test_equal_signed_up_at_uses_lower_id_as_tie_breaker(): void
    {
        $signed_up_at = now();

        $first_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, $signed_up_at);
        $second_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, $signed_up_at);

        $this->assertTrue(
            $first_cet->id < $second_cet->id,
            'First-created event trooper must have the lower ID'
        );

        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $this->assertSame(0, $this->orgGoingCount(), 'Exactly one organization spot is available: 0 / 1');
        $this->assertSame(0, $this->stationGoingCount($this->target_station), 'Exactly one station spot is available: 0 / 1');

        $this->reconcile();

        $first_cet->refresh();
        $second_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $first_cet->status, 'Lower ID promoted when times equal');
        $this->assertSame(EventTrooperStatus::STAND_BY, $second_cet->status, 'Higher ID remains STAND_BY');

        $this->assertSame(1, $this->orgGoingCount(), 'Final organization count is exactly 1');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Final station count is exactly 1');
    }

    public function test_skips_ineligible_standby_and_promotes_later_eligible_trooper(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 3]);
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);
        $this->other_station->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, now()->subMinutes(3));
        $this->makeTrooper(EventTrooperStatus::GOING, $this->other_station, now()->subMinutes(2));

        $oldest_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, now()->subMinute());
        $newer_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->other_station, now());

        $this->assertSame(2, $this->orgGoingCount(), 'Organization has one available spot: 2 / 3');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Target station is full: 1 / 1');
        $this->assertSame(1, $this->stationGoingCount($this->other_station), 'Other station has one available spot: 1 / 2');

        $original_signed_up_at = $oldest_cet->signed_up_at->copy();

        $this->reconcile();

        $oldest_cet->refresh();
        $newer_cet->refresh();

        $this->assertSame(EventTrooperStatus::STAND_BY, $oldest_cet->status, 'Oldest stays STAND_BY (station full)');
        $this->assertSame(EventTrooperStatus::GOING, $newer_cet->status, 'Newer at different station promoted');
        $this->assertTrue(
            $oldest_cet->signed_up_at->equalTo($original_signed_up_at),
            'Skipping does not rewrite signed_up_at'
        );

        $this->assertSame(3, $this->orgGoingCount(), 'Organization is exactly 3 / 3');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Target station remains exactly 1 / 1');
        $this->assertSame(2, $this->stationGoingCount($this->other_station), 'Other station is exactly 2 / 2');

        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 2]);
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 4]);

        $this->reconcile();

        $oldest_cet->refresh();
        $newer_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $oldest_cet->status, 'Skipped standby promoted once capacity opens');
        $this->assertSame(EventTrooperStatus::GOING, $newer_cet->status, 'Earlier promotion is kept');
        $this->assertTrue(
            $oldest_cet->signed_up_at->equalTo($original_signed_up_at),
            'Original signup time preserved throughout'
        );
    }

    public function test_skipped_standby_retains_queue_priority_over_newer_standby(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 3]);
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);
        $this->other_station->update([EventShiftStation::TROOPERS_ALLOWED => 2]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, now()->subMinutes(5));
        $this->makeTrooper(EventTrooperStatus::GOING, $this->other_station, now()->subMinutes(4));

        $skipped_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, now()->subMinutes(3));
        $promoted_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->other_station, now()->subMinutes(2));

        $original_signed_up_at = $skipped_cet->signed_up_at->copy();

        $this->reconcile();

        $skipped_cet->refresh();
        $promoted_cet->refresh();

        $this->assertSame(EventTrooperStatus::STAND_BY, $skipped_cet->status, 'Oldest skipped (station full)');
        $this->assertSame(EventTrooperStatus::GOING, $promoted_cet->status, 'Later eligible trooper promoted');

        $newest_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, now());

        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 2]);
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 4]);

        $this->reconcile();

        $skipped_cet->refresh();
        $promoted_cet->refresh();
        $newest_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $skipped_cet->status, 'Skipped standby promoted first');
        $this->assertSame(EventTrooperStatus::GOING, $promoted_cet->status, 'Earlier promotion is kept');
        $this->assertSame(EventTrooperStatus::STAND_BY, $newest_cet->status, 'Newest standby waits for next spot');
        $this->assertTrue(
            $skipped_cet->signed_up_at->equalTo($original_signed_up_at),
            'Skipped standby keeps original signup time'
        );
    }
}
