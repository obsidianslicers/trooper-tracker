<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EventTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event_trooper(): void
    {
        $subject = EventTrooper::factory()->create();

        $this->assertInstanceOf(EventTrooper::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_backup_costume_relationship_returns_belongs_to(): void
    {
        $subject = EventTrooper::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $subject->backup_costume());
    }

    public function test_added_by_trooper_relationship_returns_belongs_to(): void
    {
        $subject = EventTrooper::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $subject->added_by_trooper());
    }

    public function test_get_attended_attribute_returns_true_when_status_attended(): void
    {
        $subject = EventTrooper::factory()->asAttended()->create();

        $this->assertTrue($subject->attended);
    }

    public function test_get_attended_attribute_returns_false_when_status_not_attended(): void
    {
        $subject = EventTrooper::factory()->asGoing()->create();

        $this->assertFalse($subject->attended);
    }

    public function test_get_is_going_attribute_returns_true_when_status_going(): void
    {
        $subject = EventTrooper::factory()->asGoing()->create();

        $this->assertTrue($subject->is_going);
    }

    public function test_get_is_going_attribute_returns_false_when_status_not_going(): void
    {
        $subject = EventTrooper::factory()
            ->state([EventTrooper::STATUS => EventTrooperStatus::NONE])
            ->create();

        $this->assertFalse($subject->is_going);
    }

    public function test_get_is_stand_by_attribute_returns_true_when_status_stand_by(): void
    {
        $subject = EventTrooper::factory()
            ->state([EventTrooper::STATUS => EventTrooperStatus::STAND_BY])
            ->create();

        $this->assertTrue($subject->is_stand_by);
    }

    public function test_get_is_stand_by_attribute_returns_false_when_status_not_stand_by(): void
    {
        $subject = EventTrooper::factory()->asGoing()->create();

        $this->assertFalse($subject->is_stand_by);
    }

    public function test_get_audit_label_returns_formatted_string(): void
    {
        $event = Event::factory()->state([Event::NAME => 'Test Event'])->create();
        $shift = EventShift::factory()
            ->state([EventShift::EVENT_ID => $event->{Event::ID}])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->create();

        $result = $subject->getAuditLabel();

        $this->assertStringContainsString('Test Event', $result);
    }

    public function test_get_attendance_url_contains_event_trooper_and_encrypted_status(): void
    {
        $subject = EventTrooper::factory()->create();

        $url = $subject->getAttendanceUrl(EventTrooperStatus::ATTENDED);
        $route = app('router')->getRoutes()->match(Request::create($url, 'GET'));
        $route_parameters = $route->parameters();

        $this->assertSame((string) $subject->{EventTrooper::ID}, (string) $route_parameters['event_trooper']);
        $this->assertSame(
            EventTrooperStatus::ATTENDED->value,
            Crypt::decryptString((string) $route_parameters['status'])
        );
    }

    public function test_get_costumes_returns_available_costumes(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $shift = EventShift::factory()
            ->state([EventShift::EVENT_ID => $event->{Event::ID}])
            ->create();

        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->{Event::ID},
                EventOrganization::ORGANIZATION_ID => $organization->{Organization::ID},
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();

        $costume = Costume::factory()->create();
        OrganizationCostume::factory()
            ->state([
                OrganizationCostume::ORGANIZATION_ID => $organization->{Organization::ID},
                OrganizationCostume::COSTUME_ID => $costume->{Costume::ID},
            ])
            ->create();
        TrooperCostume::factory()
            ->state([
                TrooperCostume::TROOPER_ID => $trooper->{Trooper::ID},
                TrooperCostume::ORGANIZATION_COSTUME_ID => OrganizationCostume::where(
                    OrganizationCostume::COSTUME_ID,
                    $costume->{Costume::ID}
                )->first()->{OrganizationCostume::ID},
            ])
            ->create();

        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::ORGANIZATION_ID => null,
            ]);

        $result = $subject->getCostumes();

        $this->assertIsArray($result);
        $this->assertArrayHasKey($costume->{Costume::ID}, $result);
    }

    public function test_can_update_status_returns_true_when_shift_open_and_has_ownership(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::OPEN,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $result = $subject->canUpdateStatus($trooper);

        $this->assertTrue($result);
    }

    public function test_can_update_status_returns_false_when_shift_not_open(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::CLOSED])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::CLOSED,
                EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay(),
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->create();

        $result = $subject->canUpdateStatus($trooper);

        $this->assertFalse($result);
    }

    public function test_can_update_status_returns_false_when_no_ownership(): void
    {
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::OPEN,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($other_trooper)
            ->create();

        $result = $subject->canUpdateStatus($trooper);

        $this->assertFalse($result);
    }

    public function test_can_mark_attendance_returns_true_when_shift_closed_and_has_ownership_and_status_going(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->asClosed()
            ->withEventEnd(now()->subDays(1))
            ->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::CLOSED,
                EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay(),
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $result = $subject->canMarkAttendance($trooper);

        $this->assertTrue($result);
    }

    public function test_can_mark_attendance_returns_false_when_event_updates_are_expired(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->asClosed()
            ->withEventEnd(now()->subDays(31))
            ->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::CLOSED,
                EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay(),
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $result = $subject->canMarkAttendance($trooper);

        $this->assertFalse($result);
    }

    public function test_can_mark_attendance_returns_false_when_shift_is_not_closed(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::OPEN,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $result = $subject->canMarkAttendance($trooper);

        $this->assertFalse($result);
    }

    public function test_can_mark_attendance_returns_false_when_no_ownership(): void
    {
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::CLOSED])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::CLOSED,
                EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay(),
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($other_trooper)
            ->asGoing()
            ->create();

        $result = $subject->canMarkAttendance($trooper);

        $this->assertFalse($result);
    }

    public function test_can_mark_attendance_returns_false_when_status_not_going(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::CLOSED])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::CLOSED,
                EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay(),
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->state([EventTrooper::STATUS => EventTrooperStatus::CANCELLED])
            ->create();

        $result = $subject->canMarkAttendance($trooper);

        $this->assertFalse($result);
    }

    public function test_can_update_costume_returns_true_when_shift_open_and_has_ownership_and_not_handler(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::OPEN,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->state([EventTrooper::IS_HANDLER => false])
            ->create();

        $result = $subject->canUpdateCostume($trooper);

        $this->assertTrue($result);
    }

    public function test_can_update_costume_returns_true_when_is_handler(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::OPEN,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->state([EventTrooper::IS_HANDLER => true])
            ->create();

        $result = $subject->canUpdateCostume($trooper);

        $this->assertTrue($result);
    }

    public function test_can_update_costume_returns_false_when_shift_not_open(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::CLOSED])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::CLOSED,
                EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay(),
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->state([EventTrooper::IS_HANDLER => false])
            ->create();

        $result = $subject->canUpdateCostume($trooper);

        $this->assertFalse($result);
    }

    public function test_can_update_costume_returns_false_when_no_ownership(): void
    {
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::OPEN,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($other_trooper)
            ->state([EventTrooper::IS_HANDLER => false])
            ->create();

        $result = $subject->canUpdateCostume($trooper);

        $this->assertFalse($result);
    }

    public function test_can_update_costume_returns_true_when_requesting_trooper_added_assignment(): void
    {
        $requesting_trooper = Trooper::factory()->create();
        $assigned_trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::OPEN,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($assigned_trooper)
            ->state([
                EventTrooper::IS_HANDLER => false,
                EventTrooper::ADDED_BY_TROOPER_ID => $requesting_trooper->{Trooper::ID},
            ])
            ->create();

        $result = $subject->canUpdateCostume($requesting_trooper);

        $this->assertTrue($result);
    }

    public function test_can_update_costume_returns_true_when_requesting_trooper_is_guardian(): void
    {
        $guardian_trooper = Trooper::factory()->create();
        $minor_trooper = Trooper::factory()->create([
            Trooper::GUARDIAN_ID => $guardian_trooper->{Trooper::ID},
            Trooper::DATE_OF_BIRTH => now()->subYears(16),
        ]);
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::OPEN,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($minor_trooper)
            ->state([EventTrooper::IS_HANDLER => false])
            ->create();

        $result = $subject->canUpdateCostume($guardian_trooper);

        $this->assertTrue($result);
    }

    public function test_organization_relationship_returns_belongs_to(): void
    {
        $subject = EventTrooper::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $subject->organization());
    }

    public function test_can_update_status_returns_false_when_org_limit_maxed_for_non_going_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN, Event::TROOPERS_ALLOWED => null])
            ->create();
        $shift = EventShift::factory()
            ->state([EventShift::EVENT_ID => $event->id, EventShift::STATUS => EventStatus::OPEN])
            ->create();

        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 1,
            ])
            ->create();

        // Fill the org slot with another GOING trooper
        EventTrooper::factory()
            ->forEventShift($shift)
            ->asGoing()
            ->state([EventTrooper::IS_HANDLER => false, EventTrooper::ORGANIZATION_ID => $organization->id])
            ->create();

        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->state([
                EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::ORGANIZATION_ID => $organization->id,
            ])
            ->create();

        $result = $subject->canUpdateStatus($trooper);

        $this->assertFalse($result);
    }

    public function test_get_costumes_filters_to_selected_organization_when_organization_id_set(): void
    {
        $trooper = Trooper::factory()->create();
        $org_a = Organization::factory()->create();
        $org_b = Organization::factory()->create();
        $event = Event::factory()->withOrganization($org_a)->create();
        $shift = EventShift::factory()
            ->state([EventShift::EVENT_ID => $event->id])
            ->create();

        foreach ([$org_a, $org_b] as $org)
        {
            EventOrganization::factory()
                ->state([
                    EventOrganization::EVENT_ID => $event->id,
                    EventOrganization::ORGANIZATION_ID => $org->id,
                    EventOrganization::CAN_ATTEND => true,
                ])
                ->create();
        }

        $costume_a = Costume::factory()->create();
        $oc_a = OrganizationCostume::factory()
            ->state([
                OrganizationCostume::ORGANIZATION_ID => $org_a->id,
                OrganizationCostume::COSTUME_ID => $costume_a->id,
            ])
            ->create();
        TrooperCostume::factory()
            ->state([TrooperCostume::TROOPER_ID => $trooper->id, TrooperCostume::ORGANIZATION_COSTUME_ID => $oc_a->id])
            ->create();

        $costume_b = Costume::factory()->create();
        $oc_b = OrganizationCostume::factory()
            ->state([
                OrganizationCostume::ORGANIZATION_ID => $org_b->id,
                OrganizationCostume::COSTUME_ID => $costume_b->id,
            ])
            ->create();
        TrooperCostume::factory()
            ->state([TrooperCostume::TROOPER_ID => $trooper->id, TrooperCostume::ORGANIZATION_COSTUME_ID => $oc_b->id])
            ->create();

        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->state([EventTrooper::ORGANIZATION_ID => $org_a->id])
            ->create();

        $result = $subject->getCostumes();

        $this->assertArrayHasKey($costume_a->id, $result);
        $this->assertArrayNotHasKey($costume_b->id, $result);
    }

    public function test_get_costumes_uses_can_attend_orgs_when_no_organization_id_set(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $shift = EventShift::factory()
            ->state([EventShift::EVENT_ID => $event->id])
            ->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();

        $costume = Costume::factory()->create();
        $oc = OrganizationCostume::factory()
            ->state([
                OrganizationCostume::ORGANIZATION_ID => $organization->id,
                OrganizationCostume::COSTUME_ID => $costume->id,
            ])
            ->create();
        TrooperCostume::factory()
            ->state([TrooperCostume::TROOPER_ID => $trooper->id, TrooperCostume::ORGANIZATION_COSTUME_ID => $oc->id])
            ->create();

        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->state([EventTrooper::ORGANIZATION_ID => null])
            ->create();

        $result = $subject->getCostumes();

        $this->assertArrayHasKey($costume->id, $result);
    }

    public function test_status_cast_works(): void
    {
        $subject = EventTrooper::factory()->asGoing()->create();

        $this->assertInstanceOf(EventTrooperStatus::class, $subject->{EventTrooper::STATUS});
        $this->assertSame(EventTrooperStatus::GOING, $subject->{EventTrooper::STATUS});
    }

    public function test_get_costumes_returns_only_handler_costume_for_handler_membership_role(): void
    {
        $handler_costume = Costume::factory()->state(['name' => Costume::HANDLER])->create();
        Costume::factory()->state(['name' => 'Stormtrooper'])->create();

        $trooper = Trooper::factory()->state([Trooper::MEMBERSHIP_ROLE => MembershipRole::HANDLER])->create();
        $subject = EventTrooper::factory()->forTrooper($trooper)->create();

        $result = $subject->getCostumes();

        $this->assertCount(1, $result);
        $this->assertArrayHasKey($handler_costume->id, $result);
    }

    public function test_get_eligible_credit_organizations_returns_all_member_orgs_for_handler_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $handler_costume = Costume::factory()->state(['name' => Costume::HANDLER])->create();
        $subject = EventTrooper::factory()
            ->forTrooper($trooper)
            ->state([
                EventTrooper::COSTUME_ID => $handler_costume->id,
                    // Simulate observer having filtered to only org1 via can_attend
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$org1->id],
            ])
            ->create();

        $result = $subject->getEligibleCreditOrganizations();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $org1->id));
        $this->assertTrue($result->contains('id', $org2->id));
    }

    public function test_get_eligible_credit_organizations_returns_all_member_orgs_for_command_staff_costume(): void
    {
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $command_staff_costume = Costume::factory()->state(['name' => Costume::COMMAND_STAFF])->create();
        $subject = EventTrooper::factory()
            ->forTrooper($trooper)
            ->state([
                EventTrooper::COSTUME_ID => $command_staff_costume->id,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$org1->id],
            ])
            ->create();

        $result = $subject->getEligibleCreditOrganizations();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $org1->id));
        $this->assertTrue($result->contains('id', $org2->id));
    }
}