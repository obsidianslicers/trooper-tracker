<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            ->create();

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

        $result = $subject->canUpdateStatus($shift, $trooper);

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
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->create();

        $result = $subject->canUpdateStatus($shift, $trooper);

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

        $result = $subject->canUpdateStatus($shift, $trooper);

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

        $result = $subject->canUpdateCostume($shift, $trooper);

        $this->assertTrue($result);
    }

    public function test_can_update_costume_returns_false_when_is_handler(): void
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

        $result = $subject->canUpdateCostume($shift, $trooper);

        $this->assertFalse($result);
    }

    public function test_can_update_costume_returns_false_when_shift_not_open(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::CLOSED])->create();
        $shift = EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $event->{Event::ID},
                EventShift::STATUS => EventStatus::CLOSED,
            ])
            ->create();
        $subject = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->state([EventTrooper::IS_HANDLER => false])
            ->create();

        $result = $subject->canUpdateCostume($shift, $trooper);

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

        $result = $subject->canUpdateCostume($shift, $trooper);

        $this->assertFalse($result);
    }

    public function test_status_cast_works(): void
    {
        $subject = EventTrooper::factory()->asGoing()->create();

        $this->assertInstanceOf(EventTrooperStatus::class, $subject->{EventTrooper::STATUS});
        $this->assertSame(EventTrooperStatus::GOING, $subject->{EventTrooper::STATUS});
    }
}