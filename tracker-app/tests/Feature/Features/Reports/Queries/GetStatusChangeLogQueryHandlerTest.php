<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetStatusChangeLogQuery;
use App\Features\Reports\Queries\GetStatusChangeLogQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetStatusChangeLogQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_attended_event_troopers_for_moderated_troopers(): void
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
                'updated_at' => now()->subDays(5),
                'updated_id' => $moderator->id,
            ]);

        $subject = new GetStatusChangeLogQueryHandler();

        $result = $subject(new GetStatusChangeLogQuery($moderator, 30));

        $this->assertCount(1, $result);
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

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([
                'updated_at' => now()->subDays(5),
                'updated_id' => $trooper->id,
            ]);

        $subject = new GetStatusChangeLogQueryHandler();

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

        EventTrooper::factory()
            ->forEventShift($second_shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([
                'updated_at' => Carbon::parse('2026-02-15'),
                'updated_id' => $moderator->id,
            ]);

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([
                'updated_at' => Carbon::parse('2026-01-15'),
                'updated_id' => $moderator->id,
            ]);

        $subject = new GetStatusChangeLogQueryHandler();

        $result = $subject(new GetStatusChangeLogQuery($moderator, Carbon::parse('2026-02-01')));

        $this->assertCount(1, $result);
    }

    public function test_invoke_excludes_troopers_not_moderated_by_moderator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($other_trooper)
            ->asAttended()
            ->create(['updated_at' => now()->subDays(5)]);

        $subject = new GetStatusChangeLogQueryHandler();

        $result = $subject(new GetStatusChangeLogQuery($moderator, 30));

        $this->assertCount(0, $result);
    }
}
