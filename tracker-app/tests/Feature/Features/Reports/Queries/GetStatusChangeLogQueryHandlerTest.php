<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Enums\EventTrooperStatus;
use App\Features\Reports\Queries\GetStatusChangeLogQuery;
use App\Features\Reports\Queries\GetStatusChangeLogQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetStatusChangeLogQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_genuine_status_change_to_attended_for_moderated_troopers(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();
        $trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName(EventTrooper::STATUS)
            ->withOldValue(EventTrooperStatus::GOING->value)
            ->withNewValue(EventTrooperStatus::ATTENDED->value)
            ->createdAt(now()->subDays(5))
            ->state([ModelChange::TROOPER_ID => $moderator->id])
            ->create();

        $subject = new GetStatusChangeLogQueryHandler;

        $result = $subject(new GetStatusChangeLogQuery($moderator, 30));

        $this->assertCount(1, $result);
    }

    public function test_invoke_ignores_unrelated_save_when_status_never_changed(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();
        $trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([
                'updated_at' => now()->subDays(1),
                'updated_id' => $moderator->id,
            ]);

        $subject = new GetStatusChangeLogQueryHandler;

        $result = $subject(new GetStatusChangeLogQuery($moderator, 30));

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_self_updated_records(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();
        $trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName(EventTrooper::STATUS)
            ->withOldValue(EventTrooperStatus::GOING->value)
            ->withNewValue(EventTrooperStatus::ATTENDED->value)
            ->createdAt(now()->subDays(5))
            ->state([ModelChange::TROOPER_ID => $trooper->id])
            ->create();

        $subject = new GetStatusChangeLogQueryHandler;

        $result = $subject(new GetStatusChangeLogQuery($moderator, 30));

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_status_changes_to_non_attended_values(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();
        $trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->create();

        ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName(EventTrooper::STATUS)
            ->withOldValue(EventTrooperStatus::STAND_BY->value)
            ->withNewValue(EventTrooperStatus::GOING->value)
            ->createdAt(now()->subDays(5))
            ->state([ModelChange::TROOPER_ID => $moderator->id])
            ->create();

        $subject = new GetStatusChangeLogQueryHandler;

        $result = $subject(new GetStatusChangeLogQuery($moderator, 30));

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_non_status_field_changes(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();
        $trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName('costume_id')
            ->withOldValue('1')
            ->withNewValue(EventTrooperStatus::ATTENDED->value)
            ->createdAt(now()->subDays(5))
            ->state([ModelChange::TROOPER_ID => $moderator->id])
            ->create();

        $subject = new GetStatusChangeLogQueryHandler;

        $result = $subject(new GetStatusChangeLogQuery($moderator, 30));

        $this->assertCount(0, $result);
    }

    public function test_invoke_respects_lookback_period(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $trooper = Trooper::factory()->asMember()->create();
        $trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $second_shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();
        $second_event_trooper = EventTrooper::factory()->forEventShift($second_shift)->forTrooper($trooper)->asAttended()->create();

        ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName(EventTrooper::STATUS)
            ->withNewValue(EventTrooperStatus::ATTENDED->value)
            ->createdAt(Carbon::parse('2026-01-15'))
            ->state([ModelChange::TROOPER_ID => $moderator->id])
            ->create();

        ModelChange::factory()
            ->forEventTrooper($second_event_trooper)
            ->withFieldName(EventTrooper::STATUS)
            ->withNewValue(EventTrooperStatus::ATTENDED->value)
            ->createdAt(Carbon::parse('2026-02-15'))
            ->state([ModelChange::TROOPER_ID => $moderator->id])
            ->create();

        $subject = new GetStatusChangeLogQueryHandler;

        $result = $subject(new GetStatusChangeLogQuery($moderator, Carbon::parse('2026-02-01')));

        $this->assertCount(1, $result);
    }

    public function test_invoke_excludes_troopers_not_moderated_by_moderator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()->forEventShift($shift)->forTrooper($other_trooper)->asAttended()->create();

        ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName(EventTrooper::STATUS)
            ->withNewValue(EventTrooperStatus::ATTENDED->value)
            ->createdAt(now()->subDays(5))
            ->state([ModelChange::TROOPER_ID => $moderator->id])
            ->create();

        $subject = new GetStatusChangeLogQueryHandler;

        $result = $subject(new GetStatusChangeLogQuery($moderator, 30));

        $this->assertCount(0, $result);
    }
}
