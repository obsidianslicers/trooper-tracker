<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetTroopersWithoutActivityQuery;
use App\Features\Reports\Queries\GetTroopersWithoutActivityQueryHandler;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersWithoutActivityQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_active_troopers_without_recent_attendance(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $inactive_trooper = Trooper::factory()->asMember()->create();
        $inactive_trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $active_trooper = Trooper::factory()->asMember()->create();
        $active_trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($active_trooper)
            ->asAttended()
            ->withSignedUpAt(now()->subDays(5))
            ->create();

        $subject = new GetTroopersWithoutActivityQueryHandler();

        $result = $subject(new GetTroopersWithoutActivityQuery($moderator, 30));

        $result_ids = $result->pluck('id')->all();
        $this->assertContains($inactive_trooper->id, $result_ids);

    }

    public function test_invoke_excludes_troopers_with_attendance_before_lookback(): void
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
            ->withSignedUpAt(now()->subDays(40))
            ->create();

        $subject = new GetTroopersWithoutActivityQueryHandler();

        $result = $subject(new GetTroopersWithoutActivityQuery($moderator, 30));

        $this->assertNotContains($trooper->id, $result->pluck('id')->all());

    }

    public function test_invoke_only_returns_active_membership_status(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $pending_trooper = Trooper::factory()->asPending()->create();
        $pending_trooper->trooper_assignments()->create(['organization_id' => $org->id, 'is_member' => true]);

        $subject = new GetTroopersWithoutActivityQueryHandler();

        $result = $subject(new GetTroopersWithoutActivityQuery($moderator, 30));

        $this->assertNotContains($pending_trooper->id, $result->pluck('id')->all());

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

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->withSignedUpAt(Carbon::parse('2026-01-15'))
            ->create();

        $subject = new GetTroopersWithoutActivityQueryHandler();

        $result = $subject(new GetTroopersWithoutActivityQuery($moderator, Carbon::parse('2026-02-01')));

        $this->assertNotContains($trooper->id, $result->pluck('id')->all());
    }

    public function test_invoke_filters_by_moderated_troopers(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $outside_trooper = Trooper::factory()->asMember()->create();

        $subject = new GetTroopersWithoutActivityQueryHandler();

        $result = $subject(new GetTroopersWithoutActivityQuery($moderator, 30));

        $this->assertCount(0, $result);
    }
}
