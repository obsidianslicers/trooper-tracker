<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTroopersSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_status_from_form_submission(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create(['event_id' => $event->id]);

        $trooper = Trooper::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $troopers_data = [
            'troopers' => [
                $event_trooper->id => [
                    'status' => EventTrooperStatus::GOING->value,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.troopers', $event), $troopers_data);

        // Assert
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $response->assertRedirect(route('admin.events.troopers', $event));
    }

    public function test_invoke_updates_multiple_trooper_statuses(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create(['event_id' => $event->id]);

        $trooper_1 = Trooper::factory()->create();
        $trooper_2 = Trooper::factory()->create();

        $event_trooper_1 = EventTrooper::factory()->create([
            'trooper_id' => $trooper_1->id,
            'event_shift_id' => $shift->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $event_trooper_2 = EventTrooper::factory()->create([
            'trooper_id' => $trooper_2->id,
            'event_shift_id' => $shift->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $troopers_data = [
            'troopers' => [
                $event_trooper_1->id => [
                    'status' => EventTrooperStatus::GOING->value,
                ],
                $event_trooper_2->id => [
                    'status' => EventTrooperStatus::STAND_BY->value,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.troopers', $event), $troopers_data);

        // Assert
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper_1->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper_2->id,
            'status' => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_invoke_updates_trooper_to_declined_status(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create(['event_id' => $event->id]);

        $trooper = Trooper::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $troopers_data = [
            'troopers' => [
                $event_trooper->id => [
                    'status' => EventTrooperStatus::CANCELLED->value,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.troopers', $event), $troopers_data);

        // Assert
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper->id,
            'status' => EventTrooperStatus::CANCELLED->value,
        ]);
    }

    public function test_invoke_ignores_invalid_event_trooper_ids(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $troopers_data = [
            'troopers' => [
                99999 => [
                    'status' => EventTrooperStatus::GOING->value,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.troopers', $event), $troopers_data);

        // Assert
        $response->assertRedirect(route('admin.events.troopers', $event));
        $this->assertDatabaseMissing(EventTrooper::class, [
            'id' => 99999,
        ]);
    }

    public function test_invoke_only_updates_troopers_for_specified_event(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $event_1 = Event::factory()->create();
        $event_2 = Event::factory()->create();

        $shift_1 = EventShift::factory()->create(['event_id' => $event_1->id]);
        $shift_2 = EventShift::factory()->create(['event_id' => $event_2->id]);

        $trooper = Trooper::factory()->create();

        $event_trooper_1 = EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift_1->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $event_trooper_2 = EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift_2->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $troopers_data = [
            'troopers' => [
                $event_trooper_1->id => [
                    'status' => EventTrooperStatus::GOING->value,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($admin)->post(route('admin.events.troopers', $event_1), $troopers_data);

        // Assert
        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper_1->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);

        $this->assertDatabaseHas(EventTrooper::class, [
            'id' => $event_trooper_2->id,
            'status' => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_denies_access_to_moderator_without_permission(): void
    {
        // Arrange
        $organization_1 = Organization::factory()->create();
        $organization_2 = Organization::factory()->create();
        $moderator = Trooper::factory()->asModerator()->withAssignment($organization_1, member: true)->create();

        $event = Event::factory()->withOrganization($organization_2)->create();

        $shift = EventShift::factory()->create(['event_id' => $event->id]);
        $trooper = Trooper::factory()->create();
        $event_trooper = EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift->id,
        ]);

        $troopers_data = [
            'troopers' => [
                $event_trooper->id => [
                    'status' => EventTrooperStatus::GOING->value,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.events.troopers', $event), $troopers_data);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_denies_access_to_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->create(['event_id' => $event->id]);

        $event_trooper = EventTrooper::factory()->create([
            'trooper_id' => $trooper->id,
            'event_shift_id' => $shift->id,
        ]);

        $troopers_data = [
            'troopers' => [
                $event_trooper->id => [
                    'status' => EventTrooperStatus::GOING->value,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.events.troopers', $event), $troopers_data);

        // Assert
        $response->assertForbidden();
    }
}
