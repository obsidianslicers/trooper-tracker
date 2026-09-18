<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetEventTroopersMissingCreditQuery;
use App\Features\Troopers\Queries\GetEventTroopersMissingCreditQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see GetEventTroopersMissingCreditQueryHandler
 */
class GetEventTroopersMissingCreditQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_finds_shift_with_null_credit_columns(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = $this->makeAttendedEventTrooper($trooper);
        $event_trooper->updateQuietly([
            EventTrooper::ORGANIZATION_ID => null,
            EventTrooper::COSTUME_ORGANIZATION_IDS => null,
        ]);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $admin));

        $this->assertSame([$event_trooper->id], $result->pluck('event_trooper_id')->all());
        $this->assertFalse($result->first()['has_orphaned_db_value']);
    }

    public function test_invoke_detects_orphaned_credit_not_resolvable_via_organizations_pivot(): void
    {
        // Mirrors the real-world case: the trooper has an active tt_trooper_assignments row for
        // an org (so Fix406-style logic derives credit from it), but no corresponding
        // tt_trooper_organizations pivot row for that same org — so the service-record page's
        // display logic (HasOrgCreditAnnotation, matched against organizations()) can't resolve
        // it to a visible badge, even though the DB column is populated.
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $assignment_org = $this->makeRootOrganization();
        $profile_org = $this->makeRootOrganization();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($assignment_org)->asMember()->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($profile_org)->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper);
        $event_trooper->updateQuietly([
            EventTrooper::ORGANIZATION_ID => null,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$assignment_org->id],
        ]);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $admin));

        $this->assertSame([$event_trooper->id], $result->pluck('event_trooper_id')->all());
        $this->assertTrue($result->first()['has_orphaned_db_value']);
    }

    public function test_invoke_does_not_flag_shift_with_resolvable_credit(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = $this->makeRootOrganization();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper);
        $event_trooper->updateQuietly([
            EventTrooper::ORGANIZATION_ID => null,
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$org->id],
        ]);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $admin));

        $this->assertCount(0, $result);
    }

    public function test_invoke_flags_trooper_with_no_club_membership_at_all(): void
    {
        // Real-world case: a trooper with zero non-deleted tt_trooper_organizations rows can
        // never show a "Credited To" badge, no matter what gets assigned — HasOrgCreditAnnotation
        // has nothing to match a credited org against. The UI needs this flag to explain why
        // assigning credit here won't visibly change anything until that's fixed separately.
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_trooper = $this->makeAttendedEventTrooper($trooper);
        $event_trooper->updateQuietly([
            EventTrooper::ORGANIZATION_ID => null,
            EventTrooper::COSTUME_ORGANIZATION_IDS => null,
        ]);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $admin));

        $this->assertTrue($result->first()['trooper_has_no_club_membership']);
    }

    public function test_invoke_does_not_flag_trooper_with_some_club_membership(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org = $this->makeRootOrganization();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)->create();

        $event_trooper = $this->makeAttendedEventTrooper($trooper);
        $event_trooper->updateQuietly([
            EventTrooper::ORGANIZATION_ID => null,
            EventTrooper::COSTUME_ORGANIZATION_IDS => null,
        ]);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $admin));

        $this->assertFalse($result->first()['trooper_has_no_club_membership']);
    }

    public function test_invoke_scopes_results_to_moderator_authority(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $allowed_org = $this->makeRootOrganization();
        $blocked_org = $this->makeRootOrganization();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($allowed_org)->asModerator()->create();

        $in_scope_trooper = Trooper::factory()->asActive()->create();
        TrooperAssignment::factory()->forTrooper($in_scope_trooper)->forOrganization($allowed_org)->asMember()->create();
        $in_scope_row = $this->makeAttendedEventTrooper($in_scope_trooper);

        $out_of_scope_trooper = Trooper::factory()->asActive()->create();
        TrooperAssignment::factory()->forTrooper($out_of_scope_trooper)->forOrganization($blocked_org)->asMember()->create();
        $this->makeAttendedEventTrooper($out_of_scope_trooper);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $moderator));

        $this->assertSame([$in_scope_row->id], $result->pluck('event_trooper_id')->all());
    }

    public function test_invoke_filters_to_single_trooper_when_given(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper_one = Trooper::factory()->asActive()->create();
        $trooper_two = Trooper::factory()->asActive()->create();
        $this->makeAttendedEventTrooper($trooper_one);
        $row_two = $this->makeAttendedEventTrooper($trooper_two);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $admin, trooper_id: $trooper_two->id));

        $this->assertSame([$row_two->id], $result->pluck('event_trooper_id')->all());
    }

    public function test_invoke_exposes_fallback_org_options_scoped_to_trooper_membership_and_actor_authority(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $root_org = $this->makeRootOrganization();
        $other_org = $this->makeRootOrganization();

        $authorized_moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($authorized_moderator)->forOrganization($root_org)->asModerator()->create();

        $unauthorized_moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($unauthorized_moderator)->forOrganization($other_org)->asModerator()->create();

        // Trooper is currently a member of root_org (so credit assigned there CAN display), but
        // has no TrooperAssignment at all, so getEligibleCreditOrganizations() (org_options) is
        // empty — this is what makes the fallback picker appear in the first place.
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($root_org)->create();
        $this->makeAttendedEventTrooper($trooper);

        $subject = new GetEventTroopersMissingCreditQueryHandler;

        $admin_result = $subject(new GetEventTroopersMissingCreditQuery(actor: $admin, trooper_id: $trooper->id));
        $authorized_result = $subject(new GetEventTroopersMissingCreditQuery(actor: $authorized_moderator, trooper_id: $trooper->id));
        $unauthorized_result = $subject(new GetEventTroopersMissingCreditQuery(actor: $unauthorized_moderator, trooper_id: $trooper->id));

        $this->assertFalse($admin_result->first()['has_eligible_options']);
        $this->assertSame([$root_org->id], collect($admin_result->first()['fallback_org_options'])->pluck('id')->all());

        $this->assertSame([$root_org->id], collect($authorized_result->first()['fallback_org_options'])->pluck('id')->all());

        // Moderator is authorized elsewhere, but not over the club the trooper actually belongs
        // to, so there's nothing they can correctly offer — same as the true dead-end case.
        $this->assertSame([], $unauthorized_result->first()['fallback_org_options']);
    }

    public function test_invoke_fallback_org_options_never_offers_a_club_the_trooper_is_not_in(): void
    {
        // Directly guards the bug reported live: picking a club from the fallback list that the
        // trooper doesn't actually belong to writes fine but never displays, because
        // HasOrgCreditAnnotation can only resolve credit against the trooper's *current*
        // tt_trooper_organizations rows. The fallback list must never offer such a club at all.
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper_org = $this->makeRootOrganization();
        $other_org = $this->makeRootOrganization();

        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($trooper_org)->create();
        $this->makeAttendedEventTrooper($trooper);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $admin, trooper_id: $trooper->id));

        $ids = collect($result->first()['fallback_org_options'])->pluck('id')->all();
        $this->assertContains($trooper_org->id, $ids);
        $this->assertNotContains($other_org->id, $ids);
    }

    public function test_invoke_fallback_org_options_requires_actor_authority_over_trooper_root_club(): void
    {
        // tt_trooper_organizations only ever stores root/primary-club membership (enforced by
        // TrooperOrganizationObserver), so a trooper's fallback options are already root-level by
        // construction — this just confirms actor authority is still the gating factor when the
        // moderator's own authority is scoped to a sub-org/unit under that same club.
        $root_org = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $child_org = Organization::factory()->asUnit()->withParent($root_org)->withNodePath('100:200:')->create();

        $moderator = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($child_org)->asModerator()->create();

        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($root_org)->create();
        $this->makeAttendedEventTrooper($trooper);

        $subject = new GetEventTroopersMissingCreditQueryHandler;
        $result = $subject(new GetEventTroopersMissingCreditQuery(actor: $moderator, trooper_id: $trooper->id));

        $this->assertSame([], $result->first()['fallback_org_options']);
    }

    private function makeRootOrganization(): Organization
    {
        $organization = Organization::factory()->create();
        $organization->update([Organization::NODE_PATH => (string) $organization->id]);

        return $organization->fresh();
    }

    private function makeAttendedEventTrooper(Trooper $trooper): EventTrooper
    {
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()->create();

        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([EventTrooper::COSTUME_ID => null]);
    }
}
