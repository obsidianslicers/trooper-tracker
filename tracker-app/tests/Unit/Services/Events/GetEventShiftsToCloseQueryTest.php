<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Events;

use App\Enums\EventStatus;
use App\Models\EventShift;
use App\Services\Events\GetEventShiftsToCloseQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GetEventShiftsToCloseQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_active_event_shifts_that_have_ended(): void
    {
        // Create an active shift that ended yesterday
        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $subject = new GetEventShiftsToCloseQuery();

        $result = $subject();

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($ended_shift));
    }

    public function test_it_excludes_active_event_shifts_that_have_not_ended(): void
    {
        // Create an active shift that ends tomorrow
        EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::tomorrow(),
        ]);

        $subject = new GetEventShiftsToCloseQuery();

        $result = $subject();

        $this->assertCount(0, $result);
    }

    public function test_it_excludes_cancelled_event_shifts_that_have_ended(): void
    {
        // Create a cancelled shift that ended yesterday
        EventShift::factory()->create([
            'status' => EventStatus::CANCELLED,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $subject = new GetEventShiftsToCloseQuery();

        $result = $subject();

        $this->assertCount(0, $result);
    }

    public function test_it_excludes_closed_event_shifts_that_have_ended(): void
    {
        // Create a closed shift that ended yesterday
        EventShift::factory()->create([
            'status' => EventStatus::CLOSED,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $subject = new GetEventShiftsToCloseQuery();

        $result = $subject();

        $this->assertCount(0, $result);
    }

    public function test_it_returns_multiple_active_event_shifts_that_have_ended(): void
    {
        // Create multiple active shifts that have ended
        $ended_shift1 = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $ended_shift2 = EventShift::factory()->create([
            'status' => EventStatus::DRAFT,
            'shift_ends_at' => Carbon::parse('-2 days'),
        ]);

        $ended_shift3 = EventShift::factory()->create([
            'status' => EventStatus::SIGN_UP_LOCKED,
            'shift_ends_at' => Carbon::parse('-1 week'),
        ]);

        // Create a shift that hasn't ended yet
        EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::tomorrow(),
        ]);

        $subject = new GetEventShiftsToCloseQuery();

        $result = $subject();

        $this->assertCount(3, $result);
        $this->assertTrue($result->contains($ended_shift1));
        $this->assertTrue($result->contains($ended_shift2));
        $this->assertTrue($result->contains($ended_shift3));
    }

    public function test_it_returns_empty_collection_when_no_event_shifts_need_closing(): void
    {
        // Create only future shifts
        EventShift::factory()->count(3)->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::tomorrow(),
        ]);

        $subject = new GetEventShiftsToCloseQuery();

        $result = $subject();

        $this->assertCount(0, $result);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_it_eager_loads_event_organization_relationship(): void
    {
        // Create a shift that needs closing
        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $subject = new GetEventShiftsToCloseQuery();

        $result = $subject();

        // Verify the relationship is loaded
        $this->assertTrue($result->first()->relationLoaded('event'));
        $this->assertTrue($result->first()->event->relationLoaded('organization'));
    }

    public function test_it_eager_loads_event_troopers_trooper_relationship(): void
    {
        // Create a shift that needs closing with troopers
        $ended_shift = EventShift::factory()->create([
            'status' => EventStatus::OPEN,
            'shift_ends_at' => Carbon::yesterday(),
        ]);

        $subject = new GetEventShiftsToCloseQuery();

        $result = $subject();

        // Verify the relationship is loaded
        $this->assertTrue($result->first()->relationLoaded('event_troopers'));
    }
}
