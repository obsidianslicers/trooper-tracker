<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cancellation through the real production workflow: an HTTP POST to
 * events.signup-update-htmx as the record's owner, which dispatches
 * UpdateEventTrooperCommand and then PromoteNextInLineEventTrooperCommand
 * synchronously within the request (not via a queued job).
 *
 * Promotion after cancellation must respect every applicable limit and the
 * deterministic queue order (signed_up_at ASC, id ASC).
 */
class CancelEventTrooperCapacityTest extends TestCase
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

    private function cancelThroughWorkflow(EventTrooper $event_trooper): void
    {
        $response = $this->actingAs($event_trooper->trooper)->post(
            route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]),
            ['status' => EventTrooperStatus::CANCELLED->value],
        );

        $response->assertOk();
    }

    private function makeTrooper(
        EventTrooperStatus $status,
        EventShiftStation $station,
        Carbon $signed_up_at,
        ?Organization $org = null,
        bool $can_act = false,
    ): EventTrooper {
        $trooper = $can_act
            ? Trooper::factory()->asActive()->withVerifiedEmail()->create()
            : Trooper::factory()->create();

        return EventTrooper::factory()
            ->forEventShift($this->shift)
            ->forTrooper($trooper)
            ->forEventShiftStation($station)
            ->create([
                EventTrooper::STATUS => $status,
                EventTrooper::ORGANIZATION_ID => ($org ?? $this->org)->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::SIGNED_UP_AT => $signed_up_at,
            ]);
    }

    private function orgGoingCount(Organization $org): int
    {
        return EventTrooper::where(EventTrooper::ORGANIZATION_ID, $org->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
    }

    private function stationGoingCount(EventShiftStation $station): int
    {
        return EventTrooper::where(EventTrooper::EVENT_SHIFT_STATION_ID, $station->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::GOING->value)
            ->count();
    }

    public function test_cancellation_opens_all_limits_oldest_standby_promoted(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $canceler_cet = $this->makeTrooper(
            EventTrooperStatus::GOING,
            $this->target_station,
            now()->subMinutes(3),
            can_act: true,
        );
        $standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            now()->subMinutes(2),
        );

        $this->assertSame(1, $this->orgGoingCount($this->org), 'Organization full: 1 / 1');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Station full: 1 / 1');

        $this->cancelThroughWorkflow($canceler_cet);

        $canceler_cet->refresh();
        $standby_cet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $canceler_cet->status, 'Canceler is CANCELLED');
        $this->assertSame(EventTrooperStatus::GOING, $standby_cet->status, 'Oldest eligible standby promoted');

        $this->assertSame(1, $this->orgGoingCount($this->org), 'Organization stays within its limit of 1');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Station stays within its limit of 1');
    }

    public function test_cancellation_opens_station_but_standby_org_remains_full(): void
    {
        $org_b = Organization::factory()->create();
        EventOrganization::factory()
            ->forEvent($this->event)
            ->forOrganization($org_b)
            ->canAttend()
            ->create([
                EventOrganization::TROOPERS_ALLOWED => 1,
            ]);

        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->other_station, now()->subMinutes(4), $org_b);
        $canceler_cet = $this->makeTrooper(
            EventTrooperStatus::GOING,
            $this->target_station,
            now()->subMinutes(3),
            can_act: true,
        );
        $standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            now()->subMinutes(2),
            $org_b,
        );

        $this->assertSame(1, $this->orgGoingCount($org_b), 'Standby organization full: 1 / 1');
        $this->assertSame(1, $this->stationGoingCount($this->target_station), 'Station full: 1 / 1');

        $this->cancelThroughWorkflow($canceler_cet);

        $canceler_cet->refresh();
        $standby_cet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $canceler_cet->status, 'Canceler is CANCELLED');
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $standby_cet->status,
            'Standby remains STAND_BY because their organization is still full'
        );
        $this->assertSame(1, $this->orgGoingCount($org_b), 'Organization B stays at its limit of 1');
    }

    public function test_cancellation_opens_org_but_oldest_standby_station_remains_full(): void
    {
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);
        $this->other_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, now()->subMinutes(5));
        $canceler_cet = $this->makeTrooper(
            EventTrooperStatus::GOING,
            $this->other_station,
            now()->subMinutes(4),
            can_act: true,
        );
        $full_station_standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            now()->subMinutes(3),
        );
        $open_station_standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->other_station,
            now()->subMinutes(2),
        );

        $this->assertSame(2, $this->orgGoingCount($this->org), 'Organization full: 2 / 2');

        $original_signed_up_at = $full_station_standby_cet->signed_up_at->copy();

        $this->cancelThroughWorkflow($canceler_cet);

        $canceler_cet->refresh();
        $full_station_standby_cet->refresh();
        $open_station_standby_cet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $canceler_cet->status, 'Canceler is CANCELLED');
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $full_station_standby_cet->status,
            'Oldest standby at the still-full station remains STAND_BY'
        );
        $this->assertSame(
            EventTrooperStatus::GOING,
            $open_station_standby_cet->status,
            'Later standby at the opened station is promoted'
        );
        $this->assertTrue(
            $full_station_standby_cet->signed_up_at->equalTo($original_signed_up_at),
            'Skipped standby keeps the same signed_up_at'
        );
    }

    public function test_cancellation_with_two_standbys_promotes_only_the_oldest(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $canceler_cet = $this->makeTrooper(
            EventTrooperStatus::GOING,
            $this->target_station,
            now()->subMinutes(4),
            can_act: true,
        );
        $oldest_standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            now()->subMinutes(3),
        );
        $newer_standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            now()->subMinutes(2),
        );

        $this->cancelThroughWorkflow($canceler_cet);

        $oldest_standby_cet->refresh();
        $newer_standby_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $oldest_standby_cet->status, 'Oldest standby promoted');
        $this->assertSame(EventTrooperStatus::STAND_BY, $newer_standby_cet->status, 'Newer standby waits');
        $this->assertSame(1, $this->orgGoingCount($this->org), 'Organization stays within its limit of 1');
    }

    public function test_cancellation_with_equal_timestamps_promotes_lower_id(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);

        $signed_up_at = now()->subMinutes(2);

        $canceler_cet = $this->makeTrooper(
            EventTrooperStatus::GOING,
            $this->target_station,
            now()->subMinutes(4),
            can_act: true,
        );
        $first_standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            $signed_up_at,
        );
        $second_standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            $signed_up_at,
        );

        $this->assertTrue(
            $first_standby_cet->id < $second_standby_cet->id,
            'First-created event trooper must have the lower ID'
        );

        $this->cancelThroughWorkflow($canceler_cet);

        $first_standby_cet->refresh();
        $second_standby_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $first_standby_cet->status, 'Lower ID promoted');
        $this->assertSame(EventTrooperStatus::STAND_BY, $second_standby_cet->status, 'Higher ID waits');
    }

    public function test_standby_cancellation_preserves_remaining_queue(): void
    {
        $this->event_org->update([EventOrganization::TROOPERS_ALLOWED => 1]);
        $this->target_station->update([EventShiftStation::TROOPERS_ALLOWED => 1]);

        $this->makeTrooper(EventTrooperStatus::GOING, $this->target_station, now()->subMinutes(3));
        $canceling_standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            now()->subMinutes(2),
            can_act: true,
        );
        $remaining_standby_cet = $this->makeTrooper(
            EventTrooperStatus::STAND_BY,
            $this->target_station,
            now()->subMinute(),
        );

        $canceling_signed_up_at = $canceling_standby_cet->signed_up_at->copy();
        $remaining_signed_up_at = $remaining_standby_cet->signed_up_at->copy();

        $this->cancelThroughWorkflow($canceling_standby_cet);

        $canceling_standby_cet->refresh();
        $remaining_standby_cet->refresh();

        $this->assertSame(EventTrooperStatus::CANCELLED, $canceling_standby_cet->status, 'Canceled standby is CANCELLED');
        $this->assertSame(
            EventTrooperStatus::STAND_BY,
            $remaining_standby_cet->status,
            'No promotion happens because no capacity actually opened'
        );
        $this->assertTrue(
            $canceling_standby_cet->signed_up_at->equalTo($canceling_signed_up_at),
            'Canceled standby signup time unchanged'
        );
        $this->assertTrue(
            $remaining_standby_cet->signed_up_at->equalTo($remaining_signed_up_at),
            'Remaining standby signup time unchanged'
        );
        $this->assertTrue(
            $remaining_standby_cet->signed_up_at->greaterThan($canceling_standby_cet->signed_up_at),
            'Remaining relative queue order preserved'
        );
    }

    public function test_two_cancellations_promote_exactly_two_oldest_standbys(): void
    {
        $first_canceler_cet = $this->makeTrooper(
            EventTrooperStatus::GOING,
            $this->target_station,
            now()->subMinutes(6),
            can_act: true,
        );
        $second_canceler_cet = $this->makeTrooper(
            EventTrooperStatus::GOING,
            $this->target_station,
            now()->subMinutes(5),
            can_act: true,
        );
        $standby1_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, now()->subMinutes(4));
        $standby2_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, now()->subMinutes(3));
        $standby3_cet = $this->makeTrooper(EventTrooperStatus::STAND_BY, $this->target_station, now()->subMinutes(2));

        $this->assertSame(2, $this->orgGoingCount($this->org), 'Organization full: 2 / 2');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station full: 2 / 2');

        $this->cancelThroughWorkflow($first_canceler_cet);
        $this->cancelThroughWorkflow($second_canceler_cet);

        $standby1_cet->refresh();
        $standby2_cet->refresh();
        $standby3_cet->refresh();

        $this->assertSame(EventTrooperStatus::GOING, $standby1_cet->status, 'Oldest standby promoted');
        $this->assertSame(EventTrooperStatus::GOING, $standby2_cet->status, 'Second-oldest standby promoted');
        $this->assertSame(EventTrooperStatus::STAND_BY, $standby3_cet->status, 'Next standby still waits');

        $this->assertSame(2, $this->orgGoingCount($this->org), 'Organization stays within its limit of 2');
        $this->assertSame(2, $this->stationGoingCount($this->target_station), 'Station stays within its limit of 2');
    }
}
