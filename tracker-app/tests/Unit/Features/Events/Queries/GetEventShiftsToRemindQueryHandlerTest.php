<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Events\Queries\GetEventShiftsToRemindQuery;
use App\Features\Events\Queries\GetEventShiftsToRemindQueryHandler;
use App\Models\EventShift;
use App\Models\EventTrooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventShiftsToRemindQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    private GetEventShiftsToRemindQueryHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new GetEventShiftsToRemindQueryHandler();
    }

    public function test_returns_closed_shift_with_null_last_notified_at(): void
    {
        $shift = EventShift::factory()->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->subDays(1),
            EventShift::LAST_NOTIFIED_AT => null,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $result = ($this->subject)(new GetEventShiftsToRemindQuery());

        $this->assertTrue($result->contains('id', $shift->id));
    }

    public function test_returns_closed_shift_last_notified_more_than_three_days_ago(): void
    {
        $shift = EventShift::factory()->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->subDays(5),
            EventShift::LAST_NOTIFIED_AT => Carbon::now()->subDays(4),
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $result = ($this->subject)(new GetEventShiftsToRemindQuery());

        $this->assertTrue($result->contains('id', $shift->id));
    }

    public function test_excludes_shift_notified_less_than_three_days_ago(): void
    {
        $shift = EventShift::factory()->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->subDays(2),
            EventShift::LAST_NOTIFIED_AT => Carbon::now()->subDays(1),
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $result = ($this->subject)(new GetEventShiftsToRemindQuery());

        $this->assertFalse($result->contains('id', $shift->id));
    }

    public function test_excludes_shift_closed_more_than_thirty_days_ago(): void
    {
        $shift = EventShift::factory()->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->subDays(31),
            EventShift::LAST_NOTIFIED_AT => null,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $result = ($this->subject)(new GetEventShiftsToRemindQuery());

        $this->assertFalse($result->contains('id', $shift->id));
    }

    public function test_excludes_active_shifts(): void
    {
        $shift = EventShift::factory()->create([
            EventShift::STATUS => EventStatus::OPEN,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->subDays(1),
            EventShift::LAST_NOTIFIED_AT => null,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        $result = ($this->subject)(new GetEventShiftsToRemindQuery());

        $this->assertFalse($result->contains('id', $shift->id));
    }

    public function test_excludes_shifts_with_no_going_or_tentative_troopers(): void
    {
        $shift = EventShift::factory()->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->subDays(1),
            EventShift::LAST_NOTIFIED_AT => null,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
        ]);

        $result = ($this->subject)(new GetEventShiftsToRemindQuery());

        $this->assertFalse($result->contains('id', $shift->id));
    }

    public function test_includes_shift_with_tentative_trooper(): void
    {
        $shift = EventShift::factory()->create([
            EventShift::STATUS => EventStatus::CLOSED,
            EventShift::SHIFT_ENDS_AT => Carbon::now()->subDays(1),
            EventShift::LAST_NOTIFIED_AT => null,
        ]);
        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::STATUS => EventTrooperStatus::TENTATIVE,
        ]);

        $result = ($this->subject)(new GetEventShiftsToRemindQuery());

        $this->assertTrue($result->contains('id', $shift->id));
    }
}
