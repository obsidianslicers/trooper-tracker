<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\CalendarLinks\Link;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event(): void
    {
        $subject = Event::factory()->create();

        $this->assertInstanceOf(Event::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_organization_relationship_returns_belongs_to(): void
    {
        $subject = Event::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $subject->organization());
    }

    public function test_primary_organization_relationship_returns_belongs_to(): void
    {
        $subject = Event::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $subject->primary_organization());
    }

    public function test_troopers_relationship_returns_has_many_through(): void
    {
        $subject = Event::factory()->create();

        $this->assertInstanceOf(HasManyThrough::class, $subject->troopers());
    }

    public function test_event_organizations_relationship_returns_has_many(): void
    {
        $subject = Event::factory()->create();

        $this->assertInstanceOf(HasMany::class, $subject->event_organizations());
    }

    public function test_get_time_display_attribute_formats_correctly(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $end = Carbon::parse('2026-10-03 16:00:00');
        $subject = Event::factory()
            ->withEventStart($start)
            ->withEventEnd($end)
            ->create();

        $result = $subject->time_display;

        $this->assertSame('Sat - Oct 03, 2026 - 2:00pm - 4:00pm', $result);
    }

    public function test_get_full_date_display_attribute_formats_correctly(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $subject = Event::factory()
            ->withEventStart($start)
            ->create();

        $result = $subject->full_date_display;

        $this->assertSame('Sat - Oct 03, 2026', $result);
    }

    public function test_get_compact_time_display_attribute_with_different_am_pm(): void
    {
        $start = Carbon::parse('2026-10-03 11:00:00');
        $end = Carbon::parse('2026-10-03 13:00:00');
        $subject = Event::factory()
            ->withEventStart($start)
            ->withEventEnd($end)
            ->create();

        $result = $subject->compact_time_display;

        $this->assertSame('11am-1pm', $result);
    }

    public function test_get_compact_time_display_attribute_with_same_am_pm(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $end = Carbon::parse('2026-10-03 16:00:00');
        $subject = Event::factory()
            ->withEventStart($start)
            ->withEventEnd($end)
            ->create();

        $result = $subject->compact_time_display;

        $this->assertSame('2-4pm', $result);
    }

    public function test_get_compact_time_display_attribute_removes_zero_minutes(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $end = Carbon::parse('2026-10-03 16:00:00');
        $subject = Event::factory()
            ->withEventStart($start)
            ->withEventEnd($end)
            ->create();

        $result = $subject->compact_time_display;

        $this->assertStringNotContainsString(':00', $result);
    }

    public function test_get_is_open_attribute_returns_true_when_status_open(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertTrue($subject->is_open);
    }

    public function test_get_is_open_attribute_returns_false_when_status_not_open(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::DRAFT])
            ->create();

        $this->assertFalse($subject->is_open);
    }

    public function test_get_is_locked_attribute_returns_true_when_status_locked(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::SIGN_UP_LOCKED])
            ->create();

        $this->assertTrue($subject->is_locked);
    }

    public function test_get_is_locked_attribute_returns_false_when_status_not_locked(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertFalse($subject->is_locked);
    }

    public function test_get_is_draft_attribute_returns_true_when_status_draft(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::DRAFT])
            ->create();

        $this->assertTrue($subject->is_draft);
    }

    public function test_get_is_draft_attribute_returns_false_when_status_not_draft(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertFalse($subject->is_draft);
    }

    public function test_get_is_active_attribute_returns_true_when_status_draft(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::DRAFT])
            ->create();

        $this->assertTrue($subject->is_active);
    }

    public function test_get_is_active_attribute_returns_true_when_status_open(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertTrue($subject->is_active);
    }

    public function test_get_is_active_attribute_returns_true_when_status_locked(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::SIGN_UP_LOCKED])
            ->create();

        $this->assertTrue($subject->is_active);
    }

    public function test_get_is_active_attribute_returns_false_when_status_closed(): void
    {
        $subject = Event::factory()->asClosed()->create();

        $this->assertFalse($subject->is_active);
    }

    public function test_get_can_update_trooper_status_attribute_returns_true_when_active(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertTrue($subject->can_update_trooper_status);
    }

    public function test_get_can_update_trooper_status_attribute_returns_true_within_grace_period(): void
    {
        $end = Carbon::now()->subDays(20);
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::CLOSED])
            ->withEventStart($end->copy()->subHours(4))
            ->withEventEnd($end)
            ->create();

        $this->assertTrue($subject->can_update_trooper_status);
    }

    public function test_get_can_update_trooper_status_attribute_returns_false_after_grace_period(): void
    {
        $end = Carbon::now()->subDays(31);
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::CLOSED])
            ->withEventStart($end->copy()->subHours(4))
            ->withEventEnd($end)
            ->create();

        $this->assertFalse($subject->can_update_trooper_status);
    }

    public function test_get_at_risk_attribute_returns_true_when_starts_soon_with_no_troopers(): void
    {
        $start = Carbon::now()->addDays(3);
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN])
            ->withEventStart($start)
            ->withEventEnd($start->copy()->addHours(4))
            ->create();

        EventShift::factory()
            ->state([
                EventShift::EVENT_ID => $subject->{Event::ID},
                EventShift::SHIFT_STARTS_AT => $start,
                EventShift::SHIFT_ENDS_AT => $start->copy()->addHours(4),
            ])
            ->create();

        $result = $subject->fresh()->at_risk;

        $this->assertTrue($result);
    }

    public function test_get_at_risk_attribute_returns_false_when_starts_later(): void
    {
        $start = Carbon::now()->addDays(10);
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN])
            ->withEventStart($start)
            ->withEventEnd($start->copy()->addHours(4))
            ->create();

        $result = $subject->at_risk;

        $this->assertFalse($result);
    }

    public function test_get_shift_count_for_returns_correct_count(): void
    {
        $trooper = Trooper::factory()->create();
        $subject = Event::factory()->create();

        $shift_one = EventShift::factory()
            ->state([EventShift::EVENT_ID => $subject->{Event::ID}])
            ->create();
        $shift_two = EventShift::factory()
            ->state([EventShift::EVENT_ID => $subject->{Event::ID}])
            ->create();
        $shift_three = EventShift::factory()
            ->state([EventShift::EVENT_ID => $subject->{Event::ID}])
            ->create();

        EventTrooper::factory()
            ->state([
                EventTrooper::EVENT_SHIFT_ID => $shift_one->{EventShift::ID},
                EventTrooper::TROOPER_ID => $trooper->{Trooper::ID},
            ])
            ->create();
        EventTrooper::factory()
            ->state([
                EventTrooper::EVENT_SHIFT_ID => $shift_two->{EventShift::ID},
                EventTrooper::TROOPER_ID => $trooper->{Trooper::ID},
            ])
            ->create();

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(2, $result);
    }

    public function test_get_shift_count_for_returns_zero_when_not_signed_up(): void
    {
        $trooper = Trooper::factory()->create();
        $subject = Event::factory()->create();

        EventShift::factory()
            ->state([EventShift::EVENT_ID => $subject->{Event::ID}])
            ->create();

        $result = $subject->getShiftCountFor($trooper);

        $this->assertSame(0, $result);
    }

    public function test_type_cast_works(): void
    {
        $subject = Event::factory()
            ->state([Event::TYPE => EventType::REGULAR])
            ->create();

        $this->assertInstanceOf(EventType::class, $subject->{Event::TYPE});
        $this->assertSame(EventType::REGULAR, $subject->{Event::TYPE});
    }

    public function test_status_cast_works(): void
    {
        $subject = Event::factory()
            ->state([Event::STATUS => EventStatus::OPEN])
            ->create();

        $this->assertInstanceOf(EventStatus::class, $subject->{Event::STATUS});
        $this->assertSame(EventStatus::OPEN, $subject->{Event::STATUS});
    }

    public function test_create_calendar_link_returns_link_object(): void
    {
        $start = Carbon::parse('2026-10-03 14:00:00');
        $end = Carbon::parse('2026-10-03 16:00:00');
        $subject = Event::factory()->state([Event::NAME => 'Test Event'])->create();

        $result = $subject->createCalendarLink();

        $this->assertInstanceOf(Link::class, $result);
    }
}