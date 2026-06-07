<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\CalendarLinks\Link;
use Tests\TestCase;

class EventShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event_shift(): void
    {
        $subject = EventShift::factory()->create();

        $this->assertInstanceOf(EventShift::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_get_is_open_attribute_returns_true_when_event_and_shift_open(): void
    {
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertTrue($subject->is_open);
    }

    public function test_get_is_open_attribute_returns_false_when_event_not_open(): void
    {
        $event = Event::factory()->state([Event::STATUS => EventStatus::CLOSED])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertFalse($subject->is_open);
    }

    public function test_get_is_open_attribute_returns_false_when_shift_not_open(): void
    {
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::CLOSED, EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay()])
            ->create();

        $this->assertFalse($subject->is_open);
    }

    public function test_get_is_locked_attribute_returns_true_when_event_locked(): void
    {
        $event = Event::factory()->state([Event::STATUS => EventStatus::SIGN_UP_LOCKED])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertTrue($subject->is_locked);
    }

    public function test_get_is_locked_attribute_returns_true_when_shift_locked(): void
    {
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::SIGN_UP_LOCKED])
            ->create();

        $this->assertTrue($subject->is_locked);
    }

    public function test_get_is_locked_attribute_returns_false_when_neither_locked(): void
    {
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertFalse($subject->is_locked);
    }

    public function test_get_time_display_attribute_formats_correctly(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $end = Carbon::parse('2026-10-03 16:00:00');
        $subject = EventShift::factory()
            ->withShiftStartsAt($start)
            ->withShiftEndsAt($end)
            ->create();

        $result = $subject->time_display;

        $this->assertSame('Sat - Oct 03, 2026 - 2:00pm - 4:00pm', $result);
    }

    public function test_get_full_date_display_attribute_formats_correctly(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $subject = EventShift::factory()
            ->withShiftStartsAt($start)
            ->create();

        $result = $subject->full_date_display;

        $this->assertSame('Sat - Oct 03, 2026', $result);
    }

    public function test_get_compact_time_display_attribute_with_same_am_pm(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $end = Carbon::parse('2026-10-03 16:00:00');
        $subject = EventShift::factory()
            ->withShiftStartsAt($start)
            ->withShiftEndsAt($end)
            ->create();

        $result = $subject->compact_time_display;

        $this->assertSame('2-4pm', $result);
    }

    public function test_get_compact_time_display_attribute_with_different_am_pm(): void
    {
        $start = Carbon::parse('2026-10-03 11:00:00');
        $end = Carbon::parse('2026-10-03 13:00:00');
        $subject = EventShift::factory()
            ->withShiftStartsAt($start)
            ->withShiftEndsAt($end)
            ->create();

        $result = $subject->compact_time_display;

        $this->assertSame('11am-1pm', $result);
    }

    public function test_get_short_time_display_attribute_formats_correctly(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $end = Carbon::parse('2026-10-03 16:00:00');
        $subject = EventShift::factory()
            ->withShiftStartsAt($start)
            ->withShiftEndsAt($end)
            ->create();

        $result = $subject->short_time_display;

        $this->assertSame('10/03 - 2:00pm - 4:00pm', $result);
    }

    public function test_troopers_maxed_returns_false_when_troopers_allowed_null(): void
    {
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        $subject = EventShift::factory()->forEvent($event)->create();

        $result = $subject->troopersMaxed();

        $this->assertFalse($result);
    }

    public function test_troopers_maxed_returns_true_when_at_capacity(): void
    {
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => 2])->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->state([EventTrooper::IS_HANDLER => false])
            ->asGoing()
            ->count(2)
            ->create();

        $result = $subject->troopersMaxed();

        $this->assertTrue($result);
    }

    public function test_troopers_maxed_returns_false_when_below_capacity(): void
    {
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => 5])->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->state([EventTrooper::IS_HANDLER => false])
            ->asGoing()
            ->count(2)
            ->create();

        $result = $subject->troopersMaxed();

        $this->assertFalse($result);
    }

    public function test_handlers_maxed_returns_false_when_handlers_allowed_null(): void
    {
        $event = Event::factory()->state([Event::HANDLERS_ALLOWED => null])->create();
        $subject = EventShift::factory()->forEvent($event)->create();

        $result = $subject->handlersMaxed();

        $this->assertFalse($result);
    }

    public function test_handlers_maxed_returns_true_when_at_capacity(): void
    {
        $event = Event::factory()->state([Event::HANDLERS_ALLOWED => 2])->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->state([EventTrooper::IS_HANDLER => true])
            ->asGoing()
            ->count(2)
            ->create();

        $result = $subject->handlersMaxed();

        $this->assertTrue($result);
    }

    public function test_handlers_maxed_returns_false_when_below_capacity(): void
    {
        $event = Event::factory()->state([Event::HANDLERS_ALLOWED => 5])->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->state([EventTrooper::IS_HANDLER => true])
            ->asGoing()
            ->count(2)
            ->create();

        $result = $subject->handlersMaxed();

        $this->assertFalse($result);
    }

    public function test_is_signed_up_returns_true_when_trooper_signed_up(): void
    {
        $trooper = Trooper::factory()->create();
        $subject = EventShift::factory()->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->forTrooper($trooper)
            ->create();

        $result = $subject->isSignedUp($trooper);

        $this->assertTrue($result);
    }

    public function test_is_signed_up_returns_false_when_trooper_not_signed_up(): void
    {
        $trooper = Trooper::factory()->create();
        $subject = EventShift::factory()->create();

        $result = $subject->isSignedUp($trooper);

        $this->assertFalse($result);
    }

    public function test_is_going_returns_true_when_trooper_status_going(): void
    {
        $trooper = Trooper::factory()->create();
        $subject = EventShift::factory()->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $result = $subject->isGoing($trooper);

        $this->assertTrue($result);
    }

    public function test_is_going_returns_true_when_trooper_status_tentative(): void
    {
        $trooper = Trooper::factory()->create();
        $subject = EventShift::factory()->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->forTrooper($trooper)
            ->asTentative()
            ->create();

        $result = $subject->isGoing($trooper);

        $this->assertTrue($result);
    }

    public function test_is_going_returns_false_when_trooper_not_going(): void
    {
        $trooper = Trooper::factory()->create();
        $subject = EventShift::factory()->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->forTrooper($trooper)
            ->state([EventTrooper::STATUS => EventTrooperStatus::STAND_BY])
            ->create();

        $result = $subject->isGoing($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_returns_true_when_shift_open_and_not_signed_up(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $result = $subject->canSignUp($trooper);

        $this->assertTrue($result);
    }

    public function test_can_sign_up_returns_false_when_already_signed_up(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->forTrooper($trooper)
            ->create();

        $result = $subject->canSignUp($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_returns_false_when_shift_not_open(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::CLOSED])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::CLOSED, EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay()])
            ->create();

        $result = $subject->canSignUp($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_respects_shifts_allowed_limit(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->state([
                Event::STATUS => EventStatus::OPEN,
                Event::SHIFTS_ALLOWED => 2,
            ])
            ->create();

        $shift_one = EventShift::factory()->forEvent($event)->create();
        $shift_two = EventShift::factory()->forEvent($event)->create();
        $shift_three = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift_one)
            ->forTrooper($trooper)
            ->create();
        EventTrooper::factory()
            ->forEventShift($shift_two)
            ->forTrooper($trooper)
            ->create();

        $result = $shift_three->canSignUp($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_returns_false_for_minor_when_event_disallows_minors(): void
    {
        $minor_trooper = Trooper::factory()->create([
            Trooper::DATE_OF_BIRTH => now()->subYears(16),
        ]);
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $result = $subject->canSignUp($minor_trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_returns_false_for_minor_when_guardian_not_attending(): void
    {
        $guardian = Trooper::factory()->create();
        $minor_trooper = Trooper::factory()->create([
            Trooper::GUARDIAN_ID => $guardian->id,
            Trooper::DATE_OF_BIRTH => now()->subYears(16),
        ]);
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => true,
        ]);
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($organization)
            ->canAttend()
            ->create();

        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $result = $subject->canSignUp($minor_trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_returns_true_for_minor_when_guardian_attending_and_event_allows_minors(): void
    {
        $guardian = Trooper::factory()->create();
        $minor_trooper = Trooper::factory()->create([
            Trooper::GUARDIAN_ID => $guardian->id,
            Trooper::DATE_OF_BIRTH => now()->subYears(16),
        ]);
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => true,
        ]);
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($organization)
            ->canAttend()
            ->create();

        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->forTrooper($guardian)
            ->asGoing()
            ->create();

        $result = $subject->canSignUp($minor_trooper);

        $this->assertTrue($result);
    }

    public function test_can_sign_up_trooper_returns_true_when_going_and_within_limit(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->state([
                Event::STATUS => EventStatus::OPEN,
                Event::FRIENDS_ALLOWED => 2,
            ])
            ->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $result = $subject->canSignUpTrooper($trooper);

        $this->assertTrue($result);
    }

    public function test_can_sign_up_trooper_returns_true_when_going_and_friends_unlimited(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->state([
                Event::STATUS => EventStatus::OPEN,
                Event::FRIENDS_ALLOWED => null,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($subject)->forTrooper($trooper)->asGoing()->create();

        $result = $subject->canSignUpTrooper($trooper);

        $this->assertTrue($result);
    }

    public function test_can_sign_up_trooper_returns_false_when_at_friends_limit(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->state([
                Event::STATUS => EventStatus::OPEN,
                Event::FRIENDS_ALLOWED => 1,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($subject)->forTrooper($trooper)->asGoing()->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->state([
                EventTrooper::ADDED_BY_TROOPER_ID => $trooper->{Trooper::ID},
            ])
            ->create();

        $result = $subject->canSignUpTrooper($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_trooper_returns_false_when_not_going(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $result = $subject->canSignUpTrooper($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_trooper_returns_false_when_shift_not_open(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::CLOSED])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::CLOSED, EventShift::SHIFT_ENDS_AT => \Carbon\Carbon::now()->subDay()])
            ->create();

        $result = $subject->canSignUpTrooper($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_guest_returns_true_when_going_and_guests_unlimited(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->state([
                Event::STATUS => EventStatus::OPEN,
                Event::GUESTS_ALLOWED => null,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($subject)->forTrooper($trooper)->asGoing()->create();

        $result = $subject->canSignUpGuest($trooper);

        $this->assertTrue($result);
    }

    public function test_can_sign_up_guest_returns_true_when_going_and_below_limit(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->state([
                Event::STATUS => EventStatus::OPEN,
                Event::GUESTS_ALLOWED => 2,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($subject)->forTrooper($trooper)->asGoing()->create();
        EventGuest::factory()->forEventShift($subject)->forTrooper($trooper)->create();

        $result = $subject->canSignUpGuest($trooper);

        $this->assertTrue($result);
    }

    public function test_can_sign_up_guest_returns_false_when_at_limit(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()
            ->state([
                Event::STATUS => EventStatus::OPEN,
                Event::GUESTS_ALLOWED => 1,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($subject)->forTrooper($trooper)->asGoing()->create();
        EventGuest::factory()->forEventShift($subject)->forTrooper($trooper)->create();

        $result = $subject->canSignUpGuest($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_guest_returns_false_when_not_going(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $subject = EventShift::factory()->forEvent($event)->create();

        $result = $subject->canSignUpGuest($trooper);

        $this->assertFalse($result);
    }

    public function test_can_sign_up_guest_returns_false_when_shift_not_open(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $subject = EventShift::factory()->forEvent($event)->asClosed()->create();
        EventTrooper::factory()->forEventShift($subject)->forTrooper($trooper)->asGoing()->create();

        $result = $subject->canSignUpGuest($trooper);

        $this->assertFalse($result);
    }

    public function test_create_calendar_link_returns_link_object(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $end = Carbon::parse('2026-10-03 16:00:00');
        $event = Event::factory()->state([Event::NAME => 'Test Event'])->create();
        $subject = EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt($start)
            ->withShiftEndsAt($end)
            ->create();

        $result = $subject->createCalendarLink();

        $this->assertInstanceOf(Link::class, $result);
    }

    public function test_org_troopers_maxed_returns_false_when_org_not_in_event_organizations(): void
    {
        $event = Event::factory()->create();
        $organization = Organization::factory()->create();
        $subject = EventShift::factory()->forEvent($event)->create();

        $result = $subject->orgTroopersMaxed($organization->id, false);

        $this->assertFalse($result);
    }

    public function test_org_troopers_maxed_returns_false_when_limit_is_null(): void
    {
        $event = Event::factory()->create();
        $organization = Organization::factory()->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => null,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();

        $result = $subject->orgTroopersMaxed($organization->id, false);

        $this->assertFalse($result);
    }

    public function test_org_troopers_maxed_returns_false_when_below_limit(): void
    {
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        $organization = Organization::factory()->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 3,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->asGoing()
            ->state([
                EventTrooper::IS_HANDLER => false,
                EventTrooper::ORGANIZATION_ID => $organization->id,
            ])
            ->count(2)
            ->create();

        $result = $subject->orgTroopersMaxed($organization->id, false);

        $this->assertFalse($result);
    }

    public function test_org_troopers_maxed_returns_true_when_at_limit(): void
    {
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        $organization = Organization::factory()->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 2,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->asGoing()
            ->state([
                EventTrooper::IS_HANDLER => false,
                EventTrooper::ORGANIZATION_ID => $organization->id,
            ])
            ->count(2)
            ->create();

        $result = $subject->orgTroopersMaxed($organization->id, false);

        $this->assertTrue($result);
    }

    public function test_org_troopers_maxed_does_not_count_stand_by_troopers(): void
    {
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        $organization = Organization::factory()->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 1,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->state([
                EventTrooper::STATUS => \App\Enums\EventTrooperStatus::STAND_BY,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::ORGANIZATION_ID => $organization->id,
            ])
            ->create();

        $result = $subject->orgTroopersMaxed($organization->id, false);

        $this->assertFalse($result);
    }

    public function test_org_troopers_maxed_does_not_count_other_orgs(): void
    {
        $event = Event::factory()->state([Event::TROOPERS_ALLOWED => null])->create();
        $org_a = Organization::factory()->create();
        $org_b = Organization::factory()->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $org_a->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 1,
            ])
            ->create();
        $subject = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($subject)
            ->asGoing()
            ->state([
                EventTrooper::IS_HANDLER => false,
                EventTrooper::ORGANIZATION_ID => $org_b->id,
            ])
            ->create();

        $result = $subject->orgTroopersMaxed($org_a->id, false);

        $this->assertFalse($result);
    }

    public function test_status_cast_works(): void
    {
        $subject = EventShift::factory()
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertInstanceOf(EventStatus::class, $subject->{EventShift::STATUS});
        $this->assertSame(EventStatus::OPEN, $subject->{EventShift::STATUS});
    }
}