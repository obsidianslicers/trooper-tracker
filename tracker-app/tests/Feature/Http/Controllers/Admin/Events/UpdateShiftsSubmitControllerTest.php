<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateShiftsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_new_shift_from_form_submission(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $shift_date = Carbon::now()->addDays(10)->format('Y-m-d');
        $shifts_data = [
            'shifts' => [
                0 => [
                    'date' => $shift_date,
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.shifts', $event), $shifts_data);

        // Assert
        $this->assertDatabaseHas(EventShift::class, [
            'event_id' => $event->id,
        ]);

        $shift = EventShift::where('event_id', $event->id)->first();
        $this->assertEquals($shift_date . ' 10:00', $shift->shift_starts_at->format('Y-m-d H:i'));
        $this->assertEquals($shift_date . ' 14:00', $shift->shift_ends_at->format('Y-m-d H:i'));

        $response->assertRedirect(route('admin.events.shifts', $event));
    }

    public function test_invoke_updates_existing_shift(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $existing_shift = EventShift::factory()->create([
            'event_id' => $event->id,
            'shift_starts_at' => Carbon::now()->addDays(10)->setTime(10, 0),
            'shift_ends_at' => Carbon::now()->addDays(10)->setTime(14, 0),
        ]);

        $shift_date = Carbon::now()->addDays(10)->format('Y-m-d');
        $shifts_data = [
            'shifts' => [
                $existing_shift->id => [
                    'date' => $shift_date,
                    'starts_at' => '12:00',
                    'ends_at' => '16:00',
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.shifts', $event), $shifts_data);

        // Assert
        $this->assertDatabaseHas(EventShift::class, [
            'id' => $existing_shift->id,
            'event_id' => $event->id,
        ]);

        $updated_shift = EventShift::find($existing_shift->id);
        $this->assertEquals($shift_date . ' 12:00', $updated_shift->shift_starts_at->format('Y-m-d H:i'));
        $this->assertEquals($shift_date . ' 16:00', $updated_shift->shift_ends_at->format('Y-m-d H:i'));
    }

    public function test_invoke_creates_multiple_shifts(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $shift_date = Carbon::now()->addDays(10)->format('Y-m-d');
        $shifts_data = [
            'shifts' => [
                -1 => [
                    'date' => $shift_date,
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                ],
                -2 => [
                    'date' => $shift_date,
                    'starts_at' => '15:00',
                    'ends_at' => '19:00',
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.shifts', $event), $shifts_data);

        // Assert
        $this->assertCount(2, EventShift::where('event_id', $event->id)->get());
    }

    public function test_invoke_parses_date_and_time_strings_correctly(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $shifts_data = [
            'shifts' => [
                0 => [
                    'date' => '2025-06-15',
                    'starts_at' => '09:30',
                    'ends_at' => '13:45',
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.shifts', $event), $shifts_data);

        // Assert
        $shift = EventShift::where('event_id', $event->id)->first();
        $this->assertEquals('2025-06-15 09:30', $shift->shift_starts_at->format('Y-m-d H:i'));
        $this->assertEquals('2025-06-15 13:45', $shift->shift_ends_at->format('Y-m-d H:i'));
    }

    public function test_invoke_denies_access_to_moderator_without_permission(): void
    {
        // Arrange
        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($organization_1, member: true)
            ->create();

        $event = Event::factory()->withOrganization($organization_2)->create();

        $shifts_data = [
            'shifts' => [
                0 => [
                    'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.events.shifts', $event), $shifts_data);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();

        $shifts_data = [
            'shifts' => [
                0 => [
                    'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
                    'starts_at' => '10:00',
                    'ends_at' => '14:00',
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.events.shifts', $event), $shifts_data);

        // Assert
        $response->assertForbidden();
    }
}
