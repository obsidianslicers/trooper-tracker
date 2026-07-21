<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\EventTrooperStatus;
use App\Jobs\ReconcileEventRosterJob;
use App\Mail\Events\TrooperManualSelectionStandBy;
use App\Mail\Events\TrooperNextInLine;
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
use Tests\TestCase;

class ReconcileEventRosterJobTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private Event $event;
    private EventShift $shift;
    private Trooper $changed_by;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->org = Organization::factory()->create();

        $this->event = Event::factory()->create([
            Event::TROOPERS_ALLOWED  => null,
            Event::HANDLERS_ALLOWED  => null,
            Event::ORGANIZATION_ID   => $this->org->id,
        ]);

        EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($this->org)
            ->canAttend()
            ->create(['troopers_allowed' => 2, 'handlers_allowed' => null]);

        $this->shift = EventShift::factory()->forEvent($this->event)->create();

        $this->changed_by = Trooper::factory()->create();
    }

    private function makeTrooper(EventTrooperStatus $status, Carbon $signed_up_at, ?int $org_id = null, ?EventShiftStation $station = null): EventTrooper
    {
        $trooper = Trooper::factory()->create();

        return EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($trooper)
            ->withSignedUpAt($signed_up_at)
            ->create([
                EventTrooper::STATUS          => $status,
                EventTrooper::ORGANIZATION_ID => $org_id,
                EventTrooper::IS_HANDLER      => false,
                EventTrooper::COSTUME_ID      => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
                EventTrooper::EVENT_SHIFT_STATION_ID => $station?->id,
            ]);
    }

    public function test_demotes_troopers_over_org_limit(): void
    {
        $base = now()->subMinutes(10);

        $t1 = $this->makeTrooper(EventTrooperStatus::GOING, $base,                     $this->org->id);
        $t2 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinute(), $this->org->id);
        $t3 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinutes(2), $this->org->id);
        $t4 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinutes(3), $this->org->id);

        (new ReconcileEventRosterJob($this->event, $this->changed_by))->handle();

        $this->assertSame(EventTrooperStatus::GOING,    $t1->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING,    $t2->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $t3->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $t4->fresh()->status);

        Mail::assertQueued(TrooperManualSelectionStandBy::class, 2);
        Mail::assertNotQueued(TrooperNextInLine::class);
    }

    public function test_promotes_standby_when_slot_is_available(): void
    {
        $base = now()->subMinutes(10);

        $t1 = $this->makeTrooper(EventTrooperStatus::GOING,    $base,                     $this->org->id);
        $t2 = $this->makeTrooper(EventTrooperStatus::STAND_BY, $base->copy()->addMinute(), $this->org->id);

        (new ReconcileEventRosterJob($this->event, $this->changed_by))->handle();

        $this->assertSame(EventTrooperStatus::GOING, $t1->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING, $t2->fresh()->status);

        Mail::assertQueued(TrooperNextInLine::class, 1);
        Mail::assertNotQueued(TrooperManualSelectionStandBy::class);
    }

    public function test_no_changes_when_roster_is_already_correct(): void
    {
        $base = now()->subMinutes(10);

        $t1 = $this->makeTrooper(EventTrooperStatus::GOING,    $base,                     $this->org->id);
        $t2 = $this->makeTrooper(EventTrooperStatus::GOING,    $base->copy()->addMinute(), $this->org->id);
        $t3 = $this->makeTrooper(EventTrooperStatus::STAND_BY, $base->copy()->addMinutes(2), $this->org->id);

        (new ReconcileEventRosterJob($this->event, $this->changed_by))->handle();

        $this->assertSame(EventTrooperStatus::GOING,    $t1->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING,    $t2->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $t3->fresh()->status);

        Mail::assertNothingQueued();
    }

    public function test_global_limit_is_enforced_when_set(): void
    {
        $this->event->update([Event::TROOPERS_ALLOWED => 2]);

        // No per-org limit for these troopers (different org with no limit)
        $other_org = Organization::factory()->create();
        EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($other_org)
            ->canAttend()
            ->create(['troopers_allowed' => null]);

        $base = now()->subMinutes(10);

        $t1 = $this->makeTrooper(EventTrooperStatus::GOING, $base, $other_org->id);
        $t2 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinute(), $other_org->id);
        $t3 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinutes(2), $other_org->id);

        (new ReconcileEventRosterJob($this->event, $this->changed_by))->handle();

        $this->assertSame(EventTrooperStatus::GOING,    $t1->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING,    $t2->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $t3->fresh()->status);

        Mail::assertQueued(TrooperManualSelectionStandBy::class, 1);
    }

    public function test_troopers_without_org_are_not_constrained_by_org_limit(): void
    {
        $base = now()->subMinutes(10);

        // Two troopers in org (at limit)
        $this->makeTrooper(EventTrooperStatus::GOING, $base, $this->org->id);
        $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinute(), $this->org->id);

        // Trooper with no org: should not be counted against the org limit
        $t_no_org = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinutes(2), null);

        (new ReconcileEventRosterJob($this->event, $this->changed_by))->handle();

        // The no-org trooper should remain GOING since there is no global limit
        $this->assertSame(EventTrooperStatus::GOING, $t_no_org->fresh()->status);
    }

    public function test_org_troopers_maxed_counts_costume_org_troopers(): void
    {
        $this->skipIfSqlite();

        // Two GOING troopers with null org_id but costume_org=[this->org->id]
        $t1 = $this->makeTrooper(EventTrooperStatus::GOING, now()->subMinutes(2), null);
        $t2 = $this->makeTrooper(EventTrooperStatus::GOING, now()->subMinute(), null);

        foreach ([$t1, $t2] as $et) {
            \Illuminate\Support\Facades\DB::table($et->getTable())
                ->where('id', $et->id)
                ->update(['costume_organization_ids' => json_encode([$this->org->id])]);
        }

        // org limit is 2 — shift should be considered maxed
        $this->assertTrue($this->shift->orgTroopersMaxed($this->org->id, false));
    }

    public function test_infers_org_from_costume_organization_ids(): void
    {
        $this->skipIfSqlite();

        $base = now()->subMinutes(10);

        // Create troopers with null organization_id but costume_organization_ids pointing to $this->org
        $t1 = $this->makeTrooper(EventTrooperStatus::GOING, $base, null);
        $t2 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinute(), null);
        $t3 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinutes(2), null);

        // Set costume_organization_ids directly (bypasses observer)
        foreach ([$t1, $t2, $t3] as $et) {
            \Illuminate\Support\Facades\DB::table($et->getTable())
                ->where('id', $et->id)
                ->update(['costume_organization_ids' => json_encode([$this->org->id])]);
        }

        (new ReconcileEventRosterJob($this->event, $this->changed_by))->handle();

        $this->assertSame(EventTrooperStatus::GOING,    $t1->fresh()->status);
        $this->assertSame(EventTrooperStatus::GOING,    $t2->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $t3->fresh()->status);

        Mail::assertQueued(TrooperManualSelectionStandBy::class, 1);
    }

    public function test_reconciles_station_capacity_demotes_newest_when_station_capacity_exceeded(): void
    {
        $base = now()->subMinutes(10);

        $station = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 1])
            ->create();

        $t1 = $this->makeTrooper(EventTrooperStatus::GOING, $base, $this->org->id, $station);
        $t2 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinute(), $this->org->id, $station);

        (new ReconcileEventRosterJob($this->event, $this->changed_by))->handle();

        $this->assertSame(EventTrooperStatus::GOING,    $t1->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $t2->fresh()->status);

        Mail::assertQueued(TrooperManualSelectionStandBy::class, 1);
    }

    public function test_reconciles_global_and_station_limits_combined(): void
    {
        $this->event->update([Event::TROOPERS_ALLOWED => 1]);

        $base = now()->subMinutes(10);

        $station1 = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        $station2 = EventShiftStation::factory()
            ->forEventShift($this->shift)
            ->state([EventShiftStation::TROOPERS_ALLOWED => 2])
            ->create();

        $t1 = $this->makeTrooper(EventTrooperStatus::GOING, $base, null, $station1);
        $t2 = $this->makeTrooper(EventTrooperStatus::GOING, $base->copy()->addMinute(), null, $station2);

        (new ReconcileEventRosterJob($this->event, $this->changed_by))->handle();

        $this->assertSame(EventTrooperStatus::GOING,    $t1->fresh()->status);
        $this->assertSame(EventTrooperStatus::STAND_BY, $t2->fresh()->status);

        Mail::assertQueued(TrooperManualSelectionStandBy::class, 1);
    }
}
