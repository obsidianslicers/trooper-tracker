<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\UpdateEventCharity;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateEventCharityTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_records_charity_on_the_events_shifts(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $subject = new UpdateEventCharity($event, [
            $shift->id => [
                'charity_name' => 'Make-A-Wish',
                'charity_hours' => '3',
                'charity_direct_funds' => '120.50',
                'charity_indirect_funds' => null,
                'charity_notes' => 'Bake sale',
            ],
        ]);
        $subject->handle();

        $shift->refresh();
        $this->assertSame('Make-A-Wish', $shift->charity_name);
        $this->assertSame(3, $shift->charity_hours);
        $this->assertEquals(120.5, $shift->charity_direct_funds);
        $this->assertEquals(0, $shift->charity_indirect_funds);
        $this->assertSame('Bake sale', $shift->charity_notes);
    }

    public function test_handle_clears_hours_left_blank(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create([EventShift::CHARITY_HOURS => 5]);

        $subject = new UpdateEventCharity($event, [$shift->id => ['charity_hours' => '']]);
        $subject->handle();

        $this->assertNull($shift->refresh()->charity_hours);
    }

    public function test_handle_skips_shifts_from_another_event(): void
    {
        $event = Event::factory()->create();
        $other_shift = EventShift::factory()->forEvent(Event::factory()->create())->create();

        $subject = new UpdateEventCharity($event, [$other_shift->id => ['charity_name' => 'Nope']]);
        $subject->handle();

        $this->assertNotSame('Nope', $other_shift->refresh()->charity_name);
    }
}
