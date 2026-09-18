<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\AssignEventTrooperCreditCommand;
use App\Features\Events\Commands\AssignEventTrooperCreditCommandHandler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see AssignEventTrooperCreditCommandHandler
 */
class AssignEventTrooperCreditCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_assigns_org_credit_and_clears_legacy_organization_id(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $stale_org = $this->makeRootOrganization();
        $org = $this->makeRootOrganization();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper, null);
        $event_trooper->updateQuietly([
            EventTrooper::ORGANIZATION_ID => $stale_org->id,
            EventTrooper::COSTUME_ORGANIZATION_IDS => null,
        ]);

        $subject = new AssignEventTrooperCreditCommandHandler;
        $subject(new AssignEventTrooperCreditCommand(
            event_trooper: $event_trooper,
            organization_ids: [$org->id],
            actor: $admin,
        ));

        $event_trooper->refresh();
        $this->assertNull($event_trooper->organization_id);
        $this->assertSame([$org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_filters_submitted_org_ids_to_moderator_scope(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $allowed_org = $this->makeRootOrganization();
        $blocked_org = $this->makeRootOrganization();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($allowed_org)->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($allowed_org)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($blocked_org)->asMember()->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper, null);

        $subject = new AssignEventTrooperCreditCommandHandler;
        $subject(new AssignEventTrooperCreditCommand(
            event_trooper: $event_trooper,
            organization_ids: [$allowed_org->id, $blocked_org->id],
            actor: $moderator,
        ));

        $event_trooper->refresh();
        $this->assertSame([$allowed_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_without_override_drops_submission_when_trooper_has_no_eligible_orgs(): void
    {
        // Documents why the override path exists: the normal path always re-derives credit from
        // the trooper's own live eligibility and intersects the submission against it, so when
        // that eligibility is empty (no TrooperAssignment at all), the submitted org is silently
        // dropped rather than saved — which is exactly the case the override path fixes below.
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = $this->makeRootOrganization();

        $event_trooper = $this->makeAttendedEventTrooper($trooper, null);

        $subject = new AssignEventTrooperCreditCommandHandler;
        $subject(new AssignEventTrooperCreditCommand(
            event_trooper: $event_trooper,
            organization_ids: [$org->id],
            actor: $admin,
        ));

        $event_trooper->refresh();
        $this->assertSame([], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_override_assigns_credit_when_trooper_has_no_eligible_orgs(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = $this->makeRootOrganization();

        $event_trooper = $this->makeAttendedEventTrooper($trooper, null);

        $subject = new AssignEventTrooperCreditCommandHandler;
        $subject(new AssignEventTrooperCreditCommand(
            event_trooper: $event_trooper,
            organization_ids: [$org->id],
            actor: $admin,
            is_override: true,
        ));

        $event_trooper->refresh();
        $this->assertSame([$org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_override_still_filters_to_moderator_authority(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $moderator_org = $this->makeRootOrganization();
        $unauthorized_org = $this->makeRootOrganization();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($moderator_org)->asModerator()->create();

        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = $this->makeAttendedEventTrooper($trooper, null);

        $subject = new AssignEventTrooperCreditCommandHandler;
        $subject(new AssignEventTrooperCreditCommand(
            event_trooper: $event_trooper,
            organization_ids: [$moderator_org->id, $unauthorized_org->id],
            actor: $moderator,
            is_override: true,
        ));

        $event_trooper->refresh();
        $this->assertSame([$moderator_org->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_does_not_change_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = $this->makeRootOrganization();
        $costume = Costume::factory()->withName('TK Classic')->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org)->asMember()->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper, $costume->id);

        $subject = new AssignEventTrooperCreditCommandHandler;
        $subject(new AssignEventTrooperCreditCommand(
            event_trooper: $event_trooper,
            organization_ids: [$org->id],
            actor: $admin,
        ));

        $event_trooper->refresh();
        $this->assertSame($costume->id, $event_trooper->costume_id);
    }

    private function makeRootOrganization(): Organization
    {
        $organization = Organization::factory()->create();
        $organization->update([Organization::NODE_PATH => (string) $organization->id]);

        return $organization->fresh();
    }

    private function makeAttendedEventTrooper(Trooper $trooper, ?int $costume_id): EventTrooper
    {
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()->create();

        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([EventTrooper::COSTUME_ID => $costume_id]);
    }
}
